<?php
/**
 * Deploy RÁPIDO - Solo rsync (asume que repo ya está actualizado)
 */
session_start();
if (empty($_SESSION['admin_id'])) {
    die('Acceso denegado - Inicia sesión en /admin primero');
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Deploy Rápido</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #00ff00; }
        h1 { color: #00ff00; }
        pre { background: #000; padding: 20px; border-radius: 5px; }
        .success { color: #00ff00; font-weight: bold; }
        .error { color: #ff0000; font-weight: bold; }
        a { color: #00aaff; }
    </style>
</head>
<body>

<h1>⚡ Deploy Rápido (Solo Rsync)</h1>
<pre><?php

$repoPath = '/home/asegural/aseguralocr_repo';
$prodPath = '/home/asegural/public_html/aseguralocr';

echo "========================================\n";
echo "INICIO - " . date('Y-m-d H:i:s') . "\n";
echo "========================================\n\n";

// Eliminar git lock si existe
$lockFile = $repoPath . '/.git/index.lock';
if (file_exists($lockFile)) {
    echo "⚠ Eliminando git lock...\n";
    @unlink($lockFile);
}

// Solo hacer rsync
echo "📦 Copiando archivos de repo a producción...\n\n";
$rsyncCmd = "rsync -av --exclude='.git' --exclude='vendor/' --exclude='app/config/config.php' --exclude='includes/db.php' {$repoPath}/ {$prodPath}/ 2>&1";

exec($rsyncCmd, $output, $ret);

// Mostrar últimas 20 líneas
$lastLines = array_slice($output, -20);
foreach ($lastLines as $line) {
    echo $line . "\n";
}

if ($ret === 0 || $ret === 24) { // 24 = algunas files vanished pero ok
    echo "\n<span class='success'>✅ RSYNC COMPLETADO</span>\n";
} else {
    echo "\n<span class='error'>❌ ERROR en rsync (código: $ret)</span>\n";
}

echo "\n========================================\n";
echo "FIN - " . date('Y-m-d H:i:s') . "\n";
echo "========================================\n";

// Verificar archivos críticos
echo "\n📋 Verificando archivos críticos:\n\n";

$criticalFiles = [
    'admin/includes/header.php',
    'admin/clients.php',
    'admin/pdf_mapper.php',
    'api/padron.php'
];

foreach ($criticalFiles as $file) {
    $fullPath = $prodPath . '/' . $file;
    if (file_exists($fullPath)) {
        $modTime = date('Y-m-d H:i:s', filemtime($fullPath));
        echo "✓ {$file} - Modificado: {$modTime}\n";
    } else {
        echo "✗ {$file} - NO EXISTE\n";
    }
}

?></pre>

<h2>✅ Siguiente Paso:</h2>
<ul>
    <li><a href="/admin/clients.php">📋 Ir a Clientes (verificar botones)</a></li>
    <li><a href="/admin/pdf_mapper.php?tipo=autos&pdf=AUTOS_FORMULARIO.pdf">🚗 PDF Mapper Autos</a></li>
</ul>

<p><strong>Nota:</strong> Este script solo copia archivos del repo a producción, sin hacer git fetch/pull.</p>

</body>
</html>
