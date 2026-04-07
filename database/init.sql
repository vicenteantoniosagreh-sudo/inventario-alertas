-- ============================================================
-- StockAlert - Script de Inicialización de Base de Datos
-- Ejecutar con: mysql -u root < database/init.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS `alerta-inventario`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `alerta-inventario`;

-- ============================================================
-- TABLA: usuarios
-- ============================================================
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id`                int(11)      NOT NULL AUTO_INCREMENT,
  `usuario`           varchar(100) NOT NULL,
  `pass_hash`         varchar(255) NOT NULL,
  `rol`               varchar(50)  DEFAULT 'admin',
  `token`             varchar(255) DEFAULT NULL,
  `token_expira`      datetime     DEFAULT NULL,
  `intentos_fallidos` int(11)      NOT NULL DEFAULT 0,
  `bloqueado_hasta`   datetime     DEFAULT NULL,
  `creado_en`         timestamp    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usuario` (`usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: productos
-- ============================================================
CREATE TABLE IF NOT EXISTS `productos` (
  `id`               int(11)       NOT NULL AUTO_INCREMENT,
  `usuario_id`       int(11)       NULL DEFAULT NULL,
  `nombre`           varchar(255)  NOT NULL,
  `sku`              varchar(100)  NOT NULL,
  `cantidad`         int(11)       NOT NULL DEFAULT 0,
  `fecha_vencimiento` date         NOT NULL,
  `fecha_elaboracion` date         DEFAULT NULL,
  `valor_neto`       decimal(10,2) DEFAULT 0.00,
  `impuesto`         decimal(5,2)  DEFAULT 0.00,
  `categoria`        varchar(120)  NOT NULL DEFAULT 'General',
  `creado_en`        timestamp     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sku` (`sku`),
  CONSTRAINT `fk_producto_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos de prueba (sin usuario_id, visibles solo en importación inicial)
INSERT INTO `productos` (`id`, `nombre`, `sku`, `cantidad`, `fecha_vencimiento`, `categoria`) VALUES
(1, 'Croquetas Premium Adulto 5kg', 'PET-001', 14, '2026-03-17', 'Alimentos para perros'),
(2, 'Lata de Pate Salmon 400g', 'PET-002', 20, '2026-03-15', 'Alimentos para gatos'),
(4, 'Medicina antipulgas pipeta', 'PET-004', 12, '2026-03-16', 'Salud y tratamientos'),
(5, 'Snack dental pollo', 'PET-005', 18, '2026-03-17', 'Snacks'),
(6, 'Croquetas Junior 3kg', 'PET-006', 20, '2026-03-30', 'Alimentos para perros'),
(7, 'Croquetas Indoor 2.5kg', 'PET-007', 16, '2026-04-01', 'Alimentos para gatos'),
(8, 'Cama acolchada pequeña', 'PET-008', 9, '2026-03-31', 'Accesorios para mascotas'),
(9, 'Vitaminas multivitamínicas', 'PET-009', 12, '2026-04-03', 'Salud y tratamientos'),
(10, 'Snack suave pavo', 'PET-010', 22, '2026-04-02', 'Snacks'),
(11, 'Croquetas senior 7kg', 'PET-011', 18, '2026-06-27', 'Alimentos para perros'),
(12, 'Alimento húmedo pollo 12x85g', 'PET-012', 30, '2026-09-27', 'Alimentos para gatos'),
(13, 'Correa retráctil 5m', 'PET-013', 25, '2027-03-27', 'Accesorios para mascotas'),
(14, 'Cepillo de cerdas suaves', 'PET-014', 40, '2026-06-27', 'Higiene y cuidado'),
(15, 'Bolsas de desechos 200 un', 'PET-015', 60, '2026-09-27', 'Accesorios para perros'),
(16, 'Arena silvestre 5kg', 'PET-016', 35, '2027-03-27', 'Accesorios para gatos'),
(17, 'Shampoo antiparásitos 250ml', 'PET-017', 14, '2026-09-27', 'Higiene y cuidado'),
(18, 'Juguete mordedor resistente', 'PET-018', 28, '2027-03-27', 'Juguetes'),
(19, 'Kit de transporte pequeño', 'PET-019', 10, '2027-03-27', 'Accesorios para mascotas'),
(20, 'Alimento complementario multinutrición', 'PET-020', 38, '2026-09-27', 'Alimentos para perros'),
(22, 'alimento de perro', 'SKU-69c6b36d07277', 10, '2026-03-27', 'General');

-- ============================================================
-- TABLA: logs_auditoria
-- ============================================================
CREATE TABLE IF NOT EXISTS `logs_auditoria` (
  `id`        int(11)      NOT NULL AUTO_INCREMENT,
  `evento`    varchar(50)  NOT NULL,
  `usuario`   varchar(100) NOT NULL,
  `ip`        varchar(50)  NOT NULL,
  `detalles`  text         DEFAULT NULL,
  `creado_en` timestamp    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
