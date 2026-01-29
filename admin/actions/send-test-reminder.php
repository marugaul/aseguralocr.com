<?php
// admin/actions/send-test-reminder.php - Send test reminder email
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_admin();

header('Content-Type: application/json');

try {
    // Get configuration
    $stmt = $pdo->query("SELECT * FROM payment_reminders_config WHERE id = 1");
    $config = $stmt->fetch();

    if (!$config) {
        throw new Exception("No hay configuración de recordatorios");
    }

    // Get email from POST (user can specify custom email)
    $emailTo = $_POST['email_to'] ?? '';

    if (empty($emailTo) || !filter_var($emailTo, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Por favor ingresa un email válido");
    }

    // Create test data
    $testData = [
        'numero_poliza' => 'TEST-2026-001',
        'tipo_seguro' => 'auto',
        'nombre_cliente' => 'Cliente de Prueba',
        'monto' => 150000.00,
        'moneda' => 'colones',
        'fecha_vencimiento' => date('Y-m-d', strtotime('+30 days')),
        'tipo_pago' => 'cuota_mensual'
    ];

    // Replace variables
    $tipoPolizaNombres = [
        'hogar' => 'Hogar',
        'auto' => 'Auto',
        'vida' => 'Vida',
        'salud' => 'Salud',
        'otros' => 'Otros'
    ];
    $tipoPoliza = $tipoPolizaNombres[$testData['tipo_seguro']] ?? ucfirst($testData['tipo_seguro']);

    $variables = [
        '{numero_poliza}' => $testData['numero_poliza'],
        '{tipo_poliza}' => $tipoPoliza,
        '{nombre_cliente}' => $testData['nombre_cliente'],
        '{monto}' => number_format($testData['monto'], 2),
        '{moneda}' => $testData['moneda'] === 'dolares' ? '$' : '₡',
        '{fecha_vencimiento}' => date('d/m/Y', strtotime($testData['fecha_vencimiento'])),
        '{tipo_pago}' => ucfirst(str_replace('_', ' ', $testData['tipo_pago']))
    ];

    $subject = str_replace(array_keys($variables), array_values($variables), $config['email_subject']);
    $body = str_replace(array_keys($variables), array_values($variables), $config['email_template']);

    // Send email
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: {$config['email_from_name']} <{$config['email_from']}>\r\n";
    $headers .= "Reply-To: {$config['email_from']}\r\n";

    if (mail($emailTo, $subject, $body, $headers)) {
        echo json_encode([
            'success' => true,
            'message' => 'Email de prueba enviado a ' . $emailTo
        ]);
    } else {
        throw new Exception("Error al enviar el email");
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
