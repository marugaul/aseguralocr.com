<?php
session_start();
if (empty($_SESSION['admin_id'])) {
    die('Acceso denegado - Inicia sesión en /admin primero');
}

header('Content-Type: text/plain; charset=utf-8');

echo "=== LIMPIEZA DE CACHÉ ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

// 1. Limpiar OPcache (PHP)
echo "1. LIMPIANDO OPCACHE PHP:\n";
echo str_repeat('-', 60) . "\n";
if (function_exists('opcache_reset')) {
    if (opcache_reset()) {
        echo "✓ OPcache limpiado exitosamente\n";
    } else {
        echo "✗ No se pudo limpiar OPcache\n";
    }

    $status = opcache_get_status();
    echo "  Archivos en caché: " . ($status['opcache_statistics']['num_cached_scripts'] ?? 0) . "\n";
    echo "  Memoria usada: " . number_format(($status['memory_usage']['used_memory'] ?? 0) / 1024 / 1024, 2) . " MB\n";
} else {
    echo "⚠ OPcache no está disponible o no está habilitado\n";
}

// 2. Limpiar sesiones viejas
echo "\n2. LIMPIANDO SESIONES VIEJAS:\n";
echo str_repeat('-', 60) . "\n";
$sessionPath = session_save_path();
if (is_dir($sessionPath)) {
    echo "Ruta de sesiones: {$sessionPath}\n";
    $count = 0;
    $deleted = 0;
    $files = glob($sessionPath . '/sess_*');
    if ($files) {
        foreach ($files as $file) {
            $count++;
            // Eliminar sesiones más viejas de 24 horas
            if (filemtime($file) < time() - 86400) {
                @unlink($file);
                $deleted++;
            }
        }
    }
    echo "✓ Sesiones encontradas: {$count}\n";
    echo "✓ Sesiones viejas eliminadas: {$deleted}\n";
} else {
    echo "⚠ No se pudo acceder al directorio de sesiones\n";
}

// 3. Verificar headers de caché
echo "\n3. HEADERS DE CACHÉ ACTUALES:\n";
echo str_repeat('-', 60) . "\n";
$testUrl = 'https://www.aseguralocr.com/admin/includes/header.php';
$headers = @get_headers($testUrl, 1);
if ($headers) {
    $cacheHeaders = ['Cache-Control', 'Expires', 'Pragma', 'Last-Modified', 'ETag'];
    foreach ($cacheHeaders as $header) {
        if (isset($headers[$header])) {
            echo "  {$header}: " . (is_array($headers[$header]) ? implode(', ', $headers[$header]) : $headers[$header]) . "\n";
        }
    }
} else {
    echo "⚠ No se pudieron obtener headers\n";
}

// 4. Verificar archivos críticos - timestamp
echo "\n4. TIMESTAMPS DE ARCHIVOS CRÍTICOS:\n";
echo str_repeat('-', 60) . "\n";
$prodPath = '/home/asegural/public_html/aseguralocr';
$criticalFiles = [
    'admin/includes/header.php',
    'admin/client-detail.php',
    'admin/clients.php',
    'admin/actions/regenerar-plan-pagos.php'
];

foreach ($criticalFiles as $file) {
    $fullPath = $prodPath . '/' . $file;
    if (file_exists($fullPath)) {
        $modTime = filemtime($fullPath);
        $ago = time() - $modTime;
        $agoText = '';
        if ($ago < 60) {
            $agoText = "{$ago} segundos atrás";
        } elseif ($ago < 3600) {
            $agoText = floor($ago / 60) . " minutos atrás";
        } else {
            $agoText = floor($ago / 3600) . " horas atrás";
        }

        echo "✓ {$file}\n";
        echo "  Modificado: " . date('Y-m-d H:i:s', $modTime) . " ({$agoText})\n";

        // Verificar contenido específico
        if ($file === 'admin/includes/header.php') {
            $content = file_get_contents($fullPath);
            $hasInlineFlex = strpos($content, 'display: inline-flex') !== false;
            $hasTextDecoration = strpos($content, 'text-decoration: none !important') !== false;

            echo "  - inline-flex: " . ($hasInlineFlex ? "✓ SÍ" : "✗ NO") . "\n";
            echo "  - text-decoration fix: " . ($hasTextDecoration ? "✓ SÍ" : "✗ NO") . "\n";
        }

        if ($file === 'admin/client-detail.php') {
            $content = file_get_contents($fullPath);
            $hasRegenerarBtn = strpos($content, 'Regenerar Plan') !== false;
            $hasModal = strpos($content, 'regenerarPlanModal') !== false;

            echo "  - Botón Regenerar: " . ($hasRegenerarBtn ? "✓ SÍ" : "✗ NO") . "\n";
            echo "  - Modal frecuencia: " . ($hasModal ? "✓ SÍ" : "✗ NO") . "\n";
        }
    } else {
        echo "✗ {$file} - NO EXISTE\n";
    }
    echo "\n";
}

// 5. Instrucciones para usuario
echo "\n5. INSTRUCCIONES PARA EL NAVEGADOR:\n";
echo str_repeat('-', 60) . "\n";
echo "Para limpiar el caché del navegador:\n\n";
echo "Chrome/Edge:\n";
echo "  - Presiona Ctrl + Shift + R (fuerza recarga sin caché)\n";
echo "  - O Ctrl + Shift + Delete → Limpiar caché\n\n";
echo "Firefox:\n";
echo "  - Presiona Ctrl + Shift + R (fuerza recarga sin caché)\n";
echo "  - O Ctrl + Shift + Delete → Limpiar caché\n\n";
echo "Safari:\n";
echo "  - Presiona Cmd + Option + R (fuerza recarga sin caché)\n";
echo "  - O Cmd + Option + E → Vaciar caché\n\n";

// 6. Generar timestamp único para forzar recarga
echo "\n6. URL CON CACHE BUSTER:\n";
echo str_repeat('-', 60) . "\n";
$timestamp = time();
echo "Abre esta URL para forzar recarga:\n";
echo "https://www.aseguralocr.com/admin/clients.php?_={$timestamp}\n\n";

echo "=== FIN ===\n";
?>
