<?php
session_start();
if (empty($_SESSION['admin_id'])) {
    die('Acceso denegado - Inicia sesión en /admin primero');
}

header('Content-Type: text/plain; charset=utf-8');

echo "=== DIAGNÓSTICO DE DEPLOYMENT ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

$prodPath = '/home/asegural/public_html/aseguralocr';
$repoPath = '/home/asegural/aseguralocr_repo';

// 1. Verificar archivos críticos en producción
echo "1. ARCHIVOS EN PRODUCCIÓN:\n";
echo str_repeat('-', 60) . "\n";

$criticalFiles = [
    'admin/includes/header.php',
    'admin/clients.php',
    'admin/pdf_mapper.php',
    'api/padron.php',
    'riesgos-trabajo.php',
    'autos.php',
    'hogar-comprensivo.php'
];

foreach ($criticalFiles as $file) {
    $fullPath = $prodPath . '/' . $file;
    if (file_exists($fullPath)) {
        $modTime = filemtime($fullPath);
        $size = filesize($fullPath);
        echo "✓ {$file}\n";
        echo "  Modificado: " . date('Y-m-d H:i:s', $modTime) . "\n";
        echo "  Tamaño: " . number_format($size) . " bytes\n";

        // Verificar contenido específico
        if ($file === 'admin/includes/header.php') {
            $content = file_get_contents($fullPath);
            if (strpos($content, 'text-decoration: none !important') !== false) {
                echo "  ✓ Tiene el fix de text-decoration\n";
            } else {
                echo "  ✗ NO tiene el fix de text-decoration\n";
            }
            if (strpos($content, 'display: inline-flex') !== false) {
                echo "  ✓ Tiene display: inline-flex\n";
            } else {
                echo "  ✗ NO tiene display: inline-flex\n";
            }
        }

        if ($file === 'admin/pdf_mapper.php') {
            $content = file_get_contents($fullPath);
            // Contar campos de autos
            preg_match_all("/\['key' => 'autos_/", $content, $autosMatches);
            $autosCount = count($autosMatches[0]);
            echo "  Campos Autos: {$autosCount}\n";

            // Contar campos de RT
            preg_match_all("/\['key' => 'rt_/", $content, $rtMatches);
            $rtCount = count($rtMatches[0]);
            echo "  Campos RT: {$rtCount}\n";
        }

        if ($file === 'autos.php' || $file === 'hogar-comprensivo.php') {
            $content = file_get_contents($fullPath);
            if (strpos($content, 'data-padron-nombre-completo') !== false) {
                echo "  ✓ Tiene data-padron-nombre-completo\n";
            } else {
                echo "  ✗ Tiene data-padron-nombre (viejo)\n";
            }
        }

    } else {
        echo "✗ {$file} - NO EXISTE\n";
    }
    echo "\n";
}

// 2. Verificar git lock
echo "\n2. ESTADO DEL REPOSITORIO:\n";
echo str_repeat('-', 60) . "\n";

$lockFile = $repoPath . '/.git/index.lock';
if (file_exists($lockFile)) {
    echo "⚠ Git lock file EXISTE\n";
    echo "  Ruta: {$lockFile}\n";
} else {
    echo "✓ No hay git lock file\n";
}

// 3. Verificar último commit en repo
if (is_dir($repoPath)) {
    echo "\n3. ÚLTIMO COMMIT EN REPO:\n";
    echo str_repeat('-', 60) . "\n";

    chdir($repoPath);
    exec('git log -1 --oneline 2>&1', $gitLog, $ret);
    if ($ret === 0 && !empty($gitLog)) {
        echo implode("\n", $gitLog) . "\n";
    } else {
        echo "Error al obtener git log\n";
    }

    // Verificar si hay archivos modificados en el repo
    exec('git status --porcelain 2>&1', $gitStatus, $ret2);
    if (!empty($gitStatus)) {
        echo "\n⚠ Archivos modificados en repo:\n";
        echo implode("\n", $gitStatus) . "\n";
    } else {
        echo "\n✓ Repo limpio (sin cambios)\n";
    }
}

// 4. Comparar archivos entre repo y producción
echo "\n4. COMPARACIÓN REPO vs PRODUCCIÓN:\n";
echo str_repeat('-', 60) . "\n";

foreach ($criticalFiles as $file) {
    $repoFile = $repoPath . '/' . $file;
    $prodFile = $prodPath . '/' . $file;

    if (file_exists($repoFile) && file_exists($prodFile)) {
        $repoMd5 = md5_file($repoFile);
        $prodMd5 = md5_file($prodFile);

        if ($repoMd5 === $prodMd5) {
            echo "✓ {$file} - IGUAL\n";
        } else {
            echo "✗ {$file} - DIFERENTE\n";
            $repoTime = filemtime($repoFile);
            $prodTime = filemtime($prodFile);
            echo "  Repo: " . date('Y-m-d H:i:s', $repoTime) . "\n";
            echo "  Prod: " . date('Y-m-d H:i:s', $prodTime) . "\n";

            if ($repoTime > $prodTime) {
                echo "  ⚠ Repo es más reciente\n";
            } else {
                echo "  ⚠ Producción es más reciente\n";
            }
        }
    }
}

// 5. Verificar logs de cron si existen
echo "\n5. LOGS DE DEPLOYMENT:\n";
echo str_repeat('-', 60) . "\n";

$logFile = '/home/asegural/deployment.log';
if (file_exists($logFile)) {
    $logs = file($logFile);
    $lastLines = array_slice($logs, -30);
    echo implode("", $lastLines);
} else {
    echo "No se encontró archivo de logs\n";
}

echo "\n=== FIN DIAGNÓSTICO ===\n";
?>
