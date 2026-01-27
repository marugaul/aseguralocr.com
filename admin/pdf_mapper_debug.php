<?php
// admin/pdf_mapper_debug.php - Diagnóstico del PDF Mapper
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../app/services/Security.php';

Security::start();

$debug = [];

// 1. Verificar sesión
$debug['session'] = [
    'status' => session_status() === PHP_SESSION_ACTIVE ? 'active' : 'inactive',
    'id' => session_id(),
    'admin_logged' => $_SESSION['admin_logged'] ?? false
];

// 2. Verificar directorios
$externalDir = dirname(__DIR__, 2) . '/aseguralocr_mappings/';
$localDir = __DIR__ . '/../mappings/';

$debug['directories'] = [
    'external' => [
        'path' => $externalDir,
        'exists' => is_dir($externalDir),
        'writable' => is_writable($externalDir)
    ],
    'local' => [
        'path' => $localDir,
        'exists' => is_dir($localDir),
        'writable' => is_writable($localDir)
    ]
];

// 3. Intentar crear directorio externo si no existe
if (!is_dir($externalDir)) {
    $created = @mkdir($externalDir, 0777, true);
    $debug['directories']['external']['created'] = $created;
    if ($created) {
        $debug['directories']['external']['exists'] = true;
        $debug['directories']['external']['writable'] = is_writable($externalDir);
    }
}

// 4. Listar archivos de mapeo existentes
$debug['mappings'] = [];
foreach ([$externalDir, $localDir] as $dir) {
    if (is_dir($dir)) {
        $files = glob($dir . '*_mapping.json');
        foreach ($files as $file) {
            $debug['mappings'][] = [
                'file' => basename($file),
                'dir' => ($dir === $externalDir) ? 'external' : 'local',
                'size' => filesize($file),
                'modified' => date('Y-m-d H:i:s', filemtime($file))
            ];
        }
    }
}

// 5. Test de escritura
$testFile = (is_writable($externalDir) ? $externalDir : $localDir) . 'test_write.tmp';
$testWrite = @file_put_contents($testFile, 'test');
$debug['write_test'] = [
    'success' => $testWrite !== false,
    'path' => $testFile,
    'bytes' => $testWrite
];
if ($testWrite !== false) {
    @unlink($testFile);
}

// 6. PHP Info relevante
$debug['php'] = [
    'version' => PHP_VERSION,
    'user' => function_exists('posix_getpwuid') ? posix_getpwuid(posix_geteuid())['name'] : 'unknown',
    'https' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'request_uri' => $_SERVER['REQUEST_URI']
];

echo json_encode($debug, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
