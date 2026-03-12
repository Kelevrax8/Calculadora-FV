// ============================================================
//  BLOQUE 3 – Inverter selector + electrical checks
// ============================================================
(function () {

  // ── State ─────────────────────────────────────────────────
  let allInverters = [], loaded = false;
  let selectedInverter = null;
  let currentNs = 1;
  let activeManuf = 'all', activePhase = 'all';

  // Per-module values computed on init
  let mod, N_total, betaVoc, Voc_cold_per, Vmpp_hot_per, Vmpp_cold_per, P_cold_per;

  // ── DOM refs ──────────────────────────────────────────────
  const invGrid  = document.getElementById('inverters-grid');
  const invLoad  = document.getElementById('inverters-loading');
  const invErr   = document.getElementById('inverters-error');
  const invEmpty = document.getElementById('inverters-empty');
  const results3 = document.getElementById('calc3-results');
  const contBtn3 = document.getElementById('btn-bloque3-continuar');

  // ── Entry point ────────────────────────────────────────────
  window.loadInverters = async function () {
    mod     = window.calcState.module;
    N_total = window.calcState.N;
    const tmin = parseFloat(document.getElementById('tmin').value) || 25;
    const tmax = parseFloat(document.getElementById('tmax').value) || 25;

    betaVoc       = mod.temp_coeff_voc / 100;  // %/°C → decimal
    Voc_cold_per  = mod.voc_stc  * (1 + betaVoc * (tmin - 25));
    Vmpp_hot_per  = mod.vmpp_stc * (1 + betaVoc * (tmax - 25));
    Vmpp_cold_per = mod.vmpp_stc * (1 + betaVoc * (tmin - 25));
    const gammaPmax = mod.temp_coeff_pmax / 100;  // %/°C → decimal (negative)
    P_cold_per    = mod.pmax_stc * (1 + gammaPmax * (tmin - 25));  // W/mod at Tmin

    if (loaded) { refreshStringUI(); applyInvFilters(); return; }

    try {
      const res  = await fetch('/api/calculadora.php?action=get_inverters');
      const data = await res.json();
      if (!res.ok || data.error) throw new Error(data.error || 'Error desconocido');
      allInverters = data;
      loaded = true;

      // Start with all modules in one string; refreshStringUI() will clamp
      currentNs = N_total;

      buildInvFilters();
      refreshStringUI();
      applyInvFilters();

      invLoad.classList.add('d-none');
      invGrid.classList.remove('d-none');
    } catch (err) {
      invLoad.classList.add('d-none');
      invErr.textContent = '⚠ No se pudo cargar el inventario: ' + err.message;
      invErr.classList.remove('d-none');
    }
  };

  // ── String configurator ────────────────────────────────────
  function refreshStringUI() {
    // 1. Compute Ns range bounds (inverter-aware when one is selected)
    let Ns_min, Ns_max, Ns_min_reason, Ns_max_reason;
    if (selectedInverter) {
      const inv       = selectedInverter;
      const Ns_by_vdc  = Math.floor(inv.max_dc_voltage   / Voc_cold_per);   // safety ceiling
      const Ns_by_mppt = Math.floor(inv.mppt_voltage_max / Vmpp_cold_per);  // MPPT window ceiling
      const Ns_max_raw = Math.min(Ns_by_vdc, Ns_by_mppt);
      Ns_max        = Math.min(N_total, Ns_max_raw);
      Ns_max_reason = (Ns_by_mppt <= Ns_by_vdc) ? 'ventana MPPT' : 'Vdc máx';
      Ns_min        = Math.max(1, Math.ceil(inv.startup_voltage / Vmpp_hot_per));
      Ns_min_reason = 'arranque';
    } else {
      // No inverter selected: use the most permissive global bound so the user can browse freely
      const global_vdc_max = Math.max(...allInverters.map(i => i.max_dc_voltage));
      Ns_max        = Math.min(N_total, Math.floor(global_vdc_max / Voc_cold_per));
      Ns_max_reason = 'Vdc máx global';
      Ns_min        = 1;
      Ns_min_reason = '';
    }

    // 2. Clamp currentNs to valid range before computing anything
    currentNs = Math.max(Ns_min, Math.min(currentNs, Ns_max));

    // 3. Derived electrical values
    const Np        = Math.ceil(N_total / currentNs);
    const Voc_cold  = currentNs * Voc_cold_per;
    const Vmpp_hot  = currentNs * Vmpp_hot_per;
    const Vmpp_cold = currentNs * Vmpp_cold_per;

    // 3b. Remainder string detection
    const n_rem      = N_total % currentNs;
    const n_full_str = Math.floor(N_total / currentNs);
    const remEl      = document.getElementById('str-remainder-warning');
    if (n_rem > 0) {
      const rem_Voc_cold  = n_rem * Voc_cold_per;
      const rem_Vmpp_hot  = n_rem * Vmpp_hot_per;
      const rem_Vmpp_cold = n_rem * Vmpp_cold_per;
      document.getElementById('str-rem-breakdown').textContent =
        n_full_str + ' string' + (n_full_str > 1 ? 's' : '') + ' × ' + currentNs +
        ' mód  +  1 string × ' + n_rem + ' mód (string corto)';
      document.getElementById('str-rem-voc-cold').textContent  = rem_Voc_cold.toFixed(1)  + ' V';
      document.getElementById('str-rem-vmpp-hot').textContent  = rem_Vmpp_hot.toFixed(1)  + ' V';
      document.getElementById('str-rem-vmpp-cold').textContent = rem_Vmpp_cold.toFixed(1) + ' V';

      // Find divisors of N_total within the valid Ns stepper range
      const usefulDivisors = [];
      for (let d = Math.max(2, Ns_min); d <= Math.min(N_total - 1, Ns_max); d++) {
        if (N_total % d === 0) usefulDivisors.push(d);
      }
      const adviceEl  = document.getElementById('str-rem-advice');
      const mpptNote  = document.getElementById('str-rem-mppt-note');
      if (selectedInverter) {
        const remV            = (n_rem * Vmpp_hot_per).toFixed(1);
        const rem_mppt_ok     = (n_rem * Vmpp_hot_per) >= selectedInverter.mppt_voltage_min;
        const rem_startup_ok  = (n_rem * Vmpp_hot_per) >= selectedInverter.startup_voltage;

        const line1 = (rem_mppt_ok ? '✓' : '✗')
          + ' MPPT mín (' + selectedInverter.mppt_voltage_min + ' V): '
          + 'Vmpp calor string corto = ' + remV + ' V'
          + (rem_mppt_ok ? ' — dentro del rango.' : ' — ese canal no podrá rastrear.');
        const line2 = (rem_startup_ok ? '✓' : '⚠')
          + ' V arranque (' + selectedInverter.startup_voltage + ' V): '
          + remV + ' V'
          + (rem_startup_ok ? ' — el string corto la supera.' : ' — el string corto podría no arrancar el inversor.');

        mpptNote.innerHTML = '<span class="block">' + line1 + '</span>'
          + '<span class="block mt-1">' + line2 + '</span>';
        mpptNote.className = 'mt-2 small font-weight-bold '
          + (!rem_mppt_ok               ? 'text-danger'
             : !rem_startup_ok          ? 'text-danger'
             :                            'text-success');
        mpptNote.classList.remove('d-none');
      } else {
        mpptNote.classList.add('d-none');
      }
      if (usefulDivisors.length === 0) {
        adviceEl.innerHTML =
          '<strong>' + N_total + '</strong> módulos no tiene divisores exactos en el rango '
          + 'de Ns disponible (' + Ns_min + '–' + Ns_max + '). No es posible configurar '
          + 'strings uniformes con este total. Considera volver al <strong>Paso 2</strong> '
          + 'y ajustar el consumo o el módulo seleccionado.';
      } else {
        adviceEl.innerHTML =
          'Para strings uniformes, ajusta Ns a: '
          + usefulDivisors.map(d =>
              '<button type="button" data-ns-pick="' + d + '" '
              + 'class="btn btn-warning btn-xs mr-1">' + d + '</button>'
            ).join(' ') + '.';
      }
      remEl.classList.remove('d-none');
    } else {
      remEl.classList.add('d-none');
    }

    // 4. Update string configurator DOM
    document.getElementById('ns-value').textContent      = currentNs;
    document.getElementById('np-value').textContent      = Np;
    document.getElementById('str-voc-cold').textContent  = Voc_cold.toFixed(1)  + ' V';
    document.getElementById('str-vmpp-hot').textContent  = Vmpp_hot.toFixed(1)  + ' V';
    document.getElementById('str-vmpp-cold').textContent = Vmpp_cold.toFixed(1) + ' V';
    document.getElementById('str-area-total').textContent =
      (N_total * mod.length_m * mod.width_m).toFixed(1) + ' m²';

    // 5. Np vs MPPT hint
    const hintEl = document.getElementById('np-mppt-hint');
    if (selectedInverter) {
      const ok = Np <= selectedInverter.mppt_count;
      hintEl.textContent = (ok ? '✓ ' : '✗ ') + Np + ' / ' + selectedInverter.mppt_count + ' entradas MPPT';
      hintEl.className   = 'small font-weight-bold ' + (ok ? 'text-success' : 'text-danger');
    } else {
      hintEl.textContent = 'Selecciona un inversor para verificar';
      hintEl.className   = 'small text-muted';
    }

    // 6. Range hint and stepper buttons
    const rangeText = selectedInverter
      ? `Rango: ${Ns_min} (${Ns_min_reason}) – ${Ns_max} (${Ns_max_reason}) mód/string · total: ${N_total}`
      : `Rango: 1 – ${Ns_max} (${Ns_max_reason}) mód/string · total: ${N_total}`;
    document.getElementById('ns-range-hint').textContent = rangeText;

    document.getElementById('btn-ns-dec').disabled = currentNs <= Ns_min;
    document.getElementById('btn-ns-inc').disabled = currentNs >= Ns_max;

    refreshAllBadges();
    if (selectedInverter) computeInvResults(selectedInverter);
  }

  document.getElementById('btn-ns-dec').addEventListener('click', function () {
    if (!this.disabled) { currentNs--; refreshStringUI(); }
  });
  document.getElementById('btn-ns-inc').addEventListener('click', function () {
    if (!this.disabled) { currentNs++; refreshStringUI(); }
  });

  // Delegated listener for remainder-banner Ns suggestion buttons
  document.getElementById('str-remainder-warning').addEventListener('click', function (e) {
    const btn = e.target.closest('[data-ns-pick]');
    if (!btn) return;
    currentNs = parseInt(btn.dataset.nsPick);
    refreshStringUI();
  });

  // ── Filters ────────────────────────────────────────────────
  function buildInvFilters() {
    const manufs = ['all', ...new Set(allInverters.map(i => i.manufacturer))];
    const phases  = ['all', ...new Set(allInverters.map(i => i.phase_type))];
    renderInvFilter('filter-inv-manufacturer', manufs, 'inv-manuf',
      n => n === 'all' ? 'Todos' : n);
    renderInvFilter('filter-inv-phase', phases, 'inv-phase',
      n => n === 'all' ? 'Todos'
         : n === 'Single Phase' ? 'Monofásico'
         : n === 'Three Phase'  ? 'Trifásico'
         : n === 'Split Phase'  ? 'Bifásico' : n);
  }

  function renderInvFilter(containerId, values, type, labelFn) {
    const container = document.getElementById(containerId);
    container.innerHTML = '';
    values.forEach(val => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.dataset.filter = type;
      btn.dataset.value  = val;
      btn.textContent    = labelFn(val);
      setInvFilterStyle(btn, val === 'all');
      btn.addEventListener('click', () => onInvFilter(type, val));
      container.appendChild(btn);
    });
  }

  function setInvFilterStyle(btn, active) {
    btn.className = 'btn btn-xs mr-1 mb-1 ' +
      (active ? 'btn-primary' : 'btn-default border');
  }

  function onInvFilter(type, val) {
    if (type === 'inv-manuf') activeManuf = val;
    else                       activePhase = val;
    document.querySelectorAll(`[data-filter="${type}"]`).forEach(btn => {
      setInvFilterStyle(btn, btn.dataset.value === val);
    });
    applyInvFilters();
  }

  function applyInvFilters() {
    if (!loaded) return;
    const visible = allInverters.filter(i =>
      (activeManuf === 'all' || i.manufacturer === activeManuf) &&
      (activePhase === 'all' || i.phase_type   === activePhase)
    );
    invGrid.innerHTML = '';
    if (visible.length === 0) { invEmpty.classList.remove('d-none'); return; }
    invEmpty.classList.add('d-none');
    visible.forEach(inv => invGrid.appendChild(buildInvCard(inv)));
  }

  // ── Compatibility check ────────────────────────────────────
  function checkCompat(inv) {
    const Np            = Math.ceil(N_total / currentNs);
    const Voc_cold      = currentNs * Voc_cold_per;
    const Vmpp_hot      = currentNs * Vmpp_hot_per;
    const Vmpp_cold     = currentNs * Vmpp_cold_per;
    const I_per_mppt    = mod.imp_stc * 1.25;
    const I_total       = mod.isc_stc * 1.25;
    const P_cold_total  = N_total * P_cold_per;

    const hardFail =
      Np              > inv.mppt_count                   ||
      Voc_cold        > inv.max_dc_voltage               ||
      I_per_mppt      > inv.max_input_current_per_mppt   ||
      I_total         > inv.max_short_circuit_current    ||
      P_cold_total    > inv.pmax_dc_input;

    const warn =
      Vmpp_hot  < inv.mppt_voltage_min ||
      Vmpp_hot  < inv.startup_voltage  ||
      Vmpp_cold > inv.mppt_voltage_max;

    return { hardFail, warn };
  }

  // ── Build inverter card ────────────────────────────────────
  function buildInvCard(inv) {
    const isSelected = selectedInverter && selectedInverter.id === inv.id;
    const compat     = checkCompat(inv);

    const phaseLabel = inv.phase_type === 'Single Phase' ? 'Monofásico'
                     : inv.phase_type === 'Three Phase'  ? 'Trifásico'
                     : inv.phase_type === 'Split Phase'  ? 'Bifásico'
                     : inv.phase_type;
    const phaseColor = inv.phase_type === 'Three Phase'
      ? 'badge-secondary' : 'badge-info';

    const compatText  = compat.hardFail ? '✗ Incompatible'
                      : compat.warn     ? '⚠ Revisar'
                      :                   '✓ Compatible';
    const compatClass = compat.hardFail ? 'badge-danger'
                      : compat.warn     ? 'badge-warning'
                      :                   'badge-success';

    const col = document.createElement('div');
    col.className = 'col-12 col-sm-6 col-xl-4 mb-3';
    const div = document.createElement('div');
    div.dataset.inverterId = inv.id;
    div.className = 'card h-100 cursor-pointer ' +
      (isSelected ? 'card-outline card-primary' : 'card-outline card-default');

    div.innerHTML = `
      <div class="card-body p-3">
        <div class="d-flex align-items-start justify-content-between mb-2">
          <div>
            <p class="text-muted small mb-0">${inv.manufacturer}</p>
            <p class="font-weight-bold mb-0">${inv.model}</p>
          </div>
          <span class="badge ${phaseColor} ml-2">${phaseLabel}</span>
        </div>
        <p class="h5 font-weight-bold text-primary mb-2">
          ${(inv.nominal_ac_power / 1000).toFixed(1)}
          <small class="text-muted font-weight-normal">kW AC</small>
        </p>
        <table class="table table-sm small mb-2">
          <tbody>
            <tr><td class="text-muted border-0 py-1">Vdc máx</td><td class="font-weight-bold border-0 py-1">${inv.max_dc_voltage} V</td></tr>
            <tr><td class="text-muted border-0 py-1">MPPT</td><td class="font-weight-bold border-0 py-1">${inv.mppt_voltage_min}–${inv.mppt_voltage_max} V</td></tr>
            <tr><td class="text-muted border-0 py-1">V arranque</td><td class="font-weight-bold border-0 py-1">${inv.startup_voltage} V</td></tr>
            <tr><td class="text-muted border-0 py-1">I MPPT máx</td><td class="font-weight-bold border-0 py-1">${inv.max_input_current_per_mppt} A</td></tr>
            <tr><td class="text-muted border-0 py-1">I<sub>sc</sub> máx</td><td class="font-weight-bold border-0 py-1">${inv.max_short_circuit_current} A</td></tr>
            <tr><td class="text-muted border-0 py-1">Eficiencia</td><td class="font-weight-bold text-success border-0 py-1">${inv.efficiency_weighted}%</td></tr>
            <tr><td class="text-muted border-0 py-1"># MPPT</td><td class="font-weight-bold border-0 py-1">${inv.mppt_count}</td></tr>
          </tbody>
        </table>
        <span data-compat-badge class="badge ${compatClass}">${compatText}</span>
      </div>`;

    div.addEventListener('click', () => selectInverter(inv));
    col.appendChild(div);
    return col;
  }

  // ── Badge refresh (triggered by Ns change) ─────────────────
  function refreshAllBadges() {
    document.querySelectorAll('[data-inverter-id]').forEach(card => {
      const inv = allInverters.find(i => i.id === parseInt(card.dataset.inverterId));
      if (!inv) return;
      const compat = checkCompat(inv);
      const badge  = card.querySelector('[data-compat-badge]');
      if (!badge) return;
      badge.textContent = compat.hardFail ? '✗ Incompatible'
                        : compat.warn     ? '⚠ Revisar'
                        :                   '✓ Compatible';
      badge.className = 'badge ' +
        (compat.hardFail ? 'badge-danger'
         : compat.warn   ? 'badge-warning'
         :                 'badge-success');
    });
  }

  // ── Select inverter ────────────────────────────────────────
  function selectInverter(inv) {
    selectedInverter = inv;

    document.querySelectorAll('[data-inverter-id]').forEach(card => {
      const sel = parseInt(card.dataset.inverterId) === inv.id;
      card.className = 'card h-100 cursor-pointer ' +
        (sel ? 'card-outline card-primary' : 'card-outline card-default');
    });

    document.getElementById('selected-inverter-name').textContent =
      inv.manufacturer + ' – ' + inv.model;

    document.getElementById('selected-inverter-specs').innerHTML = [
      ['P AC nom',    (inv.nominal_ac_power / 1000).toFixed(1) + ' kW'],
      ['Fase',        inv.phase_type === 'Single Phase' ? 'Monofásico'
                    : inv.phase_type === 'Three Phase'  ? 'Trifásico' : 'Bifásico'],
      ['Vdc máx',    inv.max_dc_voltage + ' V'],
      ['MPPT',       inv.mppt_voltage_min + '–' + inv.mppt_voltage_max + ' V'],
      ['V arranque', inv.startup_voltage + ' V'],
      ['I MPPT máx', inv.max_input_current_per_mppt + ' A'],
      ['η ponderada', inv.efficiency_weighted + '%'],
      ['# MPPT',     inv.mppt_count],
    ].map(([l, v]) =>
      `<div class="col-6 col-sm-4 col-lg-3 mb-1"><small class="text-muted d-block">${l}</small><span class="font-weight-bold small">${v}</span></div>`
    ).join('');

    computeInvResults(inv);
    results3.classList.remove('d-none');
    results3.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

    window.calcState          = window.calcState || {};
    window.calcState.inverter = inv;
    window.calcState.Ns       = currentNs;
    window.calcState.Np       = Math.ceil(N_total / currentNs);
  }

  // ── Electrical result cards ────────────────────────────────
  function computeInvResults(inv) {
    const Np           = Math.ceil(N_total / currentNs);
    const Voc_cold     = currentNs * Voc_cold_per;
    const Vmpp_hot     = currentNs * Vmpp_hot_per;
    const Vmpp_cold    = currentNs * Vmpp_cold_per;
    const I_per_mppt   = mod.imp_stc * 1.25;        // 1 str/MPPT, NOM-001: Imp × 1.25
    const I_total      = mod.isc_stc * 1.25;        // 1 str/MPPT, NOM-001: Isc × 1.25
    const P_cold_total = N_total * P_cold_per;                // W at Tmin
    const P_stc_W      = window.calcState.P_stc_kW * 1000;
    const dc_ac        = P_stc_W / inv.nominal_ac_power;

    // Evaluate all checks
    const npPass       = Np            <= inv.mppt_count;
    const vocPass      = Voc_cold      <= inv.max_dc_voltage;
    const vmppHotPass  = Vmpp_hot      >= inv.mppt_voltage_min;
    const startupPass  = Vmpp_hot      >= inv.startup_voltage;
    const vmppColdPass = Vmpp_cold     <= inv.mppt_voltage_max;
    const iMpptPass    = I_per_mppt    <= inv.max_input_current_per_mppt;
    const iTotalPass   = I_total       <= inv.max_short_circuit_current;
    const pDcPass      = P_cold_total  <= inv.pmax_dc_input;

    setCheck('chk-np-mppt',
      Np + ' strings',
      '≤ ' + inv.mppt_count + ' entradas MPPT',
      npPass, true);

    setCheck('chk-voc',
      Voc_cold.toFixed(1) + ' V',
      '≤ ' + inv.max_dc_voltage + ' V',
      vocPass, true);

    setCheck('chk-vmpp-hot',
      Vmpp_hot.toFixed(1) + ' V',
      '≥ ' + inv.mppt_voltage_min + ' V',
      vmppHotPass, false);

    setCheck('chk-startup-v',
      Vmpp_hot.toFixed(1) + ' V',
      '≥ ' + inv.startup_voltage + ' V (arranque)',
      startupPass, false);

    setCheck('chk-vmpp-cold',
      Vmpp_cold.toFixed(1) + ' V',
      '≤ ' + inv.mppt_voltage_max + ' V',
      vmppColdPass, false);

    setCheck('chk-i-mppt',
      I_per_mppt.toFixed(2) + ' A (Imp ×1.25, 1 str/MPPT)',
      '≤ ' + inv.max_input_current_per_mppt + ' A',
      iMpptPass, true);

    setCheck('chk-i-total',
      I_total.toFixed(2) + ' A (Isc×1.25, 1 str/MPPT)',
      '≤ ' + inv.max_short_circuit_current + ' A',
      iTotalPass, true);

    setCheck('chk-p-dc',
      (P_cold_total / 1000).toFixed(2) + ' kW (T_min = ' +
        (parseFloat(document.getElementById('tmin').value) || 25) + '°C)',
      '≤ ' + (inv.pmax_dc_input / 1000).toFixed(2) + ' kW',
      pDcPass, true);

    const dcacEl  = document.getElementById('res-dcac');
    const dcacHint = document.getElementById('res-dcac-hint');
    dcacEl.textContent = dc_ac.toFixed(2);

    let dcacLabel, dcacColor;
    if (dc_ac < 0.80) {
      dcacLabel = 'Arreglo insuficiente'; dcacColor = 'text-danger';
    } else if (dc_ac < 1.00) {
      dcacLabel = 'Subóptimo';            dcacColor = 'text-warning';
    } else if (dc_ac <= 1.25) {
      dcacLabel = 'Conservador';          dcacColor = 'text-success';
    } else if (dc_ac <= 1.50) {
      dcacLabel = 'Óptimo';               dcacColor = 'text-success font-weight-bold';
    } else {
      dcacLabel = 'Sobredimensionado';    dcacColor = 'text-danger';
    }

    dcacEl.className     = 'h5 font-weight-bold mb-1 ' + dcacColor;
    dcacHint.textContent = dcacLabel;
    dcacHint.className   = 'small font-weight-bold ' + dcacColor;
    document.getElementById('res-dcac-pstc').textContent = (P_stc_W / 1000).toFixed(2) + ' kW';
    document.getElementById('res-dcac-pac').textContent  = (inv.nominal_ac_power / 1000).toFixed(2) + ' kW';

    // Block continue only on hard electrical fails — DC/AC ratio is a design warning, not a hard limit
    const anyHardFail = !npPass || !vocPass || !iMpptPass || !iTotalPass || !pDcPass;
    contBtn3.disabled = anyHardFail;
    if (anyHardFail) {
      const reasons = [];
      if (!npPass)     reasons.push('Núm. strings (' + Np + ') supera entradas MPPT (' + inv.mppt_count + ')');
      if (!vocPass)    reasons.push('Voc en frío supera Vdc máx');
      if (!iMpptPass)  reasons.push('I por MPPT supera el límite');
      if (!iTotalPass) reasons.push('Isc total supera el límite');
      if (!pDcPass)    reasons.push('P arreglo en frío supera entrada DC máx');
      contBtn3.title = reasons.join(' • ');
    } else {
      contBtn3.title = '';
    }

    document.getElementById('selected-string-config').textContent =
      `Configuración: ${currentNs} mód/string × ${Np} strings = ` +
      `${currentNs * Np} módulos (requeridos: ${N_total})`;
  }

  function setCheck(id, actual, limit, pass, isHard) {
    const card  = document.getElementById(id);
    const badge = card.querySelector('[data-badge]');
    card.className = 'card card-outline h-100 ' +
      (pass    ? 'card-success'
       : isHard ? 'card-danger'
       :          'card-warning');
    badge.className = 'badge ' +
      (pass    ? 'badge-success'
       : isHard ? 'badge-danger'
       :          'badge-warning');
    badge.textContent                              = pass ? '✓ OK' : isHard ? '✗ Falla' : '⚠ Revisar';
    card.querySelector('[data-actual]').textContent = actual;
    card.querySelector('[data-limit]').textContent  = limit;
  }

  // ── Reset (called by showStep when navigating back to step ≤ 2) ──
  window.resetBlock3 = function () {
    selectedInverter = null;
    results3.classList.add('d-none');
    contBtn3.disabled = true;
    document.getElementById('selected-inverter-name').textContent = '—';
    document.querySelectorAll('[data-inverter-id]').forEach(card => {
      card.className = 'card h-100 cursor-pointer card-outline card-default';
    });
    if (window.calcState) {
      delete window.calcState.inverter;
      delete window.calcState.Ns;
      delete window.calcState.Np;
    }
  };

  // ── Deselect ──────────────────────────────────────────────
  document.getElementById('btn-deselect-inverter').addEventListener('click', function () {
    selectedInverter = null;
    results3.classList.add('d-none');
    contBtn3.disabled = true;
    document.getElementById('selected-inverter-name').textContent = '—';
    document.querySelectorAll('[data-inverter-id]').forEach(card => {
      card.className = 'card h-100 cursor-pointer card-outline card-default';
    });
    if (window.calcState) {
      delete window.calcState.inverter;
      delete window.calcState.Ns;
      delete window.calcState.Np;
    }
  });

  // ── Back to Block 2 ───────────────────────────────────────
  document.getElementById('btn-bloque3-volver').addEventListener('click', function () {
    history.pushState({ step: 2 }, '', '#paso-2');
    window.showStep(2);
  });

  // ── Continue to Block 4 ───────────────────────────────────
  contBtn3.addEventListener('click', function () {
    if (!selectedInverter) return;
    history.pushState({ step: 4 }, '', '#paso-4');
    window.showStep(4);
  });

})();
