<?php
// mappings/index.php - Sirve archivos de mapeo desde directorio externo
header('Content-Type: application/json');

// Obtener nombre del archivo de la URL
$requestUri = $_SERVER['REQUEST_URI'];
$filename = basename(parse_url($requestUri, PHP_URL_PATH));

// Validar que sea un archivo JSON
if (!preg_match('/^[a-zA-Z0-9_-]+\.json$/', $filename)) {
    http_response_code(400);
    echo json_encode(['error' => 'Nombre de archivo inválido']);
    exit;
}

// Buscar primero en directorio externo, luego en local
$externalDir = dirname(__DIR__, 2) . '/aseguralocr_mappings/';
$localDir = __DIR__ . '/';

$filePath = null;
if (file_exists($externalDir . $filename)) {
    $filePath = $externalDir . $filename;
} elseif (file_exists($localDir . $filename)) {
    $filePath = $localDir . $filename;
}

if (!$filePath) {
    http_response_code(404);
    echo json_encode(['error' => 'Archivo no encontrado']);
    exit;
}

// Servir el archivo
readfile($filePath);
