<?php
// client/dashboard.php - Main client dashboard
require_once __DIR__ . '/includes/client_auth.php';
require_client_login();

require_once __DIR__ . '/../includes/db.php';

$clientId = get_current_client_id();
$clientData = get_client_data();

// Calculate dashboard summary directly
$summary = [
    'polizas_vigentes' => 0,
    'polizas_por_vencer' => 0,
    'pagos_pendientes' => 0,
    'total_cotizaciones' => 0
];

try {
    // Count active policies
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM policies WHERE client_id = ? AND status = 'vigente'");
    $stmt->execute([$clientId]);
    $summary['polizas_vigentes'] = $stmt->fetch()['total'] ?? 0;

    // Count expiring soon
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM policies WHERE client_id = ? AND status = 'por_vencer'");
    $stmt->execute([$clientId]);
    $summary['polizas_por_vencer'] = $stmt->fetch()['total'] ?? 0;

    // Count pending payments
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM payments WHERE client_id = ? AND status IN ('pendiente', 'vencido')");
    $stmt->execute([$clientId]);
    $summary['pagos_pendientes'] = $stmt->fetch()['total'] ?? 0;

    // Count quotes
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM quotes WHERE client_id = ?");
    $stmt->execute([$clientId]);
    $summary['total_cotizaciones'] = $stmt->fetch()['total'] ?? 0;
} catch (Exception $e) {
    // Tables might not exist yet, use defaults
}

// Initialize empty arrays
$activePolicies = [];
$recentQuotes = [];
$pendingPayments = [];
$notifications = [];

try {
    // Get active policies
    $stmt = $pdo->prepare("
        SELECT * FROM policies
        WHERE client_id = ? AND status IN ('vigente', 'por_vencer')
        ORDER BY fecha_fin_vigencia ASC
        LIMIT 5
    ");
    $stmt->execute([$clientId]);
    $activePolicies = $stmt->fetchAll();
} catch (Exception $e) {}

try {
    // Get recent quotes
    $stmt = $pdo->prepare("
        SELECT * FROM quotes
        WHERE client_id = ? AND status NOT IN ('vencida', 'convertida_poliza')
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$clientId]);
    $recentQuotes = $stmt->fetchAll();
} catch (Exception $e) {}

try {
    // Get pending payments
    $stmt = $pdo->prepare("
        SELECT p.*, pol.numero_poliza, pol.tipo_seguro
        FROM payments p
        INNER JOIN policies pol ON p.policy_id = pol.id
        WHERE pol.client_id = ? AND p.status IN ('pendiente', 'vencido')
        ORDER BY p.fecha_vencimiento ASC
        LIMIT 5
    ");
    $stmt->execute([$clientId]);
    $pendingPayments = $stmt->fetchAll();
} catch (Exception $e) {}

try {
    // Get unread notifications
    $stmt = $pdo->prepare("
        SELECT * FROM client_notifications
        WHERE client_id = ? AND leida = FALSE
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$clientId]);
    $notifications = $stmt->fetchAll();
} catch (Exception $e) {}

$notificationCount = count($notifications);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Dashboard - AseguraloCR</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        * { font-family: 'Inter', sans-serif; }
        .gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .card-hover { transition: all 0.3s ease; }
        .card-hover:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <?php include __DIR__ . '/includes/nav.php'; ?>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8">
        <!-- Welcome Header -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-8 gradient-bg text-white">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div class="flex items-center space-x-4">
                    <?php
                    $avatarUrl = $clientData['avatar_url'] ?? $clientData['google_avatar'] ?? $_SESSION['client_avatar'] ?? '';
                    $initials = strtoupper(substr($clientData['nombre_completo'] ?? 'C', 0, 1));
                    ?>
                    <?php if (!empty($avatarUrl)): ?>
                        <img src="<?= htmlspecialchars($avatarUrl) ?>"
                             alt=""
                             class="w-16 h-16 rounded-full border-4 border-white/30 object-cover"
                             referrerpolicy="no-referrer"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="w-16 h-16 rounded-full border-4 border-white/30 bg-white/20 items-center justify-center text-2xl font-bold" style="display: none;">
                            <?= $initials ?>
                        </div>
                    <?php else: ?>
                        <div class="w-16 h-16 rounded-full border-4 border-white/30 bg-white/20 flex items-center justify-center text-2xl font-bold">
                            <?= $initials ?>
                        </div>
                    <?php endif; ?>
                    <div>
                        <h1 class="text-2xl font-bold">¡Hola, <?= htmlspecialchars($clientData['nombre_completo'] ?? 'Cliente') ?>!</h1>
                        <p class="opacity-90">Bienvenido a tu portal de seguros</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-sm opacity-90">Última visita</p>
                    <p class="font-semibold"><?= $clientData['last_login'] ? date('d/m/Y H:i', strtotime($clientData['last_login'])) : 'Primera vez' ?></p>
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Active Policies -->
            <div class="bg-white rounded-xl shadow-md p-6 card-hover">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-shield-alt text-green-600 text-xl"></i>
                    </div>
                    <span class="text-3xl font-bold text-gray-800"><?= $summary['polizas_vigentes'] ?? 0 ?></span>
                </div>
                <h3 class="text-gray-600 font-semibold">Pólizas Activas</h3>
                <a href="/client/policies.php" class="text-sm text-purple-600 hover:underline mt-2 inline-block">Ver todas →</a>
            </div>

            <!-- Expiring Soon -->
            <div class="bg-white rounded-xl shadow-md p-6 card-hover">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-clock text-yellow-600 text-xl"></i>
                    </div>
                    <span class="text-3xl font-bold text-gray-800"><?= $summary['polizas_por_vencer'] ?? 0 ?></span>
                </div>
                <h3 class="text-gray-600 font-semibold">Por Vencer</h3>
                <p class="text-sm text-gray-500 mt-1">Próximos 30 días</p>
            </div>

            <!-- Pending Payments -->
            <div class="bg-white rounded-xl shadow-md p-6 card-hover">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-exclamation-circle text-red-600 text-xl"></i>
                    </div>
                    <span class="text-3xl font-bold text-gray-800"><?= $summary['pagos_pendientes'] ?? 0 ?></span>
                </div>
                <h3 class="text-gray-600 font-semibold">Pagos Pendientes</h3>
                <a href="/client/payments.php" class="text-sm text-purple-600 hover:underline mt-2 inline-block">Ver pagos →</a>
            </div>

            <!-- Total Quotes -->
            <div class="bg-white rounded-xl shadow-md p-6 card-hover">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-file-invoice text-blue-600 text-xl"></i>
                    </div>
                    <span class="text-3xl font-bold text-gray-800"><?= $summary['total_cotizaciones'] ?? 0 ?></span>
                </div>
                <h3 class="text-gray-600 font-semibold">Cotizaciones</h3>
                <a href="/client/quotes.php" class="text-sm text-purple-600 hover:underline mt-2 inline-block">Ver cotizaciones →</a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Column -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Active Policies -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-2xl font-bold text-gray-800">
                            <i class="fas fa-shield-alt text-purple-600 mr-2"></i>
                            Mis Pólizas Activas
                        </h2>
                        <a href="/client/policies.php" class="text-purple-600 hover:underline text-sm">Ver todas</a>
                    </div>

                    <?php if (empty($activePolicies)): ?>
                        <div class="text-center py-12">
                            <i class="fas fa-inbox text-gray-300 text-5xl mb-4"></i>
                            <p class="text-gray-500">No tienes pólizas activas</p>
                            <a href="/hogar-comprensivo.php" class="inline-block mt-4 bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700 transition">
                                Solicitar Cotización
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($activePolicies as $policy): ?>
                                <div class="border-2 border-gray-100 rounded-xl p-4 hover:border-purple-200 transition">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-3 mb-2">
                                                <span class="px-3 py-1 bg-green-100 text-green-700 text-xs font-semibold rounded-full">
                                                    <?= ucfirst($policy['status']) ?>
                                                </span>
                                                <span class="text-gray-500 text-sm">
                                                    <?= ucfirst($policy['tipo_seguro']) ?>
                                                </span>
                                            </div>
                                            <h3 class="font-bold text-gray-800 text-lg mb-1">
                                                Póliza #<?= htmlspecialchars($policy['numero_poliza']) ?>
                                            </h3>
                                            <p class="text-gray-600 text-sm mb-2">
                                                <?= htmlspecialchars($policy['detalles_bien_asegurado'] ?? 'Seguro de ' . $policy['tipo_seguro']) ?>
                                            </p>
                                            <div class="flex items-center gap-4 text-sm text-gray-500">
                                                <span><i class="far fa-calendar mr-1"></i> Vence: <?= date('d/m/Y', strtotime($policy['fecha_fin_vigencia'])) ?></span>
                                                <span><i class="fas fa-dollar-sign mr-1"></i> Prima: <?= number_format($policy['prima_anual'] ?? 0, 2) ?></span>
                                            </div>
                                        </div>
                                        <a href="/client/policy-detail.php?id=<?= $policy['id'] ?>"
                                           class="text-purple-600 hover:text-purple-700">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Quotes -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-2xl font-bold text-gray-800">
                            <i class="fas fa-file-invoice text-blue-600 mr-2"></i>
                            Cotizaciones Recientes
                        </h2>
                        <a href="/client/quotes.php" class="text-purple-600 hover:underline text-sm">Ver todas</a>
                    </div>

                    <?php if (empty($recentQuotes)): ?>
                        <div class="text-center py-8">
                            <i class="fas fa-inbox text-gray-300 text-4xl mb-3"></i>
                            <p class="text-gray-500">No tienes cotizaciones</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($recentQuotes as $quote): ?>
                                <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                                    <div>
                                        <h4 class="font-semibold text-gray-800">Cotización #<?= htmlspecialchars($quote['numero_cotizacion']) ?></h4>
                                        <p class="text-sm text-gray-600"><?= ucfirst($quote['tipo_seguro']) ?> - <?= htmlspecialchars($quote['status']) ?></p>
                                        <p class="text-xs text-gray-500 mt-1"><?= date('d/m/Y', strtotime($quote['fecha_cotizacion'])) ?></p>
                                    </div>
                                    <a href="/client/quote-detail.php?id=<?= $quote['id'] ?>"
                                       class="text-purple-600 hover:text-purple-700">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Pending Payments -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-credit-card text-red-600 mr-2"></i>
                        Pagos Pendientes
                    </h3>

                    <?php if (empty($pendingPayments)): ?>
                        <div class="text-center py-6">
                            <i class="fas fa-check-circle text-green-500 text-4xl mb-3"></i>
                            <p class="text-gray-600 text-sm">¡Estás al día!</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($pendingPayments as $payment): ?>
                                <div class="border-l-4 <?= $payment['status'] === 'vencido' ? 'border-red-500 bg-red-50' : 'border-yellow-500 bg-yellow-50' ?> p-3 rounded">
                                    <p class="font-semibold text-gray-800 text-sm">Póliza #<?= htmlspecialchars($payment['numero_poliza']) ?></p>
                                    <p class="text-lg font-bold text-gray-900">₡<?= number_format($payment['monto'], 2) ?></p>
                                    <p class="text-xs text-gray-600 mt-1">
                                        Vence: <?= date('d/m/Y', strtotime($payment['fecha_vencimiento'])) ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                            <a href="/client/payments.php"
                               class="block text-center bg-purple-600 text-white py-2 rounded-lg hover:bg-purple-700 transition text-sm font-semibold">
                                Ver todos los pagos
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Notifications -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-bell text-yellow-600 mr-2"></i>
                        Notificaciones
                        <?php if ($notificationCount > 0): ?>
                            <span class="text-xs bg-red-500 text-white px-2 py-1 rounded-full ml-2"><?= $notificationCount ?></span>
                        <?php endif; ?>
                    </h3>

                    <?php if (empty($notifications)): ?>
                        <p class="text-gray-500 text-sm text-center py-4">No hay notificaciones nuevas</p>
                    <?php else: ?>
                        <div class="space-y-2">
                            <?php foreach ($notifications as $notif): ?>
                                <div class="p-3 bg-blue-50 border-l-4 border-blue-500 rounded text-sm">
                                    <p class="font-semibold text-gray-800"><?= htmlspecialchars($notif['titulo']) ?></p>
                                    <p class="text-gray-600 text-xs mt-1"><?= htmlspecialchars($notif['mensaje']) ?></p>
                                    <p class="text-gray-400 text-xs mt-2"><?= date('d/m/Y H:i', strtotime($notif['created_at'])) ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Quick Actions -->
                <div class="bg-gradient-to-br from-purple-600 to-purple-800 rounded-2xl shadow-lg p-6 text-white">
                    <h3 class="text-xl font-bold mb-4">Acciones Rápidas</h3>
                    <div class="space-y-3">
                        <a href="/hogar-comprensivo.php"
                           class="block bg-white/20 hover:bg-white/30 transition p-3 rounded-lg">
                            <i class="fas fa-plus-circle mr-2"></i>Nueva Cotización
                        </a>
                        <a href="/client/profile.php"
                           class="block bg-white/20 hover:bg-white/30 transition p-3 rounded-lg">
                            <i class="fas fa-user-edit mr-2"></i>Actualizar Perfil
                        </a>
                        <a href="/contacto.php"
                           class="block bg-white/20 hover:bg-white/30 transition p-3 rounded-lg">
                            <i class="fas fa-headset mr-2"></i>Soporte
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
