-- ============================================
-- TABLA DE DOCUMENTOS PARA CLIENTES (MySQL 5.x compatible)
-- AseguraloCR.com
-- ============================================

CREATE TABLE IF NOT EXISTS `client_documents` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `client_id` INT NOT NULL,
    `policy_id` INT NULL COMMENT 'Opcional: vincular a una poliza especifica',
    `tipo` ENUM('poliza', 'cotizacion', 'factura', 'comprobante', 'contrato', 'anexo', 'otro') NOT NULL DEFAULT 'otro',
    `nombre` VARCHAR(255) NOT NULL COMMENT 'Nombre descriptivo del documento',
    `nombre_archivo` VARCHAR(255) NOT NULL COMMENT 'Nombre original del archivo',
    `ruta_archivo` VARCHAR(500) NOT NULL COMMENT 'Ruta en el servidor',
    `mime_type` VARCHAR(100) NULL,
    `tamano_bytes` INT NULL,
    `descripcion` TEXT NULL,
    `visible_cliente` TINYINT(1) DEFAULT 1 COMMENT 'Si el cliente puede ver/descargar',
    `created_by` INT NULL COMMENT 'Admin que subio el documento',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_client` (`client_id`),
    INDEX `idx_policy` (`policy_id`),
    INDEX `idx_tipo` (`tipo`),
    INDEX `idx_visible` (`visible_cliente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Agregar foreign keys solo si no existen (ignorar error si ya existen)
-- Nota: En MySQL 5.x no hay IF NOT EXISTS para constraints
