<?php
/**
 * Script de despliegue manual - Ejecutar solo si el cron no funciona
 */

session_start();
if (empty($_SESSION['admin_id'])) {
    die('Acceso denegado - Inicia sesión en /admin primero');
}

echo "<h1>Despliegue Manual</h1>";
echo "<pre>";

$repoPath = '/home/asegural/aseguralocr_repo';

// Cambiar al directorio del repositorio
if (!is_dir($repoPath)) {
    die("Error: No existe el directorio del repositorio en $repoPath");
}

chdir($repoPath);
echo "✓ Cambiado a directorio: $repoPath\n\n";

// Git fetch
echo "→ Ejecutando git fetch...\n";
$output = shell_exec('git fetch origin 2>&1');
echo $output . "\n";

// Git reset --hard origin/main
echo "→ Ejecutando git reset --hard origin/main...\n";
$output = shell_exec('git reset --hard origin/main 2>&1');
echo $output . "\n";

// Rsync a producción
echo "→ Copiando archivos a producción...\n";
$rsyncCmd = "rsync -av --delete --exclude='.git' --exclude='vendor/' --exclude='logs/' --exclude='storage/' --exclude='app/config/config.php' --exclude='includes/db.php' $repoPath/ /home/asegural/public_html/aseguralocr/ 2>&1";
$output = shell_exec($rsyncCmd);
echo "Archivos sincronizados\n\n";

echo "✅ DESPLIEGUE COMPLETADO\n";
echo "</pre>";

echo "<p><strong>Ahora refresca:</strong> <a href='/admin/padron_importar.php'>/admin/padron_importar.php</a></p>";
?>
