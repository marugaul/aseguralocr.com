<?php
// Script para verificar si los campos nuevos están en el PDF mapper
echo "<h1>Verificación de Campos en PDF Mapper</h1>";

echo "<h2>Verificando archivo en servidor...</h2>";

$mapperPath = __DIR__ . '/admin/pdf_mapper.php';
if (!file_exists($mapperPath)) {
    echo "❌ Archivo no encontrado: $mapperPath<br>";
    exit;
}

echo "✅ Archivo existe: $mapperPath<br>";
echo "Tamaño: " . filesize($mapperPath) . " bytes<br>";
echo "Última modificación: " . date('Y-m-d H:i:s', filemtime($mapperPath)) . "<br><br>";

// Leer contenido
$content = file_get_contents($mapperPath);

// Contar campos por tipo
preg_match_all("/'key' => 'autos_/", $content, $matchesAutos);
preg_match_all("/'key' => 'rt_/", $content, $matchesRT);
preg_match_all("/'key' => 'hogar_/", $content, $matchesHogar);

echo "<h3>Campos encontrados:</h3>";
echo "🚗 <strong>Autos:</strong> " . count($matchesAutos[0]) . " campos<br>";
echo "🏠 <strong>Hogar:</strong> " . count($matchesHogar[0]) . " campos<br>";
echo "👷 <strong>RT:</strong> " . count($matchesRT[0]) . " campos<br><br>";

// Verificar campos específicos nuevos
echo "<h3>Verificando campos nuevos agregados:</h3>";

$camposNuevos = [
    'autos_acople_valor' => 'Autos',
    'autos_acreedor_nombre' => 'Autos',
    'autos_beneficiario1_nombre' => 'Autos',
    'rt_cb_adjunta_planilla_si' => 'RT',
    'rt_cb_calendario_mensual' => 'RT',
    'rt_cb_pago_anual' => 'RT'
];

foreach ($camposNuevos as $campo => $tipo) {
    if (strpos($content, "'key' => '$campo'") !== false) {
        echo "✅ <strong>$tipo:</strong> Campo '$campo' encontrado<br>";
    } else {
        echo "❌ <strong>$tipo:</strong> Campo '$campo' NO encontrado<br>";
    }
}

echo "<hr>";
echo "<p>Si ves ❌, significa que el cron aún no ha desplegado los cambios.</p>";
echo "<p>Espera 3 minutos y recarga esta página.</p>";
?>
