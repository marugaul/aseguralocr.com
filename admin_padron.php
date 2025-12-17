<?php
/**
 * Script para mover y extraer el padrón electoral
 * Con progreso AJAX para evitar timeouts
 */

session_start();
if (empty($_SESSION['admin_id'])) {
    die('Acceso denegado - Inicia sesión en /admin primero');
}

$dataDir = __DIR__ . '/data/padron';
$zipDest = $dataDir . '/padron_completo.zip';
$progressFile = $dataDir . '/extract_progress.json';

// API para verificar progreso
if (isset($_GET['progress'])) {
    header('Content-Type: application/json');
    if (file_exists($progressFile)) {
        echo file_get_contents($progressFile);
    } else {
        echo json_encode(['step' => 0, 'message' => 'Iniciando...']);
    }
    exit;
}

function updateProgress($file, $step, $message) {
    $dir = dirname($file);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    file_put_contents($file, json_encode([
        'step' => $step,
        'message' => $message,
        'time' => date('H:i:s')
    ]));
}

// Procesar extracción via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['extraer'])) {
    header('Content-Type: application/json');
    set_time_limit(300);

    try {
        // Buscar ZIP
        $posiblesUbicaciones = [
            __DIR__ . '/padron_temp.zip',
            '/home/asegural/documents/padron_completo.zip',
            '/home/asegural/public_html/aseguralocr_uploads/padron_completo.zip',
            __DIR__ . '/../documents/padron_completo.zip',
            __DIR__ . '/padron_completo.zip'
        ];

        $zipSource = null;
        foreach ($posiblesUbicaciones as $ubicacion) {
            if (file_exists($ubicacion)) {
                $zipSource = $ubicacion;
                break;
            }
        }

        if (!$zipSource) {
            throw new Exception("No se encontró el archivo ZIP en ninguna ubicación");
        }

        updateProgress($progressFile, 10, "ZIP encontrado: " . basename($zipSource));

        // Crear directorio
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0755, true);
            updateProgress($progressFile, 15, "Directorio creado");
        }

        // Copiar ZIP
        updateProgress($progressFile, 20, "Copiando ZIP...");
        if (!copy($zipSource, $zipDest)) {
            throw new Exception("Error al copiar ZIP");
        }
        updateProgress($progressFile, 40, "ZIP copiado");

        // Extraer ZIP
        updateProgress($progressFile, 50, "Extrayendo archivos (puede tardar 2-3 minutos)...");

        $zip = new ZipArchive;
        if ($zip->open($zipDest) !== TRUE) {
            throw new Exception("Error al abrir ZIP");
        }

        $zip->extractTo($dataDir);
        $zip->close();

        updateProgress($progressFile, 80, "Archivos extraídos");

        // Verificar archivos
        $padronFile = $dataDir . '/PADRON_COMPLETO.txt';
        $distelecFile = $dataDir . '/distelec.txt';

        $resultado = [];
        if (file_exists($padronFile)) {
            $resultado['padron'] = round(filesize($padronFile)/1024/1024, 2) . ' MB';
        }
        if (file_exists($distelecFile)) {
            $resultado['distelec'] = round(filesize($distelecFile)/1024, 2) . ' KB';
        }

        // Limpiar ZIP
        if (file_exists($zipDest)) {
            unlink($zipDest);
        }

        // Eliminar ZIP temporal solo si está en el sitio
        if (strpos($zipSource, __DIR__) === 0 && file_exists($zipSource)) {
            unlink($zipSource);
        }

        updateProgress($progressFile, 100, "Completado");

        echo json_encode([
            'success' => true,
            'message' => 'Extracción completada',
            'files' => $resultado
        ]);

    } catch (Exception $e) {
        updateProgress($progressFile, -1, 'Error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Buscar ZIP para mostrar información
$posiblesUbicaciones = [
    __DIR__ . '/padron_temp.zip',
    '/home/asegural/documents/padron_completo.zip',
    '/home/asegural/public_html/aseguralocr_uploads/padron_completo.zip',
    __DIR__ . '/../documents/padron_completo.zip',
    __DIR__ . '/padron_completo.zip'
];

$zipEncontrado = null;
$zipSize = 0;
foreach ($posiblesUbicaciones as $ubicacion) {
    if (file_exists($ubicacion)) {
        $zipEncontrado = $ubicacion;
        $zipSize = filesize($ubicacion);
        break;
    }
}

// Verificar si ya está extraído
$padronFile = $dataDir . '/PADRON_COMPLETO.txt';
$yaExtraido = file_exists($padronFile);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurar Padrón Electoral</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-lg p-6">
        <h1 class="text-3xl font-bold mb-6 text-gray-800">Configurar Padrón Electoral</h1>

        <!-- Estado del archivo -->
        <div class="mb-6 p-4 rounded <?= $zipEncontrado ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200' ?>">
            <?php if ($zipEncontrado): ?>
                <p class="text-green-700">✓ ZIP encontrado en:</p>
                <p class="text-sm text-gray-600 mt-1"><?= $zipEncontrado ?></p>
                <p class="text-sm text-gray-600">Tamaño: <?= round($zipSize/1024/1024, 2) ?> MB</p>
            <?php else: ?>
                <p class="text-red-700">✗ No se encontró el archivo ZIP</p>
                <p class="text-sm text-gray-600 mt-2">Ubicaciones verificadas:</p>
                <ul class="text-xs text-gray-500 mt-1">
                    <?php foreach ($posiblesUbicaciones as $loc): ?>
                        <li>- <?= $loc ?></li>
                    <?php endforeach; ?>
                </ul>
                <p class="text-sm text-gray-600 mt-2">
                    Descarga: <a href="https://www.tse.go.cr/zip/padron/padron_completo.zip" target="_blank" class="text-blue-600 underline">TSE</a>
                </p>
            <?php endif; ?>
        </div>

        <?php if ($yaExtraido): ?>
            <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded">
                <p class="text-blue-700">ℹ️ El padrón ya está extraído</p>
                <p class="text-sm text-gray-600 mt-1">
                    Archivo: <?= round(filesize($padronFile)/1024/1024, 2) ?> MB
                </p>
                <p class="text-sm text-gray-600 mt-2">
                    Continúa en: <a href="/admin/padron_importar.php" class="text-blue-600 underline">/admin/padron_importar.php</a>
                </p>
            </div>
        <?php endif; ?>

        <!-- Botón de extracción -->
        <button
            id="btnExtraer"
            onclick="iniciarExtraccion()"
            <?= !$zipEncontrado ? 'disabled' : '' ?>
            class="w-full bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
            <?= $yaExtraido ? 'Re-extraer Padrón' : 'Extraer Padrón' ?>
        </button>

        <!-- Barra de progreso -->
        <div id="progressContainer" class="hidden mt-6">
            <div class="w-full bg-gray-200 rounded-full h-8 relative overflow-hidden">
                <div id="progressBar" class="bg-blue-600 h-8 rounded-full transition-all duration-300" style="width: 0%"></div>
                <div id="progressText" class="absolute inset-0 flex items-center justify-center text-sm font-bold text-gray-700">0%</div>
            </div>
            <p id="progressMessage" class="text-center text-sm text-gray-600 mt-2">Iniciando...</p>
        </div>

        <!-- Resultado -->
        <div id="resultado" class="hidden mt-6 p-4 rounded"></div>
    </div>

    <script>
    let progressInterval = null;

    function iniciarExtraccion() {
        const btn = document.getElementById('btnExtraer');
        const container = document.getElementById('progressContainer');
        const resultado = document.getElementById('resultado');

        btn.disabled = true;
        btn.textContent = 'Extrayendo...';
        container.classList.remove('hidden');
        resultado.classList.add('hidden');

        // Iniciar extracción
        fetch('admin_padron.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'extraer=1'
        })
        .then(r => r.json())
        .then(data => {
            clearInterval(progressInterval);
            if (data.success) {
                actualizarProgreso(100, 'Completado');
                mostrarResultado(true, data.message, data.files);
            } else {
                mostrarResultado(false, data.message);
            }
        })
        .catch(err => {
            clearInterval(progressInterval);
            mostrarResultado(false, 'Error de conexión: ' + err.message);
        });

        // Polling para progreso
        progressInterval = setInterval(() => {
            fetch('admin_padron.php?progress=1')
            .then(r => r.json())
            .then(data => {
                actualizarProgreso(data.step, data.message);
            });
        }, 1000);
    }

    function actualizarProgreso(step, message) {
        const bar = document.getElementById('progressBar');
        const text = document.getElementById('progressText');
        const msg = document.getElementById('progressMessage');

        const percent = Math.max(0, Math.min(100, step));
        bar.style.width = percent + '%';
        text.textContent = percent + '%';
        msg.textContent = message || '';
    }

    function mostrarResultado(success, message, files) {
        const btn = document.getElementById('btnExtraer');
        const resultado = document.getElementById('resultado');

        btn.disabled = false;
        btn.textContent = 'Extraer Padrón';

        resultado.classList.remove('hidden', 'bg-green-50', 'bg-red-50', 'border-green-200', 'border-red-200');
        resultado.classList.add('border', success ? 'bg-green-50' : 'bg-red-50', success ? 'border-green-200' : 'border-red-200');

        let html = `<p class="${success ? 'text-green-700' : 'text-red-700'} font-bold">${success ? '✅' : '❌'} ${message}</p>`;

        if (success && files) {
            html += '<div class="mt-3 text-sm text-gray-700">';
            html += '<p>Archivos extraídos:</p><ul class="mt-1">';
            for (const [name, size] of Object.entries(files)) {
                html += `<li>- ${name}: ${size}</li>`;
            }
            html += '</ul>';
            html += '<p class="mt-3"><a href="/admin/padron_importar.php" class="text-blue-600 underline">Continuar: Importar a MySQL →</a></p>';
            html += '</div>';
        }

        resultado.innerHTML = html;
    }
    </script>
</body>
</html>
