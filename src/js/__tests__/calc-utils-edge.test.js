import { describe, it, expect } from 'vitest';
import { computeArraySizing, computeMonthlyProduction, computeProtectionValues } from '../calc-utils-extra.js';
import { adjustModuleTemps } from '../calc-utils.js';
import { computeNsBoundsForInverter, buildExportPayloadPure, computeInvChecks, computeGlobalNsMax } from '../calc-utils-extra.js';

const sampleMod = {
  pmax_stc: 100,
  voc_stc: 40,
  vmpp_stc: 33,
  temp_coeff_voc: -0.3,
  temp_coeff_pmax: -0.4,
  imp_stc: 3.03,
  isc_stc: 3.5,
  length_m: 1.6,
  width_m: 1.0,
};

const sampleInv = {
  id: 1,
  mppt_count: 2,
  max_dc_voltage: 600,
  mppt_voltage_min: 200,
  mppt_voltage_max: 500,
  startup_voltage: 150,
  max_input_current_per_mppt: 20,
  max_short_circuit_current: 40,
  pmax_dc_input: 2000,
  nominal_ac_power: 480000,
  ac_voltage_nominal: 400,
  phase_type: 'Three Phase',
};

describe('Edge cases and integration', () => {
  it('computeArraySizing handles HSP = 0 without throwing', () => {
    const res = computeArraySizing(3650, 0, 40, sampleMod);
    expect(res.P_req_W).toBe(0);
    expect(res.N).toBe(0);
    expect(res.P_stc_kW).toBe(0);
  });

  it('buildExportPayloadPure returns null monthly when monthly missing or invalid', () => {
    const cs = { module: sampleMod, inverter: sampleInv, Ns: 2, Np: 5, N: 10, P_stc_kW: 1.0 };
    const payload = buildExportPayloadPure(cs, { hsp: 4 });
    expect(payload.monthly).toBeNull();
  });

  it('computeProtectionValues handles single-phase vs three-phase formulas', () => {
    const invSingle = { ...sampleInv, phase_type: 'Single Phase', ac_voltage_nominal: 230, nominal_ac_power: 5000 };
    const pSingle = computeProtectionValues(sampleMod, invSingle, false, 40);
    expect(pSingle.I_ac_base).toBeCloseTo(invSingle.nominal_ac_power / 230);
    const invThree = { ...sampleInv, phase_type: 'Three Phase', ac_voltage_nominal: 400, nominal_ac_power: 480000 };
    const pThree = computeProtectionValues(sampleMod, invThree, false, 40);
    expect(pThree.I_ac_base).toBeCloseTo(invThree.nominal_ac_power / (Math.sqrt(3) * 400));
  });

  it('computeNsBoundsForInverter handles extremely small max_dc_voltage (Ns_by_vdc can be zero)', () => {
    const temps = adjustModuleTemps(sampleMod, 0, 40);
    const tinyInv = { ...sampleInv, max_dc_voltage: 10, mppt_voltage_max: 10, startup_voltage: 5 };
    const bounds = computeNsBoundsForInverter(tinyInv, temps.Voc_cold_per, temps.Vmpp_hot_per, temps.Vmpp_cold_per, 100);
    expect(bounds.Ns_by_vdc).toBe(0);
    expect(bounds.Ns_max).toBeLessThanOrEqual(100);
    expect(bounds.Ns_min).toBeGreaterThanOrEqual(1);
  });

  it('buildExportPayloadPure respects deratingOn and consumption overlays', () => {
    const monthly = new Array(12).fill(0).map((_, i) => ({ ghi: 4 + i * 0.2 }));
    const cs = { module: sampleMod, inverter: sampleInv, Ns: 3, Np: 10, N: 30, P_stc_kW: 3.0, monthly };
    const inputs = { tmin: 10, tmax: 45, hsp: 5, consumo: 6000, showConsumption: true, consumptionPerMonth: new Array(12).fill(100), deratingOn: true };
    const payload = buildExportPayloadPure(cs, inputs);
    expect(payload.protection.derating_on).toBeTruthy();
    expect(payload.monthly[0]).toHaveProperty('consumo');
  });

  it('integration: sizing -> ns selection -> inv checks -> payload consistent', () => {
    const consumo = 3650; // 10 kWh/day
    const hsp = 5;
    const sizing = computeArraySizing(consumo, hsp, 40, sampleMod);
    const N = sizing.N;
    const P_stc_kW = sizing.P_stc_kW;
    const cs = { module: sampleMod, inverter: sampleInv, Ns: 3, Np: Math.ceil(N / 3) || 1, N, P_stc_kW, monthly: new Array(12).fill(0).map(() => ({ ghi: 4 })) };
    const invChecks = computeInvChecks(cs, sampleInv);
    const payload = buildExportPayloadPure(cs, { hsp, tmin: 0, tmax: 40, consumo });
    // payload.checks order mirrors computeInvChecks booleans
    expect(payload.checks[0].pass).toBe(invChecks.npPass);
    expect(payload.checks[1].pass).toBe(invChecks.vocPass);
    expect(payload.checks[5].pass).toBe(invChecks.iMpptPass);
  });
});
