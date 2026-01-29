<?php
// cron-send-reminders.php - Send automatic payment reminders
// Run daily via cron: 0 8 * * * php /home/asegural/public_html/aseguralocr/cron-send-reminders.php

require_once __DIR__ . '/includes/db.php';

// Get configuration
$stmt = $pdo->query("SELECT * FROM payment_reminders_config WHERE id = 1");
$config = $stmt->fetch();

if (!$config) {
    die("No configuration found\n");
}

$today = date('Y-m-d');
$sent = 0;
$failed = 0;

echo "=== Payment Reminders - " . date('Y-m-d H:i:s') . " ===\n\n";

// Function to send email
function sendReminderEmail($to, $subject, $body, $from, $fromName) {
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: {$fromName} <{$from}>\r\n";
    $headers .= "Reply-To: {$from}\r\n";

    return mail($to, $subject, $body, $headers);
}

// Function to replace variables in template
function replaceVariables($template, $data) {
    $variables = [
        '{numero_poliza}' => $data['numero_poliza'],
        '{nombre_cliente}' => $data['nombre_cliente'],
        '{monto}' => number_format($data['monto'], 2),
        '{moneda}' => $data['moneda'] === 'dolares' ? '$' : '₡',
        '{fecha_vencimiento}' => date('d/m/Y', strtotime($data['fecha_vencimiento'])),
        '{tipo_pago}' => ucfirst(str_replace('_', ' ', $data['tipo_pago']))
    ];

    return str_replace(array_keys($variables), array_values($variables), $template);
}

// 1. Reminders 30 days before
if ($config['send_30_days_before']) {
    echo "Checking 30-day reminders...\n";

    $date30 = date('Y-m-d', strtotime('+30 days'));

    $stmt = $pdo->prepare("
        SELECT
            p.id as payment_id,
            p.monto,
            p.moneda,
            p.tipo_pago,
            p.fecha_vencimiento,
            pol.numero_poliza,
            c.id as client_id,
            c.nombre_completo as nombre_cliente,
            c.email
        FROM payments p
        JOIN policies pol ON p.policy_id = pol.id
        JOIN clients c ON p.client_id = c.id
        LEFT JOIN payment_reminders_sent rs ON p.id = rs.payment_id AND rs.reminder_type = '30_days'
        WHERE p.status = 'pendiente'
            AND p.fecha_vencimiento = ?
            AND rs.id IS NULL
    ");
    $stmt->execute([$date30]);
    $payments = $stmt->fetchAll();

    foreach ($payments as $payment) {
        $subject = str_replace('{numero_poliza}', $payment['numero_poliza'], $config['email_subject']);
        $body = replaceVariables($config['email_template'], $payment);

        if (sendReminderEmail($payment['email'], $subject, $body, $config['email_from'], $config['email_from_name'])) {
            // Mark as sent
            $stmtSent = $pdo->prepare("
                INSERT INTO payment_reminders_sent (payment_id, reminder_type, email_to, status)
                VALUES (?, '30_days', ?, 'sent')
            ");
            $stmtSent->execute([$payment['payment_id'], $payment['email']]);
            echo "  ✓ Sent to {$payment['nombre_cliente']} ({$payment['email']})\n";
            $sent++;
        } else {
            $stmtFailed = $pdo->prepare("
                INSERT INTO payment_reminders_sent (payment_id, reminder_type, email_to, status)
                VALUES (?, '30_days', ?, 'failed')
            ");
            $stmtFailed->execute([$payment['payment_id'], $payment['email']]);
            echo "  ✗ Failed to {$payment['email']}\n";
            $failed++;
        }
    }
}

// 2. Reminders 15 days before
if ($config['send_15_days_before']) {
    echo "\nChecking 15-day reminders...\n";

    $date15 = date('Y-m-d', strtotime('+15 days'));

    $stmt = $pdo->prepare("
        SELECT
            p.id as payment_id,
            p.monto,
            p.moneda,
            p.tipo_pago,
            p.fecha_vencimiento,
            pol.numero_poliza,
            c.id as client_id,
            c.nombre_completo as nombre_cliente,
            c.email
        FROM payments p
        JOIN policies pol ON p.policy_id = pol.id
        JOIN clients c ON p.client_id = c.id
        LEFT JOIN payment_reminders_sent rs ON p.id = rs.payment_id AND rs.reminder_type = '15_days'
        WHERE p.status = 'pendiente'
            AND p.fecha_vencimiento = ?
            AND rs.id IS NULL
    ");
    $stmt->execute([$date15]);
    $payments = $stmt->fetchAll();

    foreach ($payments as $payment) {
        $subject = str_replace('{numero_poliza}', $payment['numero_poliza'], $config['email_subject']);
        $body = replaceVariables($config['email_template'], $payment);

        if (sendReminderEmail($payment['email'], $subject, $body, $config['email_from'], $config['email_from_name'])) {
            $stmtSent = $pdo->prepare("
                INSERT INTO payment_reminders_sent (payment_id, reminder_type, email_to, status)
                VALUES (?, '15_days', ?, 'sent')
            ");
            $stmtSent->execute([$payment['payment_id'], $payment['email']]);
            echo "  ✓ Sent to {$payment['nombre_cliente']} ({$payment['email']})\n";
            $sent++;
        } else {
            $stmtFailed = $pdo->prepare("
                INSERT INTO payment_reminders_sent (payment_id, reminder_type, email_to, status)
                VALUES (?, '15_days', ?, 'failed')
            ");
            $stmtFailed->execute([$payment['payment_id'], $payment['email']]);
            echo "  ✗ Failed to {$payment['email']}\n";
            $failed++;
        }
    }
}

// 3. Reminders 1 day before
if ($config['send_1_day_before']) {
    echo "\nChecking 1-day reminders...\n";

    $date1 = date('Y-m-d', strtotime('+1 day'));

    $stmt = $pdo->prepare("
        SELECT
            p.id as payment_id,
            p.monto,
            p.moneda,
            p.tipo_pago,
            p.fecha_vencimiento,
            pol.numero_poliza,
            c.id as client_id,
            c.nombre_completo as nombre_cliente,
            c.email
        FROM payments p
        JOIN policies pol ON p.policy_id = pol.id
        JOIN clients c ON p.client_id = c.id
        LEFT JOIN payment_reminders_sent rs ON p.id = rs.payment_id AND rs.reminder_type = '1_day'
        WHERE p.status = 'pendiente'
            AND p.fecha_vencimiento = ?
            AND rs.id IS NULL
    ");
    $stmt->execute([$date1]);
    $payments = $stmt->fetchAll();

    foreach ($payments as $payment) {
        $subject = str_replace('{numero_poliza}', $payment['numero_poliza'], $config['email_subject']);
        $body = replaceVariables($config['email_template'], $payment);

        if (sendReminderEmail($payment['email'], $subject, $body, $config['email_from'], $config['email_from_name'])) {
            $stmtSent = $pdo->prepare("
                INSERT INTO payment_reminders_sent (payment_id, reminder_type, email_to, status)
                VALUES (?, '1_day', ?, 'sent')
            ");
            $stmtSent->execute([$payment['payment_id'], $payment['email']]);
            echo "  ✓ Sent to {$payment['nombre_cliente']} ({$payment['email']})\n";
            $sent++;
        } else {
            $stmtFailed = $pdo->prepare("
                INSERT INTO payment_reminders_sent (payment_id, reminder_type, email_to, status)
                VALUES (?, '1_day', ?, 'failed')
            ");
            $stmtFailed->execute([$payment['payment_id'], $payment['email']]);
            echo "  ✗ Failed to {$payment['email']}\n";
            $failed++;
        }
    }
}

echo "\n=== Summary ===\n";
echo "Sent: {$sent}\n";
echo "Failed: {$failed}\n";
echo "Done.\n";
?>
