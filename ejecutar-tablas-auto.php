<?php
/**
 * AUTO-EJECUTOR DE TABLAS
 * Este script se ejecuta automáticamente y crea TODAS las tablas
 */

// Conexión
$pdo = new PDO("mysql:host=localhost;dbname=asegural_aseguralocr;charset=utf8mb4",
    "asegural_marugaul", "Marden7i/", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

// Log de ejecución
$log = "INICIO: " . date('Y-m-d H:i:s') . "\n";

try {
    // 1. RECREAR tabla clients (eliminar y crear nueva con UNSIGNED)
    $log .= "1. Eliminando clients antigua...\n";
    $pdo->exec("DROP TABLE IF EXISTS client_notifications");
    $pdo->exec("DROP TABLE IF EXISTS payments");
    $pdo->exec("DROP TABLE IF EXISTS quotes");
    $pdo->exec("DROP TABLE IF EXISTS policies");
    $pdo->exec("DROP TABLE IF EXISTS clients");

    $log .= "2. Creando clients nueva (UNSIGNED)...\n";
    $pdo->exec("
        CREATE TABLE clients (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            google_id VARCHAR(255) UNIQUE NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            nombre_completo VARCHAR(255) NOT NULL,
            telefono VARCHAR(50) NULL,
            cedula VARCHAR(50) NULL,
            fecha_nacimiento DATE NULL,
            direccion TEXT NULL,
            provincia VARCHAR(100) NULL,
            canton VARCHAR(100) NULL,
            distrito VARCHAR(100) NULL,
            avatar_url VARCHAR(500) NULL,
            email_verified BOOLEAN DEFAULT FALSE,
            google_access_token TEXT NULL,
            google_refresh_token TEXT NULL,
            last_login DATETIME NULL,
            status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_email (email),
            INDEX idx_google_id (google_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $log .= "3. Creando policies...\n";
    $pdo->exec("
        CREATE TABLE policies (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            client_id INT UNSIGNED NOT NULL,
            submission_id INT NULL,
            numero_poliza VARCHAR(100) UNIQUE NOT NULL,
            tipo_seguro ENUM('hogar', 'auto', 'vida', 'salud', 'otros') NOT NULL,
            aseguradora VARCHAR(100) DEFAULT 'INS',
            coberturas TEXT NULL,
            monto_asegurado DECIMAL(15,2) NULL,
            prima_anual DECIMAL(15,2) NULL,
            prima_mensual DECIMAL(15,2) NULL,
            fecha_inicio_vigencia DATE NOT NULL,
            fecha_fin_vigencia DATE NOT NULL,
            forma_pago ENUM('mensual', 'trimestral', 'semestral', 'anual') DEFAULT 'anual',
            status ENUM('vigente', 'por_vencer', 'vencida', 'cancelada') DEFAULT 'vigente',
            notas TEXT NULL,
            archivo_poliza_url VARCHAR(500) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
            INDEX idx_client_id (client_id),
            INDEX idx_numero_poliza (numero_poliza),
            INDEX idx_status (status),
            INDEX idx_fecha_fin (fecha_fin_vigencia)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $log .= "4. Creando payments...\n";
    $pdo->exec("
        CREATE TABLE payments (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            policy_id INT UNSIGNED NOT NULL,
            client_id INT UNSIGNED NOT NULL,
            monto DECIMAL(15,2) NOT NULL,
            fecha_vencimiento DATE NOT NULL,
            fecha_pago DATE NULL,
            metodo_pago ENUM('efectivo', 'transferencia', 'tarjeta', 'sinpe', 'deposito') NULL,
            referencia_pago VARCHAR(255) NULL,
            status ENUM('pendiente', 'pagado', 'vencido', 'cancelado') DEFAULT 'pendiente',
            notas TEXT NULL,
            comprobante_url VARCHAR(500) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (policy_id) REFERENCES policies(id) ON DELETE CASCADE,
            FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
            INDEX idx_policy_id (policy_id),
            INDEX idx_client_id (client_id),
            INDEX idx_status (status),
            INDEX idx_fecha_vencimiento (fecha_vencimiento)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $log .= "5. Creando quotes...\n";
    $pdo->exec("
        CREATE TABLE quotes (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            client_id INT UNSIGNED NULL,
            submission_id INT NULL,
            tipo_seguro ENUM('hogar', 'auto', 'vida', 'salud', 'otros') NOT NULL,
            monto_solicitado DECIMAL(15,2) NULL,
            prima_estimada DECIMAL(15,2) NULL,
            detalles TEXT NULL,
            status ENUM('pendiente', 'enviada', 'aceptada', 'rechazada', 'convertida') DEFAULT 'pendiente',
            fecha_envio DATE NULL,
            fecha_vencimiento_cotizacion DATE NULL,
            notas_agente TEXT NULL,
            archivo_cotizacion_url VARCHAR(500) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,
            INDEX idx_client_id (client_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $log .= "6. Creando client_notifications...\n";
    $pdo->exec("
        CREATE TABLE client_notifications (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            client_id INT UNSIGNED NOT NULL,
            tipo ENUM('pago_proximo', 'pago_vencido', 'poliza_por_vencer', 'poliza_renovada', 'cotizacion_lista', 'general') NOT NULL,
            titulo VARCHAR(255) NOT NULL,
            mensaje TEXT NOT NULL,
            leida BOOLEAN DEFAULT FALSE,
            fecha_leida DATETIME NULL,
            link_accion VARCHAR(500) NULL,
            priority ENUM('low', 'normal', 'high') DEFAULT 'normal',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
            INDEX idx_client_id (client_id),
            INDEX idx_leida (leida),
            INDEX idx_tipo (tipo)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $log .= "7. Creando oauth_settings...\n";
    $pdo->exec("
        CREATE TABLE oauth_settings (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            provider VARCHAR(50) NOT NULL,
            client_id VARCHAR(500) NOT NULL,
            client_secret VARCHAR(500) NOT NULL,
            redirect_uri VARCHAR(500) NOT NULL,
            scopes TEXT NULL,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_provider (provider)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $log .= "\n✅ TODAS LAS TABLAS CREADAS EXITOSAMENTE\n";
    $log .= "FIN: " . date('Y-m-d H:i:s') . "\n";

} catch (Exception $e) {
    $log .= "\n❌ ERROR: " . $e->getMessage() . "\n";
}

// Guardar log
file_put_contents(__DIR__ . '/resultado-creacion-tablas.txt', $log);

// Mostrar resultado
header('Content-Type: text/plain; charset=utf-8');
echo $log;

// Auto-eliminarse después de ejecutar
if (strpos($log, '✅') !== false) {
    echo "\n🗑️  Auto-eliminando script...\n";
    @unlink(__FILE__);
}
?>
