<?php
/**
 * Setup Dashboard Database Tables
 * Ejecuta la migración del sistema de dashboard
 */

// Cargar configuración
$config = require __DIR__ . '/app/config/config.php';

$DB_HOST = $config['db']['mysql']['host'];
$DB_NAME = $config['db']['mysql']['dbname'];
$DB_USER = $config['db']['mysql']['user'];
$DB_PASS = $config['db']['mysql']['pass'];
$DB_CHARSET = $config['db']['mysql']['charset'];

echo "========================================\n";
echo "Dashboard Database Setup\n";
echo "========================================\n\n";

echo "Conectando a base de datos...\n";
echo "Host: {$DB_HOST}\n";
echo "Database: {$DB_NAME}\n";
echo "User: {$DB_USER}\n\n";

try {
    $dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset={$DB_CHARSET}";
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "✅ Conexión exitosa\n\n";

    // Leer archivo de migración
    $sqlFile = __DIR__ . '/database/migrations/002_client_dashboard_system.sql';

    if (!file_exists($sqlFile)) {
        throw new Exception("Archivo de migración no encontrado: {$sqlFile}");
    }

    echo "Leyendo archivo de migración...\n";
    $sql = file_get_contents($sqlFile);

    echo "Ejecutando SQL...\n\n";

    // Separar statements por punto y coma
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && substr($stmt, 0, 2) !== '--';
        }
    );

    $success = 0;
    $failed = 0;

    foreach ($statements as $statement) {
        // Skip comments and DELIMITER commands
        if (empty($statement) ||
            stripos($statement, 'DELIMITER') === 0 ||
            substr(trim($statement), 0, 2) === '--') {
            continue;
        }

        try {
            $pdo->exec($statement);

            // Extract table/object name for display
            if (preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $statement, $matches)) {
                echo "  ✅ Tabla creada: {$matches[1]}\n";
                $success++;
            } elseif (preg_match('/CREATE TRIGGER.*?`?(\w+)`?/i', $statement, $matches)) {
                echo "  ✅ Trigger creado: {$matches[1]}\n";
                $success++;
            } elseif (preg_match('/CREATE.*?VIEW.*?`?(\w+)`?/i', $statement, $matches)) {
                echo "  ✅ Vista creada: {$matches[1]}\n";
                $success++;
            } elseif (preg_match('/ALTER TABLE.*?`?(\w+)`?/i', $statement, $matches)) {
                echo "  ✅ Tabla alterada: {$matches[1]}\n";
                $success++;
            } elseif (preg_match('/CREATE INDEX.*?ON.*?`?(\w+)`?/i', $statement, $matches)) {
                echo "  ✅ Índice creado en: {$matches[1]}\n";
                $success++;
            }
        } catch (PDOException $e) {
            // Ignore "already exists" errors
            if (strpos($e->getMessage(), 'already exists') !== false) {
                if (preg_match('/`?(\w+)`?/', $statement, $matches)) {
                    echo "  ⚠️  Ya existe: {$matches[1]}\n";
                }
            } else {
                echo "  ❌ Error: " . $e->getMessage() . "\n";
                $failed++;
            }
        }
    }

    echo "\n========================================\n";
    echo "Resumen:\n";
    echo "✅ Operaciones exitosas: {$success}\n";
    if ($failed > 0) {
        echo "❌ Operaciones fallidas: {$failed}\n";
    }
    echo "========================================\n\n";

    // Verificar tablas creadas
    echo "Verificando tablas creadas...\n\n";

    $tables = ['clients', 'policies', 'payments', 'quotes', 'client_notifications', 'oauth_settings'];

    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
        if ($stmt->rowCount() > 0) {
            // Count records
            $count = $pdo->query("SELECT COUNT(*) as cnt FROM {$table}")->fetch();
            echo "  ✅ {$table} (registros: {$count['cnt']})\n";
        } else {
            echo "  ❌ {$table} NO EXISTE\n";
        }
    }

    // Verificar vista
    $stmt = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'VIEW' AND Tables_in_{$DB_NAME} = 'client_dashboard_summary'");
    if ($stmt->rowCount() > 0) {
        echo "  ✅ client_dashboard_summary (vista)\n";
    }

    // Verificar triggers
    $triggers = $pdo->query("SHOW TRIGGERS FROM {$DB_NAME}")->fetchAll();
    if (count($triggers) > 0) {
        echo "\nTriggers activos:\n";
        foreach ($triggers as $trigger) {
            echo "  ✅ {$trigger['Trigger']} en {$trigger['Table']}\n";
        }
    }

    echo "\n✅ ¡Base de datos configurada correctamente!\n\n";

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "\nDetalles:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
