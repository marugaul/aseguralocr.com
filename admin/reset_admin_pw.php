<?php
// admin/reset_admin_pw.php - Reset admin password (DELETE AFTER USE!)
require_once __DIR__ . '/../includes/db.php';

$message = '';
$success = false;

// Security: Only allow if a special token is provided
$secretToken = 'RESET2024ADMIN'; // Cambia esto por seguridad

if ($_GET['token'] !== $secretToken) {
    die('Acceso denegado. Token requerido.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';

    if (strlen($newPassword) < 6) {
        $message = 'La contraseña debe tener al menos 6 caracteres';
    } else {
        // Hash the new password
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);

        // Check if user exists
        $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin) {
            // Update password
            $stmt = $pdo->prepare("UPDATE admins SET password_hash = ? WHERE username = ?");
            $stmt->execute([$hash, $username]);
            $message = "Contraseña actualizada para: $username";
            $success = true;
        } else {
            // Create new admin
            $stmt = $pdo->prepare("INSERT INTO admins (username, password_hash) VALUES (?, ?)");
            $stmt->execute([$username, $hash]);
            $message = "Nuevo admin creado: $username";
            $success = true;
        }
    }
}

// List existing admins
$admins = $pdo->query("SELECT id, username FROM admins")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reset Admin Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white p-8 rounded-lg shadow-lg max-w-md w-full">
        <h1 class="text-2xl font-bold mb-6 text-red-600">⚠️ Reset Admin Password</h1>

        <div class="bg-yellow-100 border border-yellow-400 text-yellow-800 p-3 rounded mb-4 text-sm">
            <strong>¡IMPORTANTE!</strong> Elimina este archivo después de usarlo.
        </div>

        <?php if ($message): ?>
            <div class="<?= $success ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?> p-3 rounded mb-4">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="mb-6">
            <h3 class="font-semibold mb-2">Admins existentes:</h3>
            <ul class="text-sm text-gray-600">
                <?php foreach ($admins as $a): ?>
                    <li>• <?= htmlspecialchars($a['username']) ?> (ID: <?= $a['id'] ?>)</li>
                <?php endforeach; ?>
                <?php if (empty($admins)): ?>
                    <li class="text-red-600">No hay admins en la base de datos</li>
                <?php endif; ?>
            </ul>
        </div>

        <form method="POST">
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Usuario</label>
                <input type="text" name="username" value="marugaul" required
                       class="w-full border p-2 rounded focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium mb-1">Nueva Contraseña</label>
                <input type="text" name="new_password" required minlength="6"
                       class="w-full border p-2 rounded focus:ring-2 focus:ring-blue-500"
                       placeholder="Mínimo 6 caracteres">
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700">
                Resetear / Crear Admin
            </button>
        </form>

        <div class="mt-6 text-center">
            <a href="/admin/login.php" class="text-blue-600 hover:underline text-sm">← Volver al login</a>
        </div>
    </div>
</body>
</html>
