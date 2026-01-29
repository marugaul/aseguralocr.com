<?php
header('Content-Type: text/plain; charset=utf-8');

echo "=== VERIFICACIÓN DE ARCHIVOS ===\n\n";

$prodPath = '/home/asegural/public_html/aseguralocr';

$files = [
    'admin/client-detail.php',
    'admin/clients.php',
    'admin/includes/header.php',
    'admin/actions/regenerar-plan-pagos.php'
];

foreach ($files as $file) {
    $fullPath = $prodPath . '/' . $file;

    echo "📁 {$file}\n";

    if (file_exists($fullPath)) {
        echo "  ✅ EXISTE\n";
        echo "  Tamaño: " . number_format(filesize($fullPath)) . " bytes\n";
        echo "  Permisos: " . substr(sprintf('%o', fileperms($fullPath)), -4) . "\n";
        echo "  Modificado: " . date('Y-m-d H:i:s', filemtime($fullPath)) . "\n";

        // Verificar contenido específico
        if ($file === 'admin/client-detail.php') {
            $content = file_get_contents($fullPath);
            echo "  Tiene 'Regenerar Plan': " . (strpos($content, 'Regenerar Plan') !== false ? 'SÍ' : 'NO') . "\n";
            echo "  Primeras 100 chars: " . substr($content, 0, 100) . "...\n";
        }
    } else {
        echo "  ❌ NO EXISTE\n";
    }
    echo "\n";
}

// Verificar directorio admin
$adminPath = $prodPath . '/admin';
if (is_dir($adminPath)) {
    echo "📂 Directorio /admin: EXISTE\n";
    echo "  Permisos: " . substr(sprintf('%o', fileperms($adminPath)), -4) . "\n";
} else {
    echo "❌ Directorio /admin: NO EXISTE\n";
}
?>
