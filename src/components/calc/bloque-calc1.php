<?php defined('APP') or die('Access denied'); ?>

<!-- ================================================================
     BLOQUE 1 – UBICACIÓN Y DATOS SOLARES
================================================================ -->
<div id="bloque-1" class="bg-white rounded-2xl shadow-sm border border-gray-200">

  <!-- Block header -->
  <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100 bg-gray-50">
    <span class="flex items-center justify-center w-7 h-7 rounded-full bg-Ipteblue text-white text-sm font-bold shrink-0">1</span>
    <div>
      <h2 class="text-base font-semibold text-gray-800">Ubicación y Consumo Energético</h2>
      <p class="text-xs text-gray-400">Selecciona la ubicación en el mapa y captura el consumo anual</p>
    </div>
  </div>

  <!-- Two-column layout: map | form -->
  <div class="grid grid-cols-1 lg:grid-cols-2">

    <!-- ── LEFT: Map ────────────────────────────────────────── -->
    <div class="relative border-b lg:border-b-0 lg:border-r border-gray-100">
      <div id="map" class="w-full" style="height: 480px;"></div>
      <!-- Coordinate badge overlay -->
      <div id="coord-badge"
           class="absolute bottom-4 left-1/2 -translate-x-1/2 bg-white/90 backdrop-blur-sm
                  rounded-lg px-4 py-2 shadow text-xs text-gray-600 pointer-events-none hidden">
        <span class="font-medium text-Ipteblue">📍</span>
        Lat: <span id="badge-lat" class="font-semibold text-gray-800">—</span>
        &nbsp;|&nbsp;
        Lng: <span id="badge-lng" class="font-semibold text-gray-800">—</span>
      </div>
    </div>

    <!-- ── RIGHT: Form ───────────────────────────────────────── -->
    <div class="px-6 py-6 flex flex-col gap-6">

      <!-- Section: Coordinates (auto-filled by map) -->
      <fieldset>
        <legend class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">
          Coordenadas Seleccionadas
        </legend>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1" for="latitud">Latitud</label>
            <input type="text" id="latitud" name="latitud" readonly
              placeholder="Haz clic en el mapa"
              class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2
                     text-sm text-gray-700 placeholder-gray-400
                     focus:outline-none focus:ring-2 focus:ring-Ipteblue/40 cursor-default"/>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1" for="longitud">Longitud</label>
            <input type="text" id="longitud" name="longitud" readonly
              placeholder="Haz clic en el mapa"
              class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2
                     text-sm text-gray-700 placeholder-gray-400
                     focus:outline-none focus:ring-2 focus:ring-Ipteblue/40 cursor-default"/>
          </div>
        </div>
      </fieldset>

      <!-- Section: Consumption -->
      <fieldset>
        <legend class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">
          Consumo Energético
        </legend>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1" for="consumo_anual_kwh">
            Consumo Anual <span class="text-gray-400 font-normal">(kWh/año)</span>
          </label>
          <input type="number" id="consumo_anual_kwh" name="consumo_anual_kwh"
            min="0" step="1" placeholder="Ej. 3650"
            class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2
                   text-sm text-gray-800 placeholder-gray-400
                   focus:outline-none focus:ring-2 focus:ring-Ipteblue/40"/>
          <p class="mt-1 text-xs text-gray-400">Puedes encontrarlo en tu recibo de CFE anual</p>
        </div>
      </fieldset>

      <!-- Section: Solar Data -->
      <fieldset>
        <legend class="flex items-center justify-between mb-3">
          <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">
            Datos Solares
          </span>
          <button type="button" id="btn-nasa-api" disabled
            title="Selecciona primero una ubicación en el mapa"
            class="flex items-center gap-1.5 rounded-md border border-gray-200 bg-gray-50
                   px-3 py-1 text-xs font-medium text-gray-400 cursor-not-allowed transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none"
                 viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 3v1m0 16v1m8.66-9h-1M4.34 12h-1m15-6.36-.71.71M6.05 17.66l-.71.71m12.02 0-.71-.71M6.05 6.34l-.71-.71M12 7a5 5 0 1 0 0 10A5 5 0 0 0 12 7Z"/>
            </svg>
            Obtener de NASA POWER
          </button>
        </legend>

        <div class="grid grid-cols-1 gap-3">
          <!-- Error message from NASA API -->
          <p id="nasa-error" class="hidden text-xs text-red-600 bg-red-50 rounded-lg px-3 py-2 border border-red-200"></p>

          <!-- HSP -->
          <div>
            <div class="flex items-center justify-between mb-1">
              <label class="text-sm font-medium text-gray-700" for="hsp">
                Horas Solar Pico – HSP
                <span class="text-gray-400 font-normal">(kWh/m²/día)</span>
              </label>
              <!-- Mode toggle: only visible after NASA data loads -->
              <div id="hsp-mode-toggle" class="flex items-center rounded-lg border border-gray-200 overflow-hidden text-xs font-medium">
                <button type="button" data-mode="min"
                  class="hsp-mode-btn px-2.5 py-1 bg-Ipteblue text-white transition-colors">
                  Peor mes
                </button>
                <button type="button" data-mode="avg"
                  class="hsp-mode-btn px-2.5 py-1 bg-white text-gray-500 hover:bg-gray-50 transition-colors">
                  Promedio
                </button>
              </div>
            </div>
            <input type="number" id="hsp" name="hsp_kwh_m2_dia"
              min="0" step="0.01" placeholder="Selecciona una ubicación y consulta la NASA" readonly
              class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2
                     text-sm text-gray-700 placeholder-gray-400
                     focus:outline-none focus:ring-2 focus:ring-Ipteblue/40 cursor-default"/>
            <p id="hsp-mode-hint" class="hidden mt-1 text-xs text-gray-400"></p>
          </div>

          <!-- Tmin / Tmax -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1" for="tmin">
                T. Mínima <span class="text-gray-400 font-normal">(°C)</span>
              </label>
              <input type="number" id="tmin" name="tmin_ambiente"
                step="0.1" placeholder="—" readonly
                class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2
                       text-sm text-gray-700 placeholder-gray-400
                       focus:outline-none focus:ring-2 focus:ring-Ipteblue/40 cursor-default"/>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1" for="tmax">
                T. Máxima <span class="text-gray-400 font-normal">(°C)</span>
              </label>
              <input type="number" id="tmax" name="tmax_ambiente"
                step="0.1" placeholder="—" readonly
                class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2
                       text-sm text-gray-700 placeholder-gray-400
                       focus:outline-none focus:ring-2 focus:ring-Ipteblue/40 cursor-default"/>
            </div>
          </div>
        </div>
      </fieldset>

      <!-- Continue button -->
      <div class="mt-auto pt-2">
        <button type="button" id="btn-bloque1-continuar"
          class="w-full rounded-xl bg-Ipteblue px-4 py-3 text-sm font-semibold
                 text-white shadow-sm hover:bg-Ipteblue/90 transition-colors
                 focus:outline-none focus:ring-2 focus:ring-Ipteblue/50">
          Continuar al Paso 2 →
        </button>
      </div>

    </div><!-- /right form -->
  </div><!-- /grid -->
</div><!-- /bloque-1 -->
