<?php
// admin/setup-recordatorios.php - Setup payment reminders tables
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurar Recordatorios - AseguraloCR</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #1e3a8a; margin-bottom: 20px; }
        .status { padding: 15px; border-radius: 8px; margin: 15px 0; }
        .status.success { background: #d1fae5; color: #047857; }
        .status.error { background: #fee2e2; color: #dc2626; }
        .status.info { background: #dbeafe; color: #1e40af; }
        pre { background: #f8fafc; padding: 15px; border-radius: 6px; overflow-x: auto; }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Configuración de Recordatorios</h1>

<?php
try {
    echo '<div class="status info">Verificando tablas...</div>';

    // Check if tables exist
    $stmt = $pdo->query("SHOW TABLES LIKE 'payment_reminders_config'");
    $configExists = $stmt->rowCount() > 0;

    $stmt = $pdo->query("SHOW TABLES LIKE 'payment_reminders_sent'");
    $sentExists = $stmt->rowCount() > 0;

    echo '<pre>';
    echo "Estado actual:\n";
    echo "  payment_reminders_config: " . ($configExists ? "✓ EXISTE" : "✗ NO EXISTE") . "\n";
    echo "  payment_reminders_sent: " . ($sentExists ? "✓ EXISTE" : "✗ NO EXISTE") . "\n";
    echo '</pre>';

    $needsSetup = !$configExists || !$sentExists;

    if ($needsSetup) {
        echo '<div class="status info">Creando tablas necesarias...</div>';

        if (!$configExists) {
            echo '<p>Creando tabla <strong>payment_reminders_config</strong>...</p>';
            $pdo->exec("
                CREATE TABLE payment_reminders_config (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    send_30_days_before BOOLEAN DEFAULT TRUE,
                    send_15_days_before BOOLEAN DEFAULT TRUE,
                    send_1_day_before BOOLEAN DEFAULT TRUE,
                    email_from VARCHAR(255) DEFAULT 'noreply@aseguralocr.com',
                    email_from_name VARCHAR(255) DEFAULT 'AseguraloCR',
                    email_subject VARCHAR(255) DEFAULT 'Recordatorio: Vencimiento de su póliza #{numero_poliza}',
                    email_template TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            ");
            echo '<p style="color: #047857;">✓ Tabla creada exitosamente</p>';
        }

        if (!$sentExists) {
            echo '<p>Creando tabla <strong>payment_reminders_sent</strong>...</p>';
            $pdo->exec("
                CREATE TABLE payment_reminders_sent (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    payment_id INT NOT NULL,
                    reminder_type ENUM('30_days', '15_days', '1_day') NOT NULL,
                    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    email_to VARCHAR(255) NOT NULL,
                    status ENUM('sent', 'failed') DEFAULT 'sent',
                    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE,
                    UNIQUE KEY unique_reminder (payment_id, reminder_type)
                )
            ");
            echo '<p style="color: #047857;">✓ Tabla creada exitosamente</p>';
        }

        // Insert default configuration if empty
        $stmt = $pdo->query("SELECT COUNT(*) FROM payment_reminders_config");
        $configCount = $stmt->fetchColumn();

        if ($configCount == 0) {
            echo '<p>Insertando configuración por defecto...</p>';

            $defaultTemplate = '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recordatorio de Pago</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center" style="padding: 40px 0;">
                <table width="600" cellpadding="0" cellspacing="0" style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <tr>
                        <td style="background: linear-gradient(135deg, #1e3a8a, #3b82f6); padding: 30px; text-align: center;">
                            <h1 style="color: white; margin: 0;">AseguraloCR</h1>
                            <p style="color: #dbeafe; margin: 10px 0 0;">Recordatorio de Pago</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 40px 30px;">
                            <h2 style="color: #1e293b; margin: 0 0 20px;">Estimado/a {nombre_cliente},</h2>
                            <p style="color: #475569; line-height: 1.6;">Le recordamos que tiene un pago pendiente para su póliza de seguro.</p>
                            <table width="100%" style="background: #f8fafc; border-radius: 8px; padding: 20px; margin: 20px 0;">
                                <tr><td style="color: #64748b; padding: 8px;">Número de Póliza:</td><td style="color: #1e293b; font-weight: 700; text-align: right;">{numero_poliza}</td></tr>
                                <tr><td style="color: #64748b; padding: 8px;">Monto a Pagar:</td><td style="color: #1e293b; font-weight: 700; font-size: 18px; text-align: right;">{moneda} {monto}</td></tr>
                                <tr><td style="color: #64748b; padding: 8px;">Fecha de Vencimiento:</td><td style="color: #dc2626; font-weight: 700; text-align: right;">{fecha_vencimiento}</td></tr>
                            </table>
                            <p style="color: #475569;">Para mantener su cobertura activa, realice el pago antes de la fecha de vencimiento.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background: #f8fafc; padding: 20px; text-align: center; border-top: 1px solid #e2e8f0;">
                            <p style="color: #64748b; font-size: 12px; margin: 0;">© 2026 AseguraloCR. Todos los derechos reservados.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';

            $stmt = $pdo->prepare("INSERT INTO payment_reminders_config (email_template) VALUES (?)");
            $stmt->execute([$defaultTemplate]);
            echo '<p style="color: #047857;">✓ Configuración por defecto insertada</p>';
        }

        echo '<div class="status success">';
        echo '<h3>✅ Configuración Completa</h3>';
        echo '<p>Las tablas se crearon exitosamente y la configuración por defecto fue insertada.</p>';
        echo '</div>';
    } else {
        echo '<div class="status success">';
        echo '<h3>✅ Todo Configurado</h3>';
        echo '<p>Las tablas ya existen. No es necesario configurar nada.</p>';
        echo '</div>';
    }

    echo '<a href="/admin/recordatorios-config.php" class="btn">→ Ir a Configuración de Recordatorios</a>';

} catch (Exception $e) {
    echo '<div class="status error">';
    echo '<h3>❌ Error</h3>';
    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    echo '</div>';
}
?>

    </div>
</body>
</html>
