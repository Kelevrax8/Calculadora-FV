<?php
// ─────────────────────────────────────────────────────────────────────────────
//  get_inverters.php
//  GET — returns all inverters with manufacturer name
// ─────────────────────────────────────────────────────────────────────────────

header('Content-Type: application/json');
define('APP', true);
require_once '../../php_actions/conn_db.php';

$db = db();

$result = $db->query(
    'SELECT
        i.id,
        mf.name                         AS manufacturer,
        i.model,
        i.phase_type,
        i.pmax_dc_input,
        i.max_dc_voltage,
        i.mppt_voltage_min,
        i.mppt_voltage_max,
        i.startup_voltage,
        i.max_input_current_per_mppt,
        i.max_short_circuit_current,
        i.nominal_ac_power,
        i.ac_voltage_nominal,
        i.efficiency_weighted,
        i.mppt_count
     FROM inverters i
     JOIN manufacturers mf ON mf.id = i.manufacturer_id
     ORDER BY mf.name, i.nominal_ac_power'
);

if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al consultar inversores: ' . $db->error]);
    exit;
}

$inverters = [];
while ($row = $result->fetch_assoc()) {
    foreach ([
        'pmax_dc_input', 'max_dc_voltage', 'mppt_voltage_min', 'mppt_voltage_max',
        'startup_voltage', 'max_input_current_per_mppt', 'max_short_circuit_current',
        'nominal_ac_power', 'ac_voltage_nominal', 'efficiency_weighted',
    ] as $field) {
        $row[$field] = (float)$row[$field];
    }
    $row['id']         = (int)$row['id'];
    $row['mppt_count'] = (int)$row['mppt_count'];
    $inverters[] = $row;
}

echo json_encode($inverters);
