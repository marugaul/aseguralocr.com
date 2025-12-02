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
                            <tr>
                                <td><?= htmlspecialchars($doc['nombre_original'] ?? $doc['filename'] ?? '-') ?></td>
                                <td><span class="badge badge-gray"><?= htmlspecialchars($doc['tipo'] ?? 'Otro') ?></span></td>
                                <td class="text-small"><?= date('d/m/Y', strtotime($doc['created_at'])) ?></td>
                                <td>
                                    <a href="/admin/actions/download-document.php?id=<?= $doc['id'] ?>" class="action-btn view">⬇️</a>
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

<?php include __DIR__ . '/includes/footer.php'; ?>
