<?php
// admin/ASEGURALO_SSH.php
// USO RESPONSABLE: sube este archivo SOLO si entiendes los riesgos. Borra el archivo tras usarlo.

session_start();
require_once __DIR__ . '/aseguralo_ssh_config.php';
$config = require __DIR__ . '/aseguralo_ssh_config.php';

// Requiere tu sistema de autenticación existente
require_once __DIR__ . '/../includes/auth.php';
require_admin(); // <- debe existir en tu proyecto

// CSRF token
if (empty($_SESSION['aseguralo_csrf'])) {
    $_SESSION['aseguralo_csrf'] = bin2hex(random_bytes(24));
}
$csrf = $_SESSION['aseguralo_csrf'];

$logFile = $config['log_file'];
$privateKey = $config['private_key_path'];
$allowedHosts = $config['allowed_hosts'];
$requireAdminPass = !empty($config['require_admin_password']);
$adminHash = $config['admin_password_hash'] ?? '';
$sshOptions = $config['ssh_options'] ?? [];
$procTimeout = intval($config['proc_timeout'] ?? 120);

function write_log($text) {
    global $logFile;
    $entry = "[".date('Y-m-d H:i:s')."] " . $text . "\n\n";
    @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
}

$errors = [];
$resultOutput = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar CSRF
    $token = $_POST['csrf'] ?? '';
    if (!hash_equals($csrf, (string)$token)) {
        $errors[] = "Token CSRF inválido.";
    }

    $hostKey = $_POST['host'] ?? '';
    $command = $_POST['command'] ?? '';
    $confirm = isset($_POST['confirm']) && $_POST['confirm'] === '1';

    if (!$confirm) $errors[] = "Debes confirmar que entiendes los riesgos marcando la casilla.";

    if (!isset($allowedHosts[$hostKey])) {
        $errors[] = "Host no autorizado seleccionado.";
    }

    if (!is_string($command) || trim($command) === '') {
        $errors[] = "Comando vacío.";
    }

    if (!file_exists($privateKey) || !is_readable($privateKey)) {
        $errors[] = "La clave privada no existe o no es legible en la ruta configurada: {$privateKey}";
    }

    if ($requireAdminPass) {
        $pass = $_POST['admin_password'] ?? '';
        if ($pass === '') {
            $errors[] = "Ingresa la contraseña de administrador para confirmar.";
        } else {
            if (!password_verify($pass, $adminHash)) {
                $errors[] = "Contraseña de administrador inválida.";
            }
        }
    }

    if (empty($errors)) {
        // Preparar comando SSH seguro
        $h = $allowedHosts[$hostKey];
        $host = $h['host'];
        $user = $h['user'];
        $port = intval($h['port'] ?? 22);

        // Construir opciones SSH: formatear -o KEY=VAL si vienen
        $optsParts = [];
        foreach ($sshOptions as $k => $vals) {
            if ($k === '-o' && is_array($vals)) {
                foreach ($vals as $v) {
                    $optsParts[] = "-o " . escapeshellarg($v);
                }
            }
        }

        // Escapar la ruta y la host/user
        $keyEsc = escapeshellarg($privateKey);
        $userAtHost = escapeshellarg("{$user}@{$host}");
        // Escapar el comando como un solo argumento (preservando shell metachar)
        // Usamos printf '%s' to pass the whole command as one argument to ssh
        $cmdEsc = escapeshellarg($command);

        // Montar el comando final (nota: -- asegura que el comando quede al final)
        $sshCmd = "ssh -i {$keyEsc} -p {$port} " . implode(' ', $optsParts) . " {$user}@{$host} -- {$cmdEsc}";

        // Ejecutar vía proc_open para capturar stdout/stderr
        $descriptors = [
            1 => ["pipe", "w"],
            2 => ["pipe", "w"]
        ];

        $cwd = dirname(__DIR__); // one level up
        $proc = @proc_open($sshCmd, $descriptors, $pipes, $cwd);

        if (is_resource($proc)) {
            // set stream blocking/non-blocking handled by stream_get_contents
            $stdout = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[2]);

            // esperar cierre
            $returnCode = proc_close($proc);

            $resultOutput = "SSH Command: {$sshCmd}\n\nReturn code: {$returnCode}\n\n--- STDOUT ---\n{$stdout}\n\n--- STDERR ---\n{$stderr}\n";
            $success = ($returnCode === 0);

            // log (no imprimas el private key ni información sensible)
            $logEntry = "user=" . ($_SESSION['admin_user'] ?? 'unknown') .
                        " host={$host} port={$port} cmd=" . $command .
                        " rc={$returnCode}";
            write_log($logEntry . "\n" . $resultOutput);
        } else {
            $errors[] = "No se pudo iniciar el proceso SSH. Comprueba que la binaria ssh esté disponible en el servidor y que PHP permite ejecutar procesos.";
            write_log("proc_open failure user=" . ($_SESSION['admin_user'] ?? 'unknown') . " host={$host}");
        }
    } else {
        // errores: logear intento
        write_log("Intento fallido por " . ($_SESSION['admin_user'] ?? 'unknown') . " errores=" . json_encode($errors));
    }
}

?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>ASEGURALO_SSH — Ejecutar SSH (protegido)</title>
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 p-6">
<div class="max-w-4xl mx-auto bg-white p-6 rounded shadow">
  <div class="flex justify-between items-center mb-4">
    <h1 class="text-xl font-bold">ASEGURALO_SSH — Ejecutar comando SSH</h1>
    <a href="/admin/dashboard.php" class="text-sm text-gray-600">Volver al dashboard</a>
  </div>

  <div class="text-sm mb-4 p-3 bg-yellow-50 border-l-4 border-yellow-300">
    <strong>Atención:</strong> este script permite ejecutar comandos en hosts autorizados. Úsalo con extrema precaución.
    Borra o renombra este archivo una vez terminado el uso.
  </div>

  <?php if (!empty($errors)): ?>
    <div class="bg-red-50 text-red-700 p-3 rounded mb-4">
      <ul>
      <?php foreach($errors as $err): ?>
        <li><?= htmlspecialchars($err) ?></li>
      <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <?php if ($resultOutput !== ''): ?>
    <div class="mb-4">
      <h2 class="font-semibold mb-2">Resultado</h2>
      <pre class="bg-black text-green-200 p-3 rounded" style="white-space:pre-wrap"><?= htmlspecialchars($resultOutput) ?></pre>
    </div>
  <?php endif; ?>

  <form method="post" class="space-y-4 bg-gray-50 p-4 rounded">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">

    <label class="block">
      <span class="text-sm font-medium">Host autorizado</span>
      <select name="host" class="w-full border p-2 rounded">
        <?php foreach($allowedHosts as $key => $h): ?>
          <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($key . ' — ' . $h['host'] . ':' . ($h['port'] ?? 22) . ' (user:' . $h['user'] . ')') ?></option>
        <?php endforeach; ?>
      </select>
    </label>

    <label class="block">
      <span class="text-sm font-medium">Comando a ejecutar</span>
      <textarea name="command" rows="4" class="w-full border p-2 rounded" placeholder="Ej. ls -la /var/www"><?= htmlspecialchars($_POST['command'] ?? '') ?></textarea>
    </label>

    <?php if ($requireAdminPass): ?>
    <label class="block">
      <span class="text-sm font-medium">Contraseña de administrador (verificación)</span>
      <input type="password" name="admin_password" class="w-full border p-2 rounded" required>
    </label>
    <?php endif; ?>

    <label class="flex items-center space-x-3">
      <input type="checkbox" name="confirm" value="1" class="form-checkbox h-4 w-4">
      <span class="text-sm">Confirmo que entiendo los riesgos y autorizo la ejecución de este comando.</span>
    </label>

    <div class="flex items-center space-x-3">
      <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded">Ejecutar comando</button>
      <a href="/admin/dashboard.php" class="text-sm text-gray-600">Cancelar</a>
    </div>
  </form>

  <div class="mt-6 text-xs text-gray-500">
    <p>Log de ejecuciones: <?= htmlspecialchars($logFile) ?></p>
    <p>Ruta clave privada configurada: <?= htmlspecialchars($privateKey) ?></p>
    <p>Después de usar, <strong>borra</strong> este archivo para reducir riesgo (por ejemplo: rm /home/tuusuario/public_html/admin/ASEGURALO_SSH.php).</p>
  </div>
</div>
</body>
</html>