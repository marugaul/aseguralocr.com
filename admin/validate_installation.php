<?php
/**
 * Script de Validación del Sistema de Generación de PDFs
 * 
 * Ejecuta este script para verificar que todo está correctamente configurado
 * Acceso: http://tudominio.com/admin/validate_installation.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$results = [];
$allOk = true;

// Header HTML
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validación de Instalación - Sistema PDF</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white p-6">
                <h1 class="text-3xl font-bold">🔍 Validación de Instalación</h1>
                <p class="text-blue-100 mt-2">Sistema de Generación de PDFs para Formularios INS</p>
            </div>

            <div class="p-6">
<?php

// ==============================================
// 1. Verificar Composer Autoload
// ==============================================
$autoloadPaths = [
    '/composer/vendor/autoload.php',
    __DIR__ . '/../../composer/vendor/autoload.php',
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../composer/vendor/autoload.php'
];

$autoloadFound = false;
$autoloadLocation = '';
foreach ($autoloadPaths as $path) {
    if (file_exists($path)) {
        $autoloadFound = true;
        $autoloadLocation = $path;
        break;
    }
}

$results[] = [
    'title' => 'Composer Autoload',
    'status' => $autoloadFound,
    'message' => $autoloadFound 
        ? "✅ Encontrado en: <code class='bg-gray-100 px-2 py-1 rounded'>{$autoloadLocation}</code>"
        : "❌ No encontrado en ninguna ubicación. Ejecuta: <code class='bg-gray-100 px-2 py-1 rounded'>composer require setasign/fpdi-fpdf</code>",
    'critical' => true
];

if (!$autoloadFound) $allOk = false;

// ==============================================
// 2. Verificar librería FPDI
// ==============================================
$fpdiInstalled = false;
if ($autoloadFound) {
    require_once $autoloadLocation;
    $fpdiInstalled = class_exists('setasign\Fpdi\Fpdi');
}

$results[] = [
    'title' => 'Librería FPDI',
    'status' => $fpdiInstalled,
    'message' => $fpdiInstalled 
        ? "✅ Instalada correctamente"
        : "❌ No instalada. Ejecuta: <code class='bg-gray-100 px-2 py-1 rounded'>composer require setasign/fpdi-fpdf</code>",
    'critical' => true
];

if (!$fpdiInstalled) $allOk = false;

// ==============================================
// 3. Verificar archivo de mapeo
// ==============================================
$mapPath = __DIR__ . '/../mappings/hogar_map.json';
$mapExists = file_exists($mapPath);
$mapValid = false;
$mapContent = null;

if ($mapExists) {
    $mapContent = @json_decode(file_get_contents($mapPath), true);
    $mapValid = $mapContent !== null && 
                isset($mapContent['meta']) && 
                isset($mapContent['fields']);
}

$results[] = [
    'title' => 'Archivo de Mapeo (hogar_map.json)',
    'status' => $mapExists && $mapValid,
    'message' => $mapExists 
        ? ($mapValid 
            ? "✅ Encontrado y válido en: <code class='bg-gray-100 px-2 py-1 rounded'>{$mapPath}</code>"
            : "⚠️ Encontrado pero JSON inválido")
        : "❌ No encontrado. Debe estar en: <code class='bg-gray-100 px-2 py-1 rounded'>{$mapPath}</code>",
    'critical' => false
];

// ==============================================
// 4. Verificar template PDF
// ==============================================
$templateExists = false;
$templatePath = '';
if ($mapValid && isset($mapContent['meta']['template'])) {
    $templatePath = __DIR__ . '/../' . ltrim($mapContent['meta']['template'], '/');
    $templateExists = file_exists($templatePath);
}

$results[] = [
    'title' => 'Formulario Base PDF',
    'status' => $templateExists,
    'message' => $templateExists 
        ? "✅ Encontrado en: <code class='bg-gray-100 px-2 py-1 rounded text-xs'>{$templatePath}</code>"
        : "❌ No encontrado. Ruta esperada: <code class='bg-gray-100 px-2 py-1 rounded text-xs'>{$templatePath}</code>",
    'critical' => false
];

// ==============================================
// 5. Verificar directorio de salida
// ==============================================
$outputDirExists = false;
$outputDirWritable = false;
$outputDir = '';
if ($mapValid && isset($mapContent['meta']['output_dir'])) {
    $outputDir = __DIR__ . '/../' . ltrim($mapContent['meta']['output_dir'], '/');
    $outputDirExists = is_dir($outputDir);
    $outputDirWritable = $outputDirExists && is_writable($outputDir);
}

$results[] = [
    'title' => 'Directorio de Salida',
    'status' => $outputDirExists && $outputDirWritable,
    'message' => $outputDirExists 
        ? ($outputDirWritable 
            ? "✅ Existe y es escribible: <code class='bg-gray-100 px-2 py-1 rounded text-xs'>{$outputDir}</code>"
            : "⚠️ Existe pero no es escribible. Ejecuta: <code class='bg-gray-100 px-2 py-1 rounded'>chmod 755 {$outputDir}</code>")
        : "❌ No existe. Créalo con: <code class='bg-gray-100 px-2 py-1 rounded'>mkdir -p {$outputDir}</code>",
    'critical' => false
];

if (!$outputDirWritable) $allOk = false;

// ==============================================
// 6. Verificar conexión a base de datos
// ==============================================
$dbConnected = false;
$dbError = '';
try {
    $dbFile = __DIR__ . '/../includes/db.php';
    if (file_exists($dbFile)) {
        require_once $dbFile;
        if (isset($pdo) && $pdo instanceof PDO) {
            $pdo->query("SELECT 1");
            $dbConnected = true;
        }
    }
} catch (Exception $e) {
    $dbError = $e->getMessage();
}

$results[] = [
    'title' => 'Conexión a Base de Datos',
    'status' => $dbConnected,
    'message' => $dbConnected 
        ? "✅ Conectado correctamente"
        : "❌ Error: " . ($dbError ?: "Archivo db.php no encontrado"),
    'critical' => true
];

if (!$dbConnected) $allOk = false;

// ==============================================
// 7. Verificar archivos PHP requeridos
// ==============================================
$requiredFiles = [
    'pdf_generator.php' => __DIR__ . '/pdf_generator.php',
    'view_cotizacion.php' => __DIR__ . '/view_cotizacion.php',
    'dashboard.php' => __DIR__ . '/dashboard.php',
];

foreach ($requiredFiles as $name => $path) {
    $exists = file_exists($path);
    $results[] = [
        'title' => "Archivo: {$name}",
        'status' => $exists,
        'message' => $exists 
            ? "✅ Encontrado"
            : "❌ No encontrado en: <code class='bg-gray-100 px-2 py-1 rounded text-xs'>{$path}</code>",
        'critical' => false
    ];
}

// ==============================================
// 8. Verificar extensión GD (para imágenes)
// ==============================================
$gdLoaded = extension_loaded('gd');
$results[] = [
    'title' => 'Extensión GD (PHP)',
    'status' => $gdLoaded,
    'message' => $gdLoaded 
        ? "✅ Instalada"
        : "⚠️ No instalada (opcional para manipulación de imágenes)",
    'critical' => false
];

// ==============================================
// Renderizar resultados
// ==============================================

$criticalCount = 0;
$warningCount = 0;

foreach ($results as $result) {
    if (!$result['status']) {
        if ($result['critical']) {
            $criticalCount++;
        } else {
            $warningCount++;
        }
    }
}

?>
                <!-- Resumen General -->
                <div class="mb-6 p-4 rounded-lg <?= $allOk ? 'bg-green-100 border border-green-400' : 'bg-red-100 border border-red-400' ?>">
                    <div class="flex items-center gap-3">
                        <?php if ($allOk): ?>
                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div>
                                <h2 class="text-xl font-bold text-green-800">¡Sistema Listo para Usar!</h2>
                                <p class="text-green-700">Todas las verificaciones críticas pasaron exitosamente.</p>
                            </div>
                        <?php else: ?>
                            <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div>
                                <h2 class="text-xl font-bold text-red-800">Se Requiere Atención</h2>
                                <p class="text-red-700">
                                    <?= $criticalCount ?> error(es) crítico(s), <?= $warningCount ?> advertencia(s)
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Resultados Detallados -->
                <div class="space-y-3">
                    <?php foreach ($results as $result): ?>
                        <div class="border rounded-lg p-4 <?= $result['status'] ? 'border-green-200 bg-green-50' : ($result['critical'] ? 'border-red-200 bg-red-50' : 'border-yellow-200 bg-yellow-50') ?>">
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0 mt-1">
                                    <?php if ($result['status']): ?>
                                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    <?php else: ?>
                                        <svg class="w-6 h-6 <?= $result['critical'] ? 'text-red-600' : 'text-yellow-600' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-900 mb-1">
                                        <?= htmlspecialchars($result['title']) ?>
                                        <?php if ($result['critical'] && !$result['status']): ?>
                                            <span class="text-xs bg-red-200 text-red-800 px-2 py-1 rounded ml-2">CRÍTICO</span>
                                        <?php endif; ?>
                                    </h3>
                                    <p class="text-sm text-gray-700"><?= $result['message'] ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Información Adicional -->
                <?php if ($mapValid): ?>
                    <div class="mt-6 border-t pt-6">
                        <h3 class="text-lg font-semibold mb-3">📋 Configuración Actual</h3>
                        <div class="bg-gray-50 p-4 rounded">
                            <pre class="text-xs overflow-x-auto"><?= htmlspecialchars(json_encode($mapContent['meta'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Acciones -->
                <div class="mt-6 border-t pt-6">
                    <h3 class="text-lg font-semibold mb-3">🚀 Próximos Pasos</h3>
                    <?php if ($allOk): ?>
                        <div class="space-y-2">
                            <a href="/admin/dashboard.php" class="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition">
                                Ir al Dashboard
                            </a>
                            <p class="text-sm text-gray-600 mt-2">
                                El sistema está listo. Puedes comenzar a generar PDFs desde el dashboard.
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="bg-yellow-50 border border-yellow-200 p-4 rounded">
                            <p class="text-sm text-gray-700 mb-2">Corrige los errores críticos antes de usar el sistema:</p>
                            <ol class="list-decimal list-inside space-y-1 text-sm text-gray-700">
                                <?php if (!$autoloadFound): ?>
                                    <li>Instala Composer y ejecuta: <code class="bg-gray-200 px-2 py-1 rounded">composer require setasign/fpdi-fpdf</code></li>
                                <?php endif; ?>
                                <?php if (!$dbConnected): ?>
                                    <li>Verifica la configuración de la base de datos en <code class="bg-gray-200 px-2 py-1 rounded">includes/db.php</code></li>
                                <?php endif; ?>
                                <?php if (!$outputDirWritable): ?>
                                    <li>Crea y da permisos al directorio de salida</li>
                                <?php endif; ?>
                            </ol>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Footer -->
                <div class="mt-8 text-center text-sm text-gray-500">
                    <p>Sistema de Generación de PDFs v1.0 | Agente: <?= htmlspecialchars($mapContent['meta']['agent_number'] ?? '110886') ?></p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>