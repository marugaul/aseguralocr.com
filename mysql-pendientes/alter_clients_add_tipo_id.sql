-- ============================================
-- AGREGAR CAMPO tipo_id A TABLA CLIENTS
-- AseguraloCR.com
-- ============================================
-- Ejecutar en phpMyAdmin o automáticamente por cron
-- ============================================

-- Agregar campo tipo_id si no existe
ALTER TABLE `clients`
ADD COLUMN IF NOT EXISTS `tipo_id` VARCHAR(20) DEFAULT 'cedula' AFTER `id`,
ADD COLUMN IF NOT EXISTS `direccion` TEXT NULL AFTER `telefono`,
ADD COLUMN IF NOT EXISTS `provincia` VARCHAR(100) NULL AFTER `direccion`,
ADD COLUMN IF NOT EXISTS `canton` VARCHAR(100) NULL AFTER `provincia`,
ADD COLUMN IF NOT EXISTS `distrito` VARCHAR(100) NULL AFTER `canton`,
ADD COLUMN IF NOT EXISTS `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

-- Agregar índice al correo para búsquedas rápidas
ALTER TABLE `clients` ADD INDEX IF NOT EXISTS `idx_clients_correo` (`correo`);
