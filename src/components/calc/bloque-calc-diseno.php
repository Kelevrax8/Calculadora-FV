<?php defined('APP') or die('Access denied'); ?>

<!-- ================================================================
     DISEÑO DEL SISTEMA – Módulo FV, cadena e inversor
     (replaces bloque-calc2.php + bloque-calc3.php)
================================================================ -->
<div id="bloque-diseno" class="card card-primary card-outline mb-3 d-none">

  <!-- Block header -->
  <div class="card-header d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center">
      <span class="badge badge-primary mr-2"
        style="font-size:.85rem;width:1.6rem;height:1.6rem;line-height:1.6rem;
               border-radius:50%;display:inline-flex;align-items:center;justify-content:center;">2</span>
      <div class="ml-1">
        <h5 class="mb-0">Diseño del Sistema</h5>
        <small class="text-muted">Módulo FV, configuración de cadena e inversor</small>
      </div>
    </div>
  </div>

  <div class="card-body">
    <div class="row">

      <!-- ════════════════════════════════════════════════════
           LEFT PANEL – selections & string config
      ════════════════════════════════════════════════════ -->
      <div class="col-lg-6 pr-lg-4">

        <!-- Loading state -->
        <div id="diseno-loading" class="text-center py-5 text-muted">
          <i class="fas fa-spinner fa-spin fa-2x mb-2 d-block"></i>
          Cargando inventario…
        </div>

        <!-- Error state -->
        <div id="diseno-error" class="alert alert-danger d-none"></div>

        <!-- ── 1. Module dropdown ──────────────────────────── -->
        <div id="module-section" class="d-none mb-4">
          <p class="text-muted text-uppercase font-weight-bold small mb-2">
            Módulo Fotovoltaico
          </p>
          <select id="module-select" class="form-control form-control-sm">
            <option value="">— Selecciona un módulo —</option>
          </select>

          <!-- Module preview card (shown after selection) -->
          <div id="module-card-preview" class="d-none mt-2 card card-outline card-primary">
            <div class="card-body p-3">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                  <p id="mod-preview-manufacturer" class="text-muted mb-0 small">—</p>
                  <p id="mod-preview-model" class="font-weight-bold mb-0">—</p>
                </div>
                <span id="mod-preview-tech-badge" class="badge badge-primary small">—</span>
              </div>
              <p id="mod-preview-power" class="h5 font-weight-bold text-primary mb-2">—</p>
              <div class="row no-gutters" style="font-size:.75rem;">
                <span class="col-4 text-muted">Voc</span>   <span id="mod-preview-voc"  class="col-8 font-weight-bold">—</span>
                <span class="col-4 text-muted">Isc</span>   <span id="mod-preview-isc"  class="col-8 font-weight-bold">—</span>
                <span class="col-4 text-muted">Vmpp</span>  <span id="mod-preview-vmpp" class="col-8 font-weight-bold">—</span>
                <span class="col-4 text-muted">Imp</span>   <span id="mod-preview-imp"  class="col-8 font-weight-bold">—</span>
                <span class="col-4 text-muted">βVoc</span>  <span id="mod-preview-bvoc" class="col-8 font-weight-bold">—</span>
                <span class="col-4 text-muted">γP</span>    <span id="mod-preview-gp"   class="col-8 font-weight-bold">—</span>
                <span class="col-4 text-muted mt-1">Área</span>       <span id="mod-preview-area" class="col-8 font-weight-bold mt-1">—</span>
                <span class="col-4 text-muted">Eficiencia</span> <span id="mod-preview-eta"  class="col-8 font-weight-bold text-success">—</span>
              </div>
            </div>
          </div>
        </div><!-- /module-section -->

        <!-- ── 2. String config (shown after module selected) ─ -->
        <div id="string-config-section" class="d-none mb-4">
          <p class="text-muted text-uppercase font-weight-bold small mb-2">
            Configuración de Cadena
          </p>

          <!-- Remainder string warning banner -->
          <blockquote id="str-remainder-warning"
            class="quote-info d-none mb-3 bg-gray-light">
            <div class="d-flex">
              <span class="mr-2"><i class="fas fa-exclamation-triangle"></i></span>
              <div class="flex-fill">
                <h5 class="font-weight-bold mb-1">String incompleto detectado</h5>
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
          </blockquote>

          <div class="row">

            <!-- Ns stepper -->
            <div class="col-6 col-sm-3 mb-3">
              <p class="text-muted small mb-1">Módulos en serie <strong>(Ns)</strong></p>
              <div class="d-flex align-items-center">
                <button id="btn-ns-dec" type="button" disabled
                  class="btn btn-sm btn-default" style="width:32px;height:32px;padding:0;">−</button>
                <span id="ns-value" class="font-weight-bold h5 mb-0 mx-2">—</span>
                <button id="btn-ns-inc" type="button" disabled
                  class="btn btn-sm btn-default" style="width:32px;height:32px;padding:0;">+</button>
              </div>
              <small id="ns-range-hint" class="text-muted">—</small>
            </div>

            <!-- Np display -->
            <div class="col-6 col-sm-3 mb-3">
              <p class="text-muted small mb-1">Strings totales <strong>(Np)</strong></p>
              <p id="np-value" class="font-weight-bold h5 mb-0">—</p>
              <small id="np-mppt-hint" class="text-muted">Selecciona inversor primero</small>
            </div>

            <!-- N_inv stepper -->
            <div class="col-6 col-sm-3 mb-3">
              <p class="text-muted small mb-1">Inversores <strong>(N<sub>inv</sub>)</strong></p>
              <div class="d-flex align-items-center">
                <button id="btn-ninv-dec" type="button" disabled
                  class="btn btn-sm btn-default" style="width:32px;height:32px;padding:0;">−</button>
                <span id="ninv-value" class="font-weight-bold h5 mb-0 mx-2">1</span>
                <button id="btn-ninv-inc" type="button"
                  class="btn btn-sm btn-default" style="width:32px;height:32px;padding:0;">+</button>
                <button id="btn-ninv-auto" type="button"
                  class="btn btn-xs btn-warning ml-2"
                  title="Calcular mínimo de inversores necesarios">Auto</button>
              </div>
              <small id="ninv-hint" class="text-muted">Selecciona inversor primero</small>
            </div>

            <!-- String voltages + area -->
            <div class="col-6 col-sm-3 mb-3">
              <div class="row text-center no-gutters">
                <div class="col-4">
                  <small class="text-muted d-block" style="font-size:.65rem;">Voc frío</small>
                  <p id="str-voc-cold" class="font-weight-bold text-danger mb-0" style="font-size:.8rem;">—</p>
                </div>
                <div class="col-4">
                  <small class="text-muted d-block" style="font-size:.65rem;">Vmpp calor</small>
                  <p id="str-vmpp-hot" class="font-weight-bold text-warning mb-0" style="font-size:.8rem;">—</p>
                </div>
                <div class="col-4">
                  <small class="text-muted d-block" style="font-size:.65rem;">Vmpp frío</small>
                  <p id="str-vmpp-cold" class="font-weight-bold text-primary mb-0" style="font-size:.8rem;">—</p>
                </div>
              </div>
              <p id="str-area-total" class="text-center text-muted mt-2 mb-0 small">—</p>
            </div>

          </div><!-- /row string config -->
        </div><!-- /string-config-section -->

        <!-- ── 3. Inverter dropdown (shown after module selected) ── -->
        <div id="inverter-section" class="d-none">
          <p class="text-muted text-uppercase font-weight-bold small mb-2">
            Inversor
          </p>
          <select id="inverter-select" class="form-control form-control-sm">
            <option value="">— Selecciona un inversor —</option>
          </select>
          <div id="inverter-compat-status" class="d-none mt-1 small font-weight-bold"></div>

          <!-- Inverter preview card (shown after selection) -->
          <div id="inverter-card-preview" class="d-none mt-2 card card-outline card-primary">
            <div class="card-body p-3">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                  <p id="inv-preview-manufacturer" class="text-muted mb-0 small">—</p>
                  <p id="inv-preview-model" class="font-weight-bold mb-0">—</p>
                </div>
                <span id="inv-preview-phase-badge" class="badge badge-info small">—</span>
              </div>
              <p id="inv-preview-power" class="h5 font-weight-bold text-primary mb-2">—</p>
              <div class="row no-gutters" style="font-size:.75rem;">
                <span class="col-5 text-muted">Vdc máx</span>    <span id="inv-preview-vdc"      class="col-7 font-weight-bold">—</span>
                <span class="col-5 text-muted">MPPT</span>        <span id="inv-preview-mppt"     class="col-7 font-weight-bold">—</span>
                <span class="col-5 text-muted">V arranque</span>  <span id="inv-preview-vstartup" class="col-7 font-weight-bold">—</span>
                <span class="col-5 text-muted">I MPPT máx</span>  <span id="inv-preview-impp"     class="col-7 font-weight-bold">—</span>
                <span class="col-5 text-muted">Isc máx</span>     <span id="inv-preview-isc"      class="col-7 font-weight-bold">—</span>
                <span class="col-5 text-muted">Cap. strings</span><span id="inv-preview-cap"      class="col-7 font-weight-bold">—</span>
                <span class="col-5 text-muted">η ponderada</span> <span id="inv-preview-eta"      class="col-7 font-weight-bold text-success">—</span>
              </div>
            </div>
          </div>
        </div><!-- /inverter-section -->

      </div><!-- /left panel -->

      <!-- ════════════════════════════════════════════════════
           RIGHT PANEL – preliminary results & electrical checks
      ════════════════════════════════════════════════════ -->
      <div class="col-lg-6 mt-4 mt-lg-0">

        <!-- Preliminary sizing (shown after module selected) -->
        <div id="prelim-results" class="d-none mb-4">
          <p class="text-muted text-uppercase font-weight-bold small mb-2">
            Dimensionamiento Preliminar
          </p>
          <div class="row">
            <div class="col-6 mb-2">
              <div class="card bg-light border-0 h-100">
                <div class="card-body p-3">
                  <p class="text-muted small mb-1">Módulos del arreglo</p>
                  <div class="d-flex align-items-center flex-wrap" style="gap:.35rem;">
                    <button type="button" id="btn-n-dec-10" class="btn btn-xs btn-default border px-2" style="line-height:1.4;" disabled>−10</button>
                    <button type="button" id="btn-n-dec" class="btn btn-xs btn-default border px-2" style="line-height:1.4;" disabled>−</button>
                    <p id="res-n-modulos" class="font-weight-bold h4 mb-0">—</p>
                    <button type="button" id="btn-n-inc" class="btn btn-xs btn-default border px-2" style="line-height:1.4;">+</button>
                    <button type="button" id="btn-n-inc-10" class="btn btn-xs btn-default border px-2" style="line-height:1.4;">+10</button>
                  </div>
                  <small>
                    <span id="res-n-base-hint" class="text-muted">unidades</span>
                    <span id="res-n-sugerido-hint" class="d-none">
                      sugerido: <strong id="res-n-sugerido">—</strong>
                      <a href="#" id="btn-n-reset" class="ml-1">restablecer</a>
                    </span>
                  </small>
                </div>
              </div>
            </div>
            <div class="col-6 mb-2">
              <div class="card bg-light border-0 h-100">
                <div class="card-body p-3">
                  <p class="text-muted small mb-1">Potencia pico (STC)</p>
                  <p id="res-p-arreglo-stc" class="font-weight-bold h4 mb-0">—</p>
                  <small class="text-muted">kWp</small>
                </div>
              </div>
            </div>
            <div class="col-12 mb-2">
              <div class="card bg-light border-0">
                <div class="card-body p-3">
                  <p class="text-muted small mb-1">Potencia en calor (T<sub>máx</sub>)</p>
                  <span id="res-p-arreglo-calor" class="font-weight-bold text-warning">—</span>
                  <span id="res-p-calor-pct" class="text-muted small ml-2">—</span>
                </div>
              </div>
            </div>
          </div>
        </div><!-- /prelim-results -->

        <!-- Electrical checks (shown after inverter selected) -->
        <div id="elect-checks-panel" class="d-none">

          <!-- Verdict banner -->
          <div id="verdict-banner" class="alert mb-3"><!-- populated by JS --></div>

          <p class="text-muted text-uppercase font-weight-bold small mb-2">
            Verificación Eléctrica
          </p>
          <div class="row">

            <!-- Hard: Strings vs MPPT capacity -->
            <div class="col-sm-6 mb-3">
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

            <!-- Hard: Voc cold vs Vdc max -->
            <div class="col-sm-6 mb-3">
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
            <div class="col-sm-6 mb-3">
              <div id="chk-vmpp-hot" class="card card-outline card-default h-100">
                <div class="card-body p-3">
                  <div class="d-flex align-items-start justify-content-between mb-1">
                    <small class="text-muted">Vmpp calor (MPPT mín)</small>
                    <span data-badge class="badge badge-secondary">—</span>
                  </div>
                  <p data-actual class="h5 font-weight-bold mb-1">—</p>
                  <small data-limit class="text-muted">límite —</small>
                </div>
              </div>
            </div>

            <!-- Soft: Vmpp hot vs startup -->
            <div class="col-sm-6 mb-3">
              <div id="chk-startup-v" class="card card-outline card-default h-100">
                <div class="card-body p-3">
                  <div class="d-flex align-items-start justify-content-between mb-1">
                    <small class="text-muted">Vmpp calor (V arranque)</small>
                    <span data-badge class="badge badge-secondary">—</span>
                  </div>
                  <p data-actual class="h5 font-weight-bold mb-1">—</p>
                  <small data-limit class="text-muted">límite —</small>
                </div>
              </div>
            </div>

            <!-- Soft: Vmpp cold vs MPPT max -->
            <div class="col-sm-6 mb-3">
              <div id="chk-vmpp-cold" class="card card-outline card-default h-100">
                <div class="card-body p-3">
                  <div class="d-flex align-items-start justify-content-between mb-1">
                    <small class="text-muted">Vmpp frío (MPPT máx)</small>
                    <span data-badge class="badge badge-secondary">—</span>
                  </div>
                  <p data-actual class="h5 font-weight-bold mb-1">—</p>
                  <small data-limit class="text-muted">límite —</small>
                </div>
              </div>
            </div>

            <!-- Hard: I MPPT per entry -->
            <div class="col-sm-6 mb-3">
              <div id="chk-i-mppt" class="card card-outline card-default h-100">
                <div class="card-body p-3">
                  <div class="d-flex align-items-start justify-content-between mb-1">
                    <small class="text-muted">Corriente por entrada MPPT</small>
                    <span data-badge class="badge badge-secondary">—</span>
                  </div>
                  <p data-actual class="h5 font-weight-bold mb-1">—</p>
                  <small data-limit class="text-muted">límite —</small>
                </div>
              </div>
            </div>

            <!-- Hard: Isc total DC -->
            <div class="col-sm-6 mb-3">
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
            <div class="col-sm-6 mb-3">
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
            <div class="col-sm-6 mb-3">
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
                </div>
              </div>
            </div>

          </div><!-- /row checks -->

        </div><!-- /elect-checks-panel -->

      </div><!-- /right panel -->

    </div><!-- /row -->
  </div><!-- /card-body -->

</div><!-- /bloque-diseno -->

<!-- ================================================================
     PRODUCCIÓN ENERGÉTICA – Estimado anual y desglose mensual
================================================================ -->
<div id="bloque-energia" class="card card-primary card-outline mb-3 d-none">

  <div class="card-header d-flex align-items-center justify-content-between flex-wrap" style="gap:.5rem;">
    <div>
      <h5 class="mb-0">Producción Energética</h5>
      <small class="text-muted">Estimado con factor de rendimiento (PR)</small>
    </div>
    <div class="d-flex align-items-center" style="gap:.5rem;">
      <label for="en-pr-input" class="mb-0 small font-weight-bold text-muted">Factor PR</label>
      <input type="number" id="en-pr-input" min="0.50" max="1.00" step="0.01" value="1"
             class="form-control form-control-sm" style="width:78px;"
             title="Performance Ratio: eficiencia global del sistema (Valor típico: 0.75–0.85)">
      <small class="text-muted">/ 1.00</small>
    </div>
  </div>

  <div class="card-body">

    <!-- Summary metrics -->
    <div class="row mb-3">

      <div class="col-6 col-sm-3 mb-2">
        <div class="card bg-light border-0 h-100">
          <div class="card-body p-3">
            <p class="text-muted small mb-1">Potencia instalada</p>
            <p id="en-p-stc" class="font-weight-bold h5 mb-0">—</p>
            <small class="text-muted">kWp</small>
          </div>
        </div>
      </div>

      <div class="col-6 col-sm-3 mb-2">
        <div class="card bg-light border-0 h-100">
          <div class="card-body p-3">
            <p class="text-muted small mb-1">Producción anual estimada</p>
            <p id="en-produccion-anual" class="font-weight-bold h5 mb-0">—</p>
            <small class="text-muted">kWh/año</small>
          </div>
        </div>
      </div>

      <div class="col-6 col-sm-3 mb-2">
        <div class="card bg-light border-0 h-100">
          <div class="card-body p-3">
            <p class="text-muted small mb-1">Cobertura estimada</p>
            <p id="en-cobertura" class="font-weight-bold h5 mb-0">—</p>
            <small class="text-muted">% del consumo anual</small>
          </div>
        </div>
      </div>

      <div class="col-6 col-sm-3 mb-2">
        <div class="card bg-light border-0 h-100">
          <div class="card-body p-3">
            <p class="text-muted small mb-1">Relación DC/AC</p>
            <p id="en-dcac" class="font-weight-bold h5 mb-0">—</p>
            <small id="en-dcac-hint" class="text-muted font-weight-bold">—</small>
          </div>
        </div>
      </div>

    </div><!-- /summary row -->

    <!-- Monthly production table (only when NASA data is available) -->
    <div id="en-monthly-section" class="d-none">

      <div class="d-flex align-items-center justify-content-between mb-2">
        <p class="text-muted text-uppercase font-weight-bold small mb-0">Desglose Mensual</p>
        <div class="d-flex align-items-center">
          <span class="text-muted small mr-2">Consumo mensual:</span>
          <div class="btn-group btn-group-sm" role="group">
            <button type="button" data-cons="off" class="en-cons-btn btn btn-primary">Solo producción</button>
            <button type="button" data-cons="on"  class="en-cons-btn btn btn-default">Ingresar consumo</button>
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
              <th class="en-cons-col d-none text-right">Consumo real<br/><span class="font-weight-normal">(kWh)</span></th>
              <th class="en-cons-col d-none text-right">Balance<br/><span class="font-weight-normal">(kWh)</span></th>
              <th class="en-cons-col d-none text-right">Bolsa Energ&eacute;tica<br/><span class="font-weight-normal">(kWh)</span></th>
            </tr>
          </thead>
          <tbody id="en-monthly-tbody"></tbody>
          <tfoot id="en-monthly-tfoot" class="font-weight-bold"></tfoot>
        </table>
      </div>

    </div><!-- /en-monthly-section -->

    <!-- Shown when NASA data is absent -->
    <div id="en-no-monthly" class="alert alert-info small mb-0 d-none">
      <i class="fas fa-info-circle mr-1"></i>
      Los datos de GHI mensual no están disponibles. Consulta la NASA POWER API en el Paso 1 para ver el desglose mensual.
    </div>

  </div><!-- /card-body -->

</div><!-- /bloque-energia -->

<!-- ================================================================
     PROTECCIONES ELÉCTRICAS – DC y AC (NOM-001-SEDE-2012 / NEC 690)
================================================================ -->
<div id="bloque-protecciones" class="card card-primary card-outline mb-3 d-none">

  <div class="card-header d-flex align-items-center justify-content-between">
    <div>
      <h5 class="mb-0">Protecciones Eléctricas</h5>
      <small class="text-muted">NOM-001-SEDE-2012, Art. 690.8 — Lado DC y AC</small>
    </div>
    <div class="d-flex align-items-center">
      <span class="text-muted small mr-2">Corrección por temperatura:</span>
      <div class="btn-group btn-group-sm" role="group">
        <button type="button" data-derating="off" id="prot-btn-derating-off"
          class="prot-derating-btn btn btn-primary">Sin corrección</button>
        <button type="button" data-derating="on"  id="prot-btn-derating-on"
          class="prot-derating-btn btn btn-default">Con corrección</button>
      </div>
    </div>
  </div>

  <div class="card-body">

    <p id="prot-derating-hint" class="text-muted small mb-3 d-none"><!-- populated by JS --></p>

    <!-- DC sub-header -->
    <p class="text-muted text-uppercase font-weight-bold small mb-2">Lado DC</p>

    <!-- DC scenario cards (one per unique strings-per-MPPT count) -->
    <div id="prot-dc-scenarios" class="row mb-1"><!-- populated by JS --></div>

    <small class="text-muted d-block mb-4" style="font-size:.72rem;">
      <strong>Fusible de cadena (gPV)</strong> requerido cuando hay &ge; 2 strings por entrada MPPT
      (protege contra corriente inversa). Calibre gPV: I<sub>sc</sub> &times; 1.56 redondeado al estándar superior.
      <strong>Conductores Cu 75&nbsp;°C</strong>: I<sub>sc</sub> &times; 1.56 (cadena) &oacute; N<sub>str</sub> &times; I<sub>sc</sub> &times; 1.56 (entrada MPPT),
      dividido por el factor de temperatura si aplica. OCPD: mismo factor 1.56. Regla Art.&nbsp;240-4(d).
    </small>

    <!-- AC sub-header -->
    <p class="text-muted text-uppercase font-weight-bold small mb-2">Lado AC — Inversor &rarr; Tablero</p>

    <div class="row">
      <div class="col-sm-6 col-lg-5 mb-3">
        <div class="card card-outline card-default">
          <div class="card-body p-0">
            <table class="table table-sm mb-0">
              <tbody>
                <tr>
                  <td class="text-muted small">Tipo de fase (<span id="prot-ac-phase">—</span>)</td>
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
                <tr id="prot-ac-small-cond-row" class="d-none">
                  <td colspan="2" class="small text-warning py-1">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    Calibre aumentado por regla de conductor peque&ntilde;o (Art.&nbsp;240-4(d))
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  </div><!-- /card-body -->

</div><!-- /bloque-protecciones -->

<!-- ─── Export ─────────────────────────────────────────────────────────── -->
<div id="bloque-export" class="card card-primary card-outline mb-3 d-none">
  <div class="card-body d-flex justify-content-end" style="gap:.75rem;">
    <button type="button" id="btn-excel-export" class="btn btn-primary">
      <i class="fas fa-download mr-2"></i>Exportar Excel (.xlsx)
    </button>
  </div>
</div>
