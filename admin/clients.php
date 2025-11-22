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

$pageTitle = "Gestión de Clientes";
include __DIR__ . '/includes/header.php';
?>

<div class="container-fluid px-4 py-5">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0"><i class="fas fa-users me-2"></i>Gestión de Clientes</h1>
            <p class="text-muted mb-0">Administra clientes, pólizas y pagos</p>
        </div>
        <div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addClientModal">
                <i class="fas fa-plus me-2"></i>Nuevo Cliente
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Total Clientes</p>
                            <h3 class="mb-0"><?= count($clients) ?></h3>
                        </div>
                        <div class="bg-primary bg-opacity-10 p-3 rounded">
                            <i class="fas fa-users text-primary fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Clientes Activos</p>
                            <h3 class="mb-0"><?= count(array_filter($clients, fn($c) => $c['status'] === 'active')) ?></h3>
                        </div>
                        <div class="bg-success bg-opacity-10 p-3 rounded">
                            <i class="fas fa-user-check text-success fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Con Google</p>
                            <h3 class="mb-0"><?= count(array_filter($clients, fn($c) => !empty($c['google_id']))) ?></h3>
                        </div>
                        <div class="bg-info bg-opacity-10 p-3 rounded">
                            <i class="fab fa-google text-info fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Con Pólizas</p>
                            <h3 class="mb-0"><?= count(array_filter($clients, fn($c) => $c['total_policies'] > 0)) ?></h3>
                        </div>
                        <div class="bg-warning bg-opacity-10 p-3 rounded">
                            <i class="fas fa-shield-alt text-warning fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Clients Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <div class="row align-items-center">
                <div class="col">
                    <h5 class="mb-0">Lista de Clientes</h5>
                </div>
                <div class="col-auto">
                    <input type="text" id="searchClient" class="form-control form-control-sm" placeholder="Buscar cliente...">
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="clientsTable">
                    <thead class="table-light">
                        <tr>
                            <th>Cliente</th>
                            <th>Email</th>
                            <th>Teléfono</th>
                            <th>Pólizas</th>
                            <th>Cotizaciones</th>
                            <th>Pagos Pend.</th>
                            <th>Estado</th>
                            <th>Último Acceso</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clients as $client): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php if ($client['avatar_url']): ?>
                                        <img src="<?= htmlspecialchars($client['avatar_url']) ?>"
                                             class="rounded-circle me-2" width="32" height="32" alt="">
                                    <?php else: ?>
                                        <div class="bg-secondary bg-opacity-10 rounded-circle me-2 d-flex align-items-center justify-center" style="width:32px;height:32px;">
                                            <i class="fas fa-user text-secondary"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="fw-semibold"><?= htmlspecialchars($client['nombre_completo']) ?></div>
                                        <?php if ($client['google_id']): ?>
                                            <small class="text-muted"><i class="fab fa-google"></i> Google</small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <small><?= htmlspecialchars($client['email']) ?></small>
                                <?php if ($client['email_verified']): ?>
                                    <i class="fas fa-check-circle text-success ms-1" title="Verificado"></i>
                                <?php endif; ?>
                            </td>
                            <td><small><?= htmlspecialchars($client['telefono'] ?: '-') ?></small></td>
                            <td>
                                <span class="badge bg-primary">
                                    <?= $client['active_policies'] ?> / <?= $client['total_policies'] ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-info"><?= $client['total_quotes'] ?></span>
                            </td>
                            <td>
                                <?php if ($client['pending_payments'] > 0): ?>
                                    <span class="badge bg-danger"><?= $client['pending_payments'] ?></span>
                                <?php else: ?>
                                    <span class="badge bg-success">0</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($client['status'] === 'active'): ?>
                                    <span class="badge bg-success">Activo</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><?= ucfirst($client['status']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small class="text-muted">
                                    <?= $client['last_login'] ? date('d/m/Y H:i', strtotime($client['last_login'])) : 'Nunca' ?>
                                </small>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="/admin/client-detail.php?id=<?= $client['id'] ?>"
                                       class="btn btn-outline-primary" title="Ver detalles">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="/admin/add-policy.php?client_id=<?= $client['id'] ?>"
                                       class="btn btn-outline-success" title="Nueva póliza">
                                        <i class="fas fa-plus"></i>
                                    </a>
                                    <button class="btn btn-outline-secondary" title="Editar"
                                            onclick="editClient(<?= htmlspecialchars(json_encode($client)) ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Client Modal -->
<div class="modal fade" id="addClientModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nuevo Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="/admin/actions/save-client.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre Completo *</label>
                        <input type="text" name="nombre_completo" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Teléfono</label>
                        <input type="tel" name="telefono" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cédula</label>
                        <input type="text" name="cedula" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear Cliente</button>
                </div>
            </form>
        </div>
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

function editClient(client) {
    // TODO: Implement edit modal
    console.log('Edit client:', client);
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
