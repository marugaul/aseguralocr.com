<?php
// setup-recordatorios.php - Setup payment reminders tables
require_once __DIR__ . '/includes/db.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== CONFIGURACIÓN DE RECORDATORIOS ===\n\n";

try {
    // Check if tables exist
    $stmt = $pdo->query("SHOW TABLES LIKE 'payment_reminders_config'");
    $configExists = $stmt->rowCount() > 0;

    $stmt = $pdo->query("SHOW TABLES LIKE 'payment_reminders_sent'");
    $sentExists = $stmt->rowCount() > 0;

    echo "Estado actual:\n";
    echo "  payment_reminders_config: " . ($configExists ? "✓ EXISTE" : "✗ NO EXISTE") . "\n";
    echo "  payment_reminders_sent: " . ($sentExists ? "✓ EXISTE" : "✗ NO EXISTE") . "\n\n";

    if (!$configExists) {
        echo "Creando tabla payment_reminders_config...\n";
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
        echo "✓ Tabla payment_reminders_config creada\n\n";
    }

    if (!$sentExists) {
        echo "Creando tabla payment_reminders_sent...\n";
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
        echo "✓ Tabla payment_reminders_sent creada\n\n";
    }

    // Insert default configuration
    $stmt = $pdo->query("SELECT COUNT(*) FROM payment_reminders_config");
    $configCount = $stmt->fetchColumn();

    if ($configCount == 0) {
        echo "Insertando configuración por defecto...\n";

        $defaultTemplate = file_get_contents(__DIR__ . '/SQL/payment_reminders.sql');
        preg_match("/email_template TEXT,.*?INSERT INTO.*?\('([^']*(?:''[^']*)*)'\\)/s", $defaultTemplate, $matches);

        if (empty($matches[1])) {
            // Fallback simple template
            $template = '
<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif;">
    <h2>Recordatorio de Pago</h2>
    <p>Estimado/a {nombre_cliente},</p>
    <p>Le recordamos que tiene un pago pendiente:</p>
    <ul>
        <li>Póliza: {numero_poliza}</li>
        <li>Monto: {moneda} {monto}</li>
        <li>Vencimiento: {fecha_vencimiento}</li>
    </ul>
    <p>Por favor realice el pago antes de la fecha indicada.</p>
</body>
</html>';
        } else {
            $template = str_replace("''", "'", $matches[1]);
        }

        $stmt = $pdo->prepare("
            INSERT INTO payment_reminders_config (email_template)
            VALUES (?)
        ");
        $stmt->execute([$template]);
        echo "✓ Configuración por defecto insertada\n\n";
    }

    echo "=== CONFIGURACIÓN COMPLETA ===\n\n";
    echo "Ahora puedes acceder a:\n";
    echo "https://www.aseguralocr.com/admin/recordatorios-config.php\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "\nDetalles técnicos:\n";
    echo $e->getTraceAsString();
}
?>
