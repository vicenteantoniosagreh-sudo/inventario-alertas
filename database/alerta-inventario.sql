-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 31-03-2026 a las 18:20:16
-- Versión del servidor: 10.4.25-MariaDB
-- Versión de PHP: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `alerta-inventario`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sku` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 0,
  `fecha_vencimiento` date NOT NULL,
  `fecha_elaboracion` date DEFAULT NULL,
  `valor_neto` decimal(10,2) DEFAULT 0.00,
  `impuesto` decimal(5,2) DEFAULT 0.00,
  `categoria` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `nombre`, `sku`, `cantidad`, `fecha_vencimiento`, `categoria`, `created_at`) VALUES
(1, 'Croquetas Premium Adulto 5kg', 'PET-001', 14, '2026-03-17', 'Alimentos para perros', '2026-03-27 14:31:29'),
(2, 'Lata de Pate Salmon 400g', 'PET-002', 20, '2026-03-15', 'Alimentos para gatos', '2026-03-27 14:31:29'),
(4, 'Medicina antipulgas pipeta', 'PET-004', 12, '2026-03-16', 'Salud y tratamientos', '2026-03-27 14:31:29'),
(5, 'Snack dental pollo', 'PET-005', 18, '2026-03-17', 'Snacks', '2026-03-27 14:31:29'),
(6, 'Croquetas Junior 3kg', 'PET-006', 20, '2026-03-30', 'Alimentos para perros', '2026-03-27 14:31:29'),
(7, 'Croquetas Indoor 2.5kg', 'PET-007', 16, '2026-04-01', 'Alimentos para gatos', '2026-03-27 14:31:29'),
(8, 'Cama acolchada pequeña', 'PET-008', 9, '2026-03-31', 'Accesorios para mascotas', '2026-03-27 14:31:29'),
(9, 'Vitaminas multivitamínicas', 'PET-009', 12, '2026-04-03', 'Salud y tratamientos', '2026-03-27 14:31:29'),
(10, 'Snack suave pavo', 'PET-010', 22, '2026-04-02', 'Snacks', '2026-03-27 14:31:29'),
(11, 'Croquetas senior 7kg', 'PET-011', 18, '2026-06-27', 'Alimentos para perros', '2026-03-27 14:31:29'),
(12, 'Alimento húmedo pollo 12x85g', 'PET-012', 30, '2026-09-27', 'Alimentos para gatos', '2026-03-27 14:31:29'),
(13, 'Correa retráctil 5m', 'PET-013', 25, '2027-03-27', 'Accesorios para mascotas', '2026-03-27 14:31:29'),
(14, 'Cepillo de cerdas suaves', 'PET-014', 40, '2026-06-27', 'Higiene y cuidado', '2026-03-27 14:31:29'),
(15, 'Bolsas de desechos 200 un', 'PET-015', 60, '2026-09-27', 'Accesorios para perros', '2026-03-27 14:31:29'),
(16, 'Arena silvestre 5kg', 'PET-016', 35, '2027-03-27', 'Accesorios para gatos', '2026-03-27 14:31:29'),
(17, 'Shampoo antiparásitos 250ml', 'PET-017', 14, '2026-09-27', 'Higiene y cuidado', '2026-03-27 14:31:29'),
(18, 'Juguete mordedor resistente', 'PET-018', 28, '2027-03-27', 'Juguetes', '2026-03-27 14:31:29'),
(19, 'Kit de transporte pequeño', 'PET-019', 10, '2027-03-27', 'Accesorios para mascotas', '2026-03-27 14:31:29'),
(20, 'Alimento complementario multinutrición', 'PET-020', 38, '2026-09-27', 'Alimentos para perros', '2026-03-27 14:31:29'),
(22, 'alimento de perro', 'SKU-69c6b36d07277', 10, '2026-03-27', 'General', '2026-03-27 16:42:21');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
