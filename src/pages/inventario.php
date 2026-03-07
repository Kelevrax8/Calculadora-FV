<?php
// ─────────────────────────────────────────────────────────────────────────────
//  Page render — AJAX is handled by /api/inventario.php
// ─────────────────────────────────────────────────────────────────────────────
define('APP', true);
$pageTitle = 'Inventario - IPTE';
include '../components/header-dashboard.php';
?>

<!-- Content Header -->
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0">Inventario</h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="/pages/dashboard.php">Inicio</a></li>
          <li class="breadcrumb-item active">Inventario</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="container-fluid">

    <!-- Nav tabs -->
    <ul class="nav nav-tabs" id="inv-tabs">
      <li class="nav-item">
        <a id="tab-manufacturadores" class="nav-link active" href="#"
           onclick="switchTab('manufacturadores'); return false;">
          <i class="fas fa-industry mr-1"></i>Manufacturadores
        </a>
      </li>
      <li class="nav-item">
        <a id="tab-modulos" class="nav-link" href="#"
           onclick="switchTab('modulos'); return false;">
          <i class="fas fa-solar-panel mr-1"></i>Módulos FV
        </a>
      </li>
      <li class="nav-item">
        <a id="tab-inversores" class="nav-link" href="#"
           onclick="switchTab('inversores'); return false;">
          <i class="fas fa-bolt mr-1"></i>Inversores
        </a>
      </li>
    </ul>

    <div class="pt-3">

      <!-- ── MANUFACTURERS PANEL ── -->
      <div id="panel-manufacturadores" class="tab-panel">
        <div class="row mb-3 align-items-center">
          <div class="col-sm-6">
            <input id="search-manufacturadores" type="text" class="form-control"
              placeholder="Buscar por nombre…" oninput="loadTable('manufacturadores',1)">
          </div>
          <div class="col-sm-6 text-right mt-2 mt-sm-0">
            <button onclick="openModal('manufacturadores')" class="btn btn-primary">
              <i class="fas fa-plus mr-1"></i>Agregar
            </button>
          </div>
        </div>
        <div class="card">
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-bordered table-hover table-sm mb-0">
                <thead class="thead-light">
                  <tr>
                    <th>Nombre</th>
                    <th class="text-center" style="width:100px;">Acciones</th>
                  </tr>
                </thead>
                <tbody id="tbody-manufacturadores"></tbody>
              </table>
            </div>
          </div>
          <div class="card-footer clearfix py-2">
            <div id="pagination-manufacturadores"></div>
          </div>
        </div>
      </div>

      <!-- ── PV MODULES PANEL ── -->
      <div id="panel-modulos" class="tab-panel d-none">
        <div class="row mb-3 align-items-center">
          <div class="col-sm-6">
            <input id="search-modulos" type="text" class="form-control"
              placeholder="Buscar por fabricante o modelo…" oninput="loadTable('modulos',1)">
          </div>
          <div class="col-sm-6 text-right mt-2 mt-sm-0">
            <button onclick="openModal('modulos')" class="btn btn-primary">
              <i class="fas fa-plus mr-1"></i>Agregar
            </button>
          </div>
        </div>
        <div class="card">
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-bordered table-hover table-sm mb-0 text-nowrap">
                <thead class="thead-light">
                  <tr>
                    <th>Fabricante</th>
                    <th>Modelo</th>
                    <th>Tecnología</th>
                    <th class="text-right">Pmax (W)</th>
                    <th class="text-right">Voc (V)</th>
                    <th class="text-right">Isc (A)</th>
                    <th class="text-right">Vmpp (V)</th>
                    <th class="text-right">Imp (A)</th>
                    <th class="text-right">β Voc (%/°C)</th>
                    <th class="text-right">β Pmax (%/°C)</th>
                    <th class="text-right">Largo (m)</th>
                    <th class="text-right">Ancho (m)</th>
                    <th class="text-center" style="width:100px;">Acciones</th>
                  </tr>
                </thead>
                <tbody id="tbody-modulos"></tbody>
              </table>
            </div>
          </div>
          <div class="card-footer clearfix py-2">
            <div id="pagination-modulos"></div>
          </div>
        </div>
      </div>

      <!-- ── INVERTERS PANEL ── -->
      <div id="panel-inversores" class="tab-panel d-none">
        <div class="row mb-3 align-items-center">
          <div class="col-sm-6">
            <input id="search-inversores" type="text" class="form-control"
              placeholder="Buscar por fabricante o modelo…" oninput="loadTable('inversores',1)">
          </div>
          <div class="col-sm-6 text-right mt-2 mt-sm-0">
            <button onclick="openModal('inversores')" class="btn btn-primary">
              <i class="fas fa-plus mr-1"></i>Agregar
            </button>
          </div>
        </div>
        <div class="card">
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-bordered table-hover table-sm mb-0 text-nowrap">
                <thead class="thead-light">
                  <tr>
                    <th>Fabricante</th>
                    <th>Modelo</th>
                    <th class="text-right">Pmax DC (W)</th>
                    <th class="text-right">V DC máx (V)</th>
                    <th class="text-right">Rango VMPP (V)</th>
                    <th class="text-right">V arranque (V)</th>
                    <th class="text-right">I/MPPT máx (A)</th>
                    <th class="text-right">Isc máx (A)</th>
                    <th class="text-right">P AC nom (W)</th>
                    <th class="text-right">V AC nom (V)</th>
                    <th>Fase</th>
                    <th class="text-right">EE pond. (%)</th>
                    <th class="text-right">MPPT</th>
                    <th class="text-center" style="width:100px;">Acciones</th>
                  </tr>
                </thead>
                <tbody id="tbody-inversores"></tbody>
              </table>
            </div>
          </div>
          <div class="card-footer clearfix py-2">
            <div id="pagination-inversores"></div>
          </div>
        </div>
      </div>

    </div><!-- /.pt-3 -->
  </div><!-- /.container-fluid -->
</section>

<!-- Modal -->
<div class="modal fade" id="modal" tabindex="-1" role="dialog"
     aria-labelledby="modal-title-label" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title" id="modal-title-label">
          <span id="modal-title"></span>
        </h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body" style="max-height:70vh; overflow-y:auto;">

        <!-- Inline error banner -->
        <div id="modal-error" class="alert alert-danger d-none" role="alert"></div>

        <!-- FORM: Manufacturadores -->
        <form id="form-manufacturadores" class="entity-form d-none" onsubmit="return false">
          <input type="hidden" id="man-id">
          <div class="form-group">
            <label>Nombre <span class="text-danger">*</span></label>
            <input type="text" id="man-name" required class="form-control">
          </div>
        </form>

        <!-- FORM: Módulos FV -->
        <form id="form-modulos" class="entity-form d-none" onsubmit="return false">
          <input type="hidden" id="mod-id">
          <div class="row">
            <div class="col-sm-6">
              <div class="form-group">
                <label>Fabricante <span class="text-danger">*</span></label>
                <select id="mod-manufacturer" required class="form-control">
                  <option value="">— Seleccionar —</option>
                </select>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                <label>Modelo <span class="text-danger">*</span></label>
                <input type="text" id="mod-model" required class="form-control">
              </div>
            </div>
            <div class="col-sm-12">
              <div class="form-group">
                <label>Tecnología <span class="text-danger">*</span></label>
                <select id="mod-technology" required class="form-control">
                  <option value="">— Seleccionar —</option>
                  <option value="Monocrystalline">Monocristalino</option>
                  <option value="Polycrystalline">Policristalino</option>
                  <option value="Thin Film">Película delgada</option>
                  <option value="Other">Otro</option>
                </select>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                <label>Pmax STC (W) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" id="mod-pmax_stc" required class="form-control">
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                <label>Voc STC (V) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" id="mod-voc_stc" required class="form-control">
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                <label>Isc STC (A) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" id="mod-isc_stc" required class="form-control">
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                <label>Vmpp STC (V) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" id="mod-vmpp_stc" required class="form-control">
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                <label>Imp STC (A) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" id="mod-imp_stc" required class="form-control">
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                <label>β Voc (%/°C) <span class="text-danger">*</span></label>
                <input type="number" step="0.0001" max="-0.0001" id="mod-temp_coeff_voc" required
                  oninput="validateNegative(this, 'warn-tcv')" class="form-control">
                <div id="warn-tcv" class="text-danger small d-none mt-1">
                  <i class="fas fa-exclamation-triangle mr-1"></i>El valor debe ser negativo (ej. -0.2800)
                </div>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                <label>β Pmax (%/°C) <span class="text-danger">*</span></label>
                <input type="number" step="0.0001" max="-0.0001" id="mod-temp_coeff_pmax" required
                  oninput="validateNegative(this, 'warn-tcp')" class="form-control">
                <div id="warn-tcp" class="text-danger small d-none mt-1">
                  <i class="fas fa-exclamation-triangle mr-1"></i>El valor debe ser negativo (ej. -0.3500)
                </div>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                <label>Largo (m) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" id="mod-length_m" required class="form-control">
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                <label>Ancho (m) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" id="mod-width_m" required class="form-control">
              </div>
            </div>
          </div>
        </form>

        <!-- FORM: Inversores -->
        <form id="form-inversores" class="entity-form d-none" onsubmit="return false">
          <input type="hidden" id="inv-id">
          <div class="row">
            <div class="col-sm-6">
              <div class="form-group">
                <label>Fabricante <span class="text-danger">*</span></label>
                <select id="inv-manufacturer" required class="form-control">
                  <option value="">— Seleccionar —</option>
                </select>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                <label>Modelo <span class="text-danger">*</span></label>
                <input type="text" id="inv-model" required class="form-control">
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                <label>Pmax entrada DC (W) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" id="inv-pmax_dc_input" required class="form-control">
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                <label>V DC máxima (V) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" id="inv-max_dc_voltage" required class="form-control">
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                <label>V MPPT mínima (V) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" id="inv-mppt_voltage_min" required class="form-control">
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                <label>V MPPT máxima (V) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" id="inv-mppt_voltage_max" required class="form-control">
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                <label>V de arranque (V) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" id="inv-startup_voltage" required class="form-control">
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                <label>I entrada máx por MPPT (A) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" id="inv-max_input_current_per_mppt" required class="form-control">
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                <label>Isc máxima (A) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" id="inv-max_short_circuit_current" required class="form-control">
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                <label>P AC nominal (W) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" id="inv-nominal_ac_power" required class="form-control">
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                <label>V AC nominal (V) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" id="inv-ac_voltage_nominal" required class="form-control">
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                <label>Tipo de fase <span class="text-danger">*</span></label>
                <select id="inv-phase_type" required class="form-control">
                  <option value="">— Seleccionar —</option>
                  <option value="Single Phase">Monofásico</option>
                  <option value="Split Phase">Bifásico</option>
                  <option value="Three Phase">Trifásico</option>
                </select>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                <label>Eficiencia ponderada (%) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" id="inv-efficiency_weighted" required class="form-control">
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                <label>Cantidad MPPT <span class="text-danger">*</span></label>
                <input type="number" step="1" min="1" id="inv-mppt_count" required class="form-control">
              </div>
            </div>
          </div>
        </form>

      </div><!-- /.modal-body -->

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
        <button type="button" id="modal-save-btn" onclick="saveEntity()" class="btn btn-primary">Guardar</button>
      </div>

    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<?php
$extraScripts = '<script src="/js/inventario.js"></script>';
include '../components/footer.php';
?>
