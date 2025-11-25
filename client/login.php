<?php
// client/login.php - Client login with Google
error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar en pantalla, pero capturar

// Configure session cookie for entire domain (www and non-www)
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '.aseguralocr.com',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

$errorMsg = null;

try {
    // If already logged in, redirect to dashboard
    if (!empty($_SESSION['client_id'])) {
        header('Location: /client/dashboard.php');
        exit;
    }

    require_once __DIR__ . '/../app/services/GoogleAuth.php';
    $googleAuth = new GoogleAuth();

    if (!$googleAuth->isConfigured()) {
        $configError = true;
    }
} catch (Throwable $e) {
    $errorMsg = $e->getMessage();
    error_log("Login page error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal de Clientes - AseguraloCR</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        * { font-family: 'Inter', sans-serif; }
        .gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .google-btn {
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .google-btn:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            transform: translateY(-2px);
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <nav class="bg-white shadow-md">
        <div class="container mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 gradient-bg rounded-lg flex items-center justify-center">
                        <i class="fas fa-shield-alt text-white text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-800">AseguraloCR</h1>
                        <p class="text-xs text-gray-500">Portal de Clientes</p>
                    </div>
                </div>
                <a href="/" class="text-gray-600 hover:text-purple-600 transition">
                    <i class="fas fa-arrow-left mr-2"></i>Volver al sitio
                </a>
            </div>
        </div>
    </nav>

    <!-- Login Content -->
    <div class="flex items-center justify-center px-4 py-16">
        <div class="max-w-md w-full">
            <!-- Welcome Card -->
            <div class="bg-white rounded-2xl shadow-xl p-8 mb-6">
                <div class="text-center mb-8">
                    <div class="w-20 h-20 gradient-bg rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-user-shield text-white text-3xl"></i>
                    </div>
                    <h2 class="text-3xl font-bold text-gray-800 mb-2">Bienvenido</h2>
                    <p class="text-gray-600">Accede a tu portal de cliente</p>
                </div>

                <?php if ($errorMsg): ?>
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-triangle text-red-600 mt-1 mr-3"></i>
                        <div>
                            <h3 class="font-semibold text-red-800 mb-1">Error del sistema</h3>
                            <p class="text-sm text-red-600"><?= htmlspecialchars($errorMsg) ?></p>
                        </div>
                    </div>
                </div>
                <?php elseif (isset($configError)): ?>
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-triangle text-red-600 mt-1 mr-3"></i>
                        <div>
                            <h3 class="font-semibold text-red-800 mb-1">Google OAuth no configurado</h3>
                            <p class="text-sm text-red-600">
                                El inicio de sesión con Google aún no está configurado. Por favor, contacta al administrador.
                            </p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Google Sign In Button -->
                <a href="<?= isset($googleAuth) ? htmlspecialchars($googleAuth->getAuthUrl()) : '#' ?>"
                   class="google-btn w-full bg-white border-2 border-gray-200 rounded-lg px-6 py-4 flex items-center justify-center hover:bg-gray-50 transition mb-4 <?= (isset($configError) || $errorMsg) ? 'opacity-50 pointer-events-none' : '' ?>">
                    <svg class="w-6 h-6 mr-3" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                    </svg>
                    <span class="font-semibold text-gray-700">Continuar con Google</span>
                </a>

                <div class="text-center text-sm text-gray-500 mt-6">
                    <p>Al continuar, aceptas nuestros</p>
                    <a href="/terminos.php" class="text-purple-600 hover:underline">Términos y Condiciones</a>
                </div>
            </div>

            <!-- Features -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white rounded-lg p-4 text-center">
                    <i class="fas fa-file-invoice text-purple-600 text-2xl mb-2"></i>
                    <p class="text-sm text-gray-600">Tus Pólizas</p>
                </div>
                <div class="bg-white rounded-lg p-4 text-center">
                    <i class="fas fa-calculator text-purple-600 text-2xl mb-2"></i>
                    <p class="text-sm text-gray-600">Cotizaciones</p>
                </div>
                <div class="bg-white rounded-lg p-4 text-center">
                    <i class="fas fa-credit-card text-purple-600 text-2xl mb-2"></i>
                    <p class="text-sm text-gray-600">Pagos</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="text-center py-8 text-gray-600 text-sm">
        <p>&copy; <?= date('Y') ?> AseguraloCR. Todos los derechos reservados.</p>
        <p class="mt-2">
            <a href="/privacidad.php" class="hover:text-purple-600 transition">Política de Privacidad</a>
            <span class="mx-2">|</span>
            <a href="/contacto.php" class="hover:text-purple-600 transition">Contacto</a>
        </p>
    </footer>
</body>
</html>
