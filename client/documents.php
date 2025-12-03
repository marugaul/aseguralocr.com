<?php
// client/documents.php - Mis documentos (portal cliente)
require_once __DIR__ . '/includes/client_auth.php';
require_client_login();

require_once __DIR__ . '/../includes/db.php';

$clientId = get_current_client_id();
$clientData = get_client_data();

// Obtener documentos visibles del cliente
$stmt = $pdo->prepare("
    SELECT d.*, p.numero_poliza, p.tipo_seguro
    FROM client_documents d
    LEFT JOIN policies p ON d.policy_id = p.id
    WHERE d.client_id = ? AND d.visible_cliente = 1
    ORDER BY d.created_at DESC
");
$stmt->execute([$clientId]);
$documents = $stmt->fetchAll();

// Agrupar por tipo
$byType = [];
foreach ($documents as $doc) {
    $tipo = $doc['tipo'];
    if (!isset($byType[$tipo])) {
        $byType[$tipo] = [];
    }
    $byType[$tipo][] = $doc;
}

$tipoLabels = [
    'poliza' => ['Docs. de Póliza', 'fas fa-file-contract', 'primary'],
    'cotizacion' => ['Cotizaciones', 'fas fa-file-invoice', 'info'],
    'factura' => ['Facturas', 'fas fa-file-invoice-dollar', 'success'],
    'comprobante' => ['Comprobantes', 'fas fa-receipt', 'warning'],
    'contrato' => ['Contratos', 'fas fa-file-signature', 'secondary'],
    'anexo' => ['Anexos', 'fas fa-paperclip', 'dark'],
    'otro' => ['Otros', 'fas fa-file', 'light']
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Documentos - AseguraloCR</title>
    <link rel="icon" type="image/svg+xml" href="/imagenes/favicon.svg">
    <link rel="icon" type="image/png" href="/imagenes/favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        * { font-family: 'Inter', sans-serif; }
        .doc-card { transition: all 0.3s ease; }
        .doc-card:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
    </style>
</head>
<body class="bg-gray-50">
    <?php include __DIR__ . '/includes/nav.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">
                <i class="fas fa-folder-open mr-3 text-blue-600"></i>Mis Documentos
            </h1>
            <p class="text-gray-600">Descarga tus pólizas, cotizaciones y otros documentos</p>
        </div>

        <?php if (empty($documents)): ?>
        <!-- Sin documentos -->
        <div class="bg-white rounded-2xl shadow-lg p-12 text-center">
            <i class="fas fa-folder-open text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-700 mb-2">No tienes documentos</h3>
            <p class="text-gray-500">Cuando tu agente suba documentos para ti, aparecerán aquí.</p>
        </div>
        <?php else: ?>

        <!-- Resumen -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <?php foreach ($byType as $tipo => $docs): ?>
            <?php
                $label = $tipoLabels[$tipo] ?? ['Otros', 'fas fa-file', 'gray'];
                $colorClasses = match($label[2]) {
                    'primary' => 'bg-blue-100 text-blue-600',
                    'info' => 'bg-cyan-100 text-cyan-600',
                    'success' => 'bg-green-100 text-green-600',
                    'warning' => 'bg-yellow-100 text-yellow-600',
                    'secondary' => 'bg-gray-100 text-gray-600',
                    'dark' => 'bg-gray-200 text-gray-800',
                    default => 'bg-gray-100 text-gray-600'
                };
            ?>
            <div class="bg-white rounded-xl shadow p-4">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 rounded-full <?= $colorClasses ?> flex items-center justify-center">
                        <i class="<?= $label[1] ?> text-xl"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-800"><?= count($docs) ?></div>
                        <div class="text-sm text-gray-500"><?= $label[0] ?></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Lista de documentos por tipo -->
        <?php foreach ($byType as $tipo => $docs): ?>
        <?php $label = $tipoLabels[$tipo] ?? ['Otros', 'fas fa-file', 'gray']; ?>
        <div class="mb-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">
                <i class="<?= $label[1] ?> mr-2"></i><?= $label[0] ?>
            </h2>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($docs as $doc): ?>
                <?php
                    $ext = strtolower(pathinfo($doc['nombre_archivo'], PATHINFO_EXTENSION));
                    $iconClass = match($ext) {
                        'pdf' => 'fas fa-file-pdf text-red-500',
                        'doc', 'docx' => 'fas fa-file-word text-blue-500',
                        'jpg', 'jpeg', 'png', 'gif' => 'fas fa-file-image text-green-500',
                        default => 'fas fa-file text-gray-500'
                    };
                ?>
                <div class="doc-card bg-white rounded-xl shadow p-5">
                    <div class="flex items-start space-x-4">
                        <div class="flex-shrink-0">
                            <i class="<?= $iconClass ?> text-4xl"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-semibold text-gray-800 truncate" title="<?= htmlspecialchars($doc['nombre']) ?>">
                                <?= htmlspecialchars($doc['nombre']) ?>
                            </h3>
                            <?php if ($doc['numero_poliza']): ?>
                            <p class="text-sm text-gray-500">
                                <i class="fas fa-shield-alt mr-1"></i>
                                Póliza: <?= htmlspecialchars($doc['numero_poliza']) ?>
                            </p>
                            <?php endif; ?>
                            <?php if ($doc['descripcion']): ?>
                            <p class="text-sm text-gray-500 mt-1 line-clamp-2">
                                <?= htmlspecialchars($doc['descripcion']) ?>
                            </p>
                            <?php endif; ?>
                            <div class="flex items-center justify-between mt-3">
                                <span class="text-xs text-gray-400">
                                    <i class="far fa-calendar mr-1"></i>
                                    <?= date('d/m/Y', strtotime($doc['created_at'])) ?>
                                    •
                                    <?= number_format($doc['tamano_bytes'] / 1024, 0) ?> KB
                                </span>
                                <a href="/client/download.php?id=<?= $doc['id'] ?>"
                                   class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition">
                                    <i class="fas fa-download mr-1"></i>
                                    Descargar
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <?php endif; ?>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
