<?php
// admin/actions/save-payment.php - Registrar pago
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
    $policy_id = intval($_POST['policy_id'] ?? 0);
    $monto = floatval($_POST['monto'] ?? 0);
    $moneda = $_POST['moneda'] ?? 'colones';
    $tipo_pago = $_POST['tipo_pago'] ?? 'cuota_mensual';
    $fecha_vencimiento = $_POST['fecha_vencimiento'] ?? null;
    $fecha_pago = $_POST['fecha_pago'] ?? null;
    $metodo_pago = $_POST['metodo_pago'] ?? 'efectivo';
    $referencia_pago = trim($_POST['referencia_pago'] ?? '');

    if (!$policy_id) {
        throw new Exception('Debe seleccionar una póliza');
    }

    if ($monto <= 0) {
        throw new Exception('El monto debe ser mayor a cero');
    }

    // Verificar que la póliza pertenece al cliente
    $checkStmt = $pdo->prepare("SELECT id FROM policies WHERE id = ? AND client_id = ?");
    $checkStmt->execute([$policy_id, $client_id]);
    if (!$checkStmt->fetch()) {
        throw new Exception('Póliza no válida');
    }

    // Determinar estado
    $status = 'pendiente';
    if ($fecha_pago) {
        $status = 'pagado';
    } elseif ($fecha_vencimiento && strtotime($fecha_vencimiento) < time()) {
        $status = 'vencido';
    }

    // Manejar comprobante si se subió
    $comprobante_url = null;
    if (isset($_FILES['comprobante']) && $_FILES['comprobante']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['comprobante'];
        $uploadDir = __DIR__ . '/../../storage/payments/' . $client_id . '/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = date('Ymd_His') . '_comprobante.' . $extension;
        $filePath = $uploadDir . $fileName;

        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            $comprobante_url = '/storage/payments/' . $client_id . '/' . $fileName;
        }
    }

    $stmt = $pdo->prepare("
        INSERT INTO payments (policy_id, monto, moneda, tipo_pago, fecha_vencimiento, fecha_pago, status, metodo_pago, comprobante_url, referencia_pago, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $policy_id,
        $monto,
        $moneda,
        $tipo_pago,
        $fecha_vencimiento ?: null,
        $fecha_pago ?: null,
        $status,
        $metodo_pago,
        $comprobante_url,
        $referencia_pago ?: null
    ]);

    $_SESSION['flash_message'] = 'Pago registrado exitosamente';
    $_SESSION['flash_type'] = 'success';

} catch (Exception $e) {
    $_SESSION['flash_message'] = 'Error: ' . $e->getMessage();
    $_SESSION['flash_type'] = 'danger';
}

header('Location: /admin/client-detail.php?id=' . $client_id);
exit;
