<?php
// admin/actions/save-note.php - Agregar nota a cliente
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
    $nota = trim($_POST['nota'] ?? '');
    $tipo = $_POST['tipo'] ?? 'general';
    $policy_id = intval($_POST['policy_id'] ?? 0) ?: null;

    if (empty($nota)) {
        throw new Exception('La nota no puede estar vacía');
    }

    // Validar tipo
    $tiposValidos = ['general', 'llamada', 'email', 'visita', 'reclamo', 'renovacion', 'pago'];
    if (!in_array($tipo, $tiposValidos)) {
        $tipo = 'general';
    }

    $stmt = $pdo->prepare("
        INSERT INTO client_notes (client_id, policy_id, nota, tipo, created_by, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $client_id,
        $policy_id,
        $nota,
        $tipo,
        $_SESSION['admin_id'] ?? null
    ]);

    $_SESSION['flash_message'] = 'Nota agregada exitosamente';
    $_SESSION['flash_type'] = 'success';

} catch (Exception $e) {
    $_SESSION['flash_message'] = 'Error: ' . $e->getMessage();
    $_SESSION['flash_type'] = 'danger';
}

header('Location: /admin/client-detail.php?id=' . $client_id);
exit;
