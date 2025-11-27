<?php
// admin/client-detail.php - Vista detallada de cliente con todas las secciones
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$client_id = intval($_GET['id'] ?? 0);
if (!$client_id) {
    header('Location: /admin/clients.php');
    exit;
}

// Obtener datos del cliente
$stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
$stmt->execute([$client_id]);
$client = $stmt->fetch();

if (!$client) {
    $_SESSION['flash_message'] = 'Cliente no encontrado';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /admin/clients.php');
    exit;
}

// Obtener pólizas del cliente
$stmtPolicies = $pdo->prepare("
    SELECT p.*,
           (SELECT COUNT(*) FROM payments WHERE policy_id = p.id AND status = 'pendiente') as pagos_pendientes,
           (SELECT SUM(monto) FROM payments WHERE policy_id = p.id AND status = 'pendiente') as deuda_total
    FROM policies p
    WHERE p.client_id = ?
    ORDER BY p.created_at DESC
");
$stmtPolicies->execute([$client_id]);
$policies = $stmtPolicies->fetchAll();

// Obtener pagos del cliente (a través de pólizas)
$stmtPayments = $pdo->prepare("
    SELECT pay.*, pol.numero_poliza, pol.tipo_seguro
    FROM payments pay
    INNER JOIN policies pol ON pay.policy_id = pol.id
    WHERE pol.client_id = ?
    ORDER BY pay.fecha_vencimiento DESC
    LIMIT 50
");
$stmtPayments->execute([$client_id]);
$payments = $stmtPayments->fetchAll();

// Obtener documentos del cliente
$stmtDocs = $pdo->prepare("
    SELECT d.*, p.numero_poliza
    FROM client_documents d
    LEFT JOIN policies p ON d.policy_id = p.id
    WHERE d.client_id = ?
    ORDER BY d.created_at DESC
");
try {
    $stmtDocs->execute([$client_id]);
    $documents = $stmtDocs->fetchAll();
} catch (PDOException $e) {
    $documents = []; // Tabla aún no creada
}

// Obtener notas del cliente
$stmtNotes = $pdo->prepare("
    SELECT n.*, p.numero_poliza
    FROM client_notes n
    LEFT JOIN policies p ON n.policy_id = p.id
    WHERE n.client_id = ?
    ORDER BY n.created_at DESC
    LIMIT 20
");
try {
    $stmtNotes->execute([$client_id]);
    $notes = $stmtNotes->fetchAll();
} catch (PDOException $e) {
    $notes = []; // Tabla aún no creada
}

// Calcular estadísticas
$totalDeuda = array_sum(array_column($policies, 'deuda_total'));
$polizasActivas = count(array_filter($policies, fn($p) => $p['status'] === 'vigente'));
$pagosPendientes = count(array_filter($payments, fn($p) => $p['status'] === 'pendiente'));

$pageTitle = "Cliente: " . $client['nombre_completo'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="p-4 md:p-8">
        <!-- Header -->
        <div class="flex flex-wrap justify-between items-start gap-4 mb-6">
            <div>
                <a href="/admin/clients.php" class="text-blue-600 hover:underline mb-2 inline-block">
                    <i class="fas fa-arrow-left mr-2"></i>Volver a Clientes
                </a>
                <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-3">
                    <?php if ($client['avatar_url']): ?>
                        <img src="<?= htmlspecialchars($client['avatar_url']) ?>" class="w-12 h-12 rounded-full" alt="">
                    <?php else: ?>
                        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-user text-blue-600 text-xl"></i>
                        </div>
                    <?php endif; ?>
                    <?= htmlspecialchars($client['nombre_completo']) ?>
                    <?php if ($client['status'] === 'active'): ?>
                        <span class="text-sm bg-green-100 text-green-800 px-2 py-1 rounded">Activo</span>
                    <?php else: ?>
                        <span class="text-sm bg-gray-100 text-gray-600 px-2 py-1 rounded"><?= ucfirst($client['status']) ?></span>
                    <?php endif; ?>
                </h1>
            </div>
            <div class="flex gap-2">
                <a href="/admin/add-policy.php?client_id=<?= $client_id ?>" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">
                    <i class="fas fa-plus mr-2"></i>Nueva Póliza
                </a>
                <button onclick="document.getElementById('editModal').classList.remove('hidden')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                    <i class="fas fa-edit mr-2"></i>Editar Cliente
                </button>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-gray-500 text-sm">Pólizas Activas</p>
                <p class="text-2xl font-bold text-blue-600"><?= $polizasActivas ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-gray-500 text-sm">Total Pólizas</p>
                <p class="text-2xl font-bold text-gray-800"><?= count($policies) ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-gray-500 text-sm">Pagos Pendientes</p>
                <p class="text-2xl font-bold <?= $pagosPendientes > 0 ? 'text-red-600' : 'text-green-600' ?>"><?= $pagosPendientes ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-gray-500 text-sm">Deuda Total</p>
                <p class="text-2xl font-bold <?= $totalDeuda > 0 ? 'text-red-600' : 'text-green-600' ?>">
                    ₡<?= number_format($totalDeuda, 0, ',', '.') ?>
                </p>
            </div>
        </div>

        <!-- Tabs Navigation -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="border-b">
                <nav class="flex flex-wrap -mb-px" id="tabs">
                    <button class="tab-btn active px-6 py-3 text-blue-600 border-b-2 border-blue-600 font-medium" data-tab="info">
                        <i class="fas fa-user mr-2"></i>Información
                    </button>
                    <button class="tab-btn px-6 py-3 text-gray-500 hover:text-gray-700" data-tab="polizas">
                        <i class="fas fa-file-contract mr-2"></i>Pólizas (<?= count($policies) ?>)
                    </button>
                    <button class="tab-btn px-6 py-3 text-gray-500 hover:text-gray-700" data-tab="pagos">
                        <i class="fas fa-credit-card mr-2"></i>Pagos (<?= count($payments) ?>)
                    </button>
                    <button class="tab-btn px-6 py-3 text-gray-500 hover:text-gray-700" data-tab="documentos">
                        <i class="fas fa-folder mr-2"></i>Documentos (<?= count($documents) ?>)
                    </button>
                    <button class="tab-btn px-6 py-3 text-gray-500 hover:text-gray-700" data-tab="notas">
                        <i class="fas fa-sticky-note mr-2"></i>Notas (<?= count($notes) ?>)
                    </button>
                </nav>
            </div>

            <!-- Tab Contents -->
            <div class="p-6">
                <!-- Info Tab -->
                <div id="tab-info" class="tab-content">
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="font-semibold text-gray-800 mb-4">Datos Personales</h3>
                            <dl class="space-y-3">
                                <div class="flex border-b pb-2">
                                    <dt class="w-1/3 text-gray-500">Email:</dt>
                                    <dd class="w-2/3"><?= htmlspecialchars($client['email']) ?>
                                        <?php if ($client['email_verified']): ?>
                                            <i class="fas fa-check-circle text-green-500 ml-1" title="Verificado"></i>
                                        <?php endif; ?>
                                    </dd>
                                </div>
                                <div class="flex border-b pb-2">
                                    <dt class="w-1/3 text-gray-500">Teléfono:</dt>
                                    <dd class="w-2/3"><?= htmlspecialchars($client['telefono'] ?: '-') ?></dd>
                                </div>
                                <div class="flex border-b pb-2">
                                    <dt class="w-1/3 text-gray-500">Cédula:</dt>
                                    <dd class="w-2/3"><?= htmlspecialchars($client['cedula'] ?: '-') ?></dd>
                                </div>
                                <div class="flex border-b pb-2">
                                    <dt class="w-1/3 text-gray-500">Fecha Nac.:</dt>
                                    <dd class="w-2/3"><?= $client['fecha_nacimiento'] ? date('d/m/Y', strtotime($client['fecha_nacimiento'])) : '-' ?></dd>
                                </div>
                            </dl>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-800 mb-4">Dirección</h3>
                            <dl class="space-y-3">
                                <div class="flex border-b pb-2">
                                    <dt class="w-1/3 text-gray-500">Dirección:</dt>
                                    <dd class="w-2/3"><?= htmlspecialchars($client['direccion'] ?: '-') ?></dd>
                                </div>
                                <div class="flex border-b pb-2">
                                    <dt class="w-1/3 text-gray-500">Provincia:</dt>
                                    <dd class="w-2/3"><?= htmlspecialchars($client['provincia'] ?: '-') ?></dd>
                                </div>
                                <div class="flex border-b pb-2">
                                    <dt class="w-1/3 text-gray-500">Cantón:</dt>
                                    <dd class="w-2/3"><?= htmlspecialchars($client['canton'] ?: '-') ?></dd>
                                </div>
                                <div class="flex border-b pb-2">
                                    <dt class="w-1/3 text-gray-500">Distrito:</dt>
                                    <dd class="w-2/3"><?= htmlspecialchars($client['distrito'] ?: '-') ?></dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                    <div class="mt-6 pt-4 border-t">
                        <p class="text-sm text-gray-500">
                            <i class="fas fa-clock mr-1"></i>Creado: <?= date('d/m/Y H:i', strtotime($client['created_at'])) ?>
                            <?php if ($client['last_login']): ?>
                                | Último acceso: <?= date('d/m/Y H:i', strtotime($client['last_login'])) ?>
                            <?php endif; ?>
                            <?php if ($client['google_id']): ?>
                                | <i class="fab fa-google text-blue-500"></i> Vinculado con Google
                            <?php endif; ?>
                        </p>
                    </div>
                </div>

                <!-- Pólizas Tab -->
                <div id="tab-polizas" class="tab-content hidden">
                    <?php if (empty($policies)): ?>
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-file-contract text-4xl mb-3"></i>
                            <p>No hay pólizas registradas</p>
                            <a href="/admin/add-policy.php?client_id=<?= $client_id ?>" class="text-blue-600 hover:underline">Agregar primera póliza</a>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Póliza</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vigencia</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Prima</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Deuda</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <?php foreach ($policies as $pol): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3">
                                            <span class="font-medium"><?= htmlspecialchars($pol['numero_poliza']) ?></span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="capitalize"><?= htmlspecialchars($pol['tipo_seguro']) ?></span>
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            <?= date('d/m/Y', strtotime($pol['fecha_inicio_vigencia'])) ?> -
                                            <?= date('d/m/Y', strtotime($pol['fecha_fin_vigencia'])) ?>
                                        </td>
                                        <td class="px-4 py-3">
                                            <?= $pol['moneda'] === 'dolares' ? '$' : '₡' ?><?= number_format($pol['prima_anual'], 0) ?>
                                        </td>
                                        <td class="px-4 py-3">
                                            <?php if ($pol['deuda_total'] > 0): ?>
                                                <span class="text-red-600 font-medium">₡<?= number_format($pol['deuda_total'], 0) ?></span>
                                            <?php else: ?>
                                                <span class="text-green-600">Al día</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-3">
                                            <?php
                                            $statusColors = [
                                                'vigente' => 'bg-green-100 text-green-800',
                                                'vencida' => 'bg-red-100 text-red-800',
                                                'por_vencer' => 'bg-yellow-100 text-yellow-800',
                                                'cancelada' => 'bg-gray-100 text-gray-800'
                                            ];
                                            $color = $statusColors[$pol['status']] ?? 'bg-gray-100 text-gray-800';
                                            ?>
                                            <span class="px-2 py-1 rounded text-xs <?= $color ?>"><?= ucfirst($pol['status']) ?></span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <button onclick="viewPolicy(<?= $pol['id'] ?>)" class="text-blue-600 hover:text-blue-800">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Pagos Tab -->
                <div id="tab-pagos" class="tab-content hidden">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-semibold">Historial de Pagos</h3>
                        <button onclick="document.getElementById('addPaymentModal').classList.remove('hidden')" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm">
                            <i class="fas fa-plus mr-1"></i>Registrar Pago
                        </button>
                    </div>
                    <?php if (empty($payments)): ?>
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-credit-card text-4xl mb-3"></i>
                            <p>No hay pagos registrados</p>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Póliza</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Monto</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vencimiento</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha Pago</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <?php foreach ($payments as $pay): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm"><?= htmlspecialchars($pay['numero_poliza']) ?></td>
                                        <td class="px-4 py-3 text-sm capitalize"><?= str_replace('_', ' ', $pay['tipo_pago']) ?></td>
                                        <td class="px-4 py-3 font-medium">
                                            <?= $pay['moneda'] === 'dolares' ? '$' : '₡' ?><?= number_format($pay['monto'], 0) ?>
                                        </td>
                                        <td class="px-4 py-3 text-sm"><?= date('d/m/Y', strtotime($pay['fecha_vencimiento'])) ?></td>
                                        <td class="px-4 py-3 text-sm"><?= $pay['fecha_pago'] ? date('d/m/Y', strtotime($pay['fecha_pago'])) : '-' ?></td>
                                        <td class="px-4 py-3">
                                            <?php
                                            $payStatusColors = [
                                                'pagado' => 'bg-green-100 text-green-800',
                                                'pendiente' => 'bg-yellow-100 text-yellow-800',
                                                'vencido' => 'bg-red-100 text-red-800'
                                            ];
                                            $payColor = $payStatusColors[$pay['status']] ?? 'bg-gray-100 text-gray-800';
                                            ?>
                                            <span class="px-2 py-1 rounded text-xs <?= $payColor ?>"><?= ucfirst($pay['status']) ?></span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <?php if ($pay['status'] !== 'pagado'): ?>
                                            <button onclick="markAsPaid(<?= $pay['id'] ?>)" class="text-green-600 hover:text-green-800 text-sm">
                                                <i class="fas fa-check mr-1"></i>Marcar Pagado
                                            </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Documentos Tab -->
                <div id="tab-documentos" class="tab-content hidden">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-semibold">Documentos del Cliente</h3>
                        <button onclick="document.getElementById('uploadModal').classList.remove('hidden')" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm">
                            <i class="fas fa-upload mr-1"></i>Subir Documento
                        </button>
                    </div>
                    <?php if (empty($documents)): ?>
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-folder-open text-4xl mb-3"></i>
                            <p>No hay documentos cargados</p>
                        </div>
                    <?php else: ?>
                        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <?php foreach ($documents as $doc): ?>
                            <div class="border rounded-lg p-4 hover:shadow-md transition">
                                <div class="flex items-start justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-blue-100 rounded flex items-center justify-center">
                                            <i class="fas fa-file-<?= $doc['archivo_mime'] === 'application/pdf' ? 'pdf text-red-500' : 'alt text-blue-500' ?>"></i>
                                        </div>
                                        <div>
                                            <p class="font-medium text-sm"><?= htmlspecialchars($doc['nombre']) ?></p>
                                            <p class="text-xs text-gray-500"><?= ucfirst($doc['tipo_documento']) ?></p>
                                        </div>
                                    </div>
                                    <a href="<?= htmlspecialchars($doc['archivo_path']) ?>" target="_blank" class="text-blue-600 hover:text-blue-800">
                                        <i class="fas fa-download"></i>
                                    </a>
                                </div>
                                <p class="text-xs text-gray-400 mt-2">
                                    <?= date('d/m/Y', strtotime($doc['created_at'])) ?>
                                    <?php if ($doc['numero_poliza']): ?>
                                        | Póliza: <?= htmlspecialchars($doc['numero_poliza']) ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Notas Tab -->
                <div id="tab-notas" class="tab-content hidden">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-semibold">Notas e Historial</h3>
                        <button onclick="document.getElementById('addNoteModal').classList.remove('hidden')" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm">
                            <i class="fas fa-plus mr-1"></i>Nueva Nota
                        </button>
                    </div>
                    <?php if (empty($notes)): ?>
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-sticky-note text-4xl mb-3"></i>
                            <p>No hay notas registradas</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($notes as $note): ?>
                            <div class="border-l-4 border-blue-500 pl-4 py-2">
                                <div class="flex justify-between items-start">
                                    <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded"><?= ucfirst($note['tipo']) ?></span>
                                    <span class="text-xs text-gray-400"><?= date('d/m/Y H:i', strtotime($note['created_at'])) ?></span>
                                </div>
                                <p class="mt-2 text-gray-700"><?= nl2br(htmlspecialchars($note['nota'])) ?></p>
                                <?php if ($note['numero_poliza']): ?>
                                    <p class="text-xs text-gray-500 mt-1">Póliza: <?= htmlspecialchars($note['numero_poliza']) ?></p>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Editar Cliente -->
    <div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg w-full max-w-lg mx-4 max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b">
                <h2 class="text-xl font-bold">Editar Cliente</h2>
            </div>
            <form action="/admin/actions/save-client.php" method="POST">
                <input type="hidden" name="id" value="<?= $client_id ?>">
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre Completo *</label>
                        <input type="text" name="nombre_completo" value="<?= htmlspecialchars($client['nombre_completo']) ?>" class="w-full border rounded-lg px-3 py-2" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($client['email']) ?>" class="w-full border rounded-lg px-3 py-2" required>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                            <input type="tel" name="telefono" value="<?= htmlspecialchars($client['telefono'] ?? '') ?>" class="w-full border rounded-lg px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cédula</label>
                            <input type="text" name="cedula" value="<?= htmlspecialchars($client['cedula'] ?? '') ?>" class="w-full border rounded-lg px-3 py-2">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Nacimiento</label>
                        <input type="date" name="fecha_nacimiento" value="<?= $client['fecha_nacimiento'] ?? '' ?>" class="w-full border rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                        <textarea name="direccion" rows="2" class="w-full border rounded-lg px-3 py-2"><?= htmlspecialchars($client['direccion'] ?? '') ?></textarea>
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Provincia</label>
                            <input type="text" name="provincia" value="<?= htmlspecialchars($client['provincia'] ?? '') ?>" class="w-full border rounded-lg px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cantón</label>
                            <input type="text" name="canton" value="<?= htmlspecialchars($client['canton'] ?? '') ?>" class="w-full border rounded-lg px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Distrito</label>
                            <input type="text" name="distrito" value="<?= htmlspecialchars($client['distrito'] ?? '') ?>" class="w-full border rounded-lg px-3 py-2">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                        <select name="status" class="w-full border rounded-lg px-3 py-2">
                            <option value="active" <?= $client['status'] === 'active' ? 'selected' : '' ?>>Activo</option>
                            <option value="inactive" <?= $client['status'] === 'inactive' ? 'selected' : '' ?>>Inactivo</option>
                            <option value="suspended" <?= $client['status'] === 'suspended' ? 'selected' : '' ?>>Suspendido</option>
                        </select>
                    </div>
                </div>
                <div class="p-6 border-t flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('editModal').classList.add('hidden')" class="px-4 py-2 border rounded-lg hover:bg-gray-50">Cancelar</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Subir Documento -->
    <div id="uploadModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg w-full max-w-md mx-4">
            <div class="p-6 border-b">
                <h2 class="text-xl font-bold">Subir Documento</h2>
            </div>
            <form action="/admin/actions/save-document.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="client_id" value="<?= $client_id ?>">
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del Documento *</label>
                        <input type="text" name="nombre" class="w-full border rounded-lg px-3 py-2" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo *</label>
                        <select name="tipo_documento" class="w-full border rounded-lg px-3 py-2" required>
                            <option value="cedula">Cédula</option>
                            <option value="poliza">Póliza</option>
                            <option value="comprobante_pago">Comprobante de Pago</option>
                            <option value="contrato">Contrato</option>
                            <option value="factura">Factura</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Póliza Asociada (opcional)</label>
                        <select name="policy_id" class="w-full border rounded-lg px-3 py-2">
                            <option value="">-- Ninguna --</option>
                            <?php foreach ($policies as $pol): ?>
                                <option value="<?= $pol['id'] ?>"><?= htmlspecialchars($pol['numero_poliza']) ?> - <?= ucfirst($pol['tipo_seguro']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Archivo *</label>
                        <input type="file" name="archivo" class="w-full border rounded-lg px-3 py-2" required accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                        <p class="text-xs text-gray-500 mt-1">PDF, imágenes o documentos Word. Máx 10MB</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
                        <textarea name="notas" rows="2" class="w-full border rounded-lg px-3 py-2"></textarea>
                    </div>
                </div>
                <div class="p-6 border-t flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('uploadModal').classList.add('hidden')" class="px-4 py-2 border rounded-lg hover:bg-gray-50">Cancelar</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Subir</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Agregar Nota -->
    <div id="addNoteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg w-full max-w-md mx-4">
            <div class="p-6 border-b">
                <h2 class="text-xl font-bold">Nueva Nota</h2>
            </div>
            <form action="/admin/actions/save-note.php" method="POST">
                <input type="hidden" name="client_id" value="<?= $client_id ?>">
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Nota</label>
                        <select name="tipo" class="w-full border rounded-lg px-3 py-2">
                            <option value="general">General</option>
                            <option value="llamada">Llamada</option>
                            <option value="email">Email</option>
                            <option value="visita">Visita</option>
                            <option value="reclamo">Reclamo</option>
                            <option value="renovacion">Renovación</option>
                            <option value="pago">Pago</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Póliza Relacionada (opcional)</label>
                        <select name="policy_id" class="w-full border rounded-lg px-3 py-2">
                            <option value="">-- Ninguna --</option>
                            <?php foreach ($policies as $pol): ?>
                                <option value="<?= $pol['id'] ?>"><?= htmlspecialchars($pol['numero_poliza']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nota *</label>
                        <textarea name="nota" rows="4" class="w-full border rounded-lg px-3 py-2" required></textarea>
                    </div>
                </div>
                <div class="p-6 border-t flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('addNoteModal').classList.add('hidden')" class="px-4 py-2 border rounded-lg hover:bg-gray-50">Cancelar</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Guardar Nota</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Registrar Pago -->
    <div id="addPaymentModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg w-full max-w-md mx-4">
            <div class="p-6 border-b">
                <h2 class="text-xl font-bold">Registrar Pago</h2>
            </div>
            <form action="/admin/actions/save-payment.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="client_id" value="<?= $client_id ?>">
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Póliza *</label>
                        <select name="policy_id" class="w-full border rounded-lg px-3 py-2" required>
                            <option value="">-- Seleccionar --</option>
                            <?php foreach ($policies as $pol): ?>
                                <option value="<?= $pol['id'] ?>"><?= htmlspecialchars($pol['numero_poliza']) ?> - <?= ucfirst($pol['tipo_seguro']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Monto *</label>
                            <input type="number" name="monto" step="0.01" class="w-full border rounded-lg px-3 py-2" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Moneda</label>
                            <select name="moneda" class="w-full border rounded-lg px-3 py-2">
                                <option value="colones">Colones</option>
                                <option value="dolares">Dólares</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Pago</label>
                        <select name="tipo_pago" class="w-full border rounded-lg px-3 py-2">
                            <option value="prima_inicial">Prima Inicial</option>
                            <option value="cuota_mensual">Cuota Mensual</option>
                            <option value="cuota_trimestral">Cuota Trimestral</option>
                            <option value="cuota_anual">Cuota Anual</option>
                            <option value="renovacion">Renovación</option>
                            <option value="otros">Otros</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Vencimiento</label>
                            <input type="date" name="fecha_vencimiento" class="w-full border rounded-lg px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Pago</label>
                            <input type="date" name="fecha_pago" value="<?= date('Y-m-d') ?>" class="w-full border rounded-lg px-3 py-2">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Método de Pago</label>
                        <select name="metodo_pago" class="w-full border rounded-lg px-3 py-2">
                            <option value="efectivo">Efectivo</option>
                            <option value="transferencia">Transferencia</option>
                            <option value="tarjeta">Tarjeta</option>
                            <option value="sinpe">SINPE Móvil</option>
                            <option value="cheque">Cheque</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Referencia/Comprobante</label>
                        <input type="text" name="referencia_pago" class="w-full border rounded-lg px-3 py-2" placeholder="Número de referencia">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Comprobante (archivo)</label>
                        <input type="file" name="comprobante" class="w-full border rounded-lg px-3 py-2" accept=".pdf,.jpg,.jpeg,.png">
                    </div>
                </div>
                <div class="p-6 border-t flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('addPaymentModal').classList.add('hidden')" class="px-4 py-2 border rounded-lg hover:bg-gray-50">Cancelar</button>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Registrar Pago</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Tab switching
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const tab = this.dataset.tab;

                // Update buttons
                document.querySelectorAll('.tab-btn').forEach(b => {
                    b.classList.remove('active', 'text-blue-600', 'border-b-2', 'border-blue-600');
                    b.classList.add('text-gray-500');
                });
                this.classList.add('active', 'text-blue-600', 'border-b-2', 'border-blue-600');
                this.classList.remove('text-gray-500');

                // Update content
                document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));
                document.getElementById('tab-' + tab).classList.remove('hidden');
            });
        });

        // Mark payment as paid
        function markAsPaid(paymentId) {
            if (confirm('¿Marcar este pago como pagado?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/admin/actions/mark-payment-paid.php';
                form.innerHTML = `<input type="hidden" name="payment_id" value="${paymentId}"><input type="hidden" name="client_id" value="<?= $client_id ?>">`;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // View policy details
        function viewPolicy(policyId) {
            // TODO: Implement policy detail modal or redirect
            alert('Ver póliza #' + policyId);
        }

        // Close modals on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.fixed').forEach(m => m.classList.add('hidden'));
            }
        });

        // Close modals on backdrop click
        document.querySelectorAll('.fixed').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.add('hidden');
                }
            });
        });
    </script>
</body>
</html>
