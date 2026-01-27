<?php
// admin/edit-policy.php - Edit existing policy
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$policyId = (int)($_GET['id'] ?? 0);
if (!$policyId) {
    header('Location: /admin/clients.php');
    exit;
}

// Get policy data
$stmt = $pdo->prepare("SELECT * FROM policies WHERE id = ?");
$stmt->execute([$policyId]);
$policy = $stmt->fetch();

if (!$policy) {
    $_SESSION['error_message'] = 'Poliza no encontrada';
    header('Location: /admin/clients.php');
    exit;
}

// Get client data
$clientStmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
$clientStmt->execute([$policy['client_id']]);
$client = $clientStmt->fetch();

// Decode coberturas
$coberturas = [];
if (!empty($policy['coberturas'])) {
    $coberturas = json_decode($policy['coberturas'], true) ?: [];
}

// Handle form submission
$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Prepare coberturas as JSON
        $coberturasJson = isset($_POST['coberturas']) ? json_encode($_POST['coberturas']) : null;

        // Handle file upload
        $archivoPolizaUrl = $policy['archivo_poliza_url']; // Keep existing
        if (!empty($_FILES['archivo_poliza']['name'])) {
            $uploadDir = __DIR__ . '/../storage/policies/';
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

        // Update policy
        $stmt = $pdo->prepare("
            UPDATE policies SET
                numero_poliza = :numero_poliza,
                tipo_seguro = :tipo_seguro,
                aseguradora = :aseguradora,
                coberturas = :coberturas,
                monto_asegurado = :monto_asegurado,
                prima_anual = :prima_anual,
                prima_mensual = :prima_mensual,
                prima_trimestral = :prima_trimestral,
                prima_semestral = :prima_semestral,
                moneda = :moneda,
                fecha_emision = :fecha_emision,
                fecha_inicio_vigencia = :fecha_inicio_vigencia,
                fecha_fin_vigencia = :fecha_fin_vigencia,
                status = :status,
                detalles_bien_asegurado = :detalles_bien_asegurado,
                notas_admin = :notas_admin,
                archivo_poliza_url = :archivo_poliza_url
            WHERE id = :id
        ");

        $stmt->execute([
            ':numero_poliza' => $_POST['numero_poliza'],
            ':tipo_seguro' => $_POST['tipo_seguro'],
            ':aseguradora' => $_POST['aseguradora'] ?? 'INS',
            ':coberturas' => $coberturasJson,
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
            ':id' => $policyId
        ]);

        $message = 'Poliza actualizada exitosamente';

        // Refresh policy data
        $stmt = $pdo->prepare("SELECT * FROM policies WHERE id = ?");
        $stmt->execute([$policyId]);
        $policy = $stmt->fetch();
        $coberturas = !empty($policy['coberturas']) ? json_decode($policy['coberturas'], true) : [];

    } catch (Exception $e) {
        $error = 'Error al actualizar: ' . $e->getMessage();
    }
}

$pageTitle = "Editar Poliza #" . ($policy['numero_poliza'] ?? $policyId);
include __DIR__ . '/includes/header.php';
?>

<style>
    .form-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        margin-bottom: 24px;
        overflow: hidden;
    }
    .form-card-header {
        padding: 16px 24px;
        font-weight: 600;
        font-size: 1rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .form-card-header.blue { background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; }
    .form-card-header.green { background: linear-gradient(135deg, #10b981, #059669); color: white; }
    .form-card-header.purple { background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; }
    .form-card-header.orange { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; }
    .form-card-body {
        padding: 24px;
    }
    .form-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
    }
    .form-group {
        margin-bottom: 20px;
    }
    .form-group label {
        display: block;
        font-weight: 600;
        font-size: 0.85rem;
        color: #374151;
        margin-bottom: 8px;
    }
    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        font-size: 0.95rem;
        transition: all 0.2s;
    }
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
    }
    .form-hint {
        font-size: 0.8rem;
        color: #64748b;
        margin-top: 6px;
    }
    .client-info-box {
        background: linear-gradient(135deg, #dbeafe, #ede9fe);
        padding: 20px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        gap: 16px;
    }
    .client-avatar {
        width: 56px;
        height: 56px;
        background: linear-gradient(135deg, #3b82f6, #8b5cf6);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.4rem;
        font-weight: 700;
    }
    .client-details h3 {
        font-size: 1.1rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 4px;
    }
    .client-details p {
        color: #64748b;
        font-size: 0.9rem;
    }
    .checkbox-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 12px;
    }
    .checkbox-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 14px;
        background: #f8fafc;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s;
    }
    .checkbox-item:hover {
        background: #e2e8f0;
    }
    .checkbox-item input {
        width: 18px;
        height: 18px;
        accent-color: #3b82f6;
    }
    .checkbox-item label {
        margin: 0;
        font-weight: 500;
        font-size: 0.9rem;
        cursor: pointer;
    }
    .form-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 32px;
        padding-top: 24px;
        border-top: 2px solid #e2e8f0;
    }
    .btn-cancel {
        background: #f1f5f9;
        color: #475569;
        padding: 14px 28px;
        border-radius: 10px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.2s;
    }
    .btn-cancel:hover {
        background: #e2e8f0;
    }
    .btn-submit {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        padding: 14px 32px;
        border: none;
        border-radius: 10px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(16,185,129,0.4);
        transition: all 0.2s;
    }
    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(16,185,129,0.5);
    }
    .current-file {
        background: #f0fdf4;
        padding: 10px 14px;
        border-radius: 8px;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
</style>

<!-- Page Header -->
<div class="page-header">
    <div>
        <a href="/admin/client-detail.php?id=<?= $policy['client_id'] ?>" style="color: #64748b; text-decoration: none; font-size: 0.9rem;">← Volver al Cliente</a>
        <h1 style="margin-top: 8px;">✏️ Editar Poliza #<?= htmlspecialchars($policy['numero_poliza'] ?? '') ?></h1>
        <p>Modifica los datos de la poliza</p>
    </div>
</div>

<?php if ($message): ?>
<div style="background: #d1fae5; color: #047857; padding: 12px 20px; border-radius: 10px; margin-bottom: 20px;">
    <?= htmlspecialchars($message) ?>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div style="background: #fee2e2; color: #dc2626; padding: 12px 20px; border-radius: 10px; margin-bottom: 20px;">
    <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">

    <!-- Client Section -->
    <div class="form-card">
        <div class="form-card-header blue">Cliente</div>
        <div class="form-card-body">
            <div class="client-info-box">
                <div class="client-avatar">
                    <?= strtoupper(substr($client['nombre_completo'] ?? 'C', 0, 1)) ?>
                </div>
                <div class="client-details">
                    <h3><?= htmlspecialchars($client['nombre_completo'] ?? 'Sin nombre') ?></h3>
                    <p><?= htmlspecialchars($client['email'] ?? '') ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Policy Information -->
    <div class="form-card">
        <div class="form-card-header green">Informacion de la Poliza</div>
        <div class="form-card-body">
            <div class="form-row">
                <div class="form-group">
                    <label>Numero de Poliza *</label>
                    <input type="text" name="numero_poliza" required value="<?= htmlspecialchars($policy['numero_poliza'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Aseguradora</label>
                    <input type="text" name="aseguradora" value="<?= htmlspecialchars($policy['aseguradora'] ?? 'INS') ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Tipo de Seguro *</label>
                    <select name="tipo_seguro" required>
                        <option value="">-- Seleccione --</option>
                        <option value="hogar" <?= ($policy['tipo_seguro'] ?? '') === 'hogar' ? 'selected' : '' ?>>Hogar</option>
                        <option value="auto" <?= ($policy['tipo_seguro'] ?? '') === 'auto' ? 'selected' : '' ?>>Auto</option>
                        <option value="rt" <?= ($policy['tipo_seguro'] ?? '') === 'rt' ? 'selected' : '' ?>>Riesgos del Trabajo</option>
                        <option value="vida" <?= ($policy['tipo_seguro'] ?? '') === 'vida' ? 'selected' : '' ?>>Vida</option>
                        <option value="salud" <?= ($policy['tipo_seguro'] ?? '') === 'salud' ? 'selected' : '' ?>>Salud</option>
                        <option value="otros" <?= ($policy['tipo_seguro'] ?? '') === 'otros' ? 'selected' : '' ?>>Otros</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Estado *</label>
                    <select name="status" required>
                        <option value="activa" <?= ($policy['status'] ?? '') === 'activa' ? 'selected' : '' ?>>Activa</option>
                        <option value="vigente" <?= ($policy['status'] ?? '') === 'vigente' ? 'selected' : '' ?>>Vigente</option>
                        <option value="por_vencer" <?= ($policy['status'] ?? '') === 'por_vencer' ? 'selected' : '' ?>>Por Vencer</option>
                        <option value="cotizacion" <?= ($policy['status'] ?? '') === 'cotizacion' ? 'selected' : '' ?>>Cotizacion</option>
                        <option value="vencida" <?= ($policy['status'] ?? '') === 'vencida' ? 'selected' : '' ?>>Vencida</option>
                        <option value="cancelada" <?= ($policy['status'] ?? '') === 'cancelada' ? 'selected' : '' ?>>Cancelada</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Descripcion del Bien Asegurado</label>
                <textarea name="detalles_bien_asegurado" rows="2"><?= htmlspecialchars($policy['detalles_bien_asegurado'] ?? '') ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Fecha de Emision *</label>
                    <input type="date" name="fecha_emision" required value="<?= htmlspecialchars($policy['fecha_emision'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Inicio de Vigencia *</label>
                    <input type="date" name="fecha_inicio_vigencia" required value="<?= htmlspecialchars($policy['fecha_inicio_vigencia'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Fin de Vigencia *</label>
                    <input type="date" name="fecha_fin_vigencia" required value="<?= htmlspecialchars($policy['fecha_fin_vigencia'] ?? '') ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- Coverage & Amounts -->
    <div class="form-card">
        <div class="form-card-header purple">Coberturas y Montos</div>
        <div class="form-card-body">
            <div class="form-row">
                <div class="form-group">
                    <label>Moneda *</label>
                    <select name="moneda" required>
                        <option value="colones" <?= ($policy['moneda'] ?? '') === 'colones' ? 'selected' : '' ?>>Colones</option>
                        <option value="dolares" <?= ($policy['moneda'] ?? '') === 'dolares' ? 'selected' : '' ?>>Dolares</option>
                        <option value="CRC" <?= ($policy['moneda'] ?? '') === 'CRC' ? 'selected' : '' ?>>CRC</option>
                        <option value="USD" <?= ($policy['moneda'] ?? '') === 'USD' ? 'selected' : '' ?>>USD</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Monto Asegurado</label>
                    <input type="number" name="monto_asegurado" step="0.01" min="0" value="<?= htmlspecialchars($policy['monto_asegurado'] ?? '') ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Prima Anual *</label>
                    <input type="number" name="prima_anual" step="0.01" min="0" required value="<?= htmlspecialchars($policy['prima_anual'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Prima Semestral</label>
                    <input type="number" name="prima_semestral" step="0.01" min="0" value="<?= htmlspecialchars($policy['prima_semestral'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Prima Trimestral</label>
                    <input type="number" name="prima_trimestral" step="0.01" min="0" value="<?= htmlspecialchars($policy['prima_trimestral'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Prima Mensual</label>
                    <input type="number" name="prima_mensual" step="0.01" min="0" value="<?= htmlspecialchars($policy['prima_mensual'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Coberturas Incluidas</label>
                <div class="checkbox-grid">
                    <div class="checkbox-item">
                        <input type="checkbox" name="coberturas[]" value="incendio" id="cob1" <?= in_array('incendio', $coberturas) ? 'checked' : '' ?>>
                        <label for="cob1">Incendio</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" name="coberturas[]" value="terremoto" id="cob2" <?= in_array('terremoto', $coberturas) ? 'checked' : '' ?>>
                        <label for="cob2">Terremoto</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" name="coberturas[]" value="robo" id="cob3" <?= in_array('robo', $coberturas) ? 'checked' : '' ?>>
                        <label for="cob3">Robo</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" name="coberturas[]" value="inundacion" id="cob4" <?= in_array('inundacion', $coberturas) ? 'checked' : '' ?>>
                        <label for="cob4">Inundacion</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" name="coberturas[]" value="rc" id="cob5" <?= in_array('rc', $coberturas) ? 'checked' : '' ?>>
                        <label for="cob5">Resp. Civil</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" name="coberturas[]" value="contenido" id="cob6" <?= in_array('contenido', $coberturas) ? 'checked' : '' ?>>
                        <label for="cob6">Contenido</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" name="coberturas[]" value="vidrios" id="cob7" <?= in_array('vidrios', $coberturas) ? 'checked' : '' ?>>
                        <label for="cob7">Vidrios</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" name="coberturas[]" value="otros" id="cob8" <?= in_array('otros', $coberturas) ? 'checked' : '' ?>>
                        <label for="cob8">Otros</label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Information -->
    <div class="form-card">
        <div class="form-card-header orange">Informacion Adicional</div>
        <div class="form-card-body">
            <?php if (!empty($policy['archivo_poliza_url'])): ?>
            <div class="current-file">
                <span>Archivo actual:</span>
                <a href="<?= htmlspecialchars($policy['archivo_poliza_url']) ?>" target="_blank" style="color: #059669; font-weight: 500;">
                    Ver PDF
                </a>
            </div>
            <?php endif; ?>

            <div class="form-group">
                <label>Archivo de Poliza (PDF)</label>
                <input type="file" name="archivo_poliza" accept=".pdf">
                <p class="form-hint">Subir nuevo PDF para reemplazar el actual (opcional)</p>
            </div>

            <div class="form-group">
                <label>Notas Administrativas</label>
                <textarea name="notas_admin" rows="3"><?= htmlspecialchars($policy['notas_admin'] ?? '') ?></textarea>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="form-actions">
        <a href="/admin/client-detail.php?id=<?= $policy['client_id'] ?>" class="btn-cancel">
            Cancelar
        </a>
        <button type="submit" class="btn-submit">
            Guardar Cambios
        </button>
    </div>
</form>

<?php include __DIR__ . '/includes/footer.php'; ?>
