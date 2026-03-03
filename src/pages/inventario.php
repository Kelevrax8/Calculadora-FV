<?php
// ─────────────────────────────────────────────────────────────────────────────
//  Page render — AJAX is handled by /api/inventario.php
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
                <th class="px-4 py-3 text-right">Rango VMPP (V)</th>
                <th class="px-4 py-3 text-right">V arranque (V)</th>
                <th class="px-4 py-3 text-right">I/MPPT máx (A)</th>
                <th class="px-4 py-3 text-right">Isc máx (A)</th>
                <th class="px-4 py-3 text-right">P AC nom (W)</th>
                <th class="px-4 py-3 text-right">V AC nom (V)</th>
                <th class="px-4 py-3 text-left">Fase</th>
                <th class="px-4 py-3 text-right">EE pond. (%)</th>
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
  <div id="modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/50 p-4">
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

        <!-- Inline error banner -->
        <div id="modal-error" class="hidden mb-4 items-start gap-2.5 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
          <span id="modal-error-text"></span>
        </div>

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
              <label class="block text-xs font-semibold text-gray-500 mb-1">Pmax STC (W) <span class="text-red-500">*</span></label>
              <input type="number" step="0.01" id="mod-pmax_stc" required
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-Ipteblue2">
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-500 mb-1">Voc STC (V) <span class="text-red-500">*</span></label>
              <input type="number" step="0.01" id="mod-voc_stc" required
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-Ipteblue2">
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-500 mb-1">Isc STC (A) <span class="text-red-500">*</span></label>
              <input type="number" step="0.01" id="mod-isc_stc" required
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-Ipteblue2">
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-500 mb-1">Vmpp STC (V) <span class="text-red-500">*</span></label>
              <input type="number" step="0.01" id="mod-vmpp_stc" required
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-Ipteblue2">
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-500 mb-1">Imp STC (A) <span class="text-red-500">*</span></label>
              <input type="number" step="0.01" id="mod-imp_stc" required
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-Ipteblue2">
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-500 mb-1">β Voc (%/°C) <span class="text-red-500">*</span></label>
              <input type="number" step="0.0001" max="-0.0001" id="mod-temp_coeff_voc" required
                oninput="validateNegative(this, 'warn-tcv')"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-Ipteblue2 transition-colors">
              <p id="warn-tcv" class="hidden mt-1 text-xs text-red-500 items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                El valor debe ser negativo (ej. -0.2800)
              </p>
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-500 mb-1">β Pmax (%/°C) <span class="text-red-500">*</span></label>
              <input type="number" step="0.0001" max="-0.0001" id="mod-temp_coeff_pmax" required
                oninput="validateNegative(this, 'warn-tcp')"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-Ipteblue2 transition-colors">
              <p id="warn-tcp" class="hidden mt-1 text-xs text-red-500 items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                El valor debe ser negativo (ej. -0.3500)
              </p>
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-500 mb-1">Largo (m) <span class="text-red-500">*</span></label>
              <input type="number" step="0.01" id="mod-length_m" required
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-Ipteblue2">
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-500 mb-1">Ancho (m) <span class="text-red-500">*</span></label>
              <input type="number" step="0.01" id="mod-width_m" required
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
              <label class="block text-xs font-semibold text-gray-500 mb-1">Pmax entrada DC (W) <span class="text-red-500">*</span></label>
              <input type="number" step="0.01" id="inv-pmax_dc_input" required
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-Ipteblue2">
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-500 mb-1">V DC máxima (V) <span class="text-red-500">*</span></label>
              <input type="number" step="0.01" id="inv-max_dc_voltage" required
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-Ipteblue2">
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-500 mb-1">V MPPT mínima (V) <span class="text-red-500">*</span></label>
              <input type="number" step="0.01" id="inv-mppt_voltage_min" required
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-Ipteblue2">
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-500 mb-1">V MPPT máxima (V) <span class="text-red-500">*</span></label>
              <input type="number" step="0.01" id="inv-mppt_voltage_max" required
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-Ipteblue2">
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-500 mb-1">V de arranque (V) <span class="text-red-500">*</span></label>
              <input type="number" step="0.01" id="inv-startup_voltage" required
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-Ipteblue2">
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-500 mb-1">I entrada máx por MPPT (A) <span class="text-red-500">*</span></label>
              <input type="number" step="0.01" id="inv-max_input_current_per_mppt" required
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-Ipteblue2">
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-500 mb-1">Isc máxima (A) <span class="text-red-500">*</span></label>
              <input type="number" step="0.01" id="inv-max_short_circuit_current" required
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-Ipteblue2">
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-500 mb-1">P AC nominal (W) <span class="text-red-500">*</span></label>
              <input type="number" step="0.01" id="inv-nominal_ac_power" required
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-Ipteblue2">
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-500 mb-1">V AC nominal (V) <span class="text-red-500">*</span></label>
              <input type="number" step="0.01" id="inv-ac_voltage_nominal" required
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-Ipteblue2">
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-500 mb-1">Tipo de fase <span class="text-red-500">*</span></label>
              <select id="inv-phase_type" required
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-Ipteblue2">
                <option value="">— Seleccionar —</option>
                <option value="Single Phase">Monofásico</option>
                <option value="Split Phase">Bifásico</option>
                <option value="Three Phase">Trifásico</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-500 mb-1">Eficiencia ponderada (%) <span class="text-red-500">*</span></label>
              <input type="number" step="0.01" id="inv-efficiency_weighted" required
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-Ipteblue2">
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-500 mb-1">Cantidad MPPT <span class="text-red-500">*</span></label>
              <input type="number" step="1" min="1" id="inv-mppt_count" required
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
  <!--  TOAST CONTAINER                                                        -->
  <!-- ══════════════════════════════════════════════════════════════════════ -->
  <div id="toast-container" class="fixed bottom-6 right-6 z-100 flex flex-col gap-2 items-end pointer-events-none"></div>

  <script src="/js/inventario.js"></script>
<?php include '../components/footer.php'; ?>
