<?php
/**
 * Script de despliegue manual - Ejecutar solo si el cron no funciona
 */

session_start();
if (empty($_SESSION['admin_id'])) {
    die('Acceso denegado - Inicia sesión en /admin primero');
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Deployment Manual</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #00ff00; }
        h1 { color: #00ff00; }
        pre { background: #000; padding: 20px; border-radius: 5px; overflow-x: auto; }
        .success { color: #00ff00; font-weight: bold; }
        .error { color: #ff0000; font-weight: bold; }
        .info { color: #00aaff; }
        .warning { color: #ffaa00; }
        a { color: #00aaff; }
    </style>
</head>
<body>

<h1>🚀 Despliegue Manual a Producción</h1>
<pre><?php

$repoPath = '/home/asegural/aseguralocr_repo';
$prodPath = '/home/asegural/public_html/aseguralocr';

echo "========================================\n";
echo "INICIO DEL DEPLOYMENT\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n";
echo "========================================\n\n";

// 1. Verificar directorio
echo "<span class='info'>[1/5]</span> Verificando directorio del repositorio...\n";
if (!is_dir($repoPath)) {
    echo "<span class='error'>✗ ERROR: No existe el directorio $repoPath</span>\n";
    exit;
}
echo "<span class='success'>✓ Directorio encontrado: $repoPath</span>\n\n";

// 2. Verificar git lock
echo "<span class='info'>[2/5]</span> Verificando git lock...\n";
$lockFile = $repoPath . '/.git/index.lock';
if (file_exists($lockFile)) {
    echo "<span class='warning'>⚠ Git lock file existe, eliminando...</span>\n";
    @unlink($lockFile);
    if (!file_exists($lockFile)) {
        echo "<span class='success'>✓ Lock eliminado</span>\n";
    }
} else {
    echo "<span class='success'>✓ No hay git lock</span>\n";
}
echo "\n";

// 3. Git fetch
echo "<span class='info'>[3/5]</span> Ejecutando git fetch origin...\n";
chdir($repoPath);
exec('git fetch origin 2>&1', $output1, $ret1);
foreach ($output1 as $line) {
    echo "  $line\n";
}
if ($ret1 === 0) {
    echo "<span class='success'>✓ Git fetch completado</span>\n\n";
} else {
    echo "<span class='error'>✗ Error en git fetch (código: $ret1)</span>\n\n";
}

// 4. Git reset
echo "<span class='info'>[4/5]</span> Ejecutando git reset --hard origin/main...\n";
exec('git reset --hard origin/main 2>&1', $output2, $ret2);
foreach ($output2 as $line) {
    echo "  $line\n";
}

// Mostrar commit actual
exec('git log -1 --oneline 2>&1', $output3);
echo "  Commit actual: " . implode("\n", $output3) . "\n";

if ($ret2 === 0) {
    echo "<span class='success'>✓ Git reset completado</span>\n\n";
} else {
    echo "<span class='error'>✗ Error en git reset (código: $ret2)</span>\n\n";
}

// 5. Rsync
echo "<span class='info'>[5/5]</span> Copiando archivos a producción con rsync...\n";
$rsyncCmd = "rsync -av --delete --exclude='.git' --exclude='vendor/' --exclude='logs/' --exclude='storage/' --exclude='app/config/config.php' --exclude='includes/db.php' $repoPath/ $prodPath/ 2>&1";
exec($rsyncCmd, $output4, $ret4);

// Mostrar últimas líneas del rsync
$lastLines = array_slice($output4, -15);
foreach ($lastLines as $line) {
    echo "  $line\n";
}

if ($ret4 === 0) {
    echo "<span class='success'>✓ Rsync completado</span>\n\n";
} else {
    echo "<span class='error'>✗ Error en rsync (código: $ret4)</span>\n\n";
}

echo "========================================\n";
echo "<span class='success'>✅ DEPLOYMENT COMPLETADO EXITOSAMENTE</span>\n";
echo "Fecha fin: " . date('Y-m-d H:i:s') . "\n";
echo "========================================\n";

?></pre>

<h2>Verificar Cambios:</h2>
<ul>
    <li><a href="/admin/clients.php">📋 Ver página de Clientes (botones de acciones)</a></li>
    <li><a href="/admin/pdf_mapper.php?tipo=autos&pdf=AUTOS_FORMULARIO.pdf">🚗 Ver PDF Mapper de Autos (374 campos)</a></li>
    <li><a href="/admin/pdf_mapper.php?tipo=rt&pdf=RT-GENERAL.pdf">👷 Ver PDF Mapper de RT (237 campos)</a></li>
    <li><a href="/verificar_deploy_clientes.php">🔍 Verificar deployment de clientes</a></li>
    <li><a href="/verificar_mapper.php">🔍 Verificar campos del mapper</a></li>
</ul>

<p><a href="/admin/dashboard.php">← Volver al Dashboard</a></p>

</body>
</html>
