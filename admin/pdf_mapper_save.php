<?php
// admin/pdf_mapper_save.php - Guardar mapeo de campos PDF
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

header('Content-Type: application/json');

try {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);

    // Validación mejorada con mensajes específicos
    if ($input === null && json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON inválido: ' . json_last_error_msg());
    }

    if (!is_array($input)) {
        throw new Exception('Se esperaba un objeto JSON');
    }

    if (!isset($input['pdf']) || empty($input['pdf'])) {
        throw new Exception('Falta el nombre del PDF');
    }

    // Permitir campos vacíos (mapeo incompleto)
    if (!isset($input['fields'])) {
        $input['fields'] = [];
    }

    $pdfName = basename($input['pdf']); // Seguridad: solo nombre de archivo
    $newFields = $input['fields'];

    // Directorio de mapeos
    $mappingsDir = __DIR__ . '/../mappings/';
    if (!is_dir($mappingsDir)) {
        mkdir($mappingsDir, 0755, true);
    }

    // Nombre del archivo de mapeo
    $mappingFile = $mappingsDir . pathinfo($pdfName, PATHINFO_FILENAME) . '_mapping.json';

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
            'key' => $field['key'],
            'label' => $field['label'],
            'type' => $field['type'],
            'source' => $field['source'] ?? 'payload',
            'page' => $field['page'],
            'x' => $field['x'],
            'y' => $field['y']
        ];
    }

    // Ordenar por página y posición Y
    usort($mappingData['fields'], function($a, $b) {
        if ($a['page'] !== $b['page']) return $a['page'] - $b['page'];
        return $a['y'] - $b['y'];
    });

    // Guardar archivo
    $json = json_encode($mappingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if (file_put_contents($mappingFile, $json) === false) {
        throw new Exception('Error al escribir el archivo de mapeo');
    }

    $isUpdate = $existingData !== null;
    echo json_encode([
        'success' => true,
        'message' => $isUpdate ? 'Mapeo actualizado' : 'Mapeo creado',
        'file' => basename($mappingFile),
        'fields_count' => count($newFields),
        'is_update' => $isUpdate
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
