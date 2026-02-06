<?php
// admin/actions/mark-payment-paid.php - Mark a payment as paid
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_admin();

header('Content-Type: application/json');

try {
    $paymentId = (int)($_POST['payment_id'] ?? 0);

    if (!$paymentId) {
        throw new Exception('ID de pago no válido');
    }

    $stmt = $pdo->prepare("UPDATE payments SET status = 'pagado', fecha_pago = NOW(), updated_by = ? WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id'] ?? null, $paymentId]);

    if ($stmt->rowCount() === 0) {
        throw new Exception('Pago no encontrado');
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
