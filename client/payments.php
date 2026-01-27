<?php
// client/payments.php - Pagos del cliente con opciones SINPE y Tarjeta
require_once __DIR__ . '/includes/client_auth.php';
require_client_login();

require_once __DIR__ . '/../includes/db.php';

$clientId = get_current_client_id();
$clientData = get_client_data();

// Obtener configuración del agente (SINPE, etc.)
$agentSettings = [];
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM agent_settings");
    while ($row = $stmt->fetch()) {
        $agentSettings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
    // Valores por defecto si la tabla no existe
    $agentSettings = [
        'sinpe_numero' => '8888-8888',
        'sinpe_nombre' => 'AseguraloCR',
        'telefono_agente' => '+506 8888-8888',
        'whatsapp_agente' => '+506 8888-8888'
    ];
}

// Filtros
$statusFilter = $_GET['status'] ?? 'all';
$policyFilter = $_GET['policy'] ?? 'all';

// Obtener pagos del cliente
$sql = "
    SELECT p.*, pol.numero_poliza, pol.tipo_seguro, pol.aseguradora
    FROM payments p
    INNER JOIN policies pol ON p.policy_id = pol.id
    WHERE pol.client_id = ?
";
$params = [$clientId];

if ($statusFilter !== 'all') {
    $sql .= " AND p.status = ?";
    $params[] = $statusFilter;
}

if ($policyFilter !== 'all') {
    $sql .= " AND pol.id = ?";
    $params[] = $policyFilter;
}

$sql .= " ORDER BY p.fecha_vencimiento ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$payments = $stmt->fetchAll();

// Obtener pólizas para filtro
$stmt = $pdo->prepare("SELECT id, numero_poliza, tipo_seguro FROM policies WHERE client_id = ? ORDER BY numero_poliza");
$stmt->execute([$clientId]);
$policies = $stmt->fetchAll();

// Calcular totales
$totalPendiente = 0;
$totalVencido = 0;
foreach ($payments as $payment) {
    if ($payment['status'] === 'pendiente') {
        $totalPendiente += $payment['monto'];
    } elseif ($payment['status'] === 'vencido') {
        $totalVencido += $payment['monto'];
    }
}

// Función para formatear moneda
function formatMoney($amount, $currency = 'CRC') {
    $symbol = $currency === 'USD' ? '$' : '₡';
    return $symbol . number_format($amount, 2);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Pagos - AseguraloCR</title>
    <link rel="icon" type="image/svg+xml" href="/imagenes/favicon.svg">
    <link rel="icon" type="image/png" href="/imagenes/favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        * { font-family: 'Inter', sans-serif; }
        .gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .card-hover { transition: all 0.3s ease; }
        .card-hover:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0,0,0,0.15); }
    </style>
</head>
<body class="bg-gray-50">
    <?php include __DIR__ . '/includes/nav.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">
                <i class="fas fa-credit-card text-purple-600 mr-3"></i>Mis Pagos
            </h1>
            <p class="text-gray-600">Gestiona tus pagos de pólizas de seguros</p>
        </div>

        <!-- Resumen de Pagos -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-yellow-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Pendientes</p>
                        <p class="text-2xl font-bold text-gray-800"><?= formatMoney($totalPendiente) ?></p>
                    </div>
                    <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-clock text-yellow-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-red-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Vencidos</p>
                        <p class="text-2xl font-bold text-red-600"><?= formatMoney($totalVencido) ?></p>
                    </div>
                    <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-r from-purple-600 to-purple-800 rounded-xl shadow-md p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-purple-200 text-sm">Total a Pagar</p>
                        <p class="text-2xl font-bold"><?= formatMoney($totalPendiente + $totalVencido) ?></p>
                    </div>
                    <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center">
                        <i class="fas fa-wallet text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Métodos de Pago Disponibles -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
            <h2 class="text-xl font-bold text-gray-800 mb-6">
                <i class="fas fa-money-bill-wave text-green-600 mr-2"></i>Métodos de Pago
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- SINPE Móvil -->
                <div class="border-2 border-green-200 rounded-xl p-6 bg-green-50">
                    <div class="flex items-center mb-4">
                        <div class="w-14 h-14 bg-green-600 rounded-xl flex items-center justify-center mr-4">
                            <i class="fas fa-mobile-alt text-white text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-800">SINPE Móvil</h3>
                            <p class="text-sm text-gray-600">Pago instantáneo desde tu celular</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg p-4 mb-4">
                        <p class="text-sm text-gray-500 mb-1">Número SINPE:</p>
                        <p class="text-2xl font-bold text-green-600 flex items-center">
                            <?= htmlspecialchars($agentSettings['sinpe_numero'] ?? '8888-8888') ?>
                            <button onclick="copySinpe()" class="ml-3 text-sm bg-green-100 hover:bg-green-200 text-green-700 px-3 py-1 rounded-lg transition">
                                <i class="fas fa-copy mr-1"></i>Copiar
                            </button>
                        </p>
                        <p class="text-sm text-gray-500 mt-2">A nombre de: <strong><?= htmlspecialchars($agentSettings['sinpe_nombre'] ?? 'AseguraloCR') ?></strong></p>
                    </div>

                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 text-sm">
                        <i class="fas fa-info-circle text-yellow-600 mr-2"></i>
                        <span class="text-yellow-800">Incluye el número de póliza en la descripción del SINPE</span>
                    </div>
                </div>

                <!-- Tarjeta de Crédito/Débito -->
                <div class="border-2 border-blue-200 rounded-xl p-6 bg-blue-50">
                    <div class="flex items-center mb-4">
                        <div class="w-14 h-14 bg-blue-600 rounded-xl flex items-center justify-center mr-4">
                            <i class="fas fa-credit-card text-white text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-800">Tarjeta de Crédito/Débito</h3>
                            <p class="text-sm text-gray-600">Visa, Mastercard, American Express</p>
                        </div>
                    </div>

                    <div class="flex items-center justify-center gap-4 mb-4">
                        <svg viewBox="0 0 48 48" class="h-10 w-16"><path fill="#1565C0" d="M45,35c0,2.2-1.8,4-4,4H7c-2.2,0-4-1.8-4-4V13c0-2.2,1.8-4,4-4h34c2.2,0,4,1.8,4,4V35z"/><path fill="#FFF" d="M15.2,28.5h-2.8l1.7-10.3h2.8L15.2,28.5z M22.2,18.5c-0.6-0.2-1.4-0.5-2.5-0.5c-2.7,0-4.7,1.4-4.7,3.5c0,1.5,1.4,2.3,2.4,2.8c1,0.5,1.4,0.8,1.4,1.3c0,0.7-0.8,1-1.6,1c-1.1,0-1.7-0.2-2.6-0.5l-0.4-0.2l-0.4,2.3c0.6,0.3,1.8,0.5,3,0.5c2.9,0,4.8-1.4,4.8-3.6c0-1.2-0.7-2.1-2.3-2.8c-1-0.5-1.6-0.8-1.6-1.3c0-0.4,0.5-0.9,1.6-0.9c0.9,0,1.6,0.2,2.1,0.4l0.3,0.1L22.2,18.5z"/><path fill="#FFF" d="M28.3,18.2h-2.1c-0.7,0-1.2,0.2-1.5,0.9l-4.2,9.4h3l0.6-1.6h3.6c0.1,0.4,0.3,1.6,0.3,1.6h2.6L28.3,18.2z M25.1,24.7c0.2-0.6,1.1-2.8,1.1-2.8c0,0,0.2-0.6,0.4-1l0.2,0.9c0,0,0.5,2.4,0.6,2.9H25.1z"/><path fill="#FFF" d="M38.3,18.2l-2.7,7.1l-0.3-1.4c-0.5-1.6-2-3.3-3.7-4.2l2.5,9.3h3l4.5-10.8H38.3z"/><path fill="#FFC107" d="M13.5,18.2H9l0,0.2c3.5,0.8,5.8,2.8,6.8,5.3l-1-4.7C14.6,18.4,14.1,18.2,13.5,18.2z"/></svg>
                        <svg viewBox="0 0 48 48" class="h-10 w-16"><path fill="#3F51B5" d="M45,35c0,2.2-1.8,4-4,4H7c-2.2,0-4-1.8-4-4V13c0-2.2,1.8-4,4-4h34c2.2,0,4,1.8,4,4V35z"/><path fill="#FFC107" d="M30,24c0,3.3-2.7,6-6,6s-6-2.7-6-6s2.7-6,6-6S30,20.7,30,24z"/><path fill="#FF3D00" d="M22.1,30c-1.5-1.4-2.5-3.5-2.5-5.8c0-2.3,0.9-4.4,2.5-5.8c-1.3-1-3-1.6-4.8-1.6c-4.4,0-7.9,3.5-7.9,7.9s3.5,7.9,7.9,7.9C19.1,32.5,20.8,31.5,22.1,30z"/><path fill="#FF9800" d="M38.6,24c0,4.4-3.5,7.9-7.9,7.9c-1.8,0-3.5-0.6-4.8-1.6c1.5-1.4,2.5-3.5,2.5-5.8c0-2.3-0.9-4.4-2.5-5.8c1.3-1,3-1.6,4.8-1.6C35.1,17,38.6,20.5,38.6,24z"/></svg>
                    </div>

                    <p class="text-sm text-gray-600 text-center mb-4">
                        Selecciona un pago de la lista y haz clic en "Pagar con Tarjeta"
                    </p>

                    <div class="bg-blue-100 border border-blue-200 rounded-lg p-3 text-sm text-center">
                        <i class="fas fa-lock text-blue-600 mr-2"></i>
                        <span class="text-blue-800">Pago seguro procesado por el INS</span>
                    </div>
                </div>
            </div>

            <!-- WhatsApp para confirmar pago -->
            <div class="mt-6 bg-gradient-to-r from-green-500 to-green-600 rounded-xl p-4 text-white">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div class="flex items-center">
                        <i class="fab fa-whatsapp text-3xl mr-4"></i>
                        <div>
                            <p class="font-semibold">¿Ya realizaste tu pago?</p>
                            <p class="text-sm text-green-100">Envíanos el comprobante por WhatsApp para confirmar</p>
                        </div>
                    </div>
                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $agentSettings['whatsapp_agente'] ?? '50688888888') ?>?text=Hola,%20acabo%20de%20realizar%20un%20pago%20de%20mi%20póliza"
                       target="_blank"
                       class="bg-white text-green-600 px-6 py-3 rounded-lg font-semibold hover:bg-green-50 transition">
                        <i class="fab fa-whatsapp mr-2"></i>Enviar Comprobante
                    </a>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="bg-white rounded-xl shadow-md p-4 mb-6">
            <form method="GET" class="flex flex-wrap gap-4 items-center">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                    <select name="status" onchange="this.form.submit()"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>Todos</option>
                        <option value="pendiente" <?= $statusFilter === 'pendiente' ? 'selected' : '' ?>>Pendientes</option>
                        <option value="vencido" <?= $statusFilter === 'vencido' ? 'selected' : '' ?>>Vencidos</option>
                        <option value="pagado" <?= $statusFilter === 'pagado' ? 'selected' : '' ?>>Pagados</option>
                    </select>
                </div>
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Póliza</label>
                    <select name="policy" onchange="this.form.submit()"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        <option value="all">Todas las pólizas</option>
                        <?php foreach ($policies as $pol): ?>
                            <option value="<?= $pol['id'] ?>" <?= $policyFilter == $pol['id'] ? 'selected' : '' ?>>
                                #<?= htmlspecialchars($pol['numero_poliza']) ?> - <?= ucfirst($pol['tipo_seguro']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>

        <!-- Lista de Pagos -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-list text-purple-600 mr-2"></i>Detalle de Pagos
                </h2>
            </div>

            <?php if (empty($payments)): ?>
                <div class="p-12 text-center">
                    <i class="fas fa-check-circle text-green-400 text-6xl mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">¡Estás al día!</h3>
                    <p class="text-gray-500">No tienes pagos pendientes</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Póliza</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Concepto</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Monto</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Vencimiento</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Estado</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($payments as $payment): ?>
                                <?php
                                $isOverdue = $payment['status'] === 'vencido';
                                $isPending = $payment['status'] === 'pendiente';
                                $isPaid = $payment['status'] === 'pagado';
                                $statusClass = $isPaid ? 'bg-green-100 text-green-700' : ($isOverdue ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700');
                                $statusIcon = $isPaid ? 'fa-check-circle' : ($isOverdue ? 'fa-exclamation-circle' : 'fa-clock');
                                ?>
                                <tr class="hover:bg-gray-50 transition <?= $isOverdue ? 'bg-red-50' : '' ?>">
                                    <td class="px-6 py-4">
                                        <div>
                                            <p class="font-semibold text-gray-800">#<?= htmlspecialchars($payment['numero_poliza']) ?></p>
                                            <p class="text-sm text-gray-500"><?= ucfirst($payment['tipo_seguro']) ?></p>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="text-gray-700"><?= ucfirst(str_replace('_', ' ', $payment['tipo_pago'])) ?></p>
                                        <?php if ($payment['numero_cuota']): ?>
                                            <p class="text-sm text-gray-500">Cuota #<?= $payment['numero_cuota'] ?></p>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="text-lg font-bold text-gray-800">
                                            <?= formatMoney($payment['monto'], $payment['moneda']) ?>
                                        </p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="text-gray-700"><?= date('d/m/Y', strtotime($payment['fecha_vencimiento'])) ?></p>
                                        <?php if ($isPaid && $payment['fecha_pago']): ?>
                                            <p class="text-sm text-green-600">Pagado: <?= date('d/m/Y', strtotime($payment['fecha_pago'])) ?></p>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?= $statusClass ?>">
                                            <i class="fas <?= $statusIcon ?> mr-2"></i>
                                            <?= ucfirst($payment['status']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <?php if (!$isPaid): ?>
                                            <div class="flex items-center justify-center gap-2">
                                                <?php if (!empty($payment['link_pago_tarjeta'])): ?>
                                                    <a href="<?= htmlspecialchars($payment['link_pago_tarjeta']) ?>"
                                                       target="_blank"
                                                       class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                                                        <i class="fas fa-credit-card mr-1"></i>Pagar
                                                    </a>
                                                <?php endif; ?>
                                                <button onclick="showPaymentModal(<?= htmlspecialchars(json_encode($payment)) ?>)"
                                                        class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                                                    <i class="fas fa-info-circle mr-1"></i>Ver
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <?php if ($payment['comprobante_url']): ?>
                                                <a href="<?= htmlspecialchars($payment['comprobante_url']) ?>"
                                                   target="_blank"
                                                   class="text-purple-600 hover:text-purple-700">
                                                    <i class="fas fa-receipt mr-1"></i>Comprobante
                                                </a>
                                            <?php else: ?>
                                                <span class="text-gray-400 text-sm">-</span>
                                            <?php endif; ?>
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

    <!-- Modal de Detalle de Pago -->
    <div id="paymentModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-xl font-bold text-gray-800">Detalle del Pago</h3>
                <button onclick="closePaymentModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="modalContent" class="p-6">
                <!-- Content loaded dynamically -->
            </div>
        </div>
    </div>

    <script>
    function copySinpe() {
        const sinpe = '<?= $agentSettings['sinpe_numero'] ?? '8888-8888' ?>';
        navigator.clipboard.writeText(sinpe.replace(/-/g, '')).then(() => {
            alert('Número SINPE copiado: ' + sinpe);
        });
    }

    function showPaymentModal(payment) {
        const modal = document.getElementById('paymentModal');
        const content = document.getElementById('modalContent');

        const statusClass = payment.status === 'pagado' ? 'bg-green-100 text-green-700' :
                           (payment.status === 'vencido' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700');

        content.innerHTML = `
            <div class="space-y-4">
                <div class="flex justify-between items-center pb-4 border-b">
                    <span class="text-gray-500">Estado</span>
                    <span class="px-3 py-1 rounded-full text-sm font-medium ${statusClass}">
                        ${payment.status.charAt(0).toUpperCase() + payment.status.slice(1)}
                    </span>
                </div>

                <div class="flex justify-between">
                    <span class="text-gray-500">Póliza</span>
                    <span class="font-semibold">#${payment.numero_poliza}</span>
                </div>

                <div class="flex justify-between">
                    <span class="text-gray-500">Tipo de Seguro</span>
                    <span class="font-semibold">${payment.tipo_seguro.charAt(0).toUpperCase() + payment.tipo_seguro.slice(1)}</span>
                </div>

                <div class="flex justify-between">
                    <span class="text-gray-500">Concepto</span>
                    <span class="font-semibold">${payment.tipo_pago.replace(/_/g, ' ')}</span>
                </div>

                <div class="flex justify-between items-center pt-4 border-t">
                    <span class="text-gray-500">Monto a Pagar</span>
                    <span class="text-2xl font-bold text-purple-600">
                        ${payment.moneda === 'USD' ? '$' : '₡'}${parseFloat(payment.monto).toLocaleString('es-CR', {minimumFractionDigits: 2})}
                    </span>
                </div>

                <div class="flex justify-between">
                    <span class="text-gray-500">Fecha Vencimiento</span>
                    <span class="font-semibold">${new Date(payment.fecha_vencimiento).toLocaleDateString('es-CR')}</span>
                </div>

                ${payment.status !== 'pagado' ? `
                    <div class="pt-6 space-y-3">
                        <p class="text-sm text-gray-500 text-center">Opciones de pago:</p>

                        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $agentSettings['whatsapp_agente'] ?? '50688888888') ?>?text=Hola,%20quiero%20pagar%20la%20póliza%20%23${payment.numero_poliza}%20por%20${payment.moneda === 'USD' ? '$' : '₡'}${payment.monto}"
                           target="_blank"
                           class="block w-full bg-green-500 hover:bg-green-600 text-white text-center py-3 rounded-lg font-semibold transition">
                            <i class="fab fa-whatsapp mr-2"></i>Pagar por SINPE
                        </a>

                        ${payment.link_pago_tarjeta ? `
                            <a href="${payment.link_pago_tarjeta}" target="_blank"
                               class="block w-full bg-blue-600 hover:bg-blue-700 text-white text-center py-3 rounded-lg font-semibold transition">
                                <i class="fas fa-credit-card mr-2"></i>Pagar con Tarjeta
                            </a>
                        ` : ''}
                    </div>
                ` : ''}
            </div>
        `;

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closePaymentModal() {
        const modal = document.getElementById('paymentModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    // Close modal on outside click
    document.getElementById('paymentModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closePaymentModal();
        }
    });
    </script>
</body>
</html>
