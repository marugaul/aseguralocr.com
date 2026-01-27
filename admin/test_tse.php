<?php
/**
 * Test de descarga del TSE
 * Acceso: /admin/test_tse.php
 */
session_start();
if (empty($_SESSION['admin_id'])) {
    die('Acceso denegado');
}

set_time_limit(0);
ini_set('memory_limit', '512M');

header('Content-Type: text/plain');

echo "=== TEST DE DESCARGA TSE ===\n\n";

$url = 'https://www.tse.go.cr/zip/padron/padron_completo.zip';

// Test 1: Verificar URL
echo "1. Verificando URL...\n";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$size = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
$error = curl_error($ch);
curl_close($ch);

echo "   HTTP Code: $httpCode\n";
echo "   Tamaño: " . round($size / 1024 / 1024, 2) . " MB\n";
if ($error) echo "   Error: $error\n";

if ($httpCode != 200) {
    die("\n\nERROR: No se puede acceder al TSE");
}

// Test 2: Descargar primeros 100KB
echo "\n2. Descargando muestra (100KB)...\n";
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_RANGE => '0-102400',
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_USERAGENT => 'Mozilla/5.0'
]);
$data = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

if ($data && strlen($data) > 0) {
    echo "   OK: Recibidos " . strlen($data) . " bytes\n";
} else {
    echo "   ERROR: $error\n";
    die("\n\nNo se pudo descargar muestra");
}

// Test 3: Verificar directorio de destino
echo "\n3. Verificando directorio...\n";
$dataDir = __DIR__ . '/../data/padron';
if (!is_dir($dataDir)) {
    if (mkdir($dataDir, 0755, true)) {
        echo "   Directorio creado: $dataDir\n";
    } else {
        echo "   ERROR: No se pudo crear directorio\n";
    }
} else {
    echo "   OK: Directorio existe\n";
}

if (is_writable($dataDir)) {
    echo "   OK: Directorio escribible\n";
} else {
    echo "   ERROR: Directorio no escribible\n";
}

// Test 4: Descargar archivo completo
echo "\n4. Descargando archivo completo (esto tarda ~2 min)...\n";
flush();

$zipFile = $dataDir . '/padron_completo.zip';
$start = time();

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 600,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
]);

$data = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

$elapsed = time() - $start;

if ($data && strlen($data) > 1000000) {
    echo "   OK: Descargados " . round(strlen($data) / 1024 / 1024, 2) . " MB en {$elapsed}s\n";

    // Guardar
    file_put_contents($zipFile, $data);
    unset($data);
    echo "   OK: Archivo guardado\n";

    // Descomprimir
    echo "\n5. Descomprimiendo...\n";
    $zip = new ZipArchive();
    if ($zip->open($zipFile) === true) {
        $zip->extractTo($dataDir);
        $zip->close();
        echo "   OK: Descomprimido\n";

        // Verificar archivos
        $padron = $dataDir . '/PADRON_COMPLETO.txt';
        if (file_exists($padron)) {
            echo "   OK: PADRON_COMPLETO.txt existe (" . round(filesize($padron) / 1024 / 1024, 1) . " MB)\n";
        }
    } else {
        echo "   ERROR: No se pudo descomprimir\n";
    }
} else {
    echo "   ERROR: Descarga falló - HTTP $httpCode - $error\n";
}

echo "\n=== FIN DEL TEST ===\n";
