<?php
// admin/pdf_mapper_save.php - Guardar mapeo de campos PDF
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['pdf']) || !isset($input['fields'])) {
        throw new Exception('Datos inválidos');
    }

    $pdfName = basename($input['pdf']); // Seguridad: solo nombre de archivo
    $fields = $input['fields'];

    // Directorio de mapeos
    $mappingsDir = __DIR__ . '/../mappings/';
    if (!is_dir($mappingsDir)) {
        mkdir($mappingsDir, 0755, true);
    }

    // Nombre del archivo de mapeo
    $mappingFile = $mappingsDir . pathinfo($pdfName, PATHINFO_FILENAME) . '_mapping.json';

    // Preparar datos para guardar
    $mappingData = [
        'meta' => [
            'pdf_template' => $pdfName,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'total_fields' => count($fields)
        ],
        'fields' => []
    ];

    // Convertir campos al formato correcto
    foreach ($fields as $id => $field) {
        $mappingData['fields'][] = [
            'id' => $id,
            'key' => $field['key'],
            'label' => $field['label'],
            'type' => $field['type'],
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

    echo json_encode([
        'success' => true,
        'message' => 'Mapeo guardado correctamente',
        'file' => basename($mappingFile),
        'fields_count' => count($fields)
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
