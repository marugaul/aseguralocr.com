<?php
// admin/actions/regenerar-plan-pagos.php - Regenerate payment plan with new frequency
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_admin();

try {
    // Validate required fields
    $required = ['policy_id', 'client_id', 'frecuencia_pago', 'anos_plan', 'moneda',
                 'prima_anual', 'fecha_inicio_vigencia'];

    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("El campo {$field} es requerido");
        }
    }

    $policyId = intval($_POST['policy_id']);
    $clientId = intval($_POST['client_id']);
    $frecuencia = $_POST['frecuencia_pago'];
    $anos = intval($_POST['anos_plan']);
    $moneda = $_POST['moneda'];
    $fechaInicio = new DateTime($_POST['fecha_inicio_vigencia']);

    // Start transaction
    $pdo->beginTransaction();

    // Delete existing payments for this policy
    $stmtDelete = $pdo->prepare("DELETE FROM payments WHERE policy_id = ?");
    $stmtDelete->execute([$policyId]);

    // Determine payment amount and interval based on frequency
    $montoPago = 0;
    $tipoPago = '';
    $pagosAnuales = 1;

    switch ($frecuencia) {
        case 'mensual':
            $montoPago = floatval($_POST['prima_mensual'] ?: ($_POST['prima_anual'] / 12));
            $tipoPago = 'cuota_mensual';
            $pagosAnuales = 12;
            break;
        case 'trimestral':
            $montoPago = floatval($_POST['prima_trimestral'] ?: ($_POST['prima_anual'] / 4));
            $tipoPago = 'cuota_trimestral';
            $pagosAnuales = 4;
            break;
        case 'semestral':
            $montoPago = floatval($_POST['prima_semestral'] ?: ($_POST['prima_anual'] / 2));
            $tipoPago = 'cuota_semestral';
            $pagosAnuales = 2;
            break;
        case 'anual':
        default:
            $montoPago = floatval($_POST['prima_anual']);
            $tipoPago = 'cuota_anual';
            $pagosAnuales = 1;
            break;
    }

    $totalPagos = $pagosAnuales * $anos;

    // Create all payment records
    $stmtPay = $pdo->prepare("
        INSERT INTO payments (client_id, policy_id, monto, moneda, tipo_pago, fecha_vencimiento, status, created_by)
        VALUES (:client_id, :policy_id, :monto, :moneda, :tipo_pago, :fecha_vencimiento, 'pendiente', :created_by)
    ");

    for ($i = 0; $i < $totalPagos; $i++) {
        $fechaVencimiento = clone $fechaInicio;

        // Calculate the date based on payment number
        if ($frecuencia === 'mensual') {
            $fechaVencimiento->modify("+{$i} month");
        } elseif ($frecuencia === 'trimestral') {
            $meses = $i * 3;
            $fechaVencimiento->modify("+{$meses} months");
        } elseif ($frecuencia === 'semestral') {
            $meses = $i * 6;
            $fechaVencimiento->modify("+{$meses} months");
        } else { // anual
            $fechaVencimiento->modify("+{$i} year");
        }

        $stmtPay->execute([
            ':client_id' => $clientId,
            ':policy_id' => $policyId,
            ':monto' => $montoPago,
            ':moneda' => $moneda,
            ':tipo_pago' => $tipoPago,
            ':fecha_vencimiento' => $fechaVencimiento->format('Y-m-d'),
            ':created_by' => $_SESSION['admin_id'] ?? null
        ]);
    }

    // Commit transaction
    $pdo->commit();

    // Create notification for client
    $stmtNotif = $pdo->prepare("
        INSERT INTO client_notifications (client_id, tipo, titulo, mensaje, policy_id)
        VALUES (:client_id, 'plan_actualizado', :titulo, :mensaje, :policy_id)
    ");
    $stmtNotif->execute([
        ':client_id' => $clientId,
        ':titulo' => 'Plan de Pagos Actualizado',
        ':mensaje' => 'Se ha regenerado tu plan de pagos con frecuencia ' . $frecuencia . '. Revisa tu nueva información.',
        ':policy_id' => $policyId
    ]);

    $_SESSION['success_message'] = "Plan de pagos regenerado exitosamente con frecuencia {$frecuencia}. Se crearon {$totalPagos} pagos.";
    header('Location: /admin/client-detail.php?id=' . $clientId);
    exit;

} catch (Exception $e) {
    // Rollback on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log("Error regenerating payment plan: " . $e->getMessage());
    $_SESSION['error_message'] = 'Error al regenerar el plan de pagos: ' . $e->getMessage();
    header('Location: /admin/client-detail.php?id=' . ($_POST['client_id'] ?? ''));
    exit;
}
