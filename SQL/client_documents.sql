-- ============================================
-- TABLA DE DOCUMENTOS PARA CLIENTES
-- AseguraloCR.com
-- ============================================
-- Ejecutar en phpMyAdmin
-- ============================================

CREATE TABLE IF NOT EXISTS `client_documents` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `client_id` INT NOT NULL,
    `policy_id` INT NULL COMMENT 'Opcional: vincular a una póliza específica',
    `tipo` ENUM('poliza', 'cotizacion', 'factura', 'comprobante', 'contrato', 'anexo', 'otro') NOT NULL DEFAULT 'otro',
    `nombre` VARCHAR(255) NOT NULL COMMENT 'Nombre descriptivo del documento',
    `nombre_archivo` VARCHAR(255) NOT NULL COMMENT 'Nombre original del archivo',
    `ruta_archivo` VARCHAR(500) NOT NULL COMMENT 'Ruta en el servidor',
    `mime_type` VARCHAR(100) NULL,
    `tamano_bytes` INT NULL,
    `descripcion` TEXT NULL,
    `visible_cliente` BOOLEAN DEFAULT TRUE COMMENT 'Si el cliente puede ver/descargar',
    `created_by` INT NULL COMMENT 'Admin que subió el documento',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`policy_id`) REFERENCES `policies`(`id`) ON DELETE SET NULL,
    INDEX `idx_client` (`client_id`),
    INDEX `idx_policy` (`policy_id`),
    INDEX `idx_tipo` (`tipo`),
    INDEX `idx_visible` (`visible_cliente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Documentos subidos para clientes (pólizas, facturas, etc.)';

-- ============================================
-- FIN DEL SCRIPT
-- ============================================
