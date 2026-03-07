<?php defined('APP') or die('Access denied'); ?>

<!-- ================================================================
     BLOQUE 3 – INVERSOR
================================================================ -->
<div id="bloque-3" class="card card-primary card-outline d-none">

  <!-- Block header -->
  <div class="card-header d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center">
      <span class="badge badge-primary mr-2" style="font-size:.85rem;width:1.6rem;height:1.6rem;line-height:1.6rem;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;">3</span>
      <div class="ml-1">
        <h5 class="mb-0">Inversor</h5>
        <small class="text-muted">Configura la cadena y selecciona el inversor del inventario</small>
      </div>
    </div>
    <button type="button" id="btn-bloque3-volver" class="btn btn-link btn-sm text-muted p-0">
      <i class="fas fa-chevron-left mr-1"></i>Volver al Paso 2
    </button>
  </div>

  <!-- ── String Configurator ─────────────────────────────────────── -->
  <div class="card-body border-bottom bg-light py-3">
    <p class="text-muted text-uppercase font-weight-bold small mb-3">
      Configuración de Cadena (String)
    </p>
    <div class="row">

      <!-- Ns stepper -->
      <div class="col-sm-3 mb-2">
        <p class="text-muted small mb-1">Módulos en serie <strong>(Ns)</strong></p>
        <div class="d-flex align-items-center">
          <button id="btn-ns-dec" type="button" disabled
            class="btn btn-sm btn-default" style="width:32px;height:32px;padding:0;">−</button>
          <span id="ns-value" class="font-weight-bold h5 mb-0 mx-2">—</span>
          <button id="btn-ns-inc" type="button" disabled
            class="btn btn-sm btn-default" style="width:32px;height:32px;padding:0;">+</button>
        </div>
        <small id="ns-range-hint" class="text-muted">Cargando…</small>
      </div>

      <!-- Np auto -->
      <div class="col-sm-3 mb-2">
        <p class="text-muted small mb-1">Strings en paralelo <strong>(Np)</strong></p>
        <p id="np-value" class="font-weight-bold h5 mb-0">—</p>
        <small id="np-mppt-hint" class="text-muted">Selecciona un inversor para verificar</small>
      </div>

      <!-- Total array area -->
      <div class="col-sm-3 mb-2">
        <p class="text-muted small mb-1">Superficie total del arreglo</p>
        <p id="str-area-total" class="font-weight-bold h5 mb-0">—</p>
        <small class="text-muted">m² (área neta de módulos)</small>
      </div>

      <!-- String voltages preview -->
      <div class="col-sm-3 mb-2">
        <div class="row text-center">
          <div class="col-4">
            <small class="text-muted d-block mb-1">Voc frío</small>
            <p id="str-voc-cold" class="font-weight-bold text-danger mb-0 small">—</p>
            <small class="text-muted">seguridad</small>
          </div>
          <div class="col-4">
            <small class="text-muted d-block mb-1">Vmpp calor</small>
            <p id="str-vmpp-hot" class="font-weight-bold text-warning mb-0 small">—</p>
            <small class="text-muted">MPPT mín</small>
          </div>
          <div class="col-4">
            <small class="text-muted d-block mb-1">Vmpp frío</small>
            <p id="str-vmpp-cold" class="font-weight-bold text-primary mb-0 small">—</p>
            <small class="text-muted">MPPT máx</small>
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- ── Remainder string warning ──────────────────────────────────── -->
  <div id="str-remainder-warning" class="alert alert-warning d-none mx-3 mt-3 mb-0">
    <div class="d-flex">
      <span class="mr-2"><i class="fas fa-exclamation-triangle"></i></span>
      <div class="flex-fill">
        <p class="font-weight-bold mb-1">String incompleto detectado</p>
        <p id="str-rem-breakdown" class="mb-2 small"></p>
        <div class="row mb-2 small">
          <div class="col-4">
            <span class="text-muted d-block">Voc frío</span>
            <strong id="str-rem-voc-cold">—</strong>
          </div>
          <div class="col-4">
            <span class="text-muted d-block">Vmpp calor</span>
            <strong id="str-rem-vmpp-hot">—</strong>
          </div>
          <div class="col-4">
            <span class="text-muted d-block">Vmpp frío</span>
            <strong id="str-rem-vmpp-cold">—</strong>
          </div>
        </div>
        <p id="str-rem-advice" class="small mb-1"></p>
        <p id="str-rem-mppt-note" class="d-none mt-2 font-weight-bold small mb-0"></p>
      </div>
    </div>
  </div>

  <!-- ── Assumption note ──────────────────────────────────────────── -->
  <div class="alert alert-info mx-3 mt-3 mb-0 small">
    <i class="fas fa-info-circle mr-1"></i>
    <strong>Supuesto de diseño:</strong> Este sistema asume <strong>1 string por entrada MPPT</strong> para evitar la necesidad de caja combinadora. Np = número de strings = número de entradas MPPT utilizadas. Si Np supera las entradas disponibles del inversor, se debe <strong>aumentar Ns</strong> (strings más largas &rarr; menos strings en paralelo).
  </div>

  <!-- ── Filters ──────────────────────────────────────────────────── -->
  <div class="card-body border-bottom py-2">
    <div class="d-flex flex-wrap align-items-center">

      <div class="d-flex align-items-center flex-wrap mr-4 mb-1">
        <span class="text-muted text-uppercase font-weight-bold small mr-2">Fabricante:</span>
        <div id="filter-inv-manufacturer" class="d-flex flex-wrap">
          <!-- populated by JS -->
        </div>
      </div>

      <div class="d-flex align-items-center flex-wrap mb-1">
        <span class="text-muted text-uppercase font-weight-bold small mr-2">Fase:</span>
        <div id="filter-inv-phase" class="d-flex flex-wrap">
          <!-- populated by JS -->
        </div>
      </div>

    </div>
  </div>

  <!-- ── Inverter card grid ────────────────────────────────────────── -->
  <div class="card-body">

    <div id="inverters-loading" class="text-center py-4 text-muted">
      <i class="fas fa-spinner fa-spin mr-1"></i>
      <span>Cargando inversores del inventario…</span>
    </div>

    <div id="inverters-error" class="alert alert-danger d-none"></div>

    <div id="inverters-grid" class="row">
      <!-- cards injected by JS -->
    </div>

    <p id="inverters-empty" class="d-none text-center text-muted py-4">
      No hay inversores que coincidan con los filtros seleccionados.
    </p>

  </div>

  <!-- ── Selected inverter + electrical check results ─────────────── -->
  <div id="calc3-results" class="card-body border-top d-none">

    <!-- Selected inverter pill + deselect -->
    <div class="d-flex align-items-center justify-content-between mb-3">
      <div class="d-flex align-items-center">
        <span class="text-muted text-uppercase font-weight-bold small mr-2">Inversor seleccionado</span>
        <span id="selected-inverter-name" class="badge badge-primary">—</span>
      </div>
      <button type="button" id="btn-deselect-inverter" class="btn btn-link btn-sm text-muted p-0">
        <i class="fas fa-times mr-1"></i>Quitar selección
      </button>
    </div>

    <!-- Datasheet quick view -->
    <div id="selected-inverter-specs"
      class="row small bg-light rounded p-2 mb-3">
      <!-- populated by JS -->
    </div>

    <!-- Electrical check cards -->
    <p class="text-muted text-uppercase font-weight-bold small mb-2">
      Verificación Eléctrica
    </p>
    <div class="row">

      <!-- Hard: Voc cold vs Vdc max -->
      <div class="col-sm-6 col-lg-4 mb-3">
        <div id="chk-voc" class="card card-outline card-default h-100">
          <div class="card-body p-3">
            <div class="d-flex align-items-start justify-content-between mb-1">
              <small class="text-muted">Voc en frío</small>
              <span data-badge class="badge badge-secondary">—</span>
            </div>
            <p data-actual class="h5 font-weight-bold mb-1">—</p>
            <small data-limit class="text-muted">límite —</small>
          </div>
        </div>
      </div>

      <!-- Soft: Vmpp hot vs MPPT min -->
      <div class="col-sm-6 col-lg-4 mb-3">
        <div id="chk-vmpp-hot" class="card card-outline card-default h-100">
          <div class="card-body p-3">
            <div class="d-flex align-items-start justify-content-between mb-1">
              <small class="text-muted">Vmpp en calor (MPPT mín)</small>
              <span data-badge class="badge badge-secondary">—</span>
            </div>
            <p data-actual class="h5 font-weight-bold mb-1">—</p>
            <small data-limit class="text-muted">límite —</small>
          </div>
        </div>
      </div>

      <!-- Soft: Vmpp hot vs startup voltage -->
      <div class="col-sm-6 col-lg-4 mb-3">
        <div id="chk-startup-v" class="card card-outline card-default h-100">
          <div class="card-body p-3">
            <div class="d-flex align-items-start justify-content-between mb-1">
              <small class="text-muted">Vmpp en calor (V arranque)</small>
              <span data-badge class="badge badge-secondary">—</span>
            </div>
            <p data-actual class="h5 font-weight-bold mb-1">—</p>
            <small data-limit class="text-muted">límite —</small>
          </div>
        </div>
      </div>

      <!-- Soft: Vmpp cold vs MPPT max -->
      <div class="col-sm-6 col-lg-4 mb-3">
        <div id="chk-vmpp-cold" class="card card-outline card-default h-100">
          <div class="card-body p-3">
            <div class="d-flex align-items-start justify-content-between mb-1">
              <small class="text-muted">Vmpp en frío (MPPT máx)</small>
              <span data-badge class="badge badge-secondary">—</span>
            </div>
            <p data-actual class="h5 font-weight-bold mb-1">—</p>
            <small data-limit class="text-muted">límite —</small>
          </div>
        </div>
      </div>

      <!-- Hard: I per MPPT (1 string per MPPT assumed) -->
      <div class="col-sm-6 col-lg-4 mb-3">
        <div id="chk-i-mppt" class="card card-outline card-default h-100">
          <div class="card-body p-3">
            <div class="d-flex align-items-start justify-content-between mb-1">
              <small class="text-muted">Corriente por MPPT <span class="text-muted">(1 string)</span></small>
              <span data-badge class="badge badge-secondary">—</span>
            </div>
            <p data-actual class="h5 font-weight-bold mb-1">—</p>
            <small data-limit class="text-muted">límite —</small>
          </div>
        </div>
      </div>

      <!-- Hard: Np vs mppt_count -->
      <div class="col-sm-6 col-lg-4 mb-3">
        <div id="chk-np-mppt" class="card card-outline card-default h-100">
          <div class="card-body p-3">
            <div class="d-flex align-items-start justify-content-between mb-1">
              <small class="text-muted">Strings vs. entradas MPPT</small>
              <span data-badge class="badge badge-secondary">—</span>
            </div>
            <p data-actual class="h5 font-weight-bold mb-1">—</p>
            <small data-limit class="text-muted">límite —</small>
          </div>
        </div>
      </div>

      <!-- Hard: Isc total -->
      <div class="col-sm-6 col-lg-4 mb-3">
        <div id="chk-i-total" class="card card-outline card-default h-100">
          <div class="card-body p-3">
            <div class="d-flex align-items-start justify-content-between mb-1">
              <small class="text-muted">I<sub>sc</sub> total DC</small>
              <span data-badge class="badge badge-secondary">—</span>
            </div>
            <p data-actual class="h5 font-weight-bold mb-1">—</p>
            <small data-limit class="text-muted">límite —</small>
          </div>
        </div>
      </div>

      <!-- Hard: P array cold vs pmax_dc_input -->
      <div class="col-sm-6 col-lg-4 mb-3">
        <div id="chk-p-dc" class="card card-outline card-default h-100">
          <div class="card-body p-3">
            <div class="d-flex align-items-start justify-content-between mb-1">
              <small class="text-muted">P arreglo en frío</small>
              <span data-badge class="badge badge-secondary">—</span>
            </div>
            <p data-actual class="h5 font-weight-bold mb-1">—</p>
            <small data-limit class="text-muted">límite —</small>
          </div>
        </div>
      </div>

      <!-- Informational: DC/AC ratio -->
      <div class="col-sm-6 col-lg-4 mb-3">
        <div class="card card-outline card-default h-100">
          <div class="card-body p-3">
            <div class="d-flex align-items-start justify-content-between mb-1">
              <small class="text-muted">Relación DC/AC</small>
              <span id="res-dcac-hint" class="text-muted small">—</span>
            </div>
            <p id="res-dcac" class="h5 font-weight-bold mb-1">—</p>
            <small class="text-muted">P<sub>STC</sub> / P<sub>AC nom</sub></small>
            <div class="mt-1 row small">
              <span class="col-6 text-muted">P<sub>STC</sub></span>
              <span id="res-dcac-pstc" class="col-6 font-weight-bold">—</span>
              <span class="col-6 text-muted">P<sub>AC nom</sub></span>
              <span id="res-dcac-pac" class="col-6 font-weight-bold">—</span>
            </div>
            <!-- Range legend -->
            <div class="mt-2 border-top pt-2 small">
              <div class="d-flex justify-content-between"><span class="text-danger">&lt; 0.80</span><span class="text-muted">Arreglo insuficiente</span></div>
              <div class="d-flex justify-content-between"><span class="text-warning">0.80 &ndash; 1.00</span><span class="text-muted">Subóptimo</span></div>
              <div class="d-flex justify-content-between"><span class="text-success">1.00 &ndash; 1.25</span><span class="text-muted">Conservador</span></div>
              <div class="d-flex justify-content-between"><span class="text-success font-weight-bold">1.25 &ndash; 1.50</span><span class="text-muted">Óptimo</span></div>
              <div class="d-flex justify-content-between"><span class="text-danger">&gt; 1.50</span><span class="text-muted">Sobredimensionado</span></div>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- String config summary -->
    <div id="selected-string-config" class="alert alert-light border small mb-3">
      Configuración: —
    </div>

  </div>

  <!-- Continue button -->
  <div class="card-footer">
    <button type="button" id="btn-bloque3-continuar" disabled
      class="btn btn-primary btn-block">
      Continuar al Paso 4 &rarr;
    </button>
  </div>

</div><!-- /bloque-3 -->
