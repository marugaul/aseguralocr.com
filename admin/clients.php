<?php
// admin/clients.php - Manage clients and their policies
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

// Get all clients with summary
$stmt = $pdo->query("
    SELECT c.*,
           COUNT(DISTINCT p.id) as total_policies,
           COUNT(DISTINCT CASE WHEN p.status = 'vigente' THEN p.id END) as active_policies,
           COUNT(DISTINCT q.id) as total_quotes,
           COUNT(DISTINCT CASE WHEN pay.status = 'pendiente' THEN pay.id END) as pending_payments
    FROM clients c
    LEFT JOIN policies p ON c.id = p.client_id
    LEFT JOIN quotes q ON c.id = q.client_id
    LEFT JOIN payments pay ON p.id = pay.policy_id AND pay.status = 'pendiente'
    GROUP BY c.id
    ORDER BY c.created_at DESC
");
$clients = $stmt->fetchAll();

$totalClients = count($clients);
$activeClients = count(array_filter($clients, fn($c) => $c['status'] === 'active'));
$googleClients = count(array_filter($clients, fn($c) => !empty($c['google_id'])));
$withPolicies = count(array_filter($clients, fn($c) => $c['total_policies'] > 0));

$pageTitle = "Gestión de Clientes";
include __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1>👥 Gestión de Clientes</h1>
        <p>Administra clientes, pólizas y pagos</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('addClientModal')">
        ➕ Nuevo Cliente
    </button>
</div>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card blue">
        <div class="label">Total Clientes</div>
        <div class="value"><?= $totalClients ?></div>
    </div>
    <div class="stat-card green">
        <div class="label">Clientes Activos</div>
        <div class="value"><?= $activeClients ?></div>
    </div>
    <div class="stat-card purple">
        <div class="label">Con Google</div>
        <div class="value"><?= $googleClients ?></div>
    </div>
    <div class="stat-card orange">
        <div class="label">Con Pólizas</div>
        <div class="value"><?= $withPolicies ?></div>
    </div>
</div>

<!-- Clients Table -->
<div class="card">
    <div class="card-header">
        <h2>Lista de Clientes</h2>
        <input type="text" id="searchClient" class="search-box" placeholder="🔍 Buscar cliente...">
    </div>
    <div class="card-body">
        <div class="table-wrapper">
            <table id="clientsTable">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Email</th>
                        <th>Teléfono</th>
                        <th>Pólizas</th>
                        <th>Cotizaciones</th>
                        <th>Pagos Pend.</th>
                        <th>Estado</th>
                        <th>Último Acceso</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clients as $client): ?>
                    <tr>
                        <td>
                            <div class="user-cell">
                                <?php if (!empty($client['avatar_url'])): ?>
                                    <div class="user-avatar">
                                        <img src="<?= htmlspecialchars($client['avatar_url']) ?>" alt="">
                                    </div>
                                <?php else: ?>
                                    <div class="user-avatar">
                                        <?= strtoupper(substr($client['nombre_completo'] ?? 'U', 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                                <div class="user-info">
                                    <div class="name"><?= htmlspecialchars($client['nombre_completo'] ?? 'Sin nombre') ?></div>
                                    <?php if (!empty($client['google_id'])): ?>
                                        <div class="meta">🔗 Google</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="text-small"><?= htmlspecialchars($client['email'] ?? '-') ?></span>
                            <?php if (!empty($client['email_verified'])): ?>
                                <span title="Verificado">✅</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-small"><?= htmlspecialchars($client['telefono'] ?? '-') ?></td>
                        <td>
                            <span class="badge badge-blue">
                                <?= $client['active_policies'] ?>/<?= $client['total_policies'] ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-purple"><?= $client['total_quotes'] ?></span>
                        </td>
                        <td>
                            <?php if ($client['pending_payments'] > 0): ?>
                                <span class="badge badge-red"><?= $client['pending_payments'] ?></span>
                            <?php else: ?>
                                <span class="badge badge-green">0</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($client['status'] === 'active'): ?>
                                <span class="badge badge-green">Activo</span>
                            <?php else: ?>
                                <span class="badge badge-gray"><?= ucfirst($client['status'] ?? 'Pendiente') ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-small text-muted">
                            <?= !empty($client['last_login']) ? date('d/m/Y H:i', strtotime($client['last_login'])) : 'Nunca' ?>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="/admin/client-detail.php?id=<?= $client['id'] ?>" class="action-btn view" title="Ver">👁</a>
                                <a href="/admin/add-policy.php?client_id=<?= $client['id'] ?>" class="action-btn edit" title="Nueva póliza">➕</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Client Modal -->
<div class="modal-overlay" id="addClientModal">
    <div class="modal">
        <div class="modal-header">
            <h3>➕ Nuevo Cliente</h3>
            <button class="modal-close" onclick="closeModal('addClientModal')">&times;</button>
        </div>
        <form action="/admin/actions/save-client.php" method="POST">
            <div class="modal-body">
                <div class="form-group">
                    <label>Nombre Completo *</label>
                    <input type="text" name="nombre_completo" required>
                </div>
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Teléfono</label>
                    <input type="tel" name="telefono">
                </div>
                <div class="form-group">
                    <label>Cédula</label>
                    <input type="text" name="cedula">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addClientModal')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Crear Cliente</button>
            </div>
        </form>
    </div>
</div>

<script>
// Search functionality
document.getElementById('searchClient')?.addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('#clientsTable tbody tr');
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
