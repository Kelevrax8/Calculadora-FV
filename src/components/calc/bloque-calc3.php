<?php defined('APP') or die('Access denied'); ?>

<!-- ================================================================
     BLOQUE 3 – INVERSOR
================================================================ -->
<div id="bloque-3" class="hidden bg-white rounded-2xl shadow-sm border border-gray-200">

  <!-- Block header -->
  <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-gray-50">
    <div class="flex items-center gap-3">
      <span class="flex items-center justify-center w-7 h-7 rounded-full bg-Ipteblue text-white text-sm font-bold shrink-0">3</span>
      <div>
        <h2 class="text-base font-semibold text-gray-800">Inversor</h2>
        <p class="text-xs text-gray-400">Configura la cadena y selecciona el inversor del inventario</p>
      </div>
    </div>
    <button type="button" id="btn-bloque3-volver"
      class="flex items-center gap-1.5 text-xs font-medium text-gray-400 hover:text-Ipteblue transition-colors">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none"
           viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/>
      </svg>
      Volver al Paso 2
    </button>
  </div>

  <!-- ── String Configurator ─────────────────────────────────────── -->
  <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/60">
    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">
      Configuración de Cadena (String)
    </p>
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-5">

      <!-- Ns stepper -->
      <div class="flex flex-col gap-1">
        <p class="text-xs text-gray-500">Módulos en serie <span class="font-semibold">(Ns)</span></p>
        <div class="flex items-center gap-2">
          <button id="btn-ns-dec" type="button" disabled
            class="w-7 h-7 rounded-lg border border-gray-200 text-gray-600 font-bold
                   hover:border-Ipteblue hover:text-Ipteblue
                   disabled:opacity-30 disabled:cursor-not-allowed transition-colors">−</button>
          <span id="ns-value" class="w-8 text-center text-lg font-bold text-gray-800">—</span>
          <button id="btn-ns-inc" type="button" disabled
            class="w-7 h-7 rounded-lg border border-gray-200 text-gray-600 font-bold
                   hover:border-Ipteblue hover:text-Ipteblue
                   disabled:opacity-30 disabled:cursor-not-allowed transition-colors">+</button>
        </div>
        <p id="ns-range-hint" class="text-xs text-gray-400">Cargando…</p>
      </div>

      <!-- Np auto -->
      <div class="flex flex-col gap-1">
        <p class="text-xs text-gray-500">Strings en paralelo <span class="font-semibold">(Np)</span></p>
        <p id="np-value" class="text-lg font-bold text-gray-800">—</p>
        <p id="np-mppt-hint" class="text-xs text-gray-400">Selecciona un inversor para verificar</p>
      </div>

      <!-- Total array area -->
      <div class="flex flex-col gap-1">
        <p class="text-xs text-gray-500">Superficie total del arreglo</p>
        <p id="str-area-total" class="text-lg font-bold text-gray-800">—</p>
        <p class="text-xs text-gray-400">m² (área neta de módulos)</p>
      </div>

      <!-- String voltages preview -->
      <div class="grid grid-cols-3 gap-2">
        <div>
          <p class="text-xs text-gray-400 mb-0.5">Voc frío</p>
          <p id="str-voc-cold" class="text-sm font-semibold text-red-500">—</p>
          <p class="text-xs text-gray-300">seguridad</p>
        </div>
        <div>
          <p class="text-xs text-gray-400 mb-0.5">Vmpp calor</p>
          <p id="str-vmpp-hot" class="text-sm font-semibold text-orange-500">—</p>
          <p class="text-xs text-gray-300">MPPT mín</p>
        </div>
        <div>
          <p class="text-xs text-gray-400 mb-0.5">Vmpp frío</p>
          <p id="str-vmpp-cold" class="text-sm font-semibold text-blue-500">—</p>
          <p class="text-xs text-gray-300">MPPT máx</p>
        </div>
      </div>

    </div>
  </div>

  <!-- ── Remainder string warning ──────────────────────────────────── -->
  <div id="str-remainder-warning" class="hidden mx-6 mt-3 rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-xs text-amber-800">
    <div class="flex gap-2 items-start">
      <span class="text-base leading-none mt-0.5">⚠</span>
      <div class="flex-1">
        <p class="font-semibold mb-1">String incompleto detectado</p>
        <p id="str-rem-breakdown" class="mb-2 text-amber-700"></p>
        <div class="grid grid-cols-3 gap-2 bg-amber-100/60 rounded-lg px-3 py-2 mb-2">
          <div>
            <p class="text-amber-600 mb-0.5">Voc frío</p>
            <p id="str-rem-voc-cold" class="font-bold">—</p>
          </div>
          <div>
            <p class="text-amber-600 mb-0.5">Vmpp calor</p>
            <p id="str-rem-vmpp-hot" class="font-bold">—</p>
          </div>
          <div>
            <p class="text-amber-600 mb-0.5">Vmpp frío</p>
            <p id="str-rem-vmpp-cold" class="font-bold">—</p>
          </div>
        </div>
        <p id="str-rem-advice" class="leading-relaxed"></p>
        <p id="str-rem-mppt-note" class="hidden mt-2 font-semibold"></p>
      </div>
    </div>
  </div>

  <!-- ── Filters ──────────────────────────────────────────────────── -->
  <!-- Assumption note -->
  <div class="mx-6 mt-1 mb-0 rounded-lg bg-blue-50 border border-blue-100 px-4 py-2.5 flex gap-2 items-start text-xs text-blue-700">
    <span class="text-base leading-none mt-0.5">&#9432;</span>
    <span><strong>Supuesto de diseño:</strong> Este sistema asume <strong>1 string por entrada MPPT</strong> para evitar la necesidad de caja combinadora. Np = número de strings = número de entradas MPPT utilizadas. Si Np supera las entradas disponibles del inversor, se debe <strong>aumentar Ns</strong> (strings más largas → menos strings en paralelo).</span>
  </div>
  <div class="px-6 pt-4 pb-3 border-b border-gray-100 flex flex-wrap items-center gap-4">

    <div class="flex items-center gap-2 flex-wrap">
      <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Fabricante:</span>
      <div id="filter-inv-manufacturer" class="flex flex-wrap gap-1.5">
        <!-- populated by JS -->
      </div>
    </div>

    <div class="flex items-center gap-2 flex-wrap">
      <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Fase:</span>
      <div id="filter-inv-phase" class="flex flex-wrap gap-1.5">
        <!-- populated by JS -->
      </div>
    </div>

  </div>

  <!-- ── Inverter card grid ────────────────────────────────────────── -->
  <div class="px-6 py-5">

    <div id="inverters-loading" class="flex items-center justify-center py-12 text-gray-400 gap-2">
      <svg class="w-4 h-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
      </svg>
      <span class="text-sm">Cargando inversores del inventario…</span>
    </div>

    <p id="inverters-error"
       class="hidden text-xs text-red-600 bg-red-50 rounded-lg px-3 py-2 border border-red-200"></p>

    <div id="inverters-grid"
         class="hidden grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
      <!-- cards injected by JS -->
    </div>

    <p id="inverters-empty"
       class="hidden text-center text-sm text-gray-400 py-8">
      No hay inversores que coincidan con los filtros seleccionados.
    </p>

  </div>

  <!-- ── Selected inverter + electrical check results ─────────────── -->
  <div id="calc3-results" class="hidden border-t border-gray-100 mx-6 mb-6 pt-5">

    <!-- Selected inverter pill + deselect -->
    <div class="flex items-center justify-between mb-4">
      <div class="flex items-center gap-2">
        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Inversor seleccionado</span>
        <span id="selected-inverter-name"
          class="inline-flex items-center rounded-full bg-Ipteblue/10 px-3 py-0.5
                 text-xs font-semibold text-Ipteblue">—</span>
      </div>
      <button type="button" id="btn-deselect-inverter"
        class="text-xs text-gray-400 hover:text-red-500 transition-colors">
        ✕ Quitar selección
      </button>
    </div>

    <!-- Datasheet quick view -->
    <div id="selected-inverter-specs"
      class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-x-4 gap-y-2 text-xs
             bg-gray-50 rounded-xl px-4 py-3 mb-5">
      <!-- populated by JS -->
    </div>

    <!-- Electrical check cards -->
    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">
      Verificación Eléctrica
    </p>
    <div class="grid grid-cols-2 lg:grid-cols-3 gap-3 mb-3">

      <!-- Hard: Voc cold vs Vdc max -->
      <div id="chk-voc" class="rounded-xl border border-gray-200 px-4 py-3">
        <div class="flex items-start justify-between mb-1">
          <p class="text-xs text-gray-400">Voc en frío</p>
          <span data-badge class="text-xs font-semibold rounded-full px-2 py-0.5 bg-gray-100 text-gray-400">—</span>
        </div>
        <p data-actual class="text-lg font-bold text-gray-700">—</p>
        <p data-limit class="text-xs text-gray-400 mt-0.5">límite —</p>
      </div>

      <!-- Soft: Vmpp hot vs MPPT min -->
      <div id="chk-vmpp-hot" class="rounded-xl border border-gray-200 px-4 py-3">
        <div class="flex items-start justify-between mb-1">
          <p class="text-xs text-gray-400">Vmpp en calor (MPPT mín)</p>
          <span data-badge class="text-xs font-semibold rounded-full px-2 py-0.5 bg-gray-100 text-gray-400">—</span>
        </div>
        <p data-actual class="text-lg font-bold text-gray-700">—</p>
        <p data-limit class="text-xs text-gray-400 mt-0.5">límite —</p>
      </div>

      <!-- Soft: Vmpp cold vs MPPT max -->
      <div id="chk-vmpp-cold" class="rounded-xl border border-gray-200 px-4 py-3">
        <div class="flex items-start justify-between mb-1">
          <p class="text-xs text-gray-400">Vmpp en frío (MPPT máx)</p>
          <span data-badge class="text-xs font-semibold rounded-full px-2 py-0.5 bg-gray-100 text-gray-400">—</span>
        </div>
        <p data-actual class="text-lg font-bold text-gray-700">—</p>
        <p data-limit class="text-xs text-gray-400 mt-0.5">límite —</p>
      </div>

      <!-- Hard: I per MPPT (1 string per MPPT assumed) -->
      <div id="chk-i-mppt" class="rounded-xl border border-gray-200 px-4 py-3">
        <div class="flex items-start justify-between mb-1">
          <p class="text-xs text-gray-400">Corriente por MPPT <span class="text-gray-300">(1 string)</span></p>
          <span data-badge class="text-xs font-semibold rounded-full px-2 py-0.5 bg-gray-100 text-gray-400">—</span>
        </div>
        <p data-actual class="text-lg font-bold text-gray-700">—</p>
        <p data-limit class="text-xs text-gray-400 mt-0.5">límite —</p>
      </div>

      <!-- Hard: Np vs mppt_count -->
      <div id="chk-np-mppt" class="rounded-xl border border-gray-200 px-4 py-3">
        <div class="flex items-start justify-between mb-1">
          <p class="text-xs text-gray-400">Strings vs. entradas MPPT</p>
          <span data-badge class="text-xs font-semibold rounded-full px-2 py-0.5 bg-gray-100 text-gray-400">—</span>
        </div>
        <p data-actual class="text-lg font-bold text-gray-700">—</p>
        <p data-limit class="text-xs text-gray-400 mt-0.5">límite —</p>
      </div>

      <!-- Hard: Isc total -->
      <div id="chk-i-total" class="rounded-xl border border-gray-200 px-4 py-3">
        <div class="flex items-start justify-between mb-1">
          <p class="text-xs text-gray-400">I<sub>sc</sub> total DC</p>
          <span data-badge class="text-xs font-semibold rounded-full px-2 py-0.5 bg-gray-100 text-gray-400">—</span>
        </div>
        <p data-actual class="text-lg font-bold text-gray-700">—</p>
        <p data-limit class="text-xs text-gray-400 mt-0.5">límite —</p>
      </div>

      <!-- Hard: P array cold vs pmax_dc_input -->
      <div id="chk-p-dc" class="rounded-xl border border-gray-200 px-4 py-3">
        <div class="flex items-start justify-between mb-1">
          <p class="text-xs text-gray-400">P arreglo en frío</p>
          <span data-badge class="text-xs font-semibold rounded-full px-2 py-0.5 bg-gray-100 text-gray-400">—</span>
        </div>
        <p data-actual class="text-lg font-bold text-gray-700">—</p>
        <p data-limit class="text-xs text-gray-400 mt-0.5">límite —</p>
      </div>

      <!-- Informational: DC/AC ratio -->
      <div class="rounded-xl border border-gray-200 px-4 py-3">
        <div class="flex items-start justify-between mb-1">
          <p class="text-xs text-gray-400">Relación DC/AC</p>
          <span id="res-dcac-hint" class="text-xs text-gray-400">—</span>
        </div>
        <p id="res-dcac" class="text-lg font-bold text-gray-700">—</p>
        <p class="text-xs text-gray-400 mt-0.5">P<sub>STC</sub> / P<sub>AC nom</sub></p>
        <div class="mt-1.5 grid grid-cols-2 gap-x-2 text-xs">
          <span class="text-gray-400">P<sub>STC</sub></span>
          <span id="res-dcac-pstc" class="font-semibold text-gray-700">—</span>
          <span class="text-gray-400">P<sub>AC nom</sub></span>
          <span id="res-dcac-pac" class="font-semibold text-gray-700">—</span>
        </div>
        <!-- Range legend -->
        <div class="mt-2 space-y-0.5 border-t border-gray-100 pt-2">
          <div class="flex justify-between text-xs"><span class="text-red-400">  &lt; 0.80</span><span class="text-gray-400">Arreglo insuficiente</span></div>
          <div class="flex justify-between text-xs"><span class="text-amber-400">0.80 – 1.00</span><span class="text-gray-400">Subóptimo</span></div>
          <div class="flex justify-between text-xs"><span class="text-green-400">1.00 – 1.25</span><span class="text-gray-400">Conservador</span></div>
          <div class="flex justify-between text-xs"><span class="text-green-600">1.25 – 1.50</span><span class="text-gray-400">Óptimo</span></div>
          <div class="flex justify-between text-xs"><span class="text-red-400">  &gt; 1.50</span><span class="text-gray-400">Sobredimensionado</span></div>
        </div>
      </div>

    </div>

    <!-- String config summary -->
    <div id="selected-string-config"
      class="text-xs text-gray-500 bg-gray-50 rounded-lg px-4 py-2">
      Configuración: —
    </div>

  </div>

  <!-- Continue button -->
  <div class="px-6 pb-6">
    <button type="button" id="btn-bloque3-continuar" disabled
      class="w-full rounded-xl bg-Ipteblue px-4 py-3 text-sm font-semibold
             text-white shadow-sm transition-colors
             enabled:hover:bg-Ipteblue/90
             disabled:opacity-40 disabled:cursor-not-allowed
             focus:outline-none focus:ring-2 focus:ring-Ipteblue/50">
      Continuar al Paso 4 →
    </button>
  </div>

</div><!-- /bloque-3 -->

<!-- Tailwind class anchor – pre-seeds dynamically injected classes -->
<div class="hidden" data-tw="
  border-green-200 bg-green-50/30 bg-green-50 text-green-600
  border-red-200   bg-red-50/30   bg-red-50   text-red-600
  border-amber-200 bg-amber-50/30 bg-amber-50  text-amber-600
  bg-purple-50 text-purple-700 bg-sky-50 text-sky-700
  text-amber-500 text-red-500 text-green-600
  text-red-400 text-amber-400 text-green-500
  border-2 border-Ipteblue bg-Ipteblue/10
"></div>
