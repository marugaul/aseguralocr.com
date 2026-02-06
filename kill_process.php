<?php
/**
 * Script de emergencia para detener procesos PHP colgados
 * SOLO para admin
 */

session_start();
if (empty($_SESSION['admin_id'])) {
    die('Acceso denegado');
}

echo "<h1>Detener Procesos PHP</h1>";
echo "<pre>";

// Método 1: Crear archivo de señal para detener importación
$stopFile = __DIR__ . '/data/padron/STOP_IMPORT';
if (isset($_GET['crear_stop'])) {
    @mkdir(dirname($stopFile), 0755, true);
    file_put_contents($stopFile, date('Y-m-d H:i:s'));
    echo "✓ Archivo STOP creado en: $stopFile\n";
    echo "Los procesos de importación deberían detenerse.\n";
}

// Método 2: Información del sistema
echo "\n--- Información del Sistema ---\n";
echo "Usuario PHP: " . get_current_user() . "\n";
echo "Memoria usada: " . round(memory_get_usage() / 1024 / 1024, 2) . " MB\n";
echo "Memoria límite: " . ini_get('memory_limit') . "\n";
echo "Tiempo máximo: " . ini_get('max_execution_time') . "s\n";

// Método 3: Verificar carga del servidor
if (function_exists('sys_getloadavg')) {
    $load = sys_getloadavg();
    echo "\nCarga del servidor: " . implode(', ', $load) . "\n";
}

echo "\n--- Acciones Disponibles ---\n";
echo "1. <a href='?crear_stop=1'>Crear archivo STOP</a> - Detiene importaciones en progreso\n";
echo "2. <a href='?limpiar=1'>Limpiar archivos temporales</a>\n";
echo "3. Desde tu panel de hosting (cPanel/Plesk):\n";
echo "   - Busca 'Select PHP Version' o 'PHP Processes'\n";
echo "   - Mata procesos que lleven más de 5 minutos\n";
echo "   - O reinicia PHP-FPM\n";

if (isset($_GET['limpiar'])) {
    echo "\n--- Limpiando archivos temporales ---\n";

    $archivos = [
        __DIR__ . '/data/padron/extract_progress.json',
        __DIR__ . '/data/padron/progress.json',
        __DIR__ . '/data/padron/STOP_IMPORT'
    ];

    foreach ($archivos as $archivo) {
        if (file_exists($archivo)) {
            unlink($archivo);
            echo "✓ Eliminado: $archivo\n";
        }
    }
}

echo "</pre>";

echo "<hr>";
echo "<h2>Soluciones sin SSH:</h2>";
echo "<ol>";
echo "<li><strong>Desde cPanel:</strong> Ve a 'Select PHP Version' → pestaña 'Options' → Reinicia PHP-FPM</li>";
echo "<li><strong>Desde Plesk:</strong> Ve a 'PHP Settings' → Reinicia PHP</li>";
echo "<li><strong>Esperar:</strong> PHP tiene timeout de 300 segundos (5 min), eventualmente se detendrá solo</li>";
echo "<li><strong>Contactar soporte:</strong> Pide que reinicien PHP-FPM en tu cuenta</li>";
echo "</ol>";

echo "<p><a href='/admin/dashboard.php'>← Volver al Dashboard</a></p>";
?>
