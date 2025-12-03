<?php
// admin/payment-settings.php - Configuración de métodos de pago
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

// Crear tabla si no existe
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS agent_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT,
            setting_label VARCHAR(255),
            setting_group VARCHAR(50) DEFAULT 'general',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
} catch (Exception $e) {
    // Tabla ya existe
}

// Configuraciones por defecto
$defaultSettings = [
    ['sinpe_numero', '8888-8888', 'Número SINPE Móvil', 'sinpe'],
    ['sinpe_nombre', 'AseguraloCR', 'Nombre del titular SINPE', 'sinpe'],
    ['sinpe_cedula', '', 'Cédula del titular', 'sinpe'],
    ['banco_nombre', 'Banco Nacional', 'Nombre del Banco', 'banco'],
    ['banco_cuenta_colones', '', 'Cuenta en Colones (IBAN)', 'banco'],
    ['banco_cuenta_dolares', '', 'Cuenta en Dólares (IBAN)', 'banco'],
    ['banco_titular', '', 'Nombre del titular de cuenta', 'banco'],
    ['whatsapp_agente', '+506 8888-8888', 'WhatsApp de contacto', 'contacto'],
    ['telefono_agente', '+506 8888-8888', 'Teléfono de contacto', 'contacto'],
    ['email_agente', 'info@aseguralocr.com', 'Email de contacto', 'contacto'],
    ['ins_link_pago', '', 'Link de pago INS (opcional)', 'tarjeta'],
];

// Insertar valores por defecto si no existen
foreach ($defaultSettings as $setting) {
    try {
        $stmt = $pdo->prepare("INSERT IGNORE INTO agent_settings (setting_key, setting_value, setting_label, setting_group) VALUES (?, ?, ?, ?)");
        $stmt->execute($setting);
    } catch (Exception $e) {
        // Ignorar errores
    }
}

// Manejar guardado
$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        foreach ($_POST['settings'] as $key => $value) {
            $stmt = $pdo->prepare("UPDATE agent_settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([trim($value), $key]);
        }
        $message = 'Configuración guardada correctamente';
    } catch (Exception $e) {
        $error = 'Error al guardar: ' . $e->getMessage();
    }
}

// Obtener configuraciones actuales
$settings = [];
try {
    $stmt = $pdo->query("SELECT * FROM agent_settings ORDER BY setting_group, id");
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_group']][] = $row;
    }
} catch (Exception $e) {
    // Si la tabla no existe, continuar con settings vacíos
}

// Helper para obtener valor de setting de forma segura
function getSetting($settings, $group, $index, $default = '') {
    return isset($settings[$group][$index]['setting_value'])
        ? $settings[$group][$index]['setting_value']
        : $default;
}

$pageTitle = "Configuración de Pagos";
include __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <a href="/admin/" style="color: #64748b; text-decoration: none; font-size: 0.9rem;">← Volver al Dashboard</a>
        <h1 style="margin-top: 8px;">💳 Configuración de Métodos de Pago</h1>
        <p>Configura los datos de SINPE, cuentas bancarias y contacto para tus clientes</p>
    </div>
</div>

<?php if ($message): ?>
<div style="background: #d1fae5; color: #047857; padding: 12px 20px; border-radius: 10px; margin-bottom: 20px;">
    ✅ <?= htmlspecialchars($message) ?>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div style="background: #fee2e2; color: #dc2626; padding: 12px 20px; border-radius: 10px; margin-bottom: 20px;">
    ❌ <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<form method="POST">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 24px;">

        <!-- SINPE Móvil -->
        <div class="card">
            <div class="card-header" style="background: #d1fae5;">
                <h2 style="color: #047857;">📱 SINPE Móvil</h2>
            </div>
            <div style="padding: 24px;">
                <?php if (!empty($settings['sinpe'])): ?>
                    <?php foreach ($settings['sinpe'] as $setting): ?>
                    <div class="form-group">
                        <label><?= htmlspecialchars($setting['setting_label']) ?></label>
                        <input type="text" name="settings[<?= $setting['setting_key'] ?>]"
                               value="<?= htmlspecialchars($setting['setting_value']) ?>"
                               placeholder="<?= $setting['setting_key'] === 'sinpe_numero' ? 'Ej: 8888-8888' : '' ?>">
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Cuenta Bancaria -->
        <div class="card">
            <div class="card-header" style="background: #dbeafe;">
                <h2 style="color: #1d4ed8;">🏦 Cuenta Bancaria</h2>
            </div>
            <div style="padding: 24px;">
                <?php if (!empty($settings['banco'])): ?>
                    <?php foreach ($settings['banco'] as $setting): ?>
                    <div class="form-group">
                        <label><?= htmlspecialchars($setting['setting_label']) ?></label>
                        <input type="text" name="settings[<?= $setting['setting_key'] ?>]"
                               value="<?= htmlspecialchars($setting['setting_value']) ?>"
                               placeholder="<?= strpos($setting['setting_key'], 'cuenta') !== false ? 'Ej: CR00000000000000000000' : '' ?>">
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Contacto -->
        <div class="card">
            <div class="card-header" style="background: #fef3c7;">
                <h2 style="color: #92400e;">📞 Datos de Contacto</h2>
            </div>
            <div style="padding: 24px;">
                <?php if (!empty($settings['contacto'])): ?>
                    <?php foreach ($settings['contacto'] as $setting): ?>
                    <div class="form-group">
                        <label><?= htmlspecialchars($setting['setting_label']) ?></label>
                        <input type="text" name="settings[<?= $setting['setting_key'] ?>]"
                               value="<?= htmlspecialchars($setting['setting_value']) ?>">
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Pago con Tarjeta -->
        <div class="card">
            <div class="card-header" style="background: #ede9fe;">
                <h2 style="color: #6d28d9;">💳 Pago con Tarjeta</h2>
            </div>
            <div style="padding: 24px;">
                <?php if (!empty($settings['tarjeta'])): ?>
                    <?php foreach ($settings['tarjeta'] as $setting): ?>
                    <div class="form-group">
                        <label><?= htmlspecialchars($setting['setting_label']) ?></label>
                        <input type="url" name="settings[<?= $setting['setting_key'] ?>]"
                               value="<?= htmlspecialchars($setting['setting_value']) ?>"
                               placeholder="https://...">
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <p style="font-size: 0.85rem; color: #64748b; margin-top: 12px;">
                    💡 El link de pago INS permite a los clientes pagar directamente con tarjeta a través de la plataforma del INS.
                </p>
            </div>
        </div>
    </div>

    <div style="margin-top: 24px; text-align: center;">
        <button type="submit" class="btn btn-primary" style="padding: 14px 40px; font-size: 1rem;">
            💾 Guardar Configuración
        </button>
    </div>
</form>

<div class="card" style="margin-top: 32px;">
    <div class="card-header">
        <h2>👁️ Vista Previa</h2>
    </div>
    <div style="padding: 24px; background: #f8fafc;">
        <p style="margin-bottom: 16px; color: #64748b;">Así verán los clientes la información de pago:</p>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;">
            <div style="background: #d1fae5; padding: 16px; border-radius: 12px; border: 2px solid #86efac;">
                <h4 style="color: #047857; margin-bottom: 8px;">📱 SINPE Móvil</h4>
                <p style="font-size: 1.5rem; font-weight: bold; color: #047857;">
                    <?= htmlspecialchars(getSetting($settings, 'sinpe', 0, '8888-8888')) ?>
                </p>
                <p style="font-size: 0.9rem; color: #065f46;">
                    A nombre de: <?= htmlspecialchars(getSetting($settings, 'sinpe', 1, 'AseguraloCR')) ?>
                </p>
            </div>

            <div style="background: #dbeafe; padding: 16px; border-radius: 12px; border: 2px solid #93c5fd;">
                <h4 style="color: #1d4ed8; margin-bottom: 8px;">🏦 Transferencia Bancaria</h4>
                <p style="font-size: 0.9rem; color: #1e40af;">
                    <strong><?= htmlspecialchars(getSetting($settings, 'banco', 0, 'Banco Nacional')) ?></strong><br>
                    <?php $colones = getSetting($settings, 'banco', 1); if ($colones): ?>
                    Colones: <?= htmlspecialchars($colones) ?><br>
                    <?php endif; ?>
                    <?php $dolares = getSetting($settings, 'banco', 2); if ($dolares): ?>
                    Dólares: <?= htmlspecialchars($dolares) ?>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
