<?php
declare(strict_types=1);
// download_pdf.php - entrega PDF por referencia (verifica existencia en DB)

// Configuraci¨®n
const DB_HOST = 'localhost';
const DB_NAME = 'asegural_aseguralocr';
const DB_USER = 'asegural_marugaul';
const DB_PASS = 'Marden7i/';
const DB_CHARSET = 'utf8mb4';

header('Content-Type: text/plain; charset=utf-8');

// Helper logging
function log_err(string $m){ error_log('[download_pdf] '.$m); }

// Validar par¨¢metro
$ref = $_GET['ref'] ?? '';
if ($ref === '') {
    http_response_code(400);
    echo 'Referencia (ref) requerida';
    exit;
}
$ref = basename($ref);

// Conectar DB para verificar que la referencia existe
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (Throwable $e) {
    log_err('DB connect error: '.$e->getMessage());
    http_response_code(500);
    echo 'Error interno';
    exit;
}

// Comprobar submissions
try {
    $stmt = $pdo->prepare("SELECT referencia, created_at FROM submissions WHERE referencia = :r LIMIT 1");
    $stmt->execute([':r' => $ref]);
    $row = $stmt->fetch();
    if (!$row) {
        http_response_code(404);
        echo 'Referencia no encontrada';
        exit;
    }
} catch (Throwable $e) {
    log_err('DB query error: '.$e->getMessage());
    http_response_code(500);
    echo 'Error interno';
    exit;
}

// Ubicaci¨®n del PDF
$pdfDir = realpath(__DIR__ . '/../storage/pdfs') ?: (__DIR__ . '/../storage/pdfs');
$candidates = [
    $pdfDir . '/' . $ref . '.pdf',
    $pdfDir . '/' . $ref
];
$found = null;
foreach ($candidates as $c) {
    if (is_file($c)) { $found = $c; break; }
}

if (!$found) {
    http_response_code(404);
    echo 'Archivo PDF no encontrado';
    exit;
}

// Entregar el archivo con headers seguros
$basename = basename($found);
$mime = mime_content_type($found) ?: 'application/pdf';
header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="' . $basename . '"');
header('Content-Length: ' . filesize($found));
header('Cache-Control: private, max-age=3600');
header('X-Content-Type-Options: nosniff');
readfile($found);
exit;