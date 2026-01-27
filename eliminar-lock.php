<?php
header('Content-Type: text/plain; charset=utf-8');

echo "=== ELIMINAR GIT LOCK AHORA ===\n\n";

$lockFile = '/home/asegural/aseguralocr_repo/.git/index.lock';

if (file_exists($lockFile)) {
    if (@unlink($lockFile)) {
        echo "✅ Git lock eliminado: {$lockFile}\n\n";
    } else {
        echo "❌ No se pudo eliminar (permisos?): {$lockFile}\n\n";
    }
} else {
    echo "✓ No existe git lock\n\n";
}

// Verificar repo
$repoPath = '/home/asegural/aseguralocr_repo';
if (is_dir($repoPath . '/.git')) {
    echo "✓ Repositorio existe\n";

    // Ver último commit
    chdir($repoPath);
    exec('git log -1 --oneline 2>&1', $output);
    echo "Último commit: " . implode('', $output) . "\n\n";
} else {
    echo "⚠ Repositorio no existe aún\n\n";
}

echo "Ahora el cron puede correr sin problemas.\n";
?>
