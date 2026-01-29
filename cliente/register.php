<?php
// cliente/register.php - Registro de clientes
require_once __DIR__ . '/../includes/db.php';

session_start();

$error = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $cedula = trim($_POST['cedula'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    // Validaciones
    if (strlen($nombre) < 3) {
        $errors[] = 'El nombre debe tener al menos 3 caracteres';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email inválido';
    }
    if (strlen($password) < 6) {
        $errors[] = 'La contraseña debe tener al menos 6 caracteres';
    }
    if ($password !== $password2) {
        $errors[] = 'Las contraseñas no coinciden';
    }

    // Verificar si email ya existe
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM clients WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'Este email ya está registrado. <a href="/cliente/login.php">¿Iniciar sesión?</a>';
        }
    }

    // Crear cuenta
    if (empty($errors)) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO clients (nombre_completo, email, telefono, cedula, password_hash, status, created_at) VALUES (?, ?, ?, ?, ?, 'active', NOW())");

        if ($stmt->execute([$nombre, $email, $telefono ?: null, $cedula ?: null, $passwordHash])) {
            // Iniciar sesión automáticamente después del registro
            $_SESSION['client_logged_in'] = true;
            $_SESSION['client_id'] = $pdo->lastInsertId();
            $_SESSION['client_name'] = $nombre;
            $_SESSION['client_email'] = $email;

            header('Location: /client/dashboard.php');
            exit;
        } else {
            $errors[] = 'Error al crear la cuenta. Intenta nuevamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Cuenta - AseguraloCR</title>
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
        .register-container {
            background: white;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 480px;
            overflow: hidden;
        }
        .register-header {
            background: linear-gradient(135deg, #10b981, #059669);
            padding: 32px;
            text-align: center;
            color: white;
        }
        .register-header h1 {
            font-size: 1.8rem;
            margin-bottom: 8px;
        }
        .register-header p {
            opacity: 0.9;
            font-size: 0.95rem;
        }
        .register-body {
            padding: 32px;
        }
        .alert-error {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        .alert-error ul {
            margin: 0;
            padding-left: 20px;
        }
        .alert-error a {
            color: #dc2626;
            font-weight: 600;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group.full {
            grid-column: 1 / -1;
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
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16,185,129,0.1);
        }
        .form-hint {
            font-size: 0.8rem;
            color: #6b7280;
            margin-top: 6px;
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
        }
        .btn-primary {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            box-shadow: 0 4px 12px rgba(16,185,129,0.4);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16,185,129,0.5);
        }
        .links {
            text-align: center;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid #e5e7eb;
        }
        .links a {
            color: #10b981;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .links a:hover {
            text-decoration: underline;
        }
        .password-strength {
            height: 4px;
            background: #e5e7eb;
            border-radius: 2px;
            margin-top: 8px;
            overflow: hidden;
        }
        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: all 0.3s;
        }
        @media (max-width: 500px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h1>📝 Crear Cuenta</h1>
            <p>Regístrate para acceder a tu portal</p>
        </div>

        <div class="register-body">
            <?php if (!empty($errors)): ?>
                <div class="alert-error">
                    <ul>
                        <?php foreach ($errors as $err): ?>
                            <li><?= $err ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-row">
                    <div class="form-group full">
                        <label>👤 Nombre Completo *</label>
                        <input type="text" name="nombre" required value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>" placeholder="Tu nombre completo">
                    </div>

                    <div class="form-group">
                        <label>📧 Email *</label>
                        <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="tu@email.com">
                    </div>

                    <div class="form-group">
                        <label>📱 Teléfono</label>
                        <input type="tel" name="telefono" value="<?= htmlspecialchars($_POST['telefono'] ?? '') ?>" placeholder="8888-8888">
                    </div>

                    <div class="form-group full">
                        <label>🪪 Cédula</label>
                        <input type="text" name="cedula" value="<?= htmlspecialchars($_POST['cedula'] ?? '') ?>" placeholder="Número de identificación">
                    </div>

                    <div class="form-group">
                        <label>🔑 Contraseña *</label>
                        <input type="password" name="password" id="password" required minlength="6" placeholder="Mínimo 6 caracteres">
                        <div class="password-strength">
                            <div class="password-strength-bar" id="strengthBar"></div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>🔑 Confirmar Contraseña *</label>
                        <input type="password" name="password2" required placeholder="Repite la contraseña">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    ✨ Crear mi cuenta
                </button>
            </form>

            <div class="links">
                <p>¿Ya tienes cuenta? <a href="/cliente/login.php">Inicia sesión</a></p>
                <p style="margin-top: 12px;"><a href="/">← Volver al inicio</a></p>
            </div>
        </div>
    </div>

    <script>
        // Password strength indicator
        document.getElementById('password')?.addEventListener('input', function(e) {
            const password = e.target.value;
            const bar = document.getElementById('strengthBar');
            let strength = 0;

            if (password.length >= 6) strength += 25;
            if (password.length >= 8) strength += 25;
            if (/[A-Z]/.test(password)) strength += 25;
            if (/[0-9]/.test(password)) strength += 25;

            bar.style.width = strength + '%';
            if (strength <= 25) bar.style.background = '#ef4444';
            else if (strength <= 50) bar.style.background = '#f59e0b';
            else if (strength <= 75) bar.style.background = '#3b82f6';
            else bar.style.background = '#10b981';
        });
    </script>
</body>
</html>
