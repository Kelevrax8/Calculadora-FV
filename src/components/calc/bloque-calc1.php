<?php defined('APP') or die('Access denied'); ?>

<!-- ================================================================
     BLOQUE 1 – UBICACIÓN Y DATOS SOLARES
================================================================ -->
<div id="bloque-1" class="card card-primary card-outline mb-3">

  <!-- Block header -->
  <div class="card-header">
    <h3 class="card-title">
      <span class="badge badge-primary mr-2">1</span>
      Ubicación y Consumo Energético
    </h3>
    <div class="card-tools">
      <small class="text-muted">Selecciona la ubicación en el mapa y captura el consumo anual</small>
    </div>
  </div>

  <div class="card-body p-0">
    <!-- Two-column layout: map | form -->
    <div class="row no-gutters">

      <!-- ── LEFT: Map -->
      <div class="col-lg-6 position-relative border-right">
        <div id="map" class="w-100" style="height: 480px;"></div>
        <!-- Coordinate badge overlay -->
        <div id="coord-badge"
             class="position-absolute d-none"
             style="bottom:1rem; left:50%; transform:translateX(-50%); background:rgba(255,255,255,.92);
                    border-radius:.5rem; padding:.4rem 1rem; box-shadow:0 1px 4px rgba(0,0,0,.15);
                    font-size:.75rem; color:#555; pointer-events:none; white-space:nowrap;">
          <i class="fas fa-map-marker-alt text-primary mr-1"></i>
          Lat: <strong id="badge-lat" class="text-dark">—</strong>
          &nbsp;|&nbsp;
          Lng: <strong id="badge-lng" class="text-dark">—</strong>
        </div>
      </div>

      <!-- ── RIGHT: Form -->
      <div class="col-lg-6 p-4 d-flex flex-column">

        <!-- Section: Coordinates -->
        <fieldset class="mb-4">
          <legend class="font-weight-bold text-muted text-uppercase mb-2" style="font-size:.7rem;">
            Coordenadas Seleccionadas
          </legend>
          <div class="row">
            <div class="col-6">
              <div class="form-group mb-2">
                <label for="latitud" class="mb-1">Latitud</label>
                <input type="text" id="latitud" name="latitud" 
                  placeholder="Haz clic en el mapa"
                  class="form-control form-control-sm">
              </div>
            </div>
            <div class="col-6">
              <div class="form-group mb-2">
                <label for="longitud" class="mb-1">Longitud</label>
                <input type="text" id="longitud" name="longitud" 
                  placeholder="Haz clic en el mapa"
                  class="form-control form-control-sm">
              </div>
            </div>
          </div>
        </fieldset>

        <!-- Section: Consumption -->
        <fieldset class="mb-4">
          <legend class="font-weight-bold text-muted text-uppercase mb-2" style="font-size:.7rem;">
            Consumo Energético
          </legend>
          <div class="form-group mb-2">
            <label for="consumo_anual_kwh">Consumo Anual <small class="text-muted">(kWh/año)</small></label>
            <input type="number" id="consumo_anual_kwh" name="consumo_anual_kwh"
              min="0" step="1" placeholder="Ej. 3650"
              class="form-control form-control-sm">
            <small class="text-muted">Puedes encontrarlo en tu recibo de CFE anual (suma de los consumos mensuales)</small>
          </div>
          <div class="form-group mb-1">
            <label for="cobertura_pct">
              Cobertura del Consumo
              <small class="text-muted">(% del consumo)</small>
            </label>
            <div class="input-group input-group-sm">
              <input type="number" id="cobertura_pct" name="cobertura_pct"
                min="1" max="100" step="1" value="100"
                class="form-control form-control-sm">
              <div class="input-group-append">
                <span class="input-group-text">%</span>
              </div>
            </div>
            <small class="text-muted">
              Porcentaje del consumo que el sistema FV cubrirá. 100% = autoabastecimiento total;
              valores menores permiten dimensionar con menor inversión inicial.
            </small>
          </div>
        </fieldset>

        <!-- Section: Solar Data -->
        <fieldset class="mb-4">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <legend class="font-weight-bold text-muted text-uppercase mb-0" style="font-size:.7rem;">
              Datos Solares
            </legend>
            <button type="button" id="btn-nasa-api" 
              title="Selecciona primero una ubicación en el mapa"
              class="btn btn-xs btn-default border">
              <i class="fas fa-sun mr-1"></i>Obtener de NASA POWER
            </button>
          </div>

          <!-- Error banner -->
          <div id="nasa-error" class="alert alert-danger d-none py-1 px-2 mb-2" style="font-size:.8rem;"></div>

          <!-- HSP -->
          <div class="form-group mb-2">
            <div class="d-flex align-items-center justify-content-between mb-1">
              <label for="hsp" class="mb-0">
                Irradiación Mensual <small class="text-muted">(kWh/m²/día)</small>
              </label>
              <div id="hsp-mode-toggle" class="btn-group btn-group-xs d-none">
                <button type="button" data-mode="avg" class="hsp-mode-btn btn btn-primary btn-xs">Promedio</button>
                <button type="button" data-mode="min" class="hsp-mode-btn btn btn-default btn-xs">Mínimo</button>
              </div>
            </div>
            <input type="number" id="hsp" name="hsp_kwh_m2_dia"
              min="0" step="0.01" placeholder="Selecciona una ubicación y consulta la NASA" readonly
              class="form-control form-control-sm bg-light">
            <small id="hsp-mode-hint" class="text-muted d-none"></small>
          </div>

          <!-- Tmin / Tmax -->
          <div class="row">
            <div class="col-6">
              <div class="form-group mb-0">
                <label for="tmin">T. Mínima <small class="text-muted">(°C)</small></label>
                <input type="number" id="tmin" name="tmin_ambiente"
                  step="0.1" placeholder="—" readonly
                  class="form-control form-control-sm bg-light">
              </div>
            </div>
            <div class="col-6">
              <div class="form-group mb-0">
                <label for="tmax">T. Máxima <small class="text-muted">(°C)</small></label>
                <input type="number" id="tmax" name="tmax_ambiente"
                  step="0.1" placeholder="—" readonly
                  class="form-control form-control-sm bg-light">
              </div>
            </div>
          </div>
        </fieldset>

        <!-- Continue button -->
        <div class="mt-auto">
          <button type="button" id="btn-bloque1-continuar" class="btn btn-primary btn-block">
            Continuar al Paso 2 <i class="fas fa-arrow-right ml-1"></i>
          </button>
        </div>

      </div><!-- /right form -->
    </div><!-- /row -->
  </div><!-- /card-body -->
</div><!-- /bloque-1 -->
