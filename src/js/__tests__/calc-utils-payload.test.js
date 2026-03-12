import { describe, it, expect } from 'vitest';
import { buildExportPayloadPure, computeInvChecks } from '../calc-utils-extra.js';

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

const monthly = new Array(12).fill(0).map((_,i) => ({ ghi: 4 + i*0.1 }));

describe('export payload builder and inv checks', () => {
  it('buildExportPayloadPure returns expected structure and energy', () => {
    const cs = {
      module: sampleMod,
      inverter: sampleInv,
      Ns: 3,
      Np: 10,
      N: 30,
      P_stc_kW: 3.0,
      monthly,
    };
    const inputs = { tmin: 10, tmax: 40, hsp: 5, lat: 20, lng: -100, consumo: 5000 };
    const payload = buildExportPayloadPure(cs, inputs);
    expect(payload).toHaveProperty('site');
    expect(payload).toHaveProperty('module');
    expect(payload).toHaveProperty('array');
    expect(payload).toHaveProperty('inverter');
    expect(payload.energy.E_year).toBeCloseTo(cs.P_stc_kW * inputs.hsp * 365 * 0.75);
    expect(payload.monthly).toHaveLength(12);
  });

  it('buildExportPayloadPure includes consumption balances when requested', () => {
    const cs = {
      module: sampleMod,
      inverter: sampleInv,
      Ns: 2,
      Np: 5,
      N: 10,
      P_stc_kW: 1.0,
      monthly,
    };
    const inputs = { showConsumption: true, consumptionPerMonth: new Array(12).fill(100), hsp: 4 };
    const payload = buildExportPayloadPure(cs, inputs);
    expect(payload.monthly[0]).toHaveProperty('consumo');
    expect(payload.monthly[0]).toHaveProperty('balance');
  });

  it('computeInvChecks flags hard and soft fails appropriately', () => {
    const cs = { module: sampleMod, Ns: 5, N: 20, P_stc_kW: 2.0, site: { tmin: 0, tmax: 40 } };
    const invGood = { ...sampleInv, max_dc_voltage: 1000, mppt_voltage_min: 50, mppt_count: 10, max_input_current_per_mppt: 100, max_short_circuit_current: 200, pmax_dc_input: 100000 };
    const good = computeInvChecks(cs, invGood);
    expect(good.anyHardFail).toBe(false);

    const invBad = { ...sampleInv, max_dc_voltage: 100, mppt_count: 1, max_input_current_per_mppt: 1, max_short_circuit_current: 1, pmax_dc_input: 1 };
    const bad = computeInvChecks(cs, invBad);
    expect(bad.anyHardFail).toBe(true);
  });
});
