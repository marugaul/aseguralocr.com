<?php
// test-db-conn.php
// Prueba simple de conexión PDO a MySQL (muestra errores para diagnóstico).
// EDITA: $dbPass con la contraseña real antes de ejecutar.

ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: text/plain; charset=utf-8');

$hosts = ['localhost', '127.0.0.1'];
$dbName = 'asegural_aseguralocr';   // nombre exacto de la BD que tienes en phpMyAdmin
$dbUser = 'asegural_marugaul';               // tu usuario según indicas
$dbPass = 'Marden7i/';                       // <-- PON AQUÍ TU CONTRASEÑA temporalmente
$charset = 'utf8mb4';

echo "Prueba de conexión a MySQL\n";
echo "DB: {$dbName}\n";
echo "Usuario: {$dbUser}\n\n";

foreach ($hosts as $host) {
    echo "---- Intentando host: {$host} ----\n";
    $dsn = "mysql:host={$host};dbname={$dbName};charset={$charset}";
    try {
        $pdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 5
        ]);
        echo "Conexión OK ✅\n";
        // Mostrar el usuario actual que MySQL reporta
        $row = $pdo->query("SELECT CURRENT_USER() AS current_user, USER() AS user_func")->fetch();
        echo "CURRENT_USER(): " . ($row['current_user'] ?? 'n/a') . "\n";
        echo "USER(): " . ($row['user_func'] ?? 'n/a') . "\n";
        // Listar 3 tablas si la DB existe
        try {
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_NUM);
            echo "Tablas en la BD (" . count($tables) . "):\n";
            $max = min(10, count($tables));
            for ($i = 0; $i < $max; $i++) {
                echo " - " . $tables[$i][0] . "\n";
            }
            if (count($tables) > $max) echo " ... y más\n";
        } catch (Throwable $e) {
            echo "No se pudieron listar tablas: " . $e->getMessage() . "\n";
        }

    } catch (PDOException $ex) {
        echo "FALLO ❌ : " . $ex->getMessage() . "\n";
        // Mensajes comunes: Access denied, Can't connect to MySQL server, SQLSTATE[HY000] ...
        // Si es Access denied, revisa usuario/contraseña y que el usuario tenga permisos sobre la BD
    }
    echo "\n";
}

echo "---- FIN de la prueba ----\n\n";
echo "NOTA: Borra este archivo del servidor cuando termines por seguridad.\n";