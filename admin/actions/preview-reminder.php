<?php
// admin/actions/preview-reminder.php - Preview reminder email template
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_admin();

header('Content-Type: text/html; charset=UTF-8');

try {
    // Get configuration
    $stmt = $pdo->query("SELECT * FROM payment_reminders_config WHERE id = 1");
    $config = $stmt->fetch();

    if (!$config) {
        die('<div style="font-family: Arial; padding: 20px; background: #fee2e2; color: #dc2626; border-radius: 8px;">No hay configuración de recordatorios</div>');
    }

    // Create test data
    $testData = [
        'numero_poliza' => 'TEST-2026-001',
        'nombre_cliente' => 'Cliente de Prueba',
        'monto' => 150000.00,
        'moneda' => 'colones',
        'fecha_vencimiento' => date('Y-m-d', strtotime('+30 days')),
        'tipo_pago' => 'cuota_mensual'
    ];

    // Replace variables
    $variables = [
        '{numero_poliza}' => $testData['numero_poliza'],
        '{nombre_cliente}' => $testData['nombre_cliente'],
        '{monto}' => number_format($testData['monto'], 2),
        '{moneda}' => $testData['moneda'] === 'dolares' ? '$' : '₡',
        '{fecha_vencimiento}' => date('d/m/Y', strtotime($testData['fecha_vencimiento'])),
        '{tipo_pago}' => ucfirst(str_replace('_', ' ', $testData['tipo_pago']))
    ];

    $body = str_replace(array_keys($variables), array_values($variables), $config['email_template']);

    // Display preview with header
    echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vista Preliminar - Email de Recordatorio</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background: #f5f5f5;
        }
        .preview-header {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .preview-header h1 {
            margin: 0;
            font-size: 1.5rem;
        }
        .preview-header p {
            margin: 10px 0 0;
            opacity: 0.9;
            font-size: 0.9rem;
        }
        .preview-content {
            max-width: 800px;
            margin: 30px auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            padding: 20px;
        }
        .btn-close {
            display: inline-block;
            margin: 20px auto;
            padding: 12px 32px;
            background: #64748b;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            text-align: center;
        }
        .btn-close:hover {
            background: #475569;
        }
    </style>
</head>
<body>
    <div class="preview-header">
        <h1>👁️ Vista Preliminar del Email</h1>
        <p>Así verá el cliente el email de recordatorio</p>
    </div>

    <div class="preview-content">
        ' . $body . '
    </div>

    <div style="text-align: center; padding: 20px;">
        <a href="javascript:window.close()" class="btn-close">✖ Cerrar Vista Preliminar</a>
    </div>
</body>
</html>';

} catch (Exception $e) {
    echo '<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Error</title></head>
<body style="font-family: Arial; padding: 40px; background: #fee2e2;">
    <div style="max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; border-left: 4px solid #dc2626;">
        <h2 style="color: #dc2626; margin: 0 0 15px;">❌ Error</h2>
        <p style="color: #374151;">' . htmlspecialchars($e->getMessage()) . '</p>
    </div>
</body>
</html>';
}
?>
