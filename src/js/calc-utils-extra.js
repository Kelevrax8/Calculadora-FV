// Extra pure utilities extracted for unit testing (non-DOM)
import { adjustModuleTemps } from './calc-utils.js';

const OCPD_SIZES = [15, 20, 25, 30, 35, 40, 45, 50, 60, 70, 80, 90, 100, 110, 125, 150, 175, 200];

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

const DERATING_TABLE = [
  { maxTemp: 30, factor: 1.00 },
  { maxTemp: 35, factor: 0.94 },
  { maxTemp: 40, factor: 0.91 },
  { maxTemp: 45, factor: 0.87 },
  { maxTemp: 50, factor: 0.82 },
  { maxTemp: 55, factor: 0.75 },
  { maxTemp: 60, factor: 0.67 },
];

const MONTH_DAYS = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

export function getDeratingFactor(tAmb) {
  const row = DERATING_TABLE.find(r => tAmb <= r.maxTemp);
  return row ? row.factor : DERATING_TABLE[DERATING_TABLE.length - 1].factor;
}

export function nextOCPD(iDesign) {
  const found = OCPD_SIZES.find(s => s >= iDesign);
  return found ?? `>${OCPD_SIZES[OCPD_SIZES.length - 1]} A (consultar)`;
}

export function minAWG(iRequired) {
  const found = AWG_TABLE.find(r => r.ampacity >= iRequired);
  return found ? found.label : 'Mayor a 4/0 AWG (consultar)';
}

export function computeMonthlyProduction(monthly, P_stc_kW, PR = 0.75) {
  if (!monthly || monthly.length !== 12) return null;
  return monthly.map((row, i) => P_stc_kW * row.ghi * MONTH_DAYS[i] * PR);
}

export function computeModuleAreaEta(mod) {
  const area = mod.length_m * mod.width_m;
  const eta  = mod.pmax_stc / (1000 * area) * 100;
  return { area, eta };
}

export function computeArraySizing(consumo, hsp, tmax, mod) {
  const E_dia_Wh = (consumo / 365) * 1000;
  const P_req_W = hsp > 0 ? E_dia_Wh / (hsp * 0.75) : 0;
  const N = P_req_W > 0 ? Math.ceil(P_req_W / mod.pmax_stc) : 0;
  const P_stc_kW = (N * mod.pmax_stc) / 1000;

  const gamma = mod.temp_coeff_pmax / 100;
  const dT = tmax - 25;
  const P_mod_calor = mod.pmax_stc * (1 + gamma * dT);
  const P_calor_kW = (N * P_mod_calor) / 1000;
  const pct_calor = P_stc_kW > 0 ? ((P_calor_kW / P_stc_kW) - 1) * 100 : 0;

  const Isc_prot = mod.isc_stc * 1.56;

  return { E_dia_Wh, P_req_W, N, P_stc_kW, P_calor_kW, pct_calor, Isc_prot };
}

export function computeProtectionValues(mod, inv, deratingOn, tmax) {
  const factor = deratingOn ? getDeratingFactor(tmax) : 1.0;
  const I_dc_design = mod.isc_stc * 1.56;
  const I_dc_required = I_dc_design / factor;

  const isThreePhase = inv.phase_type === 'Three Phase';
  const phaseDiv = isThreePhase ? Math.sqrt(3) * inv.ac_voltage_nominal : inv.ac_voltage_nominal;
  const I_ac_base = inv.nominal_ac_power / phaseDiv;
  const I_ac_design = I_ac_base * 1.25;
  const I_ac_required = I_ac_design / factor;

  return {
    factor,
    I_dc_design,
    I_dc_required,
    OCPD_dc: nextOCPD(I_dc_design),
    AWG_dc: minAWG(I_dc_required),
    I_ac_base,
    I_ac_design,
    I_ac_required,
    OCPD_ac: nextOCPD(I_ac_design),
    AWG_ac: minAWG(I_ac_required),
  };
}

export default {
  getDeratingFactor,
  nextOCPD,
  minAWG,
  computeMonthlyProduction,
  computeModuleAreaEta,
  computeArraySizing,
  computeProtectionValues,
  // Ns / string helpers
  computeNsBoundsForInverter,
  computeGlobalNsMax,
  suggestDivisors,
  computeRemainderMetrics,
  // inverter checks + payload builder
  computeInvChecks,
  buildExportPayloadPure,
};

// ------------------ Ns / remainder helpers ------------------
export function computeNsBoundsForInverter(inv, Voc_cold_per, Vmpp_hot_per, Vmpp_cold_per, N_total) {
  const Ns_by_vdc  = Math.floor(inv.max_dc_voltage / Voc_cold_per);
  const Ns_by_mppt = Math.floor(inv.mppt_voltage_max / Vmpp_cold_per);
  const Ns_max_raw = Math.min(Ns_by_vdc, Ns_by_mppt);
  const Ns_max     = Math.min(N_total, Ns_max_raw);
  const Ns_max_reason = (Ns_by_mppt <= Ns_by_vdc) ? 'ventana MPPT' : 'Vdc máx';
  const Ns_min     = Math.max(1, Math.ceil(inv.startup_voltage / Vmpp_hot_per));
  const Ns_min_reason = 'arranque';
  return { Ns_min, Ns_max, Ns_min_reason, Ns_max_reason, Ns_by_vdc, Ns_by_mppt };
}

export function computeGlobalNsMax(allInverters, Voc_cold_per, N_total) {
  const global_vdc_max = Math.max(...allInverters.map(i => i.max_dc_voltage));
  const Ns_max = Math.min(N_total, Math.floor(global_vdc_max / Voc_cold_per));
  return { Ns_max, Ns_max_reason: 'Vdc máx global', global_vdc_max };
}

export function suggestDivisors(N_total, Ns_min, Ns_max) {
  const usefulDivisors = [];
  for (let d = Math.max(2, Ns_min); d <= Math.min(N_total - 1, Ns_max); d++) {
    if (N_total % d === 0) usefulDivisors.push(d);
  }
  return usefulDivisors;
}

export function computeRemainderMetrics(N_total, currentNs, Voc_cold_per, Vmpp_hot_per, Vmpp_cold_per, selectedInverter) {
  const n_rem = N_total % currentNs;
  const n_full_str = Math.floor(N_total / currentNs);
  if (n_rem === 0) return { n_rem: 0, n_full_str, rem_Voc_cold: 0, rem_Vmpp_hot: 0, rem_Vmpp_cold: 0 };
  const rem_Voc_cold  = n_rem * Voc_cold_per;
  const rem_Vmpp_hot  = n_rem * Vmpp_hot_per;
  const rem_Vmpp_cold = n_rem * Vmpp_cold_per;
  const rem_mppt_ok = selectedInverter ? (rem_Vmpp_hot >= selectedInverter.mppt_voltage_min) : null;
  const rem_startup_ok = selectedInverter ? (rem_Vmpp_hot >= selectedInverter.startup_voltage) : null;
  return { n_rem, n_full_str, rem_Voc_cold, rem_Vmpp_hot, rem_Vmpp_cold, rem_mppt_ok, rem_startup_ok };
}

// ------------------ Inverter checks & export payload ------------------
export function computeInvChecks(cs, inv) {
  const mod = cs.module;
  const Ns = cs.Ns;
  const N_total = cs.N;
  const temps = adjustModuleTemps(mod, cs.site?.tmin ?? 25, cs.site?.tmax ?? 25);
  const Voc_cold_per = temps.Voc_cold_per;
  const Vmpp_hot_per = temps.Vmpp_hot_per;
  const Vmpp_cold_per = temps.Vmpp_cold_per;

  const Np = Math.ceil(N_total / Ns);
  const Voc_cold = Ns * Voc_cold_per;
  const Vmpp_hot = Ns * Vmpp_hot_per;
  const Vmpp_cold = Ns * Vmpp_cold_per;
  const I_per_mppt = mod.imp_stc * 1.25;
  const I_total = mod.isc_stc * 1.25;
  const P_cold_total = N_total * (mod.pmax_stc * (1 + (mod.temp_coeff_pmax/100) * ((cs.site?.tmin ?? 25) - 25)));
  const P_stc_W = cs.P_stc_kW * 1000;
  const dc_ac = P_stc_W / inv.nominal_ac_power;

  const npPass       = Np            <= inv.mppt_count;
  const vocPass      = Voc_cold      <= inv.max_dc_voltage;
  const vmppHotPass  = Vmpp_hot      >= inv.mppt_voltage_min;
  const startupPass  = Vmpp_hot      >= inv.startup_voltage;
  const vmppColdPass = Vmpp_cold     <= inv.mppt_voltage_max;
  const iMpptPass    = I_per_mppt    <= inv.max_input_current_per_mppt;
  const iTotalPass   = I_total       <= inv.max_short_circuit_current;
  const pDcPass      = P_cold_total  <= inv.pmax_dc_input;

  const anyHardFail = !npPass || !vocPass || !iMpptPass || !iTotalPass || !pDcPass;
  const anySoftFail = !vmppHotPass || !startupPass || !vmppColdPass;

  return {
    Np, Voc_cold, Vmpp_hot, Vmpp_cold, I_per_mppt, I_total, P_cold_total, dc_ac,
    npPass, vocPass, vmppHotPass, startupPass, vmppColdPass, iMpptPass, iTotalPass, pDcPass,
    anyHardFail, anySoftFail,
  };
}

export function buildExportPayloadPure(cs, inputs = {}) {
  // inputs: tmin,tmax,hsp,lat,lng,consumo,showConsumption,monthlyProduction
  const mod = cs.module;
  const inv = cs.inverter;
  const Ns = cs.Ns;
  const Np = cs.Np;
  const N = cs.N;
  const tmin = inputs.tmin ?? cs.site?.tmin ?? 25;
  const tmax = inputs.tmax ?? cs.site?.tmax ?? 25;
  const hsp  = inputs.hsp  ?? cs.site?.hsp  ?? 0;
  const lat  = inputs.lat  ?? cs.site?.lat ?? 0;
  const lng  = inputs.lng  ?? cs.site?.lng ?? 0;
  const consumo = inputs.consumo ?? cs.site?.consumo ?? 0;
  const PR = inputs.PR ?? 0.75;

  const betaVoc       = mod.temp_coeff_voc  / 100;
  const gammaPmax     = mod.temp_coeff_pmax / 100;
  const Voc_cold = Ns * mod.voc_stc  * (1 + betaVoc   * (tmin - 25));
  const Vmpp_hot = Ns * mod.vmpp_stc * (1 + betaVoc   * (tmax - 25));
  const Vmpp_cold= Ns * mod.vmpp_stc * (1 + betaVoc   * (tmin - 25));
  const P_cold_per = mod.pmax_stc * (1 + gammaPmax * (tmin - 25));
  const P_cold_total = N * P_cold_per;
  const P_stc_W = cs.P_stc_kW * 1000;
  const dc_ac = P_stc_W / inv.nominal_ac_power;

  const checks = [
    { label: 'Núm. strings ≤ Entradas MPPT del inversor',  detail: `${Np} string(s) ≤ ${inv.mppt_count} MPPT`, pass: Np <= inv.mppt_count, hard: true },
    { label: 'Voc en frío ≤ Tensión máx. DC', detail: `${Voc_cold.toFixed(1)} V ≤ ${inv.max_dc_voltage} V`, pass: Voc_cold <= inv.max_dc_voltage, hard: true },
    { label: 'Vmpp en calor ≥ Límite inferior MPPT', detail: `${Vmpp_hot.toFixed(1)} V ≥ ${inv.mppt_voltage_min} V`, pass: Vmpp_hot >= inv.mppt_voltage_min, hard: false },
    { label: 'Vmpp en calor ≥ Tensión de arranque', detail: `${Vmpp_hot.toFixed(1)} V ≥ ${inv.startup_voltage} V`, pass: Vmpp_hot >= inv.startup_voltage, hard: false },
    { label: 'Vmpp en frío ≤ Límite superior MPPT', detail: `${Vmpp_cold.toFixed(1)} V ≤ ${inv.mppt_voltage_max} V`, pass: Vmpp_cold <= inv.mppt_voltage_max, hard: false },
    { label: 'Corriente por MPPT ≤ Imáx entrada (Imp × 1.25)', detail: `${(mod.imp_stc*1.25).toFixed(2)} A ≤ ${inv.max_input_current_per_mppt} A`, pass: (mod.imp_stc*1.25) <= inv.max_input_current_per_mppt, hard: true },
    { label: 'Corriente de CC por MPPT ≤ Isc max entrada (Isc × 1.25)', detail: `${(mod.isc_stc*1.25).toFixed(2)} A ≤ ${inv.max_short_circuit_current} A`, pass: (mod.isc_stc*1.25) <= inv.max_short_circuit_current, hard: true },
    { label: 'P arreglo en frío ≤ Entrada DC máx.', detail: `${(P_cold_total/1000).toFixed(2)} kW (T_min=${tmin}°C) ≤ ${(inv.pmax_dc_input/1000).toFixed(2)} kW`, pass: P_cold_total <= inv.pmax_dc_input, hard: true },
  ];

  const E_year = cs.P_stc_kW * hsp * 365 * PR;
  const coverage = consumo > 0 ? Math.min((E_year / consumo) * 100, 999) : 0;

  const monthly = (cs.monthly && cs.monthly.length === 12)
    ? cs.monthly.map((row, i) => {
        const prod = (inputs.monthlyProduction && inputs.monthlyProduction[i] != null)
          ? inputs.monthlyProduction[i]
          : cs.P_stc_kW * row.ghi * MONTH_DAYS[i] * PR;
        const entry = { ghi: row.ghi, production: prod };
        if (inputs.showConsumption && inputs.consumptionPerMonth && typeof inputs.consumptionPerMonth[i] === 'number') {
          entry.consumo = inputs.consumptionPerMonth[i];
          entry.balance = prod - entry.consumo;
        }
        return entry;
      })
    : null;

  const protection = computeProtectionValues(mod, inv, inputs.deratingOn ?? false, tmax);
  // Mirror expected structure: include derating flag
  protection.derating_on = !!(inputs.deratingOn ?? false);
  return {
    site: { lat, lng, consumo, hsp, tmin, tmax },
    module: { ...mod },
    array: { Ns, Np, N, P_stc_kW: cs.P_stc_kW, Voc_cold, Vmpp_hot, Vmpp_cold, arrArea: mod.length_m * mod.width_m * N },
    inverter: { ...inv },
    checks,
    energy: { E_year, coverage, PR, dc_ac },
    protection,
    monthly,
  };
}

// ------------------ UI mapping helpers (pure) ------------------
export function getCheckStyle(pass, isHard) {
  const cardClass = pass ? 'card-success' : (isHard ? 'card-danger' : 'card-warning');
  const badgeClass = pass ? 'badge-success' : (isHard ? 'badge-danger' : 'badge-warning');
  const badgeText = pass ? '✓ OK' : (isHard ? '✗ Falla' : '⚠ Revisar');
  return { cardClass, badgeClass, badgeText };
}

export function phaseLabelAndColor(phase_type) {
  const label = phase_type === 'Single Phase' ? 'Monofásico'
              : phase_type === 'Three Phase'  ? 'Trifásico'
              : phase_type === 'Split Phase'  ? 'Bifásico'
              : phase_type;
  const color = phase_type === 'Three Phase' ? 'badge-secondary' : 'badge-info';
  return { label, color };
}

export function verdictBannerFor(anyHardFail, anySoftFail) {
  if (anyHardFail) {
    return { className: 'alert alert-danger', html: 'Incompatibilidad crítica detectada' };
  }
  if (anySoftFail) {
    return { className: 'alert alert-warning', html: 'Compatible con advertencias' };
  }
  return { className: 'alert alert-success', html: 'Sistema compatible' };
}

export function computeBalances(monthlyProduction, consumptionArray) {
  const perMonth = [];
  let totalCons = 0, totalBal = 0, hasAny = false;
  for (let i = 0; i < 12; i++) {
    const prod = monthlyProduction[i] ?? 0;
    const cons = (consumptionArray && typeof consumptionArray[i] === 'number') ? consumptionArray[i] : null;
    if (cons === null) {
      perMonth.push({ production: Math.round(prod), consumo: null, balance: null });
      continue;
    }
    hasAny = true;
    const balance = prod - cons;
    totalCons += cons;
    totalBal += balance;
    perMonth.push({ production: Math.round(prod), consumo: Math.round(cons), balance: Math.round(balance), sign: balance >= 0 ? '+' : '', color: balance >= 0 ? 'green' : 'red' });
  }
  return { perMonth, totalCons: hasAny ? Math.round(totalCons) : null, totalBal: hasAny ? (totalBal >= 0 ? '+' + Math.round(totalBal) : '' + Math.round(totalBal)) : null };
}
