CREATE DATABASE IF NOT EXISTS pv_calculator;
USE pv_calculator;

-- ================================
-- 1️⃣ MANUFACTURERS
-- ================================

CREATE TABLE manufacturers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ================================
-- 2️⃣ PV MODULES
-- ================================

CREATE TABLE pv_modules (
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

    length_m DECIMAL(4,2) NOT NULL,
    width_m DECIMAL(4,2) NOT NULL,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_pv_manufacturer
        FOREIGN KEY (manufacturer_id)
        REFERENCES manufacturers(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ================================
-- 3️⃣ INVERTERS
-- ================================

CREATE TABLE inverters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    manufacturer_id INT NOT NULL,
    model VARCHAR(150) NOT NULL,

    pmax_dc_input DECIMAL(8,2) NOT NULL,
    max_dc_voltage DECIMAL(6,2) NOT NULL,
    max_input_current DECIMAL(6,2) NOT NULL,
    max_short_circuit_current DECIMAL(6,2) NOT NULL,
    nominal_ac_power DECIMAL(8,2) NOT NULL,

    mppt_count INT NOT NULL,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_inverter_manufacturer
        FOREIGN KEY (manufacturer_id)
        REFERENCES manufacturers(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ================================
-- 4️⃣ CLIMATOLOGY LOCATIONS
-- ================================

CREATE TABLE climatology_locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    latitude DECIMAL(5,2) NOT NULL,
    longitude DECIMAL(5,2) NOT NULL,

    absolute_min_temp DECIMAL(5,2) NOT NULL,
    absolute_max_temp DECIMAL(5,2) NOT NULL,

    data_source VARCHAR(50) DEFAULT 'NASA POWER',
    last_updated DATETIME DEFAULT CURRENT_TIMESTAMP,

    UNIQUE (latitude, longitude)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ================================
-- 5️⃣ CLIMATOLOGY MONTHLY
-- ================================

CREATE TABLE climatology_monthly (
    id INT AUTO_INCREMENT PRIMARY KEY,
    location_id INT NOT NULL,
    month TINYINT NOT NULL,

    ghi_kwh_m2_day DECIMAL(6,3) NOT NULL,
    t2m_avg DECIMAL(5,2) NOT NULL,
    t2m_max DECIMAL(5,2) NOT NULL,
    t2m_min DECIMAL(5,2) NOT NULL,

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