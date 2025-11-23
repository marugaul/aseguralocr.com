<?php
// Setup Database - AseguraloCR
// Ejecutar una sola vez y luego ELIMINAR

// Conexión
$host = 'localhost';
$db = 'asegural_aseguralocr';
$user = 'asegural_marugaul';
$pass = 'Marden7i/';

echo "<pre style='background:#000;color:#0f0;padding:20px;font-family:monospace'>";
echo "===========================================\n";
echo "AseguraloCR - Setup Base de Datos\n";
echo "===========================================\n\n";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "✅ Conectado a: $db\n\n";

    // Leer SQL
    $sql = file_get_contents(__DIR__ . '/EJECUTAR_EN_PHPMYADMIN.sql');

    if (!$sql) {
        die("❌ ERROR: No se encontró EJECUTAR_EN_PHPMYADMIN.sql\n");
    }

    echo "📄 Ejecutando SQL...\n\n";

    // Ejecutar
    $pdo->exec($sql);

    echo "✅ SQL ejecutado exitosamente\n\n";

    // Verificar tablas
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "📊 Tablas en la BD (" . count($tables) . "):\n";

    $expected = ['clients', 'policies', 'payments', 'quotes', 'client_notifications', 'oauth_settings'];
    foreach ($expected as $table) {
        if (in_array($table, $tables)) {
            echo "   ✅ $table\n";
        } else {
            echo "   ❌ $table (NO ENCONTRADA)\n";
        }
    }

    echo "\n===========================================\n";
    echo "✅ COMPLETADO\n";
    echo "⚠️  AHORA ELIMINA ESTE ARCHIVO: setup-db.php\n";
    echo "===========================================\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>
