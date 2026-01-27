<?php
/**
 * Arreglar el lock de git que está bloqueando el cron
 */

session_start();
if (empty($_SESSION['admin_id'])) {
    die('Acceso denegado');
}

echo "<h1>Arreglar Git Lock</h1>";
echo "<pre>";

$lockFile = '/home/asegural/aseguralocr_repo/.git/index.lock';

echo "Verificando lock de git...\n\n";

if (file_exists($lockFile)) {
    echo "❌ LOCK ENCONTRADO: $lockFile\n";
    echo "Este archivo está bloqueando el cron.\n\n";

    if (isset($_GET['fix'])) {
        echo "→ Eliminando archivo de lock...\n";
        if (unlink($lockFile)) {
            echo "✅ Lock eliminado exitosamente\n\n";
            echo "El cron ahora puede funcionar correctamente.\n";
            echo "En 1 minuto debería desplegar los cambios.\n";
        } else {
            echo "❌ Error al eliminar lock. Verifica permisos.\n";
        }
    } else {
        echo "<a href='?fix=1' style='background: #dc2626; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px;'>ELIMINAR LOCK AHORA</a>\n";
    }
} else {
    echo "✅ No hay lock de git. El cron debería funcionar correctamente.\n";
}

echo "</pre>";

echo "<p><a href='/admin/dashboard.php'>← Volver</a></p>";
?>
