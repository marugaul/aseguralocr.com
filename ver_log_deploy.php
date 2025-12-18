<?php
/**
 * Ver log de despliegue
 */

session_start();
if (empty($_SESSION['admin_id'])) {
    die('Acceso denegado');
}

echo "<h1>Log de Despliegue de Producción</h1>";
echo "<pre>";

$logFile = '/home/asegural/deploy_production.log';

if (file_exists($logFile)) {
    // Mostrar las últimas 100 líneas
    $lines = file($logFile);
    $lastLines = array_slice($lines, -100);

    echo "=== ÚLTIMAS 100 LÍNEAS ===\n\n";
    echo implode('', $lastLines);
} else {
    echo "❌ No existe el archivo de log: $logFile\n";
}

echo "</pre>";

echo "<p><a href='/admin/dashboard.php'>← Volver</a></p>";
?>
