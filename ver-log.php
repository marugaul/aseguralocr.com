<?php
header('Content-Type: text/plain; charset=utf-8');

$logFile = '/home/asegural/deployment.log';

if (file_exists($logFile)) {
    $lines = file($logFile);
    $last30 = array_slice($lines, -30);

    echo "=== ÚLTIMAS 30 LÍNEAS DEL LOG ===\n\n";
    echo implode("", $last30);
} else {
    echo "❌ Log no existe aún: {$logFile}\n\n";
    echo "Espera 3 minutos para que el cron ejecute.\n";
}
?>
