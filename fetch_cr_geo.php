<?php
// fetch_cr_geo.php
// Descarga la estructura Provincias -> Cantones -> Distritos desde https://ubicaciones.paginasweb.cr/
// y genera /assets/data/cr_geo.json (legible)

set_time_limit(0);
ini_set('display_errors', 1);
error_reporting(E_ALL);

$base = 'https://ubicaciones.paginasweb.cr';
$targetDir = __DIR__ . '/assets/data';
$targetFile = $targetDir . '/cr_geo.json';

if (!is_dir($targetDir)) {
    if (!mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
        echo "No se pudo crear directorio $targetDir\n";
        exit(1);
    }
}

function getJson($url) {
    $opts = [
        "http" => [
            "method" => "GET",
            "header" => "User-Agent: AseguraloCR-fetch/1.0\r\n",
            "timeout" => 15
        ]
    ];
    $context = stream_context_create($opts);
    $s = @file_get_contents($url, false, $context);
    if ($s === false) return null;
    $j = json_decode($s, true);
    return $j;
}

echo "Descargando provincias...\n";
$provincias = getJson($base . '/provincias.json');
if (!$provincias) {
    echo "Error descargando provincias desde $base/provincias.json\n";
    exit(1);
}

$result = [];
foreach ($provincias as $pid => $pname) {
    echo "Provincia $pid => $pname\n";
    $cantonesUrl = $base . "/provincia/{$pid}/cantones.json";
    $cantones = getJson($cantonesUrl);
    if (!$cantones) {
        echo "  - No se pudo obtener cantones para provincia $pid\n";
        // continúa (fallback: dejar vacio)
        $result[$pname] = new stdClass();
        continue;
    }
    $result[$pname] = new stdClass();
    foreach ($cantones as $cid => $cname) {
        echo "  Cantón $cid => $cname\n";
        $distritosUrl = $base . "/provincia/{$pid}/canton/{$cid}/distritos.json";
        $distritos = getJson($distritosUrl);
        if (!$distritos) {
            echo "    - No se pudo obtener distritos para canton $cid\n";
            $result[$pname][$cname] = [];
            continue;
        }
        // extraer nombres (array index -> nombre)
        $districtNames = array_values($distritos);
        $result[$pname][$cname] = $districtNames;
    }
}

// Guardar JSON legible
$json = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
if (file_put_contents($targetFile, $json) === false) {
    echo "Error guardando $targetFile\n";
    exit(1);
}
echo "Archivo generado en: $targetFile\n";
echo "Tamaño: " . filesize($targetFile) . " bytes\n";
echo "Listo. Ahora el archivo se puede usar desde /assets/data/cr_geo.json\n";