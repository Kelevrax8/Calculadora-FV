// Pure calculation utilities extracted from calc-bloque3 / calc-bloque4
// Exported for unit testing with Vitest.

/**
 * Adjust module values by temperature
 * @param {{pmax_stc:number, voc_stc:number, vmpp_stc:number, temp_coeff_voc:number, temp_coeff_pmax:number}} mod
 * @param {number} tmin
 * @param {number} tmax
 * @returns {{betaVoc:number, Voc_cold_per:number, Vmpp_hot_per:number, Vmpp_cold_per:number, P_cold_per:number}}
 */
export function adjustModuleTemps(mod, tmin, tmax) {
  const betaVoc = mod.temp_coeff_voc / 100; // %/°C -> decimal
  const Voc_cold_per  = mod.voc_stc  * (1 + betaVoc * (tmin - 25));
  const Vmpp_hot_per  = mod.vmpp_stc * (1 + betaVoc * (tmax - 25));
  const Vmpp_cold_per = mod.vmpp_stc * (1 + betaVoc * (tmin - 25));
  const gammaPmax = mod.temp_coeff_pmax / 100;
  const P_cold_per = mod.pmax_stc * (1 + gammaPmax * (tmin - 25));
  return { betaVoc, Voc_cold_per, Vmpp_hot_per, Vmpp_cold_per, P_cold_per };
}

/**
 * Check compatibility between inverter and array configuration
 * Mirrors logic of checkCompat() in calc-bloque3
 */
export function checkCompatibility(inv, mod, currentNs, N_total, temps) {
  const { Voc_cold_per, Vmpp_hot_per, Vmpp_cold_per, P_cold_per } = temps;
  const Np = Math.ceil(N_total / currentNs);
  const Voc_cold = currentNs * Voc_cold_per;
  const Vmpp_hot = currentNs * Vmpp_hot_per;
  const Vmpp_cold = currentNs * Vmpp_cold_per;
  const I_per_mppt = mod.imp_stc * 1.25;
  const I_total = mod.isc_stc * 1.25;
  const P_cold_total = N_total * P_cold_per;

  const hardFail =
    Np > inv.mppt_count ||
    Voc_cold > inv.max_dc_voltage ||
    I_per_mppt > inv.max_input_current_per_mppt ||
    I_total > inv.max_short_circuit_current ||
    P_cold_total > inv.pmax_dc_input;

  const warn =
    Vmpp_hot < inv.mppt_voltage_min ||
    Vmpp_hot < inv.startup_voltage ||
    Vmpp_cold > inv.mppt_voltage_max;

  return { hardFail, warn, details: { Np, Voc_cold, Vmpp_hot, Vmpp_cold, I_per_mppt, I_total, P_cold_total } };
}

/**
 * Compute DC/AC ratio and classification label
 */
export function classifyDcAc(P_stc_kW, inv_nominal_ac_w) {
  const P_stc_W = P_stc_kW * 1000;
  const dc_ac = P_stc_W / inv_nominal_ac_w;
  let label;
  if (dc_ac < 0.80) label = 'Arreglo insuficiente';
  else if (dc_ac < 1.00) label = 'Subóptimo';
  else if (dc_ac <= 1.25) label = 'Conservador';
  else if (dc_ac <= 1.50) label = 'Óptimo';
  else label = 'Sobredimensionado';
  return { dc_ac, label };
}
