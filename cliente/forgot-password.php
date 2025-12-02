<?php
// cliente/forgot-password.php - Solicitar recuperación de contraseña
require_once __DIR__ . '/../includes/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Por favor ingresa un email válido';
    } else {
        $stmt = $pdo->prepare("SELECT id, nombre_completo FROM clients WHERE email = ?");
        $stmt->execute([$email]);
        $client = $stmt->fetch();

        if ($client) {
            // Generar token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $pdo->prepare("UPDATE clients SET reset_token = ?, reset_token_expires = ? WHERE id = ?")
                ->execute([$token, $expires, $client['id']]);

            // Enviar email
            $resetLink = "https://" . $_SERVER['HTTP_HOST'] . "/cliente/reset-password.php?token=" . $token;

            // Cargar config de email
            $config = @include __DIR__ . '/../app/config/config.php';
            if ($config) {
                $autoloadCandidates = [
                    __DIR__ . '/../vendor/autoload.php',
                    __DIR__ . '/../composer/vendor/autoload.php'
                ];
                foreach ($autoloadCandidates as $autoload) {
                    if (file_exists($autoload)) {
                        require_once $autoload;
                        break;
                    }
                }

                if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
                    try {
                        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                        $mail->isSMTP();
                        $mail->Host = $config['mail']['host'];
                        $mail->Port = $config['mail']['port'];
                        $mail->SMTPAuth = true;
                        $mail->SMTPSecure = ($config['mail']['port'] == 465) ? 'ssl' : 'tls';
                        $mail->Username = $config['mail']['user'];
                        $mail->Password = $config['mail']['pass'];
                        $mail->CharSet = 'UTF-8';

                        $mail->setFrom($config['mail']['from'][0], $config['mail']['from'][1]);
                        $mail->addAddress($email, $client['nombre_completo']);

                        $mail->isHTML(true);
                        $mail->Subject = 'Recuperar contraseña - AseguraloCR';
                        $mail->Body = "
                            <h2>Hola {$client['nombre_completo']},</h2>
                            <p>Recibimos una solicitud para restablecer tu contraseña.</p>
                            <p><a href='{$resetLink}' style='display:inline-block;background:#3b82f6;color:white;padding:12px 24px;text-decoration:none;border-radius:8px;'>Restablecer Contraseña</a></p>
                            <p>Este enlace expira en 1 hora.</p>
                            <p>Si no solicitaste esto, ignora este correo.</p>
                            <hr>
                            <p style='color:#666;font-size:12px;'>AseguraloCR - Protegiendo lo que más valoras</p>
                        ";

                        $mail->send();
                    } catch (Exception $e) {
                        error_log("Error sending reset email: " . $e->getMessage());
                    }
                }
            }
        }

        // Siempre mostrar éxito (por seguridad, no revelar si email existe)
        $success = 'Si el email está registrado, recibirás un enlace para restablecer tu contraseña.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - AseguraloCR</title>
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
            background: linear-gradient(135deg, #f59e0b, #d97706);
            padding: 32px;
            text-align: center;
            color: white;
        }
        .header h1 { font-size: 1.8rem; margin-bottom: 8px; }
        .header p { opacity: 0.9; font-size: 0.95rem; }
        .body { padding: 32px; }
        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        .alert-error { background: #fee2e2; color: #dc2626; }
        .alert-success { background: #d1fae5; color: #047857; }
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
            border-color: #f59e0b;
            box-shadow: 0 0 0 3px rgba(245,158,11,0.1);
        }
        .btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            box-shadow: 0 4px 12px rgba(245,158,11,0.4);
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(245,158,11,0.5);
        }
        .links {
            text-align: center;
            margin-top: 24px;
        }
        .links a { color: #f59e0b; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔓 Recuperar Contraseña</h1>
            <p>Te enviaremos un enlace para restablecerla</p>
        </div>
        <div class="body">
            <?php if ($error): ?>
                <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
            <?php else: ?>
                <form method="POST">
                    <div class="form-group">
                        <label>📧 Tu correo electrónico</label>
                        <input type="email" name="email" required placeholder="tu@email.com">
                    </div>
                    <button type="submit" class="btn">Enviar enlace de recuperación</button>
                </form>
            <?php endif; ?>
            <div class="links">
                <p><a href="/cliente/login.php">← Volver a iniciar sesión</a></p>
            </div>
        </div>
    </div>
</body>
</html>
