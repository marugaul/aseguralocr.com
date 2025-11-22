<?php
// Ejecutar SQL en producción
$config = require __DIR__ . '/app/config/config.php';

$host = $config['db']['mysql']['host'];
$db = $config['db']['mysql']['dbname'];
$user = $config['db']['mysql']['user'];
$pass = $config['db']['mysql']['pass'];

echo "========================================\n";
echo "Creando Tablas en Producción\n";
echo "========================================\n\n";
echo "Base de datos: $db\n";
echo "Servidor: $host\n\n";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    echo "✅ Conectado exitosamente\n\n";

    // Leer archivo SQL
    $sqlFile = __DIR__ . '/EJECUTAR_EN_PHPMYADMIN.sql';
    $sql = file_get_contents($sqlFile);

    echo "Ejecutando SQL...\n\n";

    // Separar statements
    $statements = array_filter(
        explode(';', $sql),
        function($stmt) {
            $stmt = trim($stmt);
            return !empty($stmt) && strpos($stmt, '--') !== 0;
        }
    );

    $success = 0;
    $skipped = 0;
    $errors = 0;

    foreach ($statements as $stmt) {
        $stmt = trim($stmt);
        if (empty($stmt)) continue;

        try {
            $pdo->exec($stmt);

            // Detectar qué se creó
            if (preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $stmt, $m)) {
                echo "  ✅ Tabla creada: {$m[1]}\n";
                $success++;
            } elseif (preg_match('/CREATE.*?VIEW.*?`?(\w+)`?/i', $stmt, $m)) {
                echo "  ✅ Vista creada: {$m[1]}\n";
                $success++;
            } elseif (preg_match('/ALTER TABLE.*?`?(\w+)`?/i', $stmt, $m)) {
                echo "  ✅ Tabla modificada: {$m[1]}\n";
                $success++;
            }
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'already exists') !== false) {
                if (preg_match('/`?(\w+)`?/', $stmt, $m)) {
                    echo "  ⚠️  Ya existe: {$m[1]}\n";
                    $skipped++;
                }
            } elseif (strpos($e->getMessage(), 'Duplicate column') !== false) {
                echo "  ⚠️  Columna ya existe\n";
                $skipped++;
            } else {
                echo "  ❌ Error: " . $e->getMessage() . "\n";
                $errors++;
            }
        }
    }

    echo "\n========================================\n";
    echo "Resumen:\n";
    echo "  ✅ Operaciones exitosas: $success\n";
    echo "  ⚠️  Ya existían: $skipped\n";
    if ($errors > 0) {
        echo "  ❌ Errores: $errors\n";
    }
    echo "========================================\n\n";

    // Verificar tablas creadas
    echo "Verificando tablas:\n\n";

    $tables = ['clients', 'policies', 'payments', 'quotes', 'client_notifications', 'oauth_settings'];

    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM `$table`");
            $count = $stmt->fetch()['cnt'];
            echo "  ✅ $table (registros: $count)\n";
        } catch (PDOException $e) {
            echo "  ❌ $table NO EXISTE\n";
        }
    }

    // Verificar vista
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM client_dashboard_summary");
        echo "  ✅ client_dashboard_summary (vista)\n";
    } catch (PDOException $e) {
        echo "  ❌ client_dashboard_summary NO EXISTE\n";
    }

    echo "\n✅ ¡Base de datos configurada!\n\n";

} catch (Exception $e) {
    echo "\n❌ Error fatal: " . $e->getMessage() . "\n";
    exit(1);
}
