<?php
// client/quotes.php - Cotizaciones del cliente
require_once __DIR__ . '/includes/client_auth.php';
require_client_login();

require_once __DIR__ . '/../includes/db.php';

$clientId = get_current_client_id();
$clientData = get_client_data();

// Filtros
$statusFilter = $_GET['status'] ?? 'all';

// Obtener cotizaciones
$sql = "SELECT * FROM quotes WHERE client_id = ?";
$params = [$clientId];

if ($statusFilter !== 'all') {
    $sql .= " AND status = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY created_at DESC";

$quotes = [];
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $quotes = $stmt->fetchAll();
} catch (Exception $e) {
    $quotes = [];
}

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
    <title>Mis Cotizaciones - AseguraloCR</title>
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
                    <i class="fas fa-file-invoice text-purple-600 mr-3"></i>Mis Cotizaciones
                </h1>
                <p class="text-gray-600">Historial de cotizaciones solicitadas</p>
            </div>
            <a href="/hogar-comprensivo.php"
               class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg font-semibold transition">
                <i class="fas fa-plus mr-2"></i>Nueva Cotización
            </a>
        </div>

        <!-- Filtros -->
        <div class="bg-white rounded-xl shadow p-4 mb-6">
            <form method="GET" class="flex flex-wrap gap-4 items-center">
                <select name="status" onchange="this.form.submit()"
                        class="border border-gray-300 rounded-lg px-4 py-2">
                    <option value="all">Todos los estados</option>
                    <option value="enviada" <?= $statusFilter === 'enviada' ? 'selected' : '' ?>>Enviadas</option>
                    <option value="aceptada" <?= $statusFilter === 'aceptada' ? 'selected' : '' ?>>Aceptadas</option>
                    <option value="convertida_poliza" <?= $statusFilter === 'convertida_poliza' ? 'selected' : '' ?>>Convertidas a Póliza</option>
                    <option value="vencida" <?= $statusFilter === 'vencida' ? 'selected' : '' ?>>Vencidas</option>
                </select>
            </form>
        </div>

        <!-- Lista de Cotizaciones -->
        <?php if (empty($quotes)): ?>
            <div class="bg-white rounded-2xl shadow-lg p-12 text-center">
                <i class="fas fa-file-invoice text-gray-300 text-6xl mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-700 mb-2">No tienes cotizaciones</h3>
                <p class="text-gray-500 mb-6">Solicita tu primera cotización de seguro</p>
                <a href="/hogar-comprensivo.php"
                   class="inline-block bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg font-semibold transition">
                    Solicitar Cotización
                </a>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($quotes as $quote): ?>
                    <?php
                    $tipo = $quote['tipo_seguro'];
                    $config = $tipoConfig[$tipo] ?? $tipoConfig['otros'];
                    $statusConfig = match($quote['status']) {
                        'enviada' => ['class' => 'bg-blue-100 text-blue-700', 'icon' => 'fa-paper-plane'],
                        'aceptada' => ['class' => 'bg-green-100 text-green-700', 'icon' => 'fa-check'],
                        'convertida_poliza' => ['class' => 'bg-purple-100 text-purple-700', 'icon' => 'fa-file-contract'],
                        'rechazada' => ['class' => 'bg-red-100 text-red-700', 'icon' => 'fa-times'],
                        'vencida' => ['class' => 'bg-gray-100 text-gray-700', 'icon' => 'fa-clock'],
                        default => ['class' => 'bg-gray-100 text-gray-700', 'icon' => 'fa-file']
                    };
                    ?>
                    <div class="bg-white rounded-xl shadow-md hover:shadow-lg transition p-6">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 bg-<?= $config['color'] ?>-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fas <?= $config['icon'] ?> text-<?= $config['color'] ?>-600 text-xl"></i>
                            </div>

                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2 flex-wrap">
                                    <h3 class="font-bold text-gray-800">
                                        Cotización #<?= htmlspecialchars($quote['numero_cotizacion']) ?>
                                    </h3>
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold <?= $statusConfig['class'] ?>">
                                        <i class="fas <?= $statusConfig['icon'] ?> mr-1"></i>
                                        <?= ucfirst(str_replace('_', ' ', $quote['status'])) ?>
                                    </span>
                                </div>

                                <p class="text-gray-600 mb-2">Seguro de <?= ucfirst($tipo) ?></p>

                                <div class="flex flex-wrap gap-4 text-sm text-gray-500">
                                    <span><i class="far fa-calendar mr-1"></i>Fecha: <?= date('d/m/Y', strtotime($quote['fecha_cotizacion'])) ?></span>
                                    <?php if ($quote['prima_estimada']): ?>
                                        <span><i class="fas fa-dollar-sign mr-1"></i>Prima estimada: <?= $quote['moneda'] === 'USD' ? '$' : '₡' ?><?= number_format($quote['prima_estimada'], 2) ?></span>
                                    <?php endif; ?>
                                    <?php if ($quote['fecha_vencimiento_cotizacion']): ?>
                                        <span><i class="fas fa-hourglass-half mr-1"></i>Válida hasta: <?= date('d/m/Y', strtotime($quote['fecha_vencimiento_cotizacion'])) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="flex gap-2">
                                <?php if ($quote['archivo_cotizacion_url']): ?>
                                    <a href="<?= htmlspecialchars($quote['archivo_cotizacion_url']) ?>"
                                       target="_blank"
                                       class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                                        <i class="fas fa-download mr-1"></i>Descargar
                                    </a>
                                <?php endif; ?>
                                <?php if ($quote['status'] === 'enviada'): ?>
                                    <a href="https://wa.me/50688888888?text=Hola,%20quiero%20aceptar%20la%20cotización%20%23<?= $quote['numero_cotizacion'] ?>"
                                       target="_blank"
                                       class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                                        <i class="fas fa-check mr-1"></i>Aceptar
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
