<?php
/**
 * Crear TODAS las tablas - AseguraloCR
 * Ejecutar: https://aseguralocr.com/crear-tablas-completo.php
 * ELIMINAR después de usar
 */

$host = 'localhost';
$db = 'asegural_aseguralocr';
$user = 'asegural_marugaul';
$pass = 'Marden7i/';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Crear Tablas Completo - AseguraloCR</title>
    <style>
        body { font-family: monospace; background: #1e1e1e; color: #0f0; padding: 20px; }
        .ok { color: #0f0; font-weight: bold; }
        .err { color: #f00; font-weight: bold; }
        .warn { color: #ff0; font-weight: bold; }
        pre { background: #000; padding: 15px; border-radius: 5px; line-height: 1.6; }
    </style>
</head>
<body>
<pre><?php
echo "========================================\n";
echo "AseguraloCR - Crear TODAS las Tablas\n";
echo "========================================\n\n";

try {
    // Conexión
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "<span class='ok'>✅ Conectado a: $db</span>\n\n";

    // Ejecutar cada tabla individualmente
    echo "📦 Creando tablas...\n\n";

    // 1. Tabla clients
    echo "1. <span class='ok'>clients</span>... ";
    try {
        $pdo->exec("
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "<span class='ok'>✓</span>\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo "<span class='warn'>Ya existe</span>\n";
        } else {
            echo "<span class='err'>✗ {$e->getMessage()}</span>\n";
        }
    }

    // 2. Tabla policies
    echo "2. <span class='ok'>policies</span>... ";
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `policies` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `client_id` INT NOT NULL,
                `submission_id` INT NULL,
                `numero_poliza` VARCHAR(100) UNIQUE NOT NULL,
                `tipo_seguro` ENUM('hogar', 'auto', 'vida', 'salud', 'otros') NOT NULL,
                `aseguradora` VARCHAR(100) DEFAULT 'INS',
                `coberturas` TEXT NULL,
                `monto_asegurado` DECIMAL(15,2) NULL,
                `prima_anual` DECIMAL(15,2) NULL,
                `prima_mensual` DECIMAL(15,2) NULL,
                `fecha_inicio_vigencia` DATE NOT NULL,
                `fecha_fin_vigencia` DATE NOT NULL,
                `forma_pago` ENUM('mensual', 'trimestral', 'semestral', 'anual') DEFAULT 'anual',
                `status` ENUM('vigente', 'por_vencer', 'vencida', 'cancelada') DEFAULT 'vigente',
                `notas` TEXT NULL,
                `archivo_poliza_url` VARCHAR(500) NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE,
                INDEX `idx_client_id` (`client_id`),
                INDEX `idx_numero_poliza` (`numero_poliza`),
                INDEX `idx_status` (`status`),
                INDEX `idx_fecha_fin` (`fecha_fin_vigencia`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "<span class='ok'>✓</span>\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo "<span class='warn'>Ya existe</span>\n";
        } else {
            echo "<span class='err'>✗ {$e->getMessage()}</span>\n";
        }
    }

    // 3. Tabla payments
    echo "3. <span class='ok'>payments</span>... ";
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `payments` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `policy_id` INT NOT NULL,
                `client_id` INT NOT NULL,
                `monto` DECIMAL(15,2) NOT NULL,
                `fecha_vencimiento` DATE NOT NULL,
                `fecha_pago` DATE NULL,
                `metodo_pago` ENUM('efectivo', 'transferencia', 'tarjeta', 'sinpe', 'deposito') NULL,
                `referencia_pago` VARCHAR(255) NULL,
                `status` ENUM('pendiente', 'pagado', 'vencido', 'cancelado') DEFAULT 'pendiente',
                `notas` TEXT NULL,
                `comprobante_url` VARCHAR(500) NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (`policy_id`) REFERENCES `policies`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE,
                INDEX `idx_policy_id` (`policy_id`),
                INDEX `idx_client_id` (`client_id`),
                INDEX `idx_status` (`status`),
                INDEX `idx_fecha_vencimiento` (`fecha_vencimiento`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "<span class='ok'>✓</span>\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo "<span class='warn'>Ya existe</span>\n";
        } else {
            echo "<span class='err'>✗ {$e->getMessage()}</span>\n";
        }
    }

    // 4. Tabla quotes
    echo "4. <span class='ok'>quotes</span>... ";
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `quotes` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `client_id` INT NULL,
                `submission_id` INT NULL,
                `tipo_seguro` ENUM('hogar', 'auto', 'vida', 'salud', 'otros') NOT NULL,
                `monto_solicitado` DECIMAL(15,2) NULL,
                `prima_estimada` DECIMAL(15,2) NULL,
                `detalles` TEXT NULL,
                `status` ENUM('pendiente', 'enviada', 'aceptada', 'rechazada', 'convertida') DEFAULT 'pendiente',
                `fecha_envio` DATE NULL,
                `fecha_vencimiento_cotizacion` DATE NULL,
                `notas_agente` TEXT NULL,
                `archivo_cotizacion_url` VARCHAR(500) NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE SET NULL,
                INDEX `idx_client_id` (`client_id`),
                INDEX `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "<span class='ok'>✓</span>\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo "<span class='warn'>Ya existe</span>\n";
        } else {
            echo "<span class='err'>✗ {$e->getMessage()}</span>\n";
        }
    }

    // 5. Tabla client_notifications
    echo "5. <span class='ok'>client_notifications</span>... ";
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `client_notifications` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `client_id` INT NOT NULL,
                `tipo` ENUM('pago_proximo', 'pago_vencido', 'poliza_por_vencer', 'poliza_renovada', 'cotizacion_lista', 'general') NOT NULL,
                `titulo` VARCHAR(255) NOT NULL,
                `mensaje` TEXT NOT NULL,
                `leida` BOOLEAN DEFAULT FALSE,
                `fecha_leida` DATETIME NULL,
                `link_accion` VARCHAR(500) NULL,
                `priority` ENUM('low', 'normal', 'high') DEFAULT 'normal',
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE,
                INDEX `idx_client_id` (`client_id`),
                INDEX `idx_leida` (`leida`),
                INDEX `idx_tipo` (`tipo`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "<span class='ok'>✓</span>\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo "<span class='warn'>Ya existe</span>\n";
        } else {
            echo "<span class='err'>✗ {$e->getMessage()}</span>\n";
        }
    }

    // 6. Tabla oauth_settings
    echo "6. <span class='ok'>oauth_settings</span>... ";
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `oauth_settings` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `provider` VARCHAR(50) NOT NULL,
                `client_id` VARCHAR(500) NOT NULL,
                `client_secret` VARCHAR(500) NOT NULL,
                `redirect_uri` VARCHAR(500) NOT NULL,
                `scopes` TEXT NULL,
                `is_active` BOOLEAN DEFAULT TRUE,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `unique_provider` (`provider`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "<span class='ok'>✓</span>\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo "<span class='warn'>Ya existe</span>\n";
        } else {
            echo "<span class='err'>✗ {$e->getMessage()}</span>\n";
        }
    }

    // 7. Vista client_dashboard_summary
    echo "7. <span class='ok'>client_dashboard_summary (vista)</span>... ";
    try {
        $pdo->exec("DROP VIEW IF EXISTS `client_dashboard_summary`");
        $pdo->exec("
            CREATE VIEW `client_dashboard_summary` AS
            SELECT
                c.id as client_id,
                c.nombre_completo,
                c.email,
                COUNT(DISTINCT p.id) as total_polizas,
                COUNT(DISTINCT CASE WHEN p.status = 'vigente' THEN p.id END) as polizas_vigentes,
                COUNT(DISTINCT CASE WHEN p.status = 'por_vencer' THEN p.id END) as polizas_por_vencer,
                COUNT(DISTINCT pay.id) as total_pagos,
                COUNT(DISTINCT CASE WHEN pay.status = 'pendiente' THEN pay.id END) as pagos_pendientes,
                COUNT(DISTINCT CASE WHEN pay.status = 'vencido' THEN pay.id END) as pagos_vencidos,
                SUM(CASE WHEN pay.status = 'pendiente' THEN pay.monto ELSE 0 END) as monto_pendiente,
                COUNT(DISTINCT n.id) as notificaciones_no_leidas,
                MAX(c.last_login) as ultimo_acceso
            FROM clients c
            LEFT JOIN policies p ON c.id = p.client_id
            LEFT JOIN payments pay ON c.id = pay.client_id
            LEFT JOIN client_notifications n ON c.id = n.client_id AND n.leida = FALSE
            WHERE c.status = 'active'
            GROUP BY c.id, c.nombre_completo, c.email
        ");
        echo "<span class='ok'>✓</span>\n";
    } catch (PDOException $e) {
        echo "<span class='err'>✗ {$e->getMessage()}</span>\n";
    }

    echo "\n========================================\n";
    echo "<span class='ok'>✅ PROCESO COMPLETADO</span>\n";
    echo "========================================\n\n";

    // Verificación final
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "📊 Total de tablas en BD: <span class='ok'>" . count($tables) . "</span>\n\n";

    $expected = ['clients', 'policies', 'payments', 'quotes', 'client_notifications', 'oauth_settings'];
    echo "Verificación:\n";
    foreach ($expected as $t) {
        if (in_array($t, $tables)) {
            $count = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
            echo "  <span class='ok'>✅ $t</span> ($count registros)\n";
        } else {
            echo "  <span class='err'>❌ $t (NO ENCONTRADA)</span>\n";
        }
    }

    echo "\n<span class='warn'>⚠️  ELIMINA ESTE ARCHIVO:</span>\n";
    echo "   crear-tablas-completo.php\n\n";

} catch (Exception $e) {
    echo "<span class='err'>❌ ERROR: " . $e->getMessage() . "</span>\n";
}
?></pre>
</body>
</html>
