<?php defined('APP') or die('Access denied'); ?>

<!-- ================================================================
     BLOQUE 4 – RESUMEN Y PROTECCIONES
================================================================ -->
<div id="bloque-4" class="card card-primary card-outline d-none">

  <!-- Block header -->
  <div class="card-header d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center">
      <span class="badge badge-primary mr-2" style="font-size:.85rem;width:1.6rem;height:1.6rem;line-height:1.6rem;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;">4</span>
      <div class="ml-1">
        <h5 class="mb-0">Resumen del Sistema</h5>
        <small class="text-muted">Parámetros, compatibilidad y protecciones eléctricas</small>
      </div>
    </div>
    <button type="button" id="btn-bloque4-volver" class="btn btn-link btn-sm text-muted p-0">
      <i class="fas fa-chevron-left mr-1"></i>Volver al Paso 3
    </button>
  </div>

  <!-- ── Overall Verdict ────────────────────────────────────────── -->
  <div class="card-body pb-2">
    <div id="verdict-banner" class="alert mb-0">
      <!-- populated by JS -->
    </div>
  </div>

  <!-- ── Site & Design Parameters ──────────────────────────────── -->
  <div class="card-body border-top">
    <p class="text-muted text-uppercase font-weight-bold small mb-3">Parámetros de Sitio y Diseño</p>
    <div class="row">

      <div class="col-sm-6 col-lg-3 mb-2">
        <div class="card bg-light border-0 h-100">
          <div class="card-body p-3">
            <p class="text-muted small mb-1">Ubicación</p>
            <p id="s4-location" class="font-weight-bold small mb-0">—</p>
          </div>
        </div>
      </div>

      <div class="col-sm-6 col-lg-3 mb-2">
        <div class="card bg-light border-0 h-100">
          <div class="card-body p-3">
            <p class="text-muted small mb-1">Consumo anual</p>
            <p id="s4-consumption" class="font-weight-bold small mb-0">—</p>
          </div>
        </div>
      </div>

      <div class="col-sm-6 col-lg-3 mb-2">
        <div class="card bg-light border-0 h-100">
          <div class="card-body p-3">
            <p class="text-muted small mb-1">HSP de diseño</p>
            <p id="s4-hsp" class="font-weight-bold small mb-0">—</p>
          </div>
        </div>
      </div>

      <div class="col-sm-6 col-lg-3 mb-2">
        <div class="card bg-light border-0 h-100">
          <div class="card-body p-3">
            <p class="text-muted small mb-1">Temp. mín / máx</p>
            <p id="s4-temps" class="font-weight-bold small mb-0">—</p>
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- ── PV Array ────────────────────────────────────────────────── -->
  <div class="card-body border-top">
    <p class="text-muted text-uppercase font-weight-bold small mb-3">Arreglo Fotovoltaico</p>
    <div class="row">

      <div class="col-sm-6 mb-2">
        <div class="card bg-light border-0 h-100">
          <div class="card-body p-3">
            <p class="text-muted small mb-1">Módulo</p>
            <p id="s4-module-name" class="font-weight-bold small mb-0">—</p>
          </div>
        </div>
      </div>

      <div class="col-sm-6 col-lg-3 mb-2">
        <div class="card bg-light border-0 h-100">
          <div class="card-body p-3">
            <p class="text-muted small mb-1">Potencia unitaria</p>
            <p id="s4-module-power" class="font-weight-bold small mb-0">—</p>
          </div>
        </div>
      </div>

      <div class="col-sm-6 col-lg-3 mb-2">
        <div class="card bg-light border-0 h-100">
          <div class="card-body p-3">
            <p class="text-muted small mb-1">Módulos totales</p>
            <p id="s4-total-modules" class="font-weight-bold small mb-0">—</p>
          </div>
        </div>
      </div>

      <div class="col-sm-6 col-lg-3 mb-2">
        <div class="card bg-light border-0 h-100">
          <div class="card-body p-3">
            <p class="text-muted small mb-1">Potencia pico total</p>
            <p id="s4-array-power" class="font-weight-bold small mb-0">—</p>
          </div>
        </div>
      </div>

      <div class="col-sm-6 col-lg-3 mb-2">
        <div class="card bg-light border-0 h-100">
          <div class="card-body p-3">
            <p class="text-muted small mb-1">Vmpp del arreglo</p>
            <p id="s4-vmpp-array" class="font-weight-bold small mb-0">—</p>
          </div>
        </div>
      </div>

      <div class="col-sm-6 col-lg-3 mb-2">
        <div class="card bg-light border-0 h-100">
          <div class="card-body p-3">
            <p class="text-muted small mb-1">Voc del arreglo</p>
            <p id="s4-voc-array" class="font-weight-bold small mb-0">—</p>
          </div>
        </div>
      </div>

      <div class="col-sm-6 col-lg-3 mb-2">
        <div class="card bg-light border-0 h-100">
          <div class="card-body p-3">
            <p class="text-muted small mb-1">Isc del arreglo</p>
            <p id="s4-isc-array" class="font-weight-bold small mb-0">—</p>
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- ── Inverter ────────────────────────────────────────────────── -->
  <div class="card-body border-top">
    <p class="text-muted text-uppercase font-weight-bold small mb-3">Inversor</p>
    <div class="row">

      <div class="col-sm-6 mb-2">
        <div class="card bg-light border-0 h-100">
          <div class="card-body p-3">
            <p class="text-muted small mb-1">Inversor</p>
            <p id="s4-inverter-name" class="font-weight-bold small mb-0">—</p>
          </div>
        </div>
      </div>

      <div class="col-sm-6 col-lg-3 mb-2">
        <div class="card bg-light border-0 h-100">
          <div class="card-body p-3">
            <p class="text-muted small mb-1">Potencia AC nominal</p>
            <p id="s4-inverter-power" class="font-weight-bold small mb-0">—</p>
          </div>
        </div>
      </div>

      <div class="col-sm-6 col-lg-3 mb-2">
        <div class="card bg-light border-0 h-100">
          <div class="card-body p-3">
            <p class="text-muted small mb-1">Rango MPPT</p>
            <p id="s4-mppt-range" class="font-weight-bold small mb-0">—</p>
          </div>
        </div>
      </div>

      <div class="col-sm-6 col-lg-3 mb-2">
        <div class="card bg-light border-0 h-100">
          <div class="card-body p-3">
            <p class="text-muted small mb-1">Corriente máx. entrada DC</p>
            <p id="s4-inverter-imax" class="font-weight-bold small mb-0">—</p>
          </div>
        </div>
      </div>

      <div class="col-sm-6 col-lg-3 mb-2">
        <div class="card bg-light border-0 h-100">
          <div class="card-body p-3">
            <p class="text-muted small mb-1">Tensión de arranque</p>
            <p id="s4-startup-voltage" class="font-weight-bold small mb-0">—</p>
          </div>
        </div>
      </div>

      <div class="col-sm-6 col-lg-3 mb-2">
        <div class="card bg-light border-0 h-100">
          <div class="card-body p-3">
            <p class="text-muted small mb-1">Tensión AC nominal</p>
            <p id="s4-ac-voltage" class="font-weight-bold small mb-0">—</p>
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- ── Compatibility Checks ────────────────────────────────────── -->
  <div class="card-body border-top">
    <p class="text-muted text-uppercase font-weight-bold small mb-3">Verificación de Compatibilidad</p>
    <div id="s4-compat-table" class="list-group">
      <!-- rows injected by JS -->
    </div>
  </div>

  <!-- ── Energy Estimate ─────────────────────────────────────────── -->
  <div class="card-body border-top">
    <p class="text-muted text-uppercase font-weight-bold small mb-3">Estimación de Producción Energética</p>
    <div class="row">

      <div class="col-sm-6 col-lg-3 mb-2">
        <div class="card bg-light border-0 h-100">
          <div class="card-body p-3">
            <p class="text-muted small mb-1">Producción estimada</p>
            <p id="s4-energy-production" class="font-weight-bold small mb-0">—</p>
          </div>
        </div>
      </div>

      <div class="col-sm-6 col-lg-3 mb-2">
        <div class="card bg-light border-0 h-100">
          <div class="card-body p-3">
            <p class="text-muted small mb-1">Consumo anual cubierto</p>
            <p id="s4-self-sufficiency" class="font-weight-bold small mb-0">—</p>
          </div>
        </div>
      </div>

      <div class="col-sm-6 col-lg-3 mb-2">
        <div class="card bg-light border-0 h-100">
          <div class="card-body p-3">
            <p class="text-muted small mb-1">Factor de rendimiento (PR)</p>
            <p id="s4-pr" class="font-weight-bold small mb-0">0.75</p>
          </div>
        </div>
      </div>

      <div class="col-sm-6 col-lg-3 mb-2">
        <div class="card bg-light border-0 h-100">
          <div class="card-body p-3">
            <p class="text-muted small mb-1">Relación DC/AC</p>
            <p id="s4-dcac-value" class="font-weight-bold small mb-0">—</p>
            <p id="s4-dcac-label" class="text-muted small mb-0">—</p>
          </div>
        </div>
      </div>

    </div>

    <!-- Monthly production table (only shown when NASA data is available) -->
    <div id="s4-monthly-section" class="d-none mt-3">

      <!-- Sub-header + toggle -->
      <div class="d-flex align-items-center justify-content-between mb-2">
        <p class="text-muted text-uppercase font-weight-bold small mb-0">Desglose Mensual</p>
        <div class="d-flex align-items-center">
          <span class="text-muted small mr-2">Consumo mensual real:</span>
          <div class="btn-group btn-group-sm" role="group">
            <button type="button" data-cons="off" id="btn-cons-off"
              class="cons-toggle-btn btn btn-primary">
              Solo producción
            </button>
            <button type="button" data-cons="on" id="btn-cons-on"
              class="cons-toggle-btn btn btn-default">
              Ingresar consumo
            </button>
          </div>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-sm table-bordered small mb-0">
          <thead class="thead-light">
            <tr>
              <th>Mes</th>
              <th class="text-right">GHI diario<br/><span class="font-weight-normal">(kWh/m²/día)</span></th>
              <th class="text-right">Días</th>
              <th class="text-right">Producción<br/><span class="font-weight-normal">(kWh)</span></th>
              <th class="cons-col d-none text-right">Consumo real<br/><span class="font-weight-normal">(kWh)</span></th>
              <th class="cons-col d-none text-right">Balance<br/><span class="font-weight-normal">(kWh)</span></th>
            </tr>
          </thead>
          <tbody id="s4-monthly-tbody">
            <!-- rows injected by JS -->
          </tbody>
          <tfoot id="s4-monthly-tfoot" class="font-weight-bold">
            <!-- totals row injected by JS -->
          </tfoot>
        </table>
      </div>
    </div>
  </div>

  <!-- ── Electrical Protection ──────────────────────────────────── -->
  <div class="card-body border-top">

    <!-- Section header + derating toggle -->
    <div class="d-flex align-items-center justify-content-between mb-3">
      <p class="text-muted text-uppercase font-weight-bold small mb-0">
        Protecciones Eléctricas
        <span class="text-lowercase font-weight-normal text-muted ml-1">(NOM-001-SEDE-2012, Art. 690.8)</span>
      </p>
      <div class="d-flex align-items-center">
        <span class="text-muted small mr-2">Corrección por temperatura:</span>
        <div class="btn-group btn-group-sm" role="group">
          <button type="button" data-derating="off" id="btn-derating-off"
            class="derating-btn btn btn-primary">
            Sin corrección
          </button>
          <button type="button" data-derating="on" id="btn-derating-on"
            class="derating-btn btn btn-default">
            Con corrección
          </button>
        </div>
      </div>
    </div>

    <p id="derating-hint" class="text-muted small mb-3 d-none">
      <!-- populated by JS -->
    </p>

    <div class="row">

      <!-- DC String circuit -->
      <div class="col-sm-6 mb-3">
        <div class="card card-outline card-default h-100">
          <div class="card-header py-2">
            <p class="mb-0 font-weight-bold small">Circuito DC &mdash; String &rarr; Inversor</p>
          </div>
          <div class="card-body p-0">
            <table class="table table-sm mb-0">
              <tbody>
                <tr>
                  <td class="text-muted small">Isc módulo</td>
                  <td id="prot-isc-module" class="font-weight-bold text-right small">—</td>
                </tr>
                <tr>
                  <td class="text-muted small">Corriente de diseño (&times; 1.56)</td>
                  <td id="prot-dc-idesign" class="font-weight-bold text-right small">—</td>
                </tr>
                <tr id="prot-dc-derated-row" class="d-none">
                  <td class="text-muted small">Corriente requerida en tabla (corr. temp.)</td>
                  <td id="prot-dc-derated" class="font-weight-bold text-right small">—</td>
                </tr>
                <tr class="table-info">
                  <td class="small font-weight-bold">Protección recomendada</td>
                  <td id="prot-dc-ocpd" class="font-weight-bold text-right small">—</td>
                </tr>
                <tr class="table-info">
                  <td class="small font-weight-bold">Calibre conductor</td>
                  <td id="prot-dc-awg" class="font-weight-bold text-right small">—</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- AC Output circuit -->
      <div class="col-sm-6 mb-3">
        <div class="card card-outline card-default h-100">
          <div class="card-header py-2">
            <p class="mb-0 font-weight-bold small">Circuito AC &mdash; Inversor &rarr; Tablero</p>
          </div>
          <div class="card-body p-0">
            <table class="table table-sm mb-0">
              <tbody>
                <tr>
                  <td class="text-muted small">Potencia AC / Tensión nominal (<span id="prot-ac-phase">—</span>)</td>
                  <td id="prot-ac-ratio" class="font-weight-bold text-right small">—</td>
                </tr>
                <tr>
                  <td class="text-muted small">Corriente de diseño (&times; 1.25)</td>
                  <td id="prot-ac-idesign" class="font-weight-bold text-right small">—</td>
                </tr>
                <tr id="prot-ac-derated-row" class="d-none">
                  <td class="text-muted small">Corriente requerida en tabla (corr. temp.)</td>
                  <td id="prot-ac-derated" class="font-weight-bold text-right small">—</td>
                </tr>
                <tr class="table-info">
                  <td class="small font-weight-bold">Protección recomendada</td>
                  <td id="prot-ac-ocpd" class="font-weight-bold text-right small">—</td>
                </tr>
                <tr class="table-info">
                  <td class="small font-weight-bold">Calibre conductor</td>
                  <td id="prot-ac-awg" class="font-weight-bold text-right small">—</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- ── Actions ─────────────────────────────────────────────────── -->
  <div class="card-footer d-flex flex-column flex-sm-row" style="gap:.75rem;">
    <button type="button" id="btn-reiniciar"
      class="btn btn-outline-secondary flex-fill">
      &larr; Nuevo cálculo
    </button>
    <button type="button" id="btn-excel-export"
      class="btn btn-primary flex-fill">
      <i class="fas fa-download mr-2"></i>Exportar Excel (.xlsx)
    </button>
  </div>

</div><!-- /bloque-4 -->
