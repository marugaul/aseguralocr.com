<?php
/**
 * Script para mover y extraer el padrón electoral
 * Ejecutar una vez: /admin_padron.php
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
    die("❌ Error: No existe $zipSource\n\nDescarga primero:\nhttps://www.tse.go.cr/zip/padron/padron_completo.zip\n");
}

echo "✓ ZIP encontrado: " . round(filesize($zipSource)/1024/1024, 2) . " MB\n\n";

// Crear directorio data/padron si no existe
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
    echo "✓ Creado directorio: $dataDir\n";
}

// Mover ZIP
if (copy($zipSource, $zipDest)) {
    echo "✓ ZIP copiado a: $zipDest\n";
    unlink($zipSource);
    echo "✓ ZIP temporal eliminado\n";
} else {
    die("❌ Error al copiar ZIP\n");
}

// Extraer ZIP
echo "\n⏳ Extrayendo ZIP (esto puede tardar 1-2 minutos)...\n";
$zip = new ZipArchive;
if ($zip->open($zipDest) === TRUE) {
    $zip->extractTo($dataDir);
    $zip->close();
    echo "✓ Archivos extraídos:\n";

    // Listar archivos extraídos
    $files = scandir($dataDir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..' && $file != basename($zipDest)) {
            $size = filesize("$dataDir/$file");
            echo "  - $file (" . round($size/1024/1024, 2) . " MB)\n";
        }
    }

    // Eliminar ZIP después de extraer
    unlink($zipDest);
    echo "\n✓ ZIP eliminado (archivos ya extraídos)\n";

    echo "\n<strong>✅ PADRÓN CONFIGURADO EXITOSAMENTE</strong>\n\n";
    echo "Ahora ve a: <a href='/admin/padron_importar.php'>/admin/padron_importar.php</a>\n";
    echo "Y haz clic en 'Importar a MySQL'\n";

} else {
    die("❌ Error al extraer ZIP\n");
}

echo "</pre>";
?>
