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
      loading.classList.add('d-none');
      grid.classList.remove('d-none');
    } catch (err) {
      loading.classList.add('d-none');
      errorEl.textContent = '⚠ No se pudo cargar el inventario: ' + err.message;
      errorEl.classList.remove('d-none');
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
    btn.className = 'btn btn-xs ' + (active ? 'btn-primary' : 'btn-default border');
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
      emptyEl.classList.remove('d-none');
      return;
    }
    emptyEl.classList.add('d-none');

    visible.forEach(m => {
      const card = buildCard(m);
      const col  = document.createElement('div');
      col.className = 'col-12 col-sm-6 col-xl-4 mb-3';
      col.appendChild(card);
      grid.appendChild(col);
    });
  }

  // Technology badge colours (Bootstrap badges)
  const techColor = {
    'Monocrystalline': 'badge-primary',
    'Polycrystalline':  'badge-success',
    'Thin Film':        'badge-info',
    'Other':            'badge-warning',
  };

  function buildCard(m) {
    const isSelected = selectedModule && selectedModule.id === m.id;
    const badgeCls   = techColor[m.technology] || 'badge-warning';
    const area       = m.length_m * m.width_m;
    const eta        = (m.pmax_stc / (1000 * area) * 100).toFixed(1);
    const areaStr    = area.toFixed(2);

    const div = document.createElement('div');
    div.dataset.moduleId = m.id;
    div.className = 'card card-outline h-100 cursor-pointer ' +
      (isSelected ? 'card-primary' : 'card-default');
    div.style.cursor = 'pointer';

    div.innerHTML = `
      <div class="card-body p-3">
        <div class="d-flex justify-content-between align-items-start mb-1">
          <div>
            <p class="text-muted mb-0" style="font-size:.72rem;">${m.manufacturer}</p>
            <p class="font-weight-bold mb-1" style="font-size:.9rem; line-height:1.2;">${m.model}</p>
          </div>
          <span class="badge ${badgeCls} ml-1" style="font-size:.65rem; white-space:nowrap;">${m.technology}</span>
        </div>
        <p class="text-primary font-weight-bold mb-2" style="font-size:1.1rem;">
          ${m.pmax_stc} <small class="text-muted font-weight-normal" style="font-size:.75rem;">W pico</small>
        </p>
        <div class="row row-cols-2 no-gutters" style="font-size:.75rem;">
          <div class="col text-muted">Voc</div><div class="col font-weight-bold">${m.voc_stc} V</div>
          <div class="col text-muted">Isc</div><div class="col font-weight-bold">${m.isc_stc} A</div>
          <div class="col text-muted">Vmpp</div><div class="col font-weight-bold">${m.vmpp_stc} V</div>
          <div class="col text-muted">Imp</div><div class="col font-weight-bold">${m.imp_stc} A</div>
          <div class="col text-muted">γP</div><div class="col font-weight-bold">${m.temp_coeff_pmax}%/°C</div>
          <div class="col text-muted">Área</div><div class="col font-weight-bold">${areaStr} m²</div>
          <div class="col text-muted">Eficiencia</div><div class="col font-weight-bold text-success">${eta}%</div>
        </div>
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
      const id  = parseInt(card.dataset.moduleId);
      const sel = id === m.id;
      card.className = 'card card-outline h-100 ' + (sel ? 'card-primary' : 'card-default');
      card.style.cursor = 'pointer';
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
      <div class="col-6 col-sm-3 col-lg-auto mr-3 mb-1">
        <p class="text-muted mb-0" style="font-size:.7rem;">${label}</p>
        <p class="font-weight-bold mb-0" style="font-size:.8rem;">${val}</p>
      </div>`).join('');

    // Compute live results
    computeResults(m);

    resultsEl.classList.remove('d-none');
    contBtn.disabled = false;
    resultsEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  // ── Live calculations ─────────────────────────────────────
  function computeResults(m) {
    const consumo  = parseFloat(document.getElementById('consumo_anual_kwh').value) || 0;
    const hsp      = parseFloat(document.getElementById('hsp').value)               || 0;
    const tmax     = parseFloat(document.getElementById('tmax').value)               || 25;

    const E_dia_Wh      = (consumo / 365) * 1000;
    const P_req_W       = E_dia_Wh / (hsp * 0.75);  // PR = 0.75 (factor de rendimiento estándar)
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
    resultsEl.classList.add('d-none');
    contBtn.disabled = true;
    document.getElementById('selected-module-name').textContent = '—';
    document.querySelectorAll('[data-module-id]').forEach(card => {
      card.className = 'card card-outline card-default h-100';
      card.style.cursor = 'pointer';
    });
    if (window.calcState) {
      delete window.calcState.module;
      delete window.calcState.N;
      delete window.calcState.P_stc_kW;
      delete window.calcState.monthly;
    }
  };

  // ── Deselect ──────────────────────────────────────────────
  document.getElementById('btn-deselect-module').addEventListener('click', function () {
    selectedModule = null;
    resultsEl.classList.add('d-none');
    contBtn.disabled = true;
    document.getElementById('selected-module-name').textContent = '—';
    // Reset card styles
    document.querySelectorAll('[data-module-id]').forEach(card => {
      card.className = 'card card-outline card-default h-100';
      card.style.cursor = 'pointer';
    });
  });

  // ── Continue to Block 3 ───────────────────────────────────
  contBtn.addEventListener('click', function () {
    if (!selectedModule) return;
    history.pushState({ step: 3 }, '', '#paso-3');
    window.showStep(3);
  });

})();
