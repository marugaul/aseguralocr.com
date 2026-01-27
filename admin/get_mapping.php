<?php
// admin/get_mapping.php - Obtener archivo de mapeo
header('Content-Type: application/json');

$filename = $_GET['file'] ?? '';

// Validar que sea un archivo JSON válido
if (!preg_match('/^[a-zA-Z0-9_\-]+_mapping\.json$/', $filename)) {
    http_response_code(400);
    echo json_encode(['error' => 'Nombre de archivo inválido']);
    exit;
}

// Buscar primero en directorio externo, luego en local
$externalDir = dirname(__DIR__, 2) . '/aseguralocr_mappings/';
$localDir = __DIR__ . '/../mappings/';

$filePath = null;
if (file_exists($externalDir . $filename)) {
    $filePath = $externalDir . $filename;
} elseif (file_exists($localDir . $filename)) {
    $filePath = $localDir . $filename;
}

if (!$filePath) {
    http_response_code(404);
    echo json_encode(['error' => 'Archivo no encontrado', 'searched' => [$externalDir . $filename, $localDir . $filename]]);
    exit;
}

// Servir el archivo
readfile($filePath);
