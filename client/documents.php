<?php
// client/documents.php - Documentos del cliente (cotizaciones, pólizas, manuales)
require_once __DIR__ . '/includes/client_auth.php';
require_client_login();

require_once __DIR__ . '/../includes/db.php';

$clientId = get_current_client_id();
$clientData = get_client_data();

// Filtros
$tipoFilter = $_GET['tipo'] ?? 'all';
$policyFilter = $_GET['policy'] ?? 'all';

// Obtener documentos del cliente
$documents = [];
try {
    $sql = "
        SELECT pd.*, p.numero_poliza, p.tipo_seguro
        FROM policy_documents pd
        INNER JOIN policies p ON pd.policy_id = p.id
        WHERE p.client_id = ? AND pd.visible_cliente = TRUE
    ";
    $params = [$clientId];

    if ($tipoFilter !== 'all') {
        $sql .= " AND pd.tipo = ?";
        $params[] = $tipoFilter;
    }

    if ($policyFilter !== 'all') {
        $sql .= " AND p.id = ?";
        $params[] = $policyFilter;
    }

    $sql .= " ORDER BY pd.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $documents = $stmt->fetchAll();
} catch (Exception $e) {
    // Table might not exist yet
    $documents = [];
}

// Obtener pólizas para filtro
$policies = [];
try {
    $stmt = $pdo->prepare("SELECT id, numero_poliza, tipo_seguro FROM policies WHERE client_id = ? ORDER BY numero_poliza");
    $stmt->execute([$clientId]);
    $policies = $stmt->fetchAll();
} catch (Exception $e) {
    $policies = [];
}

// Agrupar documentos por tipo
$documentsByType = [
    'cotizacion' => [],
    'poliza_emitida' => [],
    'manual_indemnizacion' => [],
    'anexo' => [],
    'comprobante_pago' => [],
    'otro' => []
];

foreach ($documents as $doc) {
    $tipo = $doc['tipo'] ?? 'otro';
    if (isset($documentsByType[$tipo])) {
        $documentsByType[$tipo][] = $doc;
    } else {
        $documentsByType['otro'][] = $doc;
    }
}

// Iconos y colores por tipo
$tipoConfig = [
    'cotizacion' => ['icon' => 'fa-file-invoice-dollar', 'color' => 'blue', 'label' => 'Cotizaciones'],
    'poliza_emitida' => ['icon' => 'fa-file-contract', 'color' => 'green', 'label' => 'Pólizas Emitidas'],
    'manual_indemnizacion' => ['icon' => 'fa-book', 'color' => 'purple', 'label' => 'Manuales de Indemnización'],
    'anexo' => ['icon' => 'fa-paperclip', 'color' => 'yellow', 'label' => 'Anexos'],
    'comprobante_pago' => ['icon' => 'fa-receipt', 'color' => 'teal', 'label' => 'Comprobantes de Pago'],
    'otro' => ['icon' => 'fa-file', 'color' => 'gray', 'label' => 'Otros Documentos']
];

function formatFileSize($bytes) {
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Documentos - AseguraloCR</title>
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
                <i class="fas fa-folder-open text-purple-600 mr-3"></i>Mis Documentos
            </h1>
            <p class="text-gray-600">Descarga tus cotizaciones, pólizas y manuales de indemnización</p>
        </div>

        <!-- Resumen por tipo -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
            <?php foreach ($tipoConfig as $tipo => $config): ?>
                <?php $count = count($documentsByType[$tipo]); ?>
                <div class="bg-white rounded-xl shadow-md p-4 text-center card-hover cursor-pointer
                            <?= $tipoFilter === $tipo ? 'ring-2 ring-purple-500' : '' ?>"
                     onclick="window.location.href='?tipo=<?= $tipo ?>'">
                    <div class="w-12 h-12 bg-<?= $config['color'] ?>-100 rounded-full flex items-center justify-center mx-auto mb-2">
                        <i class="fas <?= $config['icon'] ?> text-<?= $config['color'] ?>-600 text-xl"></i>
                    </div>
                    <p class="text-2xl font-bold text-gray-800"><?= $count ?></p>
                    <p class="text-xs text-gray-500"><?= $config['label'] ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Filtros -->
        <div class="bg-white rounded-xl shadow-md p-4 mb-6">
            <form method="GET" class="flex flex-wrap gap-4 items-center">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Documento</label>
                    <select name="tipo" onchange="this.form.submit()"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        <option value="all" <?= $tipoFilter === 'all' ? 'selected' : '' ?>>Todos los tipos</option>
                        <?php foreach ($tipoConfig as $tipo => $config): ?>
                            <option value="<?= $tipo ?>" <?= $tipoFilter === $tipo ? 'selected' : '' ?>>
                                <?= $config['label'] ?>
                            </option>
                        <?php endforeach; ?>
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
                <?php if ($tipoFilter !== 'all' || $policyFilter !== 'all'): ?>
                    <a href="/client/documents.php" class="text-purple-600 hover:text-purple-700 text-sm">
                        <i class="fas fa-times mr-1"></i>Limpiar filtros
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Lista de Documentos -->
        <?php if (empty($documents)): ?>
            <div class="bg-white rounded-2xl shadow-lg p-12 text-center">
                <i class="fas fa-folder-open text-gray-300 text-6xl mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-700 mb-2">No hay documentos disponibles</h3>
                <p class="text-gray-500 mb-6">Cuando tengas pólizas activas, aquí aparecerán tus documentos</p>
                <a href="/hogar-comprensivo.php"
                   class="inline-block bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg font-semibold transition">
                    <i class="fas fa-plus mr-2"></i>Solicitar Cotización
                </a>
            </div>
        <?php else: ?>
            <!-- Documentos agrupados por tipo -->
            <?php foreach ($documentsByType as $tipo => $docs): ?>
                <?php if (!empty($docs) && ($tipoFilter === 'all' || $tipoFilter === $tipo)): ?>
                    <?php $config = $tipoConfig[$tipo]; ?>
                    <div class="bg-white rounded-2xl shadow-lg mb-6 overflow-hidden">
                        <div class="p-6 border-b border-gray-200 bg-<?= $config['color'] ?>-50">
                            <h2 class="text-xl font-bold text-gray-800 flex items-center">
                                <i class="fas <?= $config['icon'] ?> text-<?= $config['color'] ?>-600 mr-3"></i>
                                <?= $config['label'] ?>
                                <span class="ml-2 text-sm font-normal text-gray-500">(<?= count($docs) ?>)</span>
                            </h2>
                        </div>

                        <div class="divide-y divide-gray-100">
                            <?php foreach ($docs as $doc): ?>
                                <div class="p-4 hover:bg-gray-50 transition flex items-center justify-between gap-4">
                                    <div class="flex items-center gap-4 flex-1 min-w-0">
                                        <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                            <?php
                                            $fileIcon = 'fa-file';
                                            $mimeType = $doc['archivo_tipo'] ?? '';
                                            if (strpos($mimeType, 'pdf') !== false) $fileIcon = 'fa-file-pdf text-red-500';
                                            elseif (strpos($mimeType, 'word') !== false) $fileIcon = 'fa-file-word text-blue-500';
                                            elseif (strpos($mimeType, 'image') !== false) $fileIcon = 'fa-file-image text-green-500';
                                            elseif (strpos($mimeType, 'excel') !== false || strpos($mimeType, 'sheet') !== false) $fileIcon = 'fa-file-excel text-green-600';
                                            ?>
                                            <i class="fas <?= $fileIcon ?> text-2xl"></i>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <h3 class="font-semibold text-gray-800 truncate"><?= htmlspecialchars($doc['nombre']) ?></h3>
                                            <p class="text-sm text-gray-500">
                                                Póliza #<?= htmlspecialchars($doc['numero_poliza']) ?>
                                                • <?= ucfirst($doc['tipo_seguro']) ?>
                                            </p>
                                            <p class="text-xs text-gray-400 mt-1">
                                                <?= date('d/m/Y', strtotime($doc['created_at'])) ?>
                                                <?php if ($doc['archivo_tamano']): ?>
                                                    • <?= formatFileSize($doc['archivo_tamano']) ?>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <a href="<?= htmlspecialchars($doc['archivo_url']) ?>"
                                           target="_blank"
                                           class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium transition">
                                            <i class="fas fa-eye mr-1"></i>Ver
                                        </a>
                                        <a href="<?= htmlspecialchars($doc['archivo_url']) ?>"
                                           download="<?= htmlspecialchars($doc['archivo_nombre']) ?>"
                                           class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                                            <i class="fas fa-download mr-1"></i>Descargar
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Información adicional -->
        <div class="bg-gradient-to-r from-purple-600 to-purple-800 rounded-2xl shadow-lg p-6 text-white mt-8">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-info-circle text-2xl"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold mb-2">¿Necesitas algún documento?</h3>
                    <p class="text-purple-100 mb-4">
                        Si necesitas un documento que no aparece aquí (certificado de cobertura, constancia, etc.),
                        contáctanos y te lo enviaremos.
                    </p>
                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $agentSettings['whatsapp_agente'] ?? '50688888888') ?>?text=Hola,%20necesito%20un%20documento%20de%20mi%20póliza"
                       target="_blank"
                       class="inline-block bg-white text-purple-600 px-6 py-2 rounded-lg font-semibold hover:bg-purple-50 transition">
                        <i class="fab fa-whatsapp mr-2"></i>Solicitar Documento
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
