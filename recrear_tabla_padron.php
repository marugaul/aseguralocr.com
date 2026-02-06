<?php
// Script para recrear la tabla padron_electoral con TODAS las columnas
require_once 'includes/db.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Recrear Tabla Padrón</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 { color: #333; }
        .info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .success {
            background: #d4edda;
            border: 1px solid #28a745;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .error {
            background: #f8d7da;
            border: 1px solid #dc3545;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        button {
            background: #007bff;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover { background: #0056b3; }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 Recrear Tabla Padrón Electoral</h1>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Paso 1: Eliminar tabla existente
        echo "<div class='info'>Eliminando tabla anterior...</div>";
        $conn->query("DROP TABLE IF EXISTS padron_electoral");
        echo "<div class='success'>✓ Tabla anterior eliminada</div>";

        // Paso 2: Crear tabla con TODAS las columnas
        echo "<div class='info'>Creando tabla con estructura completa...</div>";

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
            echo "<div class='success'>✓ Tabla creada exitosamente con TODAS las columnas</div>";

            echo "<h3>Estructura de la tabla:</h3>";
            echo "<pre>";
            echo "- id (AUTO_INCREMENT)\n";
            echo "- cedula (UNIQUE)\n";
            echo "- codelec\n";
            echo "- sitio_votacion\n";
            echo "- fecha_vencimiento\n";
            echo "- junta\n";
            echo "- nombre\n";
            echo "- primer_apellido\n";
            echo "- segundo_apellido\n";
            echo "- nombre_completo\n";
            echo "- created_at\n";
            echo "</pre>";

            echo "<div class='success'>";
            echo "<strong>✅ ¡Listo!</strong><br><br>";
            echo "Ahora puedes ir a <a href='admin/padron_importar_ajax.php'>admin/padron_importar_ajax.php</a> para importar los datos.";
            echo "</div>";
        } else {
            throw new Exception($conn->error);
        }

    } catch (Exception $e) {
        echo "<div class='error'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
    }
} else {
    // Verificar tabla actual
    $result = $conn->query("SHOW TABLES LIKE 'padron_electoral'");
    $tablaExiste = $result && $result->num_rows > 0;

    if ($tablaExiste) {
        echo "<div class='warning'>";
        echo "<strong>⚠️ ADVERTENCIA:</strong> La tabla 'padron_electoral' ya existe.<br><br>";

        // Mostrar estructura actual
        $columns = $conn->query("SHOW COLUMNS FROM padron_electoral");
        echo "<strong>Estructura actual:</strong><br>";
        echo "<pre>";
        while ($col = $columns->fetch_assoc()) {
            echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
        }
        echo "</pre>";

        // Contar registros
        $count = $conn->query("SELECT COUNT(*) as total FROM padron_electoral");
        $total = $count->fetch_assoc()['total'];
        echo "<strong>Registros actuales:</strong> " . number_format($total) . "<br><br>";

        if ($total > 0) {
            echo "<strong style='color: #dc3545;'>¡ATENCIÓN! La tabla tiene {$total} registros que serán eliminados.</strong><br><br>";
        }

        echo "Este script va a ELIMINAR la tabla actual y crearla de nuevo con TODAS las columnas del TSE.";
        echo "</div>";
    } else {
        echo "<div class='info'>La tabla 'padron_electoral' no existe. Se creará con la estructura completa.</div>";
    }

    echo "<div class='info'>";
    echo "<h3>Nueva estructura (TODAS las columnas TSE):</h3>";
    echo "<pre>";
    echo "- cedula (cédula de identidad)\n";
    echo "- codelec (código electoral)\n";
    echo "- sitio_votacion (lugar de votación)\n";
    echo "- fecha_vencimiento (fecha de caducidad)\n";
    echo "- junta (número de junta)\n";
    echo "- nombre\n";
    echo "- primer_apellido\n";
    echo "- segundo_apellido\n";
    echo "- nombre_completo (generado automáticamente)\n";
    echo "</pre>";
    echo "</div>";

    echo "<form method='POST'>";
    echo "<button type='submit'>🔄 Recrear Tabla</button>";
    echo "</form>";
}
?>

    </div>
</body>
</html>
