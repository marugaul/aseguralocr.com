<?php
// includes/db.php
// Conexión a base de datos MySQL - AseguraloCR
const DB_HOST = 'localhost';
const DB_NAME = 'asegural_aseguralocr';
const DB_USER = 'asegural_marugaul';
const DB_PASS = 'Marden7i/';
const DB_CHARSET = 'utf8mb4';

global $pdo;

$dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET;
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (Exception $e) {
    die('Error de conexión a la base de datos.');
}

// Conexión MySQLi para scripts legacy (padron importer)
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Error de conexión MySQLi: ' . $conn->connect_error);
}
$conn->set_charset(DB_CHARSET);
