<?php
// admin/recordatorios-config.php - Configure payment reminders
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

// Check if table exists first
try {
    $stmt = $pdo->query("SELECT * FROM payment_reminders_config WHERE id = 1");
    $config = $stmt->fetch() ?: [];
} catch (PDOException $e) {
    // Table doesn't exist, redirect to setup
    header('Location: /admin/setup-recordatorios.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $send30 = isset($_POST['send_30_days_before']) ? 1 : 0;
    $send15 = isset($_POST['send_15_days_before']) ? 1 : 0;
    $send1 = isset($_POST['send_1_day_before']) ? 1 : 0;
    $emailFrom = trim($_POST['email_from'] ?? 'noreply@aseguralocr.com');
    $emailFromName = trim($_POST['email_from_name'] ?? 'AseguraloCR');
    $emailSubject = trim($_POST['email_subject'] ?? 'Recordatorio de Pago');
    $emailTemplate = $_POST['email_template'] ?? '';

    if (empty($config)) {
        // Insert
        $stmt = $pdo->prepare("
            INSERT INTO payment_reminders_config (
                send_30_days_before, send_15_days_before, send_1_day_before,
                email_from, email_from_name, email_subject, email_template
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$send30, $send15, $send1, $emailFrom, $emailFromName, $emailSubject, $emailTemplate]);
    } else {
        // Update
        $stmt = $pdo->prepare("
            UPDATE payment_reminders_config SET
                send_30_days_before = ?,
                send_15_days_before = ?,
                send_1_day_before = ?,
                email_from = ?,
                email_from_name = ?,
                email_subject = ?,
                email_template = ?
            WHERE id = 1
        ");
        $stmt->execute([$send30, $send15, $send1, $emailFrom, $emailFromName, $emailSubject, $emailTemplate]);
    }

    $_SESSION['success_message'] = 'Configuración guardada exitosamente';
    header('Location: /admin/recordatorios-config.php');
    exit;
}

$pageTitle = "Configuración de Recordatorios";
include __DIR__ . '/includes/header.php';
?>

<style>
    .config-container {
        max-width: 1200px;
        margin: 0 auto;
    }
    .config-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        padding: 24px;
        margin-bottom: 24px;
    }
    .config-card h3 {
        margin: 0 0 20px 0;
        color: #1e293b;
        font-size: 1.2rem;
    }
    .form-group {
        margin-bottom: 20px;
    }
    .form-group label {
        display: block;
        font-weight: 600;
        margin-bottom: 8px;
        color: #374151;
    }
    .form-group input[type="text"],
    .form-group input[type="email"],
    .form-group textarea {
        width: 100%;
        padding: 10px 14px;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        font-size: 0.95rem;
    }
    .form-group textarea {
        font-family: 'Courier New', monospace;
        min-height: 400px;
    }
    .checkbox-group {
        background: #f8fafc;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    .checkbox-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px;
        margin-bottom: 10px;
        background: white;
        border-radius: 6px;
    }
    .checkbox-item input[type="checkbox"] {
        width: 20px;
        height: 20px;
        accent-color: #3b82f6;
    }
    .checkbox-item label {
        margin: 0;
        font-weight: 500;
        cursor: pointer;
    }
    .btn-save {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        padding: 12px 32px;
        border: none;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(16,185,129,0.4);
    }
    .btn-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(16,185,129,0.5);
    }
    .btn-test {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: white;
        padding: 10px 24px;
        border: none;
        border-radius: 8px;
        font-size: 0.9rem;
        font-weight: 600;
        cursor: pointer;
        margin-left: 12px;
    }
    .variables-help {
        background: #dbeafe;
        padding: 16px;
        border-radius: 8px;
        margin-top: 12px;
        font-size: 0.85rem;
        color: #1e40af;
    }
    .variables-help strong {
        display: block;
        margin-bottom: 8px;
    }
    .variables-help code {
        background: white;
        padding: 2px 6px;
        border-radius: 4px;
        font-family: 'Courier New', monospace;
    }
</style>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1>⚙️ Configuración de Recordatorios</h1>
        <p>Configura los recordatorios automáticos de pago por email</p>
    </div>
</div>

<?php if (!empty($_SESSION['success_message'])): ?>
<div style="background: #d1fae5; color: #047857; padding: 12px 20px; border-radius: 10px; margin-bottom: 20px;">
    ✅ <?= htmlspecialchars($_SESSION['success_message']) ?>
</div>
<?php unset($_SESSION['success_message']); endif; ?>

<div class="config-container">
    <form method="POST">

        <!-- Frecuencia de Envío -->
        <div class="config-card">
            <h3>📅 Frecuencia de Envío</h3>
            <p style="color: #64748b; margin-bottom: 20px;">Selecciona cuándo se enviarán los recordatorios automáticos</p>

            <div class="checkbox-group">
                <div class="checkbox-item">
                    <input type="checkbox" name="send_30_days_before" id="send30" value="1" <?= !empty($config['send_30_days_before']) ? 'checked' : '' ?>>
                    <label for="send30">Enviar recordatorio <strong>30 días antes</strong> del vencimiento</label>
                </div>

                <div class="checkbox-item">
                    <input type="checkbox" name="send_15_days_before" id="send15" value="1" <?= !empty($config['send_15_days_before']) ? 'checked' : '' ?>>
                    <label for="send15">Enviar recordatorio <strong>15 días antes</strong> del vencimiento</label>
                </div>

                <div class="checkbox-item">
                    <input type="checkbox" name="send_1_day_before" id="send1" value="1" <?= !empty($config['send_1_day_before']) ? 'checked' : '' ?>>
                    <label for="send1">Enviar recordatorio <strong>1 día antes</strong> del vencimiento (si no pagó)</label>
                </div>
            </div>
        </div>

        <!-- Configuración de Email -->
        <div class="config-card">
            <h3>✉️ Configuración de Email</h3>

            <div class="form-group">
                <label>Email Remitente</label>
                <input type="email" name="email_from" value="<?= htmlspecialchars($config['email_from'] ?? 'noreply@aseguralocr.com') ?>" required>
            </div>

            <div class="form-group">
                <label>Nombre del Remitente</label>
                <input type="text" name="email_from_name" value="<?= htmlspecialchars($config['email_from_name'] ?? 'AseguraloCR') ?>" required>
            </div>

            <div class="form-group">
                <label>Asunto del Email</label>
                <input type="text" name="email_subject" value="<?= htmlspecialchars($config['email_subject'] ?? 'Recordatorio: Vencimiento de su póliza') ?>" required>
                <div class="variables-help">
                    <strong>Variables disponibles:</strong>
                    Puedes usar: <code>{numero_poliza}</code>, <code>{nombre_cliente}</code>, <code>{monto}</code>, <code>{fecha_vencimiento}</code>
                </div>
            </div>
        </div>

        <!-- Plantilla HTML -->
        <div class="config-card">
            <h3>📝 Plantilla del Email (HTML)</h3>
            <p style="color: #64748b; margin-bottom: 12px;">Personaliza el contenido del email</p>

            <div class="form-group">
                <textarea name="email_template" required><?= htmlspecialchars($config['email_template'] ?? '') ?></textarea>

                <div class="variables-help">
                    <strong>Variables disponibles:</strong>
                    <code>{numero_poliza}</code>, <code>{nombre_cliente}</code>, <code>{monto}</code>, <code>{moneda}</code>,
                    <code>{fecha_vencimiento}</code>, <code>{tipo_pago}</code>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <a href="/admin/dashboard.php" style="color: #64748b; text-decoration: none;">← Volver al Dashboard</a>
            <div>
                <button type="button" onclick="sendTestEmail()" class="btn-test">📧 Enviar Email de Prueba</button>
                <button type="submit" class="btn-save">💾 Guardar Configuración</button>
            </div>
        </div>

    </form>
</div>

<script>
function sendTestEmail() {
    if (confirm('¿Enviar un email de prueba a tu dirección registrada?')) {
        fetch('/admin/actions/send-test-reminder.php', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Email de prueba enviado exitosamente');
            } else {
                alert('❌ Error: ' + (data.error || 'No se pudo enviar el email'));
            }
        })
        .catch(err => {
            alert('❌ Error de conexión');
            console.error(err);
        });
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
