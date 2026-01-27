<?php
// Test simple para diagnosticar el problema
session_start();

echo "<h1>Diagnóstico Padrón Importer</h1>";

// Test 1: Sesión
echo "<h2>1. Sesión Admin</h2>";
if (isset($_SESSION['admin_id'])) {
    echo "✅ Sesión activa: admin_id = " . $_SESSION['admin_id'];
} else {
    echo "❌ No hay sesión de admin activa<br>";
    echo "Variables de sesión: " . print_r($_SESSION, true);
}

// Test 2: DB connection
echo "<h2>2. Conexión a DB</h2>";
try {
    require_once __DIR__ . '/../includes/db.php';
    echo "✅ includes/db.php cargado<br>";

    if (isset($conn)) {
        echo "✅ Variable \$conn existe (mysqli)<br>";
        echo "Estado conexión: " . ($conn->ping() ? "Conectado" : "Desconectado") . "<br>";
    } else {
        echo "❌ Variable \$conn no existe<br>";
    }

    if (isset($pdo)) {
        echo "✅ Variable \$pdo existe (PDO)<br>";
    } else {
        echo "❌ Variable \$pdo no existe<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}

// Test 3: Archivo padron
echo "<h2>3. Archivo Padrón</h2>";
$posiblesUbicaciones = [
    '/home/asegural/public_html/data/padron/PADRON_COMPLETO.txt',
    '/home/asegural/public_html/data/PADRON_COMPLETO.txt',
    __DIR__ . '/../data/padron/PADRON_COMPLETO.txt',
];

foreach ($posiblesUbicaciones as $ubicacion) {
    if (file_exists($ubicacion)) {
        echo "✅ Encontrado: $ubicacion<br>";
        echo "Tamaño: " . round(filesize($ubicacion)/1024/1024, 2) . " MB<br>";
        break;
    } else {
        echo "❌ No existe: $ubicacion<br>";
    }
}

// Test 4: Verificar que padron_importar_ajax.php existe
echo "<h2>4. Archivo padron_importar_ajax.php</h2>";
$ajaxFile = __DIR__ . '/padron_importar_ajax.php';
if (file_exists($ajaxFile)) {
    echo "✅ Archivo existe<br>";
    echo "Tamaño: " . filesize($ajaxFile) . " bytes<br>";
    echo "Última modificación: " . date('Y-m-d H:i:s', filemtime($ajaxFile)) . "<br>";
} else {
    echo "❌ Archivo no existe<br>";
}

// Test 5: Tabla padron_electoral
echo "<h2>5. Tabla padron_electoral</h2>";
try {
    $result = $conn->query("SHOW TABLES LIKE 'padron_electoral'");
    if ($result && $result->num_rows > 0) {
        echo "✅ Tabla existe<br>";

        $columns = $conn->query("SHOW COLUMNS FROM padron_electoral");
        echo "<strong>Columnas:</strong><br>";
        while ($col = $columns->fetch_assoc()) {
            echo "- " . $col['Field'] . " (" . $col['Type'] . ")<br>";
        }

        $count = $conn->query("SELECT COUNT(*) as total FROM padron_electoral");
        $total = $count->fetch_assoc()['total'];
        echo "<strong>Registros:</strong> " . number_format($total) . "<br>";
    } else {
        echo "❌ Tabla no existe<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}

echo "<hr><a href='padron_importar_ajax.php'>Ir a padron_importar_ajax.php</a>";
?>
