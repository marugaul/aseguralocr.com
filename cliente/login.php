<?php
// cliente/login.php - Login para clientes (Google OAuth + Password)
require_once __DIR__ . '/../includes/db.php';

session_start();

$error = '';
$success = '';

// Si ya está logueado, redirigir al portal
if (!empty($_SESSION['client_logged_in']) && !empty($_SESSION['client_id'])) {
    header('Location: /client/dashboard.php');
    exit;
}

// Procesar login con contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_password'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $stmt = $pdo->prepare("SELECT id, nombre_completo, email, password_hash, status FROM clients WHERE email = ?");
        $stmt->execute([$email]);
        $client = $stmt->fetch();

        if ($client && $client['password_hash'] && password_verify($password, $client['password_hash'])) {
            if ($client['status'] !== 'active') {
                $error = 'Tu cuenta está inactiva. Contacta a soporte.';
            } else {
                // Login exitoso - usar mismas variables que el sistema existente
                $_SESSION['client_logged_in'] = true;
                $_SESSION['client_id'] = $client['id'];
                $_SESSION['client_name'] = $client['nombre_completo'];
                $_SESSION['client_email'] = $client['email'];

                // Actualizar last_login
                $pdo->prepare("UPDATE clients SET last_login = NOW() WHERE id = ?")->execute([$client['id']]);

                header('Location: /client/dashboard.php');
                exit;
            }
        } else {
            $error = 'Email o contraseña incorrectos';
        }
    } else {
        $error = 'Por favor ingresa email y contraseña';
    }
}

// Check for messages from register/reset
if (isset($_GET['registered'])) {
    $success = '¡Registro exitoso! Ahora puedes iniciar sesión.';
}
if (isset($_GET['reset'])) {
    $success = 'Contraseña actualizada. Ahora puedes iniciar sesión.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - AseguraloCR</title>
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
        .login-container {
            background: white;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 420px;
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(135deg, #1e3a8a, #3b82f6);
            padding: 32px;
            text-align: center;
            color: white;
        }
        .login-header h1 {
            font-size: 1.8rem;
            margin-bottom: 8px;
        }
        .login-header p {
            opacity: 0.9;
            font-size: 0.95rem;
        }
        .login-body {
            padding: 32px;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        .alert-error {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        .alert-success {
            background: #d1fae5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #374151;
            font-size: 0.9rem;
        }
        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.2s;
        }
        .form-group input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }
        .btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            box-shadow: 0 4px 12px rgba(59,130,246,0.4);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(59,130,246,0.5);
        }
        .btn-google {
            background: white;
            border: 2px solid #e5e7eb;
            color: #374151;
            margin-top: 16px;
        }
        .btn-google:hover {
            background: #f9fafb;
            border-color: #d1d5db;
        }
        .btn-google img {
            width: 20px;
            height: 20px;
        }
        .divider {
            display: flex;
            align-items: center;
            margin: 24px 0;
            color: #9ca3af;
            font-size: 0.85rem;
        }
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e5e7eb;
        }
        .divider span {
            padding: 0 16px;
        }
        .links {
            text-align: center;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid #e5e7eb;
        }
        .links a {
            color: #3b82f6;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .links a:hover {
            text-decoration: underline;
        }
        .forgot-link {
            text-align: right;
            margin-top: -12px;
            margin-bottom: 20px;
        }
        .forgot-link a {
            color: #6b7280;
            font-size: 0.85rem;
            text-decoration: none;
        }
        .forgot-link a:hover {
            color: #3b82f6;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>🔐 Bienvenido</h1>
            <p>Accede a tu portal de cliente</p>
        </div>

        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <!-- Login con contraseña -->
            <form method="POST">
                <input type="hidden" name="login_password" value="1">

                <div class="form-group">
                    <label>📧 Correo electrónico</label>
                    <input type="email" name="email" required placeholder="tu@email.com">
                </div>

                <div class="form-group">
                    <label>🔑 Contraseña</label>
                    <input type="password" name="password" required placeholder="••••••••">
                </div>

                <div class="forgot-link">
                    <a href="/cliente/forgot-password.php">¿Olvidaste tu contraseña?</a>
                </div>

                <button type="submit" class="btn btn-primary">
                    Iniciar Sesión
                </button>
            </form>

            <div class="divider">
                <span>o continúa con</span>
            </div>

            <!-- Login con Google -->
            <a href="/client/login.php" class="btn btn-google">
                <svg width="20" height="20" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
                Continuar con Google
            </a>

            <div class="links">
                <p>¿No tienes cuenta? <a href="/cliente/register.php">Regístrate aquí</a></p>
                <p style="margin-top: 12px;"><a href="/">← Volver al inicio</a></p>
            </div>
        </div>
    </div>
</body>
</html>
