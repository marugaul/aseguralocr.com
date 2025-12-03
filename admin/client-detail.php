<?php
// admin/client-detail.php - View and edit client details
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$clientId = (int)($_GET['id'] ?? 0);
if (!$clientId) {
    header('Location: /admin/clients.php');
    exit;
}

// Get client data
$stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
$stmt->execute([$clientId]);
$client = $stmt->fetch();

if (!$client) {
    header('Location: /admin/clients.php');
    exit;
}

// Get client quotes
$quotesStmt = $pdo->prepare("SELECT * FROM quotes WHERE client_id = ? ORDER BY created_at DESC");
$quotesStmt->execute([$clientId]);
$quotes = $quotesStmt->fetchAll();

// Get client policies
$policiesStmt = $pdo->prepare("SELECT * FROM policies WHERE client_id = ? ORDER BY created_at DESC");
$policiesStmt->execute([$clientId]);
$policies = $policiesStmt->fetchAll();

// Get client documents
$docsStmt = $pdo->prepare("SELECT * FROM client_documents WHERE client_id = ? ORDER BY created_at DESC");
$docsStmt->execute([$clientId]);
$documents = $docsStmt->fetchAll();

// Get payments for all policies
$policyIds = array_column($policies, 'id');
$payments = [];
if (!empty($policyIds)) {
    $placeholders = implode(',', array_fill(0, count($policyIds), '?'));
    $paymentsStmt = $pdo->prepare("SELECT * FROM payments WHERE policy_id IN ($placeholders) ORDER BY fecha_vencimiento ASC");
    $paymentsStmt->execute($policyIds);
    $allPayments = $paymentsStmt->fetchAll();
    // Group payments by policy_id
    foreach ($allPayments as $payment) {
        $payments[$payment['policy_id']][] = $payment;
    }
}

// Handle form submission
$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre_completo'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $cedula = trim($_POST['cedula'] ?? '');
    $status = $_POST['status'] ?? 'active';

    if ($nombre && $email) {
        $updateStmt = $pdo->prepare("UPDATE clients SET nombre_completo = ?, email = ?, telefono = ?, cedula = ?, status = ? WHERE id = ?");
        $updateStmt->execute([$nombre, $email, $telefono, $cedula, $status, $clientId]);
        $message = 'Cliente actualizado correctamente';

        // Refresh client data
        $stmt->execute([$clientId]);
        $client = $stmt->fetch();
    } else {
        $error = 'Nombre y email son requeridos';
    }
}

$pageTitle = "Cliente: " . ($client['nombre_completo'] ?? 'Sin nombre');
include __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <a href="/admin/clients.php" style="color: #64748b; text-decoration: none; font-size: 0.9rem;">← Volver a Clientes</a>
        <h1 style="margin-top: 8px;">👤 <?= htmlspecialchars($client['nombre_completo'] ?? 'Sin nombre') ?></h1>
        <p>Cliente desde <?= date('d/m/Y', strtotime($client['created_at'])) ?></p>
    </div>
    <div>
        <?php if (!empty($client['google_id'])): ?>
            <span class="badge badge-purple">🔗 Google Account</span>
        <?php endif; ?>
        <span class="badge <?= $client['status'] === 'active' ? 'badge-green' : 'badge-gray' ?>">
            <?= ucfirst($client['status'] ?? 'Pendiente') ?>
        </span>
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

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px;">
    <!-- Client Info Card -->
    <div class="card">
        <div class="card-header">
            <h2>📋 Información</h2>
        </div>
        <div style="padding: 24px;">
            <form method="POST">
                <div class="form-group">
                    <label>Nombre Completo *</label>
                    <input type="text" name="nombre_completo" value="<?= htmlspecialchars($client['nombre_completo'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($client['email'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Teléfono</label>
                    <input type="tel" name="telefono" value="<?= htmlspecialchars($client['telefono'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Cédula</label>
                    <input type="text" name="cedula" value="<?= htmlspecialchars($client['cedula'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Estado</label>
                    <select name="status">
                        <option value="active" <?= ($client['status'] ?? '') === 'active' ? 'selected' : '' ?>>Activo</option>
                        <option value="inactive" <?= ($client['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactivo</option>
                        <option value="pending" <?= ($client['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pendiente</option>
                    </select>
                </div>

                <div style="margin-top: 20px; padding-top: 16px; border-top: 1px solid #e2e8f0;">
                    <p class="text-small text-muted" style="margin-bottom: 8px;">
                        <strong>Último acceso:</strong> <?= !empty($client['last_login']) ? date('d/m/Y H:i', strtotime($client['last_login'])) : 'Nunca' ?>
                    </p>
                    <?php if (!empty($client['google_id'])): ?>
                    <p class="text-small text-muted">
                        <strong>Autenticación:</strong> Google OAuth ✅
                    </p>
                    <?php else: ?>
                    <p class="text-small text-muted">
                        <strong>Autenticación:</strong> Sin cuenta vinculada
                    </p>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 16px;">
                    💾 Guardar Cambios
                </button>
            </form>
        </div>
    </div>

    <!-- Right Column -->
    <div>
        <!-- Stats Row -->
        <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 24px;">
            <div class="stat-card blue">
                <div class="label">Cotizaciones</div>
                <div class="value"><?= count($quotes) ?></div>
            </div>
            <div class="stat-card green">
                <div class="label">Pólizas</div>
                <div class="value"><?= count($policies) ?></div>
            </div>
            <div class="stat-card purple">
                <div class="label">Documentos</div>
                <div class="value"><?= count($documents) ?></div>
            </div>
        </div>

        <!-- Quotes Table -->
        <div class="card" style="margin-bottom: 24px;">
            <div class="card-header">
                <h2>📝 Cotizaciones</h2>
            </div>
            <div class="card-body">
                <?php if (empty($quotes)): ?>
                <div style="padding: 40px; text-align: center; color: #64748b;">
                    No hay cotizaciones registradas
                </div>
                <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Número</th>
                                <th>Tipo</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($quotes as $quote): ?>
                            <tr>
                                <td><?= htmlspecialchars($quote['numero_cotizacion'] ?? '-') ?></td>
                                <td><span class="badge badge-blue"><?= htmlspecialchars($quote['tipo_seguro'] ?? '-') ?></span></td>
                                <td class="text-small"><?= date('d/m/Y', strtotime($quote['created_at'])) ?></td>
                                <td>
                                    <span class="badge <?= $quote['status'] === 'pendiente' ? 'badge-orange' : 'badge-green' ?>">
                                        <?= ucfirst($quote['status'] ?? 'pendiente') ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="/admin/view_cotizacion.php?id=<?= $quote['id'] ?>" class="action-btn view">👁</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Policies Section -->
        <div class="card" style="margin-bottom: 24px;">
            <div class="card-header">
                <h2>📑 Pólizas</h2>
                <a href="/admin/add-policy.php?client_id=<?= $clientId ?>" class="btn btn-sm btn-primary">➕ Nueva Póliza</a>
            </div>
            <div class="card-body">
                <?php if (empty($policies)): ?>
                <div style="padding: 40px; text-align: center; color: #64748b;">
                    No hay pólizas registradas
                </div>
                <?php else: ?>
                <?php foreach ($policies as $index => $policy): ?>
                <div class="policy-card" style="border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 16px; overflow: hidden;">
                    <!-- Policy Header (clickable) -->
                    <div class="policy-header" onclick="togglePolicy(<?= $policy['id'] ?>)" style="padding: 16px 20px; background: #f8fafc; cursor: pointer; display: flex; justify-content: space-between; align-items: center;">
                        <div style="display: flex; align-items: center; gap: 16px;">
                            <div>
                                <strong style="font-size: 1.1rem;">#<?= htmlspecialchars($policy['numero_poliza'] ?? '-') ?></strong>
                                <span class="badge badge-blue" style="margin-left: 8px;"><?= htmlspecialchars($policy['tipo_seguro'] ?? '-') ?></span>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <span class="badge <?= ($policy['status'] ?? '') === 'activa' ? 'badge-green' : (($policy['status'] ?? '') === 'vencida' ? 'badge-orange' : 'badge-gray') ?>">
                                <?= ucfirst($policy['status'] ?? 'pendiente') ?>
                            </span>
                            <span style="color: #64748b; font-weight: 600;">
                                <?= htmlspecialchars($policy['moneda'] ?? 'CRC') ?> <?= number_format($policy['prima_anual'] ?? 0, 2) ?>/año
                            </span>
                            <span class="policy-toggle" id="toggle-<?= $policy['id'] ?>" style="font-size: 1.2rem; color: #64748b;">▼</span>
                        </div>
                    </div>

                    <!-- Policy Details (expandable) -->
                    <div class="policy-details" id="policy-<?= $policy['id'] ?>" style="display: none; padding: 20px; border-top: 1px solid #e2e8f0;">
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 20px;">
                            <div>
                                <label style="font-size: 0.8rem; color: #64748b; display: block;">Aseguradora</label>
                                <strong><?= htmlspecialchars($policy['aseguradora'] ?? 'INS') ?></strong>
                            </div>
                            <div>
                                <label style="font-size: 0.8rem; color: #64748b; display: block;">Fecha Emisión</label>
                                <strong><?= !empty($policy['fecha_emision']) ? date('d/m/Y', strtotime($policy['fecha_emision'])) : '-' ?></strong>
                            </div>
                            <div>
                                <label style="font-size: 0.8rem; color: #64748b; display: block;">Monto Asegurado</label>
                                <strong><?= htmlspecialchars($policy['moneda'] ?? 'CRC') ?> <?= number_format($policy['monto_asegurado'] ?? 0, 2) ?></strong>
                            </div>
                            <div>
                                <label style="font-size: 0.8rem; color: #64748b; display: block;">Inicio Vigencia</label>
                                <strong><?= !empty($policy['fecha_inicio_vigencia']) ? date('d/m/Y', strtotime($policy['fecha_inicio_vigencia'])) : '-' ?></strong>
                            </div>
                            <div>
                                <label style="font-size: 0.8rem; color: #64748b; display: block;">Fin Vigencia</label>
                                <strong><?= !empty($policy['fecha_fin_vigencia']) ? date('d/m/Y', strtotime($policy['fecha_fin_vigencia'])) : '-' ?></strong>
                            </div>
                            <div>
                                <label style="font-size: 0.8rem; color: #64748b; display: block;">Creada</label>
                                <strong><?= !empty($policy['created_at']) ? date('d/m/Y H:i', strtotime($policy['created_at'])) : '-' ?></strong>
                            </div>
                        </div>

                        <!-- Primas -->
                        <div style="background: #f0fdf4; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px;">
                            <label style="font-size: 0.8rem; color: #166534; display: block; margin-bottom: 8px;">💰 Primas</label>
                            <div style="display: flex; gap: 24px; flex-wrap: wrap;">
                                <span><strong>Anual:</strong> <?= number_format($policy['prima_anual'] ?? 0, 2) ?></span>
                                <span><strong>Semestral:</strong> <?= number_format($policy['prima_semestral'] ?? 0, 2) ?></span>
                                <span><strong>Trimestral:</strong> <?= number_format($policy['prima_trimestral'] ?? 0, 2) ?></span>
                                <span><strong>Mensual:</strong> <?= number_format($policy['prima_mensual'] ?? 0, 2) ?></span>
                            </div>
                        </div>

                        <?php if (!empty($policy['detalles_bien_asegurado'])): ?>
                        <div style="margin-bottom: 16px;">
                            <label style="font-size: 0.8rem; color: #64748b; display: block;">Detalles del Bien Asegurado</label>
                            <p style="margin: 4px 0; white-space: pre-wrap;"><?= htmlspecialchars($policy['detalles_bien_asegurado']) ?></p>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($policy['notas_admin'])): ?>
                        <div style="background: #fef3c7; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px;">
                            <label style="font-size: 0.8rem; color: #92400e; display: block;">📝 Notas Internas</label>
                            <p style="margin: 4px 0; white-space: pre-wrap;"><?= htmlspecialchars($policy['notas_admin']) ?></p>
                        </div>
                        <?php endif; ?>

                        <!-- Action Buttons -->
                        <div style="display: flex; gap: 12px; margin-bottom: 20px;">
                            <a href="/admin/edit-policy.php?id=<?= $policy['id'] ?>" class="btn btn-sm btn-secondary">✏️ Editar Póliza</a>
                            <?php if (!empty($policy['archivo_poliza_url'])): ?>
                            <a href="<?= htmlspecialchars($policy['archivo_poliza_url']) ?>" target="_blank" class="btn btn-sm btn-primary">📄 Ver Documento</a>
                            <?php endif; ?>
                        </div>

                        <!-- Payments Table -->
                        <?php $policyPayments = $payments[$policy['id']] ?? []; ?>
                        <?php if (!empty($policyPayments)): ?>
                        <div style="border-top: 1px solid #e2e8f0; padding-top: 16px;">
                            <h4 style="margin-bottom: 12px;">📅 Plan de Pagos (<?= count($policyPayments) ?> cuotas)</h4>
                            <div class="table-wrapper">
                                <table style="font-size: 0.9rem;">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Tipo</th>
                                            <th>Monto</th>
                                            <th>Vencimiento</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($policyPayments as $i => $payment): ?>
                                        <?php
                                            $isOverdue = $payment['status'] === 'pendiente' && strtotime($payment['fecha_vencimiento']) < time();
                                        ?>
                                        <tr style="<?= $isOverdue ? 'background: #fef2f2;' : '' ?>">
                                            <td><?= $i + 1 ?></td>
                                            <td><?= ucfirst(str_replace('_', ' ', $payment['tipo_pago'] ?? '-')) ?></td>
                                            <td><strong><?= htmlspecialchars($payment['moneda'] ?? 'CRC') ?> <?= number_format($payment['monto'] ?? 0, 2) ?></strong></td>
                                            <td><?= date('d/m/Y', strtotime($payment['fecha_vencimiento'])) ?></td>
                                            <td>
                                                <?php if ($payment['status'] === 'pagado'): ?>
                                                    <span class="badge badge-green">✓ Pagado</span>
                                                <?php elseif ($isOverdue): ?>
                                                    <span class="badge badge-red">⚠ Vencido</span>
                                                <?php else: ?>
                                                    <span class="badge badge-orange">Pendiente</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($payment['status'] !== 'pagado'): ?>
                                                <button onclick="markAsPaid(<?= $payment['id'] ?>)" class="btn btn-sm btn-success" style="padding: 4px 8px; font-size: 0.75rem;">💵 Marcar Pagado</button>
                                                <?php else: ?>
                                                <span class="text-small text-muted"><?= !empty($payment['fecha_pago']) ? date('d/m/Y', strtotime($payment['fecha_pago'])) : '' ?></span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php
                                $totalPagado = array_sum(array_map(fn($p) => $p['status'] === 'pagado' ? $p['monto'] : 0, $policyPayments));
                                $totalPendiente = array_sum(array_map(fn($p) => $p['status'] !== 'pagado' ? $p['monto'] : 0, $policyPayments));
                            ?>
                            <div style="display: flex; gap: 24px; margin-top: 12px; padding: 12px; background: #f8fafc; border-radius: 8px;">
                                <span>✅ <strong>Pagado:</strong> <?= htmlspecialchars($policy['moneda'] ?? 'CRC') ?> <?= number_format($totalPagado, 2) ?></span>
                                <span>⏳ <strong>Pendiente:</strong> <?= htmlspecialchars($policy['moneda'] ?? 'CRC') ?> <?= number_format($totalPendiente, 2) ?></span>
                            </div>
                        </div>
                        <?php else: ?>
                        <div style="border-top: 1px solid #e2e8f0; padding-top: 16px; color: #64748b;">
                            No hay plan de pagos asociado a esta póliza
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Documents Table -->
        <div class="card">
            <div class="card-header">
                <h2>📄 Documentos</h2>
                <a href="/admin/documents.php?client_id=<?= $clientId ?>" class="btn btn-sm btn-primary">➕ Subir</a>
            </div>
            <div class="card-body">
                <?php if (empty($documents)): ?>
                <div style="padding: 40px; text-align: center; color: #64748b;">
                    No hay documentos subidos
                </div>
                <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Documento</th>
                                <th>Tipo</th>
                                <th>Fecha</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($documents as $doc): ?>
                            <?php
                                $filePath = __DIR__ . '/../' . ($doc['ruta_archivo'] ?? '');
                                $fileExists = !empty($doc['ruta_archivo']) && file_exists($filePath);
                            ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars($doc['nombre'] ?? $doc['nombre_archivo'] ?? '-') ?>
                                    <?php if (!$fileExists): ?>
                                        <span class="badge badge-red" title="El archivo no existe en el servidor">⚠️</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge badge-gray"><?= htmlspecialchars($doc['tipo'] ?? 'Otro') ?></span></td>
                                <td class="text-small"><?= date('d/m/Y', strtotime($doc['created_at'])) ?></td>
                                <td>
                                    <?php if ($fileExists): ?>
                                        <a href="/admin/actions/download-document.php?id=<?= $doc['id'] ?>" class="action-btn view">⬇️</a>
                                    <?php else: ?>
                                        <span class="text-muted" title="Archivo no disponible">❌</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function togglePolicy(policyId) {
    const details = document.getElementById('policy-' + policyId);
    const toggle = document.getElementById('toggle-' + policyId);
    if (details.style.display === 'none') {
        details.style.display = 'block';
        toggle.textContent = '▲';
    } else {
        details.style.display = 'none';
        toggle.textContent = '▼';
    }
}

function markAsPaid(paymentId) {
    if (confirm('¿Marcar este pago como pagado?')) {
        fetch('/admin/actions/mark-payment-paid.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'payment_id=' + paymentId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + (data.error || 'No se pudo actualizar el pago'));
            }
        })
        .catch(err => {
            alert('Error de conexión');
            console.error(err);
        });
    }
}

// Auto-expand first policy if any
document.addEventListener('DOMContentLoaded', function() {
    const firstPolicy = document.querySelector('.policy-details');
    const firstToggle = document.querySelector('.policy-toggle');
    if (firstPolicy) {
        firstPolicy.style.display = 'block';
        if (firstToggle) firstToggle.textContent = '▲';
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
