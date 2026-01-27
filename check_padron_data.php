<?php
// Script temporal para verificar datos del padrón
require_once 'includes/db.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Estado del Padrón Electoral</h1>";

// Verificar tabla
try {
    if (isset($conn)) {
        $result = $conn->query("SHOW TABLES LIKE 'padron_electoral'");
        if ($result && $result->num_rows > 0) {
            echo "✅ Tabla existe<br><br>";

            // Contar registros
            $count = $conn->query("SELECT COUNT(*) as total FROM padron_electoral");
            if ($count) {
                $total = $count->fetch_assoc()['total'];
                echo "<strong>Total registros:</strong> " . number_format($total) . "<br><br>";
            }

            // Probar búsqueda específica
            $cedula = '602870337';
            $stmt = $conn->prepare("SELECT * FROM padron_electoral WHERE cedula = ?");
            $stmt->bind_param('s', $cedula);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                echo "<h3>Prueba con cédula $cedula:</h3>";
                echo "<pre>";
                print_r($row);
                echo "</pre>";
            } else {
                echo "❌ Cédula $cedula no encontrada<br>";
            }
        } else {
            echo "❌ Tabla no existe<br>";
            echo "Necesitas ejecutar la importación primero.";
        }
    } else {
        echo "❌ Conexión a base de datos no disponible<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
