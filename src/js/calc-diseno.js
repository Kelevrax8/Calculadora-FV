// ============================================================
//  DISEÑO DEL SISTEMA – Módulo FV, cadena e inversor
//  Merged replacement for calc-bloque2.js + calc-bloque3.js
// ============================================================
(function () {

  // ── Lookup tables (NOM-001-SEDE-2012 / NEC 690) ────────────
  const OCPD_SIZES     = [15, 20, 25, 30, 35, 40, 45, 50, 60, 70, 80, 90, 100, 110, 125, 150, 175, 200];
  const GPV_FUSE_SIZES = [1, 2, 3, 4, 5, 6, 8, 10, 12, 15, 16, 20, 25, 30, 32, 35, 40, 50, 63, 80, 100];

  // Ampacity for 75 °C Cu conductors in conduit (NOM Tabla 310.15(B)(16))
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

  // NOM-001-SEDE-2012 Art. 240-4(d): max OCPD for small Cu conductors
  const SMALL_CONDUCTOR_MAX_OCPD = { '14 AWG': 15, '12 AWG': 20, '10 AWG': 30 };

  const NOM_FACTOR = 1.25;
  const STC_TEMP   = 25;

  const MONTH_NAMES = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                       'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
  const MONTH_DAYS  = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

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

  // ── State ───────────────────────────────────────────────────
  let allModules   = [];
  let allInverters = [];
  let dataLoaded   = false;
  let selectedModule   = null;
  let selectedInverter = null;
  let currentNs   = 1;
  let currentNInv = 1;
  let enMonthlyProduction = [];
  let enShowConsumption   = false;
  let deratingOn          = false;
  let N_suggested         = 1;
  let currentPR           = 1;

  // Per-module derived temperature values (set in selectModule)
  let N_total, betaVoc, Voc_cold_per, Vmpp_hot_per, Vmpp_cold_per, P_cold_per;

  // ── DOM refs ────────────────────────────────────────────────
  const loadingEl       = document.getElementById('diseno-loading');
  const errorEl         = document.getElementById('diseno-error');
  const moduleSection   = document.getElementById('module-section');
  const stringSection   = document.getElementById('string-config-section');
  const inverterSection = document.getElementById('inverter-section');
  const prelimResults   = document.getElementById('prelim-results');
  const checksPanel     = document.getElementById('elect-checks-panel');

  // ── Circuit helpers ─────────────────────────────────────────
  function nextGpvFuse(current) {
    return GPV_FUSE_SIZES.find(s => s >= current) || Math.ceil(current);
  }

  function nextOCPD(iDesign) {
    const found = OCPD_SIZES.find(s => s >= iDesign);
    return found ?? `>${OCPD_SIZES[OCPD_SIZES.length - 1]} A (consultar)`;
  }

  function resolveCircuit(I_required, I_design) {
    const ocpd = nextOCPD(I_design);
    let firstValid = null;
    for (const row of AWG_TABLE) {
      if (row.ampacity < I_required) continue;
      if (firstValid === null) firstValid = row.label;
      const ceiling = SMALL_CONDUCTOR_MAX_OCPD[row.label];
      if (ceiling !== undefined && typeof ocpd === 'number' && ocpd > ceiling) continue;
      return { ocpd, awg: row.label, upsized: row.label !== firstValid };
    }
    return { ocpd, awg: 'Mayor a 4/0 AWG (consultar)', upsized: false };
  }

  // ── MPPT-group helpers ──────────────────────────────────────
  // Returns how many parallel strings can feed a single MPPT input given a
  // module, accounting for both the hardware cap (if any) and current limits.
  function effectiveStringsPerMppt(group, mod) {
    const hwCap  = (group.max_strings_per_mppt != null) ? group.max_strings_per_mppt : Infinity;
    const impCap = Math.floor(group.max_input_current        / mod.imp_stc);
    const iscCap = Math.floor(group.max_short_circuit_current / mod.isc_stc);
    return Math.min(hwCap, impCap, iscCap);
  }

  function totalCapacityPerInv(inv, mod) {
    const groups = inv.mppt_groups || [];
    if (groups.length === 0) return inv.mppt_count || 1;
    const fromGroups = groups.reduce((s, g) => s + g.mppt_count * effectiveStringsPerMppt(g, mod), 0);
    const totalCap   = (inv.max_total_strings != null) ? inv.max_total_strings : Infinity;
    return Math.min(fromGroups, totalCap);
  }

  function distributeStrings(Np_per_inv, groups, mod) {
    if (!groups || groups.length === 0) return [];
    let remaining = Np_per_inv;
    return groups.map(g => {
      const effPerMppt = effectiveStringsPerMppt(g, mod);
      const capacity   = g.mppt_count * effPerMppt;
      const assigned   = Math.min(capacity, remaining);
      remaining       -= assigned;
      const stringsPerMppt = assigned > 0 ? Math.ceil(assigned / g.mppt_count) : 0;
      return { group: g, stringsAssigned: assigned, stringsPerMppt, effectiveCap: effPerMppt };
    });
  }

  // ── Phase label helpers ─────────────────────────────────────
  function mapPhaseLabel(phaseType) {
    return phaseType === 'Single Phase' ? 'Monofásico'
      : phaseType === 'Three Phase'  ? 'Trifásico'
      : phaseType === 'Split Phase'  ? 'Bifásico'
      : phaseType;
  }

  function mapPhaseBadgeClass(phaseType) {
    return phaseType === 'Three Phase' ? 'badge-secondary' : 'badge-info';
  }

  // ── DC/AC classification ────────────────────────────────────
  function classifyDcAcLabel(dc_ac) {
    if (dc_ac < 0.80)  return { label: 'Arreglo insuficiente', cssClass: 'text-danger' };
    if (dc_ac < 1.00)  return { label: 'Subóptimo',            cssClass: 'text-warning' };
    if (dc_ac <= 1.25) return { label: 'Conservador',          cssClass: 'text-success' };
    if (dc_ac <= 1.50) return { label: 'Óptimo',               cssClass: 'text-success font-weight-bold' };
    return               { label: 'Sobredimensionado',         cssClass: 'text-danger' };
  }

  // ── Entry point (called by showStep(2)) ─────────────────────
  window.loadDisenoBlock = async function () {
    if (dataLoaded) return;
    try {
      const [modRes, invRes] = await Promise.all([
        fetch(`${BASE_URL}/api/calculadora.php?action=get_pv_modules`),
        fetch(`${BASE_URL}/api/calculadora.php?action=get_inverters`),
      ]);
      const [modData, invData] = await Promise.all([modRes.json(), invRes.json()]);
      if (!modRes.ok || modData.error) throw new Error(modData.error || 'Error al cargar módulos');
      if (!invRes.ok || invData.error) throw new Error(invData.error || 'Error al cargar inversores');
      allModules   = modData;
      allInverters = invData;
      dataLoaded   = true;
      populateModuleDropdown();
      loadingEl.classList.add('d-none');
      moduleSection.classList.remove('d-none');
    } catch (err) {
      loadingEl.classList.add('d-none');
      errorEl.textContent = '⚠ No se pudo cargar el inventario: ' + err.message;
      errorEl.classList.remove('d-none');
    }
  };

  // ── Module dropdown ─────────────────────────────────────────
  function populateModuleDropdown() {
    const sel = document.getElementById('module-select');
    sel.innerHTML = '<option value="">— Selecciona un módulo —</option>';
    const byManuf = {};
    allModules.forEach(m => {
      (byManuf[m.manufacturer] = byManuf[m.manufacturer] || []).push(m);
    });
    Object.keys(byManuf).sort().forEach(manuf => {
      const grp = document.createElement('optgroup');
      grp.label = manuf;
      byManuf[manuf].forEach(m => {
        const opt = document.createElement('option');
        opt.value = m.id;
        opt.textContent = `${m.model}  (${m.pmax_stc} Wp · ${m.technology})`;
        grp.appendChild(opt);
      });
      sel.appendChild(grp);
    });
  }

  document.getElementById('module-select').addEventListener('change', function () {
    const id = parseInt(this.value);
    if (!id) { clearModuleSelection(); return; }
    const m = allModules.find(x => x.id === id);
    if (m) selectModule(m);
  });

  function clearModuleSelection() {
    selectedModule   = null;
    selectedInverter = null;
    document.getElementById('module-card-preview').classList.add('d-none');
    stringSection.classList.add('d-none');
    inverterSection.classList.add('d-none');
    prelimResults.classList.add('d-none');
    checksPanel.classList.add('d-none');
    if (window.calcState) {
      ['module', 'N', 'P_stc_kW', 'inverter', 'Ns', 'Np', 'N_inv', 'Np_per_inv']
        .forEach(k => delete window.calcState[k]);
    }
  }

  // Technology badge colours
  const TECH_COLOR = {
    'Monocrystalline': 'badge-primary',
    'Polycrystalline':  'badge-success',
    'Thin Film':        'badge-info',
    'Other':            'badge-warning',
  };

  function selectModule(m) {
    selectedModule   = m;
    selectedInverter = null;
    window.calcState = window.calcState || {};
    window.calcState.module = m;

    // ── Populate module preview card ──
    const area = m.length_m * m.width_m;
    const eta  = (m.pmax_stc / (1000 * area) * 100).toFixed(2);

    document.getElementById('mod-preview-manufacturer').textContent = m.manufacturer;
    document.getElementById('mod-preview-model').textContent        = m.model;
    const techBadge = document.getElementById('mod-preview-tech-badge');
    techBadge.textContent = m.technology;
    techBadge.className   = 'badge ' + (TECH_COLOR[m.technology] || 'badge-warning');
    document.getElementById('mod-preview-power').textContent  = m.pmax_stc + ' Wp';
    document.getElementById('mod-preview-voc').textContent   = m.voc_stc   + ' V';
    document.getElementById('mod-preview-isc').textContent   = m.isc_stc   + ' A';
    document.getElementById('mod-preview-vmpp').textContent  = m.vmpp_stc  + ' V';
    document.getElementById('mod-preview-imp').textContent   = m.imp_stc   + ' A';
    document.getElementById('mod-preview-bvoc').textContent  = m.temp_coeff_voc   + ' %/°C';
    document.getElementById('mod-preview-gp').textContent    = m.temp_coeff_pmax  + ' %/°C';
    document.getElementById('mod-preview-area').textContent  = area.toFixed(2) + ' m²';
    document.getElementById('mod-preview-eta').textContent   = eta + '%';
    document.getElementById('module-card-preview').classList.remove('d-none');

    // ── Preliminary sizing ──
    const consumo   = parseFloat(document.getElementById('consumo_anual_kwh').value) || 0;
    const cobertura = parseFloat(document.getElementById('cobertura_pct').value)     || 100;
    const hsp       = parseFloat(document.getElementById('hsp').value)                || 0;
    const tmax      = parseFloat(document.getElementById('tmax').value)                || STC_TEMP;
    const tmin      = parseFloat(document.getElementById('tmin').value)                || STC_TEMP;

    const E_dia_Wh  = (consumo * (cobertura / 100) / 365) * 1000;
    N_total         = hsp > 0 ? Math.ceil((E_dia_Wh / hsp) / m.pmax_stc) : 1;
    N_suggested     = N_total;
    const P_stc_kW  = (N_total * m.pmax_stc) / 1000;
    const gamma     = m.temp_coeff_pmax / 100;
    const P_calor_kW = (N_total * m.pmax_stc * (1 + gamma * (tmax - STC_TEMP))) / 1000;
    const pct_calor  = ((P_calor_kW / P_stc_kW) - 1) * 100;

    document.getElementById('res-n-modulos').textContent        = N_total;
    document.getElementById('res-p-arreglo-stc').textContent    = P_stc_kW.toFixed(2);
    document.getElementById('res-p-arreglo-calor').textContent  = P_calor_kW.toFixed(2) + ' kW';
    document.getElementById('res-p-calor-pct').textContent      =
      (pct_calor >= 0 ? '+' : '') + pct_calor.toFixed(1) + '% vs STC';

    // N stepper hint — reset to "unidades" since this is a fresh module selection
    document.getElementById('res-n-sugerido').textContent = N_suggested;
    document.getElementById('res-n-sugerido-hint').classList.add('d-none');
    document.getElementById('res-n-base-hint').classList.remove('d-none');
    document.getElementById('btn-n-dec').disabled    = N_total <= 1;
    document.getElementById('btn-n-dec-10').disabled = N_total <= 10;

    window.calcState.N        = N_total;
    window.calcState.P_stc_kW = P_stc_kW;
    prelimResults.classList.remove('d-none');

    // ── Per-module temperature metrics (for string voltage calculations) ──
    betaVoc       = m.temp_coeff_voc  / 100;
    Voc_cold_per  = m.voc_stc  * (1 + betaVoc             * (tmin - STC_TEMP));
    Vmpp_hot_per  = m.vmpp_stc * (1 + betaVoc             * (tmax - STC_TEMP));
    Vmpp_cold_per = m.vmpp_stc * (1 + betaVoc             * (tmin - STC_TEMP));
    P_cold_per    = m.pmax_stc * (1 + (m.temp_coeff_pmax / 100) * (tmin - STC_TEMP));

    // ── Init string config at N_total (clamped in refreshStringUI) ──
    currentNs   = N_total;
    currentNInv = 1;
    stringSection.classList.remove('d-none');
    refreshStringUI();

    // ── Populate inverter dropdown with compat indicators ──
    clearInverterSelection();
    document.getElementById('inverter-select').value = '';
    populateInverterDropdown();
    inverterSection.classList.remove('d-none');
    checksPanel.classList.add('d-none');
  }

  // ── Module count override ────────────────────────────────────
  function applyNewNTotal(n) {
    if (!selectedModule) return;
    const m    = selectedModule;
    N_total    = Math.max(1, n);
    const tmax = parseFloat(document.getElementById('tmax').value) || STC_TEMP;
    const gamma     = m.temp_coeff_pmax / 100;
    const P_stc_kW  = (N_total * m.pmax_stc) / 1000;
    const P_calor_kW = (N_total * m.pmax_stc * (1 + gamma * (tmax - STC_TEMP))) / 1000;
    const pct_calor  = ((P_calor_kW / P_stc_kW) - 1) * 100;

    document.getElementById('res-n-modulos').textContent       = N_total;
    document.getElementById('res-p-arreglo-stc').textContent   = P_stc_kW.toFixed(2);
    document.getElementById('res-p-arreglo-calor').textContent = P_calor_kW.toFixed(2) + ' kW';
    document.getElementById('res-p-calor-pct').textContent     =
      (pct_calor >= 0 ? '+' : '') + pct_calor.toFixed(1) + '% vs STC';

    const isDiff = N_total !== N_suggested;
    document.getElementById('res-n-sugerido-hint').classList.toggle('d-none', !isDiff);
    document.getElementById('res-n-base-hint').classList.toggle('d-none', isDiff);
    document.getElementById('btn-n-dec').disabled    = N_total <= 1;
    document.getElementById('btn-n-dec-10').disabled = N_total <= 10;

    window.calcState.N        = N_total;
    window.calcState.P_stc_kW = P_stc_kW;

    // Clamp Ns to the new N_total and refresh string config
    currentNs = Math.min(currentNs, N_total);
    refreshStringUI();

    if (selectedInverter) {
      computeChecks(selectedInverter);
    }
  }

  // ── String config ───────────────────────────────────────────
  function getNsBounds(inv) {
    if (inv) {
      const Ns_by_vdc  = Math.floor(inv.max_dc_voltage   / Voc_cold_per);
      const Ns_by_mppt = Math.floor(inv.mppt_voltage_max / Vmpp_cold_per);
      const Ns_max_raw = Math.min(Ns_by_vdc, Ns_by_mppt);
      return {
        Ns_min:        Math.max(1, Math.ceil(inv.startup_voltage / Vmpp_hot_per)),
        Ns_max:        Math.min(N_total, Ns_max_raw),
        Ns_min_reason: 'arranque',
        Ns_max_reason: (Ns_by_mppt <= Ns_by_vdc) ? 'ventana MPPT' : 'Vdc máx',
      };
    }
    const global_max = allInverters.length > 0
      ? Math.max(...allInverters.map(i => i.max_dc_voltage))
      : 1000;
    return {
      Ns_min:        1,
      Ns_max:        Math.min(N_total, Math.floor(global_max / Voc_cold_per)),
      Ns_min_reason: '',
      Ns_max_reason: 'Vdc máx global',
    };
  }

  function getStringMetrics(ns) {
    return {
      Np:        Math.ceil(N_total / ns),
      Voc_cold:  ns * Voc_cold_per,
      Vmpp_hot:  ns * Vmpp_hot_per,
      Vmpp_cold: ns * Vmpp_cold_per,
    };
  }

  function refreshStringUI() {
    const { Ns_min, Ns_max, Ns_min_reason, Ns_max_reason } = getNsBounds(selectedInverter);
    currentNs = Math.max(Ns_min, Math.min(currentNs, Ns_max));
    const { Np, Voc_cold, Vmpp_hot, Vmpp_cold } = getStringMetrics(currentNs);

    // ── Remainder string detection ──
    const n_rem      = N_total % currentNs;
    const n_full_str = Math.floor(N_total / currentNs);
    const remEl      = document.getElementById('str-remainder-warning');

    if (n_rem > 0) {
      document.getElementById('str-rem-breakdown').textContent =
        n_full_str + ' string' + (n_full_str > 1 ? 's' : '') + ' × ' + currentNs +
        ' mód  +  1 string × ' + n_rem + ' mód (string corto)';
      document.getElementById('str-rem-voc-cold').textContent  = (n_rem * Voc_cold_per).toFixed(1)  + ' V';
      document.getElementById('str-rem-vmpp-hot').textContent  = (n_rem * Vmpp_hot_per).toFixed(1)  + ' V';
      document.getElementById('str-rem-vmpp-cold').textContent = (n_rem * Vmpp_cold_per).toFixed(1) + ' V';

      const usefulDivisors = [];
      for (let d = Math.max(2, Ns_min); d <= Math.min(N_total - 1, Ns_max); d++) {
        if (N_total % d === 0) usefulDivisors.push(d);
      }
      const adviceEl = document.getElementById('str-rem-advice');
      const mpptNote = document.getElementById('str-rem-mppt-note');

      if (selectedInverter) {
        const remV           = (n_rem * Vmpp_hot_per).toFixed(1);
        const rem_mppt_ok    = (n_rem * Vmpp_hot_per) >= selectedInverter.mppt_voltage_min;
        const rem_startup_ok = (n_rem * Vmpp_hot_per) >= selectedInverter.startup_voltage;
        const hasParallel    = (selectedInverter.mppt_groups || []).some(g => g.max_strings_per_mppt == null || g.max_strings_per_mppt > 1);
        mpptNote.innerHTML =
          '<span class="d-block">' + (rem_mppt_ok ? '✓' : '✗') +
          ' MPPT mín (' + selectedInverter.mppt_voltage_min + ' V): ' +
          'Vmpp calor string corto = ' + remV + ' V' +
          (rem_mppt_ok ? ' — dentro del rango.' : ' — ese canal no podrá rastrear.') + '</span>' +
          '<span class="d-block mt-1">' + (rem_startup_ok ? '✓' : '⚠') +
          ' V arranque (' + selectedInverter.startup_voltage + ' V): ' + remV + ' V' +
          (rem_startup_ok ? ' — el string corto la supera.' : ' — podría no arrancar el inversor.') + '</span>' +
          (hasParallel
            ? '<span class="d-block mt-1">⚠ Conecta el string corto en una entrada MPPT dedicada para evitar pérdidas por desajuste de corriente.</span>'
            : '');
        mpptNote.className = 'mt-2 small font-weight-bold ' +
          (!rem_mppt_ok || !rem_startup_ok ? 'text-danger' : hasParallel ? 'text-warning' : 'text-success');
        mpptNote.classList.remove('d-none');
      } else {
        mpptNote.classList.add('d-none');
      }

      adviceEl.innerHTML = usefulDivisors.length === 0
        ? '<strong>' + N_total + '</strong> módulos no tiene divisores exactos en el rango disponible (' +
          Ns_min + '–' + Ns_max + '). Considera ajustar el módulo seleccionado.'
        : 'Para strings uniformes, ajusta Ns a: ' +
          usefulDivisors.map(d =>
            '<button type="button" data-ns-pick="' + d + '" class="btn btn-primary btn-xs mr-1">' + d + '</button>'
          ).join(' ') + '.';
      remEl.classList.remove('d-none');
    } else {
      remEl.classList.add('d-none');
    }

    // ── Update string config DOM ──
    document.getElementById('ns-value').textContent       = currentNs;
    document.getElementById('np-value').textContent       = Np;
    document.getElementById('str-voc-cold').textContent   = Voc_cold.toFixed(1)  + ' V';
    document.getElementById('str-vmpp-hot').textContent   = Vmpp_hot.toFixed(1)  + ' V';
    document.getElementById('str-vmpp-cold').textContent  = Vmpp_cold.toFixed(1) + ' V';
    document.getElementById('str-area-total').textContent =
      (N_total * selectedModule.length_m * selectedModule.width_m).toFixed(1) + ' m² área total';

    // ── N_inv stepper ──
    const Np_total  = Math.ceil(N_total / currentNs);
    const capPerInv = selectedInverter ? totalCapacityPerInv(selectedInverter, selectedModule) : 0;
    const nInvMin   = selectedInverter ? Math.ceil(Np_total / capPerInv) : 1;
    currentNInv     = Math.max(nInvMin, currentNInv);

    document.getElementById('ninv-value').textContent = currentNInv;
    document.getElementById('ninv-hint').textContent  = selectedInverter
      ? 'Mínimo: ' + nInvMin + ' inversor' + (nInvMin > 1 ? 'es' : '')
      : 'Selecciona inversor primero';
    document.getElementById('btn-ninv-dec').disabled = currentNInv <= Math.max(1, nInvMin);

    // ── Np/inv hint ──
    const Np_per_inv_display = Math.ceil(Np_total / currentNInv);
    const hintEl = document.getElementById('np-mppt-hint');
    if (selectedInverter) {
      const ok = Np_per_inv_display <= capPerInv;
      hintEl.textContent = (ok ? '✓ ' : '✗ ') + Np_per_inv_display + ' / ' + capPerInv + ' strings/inv';
      hintEl.className = 'small font-weight-bold ' + (ok ? 'text-success' : 'text-danger');
    } else {
      hintEl.textContent = 'Selecciona un inversor para verificar';
      hintEl.className = 'small text-muted';
    }

    // ── Range hint + stepper buttons ──
    document.getElementById('ns-range-hint').textContent = selectedInverter
      ? `Rango: ${Ns_min} (${Ns_min_reason}) – ${Ns_max} (${Ns_max_reason}) · total: ${N_total}`
      : `Rango: 1 – ${Ns_max} (${Ns_max_reason}) · total: ${N_total}`;
    document.getElementById('btn-ns-dec').disabled = currentNs <= Ns_min;
    document.getElementById('btn-ns-inc').disabled = currentNs >= Ns_max;

    if (selectedInverter) {
      window.calcState.Ns = currentNs;
      window.calcState.Np = Math.ceil(N_total / currentNs);
      computeChecks(selectedInverter);
    }
  }

  // N_total (module count) stepper events
  document.getElementById('btn-n-dec-10').addEventListener('click', function () {
    if (!this.disabled) applyNewNTotal(N_total - 10);
  });
  document.getElementById('btn-n-dec').addEventListener('click', function () {
    if (!this.disabled) applyNewNTotal(N_total - 1);
  });
  document.getElementById('btn-n-inc').addEventListener('click', function () {
    applyNewNTotal(N_total + 1);
  });
  document.getElementById('btn-n-inc-10').addEventListener('click', function () {
    applyNewNTotal(N_total + 10);
  });
  document.getElementById('btn-n-reset').addEventListener('click', function (e) {
    e.preventDefault();
    applyNewNTotal(N_suggested);
  });

  // Ns stepper events
  document.getElementById('btn-ns-dec').addEventListener('click', function () {
    if (!this.disabled) { currentNs--; refreshStringUI(); }
  });
  document.getElementById('btn-ns-inc').addEventListener('click', function () {
    if (!this.disabled) { currentNs++; refreshStringUI(); }
  });
  // N_inv stepper events
  document.getElementById('btn-ninv-dec').addEventListener('click', function () {
    if (!this.disabled) { currentNInv--; refreshStringUI(); }
  });
  document.getElementById('btn-ninv-inc').addEventListener('click', function () {
    currentNInv++; refreshStringUI();
  });
  document.getElementById('btn-ninv-auto').addEventListener('click', function () {
    if (!selectedInverter) return;
    const Np_total = Math.ceil(N_total / currentNs);
    currentNInv = Math.max(1, Math.ceil(Np_total / selectedInverter.mppt_count));
    refreshStringUI();
  });
  // Delegated: remainder Ns pick buttons
  document.getElementById('str-remainder-warning').addEventListener('click', function (e) {
    const btn = e.target.closest('[data-ns-pick]');
    if (!btn) return;
    currentNs = parseInt(btn.dataset.nsPick);
    refreshStringUI();
  });

  // ── Compatibility evaluation ────────────────────────────────
  function evaluateCompatibility(inv) {
    if (!selectedModule) return { hardFail: true, warn: false };
    const { Np, Voc_cold, Vmpp_hot, Vmpp_cold } = getStringMetrics(currentNs);
    const Np_per_inv     = Math.ceil(Np / currentNInv);
    const P_cold_per_inv = Np_per_inv * currentNs * P_cold_per;
    const totalCapacity     = totalCapacityPerInv(inv, selectedModule);
    const capacityPass      = Np_per_inv <= totalCapacity;
    const groupLoads        = distributeStrings(Np_per_inv, inv.mppt_groups || [], selectedModule);
    const allGroupIMpptPass = groupLoads.every(
      gl => gl.stringsPerMppt === 0 ||
            gl.stringsPerMppt * selectedModule.imp_stc <= gl.group.max_input_current
    );
    const allGroupIScPass = groupLoads.every(
      gl => gl.stringsPerMppt === 0 ||
            gl.stringsPerMppt * selectedModule.isc_stc <= gl.group.max_short_circuit_current
    );
    const vocPass      = Voc_cold <= inv.max_dc_voltage;
    const vmppHotPass  = Vmpp_hot >= inv.mppt_voltage_min;
    const startupPass  = Vmpp_hot >= inv.startup_voltage;
    const vmppColdPass = Vmpp_cold <= inv.mppt_voltage_max;
    const pDcPass      = P_cold_per_inv <= inv.pmax_dc_input;
    const hardFail     = !capacityPass || !vocPass || !allGroupIMpptPass || !allGroupIScPass || !pDcPass;
    const warn         = !vmppHotPass || !startupPass || !vmppColdPass;
    return {
      Np, Np_per_inv, Voc_cold, Vmpp_hot, Vmpp_cold,
      totalCapacity, capacityPass, groupLoads,
      vocPass, vmppHotPass, startupPass, vmppColdPass, pDcPass,
      hardFail, warn,
    };
  }

  // ── Inverter dropdown ───────────────────────────────────────
  function populateInverterDropdown() {
    const sel = document.getElementById('inverter-select');
    const prevValue = sel.value;
    sel.innerHTML = '<option value="">— Selecciona un inversor —</option>';
    const byManuf = {};
    allInverters.forEach(inv => {
      (byManuf[inv.manufacturer] = byManuf[inv.manufacturer] || []).push(inv);
    });
    Object.keys(byManuf).sort().forEach(manuf => {
      const grp = document.createElement('optgroup');
      grp.label = manuf;
      byManuf[manuf].forEach(inv => {
        const opt = document.createElement('option');
        opt.value = inv.id;
        opt.textContent = inv.model + '  (' +
          (inv.nominal_ac_power / 1000).toFixed(1) + ' kW · ' +
          mapPhaseLabel(inv.phase_type) + ')';
        grp.appendChild(opt);
      });
      sel.appendChild(grp);
    });
    if (prevValue) sel.value = prevValue;
  }

  document.getElementById('inverter-select').addEventListener('change', function () {
    const id = parseInt(this.value);
    if (!id) { clearInverterSelection(); return; }
    const inv = allInverters.find(x => x.id === id);
    if (inv) selectInverter(inv);
  });

  function clearInverterSelection() {
    selectedInverter = null;
    document.getElementById('inverter-card-preview').classList.add('d-none');
    document.getElementById('inverter-compat-status').classList.add('d-none');
    checksPanel.classList.add('d-none');
    if (window.calcState) {
      ['inverter', 'Ns', 'Np', 'N_inv', 'Np_per_inv'].forEach(k => delete window.calcState[k]);
    }
  }

  function selectInverter(inv) {
    selectedInverter = inv;
    window.calcState = window.calcState || {};

    // ── Populate inverter preview card ──
    const groups   = inv.mppt_groups || [];
    const totalCap = totalCapacityPerInv(inv, selectedModule);
    const invImpRow = groups.length > 1
      ? groups.map(g => g.group_label + ': ' + g.max_input_current + ' A').join(' / ')
      : inv.max_input_current_per_mppt + ' A';
    const invIScRow = groups.length > 1
      ? groups.map(g => g.max_short_circuit_current + ' A').join(' / ')
      : inv.max_short_circuit_current + ' A';

    document.getElementById('inv-preview-manufacturer').textContent = inv.manufacturer;
    document.getElementById('inv-preview-model').textContent        = inv.model;
    const phaseBadge = document.getElementById('inv-preview-phase-badge');
    phaseBadge.textContent = mapPhaseLabel(inv.phase_type);
    phaseBadge.className   = 'badge ' + mapPhaseBadgeClass(inv.phase_type);
    document.getElementById('inv-preview-power').textContent     = (inv.nominal_ac_power / 1000).toFixed(1) + ' kW AC';
    document.getElementById('inv-preview-vdc').textContent       = inv.max_dc_voltage + ' V';
    document.getElementById('inv-preview-mppt').textContent      = inv.mppt_voltage_min + '–' + inv.mppt_voltage_max + ' V';
    document.getElementById('inv-preview-vstartup').textContent  = inv.startup_voltage + ' V';
    document.getElementById('inv-preview-impp').textContent      = invImpRow;
    document.getElementById('inv-preview-isc').textContent       = invIScRow;
    document.getElementById('inv-preview-cap').textContent       = totalCap + ' str.';
    document.getElementById('inv-preview-eta').textContent       = inv.efficiency_weighted + '%';
    document.getElementById('inverter-card-preview').classList.remove('d-none');

    // Update calcState
    window.calcState.inverter   = inv;
    window.calcState.N_inv      = currentNInv;
    window.calcState.Ns         = currentNs;
    window.calcState.Np         = Math.ceil(N_total / currentNs);
    window.calcState.Np_per_inv = Math.ceil(Math.ceil(N_total / currentNs) / currentNInv);

    // Refresh string UI with inverter-aware Ns bounds
    refreshStringUI();

    // Repopulate dropdown with updated compat symbols, then restore selection
    populateInverterDropdown();
    document.getElementById('inverter-select').value = inv.id;
  }

  // ── Electrical checks ───────────────────────────────────────
  function computeChecks(inv) {
    if (!selectedModule || !inv) return;
    const compat  = evaluateCompatibility(inv);
    const P_stc_W = window.calcState.P_stc_kW * 1000;
    const dc_ac   = P_stc_W / (currentNInv * inv.nominal_ac_power);

    checksPanel.classList.remove('d-none');
    renderVerdictBanner(compat.hardFail, compat.warn);

    // Compat status line below inverter dropdown
    const statusEl = document.getElementById('inverter-compat-status');
    statusEl.classList.remove('d-none', 'text-danger', 'text-warning', 'text-success');
    if (compat.hardFail) {
      statusEl.textContent = '✗ Incompatibilidad crítica con la configuración actual';
      statusEl.classList.add('text-danger');
    } else if (compat.warn) {
      statusEl.textContent = '⚠ Compatible con advertencias — revisa los parámetros';
      statusEl.classList.add('text-warning');
    } else {
      statusEl.textContent = '✓ Compatible con la configuración actual';
      statusEl.classList.add('text-success');
    }

    // Check cards
    setCheck('chk-np-mppt',
      compat.Np_per_inv + ' strings/inv' + (currentNInv > 1 ? ' (' + compat.Np + ' totales ÷ ' + currentNInv + ')' : ''),
      '≤ ' + compat.totalCapacity + ' cap. total (MPPT × str máx)',
      compat.capacityPass, true);
    setCheck('chk-voc',
      compat.Voc_cold.toFixed(1) + ' V',
      '≤ ' + inv.max_dc_voltage + ' V',
      compat.vocPass, true);
    setCheck('chk-vmpp-hot',
      compat.Vmpp_hot.toFixed(1) + ' V',
      '≥ ' + inv.mppt_voltage_min + ' V',
      compat.vmppHotPass, false);
    setCheck('chk-startup-v',
      compat.Vmpp_hot.toFixed(1) + ' V',
      '≥ ' + inv.startup_voltage + ' V',
      compat.startupPass, false);
    setCheck('chk-vmpp-cold',
      compat.Vmpp_cold.toFixed(1) + ' V',
      '≤ ' + inv.mppt_voltage_max + ' V',
      compat.vmppColdPass, false);
    renderCurrentCheck('chk-i-mppt',  compat.groupLoads, 'imp');
    renderCurrentCheck('chk-i-total', compat.groupLoads, 'isc');

    const tmin           = parseFloat(document.getElementById('tmin').value) || STC_TEMP;
    const P_cold_per_inv = compat.Np_per_inv * currentNs * P_cold_per;
    setCheck('chk-p-dc',
      (P_cold_per_inv / 1000).toFixed(2) + ' kW/inv' +
        (currentNInv > 1 ? ' (÷' + currentNInv + ')' : '') +
        ' (T_min=' + tmin + '°C)',
      '≤ ' + (inv.pmax_dc_input / 1000).toFixed(2) + ' kW',
      compat.pDcPass, true);

    // DC/AC ratio
    const dcacLabel = classifyDcAcLabel(dc_ac);
    const dcacEl    = document.getElementById('res-dcac');
    const dcacHint  = document.getElementById('res-dcac-hint');
    dcacEl.textContent = dc_ac.toFixed(2);
    dcacEl.className   = 'h5 font-weight-bold mb-1 ' + dcacLabel.cssClass;
    dcacHint.textContent = dcacLabel.label;
    dcacHint.className   = 'small font-weight-bold ' + dcacLabel.cssClass;
    document.getElementById('res-dcac-pstc').textContent = (P_stc_W / 1000).toFixed(2) + ' kW';
    document.getElementById('res-dcac-pac').textContent  =
      (currentNInv > 1 ? currentNInv + ' × ' : '') +
      (inv.nominal_ac_power / 1000).toFixed(2) + ' kW' +
      (currentNInv > 1 ? ' = ' + (currentNInv * inv.nominal_ac_power / 1000).toFixed(2) + ' kW total' : '');

    // Energy production section
    renderEnergiaSection(dc_ac);

    // Sync calcState
    window.calcState.N_inv      = currentNInv;
    window.calcState.Np_per_inv = Math.ceil(compat.Np / currentNInv);
  }

  function setCheck(id, actual, limit, pass, isHard) {
    const card = document.getElementById(id);
    if (!card) return;
    const badge = card.querySelector('[data-badge]');
    card.className  = 'card card-outline h-100 ' +
      (pass ? 'card-success' : isHard ? 'card-danger' : 'card-warning');
    badge.className = 'badge ' +
      (pass ? 'badge-success' : isHard ? 'badge-danger' : 'badge-warning');
    badge.textContent = pass ? '✓ OK' : isHard ? '✗ Falla' : '⚠ Revisar';
    card.querySelector('[data-actual]').textContent = actual;
    card.querySelector('[data-limit]').textContent  = limit;
  }

  function renderCurrentCheck(cardId, groupLoads, type) {
    const card = document.getElementById(cardId);
    if (!card || !selectedModule) return;
    const baseI     = type === 'imp' ? selectedModule.imp_stc : selectedModule.isc_stc;
    const limitKey  = type === 'imp' ? 'max_input_current'    : 'max_short_circuit_current';
    const typeLabel = type === 'imp' ? 'Imp' : 'Isc';
    const active    = groupLoads.filter(gl => gl.stringsAssigned > 0);
    const allPass   = active.every(gl => gl.stringsPerMppt * baseI <= gl.group[limitKey]);

    const badge = card.querySelector('[data-badge]');
    card.className  = 'card card-outline h-100 ' + (allPass ? 'card-success' : 'card-danger');
    badge.className = 'badge ' + (allPass ? 'badge-success' : 'badge-danger');
    badge.textContent = allPass ? '✓ OK' : '✗ Falla';

    const actualEl = card.querySelector('[data-actual]');
    const limitEl  = card.querySelector('[data-limit]');
    if (active.length <= 1) {
      const gl  = active[0];
      const I   = gl ? gl.stringsPerMppt * baseI : baseI;
      const lim = gl ? gl.group[limitKey] : 0;
      actualEl.textContent = I.toFixed(2) + ' A (' +
        (gl && gl.stringsPerMppt > 1
          ? gl.stringsPerMppt + ' str × ' + typeLabel
          : typeLabel + ', 1 str') + ')';
      limitEl.textContent = '≤ ' + lim + ' A';
    } else {
      actualEl.innerHTML = active.map(gl => {
        const I   = (gl.stringsPerMppt * baseI).toFixed(2);
        const lim = gl.group[limitKey];
        const ok  = parseFloat(I) <= lim;
        return '<span style="display:block' +
          (ok ? '' : ';color:var(--danger,#dc3545);font-weight:700') + '">' +
          gl.group.group_label + ': ' + gl.stringsPerMppt + ' str = ' + I + ' A / ' + lim + ' A</span>';
      }).join('');
      limitEl.textContent = type === 'imp' ? 'I máx por entrada MPPT' : 'Isc máx por entrada MPPT';
    }
  }

  function renderVerdictBanner(anyHardFail, anySoftFail) {
    const el = document.getElementById('verdict-banner');
    if (!el) return;
    if (anyHardFail) {
      el.className = 'alert alert-danger d-flex align-items-center mb-3';
      el.innerHTML = '<i class="fas fa-times-circle mr-2"></i> Incompatibilidad crítica — revisa los parámetros marcados en rojo';
    } else if (anySoftFail) {
      el.className = 'alert alert-warning d-flex align-items-center mb-3';
      el.innerHTML = '<i class="fas fa-exclamation-triangle mr-2"></i> Compatible con advertencias — revisa los parámetros amarillos';
    } else {
      el.className = 'alert alert-success d-flex align-items-center mb-3';
      el.innerHTML = '<i class="fas fa-check-circle mr-2"></i> Sistema compatible — todos los parámetros dentro de especificación';
    }
  }

  // ── Energy production section ────────────────────────────────
  function renderEnergiaSection(dc_ac) {
    const cs      = window.calcState || {};
    const P_stc   = cs.P_stc_kW || 0;
    const hsp     = parseFloat(document.getElementById('hsp').value)                || 0;
    const consumo = parseFloat(document.getElementById('consumo_anual_kwh').value) || 0;

    const E_year    = P_stc * hsp * 365 * currentPR;
    const cobertura = consumo > 0 ? Math.min((E_year / consumo) * 100, 999) : 0;

    document.getElementById('en-p-stc').textContent            = P_stc.toFixed(2);
    document.getElementById('en-produccion-anual').textContent = Math.round(E_year) + '';
    document.getElementById('en-cobertura').textContent        = cobertura.toFixed(1) + '%';

    const dcacEl   = document.getElementById('en-dcac');
    const dcacHint = document.getElementById('en-dcac-hint');
    const dcacLbl  = classifyDcAcLabel(dc_ac);
    dcacEl.textContent   = dc_ac.toFixed(2);
    dcacEl.className     = 'font-weight-bold h5 mb-0 ' + dcacLbl.cssClass;
    dcacHint.textContent = dcacLbl.label;
    dcacHint.className   = 'text-muted font-weight-bold small ' + dcacLbl.cssClass;

    // Monthly table
    const monthly        = cs.monthly;
    const monthlySection = document.getElementById('en-monthly-section');
    const noMonthly      = document.getElementById('en-no-monthly');
    const tbody          = document.getElementById('en-monthly-tbody');
    const tfoot          = document.getElementById('en-monthly-tfoot');

    if (monthly && monthly.length === 12) {
      noMonthly.classList.add('d-none');
      monthlySection.classList.remove('d-none');

      // Recompute production with latest P_stc and PR
      enMonthlyProduction = monthly.map((row, i) => P_stc * row.ghi * MONTH_DAYS[i] * currentPR);

      const tableAlreadyBuilt = tbody.querySelector('tr') !== null;

      if (tableAlreadyBuilt) {
        // ── In-place update: patch production cells and balances only ──
        enMonthlyProduction.forEach((prod, i) => {
          const row = tbody.querySelector(`tr[data-month="${i}"]`);
          if (!row) return;
          // 4th <td> (index 3) is the production cell
          const cells = row.querySelectorAll('td');
          if (cells[3]) cells[3].textContent = Math.round(prod);
        });

        // Update total production in tfoot
        const totalProd = enMonthlyProduction.reduce((a, b) => a + b, 0);
        const totalProdEl = document.getElementById('en-total-prod');
        if (totalProdEl) totalProdEl.textContent = Math.round(totalProd);

        // Recompute balances since production values changed
        updateEnBalances();

      } else {
        // ── Full build: construct the table for the first time ──
        tbody.innerHTML = monthly.map((row, i) => {
          const prod  = enMonthlyProduction[i];
          const rowBg = i % 2 === 0 ? '' : 'table-light';
          return `
            <tr class="${rowBg}" data-month="${i}">
              <td>${MONTH_NAMES[i]}</td>
              <td class="text-right">${row.ghi.toFixed(2)}</td>
              <td class="text-right">${MONTH_DAYS[i]}</td>
              <td class="text-right font-weight-bold">${Math.round(prod)}</td>
              <td class="en-cons-col d-none text-right">
                <input type="number" min="0" step="1"
                  id="en-cons-input-${i}"
                  class="form-control form-control-sm text-right"
                  placeholder="—"/>
              </td>
              <td class="en-cons-col d-none text-right font-weight-bold" id="en-bal-${i}">—</td>
              <td class="en-cons-col d-none text-right font-weight-bold" id="en-bolsa-${i}">—</td>
            </tr>`;
        }).join('');

        const totalProd = enMonthlyProduction.reduce((a, b) => a + b, 0);
        tfoot.innerHTML = `
          <tr>
            <td>Total anual</td>
            <td class="text-right">—</td>
            <td class="text-right">365</td>
            <td class="text-right" id="en-total-prod">${Math.round(totalProd)}</td>
            <td class="en-cons-col d-none text-right" id="en-total-cons">—</td>
            <td class="en-cons-col d-none text-right" id="en-total-bal">—</td>
            <td class="en-cons-col d-none text-right" id="en-total-bolsa">—</td>
          </tr>`;

        // Delegate input listener (only needed once on initial build)
        tbody.addEventListener('input', function (e) {
          if (!e.target.matches('input[id^="en-cons-input-"]')) return;
          updateEnBalances();
        });
      }

      // Keep consumption column visibility in sync with current toggle state
      document.querySelectorAll('.en-cons-col').forEach(el => {
        el.classList.toggle('d-none', !enShowConsumption);
      });

    } else {
      monthlySection.classList.add('d-none');
      noMonthly.classList.remove('d-none');
    }

    document.getElementById('bloque-energia').classList.remove('d-none');

    // Electrical protections section
    renderProteccionesSection();
  }

  function fmtOCPD(val) { return typeof val === 'number' ? val + ' A' : String(val); }

  function getDeratingFactor(tAmb) {
    const row = DERATING_TABLE.find(r => tAmb <= r.maxTemp);
    return row ? row.factor : DERATING_TABLE[DERATING_TABLE.length - 1].factor;
  }

  // ── Electrical protections section ─────────────────────────
  function renderProteccionesSection() {
    const cs  = window.calcState || {};
    const mod = cs.module;
    const inv = cs.inverter;
    if (!mod || !inv) return;

    const tmax = parseFloat(document.getElementById('tmax').value) || STC_TEMP;
    const factor = deratingOn ? getDeratingFactor(tmax) : 1.0;

    // DC scenarios
    const Np_per_inv = cs.Np_per_inv || cs.Np || 1;
    const groupLoads = distributeStrings(Np_per_inv, inv.mppt_groups || [], mod);
    renderDcProtection(getDcScenarios(mod, groupLoads, factor), factor);

    // Derating hint
    const hint = document.getElementById('prot-derating-hint');
    if (hint) {
      if (deratingOn) {
        hint.textContent = `Tamb máx = ${tmax.toFixed(1)} °C → factor ${factor} (Tabla 310.15(B)(2)(a), conductores a 75 °C)`;
        hint.classList.remove('d-none');
      } else {
        hint.classList.add('d-none');
      }
    }

    // AC circuit
    const isThreePhase  = inv.phase_type === 'Three Phase';
    const phaseDiv      = isThreePhase ? (Math.sqrt(3) * inv.ac_voltage_nominal) : inv.ac_voltage_nominal;
    const phaseFmt      = isThreePhase
      ? `${inv.nominal_ac_power} W ÷ (√3 × ${inv.ac_voltage_nominal} V) = `
      : `${inv.nominal_ac_power} W ÷ ${inv.ac_voltage_nominal} V = `;
    const I_ac_base     = inv.nominal_ac_power / phaseDiv;
    const I_ac_design   = I_ac_base * 1.25;
    const I_ac_required = I_ac_design / factor;
    const acCircuit     = resolveCircuit(I_ac_required, I_ac_design);

    const setText = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
    setText('prot-ac-phase',   inv.phase_type);
    setText('prot-ac-ratio',   phaseFmt + I_ac_base.toFixed(2) + ' A');
    setText('prot-ac-idesign', I_ac_design.toFixed(2) + ' A');
    setText('prot-ac-derated', deratingOn
      ? `${I_ac_design.toFixed(2)} A ÷ ${factor} = ${I_ac_required.toFixed(2)} A requeridos en tabla` : '—');
    const acDeratedRow = document.getElementById('prot-ac-derated-row');
    if (acDeratedRow) acDeratedRow.classList.toggle('d-none', !deratingOn);
    setText('prot-ac-ocpd', fmtOCPD(acCircuit.ocpd));
    setText('prot-ac-awg',  acCircuit.awg);
    const acSmallRow = document.getElementById('prot-ac-small-cond-row');
    if (acSmallRow) acSmallRow.classList.toggle('d-none', !acCircuit.upsized);

    document.getElementById('bloque-protecciones').classList.remove('d-none');
    document.getElementById('bloque-export').classList.remove('d-none');
  }

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
        const e = map.get(strCount);
        e.mpptCount += count;
        e.labels.push(gl.group.group_label + (suffix ? ' ' + suffix : ''));
      }
      if (rem === 0) {
        add(ceil, total, '');
      } else {
        add(ceil,  rem,         total > 1 ? `(${rem}/${total} MPPT)` : '');
        add(floor, total - rem, total > 1 ? `(${total - rem}/${total} MPPT)` : '');
      }
    });
    return Array.from(map.entries())
      .sort((a, b) => b[0] - a[0])
      .map(([strPerMppt, { mpptCount, labels }]) => {
        const needsFuse  = strPerMppt >= 2;
        const fuseMinA   = Isc * 1.56;
        const fuseStdA   = needsFuse ? nextGpvFuse(fuseMinA) : null;
        const strCircuit  = resolveCircuit(Isc * 1.56 / factor, Isc * 1.56);
        const mpptCircuit = resolveCircuit(strPerMppt * Isc * 1.56 / factor, strPerMppt * Isc * 1.56);
        return { strPerMppt, mpptCount, labels, needsFuse, fuseMinA, fuseStdA, strCircuit, mpptCircuit, Isc };
      });
  }

  function renderDcProtection(scenarios, factor) {
    const container = document.getElementById('prot-dc-scenarios');
    if (!container) return;
    container.innerHTML = '';
    if (scenarios.length === 0) {
      container.innerHTML = '<div class="col-12"><p class="text-muted small">Sin strings asignados.</p></div>';
      return;
    }
    const row = (label, value, upsized) =>
      `<div class="d-flex justify-content-between small mb-1">` +
      `<span class="text-muted">${label}</span>` +
      `<strong${upsized ? ' class="text-warning"' : ''}>${value}</strong></div>`;
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
          sc.fuseStdA + ' A', false);
        bodyHtml += row(
          `Cable cadena Cu 75°C <small class="text-muted">(I<sub>sc</sub> ×1.56${factor !== 1.0 ? ' ÷' + factor : ''} = ${(sc.Isc * 1.56 / factor).toFixed(1)} A)</small>:`,
          sc.strCircuit.awg, sc.strCircuit.upsized);
        bodyHtml += row(
          `Cable entrada MPPT <small class="text-muted">(${sc.strPerMppt}×I<sub>sc</sub> ×1.56${factor !== 1.0 ? ' ÷' + factor : ''} = ${(sc.strPerMppt * sc.Isc * 1.56 / factor).toFixed(1)} A)</small>:`,
          sc.mpptCircuit.awg, sc.mpptCircuit.upsized);
        bodyHtml += row(
          `Protección MPPT <small class="text-muted">(${sc.strPerMppt}×I<sub>sc</sub> ×1.56 = ${(sc.strPerMppt * sc.Isc * 1.56).toFixed(1)} A)</small>:`,
          fmtOCPD(sc.mpptCircuit.ocpd), false);
        if (sc.strCircuit.upsized || sc.mpptCircuit.upsized)
          bodyHtml += `<small class="text-warning d-block mt-1"><i class="fas fa-exclamation-triangle mr-1"></i>Conductor aumentado por Art. 240-4(d)</small>`;
      } else {
        bodyHtml += `<div class="small mb-2 text-success"><i class="fas fa-check-circle mr-1"></i>String único — sin corriente inversa posible</div>`;
        bodyHtml += row(
          `Cable DC Cu 75°C <small class="text-muted">(I<sub>sc</sub> ×1.56${factor !== 1.0 ? ' ÷' + factor : ''} = ${(sc.Isc * 1.56 / factor).toFixed(1)} A)</small>:`,
          sc.strCircuit.awg, sc.strCircuit.upsized);
        bodyHtml += row(
          `Protección CC <small class="text-muted">(I<sub>sc</sub> ×1.56 = ${(sc.Isc * 1.56).toFixed(1)} A)</small>:`,
          fmtOCPD(sc.strCircuit.ocpd), false);
        if (sc.strCircuit.upsized)
          bodyHtml += `<small class="text-warning d-block mt-1"><i class="fas fa-exclamation-triangle mr-1"></i>Conductor aumentado por Art. 240-4(d)</small>`;
      }
      const col = document.createElement('div');
      col.className = 'col-sm-6 col-lg-4 mb-2';
      col.innerHTML = `
        <div class="card card-outline ${cardColor} h-100">
          <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <div>
                <p class="font-weight-bold mb-0 small">${sc.strPerMppt} string${sc.strPerMppt > 1 ? 's' : ''} por MPPT</p>
                <small class="text-muted">${mpptLine}${groupLine ? ' — ' + groupLine : ''}</small>
              </div>
              <span class="badge ${badgeColor} ml-1" style="white-space:nowrap;">${badgeText}</span>
            </div>
            ${bodyHtml}
          </div>
        </div>`;
      container.appendChild(col);
    });
  }

  function updateEnBalances() {
    let totalCons = 0, totalBal = 0, bolsa = 0, hasAny = false;
    for (let i = 0; i < 12; i++) {
      const input   = document.getElementById('en-cons-input-' + i);
      const balEl   = document.getElementById('en-bal-'   + i);
      const bolsaEl = document.getElementById('en-bolsa-' + i);
      if (!input || !balEl || !bolsaEl) continue;
      const val = parseFloat(input.value);
      if (isNaN(val) || input.value === '') {
        balEl.textContent   = '—'; balEl.style.color   = '';
        bolsaEl.textContent = '—'; bolsaEl.style.color = '';
        continue;
      }
      hasAny = true;
      const balance = enMonthlyProduction[i] - val;
      totalCons += val;
      totalBal  += balance;
      bolsa     += balance;
      balEl.textContent  = (balance >= 0 ? '+' : '') + Math.round(balance);
      balEl.style.color  = balance >= 0 ? 'var(--color-green-600,#16a34a)' : 'var(--color-red-500,#ef4444)';
      balEl.style.fontWeight = 'bold';
      bolsaEl.textContent  = (bolsa >= 0 ? '+' : '') + Math.round(bolsa);
      bolsaEl.style.color  = bolsa >= 0 ? 'var(--color-green-600,#16a34a)' : 'var(--color-red-500,#ef4444)';
      bolsaEl.style.fontWeight = 'bold';
    }
    const consEl      = document.getElementById('en-total-cons');
    const totBal      = document.getElementById('en-total-bal');
    const totBolsa    = document.getElementById('en-total-bolsa');
    if (consEl) consEl.textContent = hasAny ? Math.round(totalCons) : '—';
    if (totBal) {
      // Balance column: no aggregate — cumulative total lives in Bolsa
      totBal.textContent = '—'; totBal.style.color = ''; totBal.style.fontWeight = '';
    }
    if (totBolsa) {
      if (hasAny) {
        totBolsa.textContent     = (bolsa >= 0 ? '+' : '') + Math.round(bolsa);
        totBolsa.style.color     = bolsa >= 0 ? 'var(--color-green-600,#16a34a)' : 'var(--color-red-500,#ef4444)';
        totBolsa.style.fontWeight = 'bold';
      } else { totBolsa.textContent = '—'; totBolsa.style.color = ''; totBolsa.style.fontWeight = ''; }
    }
  }

  // ── Reset (called by showStep when navigating back to step 1) ──
  window.resetDisenoBlock = function () {
    selectedModule   = null;
    selectedInverter = null;
    currentNs        = 1;
    currentNInv      = 1;
    N_suggested      = 1;
    currentPR        = 0.80;
    document.getElementById('module-select').value   = '';
    document.getElementById('inverter-select').value = '';
    document.getElementById('module-card-preview').classList.add('d-none');
    document.getElementById('inverter-card-preview').classList.add('d-none');
    document.getElementById('inverter-compat-status').classList.add('d-none');
    stringSection.classList.add('d-none');
    inverterSection.classList.add('d-none');
    prelimResults.classList.add('d-none');
    checksPanel.classList.add('d-none');
    document.getElementById('bloque-energia').classList.add('d-none');
    document.getElementById('bloque-protecciones').classList.add('d-none');
    document.getElementById('bloque-export').classList.add('d-none');
    const prInput = document.getElementById('en-pr-input');
    if (prInput) prInput.value = '0.80';
    document.getElementById('res-n-sugerido-hint').classList.add('d-none');
    document.getElementById('res-n-base-hint').classList.remove('d-none');
    enMonthlyProduction = [];
    enShowConsumption   = false;
    deratingOn          = false;
    // Clear table so the next module selection triggers a full rebuild
    const tbody = document.getElementById('en-monthly-tbody');
    const tfoot = document.getElementById('en-monthly-tfoot');
    if (tbody) tbody.innerHTML = '';
    if (tfoot) tfoot.innerHTML = '';
  };

  // ── Navigation ──────────────────────────────────────────────
  // (single-page flow — no step transitions needed)

  // PR input — immediately re-render energy section on change
  document.getElementById('en-pr-input').addEventListener('input', function () {
    const val = parseFloat(this.value);
    if (isNaN(val) || val < 0.50 || val > 1.00) return;
    currentPR = val;
    if (selectedInverter && window.calcState && window.calcState.P_stc_kW) {
      const P_stc_W = window.calcState.P_stc_kW * 1000;
      const dc_ac   = P_stc_W / (currentNInv * selectedInverter.nominal_ac_power);
      renderEnergiaSection(dc_ac);
    }
  });

  // Consumption column toggle for the energia section
  document.querySelectorAll('.en-cons-btn').forEach(btn => {
    btn.addEventListener('click', function () {
      enShowConsumption = this.dataset.cons === 'on';
      document.querySelectorAll('.en-cons-btn').forEach(b => {
        const active = b.dataset.cons === (enShowConsumption ? 'on' : 'off');
        b.classList.toggle('btn-primary', active);
        b.classList.toggle('btn-default', !active);
      });
      document.querySelectorAll('.en-cons-col').forEach(el => {
        el.classList.toggle('d-none', !enShowConsumption);
      });
    });
  });

  // Derating toggle for the protecciones section
  document.querySelectorAll('.prot-derating-btn').forEach(btn => {
    btn.addEventListener('click', function () {
      deratingOn = this.dataset.derating === 'on';
      document.querySelectorAll('.prot-derating-btn').forEach(b => {
        const active = b.dataset.derating === (deratingOn ? 'on' : 'off');
        b.classList.toggle('btn-primary', active);
        b.classList.toggle('btn-default', !active);
      });
      if (window.calcState && window.calcState.module && window.calcState.inverter) {
        renderProteccionesSection();
      }
    });
  });

  // ── Export to Excel ─────────────────────────────────────────
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

    // Re-derive electrical values
    const betaVoc       = mod.temp_coeff_voc  / 100;
    const gammaPmax     = mod.temp_coeff_pmax / 100;
    const Voc_cold      = Ns * mod.voc_stc  * (1 + betaVoc   * (tmin - 25));
    const Vmpp_hot      = Ns * mod.vmpp_stc * (1 + betaVoc   * (tmax - 25));
    const Vmpp_cold     = Ns * mod.vmpp_stc * (1 + betaVoc   * (tmin - 25));
    const P_cold_per    = mod.pmax_stc * (1 + gammaPmax * (tmin - 25));
    const P_cold_total  = N * P_cold_per;
    const P_cold_per_inv = Np_per_inv * Ns * P_cold_per;
    const dc_ac         = (cs.P_stc_kW * 1000) / (N_inv * inv.nominal_ac_power);

    // Checks (per-group)
    const expGroups      = inv.mppt_groups || [];
    const expTotalCap    = expGroups.length > 0
      ? Math.min(
          expGroups.reduce((s, g) => s + g.mppt_count * effectiveStringsPerMppt(g, mod), 0),
          inv.max_total_strings != null ? inv.max_total_strings : Infinity
        )
      : inv.mppt_count;
    const expCapacityPass = Np_per_inv <= expTotalCap;
    const expGroupLoads   = distributeStrings(Np_per_inv, expGroups, mod);
    const expGroupCurrentChecks = expGroupLoads
      .filter(gl => gl.stringsAssigned > 0)
      .flatMap(gl => {
        const I_mp = (gl.stringsPerMppt * mod.imp_stc).toFixed(2);
        const I_sc = (gl.stringsPerMppt * mod.isc_stc).toFixed(2);
        const lim_mp = gl.group.max_input_current;
        const lim_sc = gl.group.max_short_circuit_current;
        return [
          {
            label:  `Grupo ${gl.group.group_label}: I MPPT ≤ Imáx (${gl.stringsPerMppt} str × Imp)`,
            detail: `${I_mp} A ≤ ${lim_mp} A`,
            pass:   parseFloat(I_mp) <= lim_mp, hard: true,
          },
          {
            label:  `Grupo ${gl.group.group_label}: Isc MPPT ≤ Iscmáx (${gl.stringsPerMppt} str × Isc)`,
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
        detail: `${Np_per_inv} strings/inv ≤ ${expTotalCap} (capacidad efectiva para módulo seleccionado)`, pass: expCapacityPass, hard: true  },
      { label: 'Voc en frío ≤ Tensión máx. DC',             detail: `${Voc_cold.toFixed(1)} V ≤ ${inv.max_dc_voltage} V`,                                                     pass: vocPass,           hard: true  },
      { label: 'Vmpp en calor ≥ Límite inferior MPPT',      detail: `${Vmpp_hot.toFixed(1)} V ≥ ${inv.mppt_voltage_min} V`,                                                   pass: vmppHotPass,       hard: false },
      { label: 'Vmpp en calor ≥ Tensión de arranque',       detail: `${Vmpp_hot.toFixed(1)} V ≥ ${inv.startup_voltage} V`,                                                    pass: startupPass,       hard: false },
      { label: 'Vmpp en frío ≤ Límite superior MPPT',       detail: `${Vmpp_cold.toFixed(1)} V ≤ ${inv.mppt_voltage_max} V`,                                                  pass: vmppColdPass,      hard: false },
      ...expGroupCurrentChecks,
      { label: N_inv > 1
          ? `P por inversor en frío ≤ Entrada DC máx. (${(P_cold_total/1000).toFixed(2)} kW total ÷ ${N_inv})`
          : 'P arreglo en frío ≤ Entrada DC máx.',
        detail: `${(P_cold_per_inv/1000).toFixed(2)} kW/inv (T_min=${tmin}°C) ≤ ${(inv.pmax_dc_input/1000).toFixed(2)} kW`, pass: pDcPass, hard: true },
    ];

    // Energy (with PR)
    const E_year   = cs.P_stc_kW * hsp * 365 * currentPR;
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
          const prod  = enMonthlyProduction[i] ?? (cs.P_stc_kW * row.ghi * MONTH_DAYS[i] * currentPR);
          const entry = { ghi: row.ghi, production: prod };

          if (enShowConsumption) {
            const input = document.getElementById('en-cons-input-' + i);
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
      energy:  { E_year, coverage, PR: currentPR, dc_ac },
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

    if (!document.getElementById('calc-diseno-spinner-style')) {
      const style = document.createElement('style');
      style.id = 'calc-diseno-spinner-style';
      style.textContent = '@keyframes calcDisenoSpin{100%{transform:rotate(360deg)}}';
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
    spinner.style.animation = 'calcDisenoSpin 1s linear infinite';

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

})();
