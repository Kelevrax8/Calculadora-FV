import { describe, it, expect } from 'vitest';
import { adjustModuleTemps } from '../calc-utils.js';
import {
  computeNsBoundsForInverter,
  computeGlobalNsMax,
  suggestDivisors,
  computeRemainderMetrics,
} from '../calc-utils-extra.js';

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
};

describe('Ns and remainder helpers', () => {
  it('computeNsBoundsForInverter returns sensible bounds', () => {
    const temps = adjustModuleTemps(sampleMod, 0, 40);
    const res = computeNsBoundsForInverter(sampleInv, temps.Voc_cold_per, temps.Vmpp_hot_per, temps.Vmpp_cold_per, 100);
    expect(res.Ns_min).toBeGreaterThanOrEqual(1);
    expect(res.Ns_max).toBeGreaterThanOrEqual(res.Ns_min);
    expect(['ventana MPPT', 'Vdc máx']).toContain(res.Ns_max_reason);
  });

  it('computeGlobalNsMax uses global vdc max', () => {
    const invs = [sampleInv, { ...sampleInv, max_dc_voltage: 700 }];
    const temps = adjustModuleTemps(sampleMod, 0, 40);
    const res = computeGlobalNsMax(invs, temps.Voc_cold_per, 100);
    expect(res.global_vdc_max).toBe(700);
    expect(res.Ns_max).toBeGreaterThan(0);
  });

  it('suggestDivisors finds useful divisors', () => {
    // 36 has divisors 2,3,4,6,9,12,18
    const divs = suggestDivisors(36, 2, 18);
    expect(divs).toEqual(expect.arrayContaining([2,3,4,6,9,12,18]));
  });

  it('computeRemainderMetrics computes remainder and flags', () => {
    const temps = adjustModuleTemps(sampleMod, 0, 40);
    const N_total = 10;
    const currentNs = 4;
    const metrics = computeRemainderMetrics(N_total, currentNs, temps.Voc_cold_per, temps.Vmpp_hot_per, temps.Vmpp_cold_per, sampleInv);
    expect(metrics.n_rem).toBe(2);
    expect(metrics.rem_Voc_cold).toBeGreaterThan(0);
    expect(typeof metrics.rem_mppt_ok).toBe('boolean');
  });
});
