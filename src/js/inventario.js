// ── Tab switching ─────────────────────────────────────────────────────
const ACTIVE_BTN   = ['bg-white','border-gray-200','text-Ipteblue'];
const INACTIVE_BTN = ['bg-gray-50','border-transparent','text-gray-500',
                      'hover:text-Ipteblue','hover:bg-white','hover:border-gray-200'];

// ── Table state ───────────────────────────────────────────────────────
const state = {
  manufacturadores: { page: 1, total: 0, loaded: false },
  modulos:          { page: 1, total: 0, loaded: false },
  inversores:       { page: 1, total: 0, loaded: false },
};

function switchTab(name) {
  document.querySelectorAll('.tab-panel').forEach(p => p.classList.add('hidden'));
  document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.classList.remove(...ACTIVE_BTN);
    btn.classList.add(...INACTIVE_BTN);
  });
  document.getElementById('panel-' + name).classList.remove('hidden');
  const activeBtn = document.getElementById('tab-' + name);
  activeBtn.classList.remove(...INACTIVE_BTN);
  activeBtn.classList.add(...ACTIVE_BTN);

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
    tbody.innerHTML = `<tr><td colspan="20" class="px-4 py-8 text-center text-gray-400 text-sm">Sin registros</td></tr>`;
    return;
  }
  const tdC  = 'px-4 py-3';
  const tdR  = 'px-4 py-3 text-right tabular-nums';
  tbody.innerHTML = data.map(r => {
    const actions = `
      <div class="flex items-center justify-center gap-2">
        <button onclick="openModal('${tab}', ${JSON.stringify(r).replace(/"/g,'&quot;')})"
          class="p-1.5 rounded-md text-gray-400 hover:text-Ipteblue2 hover:bg-blue-50 transition" title="Editar">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 012.828 2.828L11.828 15.828a2 2 0 01-1.414.586H9v-2.414a2 2 0 01.586-1.414z"/></svg>
        </button>
        <button onclick="deleteEntity('${tab}', ${r.id})"
          class="p-1.5 rounded-md text-gray-400 hover:text-red-500 hover:bg-red-50 transition" title="Eliminar">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4h6v3M4 7h16"/></svg>
        </button>
      </div>`;

    if (tab === 'manufacturadores') return `
      <tr class="hover:bg-gray-50 transition">
        <td class="${tdC} font-medium">${esc(r.name)}</td>
        <td class="${tdC}">${actions}</td>
      </tr>`;

    if (tab === 'modulos') return `
      <tr class="hover:bg-gray-50 transition">
        <td class="${tdC} font-medium">${esc(r.manufacturer)}</td>
        <td class="${tdC}">${esc(r.model)}</td>
        <td class="${tdC}">${esc(TECHNOLOGY_ES[r.technology] ?? r.technology)}</td>
        <td class="${tdR}">${r.pmax_stc}</td>
        <td class="${tdR}">${r.voc_stc}</td>
        <td class="${tdR}">${r.isc_stc}</td>
        <td class="${tdR}">${r.vmpp_stc}</td>
        <td class="${tdR}">${r.imp_stc}</td>
        <td class="${tdR}">${r.temp_coeff_voc}</td>
        <td class="${tdR}">${r.temp_coeff_pmax}</td>
        <td class="${tdR}">${r.length_m}</td>
        <td class="${tdR}">${r.width_m}</td>
        <td class="${tdC}">${actions}</td>
      </tr>`;

    if (tab === 'inversores') return `
      <tr class="hover:bg-gray-50 transition">
        <td class="${tdC} font-medium">${esc(r.manufacturer)}</td>
        <td class="${tdC}">${esc(r.model)}</td>
        <td class="${tdR}">${r.pmax_dc_input}</td>
        <td class="${tdR}">${r.max_dc_voltage}</td>
        <td class="${tdR}">${r.mppt_voltage_min} – ${r.mppt_voltage_max}</td>
        <td class="${tdR}">${r.startup_voltage}</td>
        <td class="${tdR}">${r.max_input_current_per_mppt}</td>
        <td class="${tdR}">${r.max_short_circuit_current}</td>
        <td class="${tdR}">${r.nominal_ac_power}</td>
        <td class="${tdR}">${r.ac_voltage_nominal}</td>
        <td class="${tdC}">${esc(PHASE_ES[r.phase_type] ?? r.phase_type)}</td>
        <td class="${tdR}">${r.efficiency_weighted}</td>
        <td class="${tdR}">${r.mppt_count}</td>
        <td class="${tdC}">${actions}</td>
      </tr>`;
  }).join('');
}

function renderPagination(tab, total, page) {
  const pages = Math.ceil(total / 5) || 1;
  const el    = document.getElementById('pagination-' + tab);
  const from  = total === 0 ? 0 : (page - 1) * 5 + 1;
  const to    = Math.min(page * 5, total);
  const btnCls = 'px-3 py-1.5 rounded-lg border text-xs font-medium transition disabled:opacity-40 disabled:cursor-not-allowed';
  el.innerHTML = `
    <span>${from}–${to} de ${total} registros</span>
    <div class="flex items-center gap-2">
      <button class="${btnCls} ${page <= 1 ? 'opacity-40 cursor-not-allowed bg-gray-50 border-gray-200 text-gray-400' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'}"
        onclick="loadTable('${tab}', ${page - 1})" ${page <= 1 ? 'disabled' : ''}>
        ← Anterior
      </button>
      <span class="text-xs text-gray-400">Pág. ${page} / ${pages}</span>
      <button class="${btnCls} ${page >= pages ? 'opacity-40 cursor-not-allowed bg-gray-50 border-gray-200 text-gray-400' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'}"
        onclick="loadTable('${tab}', ${page + 1})" ${page >= pages ? 'disabled' : ''}>
        Siguiente →
      </button>
    </div>`;
}

// ── Modal ─────────────────────────────────────────────────────────────
let currentTab = null;

function showModalError(msg) {
  const el = document.getElementById('modal-error');
  document.getElementById('modal-error-text').textContent = msg;
  el.style.display = 'flex';
}

function clearModalError() {
  const el = document.getElementById('modal-error');
  el.style.display = 'none';
  document.getElementById('modal-error-text').textContent = '';
}

async function openModal(tab, row = null) {
  currentTab = tab;
  clearModalError();
  document.getElementById('modal-title').textContent = row ? 'Editar registro' : 'Nuevo registro';
  document.querySelectorAll('.entity-form').forEach(f => f.classList.add('hidden'));
  document.getElementById('form-' + tab).classList.remove('hidden');
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

  document.getElementById('modal').classList.remove('hidden');
  document.getElementById('modal').classList.add('flex');
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
  document.getElementById('modal').classList.add('hidden');
  document.getElementById('modal').classList.remove('flex');
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

// ── Toast notifications ─────────────────────────────────────────────
function showToast(message, type = 'success') {
  const colours = {
    success: 'bg-green-600',
    error:   'bg-red-600',
    info:    'bg-Ipteblue2',
  };
  const icons = {
    success: `<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>`,
    error:   `<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>`,
    info:    `<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01"/></svg>`,
  };

  const toast = document.createElement('div');
  toast.className = [
    'pointer-events-auto flex items-center gap-2.5 text-white text-sm font-medium',
    'px-4 py-2.5 rounded-xl shadow-lg',
    'translate-x-0 opacity-100 transition-all duration-300',
    colours[type] ?? colours.info,
  ].join(' ');
  toast.innerHTML = (icons[type] ?? '') + `<span>${esc(message)}</span>`;

  const container = document.getElementById('toast-container');
  container.appendChild(toast);

  // Fade out then remove
  setTimeout(() => {
    toast.style.opacity    = '0';
    toast.style.transform  = 'translateX(1rem)';
    setTimeout(() => toast.remove(), 300);
  }, 3500);
}

// ── Negative coefficient real-time validation ──────────────────────
function validateNegative(input, warningId) {
  const val  = parseFloat(input.value);
  const warn = document.getElementById(warningId);
  const invalid = input.value !== '' && !isNaN(val) && val >= 0;
  if (invalid) {
    input.classList.add('border-red-400', 'ring-2', 'ring-red-200', 'focus:ring-red-300');
    input.classList.remove('border-gray-200', 'focus:ring-Ipteblue2');
    warn.classList.remove('hidden');
    warn.classList.add('flex');
  } else {
    input.classList.remove('border-red-400', 'ring-2', 'ring-red-200', 'focus:ring-red-300');
    input.classList.add('border-gray-200', 'focus:ring-Ipteblue2');
    warn.classList.remove('flex');
    warn.classList.add('hidden');
  }
}

function resetNegativeWarnings() {
  ['mod-temp_coeff_voc', 'mod-temp_coeff_pmax'].forEach((id, i) => {
    const input = document.getElementById(id);
    const warn  = document.getElementById(['warn-tcv', 'warn-tcp'][i]);
    input.classList.remove('border-red-400', 'ring-2', 'ring-red-200', 'focus:ring-red-300');
    input.classList.add('border-gray-200', 'focus:ring-Ipteblue2');
    warn.classList.add('hidden');
  });
}

// ── XSS-safe string helper ────────────────────────────────────────────
function esc(s) {
  if (s == null) return '';
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Close modal on backdrop click ─────────────────────────────────────
document.getElementById('modal').addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});

// ── Bootstrap ─────────────────────────────────────────────────────────
loadTable('manufacturadores');
