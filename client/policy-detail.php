<?php
// client/policy-detail.php - Detalle de póliza para el cliente
require_once __DIR__ . '/includes/client_auth.php';
require_client_login();

require_once __DIR__ . '/../includes/db.php';

$clientId = get_current_client_id();
$policyId = intval($_GET['id'] ?? 0);

if (!$policyId) {
    header('Location: /client/policies.php');
    exit;
}

// Obtener póliza (solo si pertenece al cliente)
$stmt = $pdo->prepare("SELECT * FROM policies WHERE id = ? AND client_id = ?");
$stmt->execute([$policyId, $clientId]);
$policy = $stmt->fetch();

if (!$policy) {
    header('Location: /client/policies.php');
    exit;
}

// Obtener pagos de la póliza
$paymentsStmt = $pdo->prepare("SELECT * FROM payments WHERE policy_id = ? ORDER BY fecha_vencimiento ASC");
$paymentsStmt->execute([$policyId]);
$payments = $paymentsStmt->fetchAll();

// Obtener documentos de la póliza
$docsStmt = $pdo->prepare("SELECT * FROM client_documents WHERE policy_id = ? AND visible_cliente = 1 ORDER BY created_at DESC");
$docsStmt->execute([$policyId]);
$documents = $docsStmt->fetchAll();

// Calcular totales de pagos
$totalPagado = 0;
$totalPendiente = 0;
foreach ($payments as $p) {
    if ($p['status'] === 'pagado') {
        $totalPagado += $p['monto'];
    } else {
        $totalPendiente += $p['monto'];
    }
}

// Configuración de tipos
$tipoConfig = [
    'hogar' => ['icon' => 'fa-house', 'color' => 'purple'],
    'auto' => ['icon' => 'fa-car', 'color' => 'green'],
    'vida' => ['icon' => 'fa-heart', 'color' => 'red'],
    'salud' => ['icon' => 'fa-hospital', 'color' => 'teal'],
    'viaje' => ['icon' => 'fa-plane', 'color' => 'blue'],
    'otros' => ['icon' => 'fa-shield-alt', 'color' => 'gray']
];

$tipo = $policy['tipo_seguro'] ?? 'otros';
$config = $tipoConfig[$tipo] ?? $tipoConfig['otros'];

$statusClass = match($policy['status']) {
    'vigente', 'activa' => 'bg-green-100 text-green-700 border-green-300',
    'por_vencer' => 'bg-yellow-100 text-yellow-700 border-yellow-300',
    'vencida' => 'bg-red-100 text-red-700 border-red-300',
    default => 'bg-gray-100 text-gray-700 border-gray-300'
};

$monedaSymbol = ($policy['moneda'] ?? 'CRC') === 'USD' ? '$' : '₡';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Póliza #<?= htmlspecialchars($policy['numero_poliza']) ?> - AseguraloCR</title>
    <link rel="icon" type="image/svg+xml" href="/imagenes/favicon.svg">
    <link rel="icon" type="image/png" href="/imagenes/favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        * { font-family: 'Inter', sans-serif; }
        .gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    </style>
</head>
<body class="bg-gray-50">
    <?php include __DIR__ . '/includes/nav.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <!-- Breadcrumb -->
        <div class="mb-6">
            <a href="/client/policies.php" class="text-purple-600 hover:text-purple-700">
                <i class="fas fa-arrow-left mr-2"></i>Volver a Mis Pólizas
            </a>
        </div>

        <!-- Header -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
            <div class="flex items-start gap-4 flex-wrap">
                <div class="w-16 h-16 bg-<?= $config['color'] ?>-100 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i class="fas <?= $config['icon'] ?> text-<?= $config['color'] ?>-600 text-3xl"></i>
                </div>
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2 flex-wrap">
                        <h1 class="text-2xl font-bold text-gray-800">
                            Póliza #<?= htmlspecialchars($policy['numero_poliza']) ?>
                        </h1>
                        <span class="px-3 py-1 rounded-full text-sm font-semibold border <?= $statusClass ?>">
                            <?= ucfirst(str_replace('_', ' ', $policy['status'] ?? 'pendiente')) ?>
                        </span>
                    </div>
                    <p class="text-gray-600">
                        <?= htmlspecialchars($policy['detalles_bien_asegurado'] ?? 'Seguro de ' . ucfirst($tipo)) ?>
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-500">Prima Anual</p>
                    <p class="text-2xl font-bold text-purple-600">
                        <?= $monedaSymbol ?><?= number_format($policy['prima_anual'] ?? 0, 2) ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="grid md:grid-cols-3 gap-6 mb-6">
            <!-- Información General -->
            <div class="bg-white rounded-xl shadow p-6">
                <h3 class="font-semibold text-gray-800 mb-4">
                    <i class="fas fa-info-circle text-purple-600 mr-2"></i>Información General
                </h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Aseguradora:</span>
                        <span class="font-medium"><?= htmlspecialchars($policy['aseguradora'] ?? 'INS') ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Tipo:</span>
                        <span class="font-medium"><?= ucfirst($tipo) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Moneda:</span>
                        <span class="font-medium"><?= $policy['moneda'] ?? 'CRC' ?></span>
                    </div>
                    <?php if ($policy['monto_asegurado']): ?>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Monto Asegurado:</span>
                        <span class="font-medium"><?= $monedaSymbol ?><?= number_format($policy['monto_asegurado'], 2) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Vigencia -->
            <div class="bg-white rounded-xl shadow p-6">
                <h3 class="font-semibold text-gray-800 mb-4">
                    <i class="fas fa-calendar text-purple-600 mr-2"></i>Vigencia
                </h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Emisión:</span>
                        <span class="font-medium"><?= $policy['fecha_emision'] ? date('d/m/Y', strtotime($policy['fecha_emision'])) : '-' ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Inicio:</span>
                        <span class="font-medium"><?= $policy['fecha_inicio_vigencia'] ? date('d/m/Y', strtotime($policy['fecha_inicio_vigencia'])) : '-' ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Fin:</span>
                        <span class="font-medium"><?= $policy['fecha_fin_vigencia'] ? date('d/m/Y', strtotime($policy['fecha_fin_vigencia'])) : '-' ?></span>
                    </div>
                </div>
            </div>

            <!-- Primas -->
            <div class="bg-white rounded-xl shadow p-6">
                <h3 class="font-semibold text-gray-800 mb-4">
                    <i class="fas fa-coins text-purple-600 mr-2"></i>Opciones de Pago
                </h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Anual:</span>
                        <span class="font-medium"><?= $monedaSymbol ?><?= number_format($policy['prima_anual'] ?? 0, 2) ?></span>
                    </div>
                    <?php if ($policy['prima_semestral']): ?>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Semestral:</span>
                        <span class="font-medium"><?= $monedaSymbol ?><?= number_format($policy['prima_semestral'], 2) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($policy['prima_trimestral']): ?>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Trimestral:</span>
                        <span class="font-medium"><?= $monedaSymbol ?><?= number_format($policy['prima_trimestral'], 2) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($policy['prima_mensual']): ?>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Mensual:</span>
                        <span class="font-medium"><?= $monedaSymbol ?><?= number_format($policy['prima_mensual'], 2) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Plan de Pagos -->
        <?php if (!empty($payments)): ?>
        <div class="bg-white rounded-xl shadow p-6 mb-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-semibold text-gray-800">
                    <i class="fas fa-credit-card text-purple-600 mr-2"></i>Plan de Pagos (<?= count($payments) ?> cuotas)
                </h3>
                <div class="flex gap-4 text-sm">
                    <span class="text-green-600"><i class="fas fa-check-circle mr-1"></i>Pagado: <?= $monedaSymbol ?><?= number_format($totalPagado, 2) ?></span>
                    <span class="text-orange-600"><i class="fas fa-clock mr-1"></i>Pendiente: <?= $monedaSymbol ?><?= number_format($totalPendiente, 2) ?></span>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left py-3 px-4 font-semibold text-gray-600">#</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-600">Tipo</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-600">Monto</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-600">Vencimiento</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-600">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($payments as $i => $payment): ?>
                        <?php
                            $isOverdue = $payment['status'] === 'pendiente' && strtotime($payment['fecha_vencimiento']) < time();
                            $rowClass = $isOverdue ? 'bg-red-50' : '';
                        ?>
                        <tr class="<?= $rowClass ?>">
                            <td class="py-3 px-4"><?= $i + 1 ?></td>
                            <td class="py-3 px-4"><?= ucfirst(str_replace('_', ' ', $payment['tipo_pago'] ?? '-')) ?></td>
                            <td class="py-3 px-4 font-medium"><?= $monedaSymbol ?><?= number_format($payment['monto'] ?? 0, 2) ?></td>
                            <td class="py-3 px-4"><?= date('d/m/Y', strtotime($payment['fecha_vencimiento'])) ?></td>
                            <td class="py-3 px-4">
                                <?php if ($payment['status'] === 'pagado'): ?>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                        <i class="fas fa-check mr-1"></i>Pagado
                                    </span>
                                <?php elseif ($isOverdue): ?>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                        <i class="fas fa-exclamation-triangle mr-1"></i>Vencido
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">
                                        <i class="fas fa-clock mr-1"></i>Pendiente
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Documentos -->
        <div class="bg-white rounded-xl shadow p-6 mb-6">
            <h3 class="font-semibold text-gray-800 mb-4">
                <i class="fas fa-folder text-purple-600 mr-2"></i>Documentos
            </h3>
            <?php if (empty($documents) && empty($policy['archivo_poliza_url'])): ?>
                <p class="text-gray-500 text-center py-4">No hay documentos disponibles</p>
            <?php else: ?>
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php if (!empty($policy['archivo_poliza_url'])): ?>
                    <a href="<?= htmlspecialchars($policy['archivo_poliza_url']) ?>" target="_blank"
                       class="flex items-center gap-3 p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                        <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-file-pdf text-red-600"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-800">Póliza Original</p>
                            <p class="text-xs text-gray-500">PDF</p>
                        </div>
                        <i class="fas fa-download text-gray-400 ml-auto"></i>
                    </a>
                    <?php endif; ?>

                    <?php foreach ($documents as $doc): ?>
                    <a href="/client/download.php?id=<?= $doc['id'] ?>"
                       class="flex items-center gap-3 p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-file text-blue-600"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-800"><?= htmlspecialchars($doc['nombre'] ?? $doc['nombre_archivo'] ?? 'Documento') ?></p>
                            <p class="text-xs text-gray-500"><?= ucfirst($doc['tipo'] ?? 'Otro') ?></p>
                        </div>
                        <i class="fas fa-download text-gray-400 ml-auto"></i>
                    </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Contacto -->
        <div class="bg-purple-50 rounded-xl p-6 text-center">
            <h3 class="font-semibold text-purple-800 mb-2">
                <i class="fas fa-headset mr-2"></i>¿Necesitas ayuda con esta póliza?
            </h3>
            <p class="text-purple-600 mb-4">Estamos aquí para ayudarte con cualquier consulta</p>
            <a href="https://wa.me/50688888888" target="_blank"
               class="inline-block bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded-lg font-semibold transition">
                <i class="fab fa-whatsapp mr-2"></i>Contactar por WhatsApp
            </a>
        </div>
    </div>
</body>
</html>
