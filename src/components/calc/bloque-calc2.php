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
        <p class="text-xs text-gray-400">Selecciona el panel del inventario</p>
      </div>
    </div>
    <!-- Back button -->
    <button type="button" id="btn-bloque2-volver"
      class="flex items-center gap-1.5 text-xs font-medium text-gray-400 hover:text-Ipteblue transition-colors">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none"
           viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/>
      </svg>
      Volver al Paso 1
    </button>
  </div>

  <!-- Content placeholder -->
  <div class="px-6 py-12 text-center text-gray-400">
    <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto w-10 h-10 mb-3 text-gray-300" fill="none"
         viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round"
            d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z"/>
    </svg>
    <p class="text-sm font-medium">Módulo fotovoltaico — en construcción</p>
    <p class="text-xs mt-1">Aquí irá el selector de paneles del inventario</p>
  </div>

</div><!-- /bloque-2 -->
