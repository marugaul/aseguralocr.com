<?php
// admin/actions/save-policy.php - Save policy to database
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_admin();

try {
    // Validate required fields
    $required = ['client_id', 'numero_poliza', 'tipo_seguro', 'fecha_emision',
                 'fecha_inicio_vigencia', 'fecha_fin_vigencia', 'prima_anual', 'moneda', 'status'];

    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("El campo {$field} es requerido");
        }
    }

    // Prepare coberturas as JSON
    $coberturas = isset($_POST['coberturas']) ? json_encode($_POST['coberturas']) : null;

    // Handle file upload
    $archivoPolizaUrl = null;
    if (!empty($_FILES['archivo_poliza']['name'])) {
        $uploadDir = __DIR__ . '/../../storage/policies/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $ext = pathinfo($_FILES['archivo_poliza']['name'], PATHINFO_EXTENSION);
        $filename = 'policy_' . $_POST['numero_poliza'] . '_' . time() . '.' . $ext;
        $uploadPath = $uploadDir . $filename;

        if (move_uploaded_file($_FILES['archivo_poliza']['tmp_name'], $uploadPath)) {
            $archivoPolizaUrl = '/storage/policies/' . $filename;
        }
    }

    // Insert policy
    $stmt = $pdo->prepare("
        INSERT INTO policies (
            client_id, numero_poliza, tipo_seguro, aseguradora, coberturas,
            monto_asegurado, prima_anual, prima_mensual, prima_trimestral, prima_semestral, moneda,
            fecha_emision, fecha_inicio_vigencia, fecha_fin_vigencia,
            status, detalles_bien_asegurado, notas_admin, archivo_poliza_url,
            created_by
        ) VALUES (
            :client_id, :numero_poliza, :tipo_seguro, :aseguradora, :coberturas,
            :monto_asegurado, :prima_anual, :prima_mensual, :prima_trimestral, :prima_semestral, :moneda,
            :fecha_emision, :fecha_inicio_vigencia, :fecha_fin_vigencia,
            :status, :detalles_bien_asegurado, :notas_admin, :archivo_poliza_url,
            :created_by
        )
    ");

    $stmt->execute([
        ':client_id' => $_POST['client_id'],
        ':numero_poliza' => $_POST['numero_poliza'],
        ':tipo_seguro' => $_POST['tipo_seguro'],
        ':aseguradora' => $_POST['aseguradora'] ?? 'INS',
        ':coberturas' => $coberturas,
        ':monto_asegurado' => $_POST['monto_asegurado'] ?: null,
        ':prima_anual' => $_POST['prima_anual'],
        ':prima_mensual' => $_POST['prima_mensual'] ?: null,
        ':prima_trimestral' => $_POST['prima_trimestral'] ?: null,
        ':prima_semestral' => $_POST['prima_semestral'] ?: null,
        ':moneda' => $_POST['moneda'],
        ':fecha_emision' => $_POST['fecha_emision'],
        ':fecha_inicio_vigencia' => $_POST['fecha_inicio_vigencia'],
        ':fecha_fin_vigencia' => $_POST['fecha_fin_vigencia'],
        ':status' => $_POST['status'],
        ':detalles_bien_asegurado' => $_POST['detalles_bien_asegurado'] ?: null,
        ':notas_admin' => $_POST['notas_admin'] ?: null,
        ':archivo_poliza_url' => $archivoPolizaUrl,
        ':created_by' => $_SESSION['admin_id'] ?? null
    ]);

    $policyId = $pdo->lastInsertId();

    // Create payment plan if requested
    if (!empty($_POST['crear_plan_pagos'])) {
        $frecuencia = $_POST['frecuencia_pago'] ?? 'anual';
        $anos = intval($_POST['anos_plan'] ?? 1);
        $fechaInicio = new DateTime($_POST['fecha_inicio_vigencia']);

        // Determine payment amount and interval based on frequency
        $montoPago = 0;
        $tipoPago = '';
        $intervalo = '';
        $pagosAnuales = 1;

        switch ($frecuencia) {
            case 'mensual':
                $montoPago = floatval($_POST['prima_mensual'] ?: ($_POST['prima_anual'] / 12));
                $tipoPago = 'cuota_mensual';
                $intervalo = '+1 month';
                $pagosAnuales = 12;
                break;
            case 'trimestral':
                $montoPago = floatval($_POST['prima_trimestral'] ?: ($_POST['prima_anual'] / 4));
                $tipoPago = 'cuota_trimestral';
                $intervalo = '+3 months';
                $pagosAnuales = 4;
                break;
            case 'semestral':
                $montoPago = floatval($_POST['prima_semestral'] ?: ($_POST['prima_anual'] / 2));
                $tipoPago = 'cuota_semestral';
                $intervalo = '+6 months';
                $pagosAnuales = 2;
                break;
            case 'anual':
            default:
                $montoPago = floatval($_POST['prima_anual']);
                $tipoPago = 'cuota_anual';
                $intervalo = '+1 year';
                $pagosAnuales = 1;
                break;
        }

        $totalPagos = $pagosAnuales * $anos;

        // Create all payment records
        $stmtPay = $pdo->prepare("
            INSERT INTO payments (policy_id, monto, moneda, tipo_pago, fecha_vencimiento, status, created_by)
            VALUES (:policy_id, :monto, :moneda, :tipo_pago, :fecha_vencimiento, 'pendiente', :created_by)
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
                ':policy_id' => $policyId,
                ':monto' => $montoPago,
                ':moneda' => $_POST['moneda'],
                ':tipo_pago' => $tipoPago,
                ':fecha_vencimiento' => $fechaVencimiento->format('Y-m-d'),
                ':created_by' => $_SESSION['admin_id'] ?? null
            ]);
        }
    }

    // Create notification for client
    $stmt = $pdo->prepare("
        INSERT INTO client_notifications (client_id, tipo, titulo, mensaje, policy_id)
        VALUES (:client_id, 'poliza_emitida', :titulo, :mensaje, :policy_id)
    ");
    $stmt->execute([
        ':client_id' => $_POST['client_id'],
        ':titulo' => '¡Póliza Emitida!',
        ':mensaje' => 'Tu póliza #' . $_POST['numero_poliza'] . ' ha sido emitida y está lista.',
        ':policy_id' => $policyId
    ]);

    // Redirect to client detail page
    $_SESSION['success_message'] = 'Póliza registrada exitosamente';
    header('Location: /admin/client-detail.php?id=' . $_POST['client_id']);
    exit;

} catch (Exception $e) {
    error_log("Error saving policy: " . $e->getMessage());
    $_SESSION['error_message'] = 'Error al guardar la póliza: ' . $e->getMessage();
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/admin/clients.php'));
    exit;
}
