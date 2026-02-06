<?php
// cliente/reset-password.php - Restablecer contraseña con token
require_once __DIR__ . '/../includes/db.php';

$error = '';
$success = '';
$validToken = false;
$token = $_GET['token'] ?? '';

if ($token) {
    $stmt = $pdo->prepare("SELECT id, email FROM clients WHERE reset_token = ? AND reset_token_expires > NOW()");
    $stmt->execute([$token]);
    $client = $stmt->fetch();

    if ($client) {
        $validToken = true;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password'] ?? '';
            $password2 = $_POST['password2'] ?? '';

            if (strlen($password) < 6) {
                $error = 'La contraseña debe tener al menos 6 caracteres';
            } elseif ($password !== $password2) {
                $error = 'Las contraseñas no coinciden';
            } else {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                $pdo->prepare("UPDATE clients SET password_hash = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?")
                    ->execute([$passwordHash, $client['id']]);

                header('Location: /cliente/login.php?reset=1');
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Contraseña - AseguraloCR</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 420px;
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #10b981, #059669);
            padding: 32px;
            text-align: center;
            color: white;
        }
        .header h1 { font-size: 1.8rem; margin-bottom: 8px; }
        .header p { opacity: 0.9; }
        .body { padding: 32px; }
        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        .alert-error { background: #fee2e2; color: #dc2626; }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #374151;
        }
        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
        }
        .form-group input:focus {
            outline: none;
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16,185,129,0.1);
        }
        .btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }
        .btn:hover { transform: translateY(-2px); }
        .links {
            text-align: center;
            margin-top: 24px;
        }
        .links a { color: #10b981; text-decoration: none; }
        .invalid-token {
            text-align: center;
            padding: 40px;
        }
        .invalid-token h2 { color: #dc2626; margin-bottom: 16px; }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($validToken): ?>
            <div class="header">
                <h1>🔐 Nueva Contraseña</h1>
                <p>Ingresa tu nueva contraseña</p>
            </div>
            <div class="body">
                <?php if ($error): ?>
                    <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label>🔑 Nueva Contraseña</label>
                        <input type="password" name="password" required minlength="6" placeholder="Mínimo 6 caracteres">
                    </div>
                    <div class="form-group">
                        <label>🔑 Confirmar Contraseña</label>
                        <input type="password" name="password2" required placeholder="Repite la contraseña">
                    </div>
                    <button type="submit" class="btn">Guardar nueva contraseña</button>
                </form>

                <div class="links">
                    <a href="/cliente/login.php">← Volver a login</a>
                </div>
            </div>
        <?php else: ?>
            <div class="invalid-token">
                <h2>❌ Enlace Inválido</h2>
                <p>Este enlace ha expirado o no es válido.</p>
                <p style="margin-top: 20px;"><a href="/cliente/forgot-password.php">Solicitar nuevo enlace</a></p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
