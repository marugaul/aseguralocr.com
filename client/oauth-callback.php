<?php
// client/oauth-callback.php - Handle Google OAuth callback

// Configure session cookie for entire domain (www and non-www)
// Use SameSite=None for OAuth redirects to work properly
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '.aseguralocr.com',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'None'  // Required for OAuth cross-site redirects
    ]);
    session_start();
}

require_once __DIR__ . '/../app/services/GoogleAuth.php';

$googleAuth = new GoogleAuth();
$error = null;

try {
    // Check for errors from Google
    if (isset($_GET['error'])) {
        throw new Exception('Autenticación cancelada: ' . htmlspecialchars($_GET['error']));
    }

    // Verify required parameters
    if (empty($_GET['code']) || empty($_GET['state'])) {
        throw new Exception('Parámetros inválidos en la respuesta de Google');
    }

    // Verify state to prevent CSRF
    if (!$googleAuth->verifyState($_GET['state'])) {
        throw new Exception('Estado de sesión inválido. Por favor, intenta nuevamente.');
    }

    // Exchange code for access token
    $tokens = $googleAuth->getAccessToken($_GET['code']);
    if (!$tokens || empty($tokens['access_token'])) {
        throw new Exception('Error al obtener token de acceso');
    }

    // Get user info from Google
    $userInfo = $googleAuth->getUserInfo($tokens['access_token']);
    if (!$userInfo || empty($userInfo['email'])) {
        throw new Exception('Error al obtener información del usuario');
    }

    // Create or update client record
    $clientId = $googleAuth->createOrUpdateClient($userInfo, $tokens);
    if (!$clientId) {
        throw new Exception('Error al crear registro de cliente');
    }

    // Set session
    $_SESSION['client_id'] = $clientId;
    $_SESSION['client_email'] = $userInfo['email'];
    $_SESSION['client_name'] = $userInfo['name'] ?? '';
    $_SESSION['client_avatar'] = $userInfo['picture'] ?? '';
    $_SESSION['client_logged_in'] = true;

    // Redirect to dashboard
    header('Location: /client/dashboard.php');
    exit;

} catch (Exception $e) {
    $error = $e->getMessage();
    error_log("OAuth callback error: " . $error);
}

// If we get here, there was an error
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error de Autenticación - AseguraloCR</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center px-4">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl p-8">
        <div class="text-center mb-6">
            <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-exclamation-triangle text-red-600 text-3xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 mb-2">Error de Autenticación</h2>
            <p class="text-gray-600 mb-6">
                <?= htmlspecialchars($error ?? 'Ocurrió un error desconocido') ?>
            </p>
            <a href="/client/login.php"
               class="inline-block bg-purple-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-purple-700 transition">
                <i class="fas fa-arrow-left mr-2"></i>Volver a intentar
            </a>
        </div>
    </div>
</body>
</html>
