<?php
// admin/pdf_mapper_save.php - Guardar mapeo de campos PDF
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../app/services/Security.php';

// Usar Security::start() para consistencia con pdf_mapper.php
Security::start();

// Verificar autenticación y devolver JSON en lugar de redirect
if (empty($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Sesión expirada. Recarga la página e inicia sesión.']);
    exit;
}

try {
    $rawInput = file_get_contents('php://input');

    // Log para debug
    error_log("PDF Mapper Save - Raw input length: " . strlen($rawInput));
    error_log("PDF Mapper Save - Session admin_logged: " . ($_SESSION['admin_logged'] ? 'true' : 'false'));

    if (empty($rawInput)) {
        throw new Exception('No se recibieron datos. Asegúrate de tener campos colocados en el PDF.');
    }

    $input = json_decode($rawInput, true);

    // Validación mejorada con mensajes específicos
    if ($input === null) {
        $jsonError = json_last_error_msg();
        error_log("PDF Mapper Save - JSON error: " . $jsonError);
        throw new Exception('JSON inválido: ' . $jsonError);
    }

    if (!is_array($input)) {
        throw new Exception('Se esperaba un objeto JSON');
    }

    if (!isset($input['pdf']) || empty($input['pdf'])) {
        error_log("PDF Mapper Save - Missing pdf field. Keys: " . implode(', ', array_keys($input)));
        throw new Exception('Falta el nombre del PDF');
    }

    // Permitir campos vacíos (mapeo incompleto)
    if (!isset($input['fields'])) {
        $input['fields'] = [];
    }

    $pdfName = basename($input['pdf']); // Seguridad: solo nombre de archivo
    $newFields = $input['fields'];

    error_log("PDF Mapper Save - PDF: $pdfName, Fields count: " . count($newFields));

    // Directorio de mapeos - FUERA del repo para que rsync --delete no lo borre
    $mappingsDir = dirname(__DIR__, 2) . '/aseguralocr_mappings/';

    // Fallback al directorio dentro del repo si no se puede crear fuera
    if (!is_dir($mappingsDir) && !@mkdir($mappingsDir, 0777, true)) {
        $mappingsDir = __DIR__ . '/../mappings/';
    }

    // Crear directorio si no existe
    if (!is_dir($mappingsDir)) {
        if (!mkdir($mappingsDir, 0777, true)) {
            throw new Exception('No se pudo crear el directorio de mapeos: ' . $mappingsDir);
        }
    }

    // Verificar permisos de escritura
    if (!is_writable($mappingsDir)) {
        // Intentar cambiar permisos
        @chmod($mappingsDir, 0777);
        if (!is_writable($mappingsDir)) {
            throw new Exception('El directorio de mapeos no tiene permisos de escritura: ' . $mappingsDir);
        }
    }

    // Nombre del archivo de mapeo
    $mappingFile = $mappingsDir . pathinfo($pdfName, PATHINFO_FILENAME) . '_mapping.json';

    error_log("PDF Mapper Save - Mapping file: $mappingFile");

    // Cargar mapeo existente si existe (para merge)
    $existingData = null;
    $createdAt = date('Y-m-d H:i:s');
    if (file_exists($mappingFile)) {
        $existingJson = file_get_contents($mappingFile);
        $existingData = json_decode($existingJson, true);
        if ($existingData && isset($existingData['meta']['created_at'])) {
            $createdAt = $existingData['meta']['created_at'];
        }
    }

    // Preparar datos para guardar
    $mappingData = [
        'meta' => [
            'pdf_template' => $pdfName,
            'tipo_poliza' => $input['tipo'] ?? 'hogar',
            'created_at' => $createdAt,
            'updated_at' => date('Y-m-d H:i:s'),
            'total_fields' => count($newFields)
        ],
        'fields' => []
    ];

    // Convertir campos al formato correcto, incluyendo source
    foreach ($newFields as $id => $field) {
        $mappingData['fields'][] = [
            'id' => $id,
            'key' => $field['key'] ?? '',
            'label' => $field['label'] ?? '',
            'type' => $field['type'] ?? 'text',
            'source' => $field['source'] ?? 'payload',
            'page' => $field['page'] ?? 1,
            'x' => $field['x'] ?? 0,
            'y' => $field['y'] ?? 0
        ];
    }

    // Ordenar por página y posición Y
    usort($mappingData['fields'], function($a, $b) {
        if ($a['page'] !== $b['page']) return $a['page'] - $b['page'];
        return $a['y'] - $b['y'];
    });

    // Guardar archivo
    $json = json_encode($mappingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    $bytesWritten = file_put_contents($mappingFile, $json);
    if ($bytesWritten === false) {
        $lastError = error_get_last();
        throw new Exception('Error al escribir el archivo: ' . ($lastError['message'] ?? 'desconocido'));
    }

    error_log("PDF Mapper Save - Success! Bytes written: $bytesWritten");

    $isUpdate = $existingData !== null;
    echo json_encode([
        'success' => true,
        'message' => $isUpdate ? 'Mapeo actualizado' : 'Mapeo creado',
        'file' => basename($mappingFile),
        'fields_count' => count($newFields),
        'is_update' => $isUpdate,
        'bytes_written' => $bytesWritten
    ]);

} catch (Exception $e) {
    error_log("PDF Mapper Save - ERROR: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
