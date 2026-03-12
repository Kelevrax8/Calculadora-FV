import { describe, it, expect } from 'vitest';
import { getCheckStyle, phaseLabelAndColor, verdictBannerFor, computeBalances } from '../calc-utils-extra.js';

describe('UI mapping helpers', () => {
  it('getCheckStyle returns correct classes and text', () => {
    expect(getCheckStyle(true, true)).toEqual({ cardClass: 'card-success', badgeClass: 'badge-success', badgeText: '✓ OK' });
    expect(getCheckStyle(false, true)).toEqual({ cardClass: 'card-danger', badgeClass: 'badge-danger', badgeText: '✗ Falla' });
    expect(getCheckStyle(false, false)).toEqual({ cardClass: 'card-warning', badgeClass: 'badge-warning', badgeText: '⚠ Revisar' });
  });

  it('phaseLabelAndColor maps phase types', () => {
    expect(phaseLabelAndColor('Single Phase')).toEqual({ label: 'Monofásico', color: 'badge-info' });
    expect(phaseLabelAndColor('Three Phase')).toEqual({ label: 'Trifásico', color: 'badge-secondary' });
    expect(phaseLabelAndColor('Split Phase')).toEqual({ label: 'Bifásico', color: 'badge-info' });
  });

  it('verdictBannerFor returns appropriate verdicts', () => {
    expect(verdictBannerFor(true, false).className).toContain('danger');
    expect(verdictBannerFor(false, true).className).toContain('warning');
    expect(verdictBannerFor(false, false).className).toContain('success');
  });

  it('computeBalances computes per-month balances and totals', () => {
    const monthlyProduction = new Array(12).fill(0).map((_,i) => 100 + i);
    const consumption = new Array(12).fill(0).map(() => 50);
    const res = computeBalances(monthlyProduction, consumption);
    expect(res.perMonth[0]).toHaveProperty('production');
    expect(res.perMonth[0]).toHaveProperty('consumo');
    expect(res.perMonth[0]).toHaveProperty('balance');
    expect(res.totalCons).toBeGreaterThan(0);
    expect(res.totalBal).toMatch(/^[+-]?\d+$/);
  });
});
