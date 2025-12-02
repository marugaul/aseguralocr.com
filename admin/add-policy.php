<?php
// admin/add-policy.php - Add policy issuance manually
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

// Get client if specified
$client = null;
if (!empty($_GET['client_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
    $stmt->execute([$_GET['client_id']]);
    $client = $stmt->fetch();
}

// Get all clients for dropdown
$clients = $pdo->query("SELECT id, nombre_completo, email FROM clients ORDER BY nombre_completo")->fetchAll();

$pageTitle = "Registrar Póliza";
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
</style>

<!-- Page Header -->
<div class="page-header">
    <div>
        <a href="<?= $client ? '/admin/client-detail.php?id='.$client['id'] : '/admin/clients.php' ?>" style="color: #64748b; text-decoration: none; font-size: 0.9rem;">← Volver</a>
        <h1 style="margin-top: 8px;">📋 Registrar Póliza</h1>
        <p>Ingresa los datos de la póliza emitida por la aseguradora</p>
    </div>
</div>

<form action="/admin/actions/save-policy.php" method="POST" enctype="multipart/form-data">

    <!-- Client Section -->
    <div class="form-card">
        <div class="form-card-header blue">👤 Cliente</div>
        <div class="form-card-body">
            <?php if ($client): ?>
                <input type="hidden" name="client_id" value="<?= $client['id'] ?>">
                <div class="client-info-box">
                    <div class="client-avatar">
                        <?= strtoupper(substr($client['nombre_completo'] ?? 'C', 0, 1)) ?>
                    </div>
                    <div class="client-details">
                        <h3><?= htmlspecialchars($client['nombre_completo'] ?? 'Sin nombre') ?></h3>
                        <p><?= htmlspecialchars($client['email'] ?? '') ?></p>
                    </div>
                </div>
            <?php else: ?>
                <div class="form-group">
                    <label>Seleccionar Cliente *</label>
                    <select name="client_id" required>
                        <option value="">-- Seleccione un cliente --</option>
                        <?php foreach ($clients as $c): ?>
                            <option value="<?= $c['id'] ?>">
                                <?= htmlspecialchars($c['nombre_completo'] ?? 'Sin nombre') ?> (<?= htmlspecialchars($c['email'] ?? '') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="form-hint">¿No existe? <a href="/admin/clients.php">Crear nuevo cliente</a></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Policy Information -->
    <div class="form-card">
        <div class="form-card-header green">📄 Información de la Póliza</div>
        <div class="form-card-body">
            <div class="form-row">
                <div class="form-group">
                    <label>Número de Póliza *</label>
                    <input type="text" name="numero_poliza" required placeholder="Ej: POL-2024-001234">
                    <p class="form-hint">Número oficial de la aseguradora</p>
                </div>
                <div class="form-group">
                    <label>Aseguradora</label>
                    <input type="text" name="aseguradora" value="INS">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Tipo de Seguro *</label>
                    <select name="tipo_seguro" required>
                        <option value="">-- Seleccione --</option>
                        <option value="hogar">🏠 Hogar</option>
                        <option value="auto">🚗 Auto</option>
                        <option value="rt">👷 Riesgos del Trabajo</option>
                        <option value="vida">❤️ Vida</option>
                        <option value="salud">🏥 Salud</option>
                        <option value="otros">📦 Otros</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Estado *</label>
                    <select name="status" required>
                        <option value="vigente" selected>✅ Vigente</option>
                        <option value="por_vencer">⚠️ Por Vencer</option>
                        <option value="cotizacion">📝 Cotización</option>
                        <option value="vencida">❌ Vencida</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Descripción del Bien Asegurado</label>
                <textarea name="detalles_bien_asegurado" rows="2" placeholder="Ej: Casa de habitación de 150m², ubicada en San José"></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Fecha de Emisión *</label>
                    <input type="date" name="fecha_emision" required>
                </div>
                <div class="form-group">
                    <label>Inicio de Vigencia *</label>
                    <input type="date" name="fecha_inicio_vigencia" required>
                </div>
                <div class="form-group">
                    <label>Fin de Vigencia *</label>
                    <input type="date" name="fecha_fin_vigencia" required>
                </div>
            </div>
        </div>
    </div>

    <!-- Coverage & Amounts -->
    <div class="form-card">
        <div class="form-card-header purple">💰 Coberturas y Montos</div>
        <div class="form-card-body">
            <div class="form-row">
                <div class="form-group">
                    <label>Moneda *</label>
                    <select name="moneda" required>
                        <option value="colones" selected>₡ Colones</option>
                        <option value="dolares">$ Dólares</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Monto Asegurado</label>
                    <input type="number" name="monto_asegurado" step="0.01" min="0" placeholder="0.00">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Prima Anual *</label>
                    <input type="number" name="prima_anual" step="0.01" min="0" required placeholder="0.00">
                </div>
                <div class="form-group">
                    <label>Prima Mensual</label>
                    <input type="number" name="prima_mensual" step="0.01" min="0" placeholder="0.00">
                    <p class="form-hint">Se calcula automáticamente si deja vacío</p>
                </div>
            </div>

            <div class="form-group">
                <label>Coberturas Incluidas</label>
                <div class="checkbox-grid">
                    <div class="checkbox-item">
                        <input type="checkbox" name="coberturas[]" value="incendio" id="cob1">
                        <label for="cob1">🔥 Incendio</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" name="coberturas[]" value="terremoto" id="cob2">
                        <label for="cob2">🌋 Terremoto</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" name="coberturas[]" value="robo" id="cob3">
                        <label for="cob3">🔐 Robo</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" name="coberturas[]" value="inundacion" id="cob4">
                        <label for="cob4">🌊 Inundación</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" name="coberturas[]" value="rc" id="cob5">
                        <label for="cob5">⚖️ Resp. Civil</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" name="coberturas[]" value="contenido" id="cob6">
                        <label for="cob6">🪑 Contenido</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" name="coberturas[]" value="vidrios" id="cob7">
                        <label for="cob7">🪟 Vidrios</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" name="coberturas[]" value="otros" id="cob8">
                        <label for="cob8">📦 Otros</label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Information -->
    <div class="form-card">
        <div class="form-card-header orange">📎 Información Adicional</div>
        <div class="form-card-body">
            <div class="form-group">
                <label>Archivo de Póliza (PDF)</label>
                <input type="file" name="archivo_poliza" accept=".pdf">
                <p class="form-hint">Sube el PDF de la póliza emitida (opcional)</p>
            </div>

            <div class="form-group">
                <label>Notas Administrativas</label>
                <textarea name="notas_admin" rows="3" placeholder="Observaciones, detalles especiales, etc."></textarea>
            </div>

            <div class="checkbox-item" style="display: inline-flex;">
                <input type="checkbox" name="crear_plan_pagos" value="1" id="crearPagos" checked>
                <label for="crearPagos">📅 Crear plan de pagos automáticamente</label>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="form-actions">
        <a href="<?= $client ? '/admin/client-detail.php?id='.$client['id'] : '/admin/clients.php' ?>" class="btn-cancel">
            ← Cancelar
        </a>
        <button type="submit" class="btn-submit">
            💾 Registrar Póliza
        </button>
    </div>
</form>

<script>
// Auto-calculate monthly premium from annual
document.querySelector('[name="prima_anual"]')?.addEventListener('input', function(e) {
    const annual = parseFloat(e.target.value) || 0;
    const monthly = document.querySelector('[name="prima_mensual"]');
    if (monthly && !monthly.value) {
        monthly.value = (annual / 12).toFixed(2);
    }
});

// Set default dates
document.addEventListener('DOMContentLoaded', function() {
    const today = new Date().toISOString().split('T')[0];
    const oneYearLater = new Date();
    oneYearLater.setFullYear(oneYearLater.getFullYear() + 1);
    const endDate = oneYearLater.toISOString().split('T')[0];

    const emisionInput = document.querySelector('[name="fecha_emision"]');
    const inicioInput = document.querySelector('[name="fecha_inicio_vigencia"]');
    const finInput = document.querySelector('[name="fecha_fin_vigencia"]');

    if (emisionInput && !emisionInput.value) emisionInput.value = today;
    if (inicioInput && !inicioInput.value) inicioInput.value = today;
    if (finInput && !finInput.value) finInput.value = endDate;
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
