<?php
/**
 * Importador del Padrón Electoral a MySQL
 * Importa PADRON_COMPLETO.txt a la base de datos
 */

session_start();
if (empty($_SESSION['admin_id'])) {
    die('Acceso denegado - Inicia sesión primero');
}

require_once __DIR__ . '/../includes/db.php';

set_time_limit(0);
ini_set('memory_limit', '512M');

// Buscar archivo en múltiples ubicaciones posibles
$posiblesUbicaciones = [
    '/home/asegural/public_html/data/padron/PADRON_COMPLETO.txt',
    '/home/asegural/public_html/data/PADRON_COMPLETO.txt',
    __DIR__ . '/../data/padron/PADRON_COMPLETO.txt',
    __DIR__ . '/../data/PADRON_COMPLETO.txt',
    __DIR__ . '/../../data/padron/PADRON_COMPLETO.txt',
    __DIR__ . '/../../data/PADRON_COMPLETO.txt'
];

$padronFile = null;
foreach ($posiblesUbicaciones as $ubicacion) {
    if (file_exists($ubicacion)) {
        $padronFile = $ubicacion;
        break;
    }
}

// Para distelec buscar en el mismo directorio que padron
$distelecFile = null;
if ($padronFile) {
    $dir = dirname($padronFile);
    $distelecFile = $dir . '/distelec.txt';
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
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-lg p-6">
            <h1 class="text-3xl font-bold mb-6 text-gray-800">Importar Padrón Electoral</h1>

            <?php
            // Verificar archivos
            echo "<div class='mb-6'>";
            echo "<h2 class='text-xl font-semibold mb-3'>Estado de Archivos:</h2>";

            if ($padronFile && file_exists($padronFile)) {
                $size = round(filesize($padronFile) / 1024 / 1024, 2);
                echo "<p class='text-green-600'>✓ Archivo encontrado: PADRON_COMPLETO.txt ({$size} MB)</p>";
                echo "<p class='text-xs text-gray-500 mt-1'>Ubicación: $padronFile</p>";
            } else {
                echo "<p class='text-red-600'>✗ No se encontró PADRON_COMPLETO.txt</p>";
                echo "<p class='text-sm text-gray-600 mt-2'>Ubicaciones verificadas:</p>";
                echo "<ul class='text-xs text-gray-500 ml-4'>";
                foreach ($posiblesUbicaciones as $loc) {
                    echo "<li>- $loc</li>";
                }
                echo "</ul>";
                echo "<p class='text-gray-600 mt-2'>Primero ejecuta: <a href='/admin_padron.php' class='text-blue-600 underline'>/admin_padron.php</a></p>";
                echo "</div></div></body></html>";
                exit;
            }

            if (file_exists($distelecFile)) {
                echo "<p class='text-green-600'>✓ Archivo encontrado: distelec.txt</p>";
            }
            echo "</div>";

            // Procesar importación si se solicita
            if (isset($_POST['importar'])) {
                echo "<div class='bg-blue-50 border border-blue-200 rounded p-4 mb-4'>";
                echo "<h2 class='text-xl font-semibold mb-3'>Importando...</h2>";
                echo "<pre class='text-sm'>";

                // Crear tabla si no existe
                $createTable = "CREATE TABLE IF NOT EXISTS padron_electoral (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    cedula VARCHAR(20) NOT NULL UNIQUE,
                    codigo_electoral VARCHAR(10),
                    fecha_vencimiento DATE,
                    junta VARCHAR(10),
                    nombre VARCHAR(100),
                    apellido1 VARCHAR(50),
                    apellido2 VARCHAR(50),
                    provincia VARCHAR(50),
                    canton VARCHAR(50),
                    distrito VARCHAR(50),
                    INDEX idx_cedula (cedula),
                    INDEX idx_nombre (nombre, apellido1, apellido2)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

                if ($conn->query($createTable)) {
                    echo "✓ Tabla 'padron_electoral' verificada/creada\n\n";
                } else {
                    die("Error al crear tabla: " . $conn->error);
                }

                // Truncar tabla para reimportar
                if (isset($_POST['limpiar'])) {
                    $conn->query("TRUNCATE TABLE padron_electoral");
                    echo "✓ Tabla limpiada\n\n";
                }

                // Contar registros actuales
                $result = $conn->query("SELECT COUNT(*) as total FROM padron_electoral");
                $row = $result->fetch_assoc();
                $registrosExistentes = $row['total'];
                echo "Registros existentes: " . number_format($registrosExistentes) . "\n\n";

                // Importar por lotes
                echo "⏳ Importando archivo (esto puede tardar varios minutos)...\n\n";

                $handle = fopen($padronFile, 'r');
                if (!$handle) {
                    die("Error al abrir archivo");
                }

                $importados = 0;
                $errores = 0;
                $lote = [];
                $batchSize = 1000;

                // Preparar statement
                $stmt = $conn->prepare("INSERT IGNORE INTO padron_electoral
                    (cedula, codigo_electoral, fecha_vencimiento, junta, nombre, apellido1, apellido2)
                    VALUES (?, ?, ?, ?, ?, ?, ?)");

                while (($line = fgets($handle)) !== false) {
                    $line = trim($line);
                    if (empty($line)) continue;

                    // Formato del TSE: CEDULA,COD_ELECTORAL,FECHA_VENC,JUNTA,NOMBRE,APELLIDO1,APELLIDO2
                    $datos = str_getcsv($line);

                    if (count($datos) >= 7) {
                        $cedula = trim($datos[0]);
                        $codigo = trim($datos[1]);
                        $fecha = trim($datos[2]);
                        $junta = trim($datos[3]);
                        $nombre = trim($datos[4]);
                        $apellido1 = trim($datos[5]);
                        $apellido2 = trim($datos[6]);

                        // Convertir fecha (formato DDMMYYYY)
                        if (strlen($fecha) == 8) {
                            $fechaSQL = substr($fecha, 4, 4) . '-' . substr($fecha, 2, 2) . '-' . substr($fecha, 0, 2);
                        } else {
                            $fechaSQL = null;
                        }

                        $stmt->bind_param('sssssss', $cedula, $codigo, $fechaSQL, $junta, $nombre, $apellido1, $apellido2);

                        if ($stmt->execute()) {
                            $importados++;
                        } else {
                            $errores++;
                        }

                        // Mostrar progreso cada 100,000 registros
                        if ($importados % 100000 == 0) {
                            echo "✓ " . number_format($importados) . " registros procesados...\n";
                            flush();
                            ob_flush();
                        }
                    }
                }

                fclose($handle);
                $stmt->close();

                echo "\n<strong>✅ IMPORTACIÓN COMPLETADA</strong>\n\n";
                echo "Registros importados: " . number_format($importados) . "\n";
                echo "Registros con error/duplicados: " . number_format($errores) . "\n";

                // Contar total final
                $result = $conn->query("SELECT COUNT(*) as total FROM padron_electoral");
                $row = $result->fetch_assoc();
                echo "Total en base de datos: " . number_format($row['total']) . "\n";

                echo "</pre></div>";
            }
            ?>

            <!-- Formulario de importación -->
            <form method="POST" class="mt-6">
                <div class="mb-4">
                    <label class="flex items-center">
                        <input type="checkbox" name="limpiar" value="1" class="mr-2">
                        <span class="text-sm">Limpiar tabla antes de importar (elimina datos existentes)</span>
                    </label>
                </div>

                <button type="submit" name="importar" value="1"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded">
                    Importar a MySQL
                </button>

                <p class="text-sm text-gray-600 mt-3">
                    ⚠️ Este proceso puede tardar 15-30 minutos dependiendo del servidor.
                </p>
            </form>

            <!-- Verificar registros -->
            <div class="mt-8 p-4 bg-gray-50 rounded">
                <h3 class="font-semibold mb-2">Verificación rápida:</h3>
                <?php
                $result = $conn->query("SELECT COUNT(*) as total FROM padron_electoral");
                if ($result) {
                    $row = $result->fetch_assoc();
                    echo "<p>Total de registros: <strong>" . number_format($row['total']) . "</strong></p>";

                    // Mostrar algunos registros de ejemplo
                    $sample = $conn->query("SELECT * FROM padron_electoral LIMIT 5");
                    if ($sample && $sample->num_rows > 0) {
                        echo "<table class='mt-3 text-sm w-full'>";
                        echo "<tr class='bg-gray-200'><th class='p-2'>Cédula</th><th class='p-2'>Nombre</th><th class='p-2'>Apellidos</th></tr>";
                        while ($r = $sample->fetch_assoc()) {
                            echo "<tr class='border-b'>";
                            echo "<td class='p-2'>{$r['cedula']}</td>";
                            echo "<td class='p-2'>{$r['nombre']}</td>";
                            echo "<td class='p-2'>{$r['apellido1']} {$r['apellido2']}</td>";
                            echo "</tr>";
                        }
                        echo "</table>";
                    }
                } else {
                    echo "<p class='text-gray-500'>La tabla aún no existe.</p>";
                }
                ?>
            </div>

            <div class="mt-6">
                <a href="/admin/dashboard.php" class="text-blue-600 hover:underline">← Volver al Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>
