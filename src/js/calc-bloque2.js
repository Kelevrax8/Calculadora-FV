// ============================================================
//  BLOQUE 2 – Module selector + live calculations
// ============================================================
(function () {

  // ── State ─────────────────────────────────────────────────
  let allModules     = [];
  let filteredIds    = new Set();
  let activeManuf    = 'all';
  let activeTech     = 'all';
  let selectedModule = null;
  let loaded         = false;

  // ── DOM refs ──────────────────────────────────────────────
  const grid       = document.getElementById('modules-grid');
  const loading    = document.getElementById('modules-loading');
  const errorEl    = document.getElementById('modules-error');
  const emptyEl    = document.getElementById('modules-empty');
  const resultsEl  = document.getElementById('calc2-results');
  const contBtn    = document.getElementById('btn-bloque2-continuar');

  // Technology badge colours
  const techColor = {
    'Monocrystalline': 'bg-blue-50 text-blue-700',
    'Polycrystalline': 'bg-green-50 text-green-700',
    'Thin Film':       'bg-purple-50 text-purple-700',
    'Other':           'bg-yellow-50 text-yellow-700',
  };

  // ── Public entry point (called by Block 1 continue btn) ──
  window.loadPVModules = async function () {
    if (loaded) return;
    try {
      const res  = await fetch('/api/calculadora.php?action=get_pv_modules');
      const data = await res.json();
      if (!res.ok || data.error) throw new Error(data.error || 'Error desconocido');
      allModules = data;
      loaded = true;
      buildFilters();
      applyFilters();
      loading.classList.add('hidden');
      grid.classList.remove('hidden');
    } catch (err) {
      loading.classList.add('hidden');
      errorEl.textContent = '⚠ No se pudo cargar el inventario: ' + err.message;
      errorEl.classList.remove('hidden');
    }
  };

  // ── Build filter pill buttons ─────────────────────────────
  function buildFilters() {
    const manufs = ['all', ...new Set(allModules.map(m => m.manufacturer))];
    const techs  = ['all', ...new Set(allModules.map(m => m.technology))];

    renderFilterGroup('filter-manufacturer', manufs, 'manuf', n => n === 'all' ? 'Todos' : n);
    renderFilterGroup('filter-technology',   techs,  'tech',  n => n === 'all' ? 'Todas' : n);
  }

  function renderFilterGroup(containerId, values, type, labelFn) {
    const container = document.getElementById(containerId);
    container.innerHTML = '';
    values.forEach(val => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.dataset.filter = type;
      btn.dataset.value  = val;
      btn.textContent    = labelFn(val);
      setFilterStyle(btn, val === 'all');
      btn.addEventListener('click', () => onFilterClick(type, val));
      container.appendChild(btn);
    });
  }

  function setFilterStyle(btn, active) {
    btn.className = 'rounded-full px-3 py-1 text-xs font-medium transition-colors ' +
      (active
        ? 'bg-Ipteblue text-white'
        : 'bg-white text-gray-600 border border-gray-200 hover:border-Ipteblue');
  }

  function onFilterClick(type, val) {
    if (type === 'manuf') activeManuf = val;
    else                  activeTech  = val;

    // Update pill styles
    document.querySelectorAll(`[data-filter="${type}"]`).forEach(btn => {
      setFilterStyle(btn, btn.dataset.value === val);
    });
    applyFilters();
  }

  // ── Filter + render cards ─────────────────────────────────
  function applyFilters() {
    const visible = allModules.filter(m =>
      (activeManuf === 'all' || m.manufacturer === activeManuf) &&
      (activeTech  === 'all' || m.technology   === activeTech)
    );

    filteredIds = new Set(visible.map(m => m.id));
    grid.innerHTML = '';

    if (visible.length === 0) {
      emptyEl.classList.remove('hidden');
      return;
    }
    emptyEl.classList.add('hidden');

    visible.forEach(m => {
      const card = buildCard(m);
      grid.appendChild(card);
    });
  }

  function buildCard(m) {
    const isSelected = selectedModule && selectedModule.id === m.id;
    const badge      = techColor[m.technology] || 'bg-yellow-50 text-yellow-700';
    const area       = m.length_m * m.width_m;
    const eta        = (m.pmax_stc / (1000 * area) * 100).toFixed(1);  // %
    const areaStr    = area.toFixed(2);

    const div = document.createElement('div');
    div.dataset.moduleId = m.id;
    div.className = 'cursor-pointer rounded-xl border p-4 transition-all ' +
      (isSelected
        ? 'border-2 border-Ipteblue bg-Ipteblue/10'
        : 'border-gray-200 hover:border-Ipteblue hover:shadow-sm');

    div.innerHTML = `
      <div class="flex items-start justify-between gap-2 mb-2">
        <div>
          <p class="text-xs font-semibold text-gray-500">${m.manufacturer}</p>
          <p class="text-sm font-bold text-gray-800 leading-tight">${m.model}</p>
        </div>
        <span class="shrink-0 text-xs font-medium rounded-full px-2 py-0.5 ${badge}">
          ${m.technology}
        </span>
      </div>
      <p class="text-lg font-bold text-Ipteblue mb-2">${m.pmax_stc} <span class="text-xs font-normal text-gray-400">W pico</span></p>
      <div class="grid grid-cols-2 gap-1 mt-2 text-xs">
        <span class="text-gray-500">Voc</span><span class="font-semibold text-gray-800">${m.voc_stc} V</span>
        <span class="text-gray-500">Isc</span><span class="font-semibold text-gray-800">${m.isc_stc} A</span>
        <span class="text-gray-500">Vmpp</span><span class="font-semibold text-gray-800">${m.vmpp_stc} V</span>
        <span class="text-gray-500">Imp</span><span class="font-semibold text-gray-800">${m.imp_stc} A</span>
        <span class="text-gray-500">γP</span><span class="font-semibold text-gray-800">${m.temp_coeff_pmax}%/°C</span>
        <span class="text-gray-500">Área</span><span class="font-semibold text-gray-800">${areaStr} m²</span>
        <span class="text-gray-500">Eficiencia</span><span class="font-semibold text-green-600">${eta}%</span>
      </div>`;

    div.addEventListener('click', () => selectModule(m));
    return div;
  }

  // ── Module selection ──────────────────────────────────────
  function selectModule(m) {
    selectedModule = m;
    window.calcState        = window.calcState || {};
    window.calcState.module = m;

    // Refresh card borders
    document.querySelectorAll('[data-module-id]').forEach(card => {
      const id = parseInt(card.dataset.moduleId);
      const sel = id === m.id;
      card.className = 'cursor-pointer rounded-xl border p-4 transition-all ' +
        (sel ? 'border-2 border-Ipteblue bg-Ipteblue/10' : 'border-gray-200 hover:border-Ipteblue hover:shadow-sm');
    });

    // Module name pill
    document.getElementById('selected-module-name').textContent =
      m.manufacturer + ' – ' + m.model;

    // Spec strip
    document.getElementById('selected-module-specs').innerHTML = [
      ['Pmax STC',    m.pmax_stc + ' W'],
      ['Eficiencia',  (m.pmax_stc / (1000 * m.length_m * m.width_m) * 100).toFixed(1) + ' %'],
      ['Voc',         m.voc_stc  + ' V'],
      ['Isc',         m.isc_stc  + ' A'],
      ['Vmpp',        m.vmpp_stc + ' V'],
      ['Imp',         m.imp_stc  + ' A'],
      ['γP',          m.temp_coeff_pmax + ' %/°C'],
      ['βVoc',        m.temp_coeff_voc  + ' %/°C'],
    ].map(([label, val]) => `
      <div>
        <p class="text-xs text-gray-400">${label}</p>
        <p class="text-xs font-semibold text-gray-800">${val}</p>
      </div>`).join('');

    // Compute live results
    computeResults(m);

    resultsEl.classList.remove('hidden');
    contBtn.disabled = false;
    resultsEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  // ── Live calculations ─────────────────────────────────────
  function computeResults(m) {
    const consumo  = parseFloat(document.getElementById('consumo_anual_kwh').value) || 0;
    const hsp      = parseFloat(document.getElementById('hsp').value)               || 0;
    const tmax     = parseFloat(document.getElementById('tmax').value)               || 25;

    const E_dia_Wh      = (consumo / 365) * 1000;
    const P_req_W       = E_dia_Wh / (hsp * 0.80);
    const N             = Math.ceil(P_req_W / m.pmax_stc);
    const P_stc_kW      = (N * m.pmax_stc) / 1000;

    const gamma         = m.temp_coeff_pmax / 100;          // %/°C → decimal
    const dT_calor      = tmax - 25;
    const P_mod_calor   = m.pmax_stc * (1 + gamma * dT_calor);
    const P_calor_kW    = (N * P_mod_calor) / 1000;
    const pct_calor     = ((P_calor_kW / P_stc_kW) - 1) * 100;

    const Isc_prot      = m.isc_stc * 1.56;

    document.getElementById('res-n-modulos').textContent      = N;
    document.getElementById('res-p-arreglo-stc').textContent  = P_stc_kW.toFixed(2);
    document.getElementById('res-p-arreglo-calor').textContent = P_calor_kW.toFixed(2) + ' kW';
    document.getElementById('res-p-calor-pct').textContent    =
      (pct_calor >= 0 ? '+' : '') + pct_calor.toFixed(1) + '% vs STC';
    document.getElementById('res-isc-prot').textContent       = Isc_prot.toFixed(2);

    // Share with Block 3
    window.calcState          = window.calcState || {};
    window.calcState.N        = N;
    window.calcState.P_stc_kW = P_stc_kW;
  }

  // ── Reset (called by showStep when navigating back to step 1) ──
  window.resetBlock2 = function () {
    selectedModule = null;
    resultsEl.classList.add('hidden');
    contBtn.disabled = true;
    document.getElementById('selected-module-name').textContent = '—';
    document.querySelectorAll('[data-module-id]').forEach(card => {
      card.className = 'cursor-pointer rounded-xl border border-gray-200 p-4 transition-all hover:border-Ipteblue hover:shadow-sm';
    });
    if (window.calcState) {
      delete window.calcState.module;
      delete window.calcState.N;
      delete window.calcState.P_stc_kW;
    }
  };

  // ── Deselect ──────────────────────────────────────────────
  document.getElementById('btn-deselect-module').addEventListener('click', function () {
    selectedModule = null;
    resultsEl.classList.add('hidden');
    contBtn.disabled = true;
    document.getElementById('selected-module-name').textContent = '—';
    // Reset card styles
    document.querySelectorAll('[data-module-id]').forEach(card => {
      card.className = 'cursor-pointer rounded-xl border border-gray-200 p-4 transition-all hover:border-Ipteblue hover:shadow-sm';
    });
  });

  // ── Continue to Block 3 ───────────────────────────────────
  contBtn.addEventListener('click', function () {
    if (!selectedModule) return;
    history.pushState({ step: 3 }, '', '#paso-3');
    window.showStep(3);
  });

})();
