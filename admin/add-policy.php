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
$clients = $pdo->query("SELECT id, nombre_completo, email FROM clients WHERE status = 'active' ORDER BY nombre_completo")->fetchAll();

$pageTitle = "Registrar Emisión de Póliza";
include __DIR__ . '/includes/header.php';
?>

<div class="container-fluid px-4 py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Header -->
            <div class="d-flex align-items-center mb-4">
                <a href="/admin/clients.php" class="btn btn-outline-secondary me-3">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h1 class="h3 mb-0"><i class="fas fa-file-medical me-2"></i>Registrar Emisión de Póliza</h1>
                    <p class="text-muted mb-0">Ingresa los datos de la póliza emitida por la aseguradora</p>
                </div>
            </div>

            <form action="/admin/actions/save-policy.php" method="POST" enctype="multipart/form-data">
                <!-- Client Selection -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-user me-2"></i>Cliente</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($client): ?>
                            <input type="hidden" name="client_id" value="<?= $client['id'] ?>">
                            <div class="alert alert-info mb-0">
                                <strong><?= htmlspecialchars($client['nombre_completo']) ?></strong><br>
                                <small><?= htmlspecialchars($client['email']) ?></small>
                            </div>
                        <?php else: ?>
                            <div class="mb-3">
                                <label class="form-label">Seleccionar Cliente *</label>
                                <select name="client_id" class="form-select" required>
                                    <option value="">-- Seleccione un cliente --</option>
                                    <?php foreach ($clients as $c): ?>
                                        <option value="<?= $c['id'] ?>">
                                            <?= htmlspecialchars($c['nombre_completo']) ?> (<?= htmlspecialchars($c['email']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">
                                    <a href="/admin/clients.php">Crear nuevo cliente</a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Policy Information -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Información de la Póliza</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Número de Póliza *</label>
                                <input type="text" name="numero_poliza" class="form-control" required
                                       placeholder="Ej: POL-2024-001234">
                                <div class="form-text">Número oficial de la póliza emitida por la aseguradora</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Aseguradora</label>
                                <input type="text" name="aseguradora" class="form-control" value="INS">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tipo de Seguro *</label>
                                <select name="tipo_seguro" class="form-select" required>
                                    <option value="">-- Seleccione --</option>
                                    <option value="hogar">Hogar</option>
                                    <option value="auto">Auto</option>
                                    <option value="vida">Vida</option>
                                    <option value="salud">Salud</option>
                                    <option value="otros">Otros</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Estado *</label>
                                <select name="status" class="form-select" required>
                                    <option value="vigente" selected>Vigente</option>
                                    <option value="por_vencer">Por Vencer</option>
                                    <option value="cotizacion">Cotización</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Descripción del Bien Asegurado</label>
                            <textarea name="detalles_bien_asegurado" class="form-control" rows="2"
                                      placeholder="Ej: Casa de habitación de 150m², ubicada en San José"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Fecha de Emisión *</label>
                                <input type="date" name="fecha_emision" class="form-control" required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Inicio de Vigencia *</label>
                                <input type="date" name="fecha_inicio_vigencia" class="form-control" required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Fin de Vigencia *</label>
                                <input type="date" name="fecha_fin_vigencia" class="form-control" required>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Coverage & Amounts -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-dollar-sign me-2"></i>Coberturas y Montos</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Moneda *</label>
                                <select name="moneda" class="form-select" required>
                                    <option value="colones" selected>Colones (₡)</option>
                                    <option value="dolares">Dólares ($)</option>
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Monto Asegurado</label>
                                <input type="number" name="monto_asegurado" class="form-control" step="0.01" min="0"
                                       placeholder="0.00">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Prima Anual *</label>
                                <input type="number" name="prima_anual" class="form-control" step="0.01" min="0" required
                                       placeholder="0.00">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="form-label">Prima Mensual</label>
                                <input type="number" name="prima_mensual" class="form-control" step="0.01" min="0"
                                       placeholder="0.00">
                                <div class="form-text">Se calcula automáticamente</div>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="form-label">Prima Trimestral</label>
                                <input type="number" name="prima_trimestral" class="form-control" step="0.01" min="0"
                                       placeholder="0.00">
                                <div class="form-text">Se calcula automáticamente</div>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="form-label">Prima Semestral</label>
                                <input type="number" name="prima_semestral" class="form-control" step="0.01" min="0"
                                       placeholder="0.00">
                                <div class="form-text">Se calcula automáticamente</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Coberturas Incluidas</label>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="coberturas[]" value="incendio" id="cob1">
                                        <label class="form-check-label" for="cob1">Incendio</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="coberturas[]" value="terremoto" id="cob2">
                                        <label class="form-check-label" for="cob2">Terremoto</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="coberturas[]" value="robo" id="cob3">
                                        <label class="form-check-label" for="cob3">Robo</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="coberturas[]" value="inundacion" id="cob4">
                                        <label class="form-check-label" for="cob4">Inundación</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="coberturas[]" value="rc" id="cob5">
                                        <label class="form-check-label" for="cob5">Responsabilidad Civil</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="coberturas[]" value="contenido" id="cob6">
                                        <label class="form-check-label" for="cob6">Contenido</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="coberturas[]" value="vidrios" id="cob7">
                                        <label class="form-check-label" for="cob7">Vidrios</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="coberturas[]" value="otros" id="cob8">
                                        <label class="form-check-label" for="cob8">Otros</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Information -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-warning">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Información Adicional</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Archivo de Póliza (PDF)</label>
                            <input type="file" name="archivo_poliza" class="form-control" accept=".pdf">
                            <div class="form-text">Sube el PDF de la póliza emitida (opcional)</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Notas Administrativas</label>
                            <textarea name="notas_admin" class="form-control" rows="3"
                                      placeholder="Observaciones, detalles especiales, etc."></textarea>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="crear_plan_pagos" value="1" id="crearPagos" checked>
                            <label class="form-check-label" for="crearPagos">
                                Crear plan de pagos automáticamente
                            </label>
                            <div class="form-text">Se generarán los pagos basados en la prima anual/mensual</div>
                        </div>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="d-flex justify-content-between">
                    <a href="/admin/clients.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </a>
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="fas fa-save me-2"></i>Registrar Póliza
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
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
});

// Mark fields as user-edited if manually changed
['prima_mensual', 'prima_trimestral', 'prima_semestral'].forEach(name => {
    document.querySelector(`[name="${name}"]`)?.addEventListener('input', function() {
        this.dataset.userEdited = 'true';
    });
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
