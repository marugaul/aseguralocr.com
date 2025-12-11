<?php
/**
 * Importador de Padrón Electoral del TSE
 * Descarga y procesa el padrón completo
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

$mensaje = '';
$tipo = 'info';
$stats = null;

// Procesar acción
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    global $pdo;
    try {
        if ($_POST['accion'] === 'descargar') {
            // Crear directorio si no existe
            if (!is_dir($dataDir)) {
                mkdir($dataDir, 0755, true);
            }

            // Descargar ZIP
            $mensaje = "Descargando padrón del TSE...\n";
            $ch = curl_init($zipUrl);
            $fp = fopen($zipFile, 'w');
            curl_setopt_array($ch, [
                CURLOPT_FILE => $fp,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 300,
                CURLOPT_USERAGENT => 'Mozilla/5.0 AseguraLoCR'
            ]);
            $success = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            fclose($fp);

            if (!$success || $httpCode !== 200) {
                throw new Exception("Error descargando archivo. HTTP: $httpCode");
            }

            // Descomprimir
            $zip = new ZipArchive();
            if ($zip->open($zipFile) === true) {
                $zip->extractTo($dataDir);
                $zip->close();
                $mensaje = "Descarga completada. Archivos extraídos correctamente.";
                $tipo = 'success';
            } else {
                throw new Exception("Error descomprimiendo archivo ZIP");
            }

        } elseif ($_POST['accion'] === 'importar') {
            if (!file_exists($padronFile)) {
                throw new Exception("Archivo PADRON_COMPLETO.txt no encontrado. Descargue primero.");
            }

            $startTime = time();

            // Registrar inicio
            $pdo->exec("INSERT INTO padron_actualizaciones (estado, mensaje) VALUES ('iniciado', 'Importación iniciada')");
            $importId = $pdo->lastInsertId();

            // Crear tablas si no existen
            $sqlFile = __DIR__ . '/../sql/padron_electoral.sql';
            if (file_exists($sqlFile)) {
                $sql = file_get_contents($sqlFile);
                $pdo->exec($sql);
            }

            // Importar distritos primero
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

            // Importar padrón (en bloques para mejor rendimiento)
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            $pdo->exec("TRUNCATE TABLE padron_electoral");

            $batchSize = 5000;
            $count = 0;
            $batch = [];

            $handle = fopen($padronFile, 'r');

            while (($line = fgets($handle)) !== false) {
                $parts = str_getcsv($line);
                if (count($parts) >= 8) {
                    // Parse fecha YYYYMMDD
                    $fechaRaw = trim($parts[3]);
                    $fecha = null;
                    if (strlen($fechaRaw) === 8 && $fechaRaw !== '00000000') {
                        $fecha = substr($fechaRaw, 0, 4) . '-' . substr($fechaRaw, 4, 2) . '-' . substr($fechaRaw, 6, 2);
                    }

                    $batch[] = [
                        trim($parts[0]),           // cedula
                        trim($parts[1]),           // codelec
                        $fecha,                    // fecha_vencimiento
                        trim($parts[4]),           // junta
                        trim($parts[5]),           // nombre
                        trim($parts[6]),           // primer_apellido
                        trim($parts[7])            // segundo_apellido
                    ];

                    if (count($batch) >= $batchSize) {
                        insertBatch($pdo, $batch);
                        $count += count($batch);
                        $batch = [];
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

            $mensaje = "Importación completada: $count registros en $duration segundos";
            $tipo = 'success';
        }
    } catch (Exception $e) {
        $mensaje = "Error: " . $e->getMessage();
        $tipo = 'error';
    }
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
try {
    // Verificar si la tabla existe
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importar Padrón Electoral - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">
    <?php include __DIR__ . '/includes/header.php'; ?>

    <main class="container mx-auto px-4 py-8 max-w-4xl">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">
            <i class="fas fa-database mr-3 text-blue-600"></i>Padrón Electoral TSE
        </h1>

        <?php if ($mensaje): ?>
        <div class="mb-6 p-4 rounded-lg <?= $tipo === 'success' ? 'bg-green-100 text-green-800' : ($tipo === 'error' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800') ?>">
            <i class="fas fa-<?= $tipo === 'success' ? 'check-circle' : ($tipo === 'error' ? 'exclamation-circle' : 'info-circle') ?> mr-2"></i>
            <?= htmlspecialchars($mensaje) ?>
        </div>
        <?php endif; ?>

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
                <form method="POST" onsubmit="return confirm('¿Descargar padrón del TSE? (~70MB)')">
                    <input type="hidden" name="accion" value="descargar">
                    <button type="submit" class="w-full bg-blue-600 text-white px-6 py-4 rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-download mr-2"></i>Descargar del TSE
                        <div class="text-sm opacity-75 mt-1">Descarga archivo actualizado (~70MB)</div>
                    </button>
                </form>

                <form method="POST" onsubmit="this.querySelector('button').disabled=true; this.querySelector('button').innerHTML='<i class=\'fas fa-spinner fa-spin mr-2\'></i>Importando...'; return confirm('¿Importar padrón a MySQL? Esto puede tardar varios minutos.')">
                    <input type="hidden" name="accion" value="importar">
                    <button type="submit" class="w-full bg-green-600 text-white px-6 py-4 rounded-lg hover:bg-green-700 transition <?= !$archivoExiste ? 'opacity-50 cursor-not-allowed' : '' ?>" <?= !$archivoExiste ? 'disabled' : '' ?>>
                        <i class="fas fa-database mr-2"></i>Importar a MySQL
                        <div class="text-sm opacity-75 mt-1">Procesa ~3.7M registros</div>
                    </button>
                </form>
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
    </main>
</body>
</html>
