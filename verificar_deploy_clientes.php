<?php
header('Content-Type: text/html; charset=utf-8');
echo "<h1>Estado de Deployment - Clientes</h1>";

// Verificar header.php
$headerPath = __DIR__ . '/admin/includes/header.php';
if (file_exists($headerPath)) {
    echo "<h2>✅ Header encontrado</h2>";
    echo "Última modificación: " . date('Y-m-d H:i:s', filemtime($headerPath)) . "<br><br>";

    $content = file_get_contents($headerPath);

    // Buscar CSS de action-btn
    if (strpos($content, 'text-decoration: none !important') !== false) {
        echo "✅ CSS de action-btn actualizado (con text-decoration: none !important)<br>";
    } else {
        echo "❌ CSS de action-btn NO actualizado (falta text-decoration: none !important)<br>";
    }

    if (strpos($content, 'display: inline-flex') !== false) {
        echo "✅ CSS tiene display: inline-flex<br>";
    } else {
        echo "❌ CSS NO tiene display: inline-flex<br>";
    }

    if (strpos($content, 'color: #2563eb !important') !== false) {
        echo "✅ CSS tiene colores con !important<br>";
    } else {
        echo "❌ CSS NO tiene colores con !important<br>";
    }
} else {
    echo "<h2>❌ Header NO encontrado</h2>";
}

echo "<hr>";

// Verificar clients.php
$clientsPath = __DIR__ . '/admin/clients.php';
if (file_exists($clientsPath)) {
    echo "<h2>✅ clients.php encontrado</h2>";
    echo "Última modificación: " . date('Y-m-d H:i:s', filemtime($clientsPath)) . "<br><br>";

    $content = file_get_contents($clientsPath);

    if (strpos($content, 'action-buttons') !== false) {
        echo "✅ HTML tiene div action-buttons<br>";
    }

    if (strpos($content, 'action-btn view') !== false) {
        echo "✅ HTML tiene botón Ver<br>";
    }

    if (strpos($content, 'action-btn edit') !== false) {
        echo "✅ HTML tiene botón Nueva póliza<br>";
    }
} else {
    echo "<h2>❌ clients.php NO encontrado</h2>";
}

echo "<hr>";
echo "<h3>Diagnóstico:</h3>";
echo "<p>Si ves ❌, el cron aún no ha desplegado los cambios.</p>";
echo "<p>Espera 3 minutos y recarga esta página.</p>";
echo "<p>Último commit esperado: 240e24f (Fix action buttons styling)</p>";
?>
