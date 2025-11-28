<?php
// admin/login.php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../app/services/Security.php';

// Custom log file for debugging
$debugLog = __DIR__ . '/../logs/admin_login_debug.log';
function logDebug($msg) {
    global $debugLog;
    $time = date('Y-m-d H:i:s');
    @file_put_contents($debugLog, "[$time] $msg\n", FILE_APPEND);
}

Security::start();
$error = '';

logDebug("=== Login page loaded ===");
logDebug("Session ID: " . session_id());
logDebug("Session data: " . json_encode($_SESSION));
logDebug("Request method: " . $_SERVER['REQUEST_METHOD']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    logDebug("POST received - User: " . ($_POST['user'] ?? 'none'));
    logDebug("CSRF from form: " . substr($_POST['csrf_token'] ?? '', 0, 20) . "...");
    logDebug("CSRF from session: " . substr($_SESSION['csrf'] ?? '', 0, 20) . "...");

    // Validate CSRF token
    if (!Security::validateCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Token de seguridad inválido. Intenta nuevamente.';
        logDebug("CSRF FAILED");
    }
    // Rate limiting: 3 seconds between attempts
    elseif (!Security::checkRateLimit(3000)) {
        $error = 'Demasiados intentos. Espera unos segundos.';
        logDebug("Rate limit hit");
    }
    else {
        $user = trim($_POST['user'] ?? '');
        $pass = $_POST['password'] ?? '';

        if ($user && $pass) {
            $stmt = $pdo->prepare("SELECT id, username, password_hash FROM admins WHERE username = ?");
            $stmt->execute([$user]);
            $admin = $stmt->fetch();

            logDebug("User found in DB: " . ($admin ? 'YES' : 'NO'));

            if ($admin && password_verify($pass, $admin['password_hash'])) {
                logDebug("Password verified OK");

                // Set session variables FIRST
                $_SESSION['admin_logged'] = true;
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_user'] = $admin['username'];

                logDebug("Session vars set: " . json_encode($_SESSION));

                // Regenerate session ID
                session_regenerate_id(true);
                logDebug("Session regenerated. New ID: " . session_id());

                // Force session write
                session_write_close();
                logDebug("Session closed/written");

                logDebug("Redirecting to dashboard...");
                header('Location: /admin/dashboard.php');
                exit;
            } else {
                logDebug("Password verify FAILED");
            }
        }
        $error = 'Usuario o contraseña inválidos';
    }
}

$csrf_token = Security::csrfToken();
logDebug("CSRF token generated for form");
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin — Ingreso</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
  <div class="bg-white p-8 rounded-lg shadow w-full max-w-md">
    <h2 class="text-2xl font-bold mb-6 text-center">Ingreso Administrador</h2>
    <?php if ($error): ?>
      <div class="bg-red-100 text-red-700 p-3 mb-4 rounded text-center"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <label class="block mb-4">
        <span class="block mb-1">Usuario</span>
        <input name="user" required class="w-full border p-3 rounded focus:ring-2 focus:ring-purple-500 focus:outline-none">
      </label>
      <label class="block mb-6">
        <span class="block mb-1">Contraseña</span>
        <input name="password" type="password" required class="w-full border p-3 rounded focus:ring-2 focus:ring-purple-500 focus:outline-none">
      </label>
      <div class="flex justify-between items-center">
        <button type="submit" class="bg-purple-600 text-white px-5 py-3 rounded hover:bg-purple-700 transition">Ingresar</button>
        <a href="/" class="text-sm text-gray-600 hover:underline">Volver al sitio</a>
      </div>
    </form>
  </div>
</body>
</html>