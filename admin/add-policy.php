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

    /* File Upload Styles */
    .file-upload-area {
        border: 2px dashed #cbd5e1;
        border-radius: 12px;
        padding: 32px;
        text-align: center;
        background: #f8fafc;
        transition: all 0.3s;
        cursor: pointer;
        position: relative;
    }
    .file-upload-area:hover {
        border-color: #3b82f6;
        background: #eff6ff;
    }
    .file-upload-area.drag-over {
        border-color: #10b981;
        background: #d1fae5;
        transform: scale(1.02);
    }
    .file-upload-placeholder {
        pointer-events: none;
    }
    .file-icon {
        font-size: 48px;
        margin-bottom: 12px;
        opacity: 0.6;
    }
    .file-upload-placeholder h4 {
        margin: 0 0 8px 0;
        font-size: 1.1rem;
        color: #1e293b;
        font-weight: 600;
    }
    .file-upload-placeholder p {
        margin: 0 0 12px 0;
        color: #64748b;
        font-size: 0.95rem;
    }
    .file-formats {
        display: inline-block;
        padding: 6px 16px;
        background: white;
        border-radius: 20px;
        font-size: 0.8rem;
        color: #64748b;
        border: 1px solid #e2e8f0;
    }
    .file-preview {
        display: flex;
        align-items: center;
        gap: 16px;
        background: white;
        padding: 20px;
        border-radius: 10px;
        border: 2px solid #10b981;
    }
    .file-preview-icon {
        font-size: 40px;
        line-height: 1;
    }
    .file-preview-info {
        flex: 1;
        text-align: left;
    }
    .file-preview-name {
        font-weight: 600;
        color: #1e293b;
        font-size: 0.95rem;
        margin-bottom: 4px;
        word-break: break-word;
    }
    .file-preview-size {
        font-size: 0.85rem;
        color: #64748b;
    }
    .file-remove-btn {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        border: none;
        background: #fee2e2;
        color: #dc2626;
        font-size: 16px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.2s;
        flex-shrink: 0;
    }
    .file-remove-btn:hover {
        background: #dc2626;
        color: white;
        transform: scale(1.1);
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
                    <label>Prima Semestral</label>
                    <input type="number" name="prima_semestral" step="0.01" min="0" placeholder="0.00">
                    <p class="form-hint">Se calcula automáticamente</p>
                </div>
                <div class="form-group">
                    <label>Prima Trimestral</label>
                    <input type="number" name="prima_trimestral" step="0.01" min="0" placeholder="0.00">
                    <p class="form-hint">Se calcula automáticamente</p>
                </div>
                <div class="form-group">
                    <label>Prima Mensual</label>
                    <input type="number" name="prima_mensual" step="0.01" min="0" placeholder="0.00">
                    <p class="form-hint">Se calcula automáticamente</p>
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
        <div class="form-card-header orange">📎 Documentos y Archivos</div>
        <div class="form-card-body">
            <div class="form-group">
                <label>Archivo de Póliza (PDF)</label>
                <div class="file-upload-area" id="fileUploadArea">
                    <input type="file" name="archivo_poliza" accept=".pdf,.doc,.docx" id="archivoPoliza" hidden>
                    <div class="file-upload-placeholder" id="uploadPlaceholder">
                        <div class="file-icon">📄</div>
                        <h4>Arrastra el archivo aquí</h4>
                        <p>o haz clic para seleccionar</p>
                        <span class="file-formats">PDF, DOC, DOCX (Máx. 10MB)</span>
                    </div>
                    <div class="file-preview" id="filePreview" style="display: none;">
                        <div class="file-preview-icon">📄</div>
                        <div class="file-preview-info">
                            <div class="file-preview-name" id="fileName"></div>
                            <div class="file-preview-size" id="fileSize"></div>
                        </div>
                        <button type="button" class="file-remove-btn" id="removeFile">✕</button>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Notas Administrativas</label>
                <textarea name="notas_admin" rows="3" placeholder="Observaciones, detalles especiales, etc."></textarea>
            </div>

            <div class="checkbox-item" style="display: inline-flex; margin-bottom: 16px;">
                <input type="checkbox" name="crear_plan_pagos" value="1" id="crearPagos" checked>
                <label for="crearPagos">📅 Crear plan de pagos automáticamente</label>
            </div>

            <!-- Payment Plan Options -->
            <div id="planPagosOptions" style="background: #f8fafc; border-radius: 12px; padding: 20px; margin-top: 12px;">
                <div class="form-row">
                    <div class="form-group">
                        <label>Frecuencia de Pago *</label>
                        <select name="frecuencia_pago" id="frecuenciaPago">
                            <option value="mensual">Mensual (12 pagos/año)</option>
                            <option value="trimestral">Trimestral (4 pagos/año)</option>
                            <option value="semestral">Semestral (2 pagos/año)</option>
                            <option value="anual" selected>Anual (1 pago/año)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Duración del Plan *</label>
                        <select name="anos_plan" id="anosPlan">
                            <option value="1" selected>1 año</option>
                            <option value="2">2 años</option>
                            <option value="3">3 años</option>
                            <option value="4">4 años</option>
                            <option value="5">5 años</option>
                            <option value="6">6 años</option>
                            <option value="7">7 años</option>
                            <option value="8">8 años</option>
                            <option value="9">9 años</option>
                            <option value="10">10 años</option>
                        </select>
                    </div>
                </div>
                <div style="background: #dbeafe; padding: 12px 16px; border-radius: 8px; color: #1e40af;">
                    <span id="resumenTexto">Se crearán <strong>1 pago(s)</strong> de <strong>₡0.00</strong> cada uno</span>
                </div>
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
// File Upload Functionality
const fileUploadArea = document.getElementById('fileUploadArea');
const fileInput = document.getElementById('archivoPoliza');
const uploadPlaceholder = document.getElementById('uploadPlaceholder');
const filePreview = document.getElementById('filePreview');
const fileName = document.getElementById('fileName');
const fileSize = document.getElementById('fileSize');
const removeBtn = document.getElementById('removeFile');

// Click to upload
fileUploadArea?.addEventListener('click', (e) => {
    if (!e.target.classList.contains('file-remove-btn')) {
        fileInput?.click();
    }
});

// Drag and drop
['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    fileUploadArea?.addEventListener(eventName, preventDefaults, false);
});

function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
}

['dragenter', 'dragover'].forEach(eventName => {
    fileUploadArea?.addEventListener(eventName, () => {
        fileUploadArea.classList.add('drag-over');
    }, false);
});

['dragleave', 'drop'].forEach(eventName => {
    fileUploadArea?.addEventListener(eventName', () => {
        fileUploadArea.classList.remove('drag-over');
    }, false);
});

fileUploadArea?.addEventListener('drop', (e) => {
    const dt = e.dataTransfer;
    const files = dt.files;
    fileInput.files = files;
    handleFiles(files);
});

// File selection
fileInput?.addEventListener('change', (e) => {
    handleFiles(e.target.files);
});

function handleFiles(files) {
    if (files.length === 0) return;

    const file = files[0];
    const maxSize = 10 * 1024 * 1024; // 10MB

    if (file.size > maxSize) {
        alert('⚠️ El archivo es demasiado grande. Máximo 10MB');
        fileInput.value = '';
        return;
    }

    // Show preview
    fileName.textContent = file.name;
    fileSize.textContent = formatFileSize(file.size);
    uploadPlaceholder.style.display = 'none';
    filePreview.style.display = 'flex';
}

// Remove file
removeBtn?.addEventListener('click', (e) => {
    e.stopPropagation();
    fileInput.value = '';
    uploadPlaceholder.style.display = 'block';
    filePreview.style.display = 'none';
});

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

// Auto-calculate all premiums from annual
document.querySelector('[name="prima_anual"]')?.addEventListener('input', function(e) {
    const annual = parseFloat(e.target.value) || 0;
    const monthly = document.querySelector('[name="prima_mensual"]');
    const quarterly = document.querySelector('[name="prima_trimestral"]');
    const semiannual = document.querySelector('[name="prima_semestral"]');

    if (monthly && !monthly.dataset.userEdited) {
        monthly.value = (annual / 12).toFixed(2);
    }
    if (quarterly && !quarterly.dataset.userEdited) {
        quarterly.value = (annual / 4).toFixed(2);
    }
    if (semiannual && !semiannual.dataset.userEdited) {
        semiannual.value = (annual / 2).toFixed(2);
    }
    updateResumenPlan();
});

// Mark fields as user-edited if manually changed
['prima_mensual', 'prima_trimestral', 'prima_semestral'].forEach(name => {
    document.querySelector(`[name="${name}"]`)?.addEventListener('input', function() {
        this.dataset.userEdited = 'true';
        updateResumenPlan();
    });
});

// Toggle payment plan options
document.getElementById('crearPagos')?.addEventListener('change', function() {
    document.getElementById('planPagosOptions').style.display = this.checked ? 'block' : 'none';
});

// Update summary when frequency or years change
document.getElementById('frecuenciaPago')?.addEventListener('change', updateResumenPlan);
document.getElementById('anosPlan')?.addEventListener('change', updateResumenPlan);
document.querySelector('[name="moneda"]')?.addEventListener('change', updateResumenPlan);

function updateResumenPlan() {
    const frecuencia = document.getElementById('frecuenciaPago')?.value || 'anual';
    const anos = parseInt(document.getElementById('anosPlan')?.value) || 1;
    const moneda = document.querySelector('[name="moneda"]')?.value || 'colones';
    const simbolo = moneda === 'dolares' ? '$' : '₡';

    let montoPago = 0;
    let pagosAnuales = 1;

    switch(frecuencia) {
        case 'mensual':
            montoPago = parseFloat(document.querySelector('[name="prima_mensual"]')?.value) || 0;
            pagosAnuales = 12;
            break;
        case 'trimestral':
            montoPago = parseFloat(document.querySelector('[name="prima_trimestral"]')?.value) || 0;
            pagosAnuales = 4;
            break;
        case 'semestral':
            montoPago = parseFloat(document.querySelector('[name="prima_semestral"]')?.value) || 0;
            pagosAnuales = 2;
            break;
        case 'anual':
            montoPago = parseFloat(document.querySelector('[name="prima_anual"]')?.value) || 0;
            pagosAnuales = 1;
            break;
    }

    const totalPagos = pagosAnuales * anos;
    const totalMonto = montoPago * totalPagos;

    const resumenTexto = document.getElementById('resumenTexto');
    if (resumenTexto) {
        resumenTexto.innerHTML = `Se crearán <strong>${totalPagos} pago(s)</strong> de <strong>${simbolo}${montoPago.toLocaleString('es-CR', {minimumFractionDigits: 2})}</strong> cada uno. Total: <strong>${simbolo}${totalMonto.toLocaleString('es-CR', {minimumFractionDigits: 2})}</strong>`;
    }
}

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

    updateResumenPlan();
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
