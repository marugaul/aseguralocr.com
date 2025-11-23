<?php
/**
 * ============================================
 * EJECUTAR CREACIÓN DE TABLAS EN PRODUCCIÓN
 * AseguraloCR - Setup Base de Datos
 * ============================================
 *
 * Accede a este archivo desde tu navegador:
 * https://aseguralocr.com/crear-tablas-db.php
 *
 * O ejecútalo vía cron:
 * php /home/asegural/public_html/crear-tablas-db.php
 *
 * ⚠️ IMPORTANTE: Elimina este archivo después de usarlo
 * ============================================
 */

// Configuración de la base de datos
$hosts = ['localhost', '127.0.0.1'];
$dbName = 'asegural_aseguralocr';
$dbUser = 'asegural_marugaul';
$dbPass = 'Marden7i/';

// Configurar para mostrar en navegador
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Creación de Tablas - AseguraloCR</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background: #1e1e1e;
            color: #00ff00;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: #2d2d2d;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,255,0,0.3);
        }
        h1 {
            color: #00ff00;
            text-align: center;
            border-bottom: 2px solid #00ff00;
            padding-bottom: 10px;
        }
        .success {
            color: #00ff00;
            font-weight: bold;
        }
        .error {
            color: #ff0000;
            font-weight: bold;
        }
        .warning {
            color: #ffaa00;
            font-weight: bold;
        }
        .info {
            color: #00aaff;
        }
        pre {
            background: #1a1a1a;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .step {
            margin: 20px 0;
            padding: 15px;
            background: #333;
            border-left: 4px solid #00ff00;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>🗄️ Creación de Tablas - AseguraloCR</h1>

<?php

echo "<div class='step'>\n";
echo "<h2>📋 Información de Conexión</h2>\n";
echo "<pre>\n";
echo "Base de datos: <span class='info'>$dbName</span>\n";
echo "Usuario: <span class='info'>$dbUser</span>\n";
echo "Hosts: <span class='info'>" . implode(', ', $hosts) . "</span>\n";
echo "</pre>\n";
echo "</div>\n";

// Intentar conexión con cada host
$pdo = null;
$connectedHost = null;

foreach ($hosts as $host) {
    try {
        echo "<div class='step'>\n";
        echo "<h2>🔌 Intentando conectar a: $host</h2>\n";

        $pdo = new PDO(
            "mysql:host=$host;dbname=$dbName;charset=utf8mb4",
            $dbUser,
            $dbPass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );

        $connectedHost = $host;
        echo "<p class='success'>✅ Conectado exitosamente a $host</p>\n";
        echo "</div>\n";
        break;

    } catch (PDOException $e) {
        echo "<p class='error'>❌ Error conectando a $host: {$e->getMessage()}</p>\n";
        echo "</div>\n";
        continue;
    }
}

if (!$pdo) {
    echo "<div class='step'>\n";
    echo "<h2 class='error'>❌ ERROR FATAL</h2>\n";
    echo "<p class='error'>No se pudo conectar a ningún host de MySQL</p>\n";
    echo "<p>Verifica:</p>\n";
    echo "<ul>\n";
    echo "<li>Credenciales correctas</li>\n";
    echo "<li>MySQL está corriendo</li>\n";
    echo "<li>Usuario tiene permisos</li>\n";
    echo "</ul>\n";
    echo "</div>\n";
    echo "</div></body></html>";
    exit(1);
}

// Leer archivo SQL
$sqlFile = __DIR__ . '/EJECUTAR_EN_PHPMYADMIN.sql';

echo "<div class='step'>\n";
echo "<h2>📄 Leyendo archivo SQL</h2>\n";

if (!file_exists($sqlFile)) {
    echo "<p class='error'>❌ ERROR: Archivo SQL no encontrado</p>\n";
    echo "<p>Buscado en: <code>$sqlFile</code></p>\n";
    echo "</div></div></body></html>";
    exit(1);
}

$sql = file_get_contents($sqlFile);
echo "<p class='success'>✅ Archivo leído correctamente (" . strlen($sql) . " bytes)</p>\n";
echo "</div>\n";

// Separar statements
$statements = array_filter(
    array_map('trim', explode(';', $sql)),
    function($stmt) {
        return !empty($stmt) &&
               !preg_match('/^--/', $stmt) &&
               !preg_match('/^\/\*/', $stmt);
    }
);

echo "<div class='step'>\n";
echo "<h2>🚀 Ejecutando SQL</h2>\n";
echo "<p>Total de statements a ejecutar: <span class='info'>" . count($statements) . "</span></p>\n";
echo "<pre>\n";

$executed = 0;
$errors = 0;
$created = [
    'tables' => [],
    'views' => [],
    'indexes' => []
];

foreach ($statements as $stmt) {
    $stmt = trim($stmt);
    if (empty($stmt)) continue;

    try {
        $pdo->exec($stmt);
        $executed++;

        // Detectar qué se creó
        if (preg_match('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?(\w+)`?/i', $stmt, $matches)) {
            $created['tables'][] = $matches[1];
            echo "<span class='success'>✅ Tabla creada: {$matches[1]}</span>\n";
        } elseif (preg_match('/CREATE\s+VIEW\s+(?:IF\s+NOT\s+EXISTS\s+)?`?(\w+)`?/i', $stmt, $matches)) {
            $created['views'][] = $matches[1];
            echo "<span class='success'>✅ Vista creada: {$matches[1]}</span>\n";
        } elseif (preg_match('/CREATE\s+INDEX\s+`?(\w+)`?/i', $stmt, $matches)) {
            $created['indexes'][] = $matches[1];
            echo "<span class='success'>✅ Índice creado: {$matches[1]}</span>\n";
        } else {
            echo "<span class='success'>✅ Statement ejecutado</span>\n";
        }

    } catch (PDOException $e) {
        $errors++;
        // Si es "already exists", no es un error crítico
        if (strpos($e->getMessage(), 'already exists') !== false) {
            if (preg_match('/Table\s+\'(\w+)\'/', $e->getMessage(), $matches)) {
                echo "<span class='warning'>⚠️  Tabla ya existe: {$matches[1]}</span>\n";
            } else {
                echo "<span class='warning'>⚠️  {$e->getMessage()}</span>\n";
            }
        } else {
            echo "<span class='error'>❌ Error: {$e->getMessage()}</span>\n";
        }
    }
}

echo "</pre>\n";
echo "</div>\n";

// Resumen
echo "<div class='step'>\n";
echo "<h2>📊 Resumen de Ejecución</h2>\n";
echo "<pre>\n";
echo "Statements ejecutados: <span class='success'>$executed</span>\n";
echo "Errores/Advertencias: <span class='" . ($errors > 0 ? 'warning' : 'success') . "'>$errors</span>\n\n";

if (!empty($created['tables'])) {
    echo "<span class='info'>📋 Tablas creadas:</span>\n";
    foreach ($created['tables'] as $table) {
        echo "   • $table\n";
    }
    echo "\n";
}

if (!empty($created['views'])) {
    echo "<span class='info'>👁️  Vistas creadas:</span>\n";
    foreach ($created['views'] as $view) {
        echo "   • $view\n";
    }
    echo "\n";
}

if (!empty($created['indexes'])) {
    echo "<span class='info'>🔍 Índices creados:</span>\n";
    foreach ($created['indexes'] as $index) {
        echo "   • $index\n";
    }
}
echo "</pre>\n";
echo "</div>\n";

// Verificar tablas creadas
echo "<div class='step'>\n";
echo "<h2>🔍 Verificación de Tablas</h2>\n";
echo "<pre>\n";

try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "Total de tablas en la BD: <span class='info'>" . count($tables) . "</span>\n\n";
    echo "Tablas encontradas:\n";

    $expectedTables = ['clients', 'policies', 'payments', 'quotes', 'client_notifications', 'oauth_settings'];

    foreach ($expectedTables as $expectedTable) {
        if (in_array($expectedTable, $tables)) {
            echo "<span class='success'>✅ $expectedTable</span>\n";

            // Contar registros
            $countStmt = $pdo->query("SELECT COUNT(*) as total FROM `$expectedTable`");
            $count = $countStmt->fetch()['total'];
            echo "   Registros: $count\n";
        } else {
            echo "<span class='error'>❌ $expectedTable (NO ENCONTRADA)</span>\n";
        }
    }

} catch (PDOException $e) {
    echo "<span class='error'>❌ Error verificando tablas: {$e->getMessage()}</span>\n";
}

echo "</pre>\n";
echo "</div>\n";

// Siguiente paso: Triggers
$triggerFile = __DIR__ . '/TRIGGERS_EJECUTAR_DESPUES.sql';

if (file_exists($triggerFile)) {
    echo "<div class='step'>\n";
    echo "<h2>⚙️ Ejecutar Triggers (Opcional)</h2>\n";
    echo "<p>Archivo de triggers encontrado. ¿Deseas ejecutarlo?</p>\n";
    echo "<p><a href='crear-tablas-db.php?ejecutar_triggers=1' style='color: #00ff00; font-weight: bold;'>→ SÍ, EJECUTAR TRIGGERS</a></p>\n";
    echo "</div>\n";

    // Ejecutar triggers si se solicitó
    if (isset($_GET['ejecutar_triggers']) && $_GET['ejecutar_triggers'] == '1') {
        echo "<div class='step'>\n";
        echo "<h2>🔧 Ejecutando Triggers</h2>\n";
        echo "<pre>\n";

        $triggerSQL = file_get_contents($triggerFile);
        $triggerStatements = array_filter(
            array_map('trim', preg_split('/DELIMITER|;/', $triggerSQL)),
            function($stmt) {
                $stmt = trim($stmt);
                return !empty($stmt) &&
                       !preg_match('/^--/', $stmt) &&
                       !preg_match('/^\$\$/', $stmt);
            }
        );

        foreach ($triggerStatements as $triggerStmt) {
            $triggerStmt = trim($triggerStmt);
            if (empty($triggerStmt) || $triggerStmt === '$$') continue;

            try {
                $pdo->exec($triggerStmt);

                if (preg_match('/CREATE\s+TRIGGER\s+`?(\w+)`?/i', $triggerStmt, $matches)) {
                    echo "<span class='success'>✅ Trigger creado: {$matches[1]}</span>\n";
                }
            } catch (PDOException $e) {
                echo "<span class='error'>❌ Error: {$e->getMessage()}</span>\n";
            }
        }

        echo "</pre>\n";
        echo "</div>\n";
    }
}

// Instrucciones finales
echo "<div class='step'>\n";
echo "<h2>✅ PROCESO COMPLETADO</h2>\n";
echo "<pre>\n";
echo "Las tablas han sido creadas exitosamente en:\n";
echo "   Base de datos: <span class='success'>$dbName</span>\n";
echo "   Servidor: <span class='success'>$connectedHost</span>\n\n";
echo "<span class='warning'>⚠️  IMPORTANTE - SEGURIDAD:</span>\n";
echo "1. <span class='error'>ELIMINA este archivo (crear-tablas-db.php)</span>\n";
echo "   Contiene credenciales sensibles\n\n";
echo "2. Verifica las tablas en phpMyAdmin\n\n";
echo "3. Ejecuta los triggers si aún no lo hiciste\n";
echo "   (ver enlace arriba)\n";
echo "</pre>\n";
echo "</div>\n";

?>

</div>
</body>
</html>
