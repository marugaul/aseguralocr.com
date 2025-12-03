<?php
// admin/actions/delete-document.php - Eliminar documento
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_admin();

$id = intval($_GET['id'] ?? 0);
$clientId = intval($_GET['client_id'] ?? 0);

if (!$id) {
    header('Location: /admin/documents.php?error=' . urlencode('ID de documento inválido'));
    exit;
}

try {
    // Obtener documento
    $stmt = $pdo->prepare("SELECT * FROM client_documents WHERE id = ?");
    $stmt->execute([$id]);
    $doc = $stmt->fetch();

    if (!$doc) {
        throw new Exception('Documento no encontrado');
    }

    // Eliminar archivo físico (soportar rutas absolutas y relativas)
    $ruta = $doc['ruta_archivo'];
    if (strpos($ruta, '/') === 0) {
        $filepath = $ruta;
    } else {
        $filepath = __DIR__ . '/../../' . $ruta;
    }
    if (file_exists($filepath)) {
        unlink($filepath);
    }

    // Eliminar de la base de datos
    $stmt = $pdo->prepare("DELETE FROM client_documents WHERE id = ?");
    $stmt->execute([$id]);

    $redirectClientId = $clientId ?: $doc['client_id'];
    $returnTo = $_GET['return'] ?? 'documents';

    if ($returnTo === 'detail') {
        header('Location: /admin/client-detail.php?id=' . $redirectClientId . '&msg=' . urlencode('Documento eliminado'));
    } else {
        header('Location: /admin/documents.php?client_id=' . $redirectClientId . '&success=' . urlencode('Documento eliminado'));
    }
    exit;

} catch (Exception $e) {
    $returnTo = $_GET['return'] ?? 'documents';
    if ($returnTo === 'detail') {
        header('Location: /admin/client-detail.php?id=' . $clientId . '&error=' . urlencode($e->getMessage()));
    } else {
        header('Location: /admin/documents.php?client_id=' . $clientId . '&error=' . urlencode($e->getMessage()));
    }
    exit;
}
