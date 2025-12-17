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

$dataDir = __DIR__ . '/data/padron';
$zipDest = $dataDir . '/padron_completo.zip';

echo "<h1>Configurando Padrón Electoral</h1><pre>";

// Buscar ZIP en múltiples ubicaciones posibles
$posiblesUbicaciones = [
    __DIR__ . '/padron_temp.zip',
    '/home/asegural/documents/padron_completo.zip',
    '/home/asegural/public_html/aseguralocr_uploads/padron_completo.zip',
    __DIR__ . '/../documents/padron_completo.zip',
    __DIR__ . '/padron_completo.zip'
];

$zipSource = null;
foreach ($posiblesUbicaciones as $ubicacion) {
    if (file_exists($ubicacion)) {
        $zipSource = $ubicacion;
        break;
    }
}

// Verificar ZIP origen
if (!$zipSource) {
    echo "❌ No se encontró el archivo ZIP en ninguna ubicación.\n\n";
    echo "Ubicaciones verificadas:\n";
    foreach ($posiblesUbicaciones as $ubicacion) {
        echo "  - $ubicacion\n";
    }
    echo "\nDescarga y sube el archivo a una de estas ubicaciones:\n";
    echo "https://www.tse.go.cr/zip/padron/padron_completo.zip\n";
    die();
}

echo "✓ ZIP encontrado en: $zipSource\n";
echo "✓ Tamaño: " . round(filesize($zipSource)/1024/1024, 2) . " MB\n\n";

// Crear directorio data/padron si no existe
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
    echo "✓ Creado directorio: $dataDir\n";
}

// Copiar ZIP a directorio de datos
if (copy($zipSource, $zipDest)) {
    echo "✓ ZIP copiado a: $zipDest\n";

    // Solo eliminar si está en el directorio del sitio (no en documents)
    if (strpos($zipSource, __DIR__) === 0) {
        unlink($zipSource);
        echo "✓ ZIP temporal eliminado del sitio\n";
    } else {
        echo "✓ ZIP original conservado en: $zipSource\n";
    }
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
