<?php
// client/policies.php - Listado de pólizas del cliente
require_once __DIR__ . '/includes/client_auth.php';
require_client_login();

require_once __DIR__ . '/../includes/db.php';

$clientId = get_current_client_id();
$clientData = get_client_data();

// Filtros
$statusFilter = $_GET['status'] ?? 'all';
$tipoFilter = $_GET['tipo'] ?? 'all';

// Obtener pólizas
$sql = "SELECT * FROM policies WHERE client_id = ?";
$params = [$clientId];

if ($statusFilter !== 'all') {
    $sql .= " AND status = ?";
    $params[] = $statusFilter;
}

if ($tipoFilter !== 'all') {
    $sql .= " AND tipo_seguro = ?";
    $params[] = $tipoFilter;
}

$sql .= " ORDER BY fecha_fin_vigencia ASC";

$policies = [];
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $policies = $stmt->fetchAll();
} catch (Exception $e) {
    $policies = [];
}

// Contar por estado
$counts = ['vigente' => 0, 'por_vencer' => 0, 'vencida' => 0, 'total' => count($policies)];
foreach ($policies as $p) {
    if (isset($counts[$p['status']])) {
        $counts[$p['status']]++;
    }
}

// Configuración de tipos
$tipoConfig = [
    'hogar' => ['icon' => 'fa-house', 'color' => 'blue'],
    'auto' => ['icon' => 'fa-car', 'color' => 'green'],
    'vida' => ['icon' => 'fa-heart', 'color' => 'red'],
    'salud' => ['icon' => 'fa-hospital', 'color' => 'teal'],
    'viaje' => ['icon' => 'fa-plane', 'color' => 'purple'],
    'otros' => ['icon' => 'fa-shield-alt', 'color' => 'gray']
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Pólizas - AseguraloCR</title>
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
        <!-- Header -->
        <div class="flex justify-between items-start mb-8 flex-wrap gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">
                    <i class="fas fa-shield-alt text-purple-600 mr-3"></i>Mis Pólizas
                </h1>
                <p class="text-gray-600">Consulta el estado de todas tus pólizas de seguro</p>
            </div>
            <a href="/hogar-comprensivo.php"
               class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg font-semibold transition">
                <i class="fas fa-plus mr-2"></i>Nueva Cotización
            </a>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-xl shadow p-4 text-center">
                <p class="text-3xl font-bold text-gray-800"><?= $counts['total'] ?></p>
                <p class="text-gray-500 text-sm">Total Pólizas</p>
            </div>
            <div class="bg-white rounded-xl shadow p-4 text-center border-b-4 border-green-500">
                <p class="text-3xl font-bold text-green-600"><?= $counts['vigente'] ?></p>
                <p class="text-gray-500 text-sm">Vigentes</p>
            </div>
            <div class="bg-white rounded-xl shadow p-4 text-center border-b-4 border-yellow-500">
                <p class="text-3xl font-bold text-yellow-600"><?= $counts['por_vencer'] ?></p>
                <p class="text-gray-500 text-sm">Por Vencer</p>
            </div>
            <div class="bg-white rounded-xl shadow p-4 text-center border-b-4 border-red-500">
                <p class="text-3xl font-bold text-red-600"><?= $counts['vencida'] ?></p>
                <p class="text-gray-500 text-sm">Vencidas</p>
            </div>
        </div>

        <!-- Filtros -->
        <div class="bg-white rounded-xl shadow p-4 mb-6">
            <form method="GET" class="flex flex-wrap gap-4 items-center">
                <div class="flex-1 min-w-[150px]">
                    <select name="status" onchange="this.form.submit()"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2">
                        <option value="all">Todos los estados</option>
                        <option value="vigente" <?= $statusFilter === 'vigente' ? 'selected' : '' ?>>Vigentes</option>
                        <option value="por_vencer" <?= $statusFilter === 'por_vencer' ? 'selected' : '' ?>>Por Vencer</option>
                        <option value="vencida" <?= $statusFilter === 'vencida' ? 'selected' : '' ?>>Vencidas</option>
                    </select>
                </div>
                <div class="flex-1 min-w-[150px]">
                    <select name="tipo" onchange="this.form.submit()"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2">
                        <option value="all">Todos los tipos</option>
                        <option value="hogar" <?= $tipoFilter === 'hogar' ? 'selected' : '' ?>>Hogar</option>
                        <option value="auto" <?= $tipoFilter === 'auto' ? 'selected' : '' ?>>Automóvil</option>
                        <option value="vida" <?= $tipoFilter === 'vida' ? 'selected' : '' ?>>Vida</option>
                        <option value="salud" <?= $tipoFilter === 'salud' ? 'selected' : '' ?>>Salud</option>
                    </select>
                </div>
            </form>
        </div>

        <!-- Lista de Pólizas -->
        <?php if (empty($policies)): ?>
            <div class="bg-white rounded-2xl shadow-lg p-12 text-center">
                <i class="fas fa-shield-alt text-gray-300 text-6xl mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-700 mb-2">No tienes pólizas</h3>
                <p class="text-gray-500 mb-6">Solicita una cotización para comenzar a proteger lo que más te importa</p>
                <a href="/hogar-comprensivo.php"
                   class="inline-block bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg font-semibold transition">
                    Solicitar Cotización
                </a>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($policies as $policy): ?>
                    <?php
                    $tipo = $policy['tipo_seguro'];
                    $config = $tipoConfig[$tipo] ?? $tipoConfig['otros'];
                    $statusClass = match($policy['status']) {
                        'vigente' => 'bg-green-100 text-green-700 border-green-300',
                        'por_vencer' => 'bg-yellow-100 text-yellow-700 border-yellow-300',
                        'vencida' => 'bg-red-100 text-red-700 border-red-300',
                        default => 'bg-gray-100 text-gray-700 border-gray-300'
                    };
                    $daysToExpire = (strtotime($policy['fecha_fin_vigencia']) - time()) / 86400;
                    ?>
                    <div class="bg-white rounded-xl shadow-md hover:shadow-lg transition p-6">
                        <div class="flex items-start gap-4">
                            <!-- Icono -->
                            <div class="w-14 h-14 bg-<?= $config['color'] ?>-100 rounded-xl flex items-center justify-center flex-shrink-0">
                                <i class="fas <?= $config['icon'] ?> text-<?= $config['color'] ?>-600 text-2xl"></i>
                            </div>

                            <!-- Info -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-3 mb-2 flex-wrap">
                                    <h3 class="text-lg font-bold text-gray-800">
                                        Póliza #<?= htmlspecialchars($policy['numero_poliza']) ?>
                                    </h3>
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold border <?= $statusClass ?>">
                                        <?= ucfirst(str_replace('_', ' ', $policy['status'])) ?>
                                    </span>
                                </div>

                                <p class="text-gray-600 mb-2">
                                    <?= htmlspecialchars($policy['detalles_bien_asegurado'] ?? 'Seguro de ' . ucfirst($tipo)) ?>
                                </p>

                                <div class="flex flex-wrap gap-4 text-sm text-gray-500">
                                    <span><i class="fas fa-building mr-1"></i><?= htmlspecialchars($policy['aseguradora']) ?></span>
                                    <span><i class="far fa-calendar mr-1"></i>Vigencia: <?= date('d/m/Y', strtotime($policy['fecha_inicio_vigencia'])) ?> - <?= date('d/m/Y', strtotime($policy['fecha_fin_vigencia'])) ?></span>
                                    <?php if ($policy['prima_anual']): ?>
                                        <span><i class="fas fa-dollar-sign mr-1"></i>Prima: <?= $policy['moneda'] === 'USD' ? '$' : '₡' ?><?= number_format($policy['prima_anual'], 2) ?>/año</span>
                                    <?php endif; ?>
                                </div>

                                <?php if ($daysToExpire <= 30 && $daysToExpire > 0): ?>
                                    <div class="mt-3 bg-yellow-50 border border-yellow-200 rounded-lg p-2 text-sm text-yellow-700">
                                        <i class="fas fa-exclamation-triangle mr-2"></i>
                                        Esta póliza vence en <?= ceil($daysToExpire) ?> días. Contáctanos para renovar.
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Acciones -->
                            <div class="flex flex-col gap-2">
                                <a href="/client/policy-detail.php?id=<?= $policy['id'] ?>"
                                   class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition text-center">
                                    <i class="fas fa-eye mr-1"></i>Ver Detalle
                                </a>
                                <a href="/client/documents.php?policy=<?= $policy['id'] ?>"
                                   class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium transition text-center">
                                    <i class="fas fa-folder mr-1"></i>Documentos
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
