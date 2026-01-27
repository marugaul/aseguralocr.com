<?php
// COPIAR ARCHIVOS AHORA - Sin esperar cron
$repoPath = '/home/asegural/aseguralocr_repo';
$prodPath = '/home/asegural/public_html/aseguralocr';

// Eliminar locks
@unlink($repoPath . '/.git/index.lock');

// Copiar AHORA
$files = [
    'admin/includes/header.php',
    'admin/client-detail.php',
    'admin/actions/regenerar-plan-pagos.php'
];

foreach ($files as $file) {
    $src = $repoPath . '/' . $file;
    $dst = $prodPath . '/' . $file;

    if (file_exists($src)) {
        @mkdir(dirname($dst), 0755, true);
        copy($src, $dst);
    }
}

// Limpiar OPcache
if (function_exists('opcache_reset')) {
    opcache_reset();
}

echo "OK - Archivos copiados. Presiona Ctrl+Shift+R";
?>
