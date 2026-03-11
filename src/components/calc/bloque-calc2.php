<?php defined('APP') or die('Access denied'); ?>

<!-- ================================================================
     BLOQUE 2 – MÓDULO FOTOVOLTAICO
================================================================ -->
<div id="bloque-2" class="card card-primary card-outline mb-3 d-none">

  <!-- Block header -->
  <div class="card-header">
    <h3 class="card-title">
      <span class="badge badge-primary mr-2">2</span>
      Módulo Fotovoltaico
    </h3>
    <div class="card-tools">
      <button type="button" id="btn-bloque2-volver" class="btn btn-xs btn-default">
        <i class="fas fa-chevron-left mr-1"></i>Volver al Paso 1
      </button>
    </div>
  </div>

  <!-- Filters -->
  <div class="card-header border-bottom-0 pt-2 pb-2">
    <div class="d-flex flex-wrap align-items-center" style="gap:.75rem;">
      <div class="d-flex align-items-center flex-wrap" style="gap:.4rem;">
        <span class="text-muted font-weight-bold text-uppercase mr-1" style="font-size:.7rem;">Fabricante:</span>
        <div id="filter-manufacturer" class="d-flex flex-wrap" style="gap:.3rem;"></div>
      </div>
      <div class="d-flex align-items-center flex-wrap" style="gap:.4rem;">
        <span class="text-muted font-weight-bold text-uppercase mr-1" style="font-size:.7rem;">Tecnología:</span>
        <div id="filter-technology" class="d-flex flex-wrap" style="gap:.3rem;"></div>
      </div>
    </div>
  </div>

  <div class="card-body">

    <!-- Loading state -->
    <div id="modules-loading" class="text-center py-4 text-muted">
      <i class="fas fa-spinner fa-spin mr-1"></i> Cargando módulos del inventario…
    </div>

    <!-- Error state -->
    <div id="modules-error" class="alert alert-danger d-none"></div>

    <!-- Card grid -->
    <div id="modules-grid" class="row"></div>

    <!-- Empty state -->
    <p id="modules-empty" class="text-center text-muted d-none py-3">
      No hay módulos que coincidan con los filtros seleccionados.
    </p>

    <!-- ── Selected module summary + live results -->
    <div id="calc2-results" class="d-none mt-4 border-top pt-4">

      <!-- Selected module pill -->
      <div class="d-flex align-items-center justify-content-between mb-3">
        <div class="d-flex align-items-center">
          <span class="text-muted text-uppercase mr-2" style="font-size:.7rem; font-weight:600;">Módulo seleccionado</span>
          <span id="selected-module-name" class="badge badge-primary px-2">—</span>
        </div>
        <button type="button" id="btn-deselect-module" class="btn btn-xs btn-default text-danger">
          <i class="fas fa-times mr-1"></i>Quitar selección
        </button>
      </div>

      <!-- Datasheet quick view -->
      <div id="selected-module-specs"
        class="row bg-light rounded px-3 py-2 mb-4 mx-0">
        <!-- populated by JS -->
      </div>

      <!-- Live calculation results -->
      <p class="text-muted text-uppercase mb-2" style="font-size:.7rem; font-weight:600;">
        Resultados Preliminares del Arreglo
      </p>
      <div class="row">

        <div class="col-6 col-lg-3 mb-2">
          <div class="info-box mb-0">
            <span class="info-box-icon bg-primary"><i class="fas fa-solar-panel"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Módulos requeridos</span>
              <span id="res-n-modulos" class="info-box-number">—</span>
              <span class="info-box-text">unidades</span>
            </div>
          </div>
        </div>

        <div class="col-6 col-lg-3 mb-2">
          <div class="info-box mb-0">
            <span class="info-box-icon bg-primary"><i class="fas fa-bolt"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Potencia arreglo STC</span>
              <span id="res-p-arreglo-stc" class="info-box-number">—</span>
              <span class="info-box-text">kW pico</span>
            </div>
          </div>
        </div>

        <div class="col-6 col-lg-3 mb-2">
          <div class="info-box mb-0">
            <span class="info-box-icon bg-warning"><i class="fas fa-temperature-high"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Potencia en calor</span>
              <span id="res-p-arreglo-calor" class="info-box-number text-warning">—</span>
              <span id="res-p-calor-pct" class="info-box-text">— vs STC</span>
            </div>
          </div>
        </div>

        <div class="col-6 col-lg-3 mb-2">
          <div class="info-box mb-0">
            <span class="info-box-icon bg-secondary"><i class="fas fa-shield-alt"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">I<sub>sc</sub> protección</span>
              <span id="res-isc-prot" class="info-box-number">—</span>
              <span class="info-box-text">A &middot; NOM-001</span>
            </div>
          </div>
        </div>

      </div><!-- /.row results -->
    </div><!-- /calc2-results -->

  </div><!-- /card-body -->

  <!-- Continue button -->
  <div class="card-footer" id="calc2-continue-wrap">
    <button type="button" id="btn-bloque2-continuar" disabled class="btn btn-primary btn-block">
      Continuar al Paso 3 <i class="fas fa-arrow-right ml-1"></i>
    </button>
  </div>

</div><!-- /bloque-2 -->
