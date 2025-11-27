<?php
// admin/actions/mark-payment-paid.php - Marcar pago como pagado
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/clients.php');
    exit;
}

$payment_id = intval($_POST['payment_id'] ?? 0);
$client_id = intval($_POST['client_id'] ?? 0);

if (!$payment_id) {
    $_SESSION['flash_message'] = 'Pago no especificado';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /admin/clients.php');
    exit;
}

try {
    $stmt = $pdo->prepare("
        UPDATE payments SET
            status = 'pagado',
            fecha_pago = CURDATE(),
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$payment_id]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['flash_message'] = 'Pago marcado como pagado';
        $_SESSION['flash_type'] = 'success';
    } else {
        throw new Exception('No se pudo actualizar el pago');
    }

} catch (Exception $e) {
    $_SESSION['flash_message'] = 'Error: ' . $e->getMessage();
    $_SESSION['flash_type'] = 'danger';
}

if ($client_id) {
    header('Location: /admin/client-detail.php?id=' . $client_id);
} else {
    header('Location: /admin/clients.php');
}
exit;
