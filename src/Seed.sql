-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Servidor: db
-- Tiempo de generación: 09-04-2026 a las 16:32:48
-- Versión del servidor: 8.4.8
-- Versión de PHP: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `app_db`
--

--
-- Volcado de datos para la tabla `manufacturers`
--

INSERT INTO `manufacturers` (`id`, `name`, `created_at`) VALUES
(1, 'Fronius', '2026-04-06 08:47:18'),
(2, 'Solarever', '2026-04-06 08:48:50'),
(3, 'Connera', '2026-04-09 09:28:48'),
(4, 'Growatt', '2026-04-09 09:32:35');

--
-- Volcado de datos para la tabla `inverters`
--

INSERT INTO `inverters` (`id`, `manufacturer_id`, `model`, `pmax_dc_input`, `max_dc_voltage`, `mppt_voltage_min`, `mppt_voltage_max`, `startup_voltage`, `nominal_ac_power`, `ac_voltage_nominal`, `phase_type`, `efficiency_weighted`, `created_at`) VALUES
(1, 1, 'Symo Advanced 24.0-3', 31000.00, 1000.00, 200.00, 800.00, 200.00, 24000.00, 480.00, 'Three Phase', 97.50, '2026-04-06 09:06:12'),
(2, 4, 'MIC-2000TL-X2', 3000.00, 500.00, 50.00, 500.00, 50.00, 2000.00, 230.00, 'Single Phase', 97.00, '2026-04-09 09:35:18');

--
-- Volcado de datos para la tabla `inverter_mppt_groups`
--

INSERT INTO `inverter_mppt_groups` (`id`, `inverter_id`, `group_label`, `mppt_count`, `max_strings_per_mppt`, `max_input_current`, `max_short_circuit_current`) VALUES
(5, 1, 'MPPT1', 1, 2, 33.00, 49.50),
(6, 1, 'MPPT2', 1, 1, 25.00, 37.50),
(7, 2, 'MPP1', 1, 1, 16.00, 24.00);

--
-- Volcado de datos para la tabla `pv_modules`
--

INSERT INTO `pv_modules` (`id`, `manufacturer_id`, `model`, `technology`, `pmax_stc`, `voc_stc`, `isc_stc`, `vmpp_stc`, `imp_stc`, `temp_coeff_voc`, `temp_coeff_pmax`, `noct`, `length_m`, `width_m`, `created_at`) VALUES
(1, 2, 'SE-158*158-380M-72', 'Monocrystalline', 380.00, 48.90, 9.75, 40.50, 9.39, -0.2800, -0.3700, 45.0, 1.979, 1.002, '2026-04-06 08:51:42'),
(2, 2, 'SE-182*91-540M-144', 'Monocrystalline', 540.00, 49.50, 13.80, 41.55, 13.00, -0.2700, -0.3500, 45.0, 2.278, 1.133, '2026-04-06 08:58:19'),
(3, 3, 'Connera-450M', 'Monocrystalline', 450.00, 49.30, 11.60, 41.50, 10.85, -0.2710, -0.3250, 45.0, 2.094, 1.038, '2026-04-09 09:32:09');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
