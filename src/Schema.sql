-- ============================================
-- PV CALCULATOR DATABASE SCHEMA
-- Clean Production Version
-- MySQL 8.4+
-- ============================================

-- ============================================
-- 1️⃣ MANUFACTURERS
-- ============================================

CREATE TABLE IF NOT EXISTS manufacturers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================
-- 2️⃣ PV MODULES
-- ============================================

CREATE TABLE IF NOT EXISTS pv_modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    manufacturer_id INT NOT NULL,
    model VARCHAR(150) NOT NULL,
    technology ENUM('Monocrystalline','Polycrystalline','Thin Film','Other') NOT NULL,

    pmax_stc DECIMAL(6,2) NOT NULL,
    voc_stc DECIMAL(6,2) NOT NULL,
    isc_stc DECIMAL(6,2) NOT NULL,
    vmpp_stc DECIMAL(6,2) NOT NULL,
    imp_stc DECIMAL(6,2) NOT NULL,

    temp_coeff_voc DECIMAL(6,4) NOT NULL,
    temp_coeff_pmax DECIMAL(6,4) NOT NULL,
    noct DECIMAL(4,1) NOT NULL DEFAULT 45.0,

    length_m DECIMAL(5,3) NOT NULL,
    width_m DECIMAL(5,3) NOT NULL,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_pv_manufacturer
        FOREIGN KEY (manufacturer_id)
        REFERENCES manufacturers(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================
-- 3️⃣ INVERTERS
-- ============================================

CREATE TABLE IF NOT EXISTS inverters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    manufacturer_id INT NOT NULL,
    model VARCHAR(150) NOT NULL,

    pmax_dc_input DECIMAL(8,2) NOT NULL,
    max_dc_voltage DECIMAL(6,2) NOT NULL,
    -- Shared MPPT voltage window — all MPPT inputs use the same voltage range
    mppt_voltage_min DECIMAL(6,2) NOT NULL,
    mppt_voltage_max DECIMAL(6,2) NOT NULL,
    startup_voltage DECIMAL(6,2) NOT NULL,

    nominal_ac_power DECIMAL(8,2) NOT NULL,
    ac_voltage_nominal DECIMAL(6,2) NOT NULL,
    phase_type ENUM('Single Phase','Split Phase','Three Phase') NOT NULL,
    efficiency_weighted DECIMAL(5,2) NOT NULL,
    -- Optional aggregate string cap when the datasheet states a total count
    -- rather than (or in addition to) per-MPPT caps. NULL = no aggregate cap.
    max_total_strings TINYINT UNSIGNED NULL DEFAULT NULL,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_inverter_manufacturer
        FOREIGN KEY (manufacturer_id)
        REFERENCES manufacturers(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================
-- 3b INVERTER MPPT GROUPS
-- ============================================
-- Per-group current ratings and parallel-string capacity.
-- Simple inverters (all MPPTs identical) have one row here.
-- High-power inverters with heterogeneous MPPT inputs have one row per group.
-- Voltage range is shared across all groups (stored on the inverters table).

CREATE TABLE IF NOT EXISTS inverter_mppt_groups (
    id                        INT AUTO_INCREMENT PRIMARY KEY,
    inverter_id               INT NOT NULL,
    group_label               VARCHAR(30) NOT NULL,
    -- Number of physical MPPT inputs that share identical current ratings in this group
    mppt_count                TINYINT UNSIGNED NOT NULL DEFAULT 1,
    -- Maximum parallel strings the inverter hardware allows per single MPPT input.
    -- NULL = no hard hardware limit per MPPT; effective capacity is current-limited.
    max_strings_per_mppt      TINYINT UNSIGNED NULL DEFAULT NULL,
    -- Per-MPPT-input current limits (independent of number of parallel strings)
    max_input_current         DECIMAL(6,2) NOT NULL,
    max_short_circuit_current DECIMAL(6,2) NOT NULL,

    CONSTRAINT fk_mppt_group_inverter
        FOREIGN KEY (inverter_id)
        REFERENCES inverters(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT uq_mppt_group_label
        UNIQUE (inverter_id, group_label)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================
-- 4️⃣ CLIMATOLOGY LOCATIONS
-- ============================================

CREATE TABLE IF NOT EXISTS climatology_locations (
    id INT AUTO_INCREMENT PRIMARY KEY,

    latitude DECIMAL(5,2) NOT NULL,
    longitude DECIMAL(5,2) NOT NULL,

    absolute_min_temp DECIMAL(5,2) NOT NULL,
    absolute_max_temp DECIMAL(5,2) NOT NULL,

    data_source VARCHAR(50) DEFAULT 'NASA POWER',
    last_updated DATETIME DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT unique_coordinates UNIQUE (latitude, longitude)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================
-- 5️⃣ CLIMATOLOGY MONTHLY DATA
-- ============================================

CREATE TABLE IF NOT EXISTS climatology_monthly (
    id INT AUTO_INCREMENT PRIMARY KEY,
    location_id INT NOT NULL,
    month TINYINT NOT NULL,

    ghi_kwh_m2_day DECIMAL(6,3) NOT NULL,
    dni_kwh_m2_day DECIMAL(6,3) NOT NULL DEFAULT 0,
    dhi_kwh_m2_day DECIMAL(6,3) NOT NULL DEFAULT 0,
    sun_hours DECIMAL(4,2) NOT NULL DEFAULT 0,
    t2m_avg DECIMAL(5,2) NOT NULL,
    t2m_max DECIMAL(5,2) NOT NULL,
    t2m_min DECIMAL(5,2) NOT NULL,
    ws10m   DECIMAL(4,2) NOT NULL DEFAULT 0,

    CONSTRAINT fk_climate_location
        FOREIGN KEY (location_id)
        REFERENCES climatology_locations(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT unique_location_month
        UNIQUE (location_id, month),

    CONSTRAINT chk_month
        CHECK (month BETWEEN 1 AND 12)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;