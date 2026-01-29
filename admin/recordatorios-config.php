<?php
// admin/recordatorios-config.php - Configure payment reminders
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

// Check if table exists first and create if needed
try {
    $stmt = $pdo->query("SELECT * FROM payment_reminders_config WHERE id = 1");
    $config = $stmt->fetch() ?: [];
} catch (PDOException $e) {
    // Table doesn't exist, create it now
    try {
        // Create config table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS payment_reminders_config (
                id INT PRIMARY KEY AUTO_INCREMENT,
                send_30_days_before BOOLEAN DEFAULT TRUE,
                send_15_days_before BOOLEAN DEFAULT TRUE,
                send_1_day_before BOOLEAN DEFAULT TRUE,
                email_from VARCHAR(255) DEFAULT 'info@aseguralocr.com',
                email_from_name VARCHAR(255) DEFAULT 'AseguraloCR',
                email_subject VARCHAR(255) DEFAULT 'Recordatorio: Vencimiento de su póliza #{numero_poliza}',
                email_template TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");

        // Create sent reminders table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS payment_reminders_sent (
                id INT PRIMARY KEY AUTO_INCREMENT,
                payment_id INT NOT NULL,
                reminder_type ENUM('30_days', '15_days', '1_day') NOT NULL,
                sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                email_to VARCHAR(255) NOT NULL,
                status ENUM('sent', 'failed') DEFAULT 'sent',
                UNIQUE KEY unique_reminder (payment_id, reminder_type)
            )
        ");

        // Insert default config
        $defaultTemplate = '<!DOCTYPE html>
<html><body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
<h2>Recordatorio de Pago</h2>
<p>Estimado/a {nombre_cliente},</p>
<p>Le recordamos que tiene un pago pendiente:</p>
<ul>
  <li>Póliza: {numero_poliza}</li>
  <li>Tipo: {tipo_poliza}</li>
  <li>Monto: {moneda} {monto}</li>
  <li>Vencimiento: {fecha_vencimiento}</li>
</ul>
<p>Realice el pago antes de la fecha indicada.</p>
</body></html>';

        $pdo->exec("INSERT INTO payment_reminders_config (email_template) VALUES ('" . addslashes($defaultTemplate) . "')");

        $_SESSION['success_message'] = 'Tablas creadas exitosamente. Configura tus recordatorios a continuación.';
        header('Location: /admin/recordatorios-config.php');
        exit;
    } catch (Exception $createError) {
        die('Error al crear tablas: ' . $createError->getMessage());
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $send30 = isset($_POST['send_30_days_before']) ? 1 : 0;
    $send15 = isset($_POST['send_15_days_before']) ? 1 : 0;
    $send1 = isset($_POST['send_1_day_before']) ? 1 : 0;
    $emailFrom = trim($_POST['email_from'] ?? 'info@aseguralocr.com');
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

// Fetch all clients for selection
$clientsStmt = $pdo->query("SELECT id, nombre_completo, email FROM clients ORDER BY nombre_completo");
$allClients = $clientsStmt->fetchAll();

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
    .btn-preview {
        background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        color: white;
        padding: 10px 24px;
        border: none;
        border-radius: 8px;
        font-size: 0.9rem;
        font-weight: 600;
        cursor: pointer;
        margin-left: 12px;
    }
    .test-email-section {
        background: #f8fafc;
        padding: 20px;
        border-radius: 8px;
        margin-top: 20px;
    }
    .test-email-section h4 {
        margin: 0 0 15px 0;
        color: #1e293b;
        font-size: 1rem;
    }
    .client-list {
        max-height: 400px;
        overflow-y: auto;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        padding: 15px;
        background: white;
    }
    .client-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px;
        margin-bottom: 8px;
        background: #f8fafc;
        border-radius: 6px;
        transition: background 0.2s;
    }
    .client-item:hover {
        background: #e2e8f0;
    }
    .client-item input[type="checkbox"] {
        width: 18px;
        height: 18px;
        accent-color: #3b82f6;
    }
    .client-info {
        flex: 1;
    }
    .client-name {
        font-weight: 600;
        color: #1e293b;
    }
    .client-email {
        font-size: 0.85rem;
        color: #64748b;
    }
    .select-all-btn {
        padding: 8px 16px;
        background: #3b82f6;
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 0.85rem;
        cursor: pointer;
        margin-bottom: 10px;
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
                <input type="email" name="email_from" value="<?= htmlspecialchars($config['email_from'] ?? 'info@aseguralocr.com') ?>" required>
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
                    <code>{numero_poliza}</code>, <code>{tipo_poliza}</code>, <code>{nombre_cliente}</code>, <code>{monto}</code>, <code>{moneda}</code>,
                    <code>{fecha_vencimiento}</code>, <code>{tipo_pago}</code>
                </div>
            </div>
        </div>

        <!-- Test Email Section -->
        <div class="config-card">
            <h3>🧪 Probar Email</h3>
            <p style="color: #64748b; margin-bottom: 20px;">Envía un email de prueba o visualiza cómo se verá</p>

            <div class="test-email-section">
                <h4>Email de Destino para Prueba</h4>
                <div class="form-group">
                    <input type="email" id="test_email_address" placeholder="correo@ejemplo.com" style="margin-bottom: 15px;">
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="button" onclick="previewEmail()" class="btn-preview">👁️ Vista Preliminar</button>
                    <button type="button" onclick="sendTestEmail()" class="btn-test">📧 Enviar Email de Prueba</button>
                </div>
            </div>
        </div>

        <!-- Client Selection -->
        <div class="config-card">
            <h3>👥 Seleccionar Clientes</h3>
            <p style="color: #64748b; margin-bottom: 20px;">Selecciona qué clientes recibirán los recordatorios automáticos</p>

            <button type="button" class="select-all-btn" onclick="toggleAllClients()">☑️ Seleccionar Todos / Ninguno</button>

            <div class="client-list">
                <?php if (empty($allClients)): ?>
                    <p style="color: #64748b; text-align: center; padding: 20px;">No hay clientes registrados</p>
                <?php else: ?>
                    <?php foreach ($allClients as $client): ?>
                        <div class="client-item">
                            <input type="checkbox" name="selected_clients[]" value="<?= $client['id'] ?>" id="client_<?= $client['id'] ?>" checked>
                            <label for="client_<?= $client['id'] ?>" class="client-info">
                                <div class="client-name"><?= htmlspecialchars($client['nombre_completo']) ?></div>
                                <div class="client-email"><?= htmlspecialchars($client['email'] ?? 'Sin email') ?></div>
                            </label>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
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
function previewEmail() {
    // Open preview in new window
    window.open('/admin/actions/preview-reminder.php', 'preview', 'width=900,height=700,scrollbars=yes');
}

function sendTestEmail() {
    const emailInput = document.getElementById('test_email_address');
    const emailTo = emailInput.value.trim();

    if (!emailTo) {
        alert('⚠️ Por favor ingresa un email de destino para la prueba');
        emailInput.focus();
        return;
    }

    // Basic email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(emailTo)) {
        alert('⚠️ Por favor ingresa un email válido');
        emailInput.focus();
        return;
    }

    if (confirm('¿Enviar email de prueba a ' + emailTo + '?')) {
        const formData = new FormData();
        formData.append('email_to', emailTo);

        fetch('/admin/actions/send-test-reminder.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ ' + data.message);
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

function toggleAllClients() {
    const checkboxes = document.querySelectorAll('input[name="selected_clients[]"]');
    const allChecked = Array.from(checkboxes).every(cb => cb.checked);

    checkboxes.forEach(cb => {
        cb.checked = !allChecked;
    });
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
