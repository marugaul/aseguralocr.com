<?php
/**
 * Script de diagnóstico para encontrar PADRON_COMPLETO.txt
 */

echo "<h1>Diagnóstico del Padrón</h1>";
echo "<pre>";

$ubicaciones = [
    '/home/asegural/public_html/data/PADRON_COMPLETO.txt',
    '/home/asegural/public_html/aseguralocr/data/PADRON_COMPLETO.txt',
    '/home/asegural/public_html/aseguralocr/data/padron/PADRON_COMPLETO.txt',
    __DIR__ . '/data/PADRON_COMPLETO.txt',
    __DIR__ . '/data/padron/PADRON_COMPLETO.txt',
    __DIR__ . '/../data/PADRON_COMPLETO.txt'
];

echo "Buscando PADRON_COMPLETO.txt...\n\n";

$encontrado = false;
foreach ($ubicaciones as $ruta) {
    echo "Verificando: $ruta\n";
    if (file_exists($ruta)) {
        $size = round(filesize($ruta) / 1024 / 1024, 2);
        echo "  ✅ ENCONTRADO - Tamaño: {$size} MB\n";
        $encontrado = true;
    } else {
        echo "  ❌ No existe\n";
    }
}

echo "\n" . str_repeat("=", 60) . "\n";

if ($encontrado) {
    echo "✅ Archivo encontrado. El importador debería funcionar.\n";
} else {
    echo "❌ Archivo NO encontrado. Verifica que hayas subido/descomprimido el archivo.\n";
}

echo "\nDirectorio actual: " . __DIR__ . "\n";
echo "Usuario PHP: " . get_current_user() . "\n";

echo "</pre>";
?>
