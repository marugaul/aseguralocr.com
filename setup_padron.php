<?php
/**
 * Script para mover y extraer el padrón
 * Ejecutar una vez: /setup_padron.php
 */
session_start();
if (empty($_SESSION['admin_id'])) {
    die('Acceso denegado - Inicia sesión en /admin primero');
}

set_time_limit(300);

$zipSource = __DIR__ . '/padron_temp.zip';
$dataDir = __DIR__ . '/data/padron';
$zipDest = $dataDir . '/padron_completo.zip';

echo "<h1>Configurando Padrón Electoral</h1><pre>";

// Verificar ZIP origen
if (!file_exists($zipSource)) {
    die("Error: No se encontró padron_temp.zip en la raíz del sitio");
}
echo "✓ Archivo ZIP encontrado: " . round(filesize($zipSource)/1024/1024, 1) . " MB\n";

// Crear directorio
if (!is_dir($dataDir)) {
    if (mkdir($dataDir, 0755, true)) {
        echo "✓ Directorio creado: $dataDir\n";
    } else {
        die("Error: No se pudo crear el directorio $dataDir");
    }
} else {
    echo "✓ Directorio existe: $dataDir\n";
}

// Mover ZIP
echo "→ Moviendo ZIP...\n";
if (copy($zipSource, $zipDest)) {
    echo "✓ ZIP copiado a data/padron/\n";
} else {
    die("Error: No se pudo copiar el ZIP");
}

// Extraer
echo "→ Extrayendo archivos (esto tarda ~30 segundos)...\n";
flush();

$zip = new ZipArchive();
if ($zip->open($zipDest) === true) {
    $zip->extractTo($dataDir);
    $zip->close();
    echo "✓ Archivos extraídos\n";
} else {
    die("Error: No se pudo extraer el ZIP");
}

// Verificar archivos
$padronFile = $dataDir . '/PADRON_COMPLETO.txt';
$distritosFile = $dataDir . '/distelec.txt';

if (file_exists($padronFile)) {
    echo "✓ PADRON_COMPLETO.txt: " . round(filesize($padronFile)/1024/1024, 1) . " MB\n";
} else {
    echo "⚠ PADRON_COMPLETO.txt no encontrado\n";
}

if (file_exists($distritosFile)) {
    echo "✓ distelec.txt: " . round(filesize($distritosFile)/1024, 1) . " KB\n";
}

// Eliminar ZIP de la raíz
if (unlink($zipSource)) {
    echo "✓ ZIP temporal eliminado de la raíz\n";
}

echo "\n=== COMPLETADO ===\n";
echo "</pre>";
echo "<p><strong>Ahora ve a:</strong> <a href='/admin/padron_importar.php'>/admin/padron_importar.php</a></p>";
echo "<p>Haz clic en <strong>Importar a MySQL</strong> para cargar los 3.7 millones de registros.</p>";
echo "<p style='color:red'><strong>IMPORTANTE:</strong> Elimina este archivo (setup_padron.php) después de usarlo.</p>";
