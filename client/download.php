<?php
// client/download.php - Descarga segura de documentos para clientes
require_once __DIR__ . '/includes/client_auth.php';
require_client_login();

require_once __DIR__ . '/../includes/db.php';

$clientId = get_current_client_id();
$docId = intval($_GET['id'] ?? 0);

if (!$docId) {
    http_response_code(400);
    die('ID de documento inválido');
}

// Obtener documento - solo si pertenece al cliente y es visible
$stmt = $pdo->prepare("
    SELECT * FROM client_documents
    WHERE id = ? AND client_id = ? AND visible_cliente = 1
");
$stmt->execute([$docId, $clientId]);
$doc = $stmt->fetch();

if (!$doc) {
    http_response_code(404);
    die('Documento no encontrado o no tienes acceso');
}

// Soportar rutas absolutas (nuevas) y relativas (antiguas)
$ruta = $doc['ruta_archivo'];
if (strpos($ruta, '/') === 0) {
    $filepath = $ruta;
} else {
    $filepath = __DIR__ . '/../' . $ruta;
}

if (!file_exists($filepath)) {
    http_response_code(404);
    die('Archivo no encontrado en el servidor');
}

// Enviar archivo
header('Content-Type: ' . ($doc['mime_type'] ?: 'application/octet-stream'));
header('Content-Disposition: attachment; filename="' . basename($doc['nombre_archivo']) . '"');
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

readfile($filepath);
exit;
