<?php
/**
 * Ejecutar SQL pendientes manualmente
 * Acceso: /admin/run-pending-sql.php
 * ELIMINAR después de usar
 */

require_once __DIR__ . '/../app/services/Security.php';
Security::start();

// Solo admin puede ejecutar
if (empty($_SESSION['admin_id'])) {
    die('Acceso denegado');
}

$config = require __DIR__ . '/../app/config/config.php';

try {
    $pdo = new PDO(
        "mysql:host={$config['db']['mysql']['host']};dbname={$config['db']['mysql']['dbname']};charset={$config['db']['mysql']['charset']}",
        $config['db']['mysql']['user'],
        $config['db']['mysql']['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

$pendientesDir = __DIR__ . '/../mysql-pendientes';
$ejecutadosDir = __DIR__ . '/../mysql-ejecutados';

if (!is_dir($ejecutadosDir)) {
    mkdir($ejecutadosDir, 0755, true);
}

$files = glob($pendientesDir . '/*.sql');

if (empty($files)) {
    echo "<h2>No hay archivos SQL pendientes</h2>";
    exit;
}

echo "<h1>Ejecutando SQL Pendientes</h1>";
echo "<pre>";

foreach ($files as $file) {
    $filename = basename($file);
    echo "\n=== Procesando: $filename ===\n";

    $sql = file_get_contents($file);

    // Separar por statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    $success = true;
    foreach ($statements as $stmt) {
        if (empty($stmt) || strpos($stmt, '--') === 0) continue;

        try {
            $pdo->exec($stmt);
            echo "✓ Ejecutado: " . substr($stmt, 0, 80) . "...\n";
        } catch (PDOException $e) {
            // Ignorar errores de "already exists" o "duplicate"
            if (strpos($e->getMessage(), 'already exists') !== false ||
                strpos($e->getMessage(), 'Duplicate') !== false) {
                echo "⚠ Ya existe: " . substr($stmt, 0, 60) . "...\n";
            } else {
                echo "✗ Error: " . $e->getMessage() . "\n";
                $success = false;
            }
        }
    }

    if ($success) {
        // Mover a ejecutados
        $timestamp = date('Ymd_His');
        $destino = $ejecutadosDir . '/' . $timestamp . '_' . $filename;
        if (rename($file, $destino)) {
            echo "✓ Movido a ejecutados/\n";
        } else {
            echo "⚠ No se pudo mover el archivo\n";
        }
    }
}

echo "\n=== COMPLETADO ===\n";
echo "</pre>";

echo "<p><strong>Ahora elimina este archivo:</strong> /admin/run-pending-sql.php</p>";
?>
