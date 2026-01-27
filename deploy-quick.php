<?php
header('Content-Type: text/plain; charset=utf-8');

echo "=== DEPLOYMENT RÁPIDO ===\n\n";

$repoPath = '/home/asegural/aseguralocr_repo';
$prodPath = '/home/asegural/public_html/aseguralocr';

// Eliminar git lock
$lockFile = $repoPath . '/.git/index.lock';
if (file_exists($lockFile)) {
    @unlink($lockFile);
    echo "✓ Git lock eliminado\n\n";
}

// Copiar archivos críticos directamente
echo "Copiando archivos...\n";

$files = [
    'admin/includes/header.php',
    'admin/client-detail.php',
    'admin/actions/regenerar-plan-pagos.php'
];

foreach ($files as $file) {
    $src = $repoPath . '/' . $file;
    $dst = $prodPath . '/' . $file;

    if (file_exists($src)) {
        $dir = dirname($dst);
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        if (copy($src, $dst)) {
            echo "✓ {$file}\n";

            // Verificar contenido
            if ($file === 'admin/includes/header.php') {
                $content = file_get_contents($dst);
                echo "  - Cache headers: " . (strpos($content, 'Cache-Control') !== false ? 'SÍ' : 'NO') . "\n";
                echo "  - inline-flex: " . (strpos($content, 'inline-flex') !== false ? 'SÍ' : 'NO') . "\n";
            }

            if ($file === 'admin/client-detail.php') {
                $content = file_get_contents($dst);
                echo "  - Regenerar Plan: " . (strpos($content, 'Regenerar Plan') !== false ? 'SÍ' : 'NO') . "\n";
            }
        } else {
            echo "✗ ERROR: {$file}\n";
        }
    } else {
        echo "⚠ NO EXISTE: {$file}\n";
    }
}

echo "\n✅ LISTO\n\n";
echo "Ahora presiona Ctrl + Shift + R en el admin\n";
?>
