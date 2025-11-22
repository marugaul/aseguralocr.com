<?php
// admin/aseguralo_ssh_config.php
// Configuración para ASEGURALO_SSH.php
// AJUSTA ESTOS VALORES antes de usar

return [
    // Hosts autorizados (solo a estos hosts se podrá conectar)
    // Formato: 'nombre-amigable' => ['host' => 'IP_o_hostname', 'user' => 'usuario_remoto', 'port' => 22]
    'allowed_hosts' => [
        'mi-servidor' => ['host' => '123.45.67.89', 'user' => 'asegural', 'port' => 22],
        // 'otro' => ['host' => 'example.com', 'user' => 'ubuntu', 'port' => 2222],
    ],

    // Ruta absoluta a la clave privada en el servidor (la que usarás para autenticar)
    // Ej: '/home/asegural/.ssh/id_rsa'
    'private_key_path' => __DIR__ . '/../.ssh/id_rsa', // AJUSTA si tu clave está en otra ruta

    // Si quieres obligar a pedir la contraseña del admin antes de ejecutar, pon true.
    // En ese caso el script pedirá la contraseña y la verificará con el hash 'admin_password_hash' abajo.
    'require_admin_password' => true,

    // Si require_admin_password = true, pon aquí el hash bcrypt de la contraseña del admin.
    // Ejecuta localmente: php -r "echo password_hash('MiPassAdmin', PASSWORD_DEFAULT), PHP_EOL;"
    'admin_password_hash' => '$2y$10$REEMPLAZAME_POR_EL_HASH_GENERADO',

    // Ruta del log donde se registrarán las ejecuciones (asegúrate que PHP pueda escribirla)
    'log_file' => __DIR__ . '/../logs/aseguralo_ssh.log',

    // Opciones extra SSH (se añadirán como -o KEY=VAL), por ejemplo evitar interactividad:
    'ssh_options' => [
        '-o' => ['BatchMode=yes', 'StrictHostKeyChecking=no']
    ],

    // Tiempo máximo de ejecución del proceso (segundos). Ajusta según necesidad.
    'proc_timeout' => 120,
];