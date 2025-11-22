-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 10, 2025 at 10:31 PM
-- Server version: 8.0.44
-- PHP Version: 8.4.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `asegural_aseguralocr`
--

-- --------------------------------------------------------

--
-- Table structure for table `cotizaciones`
--

CREATE TABLE `cotizaciones` (
  `id` int UNSIGNED NOT NULL,
  `referencia` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `client_id` int UNSIGNED NOT NULL,
  `monto` decimal(14,2) DEFAULT NULL,
  `moneda` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT 'colones',
  `estado` enum('pendiente','aceptada','rechazada') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `payload` json DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cotizaciones`
--

INSERT INTO `cotizaciones` (`id`, `referencia`, `client_id`, `monto`, `moneda`, `estado`, `payload`, `created_at`) VALUES
(1, 'COT-20251107233116-45e260', 1, 45000000.00, 'colones', 'pendiente', '{\"monto\": \"45000000\", \"moneda\": \"colones\", \"detalle\": {\"direccion\": \"80 MTS NORTE DE LA ESCUELA ESTADOS UNIDOS DE AMERICA, SAN JOAQUIN DE FLORES, HEREDIA.\", \"tipoPropiedad\": \"casa\"}}', '2025-11-08 00:31:16'),
(2, 'COT-20251107235510-2d69b6', 1, 45000000.00, 'colones', 'pendiente', '{\"monto\": \"45000000\", \"moneda\": \"colones\", \"detalle\": {\"direccion\": \"80 MTS NORTE DE LA ESCUELA ESTADOS UNIDOS DE AMERICA, SAN JOAQUIN DE FLORES, HEREDIA.\", \"tipoPropiedad\": \"casa\"}}', '2025-11-08 00:55:10'),
(3, 'COT-20251108001545-f1f3e1', 1, 45000000.00, 'colones', 'pendiente', '{\"monto\": \"45000000\", \"moneda\": \"colones\", \"detalle\": {\"direccion\": \"80 MTS NORTE DE LA ESCUELA ESTADOS UNIDOS DE AMERICA, SAN JOAQUIN DE FLORES, HEREDIA.\", \"tipoPropiedad\": \"casa\"}}', '2025-11-08 01:15:45'),
(4, 'COT-20251108151325-e0dd5f', 1, 45000000.00, 'colones', 'pendiente', '{\"monto\": \"45000000\", \"moneda\": \"colones\", \"detalle\": {\"direccion\": \"80 MTS NORTE DE LA ESCUELA ESTADOS UNIDOS DE AMERICA, SAN JOAQUIN DE FLORES, HEREDIA.\", \"tipoPropiedad\": \"casa\"}}', '2025-11-08 16:13:25'),
(5, 'COT-20251108151644-d248fe', 1, 45000000.00, 'colones', 'pendiente', '{\"monto\": \"45000000\", \"moneda\": \"colones\", \"detalle\": {\"direccion\": \"80 MTS NORTE DE LA ESCUELA ESTADOS UNIDOS DE AMERICA, SAN JOAQUIN DE FLORES, HEREDIA.\", \"tipoPropiedad\": \"casa\"}}', '2025-11-08 16:16:44'),
(6, 'COT-20251108151929-933ac6', 1, 45000000.00, 'colones', 'pendiente', '{\"monto\": \"45000000\", \"moneda\": \"colones\", \"detalle\": {\"direccion\": \"80 MTS NORTE DE LA ESCUELA ESTADOS UNIDOS DE AMERICA, SAN JOAQUIN DE FLORES, HEREDIA.\", \"tipoPropiedad\": \"casa\"}}', '2025-11-08 16:19:29'),
(7, 'COT-20251108153246-172753', 1, 45000000.00, 'colones', 'pendiente', '{\"monto\": \"45000000\", \"moneda\": \"colones\", \"detalle\": {\"direccion\": \"80 MTS NORTE DE LA ESCUELA ESTADOS UNIDOS DE AMERICA, SAN JOAQUIN DE FLORES, HEREDIA.\", \"tipoPropiedad\": \"casa\"}}', '2025-11-08 16:32:46'),
(8, 'COT-20251108153832-083dc2', 1, 45000000.00, 'colones', 'pendiente', '{\"monto\": \"45000000\", \"moneda\": \"colones\", \"detalle\": {\"direccion\": \"80 MTS NORTE DE LA ESCUELA ESTADOS UNIDOS DE AMERICA, SAN JOAQUIN DE FLORES, HEREDIA.\", \"tipoPropiedad\": \"casa\"}}', '2025-11-08 16:38:32'),
(9, 'COT-20251108154449-16d00c', 1, 45000000.00, 'colones', 'pendiente', '{\"monto\": \"45000000\", \"moneda\": \"colones\", \"detalle\": {\"direccion\": \"80 MTS NORTE DE LA ESCUELA ESTADOS UNIDOS DE AMERICA, SAN JOAQUIN DE FLORES, HEREDIA.\", \"tipoPropiedad\": \"casa\"}}', '2025-11-08 16:44:49'),
(10, 'COT-20251108160105-3d8280', 1, 45000000.00, 'colones', 'pendiente', '{\"monto\": \"45000000\", \"moneda\": \"colones\", \"detalle\": {\"direccion\": \"80 MTS NORTE DE LA ESCUELA ESTADOS UNIDOS DE AMERICA, SAN JOAQUIN DE FLORES, HEREDIA.\", \"tipoPropiedad\": \"casa\"}}', '2025-11-08 17:01:05'),
(11, 'COT-20251108161029-9bddac', 1, 45000000.00, 'colones', 'pendiente', '{\"monto\": \"45000000\", \"moneda\": \"colones\", \"detalle\": {\"direccion\": \"80 MTS NORTE DE LA ESCUELA ESTADOS UNIDOS DE AMERICA, SAN JOAQUIN DE FLORES, HEREDIA.\", \"tipoPropiedad\": \"casa\"}}', '2025-11-08 17:10:29'),
(12, 'COT-20251108161537-7e359e', 1, 45000000.00, 'colones', 'pendiente', '{\"monto\": \"45000000\", \"moneda\": \"colones\", \"detalle\": {\"direccion\": \"80 MTS NORTE DE LA ESCUELA ESTADOS UNIDOS DE AMERICA, SAN JOAQUIN DE FLORES, HEREDIA.\", \"tipoPropiedad\": \"casa\"}}', '2025-11-08 17:15:37'),
(13, 'COT-20251108162333-344403', 1, 45000000.00, 'colones', 'pendiente', '{\"monto\": \"45000000\", \"moneda\": \"colones\", \"detalle\": {\"direccion\": \"80 MTS NORTE DE LA ESCUELA ESTADOS UNIDOS DE AMERICA, SAN JOAQUIN DE FLORES, HEREDIA.\", \"tipoPropiedad\": \"casa\"}}', '2025-11-08 17:23:33');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cotizaciones`
--
ALTER TABLE `cotizaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `referencia` (`referencia`),
  ADD KEY `client_id` (`client_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cotizaciones`
--
ALTER TABLE `cotizaciones`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cotizaciones`
--
ALTER TABLE `cotizaciones`
  ADD CONSTRAINT `fk_cotizaciones_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
