-- =============================================================================
--  SEED DATA — app_db
--  Safe to run multiple times: manufacturers use INSERT IGNORE.
--  Modules and inverters are plain INSERTs; run once or truncate first.
-- =============================================================================

USE app_db;

-- =============================================================================
--  1. MANUFACTURERS
--  Growatt and JA Solar already exist — INSERT IGNORE skips duplicates.
-- =============================================================================

INSERT IGNORE INTO manufacturers (name) VALUES
    ('Growatt'),
    ('JA Solar'),
    ('Canadian Solar'),
    ('LONGi Solar'),
    ('SMA Solar'),
    ('SolarEdge'),
    ('Fronius'),
    ('Huawei');


-- =============================================================================
--  2. PV MODULES
--  All electrical values are at STC (1000 W/m², 25 °C, AM 1.5).
--  temp_coeff_voc / temp_coeff_pmax are stored as % per °C (e.g. -0.2800).
-- =============================================================================

-- ── JA Solar ─────────────────────────────────────────────────────────────────

INSERT INTO pv_modules
    (manufacturer_id, model, technology,
     pmax_stc, voc_stc, isc_stc, vmpp_stc, imp_stc,
     temp_coeff_voc, temp_coeff_pmax,
     length_m, width_m)
VALUES
    ((SELECT id FROM manufacturers WHERE name = 'JA Solar'),
     'JAM72S30-545/MR', 'Monocrystalline',
     545.00, 49.32, 13.97, 41.35, 13.18,
     -0.2800, -0.3500,
     2.28, 1.13),

    ((SELECT id FROM manufacturers WHERE name = 'JA Solar'),
     'JAM72S30-570/MR', 'Monocrystalline',
     570.00, 50.70, 14.20, 42.50, 13.42,
     -0.2800, -0.3500,
     2.28, 1.13),

    ((SELECT id FROM manufacturers WHERE name = 'JA Solar'),
     'JAM54S30-415/MR', 'Monocrystalline',
     415.00, 41.56, 12.72, 34.74, 11.95,
     -0.2800, -0.3500,
     1.72, 1.13),

    ((SELECT id FROM manufacturers WHERE name = 'JA Solar'),
     'JAM72D40-580/LB', 'Monocrystalline',
     580.00, 51.40, 14.37, 43.10, 13.46,
     -0.2600, -0.3400,
     2.38, 1.10);


-- ── Canadian Solar ────────────────────────────────────────────────────────────

INSERT INTO pv_modules
    (manufacturer_id, model, technology,
     pmax_stc, voc_stc, isc_stc, vmpp_stc, imp_stc,
     temp_coeff_voc, temp_coeff_pmax,
     length_m, width_m)
VALUES
    ((SELECT id FROM manufacturers WHERE name = 'Canadian Solar'),
     'CS6R-410MS', 'Monocrystalline',
     410.00, 41.50, 12.55, 34.60, 11.85,
     -0.2800, -0.3400,
     1.72, 1.13),

    ((SELECT id FROM manufacturers WHERE name = 'Canadian Solar'),
     'CS6R-440MS', 'Monocrystalline',
     440.00, 42.90, 13.00, 35.90, 12.27,
     -0.2800, -0.3400,
     1.76, 1.13),

    ((SELECT id FROM manufacturers WHERE name = 'Canadian Solar'),
     'CS7N-655MB-AG', 'Monocrystalline',
     655.00, 57.20, 14.55, 47.78, 13.70,
     -0.2500, -0.3400,
     2.47, 1.13),

    ((SELECT id FROM manufacturers WHERE name = 'Canadian Solar'),
     'CS6W-550MS', 'Monocrystalline',
     550.00, 49.80, 14.02, 41.80, 13.16,
     -0.2700, -0.3500,
     2.28, 1.13);


-- ── LONGi Solar ───────────────────────────────────────────────────────────────

INSERT INTO pv_modules
    (manufacturer_id, model, technology,
     pmax_stc, voc_stc, isc_stc, vmpp_stc, imp_stc,
     temp_coeff_voc, temp_coeff_pmax,
     length_m, width_m)
VALUES
    ((SELECT id FROM manufacturers WHERE name = 'LONGi Solar'),
     'LR5-72HIH-555M', 'Monocrystalline',
     555.00, 50.00, 14.03, 42.00, 13.22,
     -0.2700, -0.3400,
     2.26, 1.13),

    ((SELECT id FROM manufacturers WHERE name = 'LONGi Solar'),
     'LR5-72HIH-580M', 'Monocrystalline',
     580.00, 51.40, 14.40, 43.38, 13.37,
     -0.2700, -0.3400,
     2.26, 1.13),

    ((SELECT id FROM manufacturers WHERE name = 'LONGi Solar'),
     'LR4-60HIH-375M', 'Monocrystalline',
     375.00, 41.30, 11.44, 34.30, 10.93,
     -0.2800, -0.3600,
     1.76, 1.05);


-- =============================================================================
--  3. INVERTERS
--  Columns: manufacturer_id, model,
--           pmax_dc_input, max_dc_voltage,
--           mppt_voltage_min, mppt_voltage_max, startup_voltage,
--           max_input_current_per_mppt, max_short_circuit_current,
--           nominal_ac_power, ac_voltage_nominal, phase_type, efficiency_weighted,
--           mppt_count
-- =============================================================================

-- ── Growatt ───────────────────────────────────────────────────────────────────

INSERT INTO inverters
    (manufacturer_id, model,
     pmax_dc_input, max_dc_voltage,
     mppt_voltage_min, mppt_voltage_max, startup_voltage,
     nominal_ac_power, ac_voltage_nominal, phase_type, efficiency_weighted)
VALUES
    ((SELECT id FROM manufacturers WHERE name = 'Growatt'),
     'MIN 3000TL-X',
     3600.00, 600.00,
     80.00, 500.00, 100.00,
     3000.00, 230.00, 'Single Phase', 97.00),

    ((SELECT id FROM manufacturers WHERE name = 'Growatt'),
     'MIN 6000TL-X',
     7200.00, 600.00,
     80.00, 500.00, 100.00,
     6000.00, 230.00, 'Single Phase', 97.00),

    ((SELECT id FROM manufacturers WHERE name = 'Growatt'),
     'MID 15KTL3-X',
     18750.00, 1000.00,
     200.00, 800.00, 250.00,
     15000.00, 400.00, 'Three Phase', 98.00),

    ((SELECT id FROM manufacturers WHERE name = 'Growatt'),
     'MID 25KTL3-X',
     30000.00, 1000.00,
     200.00, 800.00, 250.00,
     25000.00, 400.00, 'Three Phase', 98.00);


-- ── SMA Solar ─────────────────────────────────────────────────────────────────

INSERT INTO inverters
    (manufacturer_id, model,
     pmax_dc_input, max_dc_voltage,
     mppt_voltage_min, mppt_voltage_max, startup_voltage,
     nominal_ac_power, ac_voltage_nominal, phase_type, efficiency_weighted)
VALUES
    ((SELECT id FROM manufacturers WHERE name = 'SMA Solar'),
     'Sunny Boy 3.0',
     4500.00, 600.00,
     50.00, 550.00, 80.00,
     3000.00, 230.00, 'Single Phase', 97.00),

    ((SELECT id FROM manufacturers WHERE name = 'SMA Solar'),
     'Sunny Boy 5.0',
     7500.00, 600.00,
     50.00, 550.00, 80.00,
     5000.00, 230.00, 'Single Phase', 97.20),

    ((SELECT id FROM manufacturers WHERE name = 'SMA Solar'),
     'Sunny Tripower 10.0',
     15000.00, 1000.00,
     150.00, 800.00, 188.00,
     10000.00, 400.00, 'Three Phase', 98.00),

    ((SELECT id FROM manufacturers WHERE name = 'SMA Solar'),
     'Sunny Tripower 25000TL',
     30000.00, 1000.00,
     150.00, 800.00, 188.00,
     25000.00, 400.00, 'Three Phase', 98.40);


-- ── SolarEdge ────────────────────────────────────────────────────────────────

INSERT INTO inverters
    (manufacturer_id, model,
     pmax_dc_input, max_dc_voltage,
     mppt_voltage_min, mppt_voltage_max, startup_voltage,
     nominal_ac_power, ac_voltage_nominal, phase_type, efficiency_weighted)
VALUES
    ((SELECT id FROM manufacturers WHERE name = 'SolarEdge'),
     'SE5K-RWS',
     6750.00, 480.00,
     100.00, 400.00, 120.00,
     4999.00, 230.00, 'Single Phase', 99.20),

    ((SELECT id FROM manufacturers WHERE name = 'SolarEdge'),
     'SE10K-RWS',
     13500.00, 480.00,
     100.00, 400.00, 120.00,
     9999.00, 230.00, 'Single Phase', 99.20),

    ((SELECT id FROM manufacturers WHERE name = 'SolarEdge'),
     'SE17K',
     20400.00, 1000.00,
     200.00, 800.00, 240.00,
     17000.00, 400.00, 'Three Phase', 98.30);


-- ── Fronius ───────────────────────────────────────────────────────────────────

INSERT INTO inverters
    (manufacturer_id, model,
     pmax_dc_input, max_dc_voltage,
     mppt_voltage_min, mppt_voltage_max, startup_voltage,
     nominal_ac_power, ac_voltage_nominal, phase_type, efficiency_weighted)
VALUES
    ((SELECT id FROM manufacturers WHERE name = 'Fronius'),
     'Primo 5.0-1',
     7500.00, 600.00,
     80.00, 600.00, 100.00,
     5000.00, 230.00, 'Single Phase', 97.60),

    ((SELECT id FROM manufacturers WHERE name = 'Fronius'),
     'Symo 10.0-3-M',
     15000.00, 800.00,
     150.00, 800.00, 200.00,
     10000.00, 400.00, 'Three Phase', 98.00),

    ((SELECT id FROM manufacturers WHERE name = 'Fronius'),
     'Tauro Eco 15.0-3-D',
     22500.00, 1000.00,
     200.00, 800.00, 200.00,
     15000.00, 400.00, 'Three Phase', 98.20);


-- ── Huawei ────────────────────────────────────────────────────────────────────

INSERT INTO inverters
    (manufacturer_id, model,
     pmax_dc_input, max_dc_voltage,
     mppt_voltage_min, mppt_voltage_max, startup_voltage,
     nominal_ac_power, ac_voltage_nominal, phase_type, efficiency_weighted)
VALUES
    ((SELECT id FROM manufacturers WHERE name = 'Huawei'),
     'SUN2000-5KTL-L1',
     6500.00, 600.00,
     90.00, 560.00, 120.00,
     5000.00, 230.00, 'Single Phase', 97.60),

    ((SELECT id FROM manufacturers WHERE name = 'Huawei'),
     'SUN2000-12KTL-M2',
     18000.00, 1100.00,
     160.00, 1000.00, 200.00,
     12000.00, 400.00, 'Three Phase', 98.60),

    ((SELECT id FROM manufacturers WHERE name = 'Huawei'),
     'SUN2000-30KTL-M3',
     40000.00, 1100.00,
     200.00, 1000.00, 250.00,
     30000.00, 400.00, 'Three Phase', 98.80);


-- =============================================================================
--  4. INVERTER MPPT GROUPS
--  One row per group of identically-rated MPPT inputs.
--  Simple inverters (all MPPTs equal) get a single group.
--  rule: max_strings_per_mppt = 2 when max_input_current >= 25 A, else 1.
--  Fronius Tauro Eco 15.0-3-D is the first example of a heterogeneous inverter
--  (MPP1: 33 A / 49.5 A,  MPP2: 25 A / 37.5 A).
-- =============================================================================

INSERT INTO inverter_mppt_groups
    (inverter_id, group_label, mppt_count, max_strings_per_mppt,
     max_input_current, max_short_circuit_current)
VALUES
    -- ── Growatt ──────────────────────────────────────────────────────────────
    ((SELECT id FROM inverters WHERE model = 'MIN 3000TL-X'),    'MPP1',   1, 1, 11.00, 16.50),
    ((SELECT id FROM inverters WHERE model = 'MIN 6000TL-X'),    'MPP1-2', 2, 1, 12.50, 18.75),
    ((SELECT id FROM inverters WHERE model = 'MID 15KTL3-X'),    'MPP1-3', 3, 1, 20.00, 30.00),
    ((SELECT id FROM inverters WHERE model = 'MID 25KTL3-X'),    'MPP1-4', 4, 2, 26.00, 39.00),

    -- ── SMA Solar ────────────────────────────────────────────────────────────
    ((SELECT id FROM inverters WHERE model = 'Sunny Boy 3.0'),          'MPP1-2', 2, 1, 10.00, 15.00),
    ((SELECT id FROM inverters WHERE model = 'Sunny Boy 5.0'),          'MPP1-2', 2, 1, 12.50, 20.00),
    ((SELECT id FROM inverters WHERE model = 'Sunny Tripower 10.0'),    'MPP1-3', 3, 1, 12.00, 20.00),
    ((SELECT id FROM inverters WHERE model = 'Sunny Tripower 25000TL'), 'MPP1-3', 3, 2, 27.00, 40.50),

    -- ── SolarEdge ─────────────────────────────────────────────────────────────
    ((SELECT id FROM inverters WHERE model = 'SE5K-RWS'),  'MPP1',   1, 1, 18.00, 27.00),
    ((SELECT id FROM inverters WHERE model = 'SE10K-RWS'), 'MPP1',   1, 2, 27.50, 41.25),
    ((SELECT id FROM inverters WHERE model = 'SE17K'),     'MPP1-2', 2, 2, 32.50, 48.75),

    -- ── Fronius ───────────────────────────────────────────────────────────────
    ((SELECT id FROM inverters WHERE model = 'Primo 5.0-1'),        'MPP1-2', 2, 1, 12.50, 18.75),
    ((SELECT id FROM inverters WHERE model = 'Symo 10.0-3-M'),      'MPP1-2', 2, 2, 27.00, 40.50),
    -- Tauro Eco 15.0-3-D: two groups with different current ratings
    ((SELECT id FROM inverters WHERE model = 'Tauro Eco 15.0-3-D'), 'MPP1',   1, 2, 33.00, 49.50),
    ((SELECT id FROM inverters WHERE model = 'Tauro Eco 15.0-3-D'), 'MPP2',   1, 1, 25.00, 37.50),

    -- ── Huawei ────────────────────────────────────────────────────────────────
    ((SELECT id FROM inverters WHERE model = 'SUN2000-5KTL-L1'),  'MPP1-2', 2, 1, 13.00, 19.50),
    ((SELECT id FROM inverters WHERE model = 'SUN2000-12KTL-M2'), 'MPP1-3', 3, 1, 22.00, 33.00),
    ((SELECT id FROM inverters WHERE model = 'SUN2000-30KTL-M3'), 'MPP1-4', 4, 2, 26.00, 39.00);
