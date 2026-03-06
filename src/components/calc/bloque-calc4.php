<?php defined('APP') or die('Access denied'); ?>

<!-- ================================================================
     BLOQUE 4 – RESUMEN Y PROTECCIONES
================================================================ -->
<div id="bloque-4" class="hidden bg-white rounded-2xl shadow-sm border border-gray-200">

  <!-- Block header -->
  <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-gray-50">
    <div class="flex items-center gap-3">
      <span class="flex items-center justify-center w-7 h-7 rounded-full bg-Ipteblue text-white text-sm font-bold shrink-0">4</span>
      <div>
        <h2 class="text-base font-semibold text-gray-800">Resumen del Sistema</h2>
        <p class="text-xs text-gray-400">Parámetros, compatibilidad y protecciones eléctricas</p>
      </div>
    </div>
    <button type="button" id="btn-bloque4-volver"
      class="flex items-center gap-1.5 text-xs font-medium text-gray-400 hover:text-Ipteblue transition-colors">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none"
           viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/>
      </svg>
      Volver al Paso 3
    </button>
  </div>

  <!-- ── Overall Verdict ────────────────────────────────────────── -->
  <div class="px-6 pt-5 pb-3">
    <div id="verdict-banner"
      class="flex items-center gap-3 rounded-xl border px-5 py-3 text-sm font-semibold">
      <!-- populated by JS -->
    </div>
  </div>

  <!-- ── Site & Design Parameters ──────────────────────────────── -->
  <div class="px-6 py-4 border-t border-gray-100">
    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Parámetros de Sitio y Diseño</p>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">

      <div class="rounded-xl bg-gray-50 border border-gray-100 px-4 py-3">
        <p class="text-xs text-gray-400">Ubicación</p>
        <p id="s4-location" class="text-sm font-semibold text-gray-800 mt-0.5">—</p>
      </div>

      <div class="rounded-xl bg-gray-50 border border-gray-100 px-4 py-3">
        <p class="text-xs text-gray-400">Consumo anual</p>
        <p id="s4-consumption" class="text-sm font-semibold text-gray-800 mt-0.5">—</p>
      </div>

      <div class="rounded-xl bg-gray-50 border border-gray-100 px-4 py-3">
        <p class="text-xs text-gray-400">HSP de diseño</p>
        <p id="s4-hsp" class="text-sm font-semibold text-gray-800 mt-0.5">—</p>
      </div>

      <div class="rounded-xl bg-gray-50 border border-gray-100 px-4 py-3">
        <p class="text-xs text-gray-400">Temp. mín / máx</p>
        <p id="s4-temps" class="text-sm font-semibold text-gray-800 mt-0.5">—</p>
      </div>

    </div>
  </div>

  <!-- ── PV Array ────────────────────────────────────────────────── -->
  <div class="px-6 py-4 border-t border-gray-100">
    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Arreglo Fotovoltaico</p>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">

      <div class="col-span-2 rounded-xl bg-gray-50 border border-gray-100 px-4 py-3">
        <p class="text-xs text-gray-400">Módulo</p>
        <p id="s4-module-name" class="text-sm font-semibold text-gray-800 mt-0.5">—</p>
      </div>

      <div class="rounded-xl bg-gray-50 border border-gray-100 px-4 py-3">
        <p class="text-xs text-gray-400">Potencia unitaria</p>
        <p id="s4-module-power" class="text-sm font-semibold text-gray-800 mt-0.5">—</p>
      </div>

      <div class="rounded-xl bg-gray-50 border border-gray-100 px-4 py-3">
        <p class="text-xs text-gray-400">Módulos totales</p>
        <p id="s4-total-modules" class="text-sm font-semibold text-gray-800 mt-0.5">—</p>
      </div>

      <div class="rounded-xl bg-gray-50 border border-gray-100 px-4 py-3">
        <p class="text-xs text-gray-400">Potencia pico total</p>
        <p id="s4-array-power" class="text-sm font-semibold text-gray-800 mt-0.5">—</p>
      </div>

      <div class="rounded-xl bg-gray-50 border border-gray-100 px-4 py-3">
        <p class="text-xs text-gray-400">Vmpp del arreglo</p>
        <p id="s4-vmpp-array" class="text-sm font-semibold text-gray-800 mt-0.5">—</p>
      </div>

      <div class="rounded-xl bg-gray-50 border border-gray-100 px-4 py-3">
        <p class="text-xs text-gray-400">Voc del arreglo</p>
        <p id="s4-voc-array" class="text-sm font-semibold text-gray-800 mt-0.5">—</p>
      </div>

      <div class="rounded-xl bg-gray-50 border border-gray-100 px-4 py-3">
        <p class="text-xs text-gray-400">Isc del arreglo</p>
        <p id="s4-isc-array" class="text-sm font-semibold text-gray-800 mt-0.5">—</p>
      </div>

    </div>
  </div>

  <!-- ── Inverter ────────────────────────────────────────────────── -->
  <div class="px-6 py-4 border-t border-gray-100">
    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Inversor</p>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">

      <div class="col-span-2 rounded-xl bg-gray-50 border border-gray-100 px-4 py-3">
        <p class="text-xs text-gray-400">Inversor</p>
        <p id="s4-inverter-name" class="text-sm font-semibold text-gray-800 mt-0.5">—</p>
      </div>

      <div class="rounded-xl bg-gray-50 border border-gray-100 px-4 py-3">
        <p class="text-xs text-gray-400">Potencia AC nominal</p>
        <p id="s4-inverter-power" class="text-sm font-semibold text-gray-800 mt-0.5">—</p>
      </div>

      <div class="rounded-xl bg-gray-50 border border-gray-100 px-4 py-3">
        <p class="text-xs text-gray-400">Rango MPPT</p>
        <p id="s4-mppt-range" class="text-sm font-semibold text-gray-800 mt-0.5">—</p>
      </div>

      <div class="rounded-xl bg-gray-50 border border-gray-100 px-4 py-3">
        <p class="text-xs text-gray-400">Corriente máx. entrada DC</p>
        <p id="s4-inverter-imax" class="text-sm font-semibold text-gray-800 mt-0.5">—</p>
      </div>

      <div class="rounded-xl bg-gray-50 border border-gray-100 px-4 py-3">
        <p class="text-xs text-gray-400">Tensión de arranque</p>
        <p id="s4-startup-voltage" class="text-sm font-semibold text-gray-800 mt-0.5">—</p>
      </div>

      <div class="rounded-xl bg-gray-50 border border-gray-100 px-4 py-3">
        <p class="text-xs text-gray-400">Tensión AC nominal</p>
        <p id="s4-ac-voltage" class="text-sm font-semibold text-gray-800 mt-0.5">—</p>
      </div>

    </div>
  </div>

  <!-- ── Compatibility Checks ────────────────────────────────────── -->
  <div class="px-6 py-4 border-t border-gray-100">
    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Verificación de Compatibilidad</p>
    <div id="s4-compat-table" class="divide-y divide-gray-100 rounded-xl border border-gray-200 overflow-hidden">
      <!-- rows injected by JS -->
    </div>
  </div>

  <!-- ── Energy Estimate ─────────────────────────────────────────── -->
  <div class="px-6 py-4 border-t border-gray-100">
    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Estimación de Producción Energética</p>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">

      <div class="rounded-xl bg-gray-50 border border-gray-100 px-4 py-3">
        <p class="text-xs text-gray-400">Producción estimada</p>
        <p id="s4-energy-production" class="text-sm font-semibold text-gray-800 mt-0.5">—</p>
      </div>

      <div class="rounded-xl bg-gray-50 border border-gray-100 px-4 py-3">
        <p class="text-xs text-gray-400">Consumo anual cubierto</p>
        <p id="s4-self-sufficiency" class="text-sm font-semibold text-gray-800 mt-0.5">—</p>
      </div>

      <div class="rounded-xl bg-gray-50 border border-gray-100 px-4 py-3">
        <p class="text-xs text-gray-400">Factor de rendimiento (PR)</p>
        <p id="s4-pr" class="text-sm font-semibold text-gray-800 mt-0.5">0.75</p>
      </div>

      <div class="rounded-xl bg-gray-50 border border-gray-100 px-4 py-3">
        <p class="text-xs text-gray-400">Relación DC/AC</p>
        <p id="s4-dcac-value" class="text-sm font-semibold text-gray-800 mt-0.5">—</p>
        <p id="s4-dcac-label" class="text-xs text-gray-400">—</p>
      </div>

    </div>

    <!-- Monthly production table (only shown when NASA data is available) -->
    <div id="s4-monthly-section" class="hidden mt-4">

      <!-- Sub-header + toggle -->
      <div class="flex items-center justify-between mb-2">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Desglose Mensual</p>
        <div class="flex items-center gap-2">
          <span class="text-xs text-gray-500">Consumo mensual real:</span>
          <div class="flex rounded-lg border border-gray-200 overflow-hidden text-xs font-medium">
            <button type="button" data-cons="off" id="btn-cons-off"
              class="cons-toggle-btn px-3 py-1 bg-Ipteblue text-white transition-colors">
              Solo producción
            </button>
            <button type="button" data-cons="on" id="btn-cons-on"
              class="cons-toggle-btn px-3 py-1 bg-white text-gray-500 transition-colors">
              Ingresar consumo
            </button>
          </div>
        </div>
      </div>

      <div class="overflow-x-auto rounded-xl border border-gray-200">
        <table class="w-full text-xs">
          <thead>
            <tr class="bg-gray-50 border-b border-gray-200 text-gray-500">
              <th class="px-3 py-2 text-left font-semibold">Mes</th>
              <th class="px-3 py-2 text-right font-semibold">GHI diario<br/><span class="font-normal">(kWh/m²/día)</span></th>
              <th class="px-3 py-2 text-right font-semibold">Días</th>
              <th class="px-3 py-2 text-right font-semibold">Producción<br/><span class="font-normal">(kWh)</span></th>
              <th class="cons-col hidden px-3 py-2 text-right font-semibold">Consumo real<br/><span class="font-normal">(kWh)</span></th>
              <th class="cons-col hidden px-3 py-2 text-right font-semibold">Balance<br/><span class="font-normal">(kWh)</span></th>
            </tr>
          </thead>
          <tbody id="s4-monthly-tbody">
            <!-- rows injected by JS -->
          </tbody>
          <tfoot id="s4-monthly-tfoot" class="border-t-2 border-gray-300 bg-gray-50 font-semibold">
            <!-- totals row injected by JS -->
          </tfoot>
        </table>
      </div>
    </div>
  </div>

  <!-- ── Electrical Protection ──────────────────────────────────── -->
  <div class="px-6 py-4 border-t border-gray-100">

    <!-- Section header + derating toggle -->
    <div class="flex items-center justify-between mb-3">
      <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">
        Protecciones Eléctricas
        <span class="normal-case font-normal text-gray-400 ml-1">(NOM-001-SEDE-2012, Art. 690.8)</span>
      </p>
      <div class="flex items-center gap-2">
        <span class="text-xs text-gray-500">Corrección por temperatura:</span>
        <div class="flex rounded-lg border border-gray-200 overflow-hidden text-xs font-medium">
          <button type="button" data-derating="off" id="btn-derating-off"
            class="derating-btn px-3 py-1 bg-Ipteblue text-white transition-colors">
            Sin corrección
          </button>
          <button type="button" data-derating="on" id="btn-derating-on"
            class="derating-btn px-3 py-1 bg-white text-gray-500 transition-colors">
            Con corrección
          </button>
        </div>
      </div>
    </div>

    <p id="derating-hint" class="text-xs text-gray-400 mb-3 hidden">
      <!-- populated by JS e.g. "Tamb = 42.6 °C → factor 0.87 (Tabla 310.15(B)(2)(a))" -->
    </p>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

      <!-- DC String circuit -->
      <div class="rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-4 py-2 bg-gray-50 border-b border-gray-200">
          <p class="text-xs font-semibold text-gray-600">Circuito DC — String → Inversor</p>
        </div>
        <div class="divide-y divide-gray-100">
          <div class="flex justify-between items-center px-4 py-2.5 text-xs">
            <span class="text-gray-500">Isc módulo</span>
            <span id="prot-isc-module" class="font-semibold text-gray-800">—</span>
          </div>
          <div class="flex justify-between items-center px-4 py-2.5 text-xs">
            <span class="text-gray-500">Corriente de diseño (× 1.56)</span>
            <span id="prot-dc-idesign" class="font-semibold text-gray-800">—</span>
          </div>
          <div class="flex justify-between items-center px-4 py-2.5 text-xs bg-blue-50/50">
            <span class="text-gray-600 font-medium">Protección recomendada</span>
            <span id="prot-dc-ocpd" class="font-bold text-Ipteblue">—</span>
          </div>
          <div class="flex justify-between items-center px-4 py-2.5 text-xs bg-blue-50/50">
            <span class="text-gray-600 font-medium">Calibre conductor</span>
            <span id="prot-dc-awg" class="font-bold text-Ipteblue">—</span>
          </div>
        </div>
      </div>

      <!-- AC Output circuit -->
      <div class="rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-4 py-2 bg-gray-50 border-b border-gray-200">
          <p class="text-xs font-semibold text-gray-600">Circuito AC — Inversor → Tablero</p>
        </div>
        <div class="divide-y divide-gray-100">
          <div class="flex justify-between items-center px-4 py-2.5 text-xs">
            <span class="text-gray-500">Potencia AC / Tensión nominal (<span id="prot-ac-phase">—</span>)</span>
            <span id="prot-ac-ratio" class="font-semibold text-gray-800">—</span>
          </div>
          <div class="flex justify-between items-center px-4 py-2.5 text-xs">
            <span class="text-gray-500">Corriente de diseño (× 1.25)</span>
            <span id="prot-ac-idesign" class="font-semibold text-gray-800">—</span>
          </div>
          <div class="flex justify-between items-center px-4 py-2.5 text-xs bg-blue-50/50">
            <span class="text-gray-600 font-medium">Protección recomendada</span>
            <span id="prot-ac-ocpd" class="font-bold text-Ipteblue">—</span>
          </div>
          <div class="flex justify-between items-center px-4 py-2.5 text-xs bg-blue-50/50">
            <span class="text-gray-600 font-medium">Calibre conductor</span>
            <span id="prot-ac-awg" class="font-bold text-Ipteblue">—</span>
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- ── Actions ─────────────────────────────────────────────────── -->
  <div class="px-6 py-5 border-t border-gray-100 flex flex-col sm:flex-row gap-3">
    <button type="button" id="btn-reiniciar"
      class="flex-1 rounded-xl border border-gray-200 px-4 py-3 text-sm font-semibold
             text-gray-600 hover:border-red-300 hover:text-red-600 hover:bg-red-50/50
             transition-colors focus:outline-none focus:ring-2 focus:ring-red-200">
      ← Nuevo cálculo
    </button>
    <button type="button" id="btn-excel-export"
      class="flex-1 rounded-xl bg-Ipteblue px-4 py-3 text-sm font-semibold
             text-white shadow-sm hover:bg-Ipteblue/90
             transition-colors focus:outline-none focus:ring-2 focus:ring-Ipteblue/50
             flex items-center justify-center gap-2">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none"
           viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
      </svg>
      Exportar Excel (.xlsx)
    </button>
  </div>

</div><!-- /bloque-4 -->
