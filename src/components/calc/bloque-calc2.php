<?php defined('APP') or die('Access denied'); ?>

<!-- ================================================================
     BLOQUE 2 – MÓDULO FOTOVOLTAICO
================================================================ -->
<div id="bloque-2" class="hidden bg-white rounded-2xl shadow-sm border border-gray-200">

  <!-- Block header -->
  <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-gray-50">
    <div class="flex items-center gap-3">
      <span class="flex items-center justify-center w-7 h-7 rounded-full bg-Ipteblue text-white text-sm font-bold shrink-0">2</span>
      <div>
        <h2 class="text-base font-semibold text-gray-800">Módulo Fotovoltaico</h2>
        <p class="text-xs text-gray-400">Selecciona el panel fotovoltaico del inventario</p>
      </div>
    </div>
    <button type="button" id="btn-bloque2-volver"
      class="flex items-center gap-1.5 text-xs font-medium text-gray-400 hover:text-Ipteblue transition-colors">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none"
           viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/>
      </svg>
      Volver al Paso 1
    </button>
  </div>

  <!-- Filters -->
  <div class="px-6 pt-5 pb-3 border-b border-gray-100 flex flex-wrap items-center gap-4">

    <!-- Manufacturer filter -->
    <div class="flex items-center gap-2 flex-wrap">
      <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Fabricante:</span>
      <div id="filter-manufacturer" class="flex flex-wrap gap-1.5">
        <!-- populated by JS -->
      </div>
    </div>

    <!-- Technology filter -->
    <div class="flex items-center gap-2 flex-wrap">
      <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Tecnología:</span>
      <div id="filter-technology" class="flex flex-wrap gap-1.5">
        <!-- populated by JS -->
      </div>
    </div>

  </div>

  <!-- Module card grid -->
  <div class="px-6 py-5">

    <!-- Loading state -->
    <div id="modules-loading" class="flex items-center justify-center py-12 text-gray-400 gap-2">
      <svg class="w-4 h-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
      </svg>
      <span class="text-sm">Cargando módulos del inventario…</span>
    </div>

    <!-- Error state -->
    <p id="modules-error" class="hidden text-xs text-red-600 bg-red-50 rounded-lg px-3 py-2 border border-red-200"></p>

    <!-- Card grid -->
    <div id="modules-grid" class="hidden grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
      <!-- cards injected by JS -->
    </div>

    <!-- Empty state (after filter) -->
    <p id="modules-empty" class="hidden text-center text-sm text-gray-400 py-8">
      No hay módulos que coincidan con los filtros seleccionados.
    </p>

  </div>

  <!-- ── Selected module summary + live results ──────────────────── -->
  <div id="calc2-results" class="hidden border-t border-gray-100 mx-6 mb-6 pt-5">

    <!-- Selected module pill -->
    <div class="flex items-center justify-between mb-4">
      <div class="flex items-center gap-2">
        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Módulo seleccionado</span>
        <span id="selected-module-name"
          class="inline-flex items-center rounded-full bg-Ipteblue/10 px-3 py-0.5
                 text-xs font-semibold text-Ipteblue">—</span>
      </div>
      <button type="button" id="btn-deselect-module"
        class="text-xs text-gray-400 hover:text-red-500 transition-colors">
        ✕ Quitar selección
      </button>
    </div>

    <!-- Datasheet quick view -->
    <div id="selected-module-specs"
      class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-x-4 gap-y-2 text-xs
             bg-gray-50 rounded-xl px-4 py-3 mb-5">
      <!-- populated by JS -->
    </div>

    <!-- Live calculation results -->
    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">
      Resultados Preliminares del Arreglo
    </p>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">

      <div class="rounded-xl border border-gray-200 bg-white px-4 py-3">
        <p class="text-xs text-gray-400 mb-0.5">Módulos requeridos</p>
        <p id="res-n-modulos" class="text-2xl font-bold text-Ipteblue">—</p>
        <p class="text-xs text-gray-400 mt-0.5">unidades</p>
      </div>

      <div class="rounded-xl border border-gray-200 bg-white px-4 py-3">
        <p class="text-xs text-gray-400 mb-0.5">Potencia arreglo STC</p>
        <p id="res-p-arreglo-stc" class="text-2xl font-bold text-Ipteblue">—</p>
        <p class="text-xs text-gray-400 mt-0.5">kW pico</p>
      </div>

      <div class="rounded-xl border border-gray-200 bg-white px-4 py-3">
        <p class="text-xs text-gray-400 mb-0.5">Potencia en calor</p>
        <p id="res-p-arreglo-calor" class="text-2xl font-bold text-orange-500">—</p>
        <p id="res-p-calor-pct" class="text-xs text-gray-400 mt-0.5">— vs STC</p>
      </div>

      <div class="rounded-xl border border-gray-200 bg-white px-4 py-3">
        <p class="text-xs text-gray-400 mb-0.5">I<sub>sc</sub> protección</p>
        <p id="res-isc-prot" class="text-2xl font-bold text-gray-700">—</p>
        <p class="text-xs text-gray-400 mt-0.5">A · NOM-001</p>
      </div>

    </div>
  </div>

  <!-- Continue button -->
  <div class="px-6 pb-6" id="calc2-continue-wrap">
    <button type="button" id="btn-bloque2-continuar" disabled
      class="w-full rounded-xl bg-Ipteblue px-4 py-3 text-sm font-semibold
             text-white shadow-sm transition-colors
             enabled:hover:bg-Ipteblue/90
             disabled:opacity-40 disabled:cursor-not-allowed
             focus:outline-none focus:ring-2 focus:ring-Ipteblue/50">
      Continuar al Paso 3 →
    </button>
  </div>

</div><!-- /bloque-2 -->

<!-- ── Tailwind class anchor (never displayed) ──────────────────────
     Ensures dynamically injected card/filter classes are compiled.
──────────────────────────────────────────────────────────────────── -->
<div class="hidden"
  data-tw="cursor-pointer rounded-xl border border-gray-200 p-4 transition-all
            hover:border-Ipteblue hover:shadow-sm
            border-2 border-Ipteblue bg-Ipteblue/10 ring-2 ring-Ipteblue/30
            rounded-full px-3 py-1 text-xs font-medium
            bg-Ipteblue text-white bg-white text-gray-600 border-gray-200
            text-gray-500 font-semibold text-gray-800 text-gray-700
            bg-blue-50 text-blue-700 bg-green-50 text-green-700
            bg-purple-50 text-purple-700 bg-yellow-50 text-yellow-700
            text-lg font-bold text-Ipteblue text-orange-500
            grid grid-cols-2 gap-1 mt-2">
</div>

