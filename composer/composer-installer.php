<?php
// Descarga del instalador oficial de Composer
$installer = 'https://getcomposer.org/installer';
$installerFile = 'composer-setup.php';

file_put_contents($installerFile, file_get_contents($installer));

// Verificación de hash (opcional pero recomendable)
$hash = trim(file_get_contents('https://composer.github.io/installer.sig'));
if (hash_file('SHA384', $installerFile) === $hash) {
    echo "Verificación OK. Instalando Composer...\n";
    // Instala Composer en el mismo directorio
    @unlink('composer.phar');
    passthru('php ' . $installerFile . ' --install-dir=. --filename=composer.phar');
    echo "\n✅ Composer instalado correctamente.\n";
} else {
    echo "❌ Verificación falló.";
}
