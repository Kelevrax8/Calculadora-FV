<?php
require_once '../php_actions/conn_db.php';

// ─────────────────────────────────────────────────────────────────────────────
//  AJAX handler — exits before any HTML is rendered
// ─────────────────────────────────────────────────────────────────────────────
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    $action = $_GET['action'];
    $db     = db();

    $page   = max(1, (int)($_GET['page'] ?? 1));
    $limit  = 5;
    $offset = ($page - 1) * $limit;
    $q      = '%' . $db->real_escape_string($_GET['q'] ?? '') . '%';

    // ── MANUFACTURERS ────────────────────────────────────────────────────────
    if ($action === 'list_manufacturers') {
        $total = $db->query("SELECT COUNT(*) FROM manufacturers WHERE name LIKE '$q'")->fetch_row()[0];
        $rows  = $db->query("SELECT id, name, DATE_FORMAT(created_at,'%d/%m/%Y') AS created_at
                              FROM manufacturers WHERE name LIKE '$q'
                              ORDER BY name LIMIT $limit OFFSET $offset");
        $data = [];
        while ($r = $rows->fetch_assoc()) $data[] = $r;
        echo json_encode(['total' => (int)$total, 'data' => $data]);
        exit;
    }

    if ($action === 'save_manufacturer') {
        $b    = json_decode(file_get_contents('php://input'), true);
        $name = $db->real_escape_string(trim($b['name'] ?? ''));
        $id   = (int)($b['id'] ?? 0);
        if ($name === '') { echo json_encode(['error' => 'Nombre requerido']); exit; }
        if ($id > 0) {
            $db->query("UPDATE manufacturers SET name='$name' WHERE id=$id");
        } else {
            $db->query("INSERT INTO manufacturers (name) VALUES ('$name')");
        }
        echo $db->error ? json_encode(['error' => $db->error]) : json_encode(['ok' => true]);
        exit;
    }

    if ($action === 'delete_manufacturer') {
        $b  = json_decode(file_get_contents('php://input'), true);
        $id = (int)($b['id'] ?? 0);
        $db->query("DELETE FROM manufacturers WHERE id=$id");
        echo $db->error ? json_encode(['error' => $db->error]) : json_encode(['ok' => true]);
        exit;
    }

    if ($action === 'manufacturers_select') {
        $rows = $db->query("SELECT id, name FROM manufacturers ORDER BY name");
        $data = [];
        while ($r = $rows->fetch_assoc()) $data[] = $r;
        echo json_encode($data);
        exit;
    }

    // ── PV MODULES ───────────────────────────────────────────────────────────
    if ($action === 'list_modules') {
        $total = $db->query("SELECT COUNT(*) FROM pv_modules m
                              JOIN manufacturers mf ON m.manufacturer_id = mf.id
                              WHERE m.model LIKE '$q' OR mf.name LIKE '$q'")->fetch_row()[0];
        $rows  = $db->query("SELECT m.id, mf.name AS manufacturer, m.manufacturer_id, m.model,
                               m.technology, m.pmax_stc, m.voc_stc, m.isc_stc, m.vmpp_stc, m.imp_stc,
                               m.temp_coeff_voc, m.temp_coeff_pmax, m.length_m, m.width_m,
                               DATE_FORMAT(m.created_at,'%d/%m/%Y') AS created_at
                              FROM pv_modules m
                              JOIN manufacturers mf ON m.manufacturer_id = mf.id
                              WHERE m.model LIKE '$q' OR mf.name LIKE '$q'
                              ORDER BY mf.name, m.model LIMIT $limit OFFSET $offset");
        $data = [];
        while ($r = $rows->fetch_assoc()) $data[] = $r;
        echo json_encode(['total' => (int)$total, 'data' => $data]);
        exit;
    }

    if ($action === 'save_module') {
        $b   = json_decode(file_get_contents('php://input'), true);
        $id  = (int)($b['id'] ?? 0);
        $mid = (int)($b['manufacturer_id'] ?? 0);
        $model = $db->real_escape_string(trim($b['model'] ?? ''));
        $tech  = $db->real_escape_string($b['technology'] ?? '');
        $pmax  = (float)($b['pmax_stc']        ?? 0);
        $voc   = (float)($b['voc_stc']         ?? 0);
        $isc   = (float)($b['isc_stc']         ?? 0);
        $vmpp  = (float)($b['vmpp_stc']        ?? 0);
        $imp   = (float)($b['imp_stc']         ?? 0);
        $tcv   = (float)($b['temp_coeff_voc']  ?? 0);
        $tcp   = (float)($b['temp_coeff_pmax'] ?? 0);
        $len   = (float)($b['length_m']        ?? 0);
        $wid   = (float)($b['width_m']         ?? 0);
        if ($tcv >= 0) { echo json_encode(['error' => 'El coeficiente de temperatura de Voc (β Voc) debe ser negativo.']); exit; }
        if ($tcp >= 0) { echo json_encode(['error' => 'El coeficiente de temperatura de Pmax (β Pmax) debe ser negativo.']); exit; }
        if ($id > 0) {
            $db->query("UPDATE pv_modules SET manufacturer_id=$mid, model='$model', technology='$tech',
                         pmax_stc=$pmax, voc_stc=$voc, isc_stc=$isc, vmpp_stc=$vmpp, imp_stc=$imp,
                         temp_coeff_voc=$tcv, temp_coeff_pmax=$tcp, length_m=$len, width_m=$wid
                         WHERE id=$id");
        } else {
            $db->query("INSERT INTO pv_modules
                         (manufacturer_id,model,technology,pmax_stc,voc_stc,isc_stc,vmpp_stc,imp_stc,
                          temp_coeff_voc,temp_coeff_pmax,length_m,width_m)
                         VALUES ($mid,'$model','$tech',$pmax,$voc,$isc,$vmpp,$imp,$tcv,$tcp,$len,$wid)");
        }
        echo $db->error ? json_encode(['error' => $db->error]) : json_encode(['ok' => true]);
        exit;
    }

    if ($action === 'delete_module') {
        $b  = json_decode(file_get_contents('php://input'), true);
        $id = (int)($b['id'] ?? 0);
        $db->query("DELETE FROM pv_modules WHERE id=$id");
        echo $db->error ? json_encode(['error' => $db->error]) : json_encode(['ok' => true]);
        exit;
    }

    // ── INVERTERS ────────────────────────────────────────────────────────────
    if ($action === 'list_inverters') {
        $total = $db->query("SELECT COUNT(*) FROM inverters i
                              JOIN manufacturers mf ON i.manufacturer_id = mf.id
                              WHERE i.model LIKE '$q' OR mf.name LIKE '$q'")->fetch_row()[0];
        $rows  = $db->query("SELECT i.id, mf.name AS manufacturer, i.manufacturer_id, i.model,
                               i.pmax_dc_input, i.max_dc_voltage, i.max_input_current,
                               i.max_short_circuit_current, i.nominal_ac_power, i.mppt_count,
                               DATE_FORMAT(i.created_at,'%d/%m/%Y') AS created_at
                              FROM inverters i
                              JOIN manufacturers mf ON i.manufacturer_id = mf.id
                              WHERE i.model LIKE '$q' OR mf.name LIKE '$q'
                              ORDER BY mf.name, i.model LIMIT $limit OFFSET $offset");
        $data = [];
        while ($r = $rows->fetch_assoc()) $data[] = $r;
        echo json_encode(['total' => (int)$total, 'data' => $data]);
        exit;
    }

    if ($action === 'save_inverter') {
        $b    = json_decode(file_get_contents('php://input'), true);
        $id   = (int)($b['id'] ?? 0);
        $mid  = (int)($b['manufacturer_id'] ?? 0);
        $model = $db->real_escape_string(trim($b['model'] ?? ''));
        $pmax  = (float)($b['pmax_dc_input']            ?? 0);
        $vmax  = (float)($b['max_dc_voltage']            ?? 0);
        $imax  = (float)($b['max_input_current']         ?? 0);
        $isc   = (float)($b['max_short_circuit_current'] ?? 0);
        $pac   = (float)($b['nominal_ac_power']          ?? 0);
        $mppt  = (int)($b['mppt_count']                  ?? 0);
        if ($id > 0) {
            $db->query("UPDATE inverters SET manufacturer_id=$mid, model='$model',
                         pmax_dc_input=$pmax, max_dc_voltage=$vmax, max_input_current=$imax,
                         max_short_circuit_current=$isc, nominal_ac_power=$pac, mppt_count=$mppt
                         WHERE id=$id");
        } else {
            $db->query("INSERT INTO inverters
                         (manufacturer_id,model,pmax_dc_input,max_dc_voltage,max_input_current,
                          max_short_circuit_current,nominal_ac_power,mppt_count)
                         VALUES ($mid,'$model',$pmax,$vmax,$imax,$isc,$pac,$mppt)");
        }
        echo $db->error ? json_encode(['error' => $db->error]) : json_encode(['ok' => true]);
        exit;
    }

    if ($action === 'delete_inverter') {
        $b  = json_decode(file_get_contents('php://input'), true);
        $id = (int)($b['id'] ?? 0);
        $db->query("DELETE FROM inverters WHERE id=$id");
        echo $db->error ? json_encode(['error' => $db->error]) : json_encode(['ok' => true]);
        exit;
    }

    echo json_encode(['error' => 'Unknown action']);
    exit;
}

// ─────────────────────────────────────────────────────────────────────────────
//  Page render
// ─────────────────────────────────────────────────────────────────────────────
define('APP', true);
$pageTitle = 'Inventario - IPTE';
include '../components/header-dashboard.php';
?>

  <main class="flex-1 overflow-y-auto">
    <div class="max-w-7xl mx-auto px-6 py-10">

      <!-- Page header -->
      <div class="mb-4">
        <h1 class="text-2xl font-bold text-Ipteblue">Inventario</h1>
        <p class="text-sm text-gray-400 mt-1">Gestión de equipos y componentes fotovoltaicos</p>
      </div>

      <!-- Tab bar -->
      <div class="border-b border-gray-200 mb-6">
        <nav class="-mb-px flex gap-1" id="inv-tabs">
          <button onclick="switchTab('manufacturadores')" id="tab-manufacturadores"
            class="tab-btn px-5 py-2.5 text-sm font-medium rounded-t-md border border-b-0 transition-colors
                   bg-white border-gray-200 text-Ipteblue">
            Manufacturadores
          </button>
          <button onclick="switchTab('modulos')" id="tab-modulos"
            class="tab-btn px-5 py-2.5 text-sm font-medium rounded-t-md border border-b-0 transition-colors
                   bg-gray-50 border-transparent text-gray-500 hover:text-Ipteblue hover:bg-white hover:border-gray-200">
            Módulos FV
          </button>
          <button onclick="switchTab('inversores')" id="tab-inversores"
            class="tab-btn px-5 py-2.5 text-sm font-medium rounded-t-md border border-b-0 transition-colors
                   bg-gray-50 border-transparent text-gray-500 hover:text-Ipteblue hover:bg-white hover:border-gray-200">
            Inversores
          </button>
        </nav>
      </div>

      <!-- ── MANUFACTURERS PANEL ─────────────────────────────────────────── -->
      <div id="panel-manufacturadores" class="tab-panel">
        <div class="flex items-center justify-between mb-4 gap-3">
          <input id="search-manufacturadores" type="text" placeholder="Buscar por nombre…"
            oninput="loadTable('manufacturadores',1)"
            class="border border-gray-200 rounded-lg px-3 py-2 text-sm w-64 focus:outline-none focus:ring-2 focus:ring-Ipteblue2">
          <button onclick="openModal('manufacturadores')"
            class="flex items-center gap-1.5 bg-Ipteblue2 text-white text-sm font-medium px-4 py-2 rounded-lg hover:opacity-90 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Agregar
          </button>
        </div>
        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wide">
              <tr>
                <th class="px-4 py-3 text-left">Nombre</th>
                <th class="px-4 py-3 text-center">Acciones</th>
              </tr>
            </thead>
            <tbody id="tbody-manufacturadores" class="divide-y divide-gray-100 text-gray-700"></tbody>
          </table>
        </div>
        <div id="pagination-manufacturadores" class="flex items-center justify-between mt-4 text-sm text-gray-500"></div>
      </div>

      <!-- ── PV MODULES PANEL ────────────────────────────────────────────── -->
      <div id="panel-modulos" class="tab-panel hidden">
        <div class="flex items-center justify-between mb-4 gap-3">
          <input id="search-modulos" type="text" placeholder="Buscar por fabricante o modelo…"
            oninput="loadTable('modulos',1)"
            class="border border-gray-200 rounded-lg px-3 py-2 text-sm w-72 focus:outline-none focus:ring-2 focus:ring-Ipteblue2">
          <button onclick="openModal('modulos')"
            class="flex items-center gap-1.5 bg-Ipteblue2 text-white text-sm font-medium px-4 py-2 rounded-lg hover:opacity-90 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Agregar
          </button>
        </div>
        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
          <table class="w-full text-sm whitespace-nowrap">
            <thead class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wide">
              <tr>
                <th class="px-4 py-3 text-left">Fabricante</th>
                <th class="px-4 py-3 text-left">Modelo</th>
                <th class="px-4 py-3 text-left">Tecnología</th>
                <th class="px-4 py-3 text-right">Pmax (W)</th>
                <th class="px-4 py-3 text-right">Voc (V)</th>
                <th class="px-4 py-3 text-right">Isc (A)</th>
                <th class="px-4 py-3 text-right">Vmpp (V)</th>
                <th class="px-4 py-3 text-right">Imp (A)</th>
                <th class="px-4 py-3 text-right">β Voc (%/°C)</th>
                <th class="px-4 py-3 text-right">β Pmax (%/°C)</th>
                <th class="px-4 py-3 text-right">Largo (m)</th>
                <th class="px-4 py-3 text-right">Ancho (m)</th>
                <th class="px-4 py-3 text-center">Acciones</th>
              </tr>
            </thead>
            <tbody id="tbody-modulos" class="divide-y divide-gray-100 text-gray-700"></tbody>
          </table>
        </div>
        <div id="pagination-modulos" class="flex items-center justify-between mt-4 text-sm text-gray-500"></div>
      </div>

      <!-- ── INVERTERS PANEL ─────────────────────────────────────────────── -->
      <div id="panel-inversores" class="tab-panel hidden">
        <div class="flex items-center justify-between mb-4 gap-3">
          <input id="search-inversores" type="text" placeholder="Buscar por fabricante o modelo…"
            oninput="loadTable('inversores',1)"
            class="border border-gray-200 rounded-lg px-3 py-2 text-sm w-72 focus:outline-none focus:ring-2 focus:ring-Ipteblue2">
          <button onclick="openModal('inversores')"
            class="flex items-center gap-1.5 bg-Ipteblue2 text-white text-sm font-medium px-4 py-2 rounded-lg hover:opacity-90 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Agregar
          </button>
        </div>
        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
          <table class="w-full text-sm whitespace-nowrap">
            <thead class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wide">
              <tr>
                <th class="px-4 py-3 text-left">Fabricante</th>
                <th class="px-4 py-3 text-left">Modelo</th>
                <th class="px-4 py-3 text-right">Pmax DC (W)</th>
                <th class="px-4 py-3 text-right">V DC máx (V)</th>
                <th class="px-4 py-3 text-right">I entrada máx (A)</th>
                <th class="px-4 py-3 text-right">Isc máx (A)</th>
                <th class="px-4 py-3 text-right">P AC nom (W)</th>
                <th class="px-4 py-3 text-right">MPPT</th>
                <th class="px-4 py-3 text-center">Acciones</th>
              </tr>
            </thead>
            <tbody id="tbody-inversores" class="divide-y divide-gray-100 text-gray-700"></tbody>
          </table>
        </div>
        <div id="pagination-inversores" class="flex items-center justify-between mt-4 text-sm text-gray-500"></div>
      </div>

    </div>
  </main>

  <!-- ══════════════════════════════════════════════════════════════════════ -->
  <!--  MODAL                                                                 -->
  <!-- ══════════════════════════════════════════════════════════════════════ -->
  <div id="modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col">

      <!-- Header -->
      <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
        <h2 id="modal-title" class="text-base font-bold text-Ipteblue"></h2>
        <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 transition">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>

      <!-- Body (scrollable) -->
      <div class="overflow-y-auto px-6 py-5 flex-1">

        <!-- Error banner -->
        <div id="modal-error" class="hidden mb-4 bg-red-50 text-red-700 text-sm px-4 py-2 rounded-lg border border-red-200"></div>

        <!-- FORM: Manufacturadores -->
        <form id="form-manufacturadores" class="entity-form hidden space-y-4" onsubmit="return false">
          <input type="hidden" id="man-id">
          <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1">Nombre <span class="text-red-500">*</span></label>
            <input type="text" id="man-name" required
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-Ipteblue2">
          </div>
        </form>

        <!-- FORM: Módulos FV -->
        <form id="form-modulos" class="entity-form hidden" onsubmit="return false">
          <input type="hidden" id="mod-id">
          <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2 grid grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Fabricante <span class="text-red-500">*</span></label>
                <select id="mod-manufacturer" required
                  class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-Ipteblue2">
                  <option value="">— Seleccionar —</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Modelo <span class="text-red-500">*</span></label>
                <input type="text" id="mod-model" required
                  class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-Ipteblue2">
              </div>
            </div>
            <div class="col-span-2">
              <label class="block text-xs font-semibold text-gray-500 mb-1">Tecnología <span class="text-red-500">*</span></label>
              <select id="mod-technology" required
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-Ipteblue2">
                <option value="">— Seleccionar —</option>
                <option value="Monocrystalline">Monocristalino</option>
                <option value="Polycrystalline">Policristalino</option>
                <option value="Thin Film">Película delgada</option>
                <option value="Other">Otro</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-500 mb-1">Pmax STC (W)</label>
              <input type="number" step="0.01" id="mod-pmax_stc"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-Ipteblue2">
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-500 mb-1">Voc STC (V)</label>
              <input type="number" step="0.01" id="mod-voc_stc"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-Ipteblue2">
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-500 mb-1">Isc STC (A)</label>
              <input type="number" step="0.01" id="mod-isc_stc"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-Ipteblue2">
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-500 mb-1">Vmpp STC (V)</label>
              <input type="number" step="0.01" id="mod-vmpp_stc"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-Ipteblue2">
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-500 mb-1">Imp STC (A)</label>
              <input type="number" step="0.01" id="mod-imp_stc"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-Ipteblue2">
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-500 mb-1">β Voc (%/°C) <span class="font-normal text-gray-400">(debe ser negativo)</span></label>
              <input type="number" step="0.0001" max="-0.0001" id="mod-temp_coeff_voc"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-Ipteblue2">
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-500 mb-1">β Pmax (%/°C) <span class="font-normal text-gray-400">(debe ser negativo)</span></label>
              <input type="number" step="0.0001" max="-0.0001" id="mod-temp_coeff_pmax"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-Ipteblue2">
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-500 mb-1">Largo (m)</label>
              <input type="number" step="0.01" id="mod-length_m"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-Ipteblue2">
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-500 mb-1">Ancho (m)</label>
              <input type="number" step="0.01" id="mod-width_m"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-Ipteblue2">
            </div>
          </div>
        </form>

        <!-- FORM: Inversores -->
        <form id="form-inversores" class="entity-form hidden" onsubmit="return false">
          <input type="hidden" id="inv-id">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-gray-500 mb-1">Fabricante <span class="text-red-500">*</span></label>
              <select id="inv-manufacturer" required
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-Ipteblue2">
                <option value="">— Seleccionar —</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-500 mb-1">Modelo <span class="text-red-500">*</span></label>
              <input type="text" id="inv-model" required
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-Ipteblue2">
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-500 mb-1">Pmax entrada DC (W)</label>
              <input type="number" step="0.01" id="inv-pmax_dc_input"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-Ipteblue2">
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-500 mb-1">V DC máxima (V)</label>
              <input type="number" step="0.01" id="inv-max_dc_voltage"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-Ipteblue2">
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-500 mb-1">I entrada máxima (A)</label>
              <input type="number" step="0.01" id="inv-max_input_current"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-Ipteblue2">
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-500 mb-1">Isc máxima (A)</label>
              <input type="number" step="0.01" id="inv-max_short_circuit_current"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-Ipteblue2">
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-500 mb-1">P AC nominal (W)</label>
              <input type="number" step="0.01" id="inv-nominal_ac_power"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-Ipteblue2">
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-500 mb-1">Cantidad MPPT</label>
              <input type="number" step="1" min="1" id="inv-mppt_count"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-Ipteblue2">
            </div>
          </div>
        </form>

      </div>

      <!-- Footer -->
      <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-100">
        <button onclick="closeModal()"
          class="px-4 py-2 text-sm font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
          Cancelar
        </button>
        <button id="modal-save-btn" onclick="saveEntity()"
          class="px-5 py-2 text-sm font-medium text-white bg-Ipteblue2 rounded-lg hover:opacity-90 transition">
          Guardar
        </button>
      </div>
    </div>
  </div>

  <!-- ══════════════════════════════════════════════════════════════════════ -->
  <!--  JAVASCRIPT                                                             -->
  <!-- ══════════════════════════════════════════════════════════════════════ -->
  <script>
    // ── Tab switching ─────────────────────────────────────────────────────
    const ACTIVE_BTN   = ['bg-white','border-gray-200','text-Ipteblue'];
    const INACTIVE_BTN = ['bg-gray-50','border-transparent','text-gray-500',
                          'hover:text-Ipteblue','hover:bg-white','hover:border-gray-200'];

    // ── Table state ───────────────────────────────────────────────────────
    const state = {
      manufacturadores: { page: 1, total: 0, loaded: false },
      modulos:          { page: 1, total: 0, loaded: false },
      inversores:       { page: 1, total: 0, loaded: false },
    };

    function switchTab(name) {
      document.querySelectorAll('.tab-panel').forEach(p => p.classList.add('hidden'));
      document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove(...ACTIVE_BTN);
        btn.classList.add(...INACTIVE_BTN);
      });
      document.getElementById('panel-' + name).classList.remove('hidden');
      const activeBtn = document.getElementById('tab-' + name);
      activeBtn.classList.remove(...INACTIVE_BTN);
      activeBtn.classList.add(...ACTIVE_BTN);

      // Load data the first time a tab is opened
      if (!state[name].loaded) {
        loadTable(name, 1);
      }
    }

    // ── Load & render table ───────────────────────────────────────────────
    async function loadTable(tab, page = 1) {
      state[tab].page = page;
      const q   = document.getElementById('search-' + tab)?.value ?? '';
      const map = { manufacturadores:'list_manufacturers', modulos:'list_modules', inversores:'list_inverters' };
      const res = await fetch(`inventario.php?action=${map[tab]}&page=${page}&q=${encodeURIComponent(q)}`);
      const json = await res.json();
      state[tab].total = json.total;
      state[tab].loaded = true;
      renderRows(tab, json.data);
      renderPagination(tab, json.total, page);
    }

    function renderRows(tab, data) {
      const tbody = document.getElementById('tbody-' + tab);
      if (!data.length) {
        tbody.innerHTML = `<tr><td colspan="20" class="px-4 py-8 text-center text-gray-400 text-sm">Sin registros</td></tr>`;
        return;
      }
      const tdC  = 'px-4 py-3';
      const tdR  = 'px-4 py-3 text-right tabular-nums';
      tbody.innerHTML = data.map(r => {
        const actions = `
          <div class="flex items-center justify-center gap-2">
            <button onclick="openModal('${tab}', ${JSON.stringify(r).replace(/"/g,'&quot;')})"
              class="p-1.5 rounded-md text-gray-400 hover:text-Ipteblue2 hover:bg-blue-50 transition" title="Editar">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 012.828 2.828L11.828 15.828a2 2 0 01-1.414.586H9v-2.414a2 2 0 01.586-1.414z"/></svg>
            </button>
            <button onclick="deleteEntity('${tab}', ${r.id})"
              class="p-1.5 rounded-md text-gray-400 hover:text-red-500 hover:bg-red-50 transition" title="Eliminar">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4h6v3M4 7h16"/></svg>
            </button>
          </div>`;

        if (tab === 'manufacturadores') return `
          <tr class="hover:bg-gray-50 transition">
            <td class="${tdC} font-medium">${esc(r.name)}</td>
            <td class="${tdC}">${actions}</td>
          </tr>`;

        if (tab === 'modulos') return `
          <tr class="hover:bg-gray-50 transition">
            <td class="${tdC} font-medium">${esc(r.manufacturer)}</td>
            <td class="${tdC}">${esc(r.model)}</td>
            <td class="${tdC}">${esc(r.technology)}</td>
            <td class="${tdR}">${r.pmax_stc}</td>
            <td class="${tdR}">${r.voc_stc}</td>
            <td class="${tdR}">${r.isc_stc}</td>
            <td class="${tdR}">${r.vmpp_stc}</td>
            <td class="${tdR}">${r.imp_stc}</td>
            <td class="${tdR}">${r.temp_coeff_voc}</td>
            <td class="${tdR}">${r.temp_coeff_pmax}</td>
            <td class="${tdR}">${r.length_m}</td>
            <td class="${tdR}">${r.width_m}</td>
            <td class="${tdC}">${actions}</td>
          </tr>`;

        if (tab === 'inversores') return `
          <tr class="hover:bg-gray-50 transition">
            <td class="${tdC} font-medium">${esc(r.manufacturer)}</td>
            <td class="${tdC}">${esc(r.model)}</td>
            <td class="${tdR}">${r.pmax_dc_input}</td>
            <td class="${tdR}">${r.max_dc_voltage}</td>
            <td class="${tdR}">${r.max_input_current}</td>
            <td class="${tdR}">${r.max_short_circuit_current}</td>
            <td class="${tdR}">${r.nominal_ac_power}</td>
            <td class="${tdR}">${r.mppt_count}</td>
            <td class="${tdC}">${actions}</td>
          </tr>`;
      }).join('');
    }

    function renderPagination(tab, total, page) {
      const pages = Math.ceil(total / 5) || 1;
      const el    = document.getElementById('pagination-' + tab);
      const from  = total === 0 ? 0 : (page - 1) * 5 + 1;
      const to    = Math.min(page * 5, total);
      const btnCls = 'px-3 py-1.5 rounded-lg border text-xs font-medium transition disabled:opacity-40 disabled:cursor-not-allowed';
      el.innerHTML = `
        <span>${from}–${to} de ${total} registros</span>
        <div class="flex items-center gap-2">
          <button class="${btnCls} ${page <= 1 ? 'opacity-40 cursor-not-allowed bg-gray-50 border-gray-200 text-gray-400' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'}"
            onclick="loadTable('${tab}', ${page - 1})" ${page <= 1 ? 'disabled' : ''}>
            ← Anterior
          </button>
          <span class="text-xs text-gray-400">Pág. ${page} / ${pages}</span>
          <button class="${btnCls} ${page >= pages ? 'opacity-40 cursor-not-allowed bg-gray-50 border-gray-200 text-gray-400' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'}"
            onclick="loadTable('${tab}', ${page + 1})" ${page >= pages ? 'disabled' : ''}>
            Siguiente →
          </button>
        </div>`;
    }

    // ── Modal ─────────────────────────────────────────────────────────────
    let currentTab = null;

    async function openModal(tab, row = null) {
      currentTab = tab;
      document.getElementById('modal-title').textContent = row ? 'Editar registro' : 'Nuevo registro';
      document.querySelectorAll('.entity-form').forEach(f => f.classList.add('hidden'));
      document.getElementById('form-' + tab).classList.remove('hidden');
      document.getElementById('modal-error').classList.add('hidden');

      // Populate manufacturer selects if needed
      if (tab === 'modulos' || tab === 'inversores') {
        await populateManufacturers(tab === 'modulos' ? 'mod-manufacturer' : 'inv-manufacturer', row?.manufacturer_id);
      }

      // Populate form fields
      if (tab === 'manufacturadores') {
        document.getElementById('man-id').value   = row?.id   ?? '';
        document.getElementById('man-name').value = row?.name ?? '';
      }
      if (tab === 'modulos') {
        document.getElementById('mod-id').value            = row?.id             ?? '';
        document.getElementById('mod-model').value         = row?.model          ?? '';
        document.getElementById('mod-technology').value    = row?.technology     ?? '';
        document.getElementById('mod-pmax_stc').value      = row?.pmax_stc       ?? '';
        document.getElementById('mod-voc_stc').value       = row?.voc_stc        ?? '';
        document.getElementById('mod-isc_stc').value       = row?.isc_stc        ?? '';
        document.getElementById('mod-vmpp_stc').value      = row?.vmpp_stc       ?? '';
        document.getElementById('mod-imp_stc').value       = row?.imp_stc        ?? '';
        document.getElementById('mod-temp_coeff_voc').value  = row?.temp_coeff_voc  ?? '';
        document.getElementById('mod-temp_coeff_pmax').value = row?.temp_coeff_pmax ?? '';
        document.getElementById('mod-length_m').value      = row?.length_m       ?? '';
        document.getElementById('mod-width_m').value       = row?.width_m        ?? '';
      }
      if (tab === 'inversores') {
        document.getElementById('inv-id').value                      = row?.id                        ?? '';
        document.getElementById('inv-model').value                   = row?.model                     ?? '';
        document.getElementById('inv-pmax_dc_input').value           = row?.pmax_dc_input             ?? '';
        document.getElementById('inv-max_dc_voltage').value          = row?.max_dc_voltage            ?? '';
        document.getElementById('inv-max_input_current').value       = row?.max_input_current         ?? '';
        document.getElementById('inv-max_short_circuit_current').value = row?.max_short_circuit_current ?? '';
        document.getElementById('inv-nominal_ac_power').value        = row?.nominal_ac_power          ?? '';
        document.getElementById('inv-mppt_count').value              = row?.mppt_count                ?? '';
      }

      document.getElementById('modal').classList.remove('hidden');
    }

    async function populateManufacturers(selectId, selectedId = null) {
      const res  = await fetch('inventario.php?action=manufacturers_select');
      const list = await res.json();
      const sel  = document.getElementById(selectId);
      sel.innerHTML = '<option value="">— Seleccionar —</option>' +
        list.map(m => `<option value="${m.id}" ${m.id == selectedId ? 'selected' : ''}>${esc(m.name)}</option>`).join('');
    }

    function closeModal() {
      document.getElementById('modal').classList.add('hidden');
      currentTab = null;
    }

    async function saveEntity() {
      document.getElementById('modal-error').classList.add('hidden');
      const tab = currentTab;
      let payload = {};

      if (tab === 'manufacturadores') {
        payload = { id: document.getElementById('man-id').value, name: document.getElementById('man-name').value };
      }
      if (tab === 'modulos') {
        const tcv  = parseFloat(document.getElementById('mod-temp_coeff_voc').value);
        const tcp  = parseFloat(document.getElementById('mod-temp_coeff_pmax').value);
        if (isNaN(tcv) || tcv >= 0) {
          const err = document.getElementById('modal-error');
          err.textContent = 'El coeficiente de temperatura de Voc (β Voc) debe ser un valor negativo.';
          err.classList.remove('hidden');
          return;
        }
        if (isNaN(tcp) || tcp >= 0) {
          const err = document.getElementById('modal-error');
          err.textContent = 'El coeficiente de temperatura de Pmax (β Pmax) debe ser un valor negativo.';
          err.classList.remove('hidden');
          return;
        }
        payload = {
          id: document.getElementById('mod-id').value,
          manufacturer_id: document.getElementById('mod-manufacturer').value,
          model:            document.getElementById('mod-model').value,
          technology:       document.getElementById('mod-technology').value,
          pmax_stc:         document.getElementById('mod-pmax_stc').value,
          voc_stc:          document.getElementById('mod-voc_stc').value,
          isc_stc:          document.getElementById('mod-isc_stc').value,
          vmpp_stc:         document.getElementById('mod-vmpp_stc').value,
          imp_stc:          document.getElementById('mod-imp_stc').value,
          temp_coeff_voc:   document.getElementById('mod-temp_coeff_voc').value,
          temp_coeff_pmax:  document.getElementById('mod-temp_coeff_pmax').value,
          length_m:         document.getElementById('mod-length_m').value,
          width_m:          document.getElementById('mod-width_m').value,
        };
      }
      if (tab === 'inversores') {
        payload = {
          id: document.getElementById('inv-id').value,
          manufacturer_id:           document.getElementById('inv-manufacturer').value,
          model:                     document.getElementById('inv-model').value,
          pmax_dc_input:             document.getElementById('inv-pmax_dc_input').value,
          max_dc_voltage:            document.getElementById('inv-max_dc_voltage').value,
          max_input_current:         document.getElementById('inv-max_input_current').value,
          max_short_circuit_current: document.getElementById('inv-max_short_circuit_current').value,
          nominal_ac_power:          document.getElementById('inv-nominal_ac_power').value,
          mppt_count:                document.getElementById('inv-mppt_count').value,
        };
      }

      const actionMap = { manufacturadores: 'save_manufacturer', modulos: 'save_module', inversores: 'save_inverter' };
      const res  = await fetch(`inventario.php?action=${actionMap[tab]}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });
      const json = await res.json();
      if (json.error) {
        const err = document.getElementById('modal-error');
        err.textContent = json.error;
        err.classList.remove('hidden');
        return;
      }
      closeModal();
      loadTable(tab, state[tab].page);
    }

    // ── Delete ────────────────────────────────────────────────────────────
    async function deleteEntity(tab, id) {
      if (!confirm('¿Eliminar este registro? Esta acción no se puede deshacer.')) return;
      const actionMap = { manufacturadores: 'delete_manufacturer', modulos: 'delete_module', inversores: 'delete_inverter' };
      const res  = await fetch(`inventario.php?action=${actionMap[tab]}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id }),
      });
      const json = await res.json();
      if (json.error) { alert('Error: ' + json.error); return; }
      const newPage = state[tab].page;
      loadTable(tab, newPage);
    }

    // ── XSS-safe string helper ────────────────────────────────────────────
    function esc(s) {
      if (s == null) return '';
      return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // ── Close modal on backdrop click ─────────────────────────────────────
    document.getElementById('modal').addEventListener('click', function(e) {
      if (e.target === this) closeModal();
    });

    // ── Bootstrap ─────────────────────────────────────────────────────────
    loadTable('manufacturadores');
  </script>

<?php include '../components/footer.php'; ?>
