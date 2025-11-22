-- ============================================
-- SISTEMA DE DASHBOARD PARA CLIENTES
-- AseguraloCR.com
-- ============================================
-- INSTRUCCIONES:
-- 1. Abre phpMyAdmin en tu cPanel
-- 2. Selecciona la base de datos: asegural_aseguralocr
-- 3. Ve a la pestaña "SQL"
-- 4. Copia y pega TODO este archivo
-- 5. Click en "Continuar"
-- ============================================

-- Tabla de clientes (con Google OAuth)
CREATE TABLE IF NOT EXISTS `clients` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `google_id` VARCHAR(255) UNIQUE NULL,
    `email` VARCHAR(255) UNIQUE NOT NULL,
    `nombre_completo` VARCHAR(255) NOT NULL,
    `telefono` VARCHAR(50) NULL,
    `cedula` VARCHAR(50) NULL,
    `fecha_nacimiento` DATE NULL,
    `direccion` TEXT NULL,
    `provincia` VARCHAR(100) NULL,
    `canton` VARCHAR(100) NULL,
    `distrito` VARCHAR(100) NULL,
    `avatar_url` VARCHAR(500) NULL,
    `email_verified` BOOLEAN DEFAULT FALSE,
    `google_access_token` TEXT NULL,
    `google_refresh_token` TEXT NULL,
    `last_login` DATETIME NULL,
    `status` ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_email` (`email`),
    INDEX `idx_google_id` (`google_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Clientes con autenticación Google OAuth';

-- Tabla de pólizas
CREATE TABLE IF NOT EXISTS `policies` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `client_id` INT NOT NULL,
    `submission_id` INT NULL COMMENT 'Link to original submission if exists',
    `numero_poliza` VARCHAR(100) UNIQUE NOT NULL,
    `tipo_seguro` ENUM('hogar', 'auto', 'vida', 'salud', 'otros') NOT NULL,
    `aseguradora` VARCHAR(100) DEFAULT 'INS',
    `coberturas` TEXT NULL COMMENT 'JSON con detalles de coberturas',
    `monto_asegurado` DECIMAL(15,2) NULL,
    `prima_anual` DECIMAL(15,2) NULL,
    `prima_mensual` DECIMAL(15,2) NULL,
    `moneda` ENUM('colones', 'dolares') DEFAULT 'colones',
    `fecha_emision` DATE NOT NULL,
    `fecha_inicio_vigencia` DATE NOT NULL,
    `fecha_fin_vigencia` DATE NOT NULL,
    `status` ENUM('cotizacion', 'vigente', 'por_vencer', 'vencida', 'cancelada', 'renovada') DEFAULT 'vigente',
    `detalles_bien_asegurado` TEXT NULL COMMENT 'Descripción del bien asegurado',
    `notas_admin` TEXT NULL,
    `archivo_poliza_url` VARCHAR(500) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by` INT NULL COMMENT 'Admin user who created this',
    FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE,
    INDEX `idx_client` (`client_id`),
    INDEX `idx_numero_poliza` (`numero_poliza`),
    INDEX `idx_status` (`status`),
    INDEX `idx_vigencia` (`fecha_fin_vigencia`),
    INDEX `idx_policies_client_status` (`client_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Pólizas de seguros emitidas';

-- Tabla de pagos
CREATE TABLE IF NOT EXISTS `payments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `policy_id` INT NOT NULL,
    `monto` DECIMAL(15,2) NOT NULL,
    `moneda` ENUM('colones', 'dolares') DEFAULT 'colones',
    `tipo_pago` ENUM('prima_inicial', 'cuota_mensual', 'cuota_trimestral', 'cuota_semestral', 'cuota_anual', 'renovacion', 'otros') NOT NULL,
    `fecha_vencimiento` DATE NOT NULL,
    `fecha_pago` DATE NULL,
    `status` ENUM('pendiente', 'pagado', 'vencido', 'parcial', 'cancelado') DEFAULT 'pendiente',
    `metodo_pago` VARCHAR(100) NULL COMMENT 'transferencia, tarjeta, efectivo, etc',
    `comprobante_url` VARCHAR(500) NULL,
    `referencia_pago` VARCHAR(255) NULL,
    `notas` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by` INT NULL COMMENT 'Admin user who created this',
    FOREIGN KEY (`policy_id`) REFERENCES `policies`(`id`) ON DELETE CASCADE,
    INDEX `idx_policy` (`policy_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_vencimiento` (`fecha_vencimiento`),
    INDEX `idx_payments_policy_status` (`policy_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Pagos y cuotas de pólizas';

-- Tabla de cotizaciones
CREATE TABLE IF NOT EXISTS `quotes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `client_id` INT NOT NULL,
    `submission_id` INT NULL COMMENT 'Link to submission if exists',
    `numero_cotizacion` VARCHAR(100) UNIQUE NOT NULL,
    `tipo_seguro` ENUM('hogar', 'auto', 'vida', 'salud', 'otros') NOT NULL,
    `coberturas_solicitadas` TEXT NULL COMMENT 'JSON con coberturas solicitadas',
    `monto_estimado` DECIMAL(15,2) NULL,
    `prima_estimada` DECIMAL(15,2) NULL,
    `moneda` ENUM('colones', 'dolares') DEFAULT 'colones',
    `fecha_cotizacion` DATE NOT NULL,
    `fecha_vencimiento_cotizacion` DATE NULL COMMENT 'Válida por 30 días típicamente',
    `status` ENUM('borrador', 'enviada', 'aceptada', 'rechazada', 'vencida', 'convertida_poliza') DEFAULT 'enviada',
    `archivo_cotizacion_url` VARCHAR(500) NULL,
    `notas_cliente` TEXT NULL,
    `notas_admin` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by` INT NULL,
    FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE,
    INDEX `idx_client` (`client_id`),
    INDEX `idx_numero_cotizacion` (`numero_cotizacion`),
    INDEX `idx_status` (`status`),
    INDEX `idx_quotes_client_status` (`client_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cotizaciones enviadas a clientes';

-- Tabla de notificaciones para clientes
CREATE TABLE IF NOT EXISTS `client_notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `client_id` INT NOT NULL,
    `tipo` ENUM('pago_pendiente', 'poliza_por_vencer', 'cotizacion_lista', 'pago_recibido', 'poliza_emitida', 'general') NOT NULL,
    `titulo` VARCHAR(255) NOT NULL,
    `mensaje` TEXT NOT NULL,
    `policy_id` INT NULL,
    `payment_id` INT NULL,
    `quote_id` INT NULL,
    `leida` BOOLEAN DEFAULT FALSE,
    `fecha_lectura` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE,
    INDEX `idx_client_leida` (`client_id`, `leida`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Notificaciones para clientes';

-- Tabla de configuración de Google OAuth
CREATE TABLE IF NOT EXISTS `oauth_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `provider` VARCHAR(50) NOT NULL DEFAULT 'google',
    `client_id` VARCHAR(500) NOT NULL,
    `client_secret` VARCHAR(500) NOT NULL,
    `redirect_uri` VARCHAR(500) NOT NULL,
    `scopes` TEXT NULL,
    `enabled` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Modificar tabla submissions para vincular con clientes
ALTER TABLE `submissions`
ADD COLUMN IF NOT EXISTS `client_id` INT NULL AFTER `id`,
ADD COLUMN IF NOT EXISTS `status` ENUM('pendiente', 'en_proceso', 'cotizado', 'emitido', 'rechazado') DEFAULT 'pendiente' AFTER `payload`,
ADD INDEX IF NOT EXISTS `idx_client` (`client_id`);

-- Vista para dashboard del cliente
CREATE OR REPLACE VIEW `client_dashboard_summary` AS
SELECT
    c.id as client_id,
    c.nombre_completo,
    c.email,
    COUNT(DISTINCT p.id) as total_polizas,
    COUNT(DISTINCT CASE WHEN p.status = 'vigente' THEN p.id END) as polizas_vigentes,
    COUNT(DISTINCT CASE WHEN p.status = 'por_vencer' THEN p.id END) as polizas_por_vencer,
    COUNT(DISTINCT q.id) as total_cotizaciones,
    COUNT(DISTINCT CASE WHEN pay.status = 'pendiente' THEN pay.id END) as pagos_pendientes,
    SUM(CASE WHEN pay.status = 'pendiente' THEN pay.monto ELSE 0 END) as monto_pendiente,
    MAX(p.fecha_fin_vigencia) as proxima_renovacion
FROM clients c
LEFT JOIN policies p ON c.id = p.client_id
LEFT JOIN quotes q ON c.id = q.client_id
LEFT JOIN payments pay ON p.id = pay.policy_id
GROUP BY c.id, c.nombre_completo, c.email;

-- ============================================
-- FIN DEL SCRIPT
-- ============================================
-- Si todo salió bien, verás mensaje de éxito en phpMyAdmin
-- ============================================
