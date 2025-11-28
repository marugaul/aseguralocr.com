<?php
// admin/actions/download-document.php - Descargar documento (admin)
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_admin();

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    http_response_code(400);
    die('ID de documento inválido');
}

// Obtener documento
$stmt = $pdo->prepare("SELECT * FROM client_documents WHERE id = ?");
$stmt->execute([$id]);
$doc = $stmt->fetch();

if (!$doc) {
    http_response_code(404);
    die('Documento no encontrado');
}

$filepath = __DIR__ . '/../../' . $doc['ruta_archivo'];

if (!file_exists($filepath)) {
    http_response_code(404);
    die('Archivo no encontrado en el servidor');
}

// Enviar archivo
header('Content-Type: ' . ($doc['mime_type'] ?: 'application/octet-stream'));
header('Content-Disposition: attachment; filename="' . basename($doc['nombre_archivo']) . '"');
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: no-cache, must-revalidate');

readfile($filepath);
exit;
