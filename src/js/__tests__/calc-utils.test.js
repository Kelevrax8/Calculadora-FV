import { describe, it, expect } from 'vitest';
import { adjustModuleTemps, checkCompatibility, classifyDcAc } from '../calc-utils.js';

describe('calc-utils', () => {
  const sampleMod = {
    pmax_stc: 100,
    voc_stc: 40,
    vmpp_stc: 33,
    temp_coeff_voc: -0.3, // %/°C
    temp_coeff_pmax: -0.4, // %/°C
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

  it('adjustModuleTemps produces expected numeric outputs', () => {
    const temps = adjustModuleTemps(sampleMod, 0, 40);
    expect(typeof temps.betaVoc).toBe('number');
    expect(temps.Voc_cold_per).toBeCloseTo(40 * (1 + (-0.3/100) * (0 - 25)));
    expect(temps.P_cold_per).toBeCloseTo(100 * (1 + (-0.4/100) * (0 - 25)));
  });

  it('checkCompatibility detects compatibility correctly', () => {
    const temps = adjustModuleTemps(sampleMod, -10, 40);
    const res = checkCompatibility(sampleInv, sampleMod, 10, 20, temps);
    expect(res).toHaveProperty('hardFail');
    expect(res).toHaveProperty('warn');
    // With these numbers, ensure res.details contains expected keys
    expect(res.details).toHaveProperty('Np');
    expect(res.details).toHaveProperty('Voc_cold');
  });

  it('classifyDcAc returns expected labels', () => {
    // P_stc_kW such that dc_ac < 0.8
    expect(classifyDcAc(0.3, 1000).label).toBe('Arreglo insuficiente');
    expect(classifyDcAc(0.9, 1000).label).toBe('Subóptimo');
    expect(classifyDcAc(1.1, 1000).label).toBe('Conservador');
    expect(classifyDcAc(1.4, 1000).label).toBe('Óptimo');
    expect(classifyDcAc(2.0, 1000).label).toBe('Sobredimensionado');
  });
});
