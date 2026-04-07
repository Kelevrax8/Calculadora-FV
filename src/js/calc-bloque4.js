// ============================================================
//  BLOQUE 4 – RESUMEN Y PROTECCIONES (NOM-001-SEDE-2012)
// ============================================================
(function () {

  // ── Lookup tables ─────────────────────────────────────────
  const OCPD_SIZES     = [15, 20, 25, 30, 35, 40, 45, 50, 60, 70, 80, 90, 100, 110, 125, 150, 175, 200];
  const GPV_FUSE_SIZES = [1, 2, 3, 4, 5, 6, 8, 10, 12, 15, 16, 20, 25, 30, 32, 35, 40, 50, 63, 80, 100];

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

  // NOM-001-SEDE-2012 Art. 240-4(d) — maximum OCPD for small Cu conductors in conduit
  const SMALL_CONDUCTOR_MAX_OCPD = {
    '14 AWG': 15,
    '12 AWG': 20,
    '10 AWG': 30,
  };

  // Temperature derating factors for 75 °C conductors (NOM Tabla 310.15(B)(2)(a))
  const DERATING_TABLE = [
    { maxTemp: 10, factor: 1.20 },
    { maxTemp: 15, factor: 1.15 },
    { maxTemp: 20, factor: 1.11 },
    { maxTemp: 25, factor: 1.05 },
    { maxTemp: 30, factor: 1.00 },
    { maxTemp: 35, factor: 0.94 },
    { maxTemp: 40, factor: 0.88 },
    { maxTemp: 45, factor: 0.82 },
    { maxTemp: 50, factor: 0.75 },
    { maxTemp: 55, factor: 0.67 },
    { maxTemp: 60, factor: 0.58 },
  ];

  // Performance ratio — typical value for preliminary design (wiring + inverter + temp + soiling losses)
  // Must match the PR used in block 2 for module sizing (calc-bloque2.js)
  const PR = 0.75;

  // ── MPPT-group helper (mirrors bloque3; both live in separate IIFEs) ────────
  function distributeStrings(Np_per_inv, groups) {
    if (!groups || groups.length === 0) return [];
    let remaining = Np_per_inv;
    return groups.map(g => {
      const capacity       = g.mppt_count * g.max_strings_per_mppt;
      const assigned       = Math.min(capacity, remaining);
      remaining           -= assigned;
      const stringsPerMppt = assigned > 0 ? Math.ceil(assigned / g.mppt_count) : 0;
      return { group: g, stringsAssigned: assigned, stringsPerMppt };
    });
  }

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

  // NOM-001-SEDE-2012 Art. 240-4(d) — "small conductor" rule.
  // The conductor must satisfy both: (a) its ampacity ≥ I_required, and
  // (b) the OCPD protecting it does not exceed the ceiling set by 240-4(d)
  // for that gauge. If a candidate gauge fails (b), the next larger gauge is tried.
  // Returns { ocpd, awg, upsized } where upsized=true means the conductor was
  // forced larger than pure ampacity would require.
  function resolveCircuit(I_required, I_design) {
    const ocpd = nextOCPD(I_design);   // OCPD always sized to design current
    let firstValid = null;             // smallest gauge that alone satisfies ampacity

    for (const row of AWG_TABLE) {
      if (row.ampacity < I_required) continue;
      if (firstValid === null) firstValid = row.label;

      const ceiling = SMALL_CONDUCTOR_MAX_OCPD[row.label];
      // If this gauge has a 240-4(d) ceiling and the OCPD exceeds it, try next gauge
      if (ceiling !== undefined && typeof ocpd === 'number' && ocpd > ceiling) continue;

      return { ocpd, awg: row.label, upsized: row.label !== firstValid };
    }
    return { ocpd, awg: 'Mayor a 4/0 AWG (consultar)', upsized: false };
  }

  function setText(id, val) {
    const el = document.getElementById(id);
    if (el) el.textContent = val;
  }

  // ── Main populate function ─────────────────────────────────
  function populateBlock4() {
    const cs  = window.calcState;
    if (!cs || !cs.module || !cs.inverter) return;

    const mod        = cs.module;
    const inv        = cs.inverter;
    const Ns         = cs.Ns;
    const Np         = cs.Np;
    const N          = cs.N;
    const N_inv      = cs.N_inv      || 1;
    const Np_per_inv = cs.Np_per_inv || Np;

    const tmin = parseFloat(document.getElementById('tmin').value) || 25;
    const tmax = parseFloat(document.getElementById('tmax').value) || 25;
    const hsp  = parseFloat(document.getElementById('hsp').value)  || 0;
    const lat  = document.getElementById('latitud').value  || '—';
    const lng  = document.getElementById('longitud').value || '—';
    const consumo = parseFloat(document.getElementById('consumo_anual_kwh').value) || 0;
    const modArea = mod.length_m * mod.width_m;

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
    const P_cold_total   = N * P_cold_per;    // W at Tmin (whole array)
    const P_cold_per_inv = Np_per_inv * Ns * P_cold_per; // W per inverter at Tmin
    const P_stc_W        = cs.P_stc_kW * 1000;
    const dc_ac          = P_stc_W / (N_inv * inv.nominal_ac_power);

    // ── Site & Design ──────────────────────────────────────
    setText('s4-location',    `${parseFloat(lat).toFixed(2)}° N, ${parseFloat(lng).toFixed(2)}° O`);
    setText('s4-consumption', consumo.toLocaleString('es-MX') + ' kWh/año');
    setText('s4-hsp',         hsp.toFixed(2) + ' h/día');
    setText('s4-temps',       `${tmin.toFixed(1)} °C / ${tmax.toFixed(1)} °C`);

    // ── PV Array ───────────────────────────────────────────
    setText('s4-module-name',    `${mod.manufacturer} — ${mod.model}`);
    setText('s4-module-power',   mod.pmax_stc + ' Wp');
    const n_rem = N % Ns;
    if (n_rem === 0) {
      setText('s4-total-modules', `${N} (${Ns} mód/str × ${Np} strings)`);
    } else {
      const n_full = Math.floor(N / Ns);
      setText('s4-total-modules',
        `${N} (${n_full} string${n_full > 1 ? 's' : ''} × ${Ns} mód + 1 string × ${n_rem} mód — string corto)`);
    }
    setText('s4-array-power',    cs.P_stc_kW.toFixed(2) + ' kWp');
    setText('s4-vmpp-array',     `${Ns} × ${mod.vmpp_stc} V = ${Vmpp_nom.toFixed(1)} V`);
    setText('s4-voc-array',      `${Ns} × ${mod.voc_stc} V = ${(Ns * mod.voc_stc).toFixed(1)} V`);
    setText('s4-module-area',    `${N} × ${(modArea).toFixed(2)} m² = ${(N * modArea).toFixed(2)} m²`);

    // ── Inverter ───────────────────────────────────────────
    setText('s4-inverter-name',
      N_inv > 1
        ? `${N_inv} × ${inv.manufacturer} — ${inv.model} (${(N_inv * inv.nominal_ac_power / 1000).toFixed(2)} kW AC total)`
        : `${inv.manufacturer} — ${inv.model}`);
    setText('s4-inverter-power',
      N_inv > 1
        ? `${N_inv} × ${inv.nominal_ac_power} W = ${N_inv * inv.nominal_ac_power} W`
        : inv.nominal_ac_power + ' W');
    setText('s4-mppt-range',      `${inv.mppt_voltage_min} – ${inv.mppt_voltage_max} V`);
    const invGroups4 = inv.mppt_groups || [];
    setText('s4-inverter-imax',
      invGroups4.length > 1
        ? invGroups4.map(g => g.group_label + ': ' + g.max_short_circuit_current + ' A').join(' / ')
        : inv.max_short_circuit_current + ' A');
    setText('s4-startup-voltage', inv.startup_voltage + ' V');
    setText('s4-ac-voltage',      inv.ac_voltage_nominal + ' V');

    // ── Compatibility checks ───────────────────────────────
    const vocPass      = Voc_cold     <= inv.max_dc_voltage;
    const vmppHotPass  = Vmpp_hot     >= inv.mppt_voltage_min;
    const startupPass  = Vmpp_hot     >= inv.startup_voltage;
    const vmppColdPass = Vmpp_cold    <= inv.mppt_voltage_max;
    const pDcPass      = P_cold_per_inv <= inv.pmax_dc_input;

    // Per-group capacity and current checks
    const groups4       = inv.mppt_groups || [];
    const totalCapacity = groups4.length > 0
      ? groups4.reduce((s, g) => s + g.mppt_count * g.max_strings_per_mppt, 0)
      : inv.mppt_count;
    const capacityPass = Np_per_inv <= totalCapacity;
    const groupLoads   = distributeStrings(Np_per_inv, groups4);
    const allGroupIMpptPass = groupLoads.every(
      gl => gl.stringsPerMppt === 0 || gl.stringsPerMppt * mod.imp_stc * 1.25 <= gl.group.max_input_current
    );
    const allGroupIScPass = groupLoads.every(
      gl => gl.stringsPerMppt === 0 || gl.stringsPerMppt * mod.isc_stc * 1.25 <= gl.group.max_short_circuit_current
    );

    // hard = blocks continue in block 3; soft = warning only
    const groupCurrentChecks = groupLoads
      .filter(gl => gl.stringsAssigned > 0)
      .flatMap(gl => {
        const I_mp = (gl.stringsPerMppt * mod.imp_stc * 1.25).toFixed(2);
        const I_sc = (gl.stringsPerMppt * mod.isc_stc * 1.25).toFixed(2);
        const lim_mp = gl.group.max_input_current;
        const lim_sc = gl.group.max_short_circuit_current;
        return [
          {
            label:  `Grupo ${gl.group.group_label}: I MPPT ≤ Imáx (${gl.stringsPerMppt} str × Imp ×1.25)`,
            detail: `${I_mp} A ≤ ${lim_mp} A`,
            pass:   parseFloat(I_mp) <= lim_mp, hard: true,
          },
          {
            label:  `Grupo ${gl.group.group_label}: Isc MPPT ≤ Iscmáx (${gl.stringsPerMppt} str × Isc ×1.25)`,
            detail: `${I_sc} A ≤ ${lim_sc} A`,
            pass:   parseFloat(I_sc) <= lim_sc, hard: true,
          },
        ];
      });

    const checks = [
      {
        label: N_inv > 1
          ? `Strings/inv (${Np_per_inv}) ≤ Capacidad total (${Np} totales ÷ ${N_inv} inv)`
          : 'Strings ≤ Capacidad total del inversor',
        detail: `${Np_per_inv} strings/inv ≤ ${totalCapacity} (MPPT × str máx/MPPT)`,
        pass:   capacityPass, hard: true,
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
      ...groupCurrentChecks,
      {
        label:  N_inv > 1
          ? `P por inversor en frío ≤ Entrada DC máx. (${(P_cold_total/1000).toFixed(2)} kW total ÷ ${N_inv})`
          : 'P arreglo en frío ≤ Entrada DC máx.',
        detail: `${(P_cold_per_inv/1000).toFixed(2)} kW/inv (T_min=${tmin}°C) ≤ ${(inv.pmax_dc_input/1000).toFixed(2)} kW`,
        pass:   pDcPass, hard: true,
      },
    ];

    const anyHardFail = !capacityPass || !vocPass || !allGroupIMpptPass || !allGroupIScPass || !pDcPass;
    const anySoftFail = !vmppHotPass || !startupPass || !vmppColdPass;

    renderVerdictBanner(anyHardFail, anySoftFail);
    renderCompatTable(checks);

    // ── DC/AC ratio ────────────────────────────────────────
    let dcacLabel, dcacColor;
    if      (dc_ac < 0.80)  { dcacLabel = 'Arreglo insuficiente'; dcacColor = 'text-danger'; }
    else if (dc_ac < 1.00)  { dcacLabel = 'Subóptimo';            dcacColor = 'text-warning'; }
    else if (dc_ac <= 1.25) { dcacLabel = 'Conservador';          dcacColor = 'text-success'; }
    else if (dc_ac <= 1.50) { dcacLabel = 'Óptimo';               dcacColor = 'text-success font-weight-bold'; }
    else                    { dcacLabel = 'Sobredimensionado';     dcacColor = 'text-danger'; }

    const dcacEl = document.getElementById('s4-dcac-value');
    const dcacLabelEl = document.getElementById('s4-dcac-label');
    if (dcacEl)      { dcacEl.textContent = dc_ac.toFixed(2); dcacEl.className = 'font-weight-bold small mb-0 ' + dcacColor; }
    if (dcacLabelEl) { dcacLabelEl.textContent = dcacLabel;   dcacLabelEl.className = 'small font-weight-bold mb-0 ' + dcacColor; }

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
      section.classList.add('d-none');
      return;
    }

    section.classList.remove('d-none');

    // Pre-compute production per month
    monthlyProduction = monthly.map((row, i) =>
      P_stc_kW * row.ghi * MONTH_DAYS[i] * PR
    );

    // Build rows — consumption column contains an editable input
    tbody.innerHTML = monthly.map((row, i) => {
      const dias  = MONTH_DAYS[i];
      const prod  = monthlyProduction[i];
      const rowBg = i % 2 === 0 ? '' : 'table-light';
      return `
        <tr class="${rowBg}" data-month="${i}">
          <td>${MONTH_NAMES[i]}</td>
          <td class="text-right">${row.ghi.toFixed(2)}</td>
          <td class="text-right">${dias}</td>
          <td class="text-right font-weight-bold">${Math.round(prod)}</td>
          <td class="cons-col d-none text-right">
            <input type="number" min="0" step="1"
              id="cons-input-${i}"
              class="form-control form-control-sm text-right"
              placeholder="—"/>
          </td>
          <td class="cons-col d-none text-right font-weight-bold"
              id="bal-${i}">—</td>
        </tr>`;
    }).join('');

    // Totals row
    const totalProd = monthlyProduction.reduce((a, b) => a + b, 0);
    tfoot.innerHTML = `
      <tr>
        <td>Total anual</td>
        <td class="text-right">—</td>
        <td class="text-right">365</td>
        <td class="text-right" id="s4-total-prod">${Math.round(totalProd)}</td>
        <td class="cons-col d-none text-right" id="s4-total-cons">—</td>
        <td class="cons-col d-none text-right" id="s4-total-bal">—</td>
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
        b.classList.toggle('btn-primary',  active);
        b.classList.toggle('btn-default',  !active);
      });

      document.querySelectorAll('.cons-col').forEach(el => {
        el.classList.toggle('d-none', !showConsumption);
      });
    });
  });

  // ── Compat table renderer ──────────────────────────────────
  function renderCompatTable(checks) {
    const container = document.getElementById('s4-compat-table');
    if (!container) return;
    container.innerHTML = checks.map(c => {
      const bg   = c.pass ? 'list-group-item-success' : c.hard ? 'list-group-item-danger' : 'list-group-item-warning';
      const icon = c.pass ? '<i class="fas fa-check"></i>' : c.hard ? '<i class="fas fa-times"></i>' : '<i class="fas fa-exclamation-triangle"></i>';
      return `
        <div class="list-group-item ${bg} d-flex align-items-center justify-content-between small">
          <div>
            <span class="font-weight-bold">${c.label}</span>
            <span class="text-muted ml-2">${c.detail}</span>
          </div>
          <span class="ml-3">${icon}</span>
        </div>`;
    }).join('');
  }

  // ── Verdict banner ─────────────────────────────────────────
  function renderVerdictBanner(anyHardFail, anySoftFail) {
    const el = document.getElementById('verdict-banner');
    if (!el) return;
    if (anyHardFail) {
      el.className = 'alert alert-danger d-flex align-items-center mb-0';
      el.innerHTML = '<i class="fas fa-times-circle mr-2"></i> Incompatibilidad crítica detectada — revisa los parámetros marcados en rojo';
    } else if (anySoftFail) {
      el.className = 'alert alert-warning d-flex align-items-center mb-0';
      el.innerHTML = '<i class="fas fa-exclamation-triangle mr-2"></i> Compatible con advertencias — revisa los parámetros marcados en amarillo';
    } else {
      el.className = 'alert alert-success d-flex align-items-center mb-0';
      el.innerHTML = '<i class="fas fa-check-circle mr-2"></i> Sistema compatible — todos los parámetros dentro de especificación';
    }
  }

  function nextGpvFuse(current) {
    return GPV_FUSE_SIZES.find(s => s >= current) || Math.ceil(current);
  }

  /**
   * Breaks groupLoads into distinct per-MPPT scenarios by strings-per-MPPT count,
   * applying the temperature derating factor to conductor sizing (not OCPD/fuse).
   */
  function getDcScenarios(mod, groupLoads, factor) {
    const map = new Map();
    const Isc = mod.isc_stc;

    groupLoads.forEach(gl => {
      if (gl.stringsAssigned === 0) return;
      const total = gl.group.mppt_count;
      const rem   = gl.stringsAssigned % total;
      const ceil  = gl.stringsPerMppt;
      const floor = Math.floor(gl.stringsAssigned / total);

      function add(strCount, count, suffix) {
        if (strCount <= 0 || count <= 0) return;
        if (!map.has(strCount)) map.set(strCount, { mpptCount: 0, labels: [] });
        const entry = map.get(strCount);
        entry.mpptCount += count;
        entry.labels.push(gl.group.group_label + (suffix ? ' ' + suffix : ''));
      }

      if (rem === 0) {
        add(ceil, total, '');
      } else {
        add(ceil,  rem,         total > 1 ? `(${rem}/${total} MPPT)` : '');
        add(floor, total - rem, total > 1 ? `(${total - rem}/${total} MPPT)` : '');
      }
    });

    return Array.from(map.entries())
      .sort((a, b) => b[0] - a[0])   // descending: parallel-heavy scenarios first
      .map(([strPerMppt, { mpptCount, labels }]) => {
        const needsFuse = strPerMppt >= 2;
        const fuseMinA  = Isc * 1.56;
        const fuseStdA  = needsFuse ? nextGpvFuse(fuseMinA) : null;
        // Conductor sized to derated requirement; OCPD/fuse sized to design current (not derated)
        const strCircuit  = resolveCircuit(Isc * 1.25 / factor, Isc * 1.56);
        const mpptCircuit = resolveCircuit(strPerMppt * Isc * 1.25 / factor, strPerMppt * Isc * 1.56);
        return { strPerMppt, mpptCount, labels, needsFuse, fuseMinA, fuseStdA, strCircuit, mpptCircuit, Isc };
      });
  }

  /** Renders DC protection scenario cards into #prot-dc-scenarios. */
  function renderDcProtection(scenarios, factor) {
    const container = document.getElementById('prot-dc-scenarios');
    if (!container) return;
    container.innerHTML = '';

    if (scenarios.length === 0) {
      container.innerHTML = '<div class="col-12"><p class="text-muted small">Sin strings asignados.</p></div>';
      return;
    }

    const fmtOCPD = val => typeof val === 'number' ? val + ' A' : val;
    const row = (label, value, upsized) => `
      <div class="d-flex justify-content-between small mb-1">
        <span class="text-muted">${label}</span>
        <strong${upsized ? ' class="text-warning"' : ''}>${value}</strong>
      </div>`;
    const deratingNote = factor !== 1.0
      ? `<small class="text-info d-block mb-1"><i class="fas fa-thermometer-half mr-1"></i>Factor corrección temp.: ${factor} aplicado al conductor</small>`
      : '';

    scenarios.forEach(sc => {
      const cardColor  = sc.needsFuse ? 'card-warning' : 'card-success';
      const badgeColor = sc.needsFuse ? 'badge-warning' : 'badge-success';
      const badgeText  = sc.needsFuse ? 'Fusible requerido' : 'Sin fusible de cadena';
      const mpptLine   = sc.mpptCount + ' entrada' + (sc.mpptCount > 1 ? 's' : '') + ' MPPT';
      const groupLine  = sc.labels.filter(Boolean).join(', ');

      let bodyHtml = deratingNote;

      if (sc.needsFuse) {
        bodyHtml += row(
          `Fusible cadena gPV <small class="text-muted">(I<sub>sc</sub> ×1.56 = ${sc.fuseMinA.toFixed(1)} A)</small>:`,
          sc.fuseStdA + ' A', false
        );
        bodyHtml += row(
          `Cable cadena Cu 75°C <small class="text-muted">(I<sub>sc</sub> ×1.25${factor !== 1.0 ? ' ÷' + factor : ''} = ${(sc.Isc * 1.25 / factor).toFixed(1)} A)</small>:`,
          sc.strCircuit.awg, sc.strCircuit.upsized
        );
        bodyHtml += row(
          `Cable entrada MPPT <small class="text-muted">(${sc.strPerMppt}×I<sub>sc</sub> ×1.25${factor !== 1.0 ? ' ÷' + factor : ''} = ${(sc.strPerMppt * sc.Isc * 1.25 / factor).toFixed(1)} A)</small>:`,
          sc.mpptCircuit.awg, sc.mpptCircuit.upsized
        );
        bodyHtml += row(
          `Protección MPPT <small class="text-muted">(${sc.strPerMppt}×I<sub>sc</sub> ×1.56 = ${(sc.strPerMppt * sc.Isc * 1.56).toFixed(1)} A)</small>:`,
          fmtOCPD(sc.mpptCircuit.ocpd), false
        );
        if (sc.strCircuit.upsized || sc.mpptCircuit.upsized) {
          bodyHtml += `<small class="text-warning d-block mt-1"><i class="fas fa-exclamation-triangle mr-1"></i>Conductor aumentado por Art. 240-4(d)</small>`;
        }
      } else {
        bodyHtml += `<div class="small mb-2 text-success"><i class="fas fa-check-circle mr-1"></i>String único &mdash; sin corriente inversa posible</div>`;
        bodyHtml += row(
          `Cable DC Cu 75°C <small class="text-muted">(I<sub>sc</sub> ×1.25${factor !== 1.0 ? ' ÷' + factor : ''} = ${(sc.Isc * 1.25 / factor).toFixed(1)} A)</small>:`,
          sc.strCircuit.awg, sc.strCircuit.upsized
        );
        bodyHtml += row(
          `Protección CC <small class="text-muted">(I<sub>sc</sub> ×1.56 = ${(sc.Isc * 1.56).toFixed(1)} A)</small>:`,
          fmtOCPD(sc.strCircuit.ocpd), false
        );
        if (sc.strCircuit.upsized) {
          bodyHtml += `<small class="text-warning d-block mt-1"><i class="fas fa-exclamation-triangle mr-1"></i>Conductor aumentado por Art. 240-4(d)</small>`;
        }
      }

      const col = document.createElement('div');
      col.className = 'col-sm-6 col-lg-4 mb-2';
      col.innerHTML = `
        <div class="card card-outline ${cardColor} h-100">
          <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <div>
                <p class="font-weight-bold mb-0 small">${sc.strPerMppt} string${sc.strPerMppt > 1 ? 's' : ''} por MPPT</p>
                <small class="text-muted">${mpptLine}${groupLine ? ' &mdash; ' + groupLine : ''}</small>
              </div>
              <span class="badge ${badgeColor} ml-1" style="white-space:nowrap;">${badgeText}</span>
            </div>
            ${bodyHtml}
          </div>
        </div>`;
      container.appendChild(col);
    });
  }

  // ── Protection calculator ──────────────────────────────────
  function computeProtection(mod, inv, tmax) {
    const factor = deratingOn ? getDeratingFactor(tmax) : 1.0;

    // DC: derive groupLoads from calcState and render per-MPPT scenario cards
    const Np_per_inv = (window.calcState && window.calcState.Np_per_inv) || (window.calcState && window.calcState.Np) || 1;
    const groups     = inv.mppt_groups || [];
    const groupLoads = distributeStrings(Np_per_inv, groups);
    renderDcProtection(getDcScenarios(mod, groupLoads, factor), factor);

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
    setText('prot-ac-idesign', I_ac_design.toFixed(2) + ' A');
    // Same as DC: OCPD on design current, conductor on derated requirement.
    setText('prot-ac-derated', deratingOn ? `${I_ac_design.toFixed(2)} A ÷ ${factor} = ${I_ac_required.toFixed(2)} A requeridos en tabla` : '—');
    const acDeratedRow = document.getElementById('prot-ac-derated-row');
    if (acDeratedRow) acDeratedRow.classList.toggle('d-none', !deratingOn);
    const acCircuit = resolveCircuit(I_ac_required, I_ac_design);
    setText('prot-ac-ocpd', fmtOCPD(acCircuit.ocpd));
    setText('prot-ac-awg',  acCircuit.awg);
    const acSmallRow = document.getElementById('prot-ac-small-cond-row');
    if (acSmallRow) acSmallRow.classList.toggle('d-none', !acCircuit.upsized);
  }

  // ── Derating toggle ────────────────────────────────────────
  document.querySelectorAll('.derating-btn').forEach(btn => {
    btn.addEventListener('click', function () {
      deratingOn = this.dataset.derating === 'on';

      document.querySelectorAll('.derating-btn').forEach(b => {
        const active = b.dataset.derating === (deratingOn ? 'on' : 'off');
        b.classList.toggle('btn-primary',  active);
        b.classList.toggle('btn-default',  !active);
      });

      const hint = document.getElementById('derating-hint');
      if (deratingOn && window.calcState) {
        const tmax  = parseFloat(document.getElementById('tmax').value) || 25;
        const f     = getDeratingFactor(tmax);
        hint.textContent = `Tamb máx = ${tmax.toFixed(1)} °C → factor ${f} (Tabla 310.15(B)(2)(a), conductores a 75 °C)`;
hint.classList.remove('d-none');
    } else {
      hint.classList.add('d-none');
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
    document.getElementById('btn-derating-off').classList.add('btn-primary');
    document.getElementById('btn-derating-off').classList.remove('btn-default');
    document.getElementById('btn-derating-on').classList.remove('btn-primary');
    document.getElementById('btn-derating-on').classList.add('btn-default');
    document.getElementById('derating-hint').classList.add('d-none');
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
    if (off) { off.classList.add('btn-primary'); off.classList.remove('btn-default'); }
    if (on)  { on.classList.remove('btn-primary'); on.classList.add('btn-default'); }
    const hint = document.getElementById('derating-hint');
    if (hint) hint.classList.add('d-none');
    // Reset consumption toggle
    const cOff = document.getElementById('btn-cons-off');
    const cOn  = document.getElementById('btn-cons-on');
    if (cOff) { cOff.classList.add('btn-primary'); cOff.classList.remove('btn-default'); }
    if (cOn)  { cOn.classList.remove('btn-primary'); cOn.classList.add('btn-default'); }
    document.querySelectorAll('.cons-col').forEach(el => el.classList.add('d-none'));
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
    const N_inv      = cs.N_inv      || 1;
    const Np_per_inv = cs.Np_per_inv || Np;
    const n_rem = N % Ns;

    const tmin    = parseFloat(document.getElementById('tmin').value)              || 25;
    const tmax    = parseFloat(document.getElementById('tmax').value)              || 25;
    const hsp     = parseFloat(document.getElementById('hsp').value)               || 0;
    const lat     = parseFloat(document.getElementById('latitud').value)           || 0;
    const lng     = parseFloat(document.getElementById('longitud').value)          || 0;
    const consumo = parseFloat(document.getElementById('consumo_anual_kwh').value) || 0;
    const arrArea  = mod.length_m * mod.width_m * N;


    // Re-derive electrical values (same as populateBlock4)
    const betaVoc       = mod.temp_coeff_voc  / 100;
    const gammaPmax     = mod.temp_coeff_pmax / 100;
    const Voc_cold      = Ns * mod.voc_stc  * (1 + betaVoc   * (tmin - 25));
    const Vmpp_hot      = Ns * mod.vmpp_stc * (1 + betaVoc   * (tmax - 25));
    const Vmpp_cold     = Ns * mod.vmpp_stc * (1 + betaVoc   * (tmin - 25));
    const P_cold_per    = mod.pmax_stc * (1 + gammaPmax * (tmin - 25));
    const Isc_array     = Np * mod.isc_stc;
    const P_cold_total  = N * P_cold_per;
    const P_cold_per_inv = Np_per_inv * Ns * P_cold_per;
    const dc_ac         = (cs.P_stc_kW * 1000) / (N_inv * inv.nominal_ac_power);

    // Checks (per-group, mirrors populateBlock4)
    const expGroups      = inv.mppt_groups || [];
    const expTotalCap    = expGroups.length > 0
      ? expGroups.reduce((s, g) => s + g.mppt_count * g.max_strings_per_mppt, 0)
      : inv.mppt_count;
    const expCapacityPass = Np_per_inv <= expTotalCap;
    const expGroupLoads   = distributeStrings(Np_per_inv, expGroups);
    const expGroupCurrentChecks = expGroupLoads
      .filter(gl => gl.stringsAssigned > 0)
      .flatMap(gl => {
        const I_mp = (gl.stringsPerMppt * mod.imp_stc * 1.25).toFixed(2);
        const I_sc = (gl.stringsPerMppt * mod.isc_stc * 1.25).toFixed(2);
        const lim_mp = gl.group.max_input_current;
        const lim_sc = gl.group.max_short_circuit_current;
        return [
          {
            label:  `Grupo ${gl.group.group_label}: I MPPT ≤ Imáx (${gl.stringsPerMppt} str × Imp ×1.25)`,
            detail: `${I_mp} A ≤ ${lim_mp} A`,
            pass:   parseFloat(I_mp) <= lim_mp, hard: true,
          },
          {
            label:  `Grupo ${gl.group.group_label}: Isc MPPT ≤ Iscmáx (${gl.stringsPerMppt} str × Isc ×1.25)`,
            detail: `${I_sc} A ≤ ${lim_sc} A`,
            pass:   parseFloat(I_sc) <= lim_sc, hard: true,
          },
        ];
      });

    const vocPass      = Voc_cold  <= inv.max_dc_voltage;
    const vmppHotPass  = Vmpp_hot  >= inv.mppt_voltage_min;
    const startupPass  = Vmpp_hot  >= inv.startup_voltage;
    const vmppColdPass = Vmpp_cold <= inv.mppt_voltage_max;
    const pDcPass      = P_cold_per_inv <= inv.pmax_dc_input;

    const checks = [
      { label: N_inv > 1
          ? `Strings/inv (${Np_per_inv}) ≤ Capacidad total (${Np} totales ÷ ${N_inv} inv)`
          : 'Strings ≤ Capacidad total del inversor',
        detail: `${Np_per_inv} strings/inv ≤ ${expTotalCap} (MPPT × str máx/MPPT)`, pass: expCapacityPass, hard: true  },
      { label: 'Voc en frío ≤ Tensión máx. DC',             detail: `${Voc_cold.toFixed(1)} V ≤ ${inv.max_dc_voltage} V`,                                                     pass: vocPass,           hard: true  },
      { label: 'Vmpp en calor ≥ Límite inferior MPPT',      detail: `${Vmpp_hot.toFixed(1)} V ≥ ${inv.mppt_voltage_min} V`,                                                   pass: vmppHotPass,       hard: false },
      { label: 'Vmpp en calor ≥ Tensión de arranque',       detail: `${Vmpp_hot.toFixed(1)} V ≥ ${inv.startup_voltage} V`,                                                    pass: startupPass,       hard: false },
      { label: 'Vmpp en frío ≤ Límite superior MPPT',       detail: `${Vmpp_cold.toFixed(1)} V ≤ ${inv.mppt_voltage_max} V`,                                                  pass: vmppColdPass,      hard: false },
      ...expGroupCurrentChecks,
      { label: N_inv > 1
          ? `P por inversor en frío ≤ Entrada DC máx. (${(P_cold_total/1000).toFixed(2)} kW total ÷ ${N_inv})`
          : 'P arreglo en frío ≤ Entrada DC máx.',
        detail: `${(P_cold_per_inv/1000).toFixed(2)} kW/inv (T_min=${tmin}°C) ≤ ${(inv.pmax_dc_input/1000).toFixed(2)} kW`, pass: pDcPass,           hard: true  },
    ];

    // Energy
    const E_year   = cs.P_stc_kW * hsp * 365 * PR;
    const coverage = consumo > 0 ? Math.min((E_year / consumo) * 100, 999) : 0;

    // Protection
    const factor         = deratingOn ? getDeratingFactor(tmax) : 1.0;
    const expDcScenarios = getDcScenarios(mod, expGroupLoads, factor);
    const isThreePhase   = inv.phase_type === 'Three Phase';
    const phaseDiv       = isThreePhase ? (Math.sqrt(3) * inv.ac_voltage_nominal) : inv.ac_voltage_nominal;
    const I_ac_base      = inv.nominal_ac_power / phaseDiv;
    const I_ac_design    = I_ac_base * 1.25;
    const I_ac_required  = I_ac_design / factor;
    const acCircuit_exp  = resolveCircuit(I_ac_required, I_ac_design);

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
      array:   { Ns, Np, N_inv, Np_per_inv, N, P_stc_kW: cs.P_stc_kW, Voc_cold, Vmpp_hot, Vmpp_cold, arrArea, n_rem },
      inverter:{ ...inv },
      checks,
      energy:  { E_year, coverage, PR, dc_ac },
      protection: {
        derating_on:     deratingOn,
        derating_factor: factor,
        tmax,
        dc_scenarios: expDcScenarios.map(sc => ({
          strPerMppt:  sc.strPerMppt,
          mpptCount:   sc.mpptCount,
          needsFuse:   sc.needsFuse,
          fuseStdA:    sc.fuseStdA,
          strCircuit:  { OCPD: fmtOCPD(sc.strCircuit.ocpd),  AWG: sc.strCircuit.awg,  upsized: sc.strCircuit.upsized  },
          mpptCircuit: { OCPD: fmtOCPD(sc.mpptCircuit.ocpd), AWG: sc.mpptCircuit.awg, upsized: sc.mpptCircuit.upsized },
        })),
        ac: { phase_type: inv.phase_type, I_base: I_ac_base, I_design: I_ac_design,
              I_required: I_ac_required,
              OCPD: fmtOCPD(acCircuit_exp.ocpd),
              AWG:  acCircuit_exp.awg,
              small_conductor_upsized: acCircuit_exp.upsized },
      },
      monthly,
    };
  }

  async function handleExport() {
    const btn      = document.getElementById('btn-excel-export');
    const origHTML = btn.innerHTML;
    btn.disabled   = true;

    // Create spinner via DOM APIs. Insert small keyframes if missing.
    if (!document.getElementById('calc-block4-spinner-style')) {
      const style = document.createElement('style');
      style.id = 'calc-block4-spinner-style';
      style.textContent = '@keyframes calcBlock4Spin{100%{transform:rotate(360deg)}}';
      document.head.appendChild(style);
    }

    const svgNS = 'http://www.w3.org/2000/svg';
    const spinner = document.createElementNS(svgNS, 'svg');
    spinner.setAttribute('viewBox', '0 0 24 24');
    spinner.setAttribute('width', '14');
    spinner.setAttribute('height', '14');
    spinner.setAttribute('aria-hidden', 'true');
    spinner.style.verticalAlign = 'middle';
    spinner.style.marginRight = '6px';
    spinner.style.animation = 'calcBlock4Spin 1s linear infinite';

    const circle = document.createElementNS(svgNS, 'circle');
    circle.setAttribute('cx', '12');
    circle.setAttribute('cy', '12');
    circle.setAttribute('r', '10');
    circle.setAttribute('stroke', 'currentColor');
    circle.setAttribute('stroke-width', '4');
    circle.setAttribute('fill', 'none');
    circle.setAttribute('opacity', '0.25');
    spinner.appendChild(circle);

    const path = document.createElementNS(svgNS, 'path');
    path.setAttribute('d', 'M4 12a8 8 0 018-8v8H4z');
    path.setAttribute('fill', 'currentColor');
    path.setAttribute('opacity', '0.75');
    spinner.appendChild(path);

    // Clear button content and append spinner + text node
    btn.textContent = '';
    btn.appendChild(spinner);
    btn.appendChild(document.createTextNode(' Generando…'));

    try {
      const payload = buildExportPayload();
      const res     = await fetch(`${BASE_URL}/api/export_excel.php`, {
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
