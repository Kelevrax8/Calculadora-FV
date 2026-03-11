// ── Table state ───────────────────────────────────────────────────────
const state = {
  manufacturadores: { page: 1, total: 0, loaded: false },
  modulos:          { page: 1, total: 0, loaded: false },
  inversores:       { page: 1, total: 0, loaded: false },
};

function switchTab(name) {
  document.querySelectorAll('.tab-panel').forEach(p => p.classList.add('d-none'));
  document.querySelectorAll('#inv-tabs .nav-link').forEach(a => a.classList.remove('active'));
  document.getElementById('panel-' + name).classList.remove('d-none');
  document.getElementById('tab-' + name).classList.add('active');

  // Load data the first time a tab is opened
  if (!state[name].loaded) {
    loadTable(name, 1);
  }
}

// ── Load & render table ───────────────────────────────────────────────
async function loadTable(tab, page = 1) {
  state[tab].page = page;
  const q   = document.getElementById('search-' + tab)?.value ?? '';
  const map = { manufacturadores:'list_manufacturers', modulos:'list_modules', inversores:'list_inverters' };
  const res = await fetch(`/api/inventario.php?action=${map[tab]}&page=${page}&q=${encodeURIComponent(q)}`);
  const json = await res.json();
  state[tab].total = json.total;
  state[tab].loaded = true;
  renderRows(tab, json.data);
  renderPagination(tab, json.total, page);
}

// ── Translation maps ────────────────────────────────────────────────
const TECHNOLOGY_ES = {
  'Monocrystalline': 'Monocristalino',
  'Polycrystalline':  'Policristalino',
  'Thin Film':        'Película delgada',
  'Other':            'Otro',
};
const PHASE_ES = {
  'Single Phase': 'Monofásico',
  'Split Phase':  'Bifásico',
  'Three Phase':  'Trifásico',
};

function renderRows(tab, data) {
  const tbody = document.getElementById('tbody-' + tab);
  if (!data.length) {
    tbody.innerHTML = `<tr><td colspan="20" class="text-center text-muted py-4">Sin registros</td></tr>`;
    return;
  }
  tbody.innerHTML = data.map(r => {
    const actions = `
      <div class="btn-group btn-group-sm">
        <button onclick="openModal('${tab}', ${JSON.stringify(r).replace(/"/g,'&quot;')})"
          class="btn btn-default" title="Editar">
          <i class="fas fa-edit text-primary"></i>
        </button>
        <button onclick="deleteEntity('${tab}', ${r.id})"
          class="btn btn-default" title="Eliminar">
          <i class="fas fa-trash text-danger"></i>
        </button>
      </div>`;

    if (tab === 'manufacturadores') return `
      <tr>
        <td class="font-weight-bold">${esc(r.name)}</td>
        <td class="text-center">${actions}</td>
      </tr>`;

    if (tab === 'modulos') return `
      <tr>
        <td class="font-weight-bold">${esc(r.manufacturer)}</td>
        <td>${esc(r.model)}</td>
        <td>${esc(TECHNOLOGY_ES[r.technology] ?? r.technology)}</td>
        <td class="text-right">${r.pmax_stc}</td>
        <td class="text-right">${r.voc_stc}</td>
        <td class="text-right">${r.isc_stc}</td>
        <td class="text-right">${r.vmpp_stc}</td>
        <td class="text-right">${r.imp_stc}</td>
        <td class="text-right">${r.temp_coeff_voc}</td>
        <td class="text-right">${r.temp_coeff_pmax}</td>
        <td class="text-right">${r.length_m}</td>
        <td class="text-right">${r.width_m}</td>
        <td class="text-center">${actions}</td>
      </tr>`;

    if (tab === 'inversores') return `
      <tr>
        <td class="font-weight-bold">${esc(r.manufacturer)}</td>
        <td>${esc(r.model)}</td>
        <td class="text-right">${r.pmax_dc_input}</td>
        <td class="text-right">${r.max_dc_voltage}</td>
        <td class="text-right">${r.mppt_voltage_min} – ${r.mppt_voltage_max}</td>
        <td class="text-right">${r.startup_voltage}</td>
        <td class="text-right">${r.max_input_current_per_mppt}</td>
        <td class="text-right">${r.max_short_circuit_current}</td>
        <td class="text-right">${r.nominal_ac_power}</td>
        <td class="text-right">${r.ac_voltage_nominal}</td>
        <td>${esc(PHASE_ES[r.phase_type] ?? r.phase_type)}</td>
        <td class="text-right">${r.efficiency_weighted}</td>
        <td class="text-right">${r.mppt_count}</td>
        <td class="text-center">${actions}</td>
      </tr>`;
  }).join('');
}

function renderPagination(tab, total, page) {
  const pages = Math.ceil(total / 5) || 1;
  const el    = document.getElementById('pagination-' + tab);
  const from  = total === 0 ? 0 : (page - 1) * 5 + 1;
  const to    = Math.min(page * 5, total);
  el.innerHTML = `
    <ul class="pagination pagination-sm m-0 float-right">
      <li class="page-item${page <= 1 ? ' disabled' : ''}">
        <a class="page-link" href="#" onclick="loadTable('${tab}', ${page - 1}); return false;">&laquo;</a>
      </li>
      <li class="page-item disabled">
        <span class="page-link">${from}–${to} de ${total}</span>
      </li>
      <li class="page-item${page >= pages ? ' disabled' : ''}">
        <a class="page-link" href="#" onclick="loadTable('${tab}', ${page + 1}); return false;">&raquo;</a>
      </li>
    </ul>`;
}

// ── Modal ─────────────────────────────────────────────────────────────
let currentTab = null;

function showModalError(msg) {
  const el = document.getElementById('modal-error');
  el.textContent = msg;
  el.classList.remove('d-none');
}

function clearModalError() {
  const el = document.getElementById('modal-error');
  el.classList.add('d-none');
  el.textContent = '';
}

async function openModal(tab, row = null) {
  currentTab = tab;
  clearModalError();
  document.getElementById('modal-title').textContent = row ? 'Editar registro' : 'Nuevo registro';
  document.querySelectorAll('.entity-form').forEach(f => f.classList.add('d-none'));
  document.getElementById('form-' + tab).classList.remove('d-none');
  resetNegativeWarnings();
  if (tab === 'modulos' || tab === 'inversores') {
    await populateManufacturers(tab === 'modulos' ? 'mod-manufacturer' : 'inv-manufacturer', row?.manufacturer_id);
  }

  // Populate form fields
  if (tab === 'manufacturadores') {
    document.getElementById('man-id').value   = row?.id   ?? '';
    document.getElementById('man-name').value = row?.name ?? '';
  }
  if (tab === 'modulos') {
    document.getElementById('mod-id').value            = row?.id             ?? '';
    document.getElementById('mod-model').value         = row?.model          ?? '';
    document.getElementById('mod-technology').value    = row?.technology     ?? '';
    document.getElementById('mod-pmax_stc').value      = row?.pmax_stc       ?? '';
    document.getElementById('mod-voc_stc').value       = row?.voc_stc        ?? '';
    document.getElementById('mod-isc_stc').value       = row?.isc_stc        ?? '';
    document.getElementById('mod-vmpp_stc').value      = row?.vmpp_stc       ?? '';
    document.getElementById('mod-imp_stc').value       = row?.imp_stc        ?? '';
    document.getElementById('mod-temp_coeff_voc').value  = row?.temp_coeff_voc  ?? '';
    document.getElementById('mod-temp_coeff_pmax').value = row?.temp_coeff_pmax ?? '';
    document.getElementById('mod-length_m').value      = row?.length_m       ?? '';
    document.getElementById('mod-width_m').value       = row?.width_m        ?? '';
  }
  if (tab === 'inversores') {
    document.getElementById('inv-id').value                          = row?.id                           ?? '';
    document.getElementById('inv-model').value                       = row?.model                        ?? '';
    document.getElementById('inv-pmax_dc_input').value               = row?.pmax_dc_input                ?? '';
    document.getElementById('inv-max_dc_voltage').value              = row?.max_dc_voltage               ?? '';
    document.getElementById('inv-mppt_voltage_min').value            = row?.mppt_voltage_min             ?? '';
    document.getElementById('inv-mppt_voltage_max').value            = row?.mppt_voltage_max             ?? '';
    document.getElementById('inv-startup_voltage').value             = row?.startup_voltage              ?? '';
    document.getElementById('inv-max_input_current_per_mppt').value  = row?.max_input_current_per_mppt  ?? '';
    document.getElementById('inv-max_short_circuit_current').value   = row?.max_short_circuit_current   ?? '';
    document.getElementById('inv-nominal_ac_power').value            = row?.nominal_ac_power             ?? '';
    document.getElementById('inv-ac_voltage_nominal').value          = row?.ac_voltage_nominal           ?? '';
    document.getElementById('inv-phase_type').value                  = row?.phase_type                   ?? '';
    document.getElementById('inv-efficiency_weighted').value         = row?.efficiency_weighted          ?? '';
    document.getElementById('inv-mppt_count').value                  = row?.mppt_count                   ?? '';
  }

  $('#modal').modal('show');
}

async function populateManufacturers(selectId, selectedId = null) {
  const res  = await fetch('/api/inventario.php?action=manufacturers_select');
  const list = await res.json();
  const sel  = document.getElementById(selectId);
  sel.innerHTML = '<option value="">— Seleccionar —</option>' +
    list.map(m => `<option value="${m.id}" ${m.id == selectedId ? 'selected' : ''}>${esc(m.name)}</option>`).join('');
}

function closeModal() {
  clearModalError();
  $('#modal').modal('hide');
  currentTab = null;
}

async function saveEntity() {
  const tab  = currentTab;
  const form = document.getElementById('form-' + tab);
  if (!form.reportValidity()) return;
  let payload = {};

  if (tab === 'manufacturadores') {
    payload = { id: document.getElementById('man-id').value, name: document.getElementById('man-name').value };
  }
  if (tab === 'modulos') {
      payload = {
      id: document.getElementById('mod-id').value,
      manufacturer_id: document.getElementById('mod-manufacturer').value,
      model:            document.getElementById('mod-model').value,
      technology:       document.getElementById('mod-technology').value,
      pmax_stc:         document.getElementById('mod-pmax_stc').value,
      voc_stc:          document.getElementById('mod-voc_stc').value,
      isc_stc:          document.getElementById('mod-isc_stc').value,
      vmpp_stc:         document.getElementById('mod-vmpp_stc').value,
      imp_stc:          document.getElementById('mod-imp_stc').value,
      temp_coeff_voc:   document.getElementById('mod-temp_coeff_voc').value,
      temp_coeff_pmax:  document.getElementById('mod-temp_coeff_pmax').value,
      length_m:         document.getElementById('mod-length_m').value,
      width_m:          document.getElementById('mod-width_m').value,
    };
  }
  if (tab === 'inversores') {
    payload = {
      id:                          document.getElementById('inv-id').value,
      manufacturer_id:             document.getElementById('inv-manufacturer').value,
      model:                       document.getElementById('inv-model').value,
      pmax_dc_input:               document.getElementById('inv-pmax_dc_input').value,
      max_dc_voltage:              document.getElementById('inv-max_dc_voltage').value,
      mppt_voltage_min:            document.getElementById('inv-mppt_voltage_min').value,
      mppt_voltage_max:            document.getElementById('inv-mppt_voltage_max').value,
      startup_voltage:             document.getElementById('inv-startup_voltage').value,
      max_input_current_per_mppt:  document.getElementById('inv-max_input_current_per_mppt').value,
      max_short_circuit_current:   document.getElementById('inv-max_short_circuit_current').value,
      nominal_ac_power:            document.getElementById('inv-nominal_ac_power').value,
      ac_voltage_nominal:          document.getElementById('inv-ac_voltage_nominal').value,
      phase_type:                  document.getElementById('inv-phase_type').value,
      efficiency_weighted:         document.getElementById('inv-efficiency_weighted').value,
      mppt_count:                  document.getElementById('inv-mppt_count').value,
    };
  }

  const actionMap = { manufacturadores: 'save_manufacturer', modulos: 'save_module', inversores: 'save_inverter' };
  let json;
  try {
    const res  = await fetch(`/api/inventario.php?action=${actionMap[tab]}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    });
    const text = await res.text();
    console.debug('[saveEntity] raw response:', text);
    try {
      json = JSON.parse(text);
    } catch (_) {
      const match = text.match(/\{"[\s\S]*?(?:"ok"|"error")[\s\S]*?\}/);
      if (!match) throw new Error('No JSON found: ' + text);
      json = JSON.parse(match[0]);
    }
  } catch (e) {
    console.error('[saveEntity] parse error:', e);
    showModalError('Error de comunicación con el servidor.');
    return;
  }
  if (json.error) {
    showModalError(json.error);
    return;
  }
  const isEdit = !!payload.id;
  closeModal();
  loadTable(tab, state[tab].page);
  showToast(isEdit ? 'Registro actualizado correctamente.' : 'Registro creado correctamente.');
}

// ── Delete ────────────────────────────────────────────────────────────
async function deleteEntity(tab, id) {
  if (!confirm('¿Eliminar este registro? Esta acción no se puede deshacer.')) return;
  const actionMap = { manufacturadores: 'delete_manufacturer', modulos: 'delete_module', inversores: 'delete_inverter' };
  const res  = await fetch(`/api/inventario.php?action=${actionMap[tab]}`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id }),
  });
  const json = await res.json();
  if (json.error) { showToast('Error: ' + json.error, 'error'); return; }
  const newPage = state[tab].page;
  loadTable(tab, newPage);
  showToast('Registro eliminado correctamente.', 'info');
}

// ── Toast notifications (toastr) ────────────────────────────────────
function showToast(message, type = 'success') {
  const map = { success: 'success', error: 'error', info: 'info' };
  toastr[map[type] ?? 'info'](message);
}

// ── Negative coefficient real-time validation ──────────────────────
function validateNegative(input, warningId) {
  const val     = parseFloat(input.value);
  const warn    = document.getElementById(warningId);
  const invalid = input.value !== '' && !isNaN(val) && val >= 0;
  if (invalid) {
    input.classList.add('is-invalid');
    warn.classList.remove('d-none');
  } else {
    input.classList.remove('is-invalid');
    warn.classList.add('d-none');
  }
}

function resetNegativeWarnings() {
  ['mod-temp_coeff_voc', 'mod-temp_coeff_pmax'].forEach((id, i) => {
    const input = document.getElementById(id);
    const warn  = document.getElementById(['warn-tcv', 'warn-tcp'][i]);
    input.classList.remove('is-invalid');
    warn.classList.add('d-none');
  });
}

// ── XSS-safe string helper ────────────────────────────────────────────
function esc(s) {
  if (s == null) return '';
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Bootstrap Modal cleanup on close ─────────────────────────────────
$('#modal').on('hide.bs.modal', function () {
  clearModalError();
  currentTab = null;
});

// ── Init ─────────────────────────────────────────────────────────────
loadTable('manufacturadores');
