import { describe, it, expect } from 'vitest';
import {
  getDeratingFactor,
  nextOCPD,
  minAWG,
  computeMonthlyProduction,
  computeModuleAreaEta,
  computeArraySizing,
  computeProtectionValues,
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
  ac_voltage_nominal: 400,
  phase_type: 'Three Phase',
};

describe('calc-utils-extra', () => {
  it('getDeratingFactor returns expected factors', () => {
    expect(getDeratingFactor(30)).toBeCloseTo(1.00);
    expect(getDeratingFactor(40)).toBeCloseTo(0.91);
    expect(getDeratingFactor(55)).toBeCloseTo(0.75);
  });

  it('nextOCPD and minAWG behave correctly', () => {
    expect(nextOCPD(27)).toBe(30);
    expect(nextOCPD(201)).toBe('>200 A (consultar)');
    expect(minAWG(40)).toBe('8 AWG');
    expect(minAWG(999)).toBe('Mayor a 4/0 AWG (consultar)');
  });

  it('computeMonthlyProduction computes expected values', () => {
    const monthly = new Array(12).fill(0).map(() => ({ ghi: 1 }));
    const prod = computeMonthlyProduction(monthly, 1);
    expect(prod).toHaveLength(12);
    expect(prod[0]).toBeCloseTo(31 * 0.75);
  });

  it('computeModuleAreaEta returns area and efficiency', () => {
    const { area, eta } = computeModuleAreaEta(sampleMod);
    expect(area).toBeCloseTo(1.6);
    expect(eta).toBeCloseTo(6.25);
  });

  it('computeArraySizing returns expected sizing values', () => {
    const consumo = 3650; // 10 kWh/day average
    const hsp = 5;
    const tmax = 40;
    const res = computeArraySizing(consumo, hsp, tmax, sampleMod);
    expect(res.N).toBe(27);
    expect(res.P_stc_kW).toBeCloseTo(2.7);
    expect(res.pct_calor).toBeCloseTo(-6.0, 1);
  });

  it('computeProtectionValues returns expected protection numbers', () => {
    const res = computeProtectionValues(sampleMod, sampleInv, true, 40);
    expect(res.I_dc_design).toBeCloseTo(sampleMod.isc_stc * 1.56);
    expect(res.factor).toBeCloseTo(0.91);
    expect(res.AWG_dc).toBe('14 AWG');
    expect(res.OCPD_dc).toBe(15);
  });

});
