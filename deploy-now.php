<?php
session_start();
if (empty($_SESSION['admin_id'])) {
    die('Acceso denegado - Inicia sesión en /admin primero');
}

header('Content-Type: text/plain; charset=utf-8');

echo "=== DEPLOYMENT MANUAL ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

$repoPath = '/home/asegural/aseguralocr_repo';
$prodPath = '/home/asegural/public_html/aseguralocr';

// 1. Remove git lock if exists
echo "1. VERIFICANDO GIT LOCK:\n";
echo str_repeat('-', 60) . "\n";
$lockFile = $repoPath . '/.git/index.lock';
if (file_exists($lockFile)) {
    echo "⚠ Git lock existe, eliminando...\n";
    @unlink($lockFile);
    echo "✓ Lock eliminado\n";
} else {
    echo "✓ No hay git lock\n";
}

// 2. Rsync specific critical files first
echo "\n2. COPIANDO ARCHIVOS CRÍTICOS:\n";
echo str_repeat('-', 60) . "\n";

$criticalFiles = [
    'admin/client-detail.php',
    'admin/actions/regenerar-plan-pagos.php',
    'admin/includes/header.php',
    'limpiar-cache.php'
];

foreach ($criticalFiles as $file) {
    $source = $repoPath . '/' . $file;
    $dest = $prodPath . '/' . $file;

    if (file_exists($source)) {
        // Create directory if needed
        $destDir = dirname($dest);
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        // Copy file
        if (copy($source, $dest)) {
            echo "✓ {$file} - COPIADO\n";
            echo "  Tamaño: " . number_format(filesize($dest)) . " bytes\n";
            echo "  Modificado: " . date('Y-m-d H:i:s', filemtime($dest)) . "\n";
        } else {
            echo "✗ {$file} - ERROR AL COPIAR\n";
        }
    } else {
        echo "✗ {$file} - NO EXISTE EN REPO\n";
    }
}

// 3. Full rsync for everything else
echo "\n3. SINCRONIZANDO TODO:\n";
echo str_repeat('-', 60) . "\n";

$rsyncCmd = "rsync -av --delete " .
    "--exclude='.git' " .
    "--exclude='vendor/' " .
    "--exclude='logs/' " .
    "--exclude='storage/' " .
    "--exclude='app/config/config.php' " .
    "--exclude='includes/db.php' " .
    "{$repoPath}/ {$prodPath}/ 2>&1";

exec($rsyncCmd, $output, $ret);

if ($ret === 0) {
    echo "✓ Rsync completado exitosamente\n";
} else {
    echo "⚠ Rsync terminó con código: {$ret}\n";
}

// Show last 10 lines of output
echo "\nÚltimas líneas del rsync:\n";
$lastLines = array_slice($output, -10);
foreach ($lastLines as $line) {
    echo "  {$line}\n";
}

// 4. Verify critical files in production
echo "\n4. VERIFICACIÓN FINAL:\n";
echo str_repeat('-', 60) . "\n";

foreach ($criticalFiles as $file) {
    $fullPath = $prodPath . '/' . $file;
    if (file_exists($fullPath)) {
        echo "✓ {$file} - EXISTE\n";

        // Verify content
        if ($file === 'admin/client-detail.php') {
            $content = file_get_contents($fullPath);
            $hasBtn = strpos($content, 'Regenerar Plan') !== false;
            $hasModal = strpos($content, 'regenerarPlanModal') !== false;
            echo "  - Botón Regenerar: " . ($hasBtn ? "✓ SÍ" : "✗ NO") . "\n";
            echo "  - Modal: " . ($hasModal ? "✓ SÍ" : "✗ NO") . "\n";
        }

        if ($file === 'admin/includes/header.php') {
            $content = file_get_contents($fullPath);
            $hasCache = strpos($content, 'Cache-Control: no-cache') !== false;
            echo "  - Headers anti-caché: " . ($hasCache ? "✓ SÍ" : "✗ NO") . "\n";
        }
    } else {
        echo "✗ {$file} - NO EXISTE\n";
    }
}

echo "\n=== DEPLOYMENT COMPLETADO ===\n";
echo "\nAhora presiona Ctrl + Shift + R para recargar las páginas del admin\n";
?>
