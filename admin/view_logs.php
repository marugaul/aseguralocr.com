<?php
// admin/view_logs.php - ver últimos logs (protegido por auth)
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$logFile = __DIR__ . '/../logs/dashboard_errors.log';
$tail = 4000; // caracteres a mostrar

$content = '';
if (file_exists($logFile)) {
    $content = file_get_contents($logFile);
    if ($content === false) $content = "No se puede leer el archivo de logs.";
} else {
    $content = "Archivo de logs no existe: $logFile";
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8"><title>Logs Dashboard</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="p-6 bg-gray-50 min-h-screen">
  <div class="max-w-5xl mx-auto bg-white p-6 rounded shadow">
    <div class="flex justify-between items-center mb-4">
      <h1 class="text-xl font-bold">Logs — dashboard_errors.log</h1>
      <a href="/admin/dashboard.php" class="text-sm text-gray-700">Volver</a>
    </div>
    <pre style="white-space:pre-wrap; background:#111; color:#0f0; padding:12px; border-radius:6px; max-height:70vh; overflow:auto;">
<?= htmlspecialchars($content) ?>
    </pre>
  </div>
</body>
</html>