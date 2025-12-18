<?php
/**
 * Importador del Padrón Electoral con AJAX
 * Evita timeouts de nginx
 */

session_start();
if (empty($_SESSION['admin_id'])) {
    die('Acceso denegado - Inicia sesión primero');
}

require_once __DIR__ . '/../includes/db.php';

$dataDir = __DIR__ . '/../data/padron';
$progressFile = $dataDir . '/import_progress.json';
$stopFile = $dataDir . '/stop_import.txt';

// Buscar archivo
$posiblesUbicaciones = [
    '/home/asegural/public_html/data/padron/PADRON_COMPLETO.txt',
    '/home/asegural/public_html/data/PADRON_COMPLETO.txt',
    __DIR__ . '/../data/padron/PADRON_COMPLETO.txt',
];

$padronFile = null;
foreach ($posiblesUbicaciones as $ubicacion) {
    if (file_exists($ubicacion)) {
        $padronFile = $ubicacion;
        break;
    }
}

// API para recrear la tabla
if (isset($_POST['recrear_tabla'])) {
    header('Content-Type: application/json');
    try {
        // Eliminar tabla existente
        $conn->query("DROP TABLE IF EXISTS padron_electoral");

        // Crear tabla con TODAS las columnas
        $createTable = "CREATE TABLE padron_electoral (
            id INT AUTO_INCREMENT PRIMARY KEY,
            cedula VARCHAR(20) NOT NULL UNIQUE,
            codelec VARCHAR(10),
            sitio_votacion VARCHAR(200),
            fecha_vencimiento DATE,
            junta VARCHAR(10),
            nombre VARCHAR(100),
            primer_apellido VARCHAR(50),
            segundo_apellido VARCHAR(50),
            nombre_completo VARCHAR(200),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_cedula (cedula),
            INDEX idx_nombre (nombre, primer_apellido, segundo_apellido),
            INDEX idx_nombre_completo (nombre_completo)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        if ($conn->query($createTable)) {
            echo json_encode(['success' => true, 'message' => 'Tabla recreada exitosamente con todas las columnas']);
        } else {
            throw new Exception($conn->error);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// API para detener la importación
if (isset($_POST['stop'])) {
    header('Content-Type: application/json');
    $dir = dirname($stopFile);
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    file_put_contents($stopFile, '1');

    // Matar procesos PHP relacionados con importación
    exec("pkill -f padron_importar_ajax.php 2>&1", $output, $ret);

    echo json_encode(['success' => true, 'message' => 'Señal de detención enviada']);
    exit;
}

// API para verificar progreso
if (isset($_GET['progress'])) {
    header('Content-Type: application/json');
    if (file_exists($progressFile)) {
        echo file_get_contents($progressFile);
    } else {
        echo json_encode(['step' => 0, 'message' => 'Iniciando...', 'count' => 0]);
    }
    exit;
}

function updateProgress($file, $step, $message, $count = 0) {
    $dir = dirname($file);
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    file_put_contents($file, json_encode([
        'step' => $step,
        'message' => $message,
        'count' => $count,
        'time' => date('H:i:s')
    ]));
}

// Procesar importación via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['importar'])) {
    header('Content-Type: application/json');
    set_time_limit(0);
    ini_set('memory_limit', '1024M');

    try {
        if (!$padronFile || !file_exists($padronFile)) {
            throw new Exception("Archivo PADRON_COMPLETO.txt no encontrado");
        }

        updateProgress($progressFile, 5, 'Creando tabla...', 0);

        // Crear tabla con TODAS las columnas del padrón TSE
        $createTable = "CREATE TABLE IF NOT EXISTS padron_electoral (
            id INT AUTO_INCREMENT PRIMARY KEY,
            cedula VARCHAR(20) NOT NULL UNIQUE,
            codelec VARCHAR(10),
            sitio_votacion VARCHAR(200),
            fecha_vencimiento DATE,
            junta VARCHAR(10),
            nombre VARCHAR(100),
            primer_apellido VARCHAR(50),
            segundo_apellido VARCHAR(50),
            nombre_completo VARCHAR(200),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_cedula (cedula),
            INDEX idx_nombre (nombre, primer_apellido, segundo_apellido),
            INDEX idx_nombre_completo (nombre_completo)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $conn->query($createTable);
        updateProgress($progressFile, 10, 'Tabla creada, limpiando datos previos...', 0);

        // Limpiar tabla
        if (isset($_POST['limpiar'])) {
            $conn->query("TRUNCATE TABLE padron_electoral");
        }

        updateProgress($progressFile, 15, 'Abriendo archivo...', 0);

        // Abrir archivo
        $handle = fopen($padronFile, 'r');
        if (!$handle) {
            throw new Exception("Error al abrir archivo");
        }

        updateProgress($progressFile, 20, 'Importando registros...', 0);

        $importados = 0;
        $errores = 0;
        $batchSize = 500; // Lotes pequeños para evitar timeouts
        $values = [];

        // Eliminar archivo de detención si existe (inicio limpio)
        if (file_exists($stopFile)) {
            @unlink($stopFile);
        }

        // Preparar statement para inserción por lotes
        while (($line = fgets($handle)) !== false) {
            // Verificar si se solicitó detener
            if (file_exists($stopFile)) {
                fclose($handle);
                @unlink($stopFile);
                updateProgress($progressFile, 95, '⛔ Importación detenida por el usuario. Registros importados: ' . number_format($importados), $importados);
                echo json_encode([
                    'success' => false,
                    'stopped' => true,
                    'message' => 'Importación detenida. Se importaron ' . number_format($importados) . ' registros.'
                ]);
                exit;
            }

            $line = trim($line);
            if (empty($line)) continue;

            $datos = str_getcsv($line);
            // Formato TSE: CEDULA,CODELEC,SITIO_VOTACION,FECHA_CADUCIDAD,JUNTA,NOMBRE,PRIMER_APELLIDO,SEGUNDO_APELLIDO
            if (count($datos) >= 8) {
                $cedula = $conn->real_escape_string(trim($datos[0]));
                $codelec = $conn->real_escape_string(trim($datos[1]));
                $sitio = $conn->real_escape_string(trim($datos[2]));
                $fecha = trim($datos[3]);
                $junta = $conn->real_escape_string(trim($datos[4]));
                $nombre = $conn->real_escape_string(trim($datos[5]));
                $primer_apellido = $conn->real_escape_string(trim($datos[6]));
                $segundo_apellido = $conn->real_escape_string(trim($datos[7]));

                // Generar nombre completo
                $nombre_completo = $conn->real_escape_string(trim("$nombre $primer_apellido $segundo_apellido"));

                // Convertir fecha (formato DDMMYYYY del TSE)
                $fechaSQL = 'NULL';
                if (strlen($fecha) == 8 && $fecha !== '00000000') {
                    $fechaSQL = "'" . substr($fecha, 4, 4) . '-' . substr($fecha, 2, 2) . '-' . substr($fecha, 0, 2) . "'";
                }

                $values[] = "('$cedula', '$codelec', '$sitio', $fechaSQL, '$junta', '$nombre', '$primer_apellido', '$segundo_apellido', '$nombre_completo')";

                // Insertar por lotes
                if (count($values) >= $batchSize) {
                    $sql = "INSERT IGNORE INTO padron_electoral
                            (cedula, codelec, sitio_votacion, fecha_vencimiento, junta, nombre, primer_apellido, segundo_apellido, nombre_completo)
                            VALUES " . implode(',', $values);

                    if ($conn->query($sql)) {
                        $importados += count($values);
                    } else {
                        $errores += count($values);
                    }

                    $values = [];

                    // Actualizar progreso cada 50,000 registros
                    if ($importados % 50000 == 0) {
                        $percent = 20 + min(75, ($importados / 3700000) * 75);
                        updateProgress($progressFile, $percent,
                            'Importados: ' . number_format($importados) . ' registros',
                            $importados);
                    }
                }
            }
        }

        // Insertar registros restantes
        if (!empty($values)) {
            $sql = "INSERT IGNORE INTO padron_electoral
                    (cedula, codelec, sitio_votacion, fecha_vencimiento, junta, nombre, primer_apellido, segundo_apellido, nombre_completo)
                    VALUES " . implode(',', $values);

            if ($conn->query($sql)) {
                $importados += count($values);
            }
        }

        fclose($handle);

        updateProgress($progressFile, 100, 'Importación completada', $importados);

        echo json_encode([
            'success' => true,
            'message' => 'Importación completada',
            'count' => $importados,
            'errors' => $errores
        ]);

    } catch (Exception $e) {
        updateProgress($progressFile, -1, 'Error: ' . $e->getMessage(), 0);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Verificar si ya está extraído
$yaImportado = false;
$totalRegistros = 0;

if ($padronFile) {
    $result = $conn->query("SELECT COUNT(*) as total FROM padron_electoral");
    if ($result) {
        $row = $result->fetch_assoc();
        $totalRegistros = $row['total'];
        $yaImportado = $totalRegistros > 0;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importar Padrón Electoral</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-lg p-6">
        <h1 class="text-3xl font-bold mb-6 text-gray-800">Importar Padrón Electoral (AJAX)</h1>

        <!-- Estado del archivo -->
        <div class="mb-6 p-4 rounded <?= $padronFile ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200' ?>">
            <?php if ($padronFile): ?>
                <p class="text-green-700">✓ Archivo encontrado</p>
                <p class="text-sm text-gray-600 mt-1"><?= basename($padronFile) ?> (<?= round(filesize($padronFile)/1024/1024, 2) ?> MB)</p>
                <p class="text-xs text-gray-500 mt-1"><?= $padronFile ?></p>
            <?php else: ?>
                <p class="text-red-700">✗ Archivo no encontrado</p>
            <?php endif; ?>
        </div>

        <?php if ($yaImportado): ?>
            <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded">
                <p class="text-blue-700">ℹ️ Registros en BD: <strong><?= number_format($totalRegistros) ?></strong></p>
                <p class="text-sm text-gray-600 mt-1">Puedes re-importar marcando "Limpiar tabla"</p>
            </div>
        <?php endif; ?>

        <!-- Formulario -->
        <div class="mb-6">
            <button
                id="btnRecrear"
                onclick="recrearTabla()"
                class="w-full bg-orange-600 text-white px-6 py-3 rounded-lg hover:bg-orange-700 transition mb-4">
                🔄 Recrear Tabla (Elimina estructura vieja y crea nueva con todas las columnas)
            </button>

            <label class="flex items-center mb-4">
                <input type="checkbox" id="limpiar" class="mr-2">
                <span class="text-sm">Limpiar tabla antes de importar (elimina datos existentes)</span>
            </label>

            <button
                id="btnImportar"
                onclick="iniciarImportacion()"
                <?= !$padronFile ? 'disabled' : '' ?>
                class="w-full bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                Importar a MySQL
            </button>

            <button
                id="btnDetener"
                onclick="detenerImportacion()"
                class="hidden w-full bg-red-600 text-white px-6 py-3 rounded-lg hover:bg-red-700 transition mt-3">
                ⛔ Detener Importación
            </button>
        </div>

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

        <div class="mt-6">
            <a href="/admin/dashboard.php" class="text-blue-600 hover:underline">← Volver al Dashboard</a>
        </div>
    </div>

    <script>
    let progressInterval = null;

    function iniciarImportacion() {
        if (!confirm('¿Importar padrón a MySQL? Esto puede tardar varios minutos.')) return;

        const btn = document.getElementById('btnImportar');
        const btnDetener = document.getElementById('btnDetener');
        const container = document.getElementById('progressContainer');
        const resultado = document.getElementById('resultado');
        const limpiar = document.getElementById('limpiar').checked;

        btn.disabled = true;
        btn.textContent = 'Importando...';
        btn.classList.add('hidden');
        btnDetener.classList.remove('hidden');
        container.classList.remove('hidden');
        resultado.classList.add('hidden');

        // Iniciar importación
        const formData = new FormData();
        formData.append('importar', '1');
        if (limpiar) formData.append('limpiar', '1');

        fetch('padron_importar_ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            clearInterval(progressInterval);
            if (data.success) {
                actualizarProgreso(100, 'Completado: ' + number_format(data.count) + ' registros');
                mostrarResultado(true, data.message, data.count);
            } else if (data.stopped) {
                // Importación detenida por el usuario
                mostrarResultado(false, data.message);
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
            fetch('padron_importar_ajax.php?progress=1')
            .then(r => r.json())
            .then(data => {
                actualizarProgreso(data.step, data.message);
            });
        }, 2000);
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

    function mostrarResultado(success, message, count) {
        const btn = document.getElementById('btnImportar');
        const btnDetener = document.getElementById('btnDetener');
        const resultado = document.getElementById('resultado');

        btn.disabled = false;
        btn.textContent = 'Importar a MySQL';
        btn.classList.remove('hidden');
        btnDetener.classList.add('hidden');

        resultado.classList.remove('hidden', 'bg-green-50', 'bg-red-50', 'border-green-200', 'border-red-200');
        resultado.classList.add('border', success ? 'bg-green-50' : 'bg-red-50', success ? 'border-green-200' : 'border-red-200');

        let html = `<p class="${success ? 'text-green-700' : 'text-red-700'} font-bold">${success ? '✅' : '❌'} ${message}</p>`;

        if (success && count) {
            html += `<p class="text-sm text-gray-700 mt-2">Total de registros: <strong>${number_format(count)}</strong></p>`;
            html += '<p class="text-sm text-gray-600 mt-2">El padrón está listo para usar en los formularios.</p>';
        }

        resultado.innerHTML = html;
    }

    function detenerImportacion() {
        if (!confirm('¿Estás seguro de detener la importación?\n\nSe guardarán los registros importados hasta ahora.')) {
            return;
        }

        const btnDetener = document.getElementById('btnDetener');
        btnDetener.disabled = true;
        btnDetener.textContent = 'Deteniendo...';

        const formData = new FormData();
        formData.append('stop', '1');

        fetch('padron_importar_ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            clearInterval(progressInterval);
            mostrarResultado(false, '⛔ Importación detenida. Los registros importados se guardaron correctamente.');
        })
        .catch(err => {
            clearInterval(progressInterval);
            mostrarResultado(false, 'Error al detener: ' + err.message);
        });
    }

    function recrearTabla() {
        if (!confirm('¿RECREAR la tabla padron_electoral?\n\nEsto eliminará la tabla actual y la creará de nuevo con TODAS las columnas del TSE.\n\n⚠️ Se perderán todos los datos existentes.')) {
            return;
        }

        const btnRecrear = document.getElementById('btnRecrear');
        const resultado = document.getElementById('resultado');

        btnRecrear.disabled = true;
        btnRecrear.textContent = '🔄 Recreando tabla...';
        resultado.classList.add('hidden');

        const formData = new FormData();
        formData.append('recrear_tabla', '1');

        fetch('padron_importar_ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            btnRecrear.disabled = false;
            btnRecrear.textContent = '🔄 Recrear Tabla (Elimina estructura vieja y crea nueva con todas las columnas)';

            if (data.success) {
                mostrarResultado(true, '✅ ' + data.message + '\n\nAhora puedes importar el padrón.');
            } else {
                mostrarResultado(false, data.message);
            }
        })
        .catch(err => {
            btnRecrear.disabled = false;
            btnRecrear.textContent = '🔄 Recrear Tabla (Elimina estructura vieja y crea nueva con todas las columnas)';
            mostrarResultado(false, 'Error: ' + err.message);
        });
    }

    function number_format(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
    </script>
</body>
</html>
