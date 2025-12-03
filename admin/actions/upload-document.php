<?php
// admin/actions/upload-document.php - Subir documento para cliente
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/documents.php');
    exit;
}

$clientId = intval($_POST['client_id'] ?? 0);
if (!$clientId) {
    header('Location: /admin/documents.php?error=' . urlencode('Cliente no válido'));
    exit;
}

try {
    // Validar archivo
    if (!isset($_FILES['documento']) || $_FILES['documento']['error'] !== UPLOAD_ERR_OK) {
        $errorMsg = match($_FILES['documento']['error'] ?? -1) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'El archivo es demasiado grande',
            UPLOAD_ERR_NO_FILE => 'No se seleccionó ningún archivo',
            default => 'Error al subir el archivo'
        };
        throw new Exception($errorMsg);
    }

    $file = $_FILES['documento'];
    $maxSize = 10 * 1024 * 1024; // 10MB

    if ($file['size'] > $maxSize) {
        throw new Exception('El archivo excede el tamaño máximo de 10MB');
    }

    // Validar tipo de archivo
    $allowedTypes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'image/jpeg',
        'image/png',
        'image/gif'
    ];
    $allowedExts = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif'];

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($ext, $allowedExts)) {
        throw new Exception('Tipo de archivo no permitido. Use PDF, Word o imágenes.');
    }

    // Crear directorio si no existe
    $uploadDir = __DIR__ . '/../../uploads/documents/' . $clientId . '/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception('Error al crear el directorio de subida. Verifique permisos.');
        }
    }

    // Verificar que el directorio es escribible
    if (!is_writable($uploadDir)) {
        throw new Exception('El directorio de subida no tiene permisos de escritura.');
    }

    // Generar nombre único para el archivo
    $newFilename = date('Ymd_His') . '_' . uniqid() . '.' . $ext;
    $filepath = $uploadDir . $newFilename;
    $relativePath = 'uploads/documents/' . $clientId . '/' . $newFilename;

    // Mover archivo
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Error al guardar el archivo. Código: ' . error_get_last()['message'] ?? 'desconocido');
    }

    // Verificar que el archivo se guardó correctamente
    if (!file_exists($filepath)) {
        throw new Exception('El archivo no se guardó correctamente en el servidor.');
    }

    // Obtener datos del formulario
    $nombre = trim($_POST['nombre'] ?? '');
    $tipo = $_POST['tipo'] ?? 'otro';
    $policyId = !empty($_POST['policy_id']) ? intval($_POST['policy_id']) : null;
    $descripcion = trim($_POST['descripcion'] ?? '');
    $visibleCliente = isset($_POST['visible_cliente']) ? 1 : 0;

    if (empty($nombre)) {
        $nombre = pathinfo($file['name'], PATHINFO_FILENAME);
    }

    // Guardar en base de datos
    $stmt = $pdo->prepare("
        INSERT INTO client_documents
        (client_id, policy_id, tipo, nombre, nombre_archivo, ruta_archivo, mime_type, tamano_bytes, descripcion, visible_cliente, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $clientId,
        $policyId,
        $tipo,
        $nombre,
        $file['name'],
        $relativePath,
        $mimeType,
        $file['size'],
        $descripcion,
        $visibleCliente,
        $_SESSION['admin_id'] ?? null
    ]);

    header('Location: /admin/documents.php?client_id=' . $clientId . '&success=' . urlencode('Documento subido correctamente'));
    exit;

} catch (Exception $e) {
    header('Location: /admin/documents.php?client_id=' . $clientId . '&error=' . urlencode($e->getMessage()));
    exit;
}
