<?php
// admin/actions/save-document.php - Subir documento de cliente
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/clients.php');
    exit;
}

$client_id = intval($_POST['client_id'] ?? 0);
if (!$client_id) {
    $_SESSION['flash_message'] = 'Cliente no especificado';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /admin/clients.php');
    exit;
}

try {
    $nombre = trim($_POST['nombre'] ?? '');
    $tipo_documento = $_POST['tipo_documento'] ?? 'otro';
    $policy_id = intval($_POST['policy_id'] ?? 0) ?: null;
    $notas = trim($_POST['notas'] ?? '');

    if (empty($nombre)) {
        throw new Exception('El nombre del documento es requerido');
    }

    // Validar archivo
    if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Debe seleccionar un archivo válido');
    }

    $file = $_FILES['archivo'];
    $maxSize = 10 * 1024 * 1024; // 10MB

    if ($file['size'] > $maxSize) {
        throw new Exception('El archivo excede el tamaño máximo de 10MB');
    }

    // Validar tipo de archivo
    $allowedTypes = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/jpg',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        throw new Exception('Tipo de archivo no permitido');
    }

    // Crear directorio si no existe
    $uploadDir = __DIR__ . '/../../storage/documents/' . $client_id . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Generar nombre único
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = date('Ymd_His') . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $nombre) . '.' . $extension;
    $filePath = $uploadDir . $fileName;
    $relativePath = '/storage/documents/' . $client_id . '/' . $fileName;

    // Mover archivo
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        throw new Exception('Error al guardar el archivo');
    }

    // Guardar en base de datos
    $stmt = $pdo->prepare("
        INSERT INTO client_documents (client_id, policy_id, nombre, tipo_documento, archivo_nombre, archivo_path, archivo_size, archivo_mime, uploaded_by, notas, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $client_id,
        $policy_id,
        $nombre,
        $tipo_documento,
        $file['name'],
        $relativePath,
        $file['size'],
        $mimeType,
        $_SESSION['admin_id'] ?? null,
        $notas ?: null
    ]);

    $_SESSION['flash_message'] = 'Documento subido exitosamente';
    $_SESSION['flash_type'] = 'success';

} catch (Exception $e) {
    $_SESSION['flash_message'] = 'Error: ' . $e->getMessage();
    $_SESSION['flash_type'] = 'danger';
}

header('Location: /admin/client-detail.php?id=' . $client_id);
exit;
