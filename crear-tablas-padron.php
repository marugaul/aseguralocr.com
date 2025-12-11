<?php
/**
 * Script para crear las tablas del padrón electoral
 * Ejecutar una sola vez y luego eliminar
 * Acceso: /crear-tablas-padron.php
 */

require_once __DIR__ . '/includes/db.php';
global $pdo;

echo "<h1>Creando tablas del Padrón Electoral</h1>";
echo "<pre>";

$sqlFile = __DIR__ . '/mysql-pendientes/padron_electoral.sql';

if (!file_exists($sqlFile)) {
    die("Error: No se encontró el archivo SQL");
}

$sql = file_get_contents($sqlFile);
$statements = array_filter(array_map('trim', explode(';', $sql)));

$success = true;
foreach ($statements as $stmt) {
    if (empty($stmt) || strpos($stmt, '--') === 0) continue;

    try {
        $pdo->exec($stmt);
        echo "OK: " . substr($stmt, 0, 80) . "...\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo "Ya existe: " . substr($stmt, 0, 60) . "...\n";
        } else {
            echo "ERROR: " . $e->getMessage() . "\n";
            $success = false;
        }
    }
}

if ($success) {
    echo "\n=== TABLAS CREADAS EXITOSAMENTE ===\n";

    // Verificar tablas
    $tables = $pdo->query("SHOW TABLES LIKE 'padron%'")->fetchAll(PDO::FETCH_COLUMN);
    echo "\nTablas creadas:\n";
    foreach ($tables as $t) {
        echo "  - $t\n";
    }

    // Mover archivo a ejecutados
    $ejecutadosDir = __DIR__ . '/mysql-ejecutados';
    if (!is_dir($ejecutadosDir)) {
        mkdir($ejecutadosDir, 0755, true);
    }
    $destino = $ejecutadosDir . '/' . date('Ymd_His') . '_padron_electoral.sql';
    rename($sqlFile, $destino);
    echo "\nArchivo movido a mysql-ejecutados/\n";
}

echo "</pre>";
echo "<p><strong>Ahora puedes ir a:</strong> <a href='/admin/padron_importar.php'>/admin/padron_importar.php</a> para importar los datos</p>";
echo "<p style='color:red'><strong>IMPORTANTE:</strong> Elimina este archivo después de usarlo</p>";
?>
