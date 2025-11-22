<?php
// Descarga robusta de composer.phar (sin SSH)
// Intenta con cURL y si no existe, usa file_get_contents.

set_time_limit(0);
$dest = __DIR__ . '/composer.phar';
$url  = 'https://getcomposer.org/download/latest-stable/composer.phar';

function download($url, $dest) {
    // cURL primero
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        $fh = fopen($dest, 'w');
        curl_setopt_array($ch, [
            CURLOPT_FILE => $fh,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_FAILONERROR => true,
            CURLOPT_TIMEOUT => 300,
        ]);
        $ok = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        fclose($fh);
        if ($ok === false) {
            @unlink($dest);
            echo "cURL falló: $err\n";
            return false;
        }
        return true;
    }

    // fallback: file_get_contents
    $data = @file_get_contents($url);
    if ($data === false) {
        echo "file_get_contents falló al descargar.\n";
        return false;
    }
    $ok = @file_put_contents($dest, $data);
    return $ok !== false;
}

echo "<pre>";
if (download($url, $dest)) {
    echo "✅ Descargado composer.phar en: $dest\n";
    echo "Tamaño: " . filesize($dest) . " bytes\n";
} else {
    echo "❌ No se pudo descargar composer.phar.\n";
    echo "Verifica que allow_url_fopen o cURL estén habilitados en tu hosting.\n";
}
echo "</pre>";
