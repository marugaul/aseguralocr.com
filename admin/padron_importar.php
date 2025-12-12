<?php
/**
 * Importador de Padrón Electoral del TSE
 * Con barras de progreso para descarga e importación
 */

session_start();

// Solo admin
if (empty($_SESSION['admin_id'])) {
    header('Location: /admin/login.php');
    exit;
}

require_once __DIR__ . '/../includes/db.php';

// Configuración
set_time_limit(0);
ini_set('memory_limit', '512M');

$dataDir = __DIR__ . '/../data/padron';
$zipUrl = 'https://www.tse.go.cr/zip/padron/padron_completo.zip';
$zipFile = $dataDir . '/padron_completo.zip';
$padronFile = $dataDir . '/PADRON_COMPLETO.txt';
$distritosFile = $dataDir . '/distelec.txt';
$progressFile = $dataDir . '/progress.json';

// API para verificar progreso
if (isset($_GET['progress'])) {
    header('Content-Type: application/json');
    if (file_exists($progressFile)) {
        echo file_get_contents($progressFile);
    } else {
        echo json_encode(['percent' => 0, 'message' => 'Iniciando...']);
    }
    exit;
}

// API para verificar tamaño de descarga
if (isset($_GET['download_size'])) {
    header('Content-Type: application/json');
    $size = file_exists($zipFile) ? filesize($zipFile) : 0;
    echo json_encode(['size' => $size]);
    exit;
}

function updateProgress($file, $percent, $message) {
    file_put_contents($file, json_encode([
        'percent' => $percent,
        'message' => $message,
        'time' => date('H:i:s')
    ]));
}

// Procesar acción AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    header('Content-Type: application/json');
    global $pdo;

    try {
        if ($_POST['accion'] === 'descargar') {
            // Crear directorio si no existe
            if (!is_dir($dataDir)) {
                mkdir($dataDir, 0755, true);
            }

            // Limpiar archivo anterior
            if (file_exists($zipFile)) {
                unlink($zipFile);
            }

            updateProgress($progressFile, 5, 'Conectando con TSE...');

            // Descargar usando streaming a archivo (no usa memoria)
            $fp = fopen($zipFile, 'w');
            if (!$fp) {
                throw new Exception("No se puede crear archivo destino");
            }

            $ch = curl_init($zipUrl);
            curl_setopt_array($ch, [
                CURLOPT_FILE => $fp,  // Escribir directo a archivo
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 600,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_BUFFERSIZE => 128000
            ]);

            updateProgress($progressFile, 10, 'Descargando archivo (~70MB)... Espere 1-2 minutos');

            $success = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            $downloadedSize = curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD);
            curl_close($ch);
            fclose($fp);

            if (!$success || $httpCode !== 200) {
                if (file_exists($zipFile)) unlink($zipFile);
                throw new Exception("Error descargando: HTTP $httpCode - $error");
            }

            // Verificar tamaño
            $fileSize = filesize($zipFile);
            if ($fileSize < 1000000) {
                unlink($zipFile);
                throw new Exception("Archivo descargado muy pequeño: " . round($fileSize/1024) . "KB");
            }

            updateProgress($progressFile, 80, 'Descargado ' . round($fileSize/1024/1024, 1) . 'MB. Descomprimiendo...');

            // Descomprimir
            $zip = new ZipArchive();
            if ($zip->open($zipFile) === true) {
                $zip->extractTo($dataDir);
                $zip->close();
                updateProgress($progressFile, 100, 'Descarga completada');
                echo json_encode(['success' => true, 'message' => 'Descarga completada: ' . round($fileSize/1024/1024, 1) . 'MB']);
            } else {
                throw new Exception("Error descomprimiendo archivo ZIP");
            }

        } elseif ($_POST['accion'] === 'importar') {
            if (!file_exists($padronFile)) {
                throw new Exception("Archivo PADRON_COMPLETO.txt no encontrado. Descargue primero.");
            }

            $startTime = time();
            updateProgress($progressFile, 0, 'Iniciando importación...');

            // Contar líneas totales
            $totalLines = 0;
            $fp = fopen($padronFile, 'r');
            while (!feof($fp)) {
                fgets($fp);
                $totalLines++;
            }
            fclose($fp);

            updateProgress($progressFile, 2, "Preparando $totalLines registros...");

            // Registrar inicio
            $pdo->exec("INSERT INTO padron_actualizaciones (estado, mensaje) VALUES ('iniciado', 'Importación iniciada')");
            $importId = $pdo->lastInsertId();

            // Importar distritos primero
            updateProgress($progressFile, 5, 'Importando distritos...');
            if (file_exists($distritosFile)) {
                $pdo->exec("TRUNCATE TABLE padron_distritos");
                $stmt = $pdo->prepare("INSERT INTO padron_distritos (codelec, provincia, canton, distrito) VALUES (?, ?, ?, ?)");

                $handle = fopen($distritosFile, 'r');
                while (($line = fgets($handle)) !== false) {
                    $parts = str_getcsv($line);
                    if (count($parts) >= 4) {
                        $stmt->execute([
                            trim($parts[0]),
                            trim($parts[1]),
                            trim($parts[2]),
                            trim($parts[3])
                        ]);
                    }
                }
                fclose($handle);
            }

            updateProgress($progressFile, 8, 'Limpiando tabla de padrón...');

            // Importar padrón
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            $pdo->exec("TRUNCATE TABLE padron_electoral");

            $batchSize = 5000;
            $count = 0;
            $batch = [];
            $lastUpdate = 0;

            $handle = fopen($padronFile, 'r');

            while (($line = fgets($handle)) !== false) {
                $parts = str_getcsv($line);
                if (count($parts) >= 8) {
                    $fechaRaw = trim($parts[3]);
                    $fecha = null;
                    if (strlen($fechaRaw) === 8 && $fechaRaw !== '00000000') {
                        $fecha = substr($fechaRaw, 0, 4) . '-' . substr($fechaRaw, 4, 2) . '-' . substr($fechaRaw, 6, 2);
                    }

                    $batch[] = [
                        trim($parts[0]),
                        trim($parts[1]),
                        $fecha,
                        trim($parts[4]),
                        trim($parts[5]),
                        trim($parts[6]),
                        trim($parts[7])
                    ];

                    if (count($batch) >= $batchSize) {
                        insertBatch($pdo, $batch);
                        $count += count($batch);
                        $batch = [];

                        // Actualizar progreso cada 50,000 registros
                        if ($count - $lastUpdate >= 50000) {
                            $percent = 10 + round(($count / $totalLines) * 88);
                            updateProgress($progressFile, $percent, 'Importando: ' . number_format($count) . ' / ' . number_format($totalLines) . ' registros');
                            $lastUpdate = $count;
                        }
                    }
                }
            }

            // Insertar registros restantes
            if (!empty($batch)) {
                insertBatch($pdo, $batch);
                $count += count($batch);
            }

            fclose($handle);
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

            $duration = time() - $startTime;

            // Actualizar registro
            $pdo->prepare("UPDATE padron_actualizaciones SET estado = 'completado', registros_importados = ?, duracion_segundos = ?, mensaje = ? WHERE id = ?")
                ->execute([$count, $duration, "Importados $count registros en $duration segundos", $importId]);

            updateProgress($progressFile, 100, 'Importación completada');
            echo json_encode(['success' => true, 'message' => "Importación completada: " . number_format($count) . " registros en $duration segundos"]);
        }
    } catch (Exception $e) {
        updateProgress($progressFile, -1, 'Error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

function insertBatch($pdo, $batch) {
    if (empty($batch)) return;

    $placeholders = [];
    $values = [];

    foreach ($batch as $row) {
        $placeholders[] = "(?, ?, ?, ?, ?, ?, ?)";
        $values = array_merge($values, $row);
    }

    $sql = "INSERT IGNORE INTO padron_electoral (cedula, codelec, fecha_vencimiento, junta, nombre, primer_apellido, segundo_apellido) VALUES " . implode(',', $placeholders);
    $pdo->prepare($sql)->execute($values);
}

// Obtener estadísticas
global $pdo;
$stats = null;
try {
    $tableExists = $pdo->query("SHOW TABLES LIKE 'padron_electoral'")->rowCount() > 0;

    if ($tableExists) {
        $stats = [
            'total' => $pdo->query("SELECT COUNT(*) FROM padron_electoral")->fetchColumn(),
            'ultima_actualizacion' => $pdo->query("SELECT MAX(fecha_actualizacion) FROM padron_actualizaciones WHERE estado = 'completado'")->fetchColumn()
        ];
    }
} catch (Exception $e) {
    // Tabla no existe aún
}

// Verificar archivos
$archivoExiste = file_exists($padronFile);
$archivoTamano = $archivoExiste ? round(filesize($padronFile) / 1024 / 1024, 1) : 0;

$pageTitle = 'Padrón Electoral';
include __DIR__ . '/includes/header.php';
?>

<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    .progress-container {
        display: none;
        margin-top: 1rem;
    }
    .progress-bar {
        width: 100%;
        height: 30px;
        background: #e5e7eb;
        border-radius: 15px;
        overflow: hidden;
        position: relative;
    }
    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #3b82f6, #2563eb);
        border-radius: 15px;
        transition: width 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .progress-text {
        position: absolute;
        width: 100%;
        text-align: center;
        line-height: 30px;
        font-weight: bold;
        color: #1f2937;
        font-size: 14px;
    }
    .progress-message {
        margin-top: 0.5rem;
        text-align: center;
        color: #4b5563;
        font-size: 14px;
    }
    .btn-action {
        transition: all 0.3s;
    }
    .btn-action:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
</style>

<h1 class="text-3xl font-bold text-gray-800 mb-6">
    <i class="fas fa-database mr-3 text-blue-600"></i>Padrón Electoral TSE
</h1>

<!-- Estadísticas -->
<div class="grid md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="text-3xl font-bold text-blue-600"><?= $stats ? number_format($stats['total']) : '0' ?></div>
        <div class="text-gray-600">Registros en BD</div>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <div class="text-lg font-bold text-gray-800"><?= $stats['ultima_actualizacion'] ?? 'Nunca' ?></div>
        <div class="text-gray-600">Última actualización</div>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <div class="text-lg font-bold <?= $archivoExiste ? 'text-green-600' : 'text-red-600' ?>">
            <?= $archivoExiste ? "$archivoTamano MB" : 'No descargado' ?>
        </div>
        <div class="text-gray-600">Archivo local</div>
    </div>
</div>

<!-- Acciones -->
<div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-xl font-bold text-gray-800 mb-4">Acciones</h2>

    <div class="grid md:grid-cols-2 gap-4">
        <!-- Descargar -->
        <div>
            <button id="btnDescargar" onclick="iniciarDescarga()" class="btn-action w-full bg-blue-600 text-white px-6 py-4 rounded-lg hover:bg-blue-700 transition">
                <i class="fas fa-download mr-2"></i>Descargar del TSE
                <div class="text-sm opacity-75 mt-1">Descarga archivo actualizado (~70MB)</div>
            </button>
            <div id="progressDescargar" class="progress-container">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 0%"></div>
                    <div class="progress-text">0%</div>
                </div>
                <div class="progress-message">Iniciando...</div>
            </div>
        </div>

        <!-- Importar -->
        <div>
            <button id="btnImportar" onclick="iniciarImportacion()" class="btn-action w-full bg-green-600 text-white px-6 py-4 rounded-lg hover:bg-green-700 transition <?= !$archivoExiste ? 'opacity-50 cursor-not-allowed' : '' ?>" <?= !$archivoExiste ? 'disabled' : '' ?>>
                <i class="fas fa-database mr-2"></i>Importar a MySQL
                <div class="text-sm opacity-75 mt-1">Procesa ~3.7M registros</div>
            </button>
            <div id="progressImportar" class="progress-container">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 0%"></div>
                    <div class="progress-text">0%</div>
                </div>
                <div class="progress-message">Iniciando...</div>
            </div>
        </div>
    </div>

    <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
        <h3 class="font-bold text-yellow-800 mb-2"><i class="fas fa-info-circle mr-2"></i>Información</h3>
        <ul class="text-sm text-yellow-700 space-y-1">
            <li>El padrón contiene datos públicos del TSE (nombre, cédula, ubicación electoral)</li>
            <li>Se recomienda actualizar mensualmente</li>
            <li>La importación puede tardar 2-5 minutos dependiendo del servidor</li>
        </ul>
    </div>
</div>

<!-- Uso en formularios -->
<div class="bg-white rounded-lg shadow p-6 mt-6">
    <h2 class="text-xl font-bold text-gray-800 mb-4">Uso en Formularios</h2>
    <p class="text-gray-600 mb-4">Una vez importado el padrón, los formularios pueden autocompletar el nombre del cliente al digitar la cédula.</p>
    <code class="block bg-gray-100 p-4 rounded text-sm">
        GET /api/padron.php?cedula=123456789<br>
        Respuesta: {"cedula":"123456789","nombre":"JUAN","apellido1":"PEREZ","apellido2":"GOMEZ","nombre_completo":"JUAN PEREZ GOMEZ"}
    </code>
</div>

<script>
let progressInterval = null;

function iniciarDescarga() {
    if (!confirm('¿Descargar padrón del TSE? (~70MB)')) return;

    const btn = document.getElementById('btnDescargar');
    const progress = document.getElementById('progressDescargar');

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Descargando...';
    progress.style.display = 'block';

    // Iniciar descarga via AJAX
    fetch('padron_importar.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'accion=descargar'
    })
    .then(r => r.json())
    .then(data => {
        clearInterval(progressInterval);
        if (data.success) {
            actualizarProgreso('progressDescargar', 100, data.message);
            btn.innerHTML = '<i class="fas fa-check mr-2"></i>Completado';
            btn.classList.remove('bg-blue-600');
            btn.classList.add('bg-green-600');
            setTimeout(() => location.reload(), 2000);
        } else {
            actualizarProgreso('progressDescargar', 0, 'Error: ' + data.message);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-download mr-2"></i>Reintentar';
        }
    })
    .catch(err => {
        clearInterval(progressInterval);
        actualizarProgreso('progressDescargar', 0, 'Error de conexión');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-download mr-2"></i>Reintentar';
    });

    // Polling para progreso
    progressInterval = setInterval(() => {
        fetch('padron_importar.php?progress=1')
        .then(r => r.json())
        .then(data => {
            actualizarProgreso('progressDescargar', data.percent, data.message);
        });
    }, 500);
}

function iniciarImportacion() {
    if (!confirm('¿Importar padrón a MySQL? Esto puede tardar varios minutos.')) return;

    const btn = document.getElementById('btnImportar');
    const progress = document.getElementById('progressImportar');

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Importando...';
    progress.style.display = 'block';

    // Iniciar importación via AJAX
    fetch('padron_importar.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'accion=importar'
    })
    .then(r => r.json())
    .then(data => {
        clearInterval(progressInterval);
        if (data.success) {
            actualizarProgreso('progressImportar', 100, data.message);
            btn.innerHTML = '<i class="fas fa-check mr-2"></i>Completado';
            btn.classList.remove('bg-green-600');
            btn.classList.add('bg-blue-600');
            setTimeout(() => location.reload(), 2000);
        } else {
            actualizarProgreso('progressImportar', 0, 'Error: ' + data.message);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-database mr-2"></i>Reintentar';
        }
    })
    .catch(err => {
        clearInterval(progressInterval);
        actualizarProgreso('progressImportar', 0, 'Error de conexión');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-database mr-2"></i>Reintentar';
    });

    // Polling para progreso
    progressInterval = setInterval(() => {
        fetch('padron_importar.php?progress=1')
        .then(r => r.json())
        .then(data => {
            actualizarProgreso('progressImportar', data.percent, data.message);
        });
    }, 1000);
}

function actualizarProgreso(containerId, percent, message) {
    const container = document.getElementById(containerId);
    const fill = container.querySelector('.progress-fill');
    const text = container.querySelector('.progress-text');
    const msg = container.querySelector('.progress-message');

    percent = Math.max(0, Math.min(100, percent));
    fill.style.width = percent + '%';
    text.textContent = percent + '%';
    msg.textContent = message || '';

    // Cambiar color si hay error
    if (percent < 0 || message?.includes('Error')) {
        fill.style.background = 'linear-gradient(90deg, #ef4444, #dc2626)';
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
