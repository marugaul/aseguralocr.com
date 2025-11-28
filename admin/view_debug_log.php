<?php
// admin/view_debug_log.php - View debug log (temporary)
$logFile = __DIR__ . '/../logs/admin_login_debug.log';

// Simple auth check via query param (temporary)
if ($_GET['key'] !== 'debug2024') {
    die('Access denied');
}

// Clear log if requested
if (isset($_GET['clear'])) {
    file_put_contents($logFile, '');
    header('Location: ?key=debug2024');
    exit;
}

$content = file_exists($logFile) ? file_get_contents($logFile) : 'Log file empty or not found';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Log</title>
    <style>
        body { font-family: monospace; background: #1a1a2e; color: #0f0; padding: 20px; }
        pre { white-space: pre-wrap; word-wrap: break-word; }
        a { color: #fff; margin-right: 20px; }
    </style>
</head>
<body>
    <h2>Admin Login Debug Log</h2>
    <p>
        <a href="?key=debug2024">Refresh</a>
        <a href="?key=debug2024&clear=1" onclick="return confirm('Clear log?')">Clear Log</a>
        <a href="/admin/login.php">Go to Login</a>
    </p>
    <hr>
    <pre><?= htmlspecialchars($content) ?></pre>
</body>
</html>
