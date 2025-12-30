<?php
session_start();
if (empty($_SESSION['admin_id'])) {
    die('Acceso denegado - Inicia sesión en /admin primero');
}

header('Content-Type: text/plain; charset=utf-8');
set_time_limit(300); // 5 minutes

echo "=== FIX DEFINITIVO - DEPLOYMENT COMPLETO ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

$repoPath = '/home/asegural/aseguralocr_repo';
$prodPath = '/home/asegural/public_html/aseguralocr';

// 1. KILL GIT LOCK
echo "1. ELIMINANDO GIT LOCKS:\n";
echo str_repeat('=', 60) . "\n";
$lockFile = $repoPath . '/.git/index.lock';
if (file_exists($lockFile)) {
    @unlink($lockFile);
    echo "✓ Git lock eliminado\n";
}
// También eliminar otros locks posibles
$locks = [
    $repoPath . '/.git/refs/heads/main.lock',
    $repoPath . '/.git/HEAD.lock',
    $repoPath . '/.git/index.lock'
];
foreach ($locks as $lock) {
    if (file_exists($lock)) {
        @unlink($lock);
        echo "✓ Eliminado: " . basename($lock) . "\n";
    }
}

// 2. FORZAR GIT FETCH Y RESET
echo "\n2. ACTUALIZANDO REPOSITORIO:\n";
echo str_repeat('=', 60) . "\n";
chdir($repoPath);

// Limpiar cualquier cambio local
exec('git reset --hard 2>&1', $output1);
exec('git clean -fd 2>&1', $output2);
echo "✓ Repositorio limpiado\n";

// Fetch forzado
exec('git fetch origin --force 2>&1', $output3);
echo "✓ Fetch ejecutado\n";

// Reset a la última versión
exec('git reset --hard origin/main 2>&1', $output4);
echo "✓ Reset a origin/main\n";

// Verificar commit actual
exec('git log -1 --oneline 2>&1', $currentCommit);
echo "✓ Commit actual: " . implode('', $currentCommit) . "\n";

// 3. COPIAR TODOS LOS ARCHIVOS CRÍTICOS MANUALMENTE
echo "\n3. COPIANDO ARCHIVOS CRÍTICOS:\n";
echo str_repeat('=', 60) . "\n";

$allCriticalFiles = [
    // Admin core
    'admin/includes/header.php',
    'admin/includes/footer.php',
    'admin/client-detail.php',
    'admin/clients.php',
    'admin/add-policy.php',
    'admin/edit-policy.php',
    'admin/pdf_mapper.php',

    // Actions
    'admin/actions/regenerar-plan-pagos.php',
    'admin/actions/save-policy.php',
    'admin/actions/mark-payment-paid.php',
    'admin/actions/delete-policy.php',

    // API
    'api/padron.php',

    // Forms
    'autos.php',
    'hogar-comprensivo.php',
    'riesgos-trabajo.php',

    // Utils
    'limpiar-cache.php',
    'deploy-now.php',
    'STOP_CRON.txt',
    'cron-deploy-safe.sh'
];

$copied = 0;
$failed = 0;

foreach ($allCriticalFiles as $file) {
    $source = $repoPath . '/' . $file;
    $dest = $prodPath . '/' . $file;

    if (file_exists($source)) {
        $destDir = dirname($dest);
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        if (copy($source, $dest)) {
            echo "✓ {$file}\n";
            $copied++;
        } else {
            echo "✗ ERROR: {$file}\n";
            $failed++;
        }
    } else {
        echo "⚠ NO EXISTE EN REPO: {$file}\n";
    }
}

echo "\nResumen: {$copied} copiados, {$failed} fallidos\n";

// 4. RSYNC COMPLETO
echo "\n4. SINCRONIZACIÓN COMPLETA (RSYNC):\n";
echo str_repeat('=', 60) . "\n";

$rsyncCmd = "rsync -av --delete " .
    "--exclude='.git' " .
    "--exclude='vendor/' " .
    "--exclude='logs/' " .
    "--exclude='storage/' " .
    "--exclude='app/config/config.php' " .
    "--exclude='includes/db.php' " .
    "{$repoPath}/ {$prodPath}/ 2>&1";

exec($rsyncCmd, $rsyncOutput, $rsyncRet);

echo "✓ Rsync completado (código: {$rsyncRet})\n";

// Mostrar últimas 5 líneas
$lastLines = array_slice($rsyncOutput, -5);
foreach ($lastLines as $line) {
    echo "  {$line}\n";
}

// 5. VERIFICACIÓN FINAL
echo "\n5. VERIFICACIÓN FINAL:\n";
echo str_repeat('=', 60) . "\n";

$verifyFiles = [
    'admin/client-detail.php' => ['Regenerar Plan', 'regenerarPlanModal'],
    'admin/includes/header.php' => ['Cache-Control: no-cache', 'inline-flex'],
    'admin/actions/regenerar-plan-pagos.php' => ['frecuencia_pago', 'DELETE FROM payments'],
    'STOP_CRON.txt' => ['STOP']
];

$allOk = true;

foreach ($verifyFiles as $file => $checks) {
    $fullPath = $prodPath . '/' . $file;

    if (file_exists($fullPath)) {
        $content = file_get_contents($fullPath);
        $fileOk = true;

        echo "✓ {$file} - EXISTE\n";

        foreach ($checks as $check) {
            if (strpos($content, $check) !== false) {
                echo "  ✓ Contiene: {$check}\n";
            } else {
                echo "  ✗ FALTA: {$check}\n";
                $fileOk = false;
                $allOk = false;
            }
        }
    } else {
        echo "✗ {$file} - NO EXISTE\n";
        $allOk = false;
    }
}

// 6. RESULTADO FINAL
echo "\n" . str_repeat('=', 60) . "\n";
if ($allOk) {
    echo "✅ ✅ ✅ TODO PERFECTO ✅ ✅ ✅\n";
    echo "\nPróximos pasos:\n";
    echo "1. Presiona Ctrl + Shift + R en el admin\n";
    echo "2. Verifica que veas los botones correctamente\n";
    echo "3. El cron está DETENIDO por STOP_CRON.txt\n";
    echo "4. Para futuros deployments usa: deploy-now.php\n";
} else {
    echo "⚠️ ALGUNOS ARCHIVOS TIENEN PROBLEMAS\n";
    echo "Revisa los detalles arriba\n";
}

echo "\n=== FIN ===\n";
?>
