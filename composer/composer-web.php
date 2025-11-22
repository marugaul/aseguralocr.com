<?php
// composer-web.php — ejecutar Composer desde navegador sin SSH
// --------------------------------------------------------------
// Uso:
//   ?cmd=-V                → mostrar versión
//   ?cmd=install           → instalar dependencias
//   ?cmd=update            → actualizar dependencias
//   ?cmd="require vendor/package:^1.0" → instalar un paquete
//
// Ejemplo:
//   https://tusitio.com/composer/composer-web.php?cmd=-V
// --------------------------------------------------------------

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors', 0);
@ini_set('memory_limit', '1024M');
@set_time_limit(0);

header('Content-Type: text/plain; charset=UTF-8');

// ubicación del phar
$phar = __DIR__ . '/composer.phar';

// comando recibido por parámetro (?cmd=)
$cmd = isset($_GET['cmd']) ? trim($_GET['cmd']) : 'install';

// verifica existencia de composer.phar
if (!file_exists($phar)) {
    http_response_code(500);
    exit("❌ No se encuentra composer.phar en: $phar\n");
}

// variables de entorno necesarias para Composer
putenv('COMPOSER_DISABLE_XDEBUG_WARN=1');
putenv('COMPOSER_ALLOW_SUPERUSER=1');

// ejecuta composer mediante PHP CLI interno (funciona en la mayoría de hostings)
echo "🧩 Ejecutando Composer...\n\n";
flush();

// intenta shell_exec primero, si está bloqueado usa require del PHAR
if (function_exists('shell_exec')) {
    echo shell_exec("php $phar $cmd 2>&1");
} else {
    // modo fallback: ejecución interna (sin shell)
    if (!class_exists('Phar')) {
        exit("❌ La extensión Phar está deshabilitada en el servidor.\n");
    }
    // simula argv y argc para el binario de Composer
    $_SERVER['argv'] = array_merge(['composer'], explode(' ', $cmd));
    $_SERVER['argc'] = count($_SERVER['argv']);
    require 'phar://composer.phar/bin/composer';
}

echo "\n✅ Finalizado.\n";
?>
