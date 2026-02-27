<?php
// ─────────────────────────────────────────────────────────────────────────────
//  get_climate_data.php
//  POST { lat, lng }
//  Returns climatology data for the given coordinates.
//  - If already cached in DB → returns from DB immediately.
//  - If not → calls NASA POWER API, stores result, then returns it.
// ─────────────────────────────────────────────────────────────────────────────

header('Content-Type: application/json');
define('APP', true);
require_once '../../php_actions/conn_db.php';

// ── Input validation ─────────────────────────────────────────────────────────
$lat = isset($_POST['lat']) ? (float) $_POST['lat'] : null;
$lng = isset($_POST['lng']) ? (float) $_POST['lng'] : null;

if ($lat === null || $lng === null || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
    http_response_code(400);
    echo json_encode(['error' => 'Coordenadas inválidas.']);
    exit;
}

// Round to 2 decimal places to match DB UNIQUE constraint precision
$lat = round($lat, 2);
$lng = round($lng, 2);

$db = db();

// ── Check cache ──────────────────────────────────────────────────────────────
$stmt = $db->prepare('SELECT id, absolute_min_temp, absolute_max_temp FROM climatology_locations WHERE latitude = ? AND longitude = ?');
$stmt->bind_param('dd', $lat, $lng);
$stmt->execute();
$loc = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($loc) {
    // Location already cached — fetch monthly rows
    $monthly = fetch_monthly($db, $loc['id']);
    echo json_encode([
        'source'   => 'cache',
        'lat'      => $lat,
        'lng'      => $lng,
        'hsp'      => worst_month_hsp($monthly),
        'tmin'     => (float) $loc['absolute_min_temp'],
        'tmax'     => (float) $loc['absolute_max_temp'],
        'monthly'  => $monthly,
    ]);
    exit;
}

// ── Call NASA POWER API ──────────────────────────────────────────────────────
$api_url = sprintf(
    'https://power.larc.nasa.gov/api/temporal/climatology/point' .
    '?parameters=ALLSKY_SFC_SW_DWN,T2M,T2M_MAX,T2M_MIN' .
    '&community=RE&latitude=%s&longitude=%s&format=JSON',
    $lat, $lng
);

$ctx = stream_context_create(['http' => ['timeout' => 20]]);
$raw = @file_get_contents($api_url, false, $ctx);

if ($raw === false) {
    http_response_code(502);
    echo json_encode(['error' => 'No se pudo conectar con la API de NASA POWER. Intenta de nuevo o ingresa los datos manualmente.']);
    exit;
}

$data = json_decode($raw, true);
$params = $data['properties']['parameter'] ?? null;

if (!$params || !isset($params['ALLSKY_SFC_SW_DWN'], $params['T2M'], $params['T2M_MAX'], $params['T2M_MIN'])) {
    http_response_code(502);
    echo json_encode(['error' => 'Respuesta inesperada de NASA POWER.']);
    exit;
}

// ── Parse monthly values ─────────────────────────────────────────────────────
$month_keys = ['JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC'];

$monthly = [];
foreach ($month_keys as $i => $key) {
    $monthly[] = [
        'month'         => $i + 1,
        'ghi'           => (float) $params['ALLSKY_SFC_SW_DWN'][$key],
        't2m_avg'       => (float) $params['T2M'][$key],
        't2m_max'       => (float) $params['T2M_MAX'][$key],
        't2m_min'       => (float) $params['T2M_MIN'][$key],
    ];
}

// Derive absolute extremes for the location row
$abs_tmin = min(array_column($monthly, 't2m_min'));
$abs_tmax = max(array_column($monthly, 't2m_max'));

// ── Persist to DB ─────────────────────────────────────────────────────────────
$db->begin_transaction();
try {
    // Insert location
    $stmt = $db->prepare(
        'INSERT INTO climatology_locations (latitude, longitude, absolute_min_temp, absolute_max_temp, data_source)
         VALUES (?, ?, ?, ?, "NASA POWER")'
    );
    $stmt->bind_param('dddd', $lat, $lng, $abs_tmin, $abs_tmax);
    $stmt->execute();
    $location_id = $db->insert_id;
    $stmt->close();

    // Insert 12 monthly rows
    $stmt = $db->prepare(
        'INSERT INTO climatology_monthly (location_id, month, ghi_kwh_m2_day, t2m_avg, t2m_max, t2m_min)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    foreach ($monthly as $row) {
        $stmt->bind_param('iidddd', $location_id, $row['month'], $row['ghi'], $row['t2m_avg'], $row['t2m_max'], $row['t2m_min']);
        $stmt->execute();
    }
    $stmt->close();

    $db->commit();
} catch (Exception $e) {
    $db->rollback();
    http_response_code(500);
    echo json_encode(['error' => 'Error al guardar los datos climáticos: ' . $e->getMessage()]);
    exit;
}

// ── Return result ─────────────────────────────────────────────────────────────
echo json_encode([
    'source'  => 'api',
    'lat'     => $lat,
    'lng'     => $lng,
    'hsp'     => worst_month_hsp($monthly),
    'tmin'    => $abs_tmin,
    'tmax'    => $abs_tmax,
    'monthly' => $monthly,
]);

// ── Helpers ───────────────────────────────────────────────────────────────────
function worst_month_hsp(array $monthly): float {
    return min(array_column($monthly, 'ghi'));
}

function fetch_monthly(mysqli $db, int $location_id): array {
    $stmt = $db->prepare(
        'SELECT month, ghi_kwh_m2_day AS ghi, t2m_avg, t2m_max, t2m_min
         FROM climatology_monthly WHERE location_id = ? ORDER BY month'
    );
    $stmt->bind_param('i', $location_id);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    // Cast to float
    return array_map(fn($r) => array_map('floatval', $r), $rows);
}
