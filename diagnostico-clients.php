<?php
// Diagnóstico de estructura de tabla clients
$pdo = new PDO("mysql:host=localhost;dbname=asegural_aseguralocr;charset=utf8mb4",
    "asegural_marugaul", "Marden7i/", [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

header('Content-Type: text/plain; charset=utf-8');

echo "ESTRUCTURA DE TABLA CLIENTS:\n";
echo "========================================\n\n";

$result = $pdo->query("SHOW CREATE TABLE clients");
$row = $result->fetch(PDO::FETCH_ASSOC);

echo $row['Create Table'];
echo "\n\n========================================\n";
echo "COLUMNAS:\n\n";

$cols = $pdo->query("SHOW COLUMNS FROM clients");
foreach ($cols as $col) {
    echo sprintf("%-20s %-30s\n", $col['Field'], $col['Type']);
}
?>
