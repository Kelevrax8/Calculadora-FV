<?php
// ─────────────────────────────────────────────────────────────────────────────
//  get_pv_modules.php
//  GET — returns all PV modules with manufacturer name
// ─────────────────────────────────────────────────────────────────────────────

header('Content-Type: application/json');
define('APP', true);
require_once '../../php_actions/conn_db.php';

$db = db();

$result = $db->query(
    'SELECT
        m.id,
        mf.name  AS manufacturer,
        m.model,
        m.technology,
        m.pmax_stc,
        m.voc_stc,
        m.isc_stc,
        m.vmpp_stc,
        m.imp_stc,
        m.temp_coeff_voc,
        m.temp_coeff_pmax,
        m.length_m,
        m.width_m
     FROM pv_modules m
     JOIN manufacturers mf ON mf.id = m.manufacturer_id
     ORDER BY mf.name, m.pmax_stc'
);

if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al consultar módulos: ' . $db->error]);
    exit;
}

$modules = [];
while ($row = $result->fetch_assoc()) {
    // Cast numeric fields to their proper types
    $modules[] = [
        'id'              => (int)   $row['id'],
        'manufacturer'    =>         $row['manufacturer'],
        'model'           =>         $row['model'],
        'technology'      =>         $row['technology'],
        'pmax_stc'        => (float) $row['pmax_stc'],
        'voc_stc'         => (float) $row['voc_stc'],
        'isc_stc'         => (float) $row['isc_stc'],
        'vmpp_stc'        => (float) $row['vmpp_stc'],
        'imp_stc'         => (float) $row['imp_stc'],
        'temp_coeff_voc'  => (float) $row['temp_coeff_voc'],   // stored as %/°C
        'temp_coeff_pmax' => (float) $row['temp_coeff_pmax'],  // stored as %/°C
        'length_m'        => (float) $row['length_m'],
        'width_m'         => (float) $row['width_m'],
    ];
}

echo json_encode($modules);
