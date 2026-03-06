// ============================================================
//  BLOQUE 4 – RESUMEN Y PROTECCIONES (NOM-001-SEDE-2012)
// ============================================================
(function () {

  // ── Lookup tables ─────────────────────────────────────────
  const OCPD_SIZES = [15, 20, 25, 30, 35, 40, 45, 50, 60, 70, 80, 90, 100, 110, 125, 150, 175, 200];

  // Ampacity for 75 °C Cu conductors in conduit (NOM Tabla 310.15(B)(16), columna 75 °C)
  const AWG_TABLE = [
    { label: '14 AWG',  ampacity: 20  },
    { label: '12 AWG',  ampacity: 25  },
    { label: '10 AWG',  ampacity: 35  },
    { label: '8 AWG',   ampacity: 50  },
    { label: '6 AWG',   ampacity: 65  },
    { label: '4 AWG',   ampacity: 85  },
    { label: '3 AWG',   ampacity: 100 },
    { label: '2 AWG',   ampacity: 115 },
    { label: '1 AWG',   ampacity: 130 },
    { label: '1/0 AWG', ampacity: 150 },
    { label: '2/0 AWG', ampacity: 175 },
    { label: '3/0 AWG', ampacity: 200 },
    { label: '4/0 AWG', ampacity: 230 },
  ];

  // Temperature derating factors for 75 °C conductors (NOM Tabla 310.15(B)(2)(a))
  const DERATING_TABLE = [
    { maxTemp: 30, factor: 1.00 },
    { maxTemp: 35, factor: 0.94 },
    { maxTemp: 40, factor: 0.91 },
    { maxTemp: 45, factor: 0.87 },
    { maxTemp: 50, factor: 0.82 },
    { maxTemp: 55, factor: 0.75 },
    { maxTemp: 60, factor: 0.67 },
  ];

  // Performance ratio — typical value for preliminary design (wiring + inverter + temp + soiling losses)
  // Must match the PR used in block 2 for module sizing (calc-bloque2.js)
  const PR = 0.75;

  // ── State ─────────────────────────────────────────────────
  let deratingOn = false;

  // ── Helpers ───────────────────────────────────────────────
  function getDeratingFactor(tAmb) {
    const row = DERATING_TABLE.find(r => tAmb <= r.maxTemp);
    return row ? row.factor : DERATING_TABLE[DERATING_TABLE.length - 1].factor;
  }

  function nextOCPD(iDesign) {
    const found = OCPD_SIZES.find(s => s >= iDesign);
    return found ?? `>${OCPD_SIZES[OCPD_SIZES.length - 1]} A (consultar)`;
  }

  function minAWG(iRequired) {
    const found = AWG_TABLE.find(r => r.ampacity >= iRequired);
    return found ? found.label : 'Mayor a 4/0 AWG (consultar)';
  }

  function setText(id, val) {
    const el = document.getElementById(id);
    if (el) el.textContent = val;
  }

  // ── Main populate function ─────────────────────────────────
  function populateBlock4() {
    const cs  = window.calcState;
    if (!cs || !cs.module || !cs.inverter) return;

    const mod = cs.module;
    const inv = cs.inverter;
    const Ns  = cs.Ns;
    const Np  = cs.Np;
    const N   = cs.N;

    const tmin = parseFloat(document.getElementById('tmin').value) || 25;
    const tmax = parseFloat(document.getElementById('tmax').value) || 25;
    const hsp  = parseFloat(document.getElementById('hsp').value)  || 0;
    const lat  = document.getElementById('latitud').value  || '—';
    const lng  = document.getElementById('longitud').value || '—';
    const consumo = parseFloat(document.getElementById('consumo_anual_kwh').value) || 0;

    // ── Derived electrical values ──────────────────────────
    const betaVoc      = mod.temp_coeff_voc  / 100;
    const gammaPmax    = mod.temp_coeff_pmax / 100;
    const Voc_cold_per  = mod.voc_stc  * (1 + betaVoc   * (tmin - 25));
    const Vmpp_hot_per  = mod.vmpp_stc * (1 + betaVoc   * (tmax - 25));
    const Vmpp_cold_per = mod.vmpp_stc * (1 + betaVoc   * (tmin - 25));
    const P_cold_per    = mod.pmax_stc * (1 + gammaPmax * (tmin - 25));

    const Voc_cold     = Ns * Voc_cold_per;
    const Vmpp_hot     = Ns * Vmpp_hot_per;
    const Vmpp_cold    = Ns * Vmpp_cold_per;
    const Vmpp_nom     = Ns * mod.vmpp_stc;   // STC — display only
    const Isc_array    = Np * mod.isc_stc;    // display only
    const I_per_mppt   = mod.imp_stc * 1.25;          // NOM-001: Imp × 1.25, per MPPT input (1 string/MPPT)
    const I_total      = Np * mod.isc_stc * 1.25;    // NOM-001: Np × Isc × 1.25 total array
    const P_cold_total = N   * P_cold_per;    // W at Tmin
    const P_stc_W      = cs.P_stc_kW * 1000;
    const dc_ac        = P_stc_W / inv.nominal_ac_power;

    // ── Site & Design ──────────────────────────────────────
    setText('s4-location',    `${parseFloat(lat).toFixed(2)}° N, ${parseFloat(lng).toFixed(2)}° O`);
    setText('s4-consumption', consumo.toLocaleString('es-MX') + ' kWh/año');
    setText('s4-hsp',         hsp.toFixed(2) + ' h/día');
    setText('s4-temps',       `${tmin.toFixed(1)} °C / ${tmax.toFixed(1)} °C`);

    // ── PV Array ───────────────────────────────────────────
    setText('s4-module-name',    `${mod.manufacturer} — ${mod.model}`);
    setText('s4-module-power',   mod.pmax_stc + ' Wp');
    setText('s4-total-modules',  `${N} (${Ns}S × ${Np}P)`);
    setText('s4-array-power',    cs.P_stc_kW.toFixed(2) + ' kWp');
    setText('s4-vmpp-array',     `${Ns} × ${mod.vmpp_stc} V = ${Vmpp_nom.toFixed(1)} V`);
    setText('s4-voc-array',      `${Ns} × ${mod.voc_stc} V = ${(Ns * mod.voc_stc).toFixed(1)} V`);
    setText('s4-isc-array',      `${Np} × ${mod.isc_stc} A = ${Isc_array.toFixed(1)} A`);

    // ── Inverter ───────────────────────────────────────────
    setText('s4-inverter-name',  `${inv.manufacturer} — ${inv.model}`);
    setText('s4-inverter-power', inv.nominal_ac_power + ' W');
    setText('s4-mppt-range',     `${inv.mppt_voltage_min} – ${inv.mppt_voltage_max} V`);
    setText('s4-inverter-imax',  inv.max_short_circuit_current + ' A');
    setText('s4-startup-voltage',inv.startup_voltage + ' V');
    setText('s4-ac-voltage',     inv.ac_voltage_nominal + ' V');

    // ── Compatibility checks ───────────────────────────────
    const npPass       = Np           <= inv.mppt_count;
    const vocPass      = Voc_cold     <= inv.max_dc_voltage;
    const vmppHotPass  = Vmpp_hot     >= inv.mppt_voltage_min;
    const startupPass  = Vmpp_hot     >= inv.startup_voltage;
    const vmppColdPass = Vmpp_cold    <= inv.mppt_voltage_max;
    const iMpptPass    = I_per_mppt   <= inv.max_input_current_per_mppt;
    const iTotalPass   = I_total      <= inv.max_short_circuit_current;
    const pDcPass      = P_cold_total <= inv.pmax_dc_input;

    // hard = blocks continue in block 3; soft = warning only
    const checks = [
      {
        label:  'Np ≤ Entradas MPPT del inversor',
        detail: `${Np} string(s) ≤ ${inv.mppt_count} MPPT`,
        pass:   npPass,  hard: true,
      },
      {
        label:  'Voc en frío ≤ Tensión máx. DC',
        detail: `${Voc_cold.toFixed(1)} V ≤ ${inv.max_dc_voltage} V`,
        pass:   vocPass, hard: true,
      },
      {
        label:  'Vmpp en calor ≥ Límite inferior MPPT',
        detail: `${Vmpp_hot.toFixed(1)} V ≥ ${inv.mppt_voltage_min} V`,
        pass:   vmppHotPass, hard: false,
      },
      {
        label:  'Vmpp en calor ≥ Tensión de arranque',
        detail: `${Vmpp_hot.toFixed(1)} V ≥ ${inv.startup_voltage} V`,
        pass:   startupPass, hard: false,
      },
      {
        label:  'Vmpp en frío ≤ Límite superior MPPT',
        detail: `${Vmpp_cold.toFixed(1)} V ≤ ${inv.mppt_voltage_max} V`,
        pass:   vmppColdPass, hard: false,
      },
      {
        label:  'Corriente por MPPT ≤ Imáx entrada (Imp × 1.25)',
        detail: `${I_per_mppt.toFixed(2)} A ≤ ${inv.max_input_current_per_mppt} A`,
        pass:   iMpptPass, hard: true,
      },
      {
        label:  'Isc total ≤ Corriente máx. CC entrada (Np × Isc × 1.25)',
        detail: `${I_total.toFixed(2)} A = ${Np} × ${mod.isc_stc} A × 1.25 ≤ ${inv.max_short_circuit_current} A`,
        pass:   iTotalPass, hard: true,
      },
      {
        label:  'P arreglo en frío ≤ Entrada DC máx.',
        detail: `${(P_cold_total/1000).toFixed(2)} kW (T_min=${tmin}°C) ≤ ${(inv.pmax_dc_input/1000).toFixed(2)} kW`,
        pass:   pDcPass, hard: true,
      },
    ];

    const anyHardFail = !npPass || !vocPass || !iMpptPass || !iTotalPass || !pDcPass;
    const anySoftFail = !vmppHotPass || !startupPass || !vmppColdPass;

    renderVerdictBanner(anyHardFail, anySoftFail);
    renderCompatTable(checks);

    // ── DC/AC ratio ────────────────────────────────────────
    let dcacLabel, dcacColor;
    if      (dc_ac < 0.80)  { dcacLabel = 'Arreglo insuficiente'; dcacColor = 'text-red-500'; }
    else if (dc_ac < 1.00)  { dcacLabel = 'Subóptimo';            dcacColor = 'text-amber-500'; }
    else if (dc_ac <= 1.25) { dcacLabel = 'Conservador';          dcacColor = 'text-green-400'; }
    else if (dc_ac <= 1.50) { dcacLabel = 'Óptimo';               dcacColor = 'text-green-600'; }
    else                    { dcacLabel = 'Sobredimensionado';     dcacColor = 'text-red-500'; }

    const dcacEl = document.getElementById('s4-dcac-value');
    const dcacLabelEl = document.getElementById('s4-dcac-label');
    if (dcacEl)      { dcacEl.textContent = dc_ac.toFixed(2); dcacEl.className = 'text-sm font-bold ' + dcacColor; }
    if (dcacLabelEl) { dcacLabelEl.textContent = dcacLabel;   dcacLabelEl.className = 'text-xs font-semibold ' + dcacColor; }

    // ── Energy estimate ────────────────────────────────────
    const E_year   = cs.P_stc_kW * hsp * 365 * PR;
    const coverage = consumo > 0 ? Math.min((E_year / consumo) * 100, 999) : 0;
    setText('s4-energy-production', E_year.toFixed(0) + ' kWh/año');
    setText('s4-self-sufficiency',  coverage.toFixed(1) + '%');
    setText('s4-pr',                (PR * 100).toFixed(0) + '%');
    // ── Monthly table ────────────────────────────────────
    renderMonthlyTable(cs.monthly, cs.P_stc_kW, consumo);
    // ── Electrical protection ──────────────────────────────
    computeProtection(mod, inv, tmax);
  }

  // ── Monthly table renderer ────────────────────────────────
  const MONTH_NAMES = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                       'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
  const MONTH_DAYS  = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

  let monthlyProduction = [];   // kWh per month, set once on render
  let showConsumption   = false;

  function renderMonthlyTable(monthly, P_stc_kW, consumo) {
    const section = document.getElementById('s4-monthly-section');
    const tbody   = document.getElementById('s4-monthly-tbody');
    const tfoot   = document.getElementById('s4-monthly-tfoot');
    if (!section || !tbody || !tfoot) return;

    if (!monthly || monthly.length !== 12) {
      section.classList.add('hidden');
      return;
    }

    section.classList.remove('hidden');

    // Pre-compute production per month
    monthlyProduction = monthly.map((row, i) =>
      P_stc_kW * row.ghi * MONTH_DAYS[i] * PR
    );

    // Build rows — consumption column contains an editable input
    tbody.innerHTML = monthly.map((row, i) => {
      const dias  = MONTH_DAYS[i];
      const prod  = monthlyProduction[i];
      const rowBg = i % 2 === 0 ? '' : 'bg-gray-50/60';
      return `
        <tr class="${rowBg} border-b border-gray-100" data-month="${i}">
          <td class="px-3 py-2 text-gray-700 font-medium">${MONTH_NAMES[i]}</td>
          <td class="px-3 py-2 text-right text-gray-600">${row.ghi.toFixed(2)}</td>
          <td class="px-3 py-2 text-right text-gray-600">${dias}</td>
          <td class="px-3 py-2 text-right text-gray-700 font-semibold">${Math.round(prod)}</td>
          <td class="cons-col hidden px-3 py-2 text-right">
            <input type="number" min="0" step="1"
              id="cons-input-${i}"
              class="w-20 rounded border border-gray-200 px-2 py-1 text-right
                     text-gray-700 focus:outline-none focus:ring-1 focus:ring-Ipteblue/50"
              placeholder="—"/>
          </td>
          <td class="cons-col hidden px-3 py-2 text-right font-semibold text-gray-400"
              id="bal-${i}">—</td>
        </tr>`;
    }).join('');

    // Totals row
    const totalProd = monthlyProduction.reduce((a, b) => a + b, 0);
    tfoot.innerHTML = `
      <tr>
        <td class="px-3 py-2 text-gray-700">Total anual</td>
        <td class="px-3 py-2 text-right text-gray-500">—</td>
        <td class="px-3 py-2 text-right text-gray-700">365</td>
        <td class="px-3 py-2 text-right text-gray-800" id="s4-total-prod">${Math.round(totalProd)}</td>
        <td class="cons-col hidden px-3 py-2 text-right text-gray-800" id="s4-total-cons">—</td>
        <td class="cons-col hidden px-3 py-2 text-right" id="s4-total-bal">—</td>
      </tr>`;

    // Live balance update on any consumption input
    tbody.addEventListener('input', function (e) {
      if (!e.target.matches('input[id^="cons-input-"]')) return;
      updateBalances();
    });
  }

  function updateBalances() {
    let totalCons = 0, totalBal = 0, hasAnyValue = false;

    for (let i = 0; i < 12; i++) {
      const input = document.getElementById('cons-input-' + i);
      const balEl = document.getElementById('bal-' + i);
      if (!input || !balEl) continue;

      const val = parseFloat(input.value);
      if (isNaN(val) || input.value === '') {
        balEl.textContent = '—';
        balEl.style.color = '';
        continue;
      }

      hasAnyValue   = true;
      const prod    = monthlyProduction[i];
      const balance = prod - val;
      totalCons    += val;
      totalBal     += balance;

      const sign  = balance >= 0 ? '+' : '';
      balEl.textContent  = sign + Math.round(balance);
      balEl.style.color  = balance >= 0 ? 'var(--color-green-600, #16a34a)' : 'var(--color-red-500, #ef4444)';
      balEl.style.fontWeight = 'bold';
    }

    const consEl = document.getElementById('s4-total-cons');
    const totBal = document.getElementById('s4-total-bal');
    if (consEl) consEl.textContent = hasAnyValue ? Math.round(totalCons) : '—';
    if (totBal) {
      if (hasAnyValue) {
        const sign = totalBal >= 0 ? '+' : '';
        totBal.textContent  = sign + Math.round(totalBal);
        totBal.style.color  = totalBal >= 0 ? 'var(--color-green-600, #16a34a)' : 'var(--color-red-500, #ef4444)';
        totBal.style.fontWeight = 'bold';
      } else {
        totBal.textContent = '—';
        totBal.style.color = '';
        totBal.style.fontWeight = '';
      }
    }
  }

  // Consumption column toggle
  document.querySelectorAll('.cons-toggle-btn').forEach(btn => {
    btn.addEventListener('click', function () {
      showConsumption = this.dataset.cons === 'on';

      document.querySelectorAll('.cons-toggle-btn').forEach(b => {
        const active = b.dataset.cons === (showConsumption ? 'on' : 'off');
        b.classList.toggle('bg-Ipteblue',    active);
        b.classList.toggle('text-white',     active);
        b.classList.toggle('bg-white',       !active);
        b.classList.toggle('text-gray-500',  !active);
      });

      document.querySelectorAll('.cons-col').forEach(el => {
        el.classList.toggle('hidden', !showConsumption);
      });
    });
  });

  // ── Compat table renderer ──────────────────────────────────
  function renderCompatTable(checks) {
    const container = document.getElementById('s4-compat-table');
    if (!container) return;
    container.innerHTML = checks.map(c => {
      const bg    = c.pass ? 'bg-green-50/40'  : c.hard ? 'bg-red-50/40'   : 'bg-amber-50/40';
      const icon  = c.pass ? '✔'              : c.hard ? '✖'               : '⚠';
      const color = c.pass ? 'text-green-600'  : c.hard ? 'text-red-500'   : 'text-amber-500';
      return `
        <div class="flex items-center justify-between px-4 py-2.5 text-xs ${bg}">
          <div>
            <span class="font-medium text-gray-700">${c.label}</span>
            <span class="ml-2 text-gray-400">${c.detail}</span>
          </div>
          <span class="text-base font-bold ${color} shrink-0 ml-3">${icon}</span>
        </div>`;
    }).join('');
  }

  // ── Verdict banner ─────────────────────────────────────────
  function renderVerdictBanner(anyHardFail, anySoftFail) {
    const el = document.getElementById('verdict-banner');
    if (!el) return;
    if (anyHardFail) {
      el.className = 'flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 px-5 py-3 text-sm font-semibold text-red-700';
      el.innerHTML = '<span class="text-lg">✖</span> Incompatibilidad crítica detectada — revisa los parámetros marcados en rojo';
    } else if (anySoftFail) {
      el.className = 'flex items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 px-5 py-3 text-sm font-semibold text-amber-700';
      el.innerHTML = '<span class="text-lg">⚠</span> Compatible con advertencias — revisa los parámetros marcados en amarillo';
    } else {
      el.className = 'flex items-center gap-3 rounded-xl border border-green-200 bg-green-50 px-5 py-3 text-sm font-semibold text-green-700';
      el.innerHTML = '<span class="text-lg">✔</span> Sistema compatible — todos los parámetros dentro de especificación';
    }
  }

  // ── Protection calculator ──────────────────────────────────
  function computeProtection(mod, inv, tmax) {
    const factor   = deratingOn ? getDeratingFactor(tmax) : 1.0;

    function fmtOCPD(val) { return typeof val === 'number' ? val + ' A' : val; }

    // DC circuit
    const I_dc_design   = mod.isc_stc * 1.56;
    const I_dc_required = I_dc_design / factor;
    setText('prot-isc-module', mod.isc_stc.toFixed(2) + ' A');
    setText('prot-dc-idesign', I_dc_design.toFixed(2) + ' A'
      + (deratingOn ? ` ÷ ${factor} = ${I_dc_required.toFixed(2)} A requeridos en tabla` : ''));
    // OCPD is sized to design current; conductor is sized to derated table requirement
    setText('prot-dc-ocpd', fmtOCPD(nextOCPD(I_dc_design)));
    setText('prot-dc-awg',  minAWG(I_dc_required));

    // AC circuit — formula depends on phase type
    const isThreePhase  = inv.phase_type === 'Three Phase';
    const phaseDiv      = isThreePhase ? (Math.sqrt(3) * inv.ac_voltage_nominal) : inv.ac_voltage_nominal;
    const phaseFmt      = isThreePhase
      ? `${inv.nominal_ac_power} W ÷ (√3 × ${inv.ac_voltage_nominal} V) = `
      : `${inv.nominal_ac_power} W ÷ ${inv.ac_voltage_nominal} V = `;
    const I_ac_base     = inv.nominal_ac_power / phaseDiv;
    const I_ac_design   = I_ac_base * 1.25;
    const I_ac_required = I_ac_design / factor;
    setText('prot-ac-phase',   inv.phase_type);
    setText('prot-ac-ratio',   phaseFmt + I_ac_base.toFixed(2) + ' A');
    setText('prot-ac-idesign', I_ac_design.toFixed(2) + ' A'
      + (deratingOn ? ` ÷ ${factor} = ${I_ac_required.toFixed(2)} A requeridos en tabla` : ''));
    // OCPD is sized to design current; conductor is sized to derated table requirement
    setText('prot-ac-ocpd', fmtOCPD(nextOCPD(I_ac_design)));
    setText('prot-ac-awg',  minAWG(I_ac_required));
  }

  // ── Derating toggle ────────────────────────────────────────
  document.querySelectorAll('.derating-btn').forEach(btn => {
    btn.addEventListener('click', function () {
      deratingOn = this.dataset.derating === 'on';

      document.querySelectorAll('.derating-btn').forEach(b => {
        const active = b.dataset.derating === (deratingOn ? 'on' : 'off');
        b.classList.toggle('bg-Ipteblue', active);
        b.classList.toggle('text-white',  active);
        b.classList.toggle('bg-white',    !active);
        b.classList.toggle('text-gray-500', !active);
      });

      const hint = document.getElementById('derating-hint');
      if (deratingOn && window.calcState) {
        const tmax  = parseFloat(document.getElementById('tmax').value) || 25;
        const f     = getDeratingFactor(tmax);
        hint.textContent = `Tamb máx = ${tmax.toFixed(1)} °C → factor ${f} (Tabla 310.15(B)(2)(a), conductores a 75 °C)`;
        hint.classList.remove('hidden');
      } else {
        hint.classList.add('hidden');
      }

      if (window.calcState && window.calcState.module && window.calcState.inverter) {
        const tmax = parseFloat(document.getElementById('tmax').value) || 25;
        computeProtection(window.calcState.module, window.calcState.inverter, tmax);
      }
    });
  });

  // ── Back to block 3 ───────────────────────────────────────
  document.getElementById('btn-bloque4-volver').addEventListener('click', function () {
    history.pushState({ step: 3 }, '', '#paso-3');
    window.showStep(3);
  });

  // ── Start over ────────────────────────────────────────────
  document.getElementById('btn-reiniciar').addEventListener('click', function () {
    if (typeof window.resetBlock3 === 'function') window.resetBlock3();
    if (typeof window.resetBlock2 === 'function') window.resetBlock2();
    if (typeof window.resetBlock1 === 'function') window.resetBlock1();
    window.calcState = {};
    deratingOn = false;
    document.getElementById('btn-derating-off').classList.add('bg-Ipteblue', 'text-white');
    document.getElementById('btn-derating-off').classList.remove('bg-white', 'text-gray-500');
    document.getElementById('btn-derating-on').classList.remove('bg-Ipteblue', 'text-white');
    document.getElementById('btn-derating-on').classList.add('bg-white', 'text-gray-500');
    document.getElementById('derating-hint').classList.add('hidden');
    history.pushState({ step: 1 }, '', '#paso-1');
    window.showStep(1);
  });

  // ── Reset (called by showStep if we ever navigate back from a future step) ─
  window.resetBlock4 = function () {
    deratingOn        = false;
    showConsumption   = false;
    monthlyProduction = [];
    const off = document.getElementById('btn-derating-off');
    const on  = document.getElementById('btn-derating-on');
    if (off) { off.classList.add('bg-Ipteblue','text-white'); off.classList.remove('bg-white','text-gray-500'); }
    if (on)  { on.classList.remove('bg-Ipteblue','text-white'); on.classList.add('bg-white','text-gray-500'); }
    const hint = document.getElementById('derating-hint');
    if (hint) hint.classList.add('hidden');
    // Reset consumption toggle
    const cOff = document.getElementById('btn-cons-off');
    const cOn  = document.getElementById('btn-cons-on');
    if (cOff) { cOff.classList.add('bg-Ipteblue','text-white'); cOff.classList.remove('bg-white','text-gray-500'); }
    if (cOn)  { cOn.classList.remove('bg-Ipteblue','text-white'); cOn.classList.add('bg-white','text-gray-500'); }
    document.querySelectorAll('.cons-col').forEach(el => el.classList.add('hidden'));
  };

  // ── Export to Excel ───────────────────────────────────────
  function fmtOCPD(val) { return typeof val === 'number' ? val + ' A' : String(val); }

  function buildExportPayload() {
    const cs  = window.calcState;
    const mod = cs.module;
    const inv = cs.inverter;
    const Ns  = cs.Ns;
    const Np  = cs.Np;
    const N   = cs.N;

    const tmin    = parseFloat(document.getElementById('tmin').value)              || 25;
    const tmax    = parseFloat(document.getElementById('tmax').value)              || 25;
    const hsp     = parseFloat(document.getElementById('hsp').value)               || 0;
    const lat     = parseFloat(document.getElementById('latitud').value)           || 0;
    const lng     = parseFloat(document.getElementById('longitud').value)          || 0;
    const consumo = parseFloat(document.getElementById('consumo_anual_kwh').value) || 0;

    // Re-derive electrical values (same as populateBlock4)
    const betaVoc       = mod.temp_coeff_voc  / 100;
    const gammaPmax     = mod.temp_coeff_pmax / 100;
    const Voc_cold      = Ns * mod.voc_stc  * (1 + betaVoc   * (tmin - 25));
    const Vmpp_hot      = Ns * mod.vmpp_stc * (1 + betaVoc   * (tmax - 25));
    const Vmpp_cold     = Ns * mod.vmpp_stc * (1 + betaVoc   * (tmin - 25));
    const P_cold_per    = mod.pmax_stc * (1 + gammaPmax * (tmin - 25));
    const Isc_array     = Np * mod.isc_stc;
    const I_per_mppt    = mod.imp_stc * 1.25;          // per MPPT input (1 string/MPPT)
    const I_total       = Np * mod.isc_stc * 1.25;    // total array Np × Isc × 1.25
    const P_cold_total  = N * P_cold_per;
    const dc_ac         = (cs.P_stc_kW * 1000) / inv.nominal_ac_power;

    // Checks
    const npPass       = Np           <= inv.mppt_count;
    const vocPass      = Voc_cold     <= inv.max_dc_voltage;
    const vmppHotPass  = Vmpp_hot     >= inv.mppt_voltage_min;
    const startupPass  = Vmpp_hot     >= inv.startup_voltage;
    const vmppColdPass = Vmpp_cold    <= inv.mppt_voltage_max;
    const iMpptPass    = I_per_mppt   <= inv.max_input_current_per_mppt;
    const iTotalPass   = I_total      <= inv.max_short_circuit_current;
    const pDcPass      = P_cold_total <= inv.pmax_dc_input;

    const checks = [
      { label: 'Np ≤ Entradas MPPT del inversor',           detail: `${Np} string(s) ≤ ${inv.mppt_count} MPPT`,                                                               pass: npPass,       hard: true  },
      { label: 'Voc en frío ≤ Tensión máx. DC',             detail: `${Voc_cold.toFixed(1)} V ≤ ${inv.max_dc_voltage} V`,                                                     pass: vocPass,      hard: true  },
      { label: 'Vmpp en calor ≥ Límite inferior MPPT',      detail: `${Vmpp_hot.toFixed(1)} V ≥ ${inv.mppt_voltage_min} V`,                                                   pass: vmppHotPass,  hard: false },
      { label: 'Vmpp en calor ≥ Tensión de arranque',       detail: `${Vmpp_hot.toFixed(1)} V ≥ ${inv.startup_voltage} V`,                                                    pass: startupPass,  hard: false },
      { label: 'Vmpp en frío ≤ Límite superior MPPT',       detail: `${Vmpp_cold.toFixed(1)} V ≤ ${inv.mppt_voltage_max} V`,                                                  pass: vmppColdPass, hard: false },
      { label: 'Corriente por MPPT ≤ Imáx entrada (Imp × 1.25)',          detail: `${I_per_mppt.toFixed(2)} A ≤ ${inv.max_input_current_per_mppt} A`,                                                            pass: iMpptPass,    hard: true  },
      { label: 'Isc total ≤ Corriente máx. CC entrada (Np × Isc × 1.25)', detail: `${I_total.toFixed(2)} A = ${Np} × ${mod.isc_stc} A × 1.25 ≤ ${inv.max_short_circuit_current} A`,                     pass: iTotalPass,   hard: true  },
      { label: 'P arreglo en frío ≤ Entrada DC máx.',       detail: `${(P_cold_total/1000).toFixed(2)} kW (T_min=${tmin}°C) ≤ ${(inv.pmax_dc_input/1000).toFixed(2)} kW`,    pass: pDcPass,      hard: true  },
    ];

    // Energy
    const E_year   = cs.P_stc_kW * hsp * 365 * PR;
    const coverage = consumo > 0 ? Math.min((E_year / consumo) * 100, 999) : 0;

    // Protection
    const factor        = deratingOn ? getDeratingFactor(tmax) : 1.0;
    const I_dc_design   = mod.isc_stc * 1.56;
    const I_dc_required = I_dc_design / factor;
    const isThreePhase  = inv.phase_type === 'Three Phase';
    const phaseDiv      = isThreePhase ? (Math.sqrt(3) * inv.ac_voltage_nominal) : inv.ac_voltage_nominal;
    const I_ac_base     = inv.nominal_ac_power / phaseDiv;
    const I_ac_design   = I_ac_base * 1.25;
    const I_ac_required = I_ac_design / factor;

    // Monthly — include consumption + balance if the user toggled that view on
    const monthly = (cs.monthly && cs.monthly.length === 12)
      ? cs.monthly.map((row, i) => {
          const prod  = monthlyProduction[i] ?? (cs.P_stc_kW * row.ghi * MONTH_DAYS[i] * PR);
          const entry = { ghi: row.ghi, production: prod };

          if (showConsumption) {
            const input = document.getElementById('cons-input-' + i);
            const val   = input ? parseFloat(input.value) : NaN;
            if (!isNaN(val) && input.value !== '') {
              entry.consumo = val;
              entry.balance = prod - val;
            }
          }

          return entry;
        })
      : null;

    return {
      site:    { lat, lng, consumo, hsp, tmin, tmax },
      module:  { ...mod },
      array:   { Ns, Np, N, P_stc_kW: cs.P_stc_kW, Voc_cold, Vmpp_hot, Vmpp_cold, Isc_array },
      inverter:{ ...inv },
      checks,
      energy:  { E_year, coverage, PR, dc_ac },
      protection: {
        derating_on:     deratingOn,
        derating_factor: factor,
        tmax,
        dc: { isc_module: mod.isc_stc, I_design: I_dc_design,
              I_required: I_dc_required,
              OCPD: fmtOCPD(nextOCPD(I_dc_design)), AWG: minAWG(I_dc_required) },
        ac: { phase_type: inv.phase_type, I_base: I_ac_base, I_design: I_ac_design,
              I_required: I_ac_required,
              OCPD: fmtOCPD(nextOCPD(I_ac_design)), AWG: minAWG(I_ac_required) },
      },
      monthly,
    };
  }

  async function handleExport() {
    const btn      = document.getElementById('btn-excel-export');
    const origHTML = btn.innerHTML;
    btn.disabled   = true;
    btn.innerHTML  = '<svg class="w-3.5 h-3.5 animate-spin inline mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg> Generando…';

    try {
      const payload = buildExportPayload();
      const res     = await fetch('/api/export_excel.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify(payload),
      });

      if (!res.ok) {
        const err = await res.json().catch(() => ({ error: 'Error desconocido' }));
        throw new Error(err.error || 'Error al generar el archivo');
      }

      const blob     = await res.blob();
      const url      = URL.createObjectURL(blob);
      const a        = document.createElement('a');
      a.href         = url;
      a.download     = `Sistema-FV-${new Date().toISOString().slice(0, 10)}.xlsx`;
      a.click();
      URL.revokeObjectURL(url);

      btn.textContent = '✓ Descargado';
      setTimeout(() => { btn.disabled = false; btn.innerHTML = origHTML; }, 2500);
    } catch (err) {
      alert('No se pudo exportar: ' + err.message);
      btn.disabled  = false;
      btn.innerHTML = origHTML;
    }
  }

  document.getElementById('btn-excel-export').addEventListener('click', handleExport);

  // ── Called by showStep(4) to trigger population ───────────
  window.loadBlock4 = function () {
    populateBlock4();
  };

})();
