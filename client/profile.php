<?php
// client/profile.php - Perfil del cliente
require_once __DIR__ . '/includes/client_auth.php';
require_client_login();

require_once __DIR__ . '/../includes/db.php';

$clientId = get_current_client_id();
$clientData = get_client_data();

$message = '';
$messageType = '';

// Procesar actualización de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare("UPDATE clients SET
            telefono = ?,
            cedula = ?,
            fecha_nacimiento = ?,
            direccion = ?,
            provincia = ?,
            canton = ?,
            distrito = ?,
            updated_at = NOW()
            WHERE id = ?");

        $stmt->execute([
            $_POST['telefono'] ?? null,
            $_POST['cedula'] ?? null,
            $_POST['fecha_nacimiento'] ?: null,
            $_POST['direccion'] ?? null,
            $_POST['provincia'] ?? null,
            $_POST['canton'] ?? null,
            $_POST['distrito'] ?? null,
            $clientId
        ]);

        // Refresh client data
        $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
        $stmt->execute([$clientId]);
        $clientData = $stmt->fetch();

        $message = 'Perfil actualizado correctamente';
        $messageType = 'success';
    } catch (Exception $e) {
        $message = 'Error al actualizar el perfil';
        $messageType = 'error';
    }
}

// Provincias de Costa Rica
$provincias = ['San José', 'Alajuela', 'Cartago', 'Heredia', 'Guanacaste', 'Puntarenas', 'Limón'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - AseguraloCR</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        * { font-family: 'Inter', sans-serif; }
        .gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    </style>
</head>
<body class="bg-gray-50">
    <?php include __DIR__ . '/includes/nav.php'; ?>

    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">
                <i class="fas fa-user-circle text-purple-600 mr-3"></i>Mi Perfil
            </h1>
            <p class="text-gray-600">Administra tu información personal</p>
        </div>

        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?= $messageType === 'success' ? 'bg-green-100 text-green-700 border border-green-300' : 'bg-red-100 text-red-700 border border-red-300' ?>">
                <i class="fas <?= $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?> mr-2"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Perfil Card -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <!-- Header con avatar -->
            <div class="gradient-bg p-6 text-white">
                <div class="flex items-center gap-4">
                    <?php if (!empty($clientData['avatar_url'])): ?>
                        <img src="<?= htmlspecialchars($clientData['avatar_url']) ?>"
                             alt="Avatar"
                             class="w-20 h-20 rounded-full border-4 border-white/30">
                    <?php else: ?>
                        <div class="w-20 h-20 rounded-full border-4 border-white/30 bg-white/20 flex items-center justify-center">
                            <i class="fas fa-user text-3xl"></i>
                        </div>
                    <?php endif; ?>
                    <div>
                        <h2 class="text-2xl font-bold"><?= htmlspecialchars($clientData['nombre_completo'] ?? 'Cliente') ?></h2>
                        <p class="text-purple-200"><?= htmlspecialchars($clientData['email'] ?? '') ?></p>
                        <?php if ($clientData['email_verified']): ?>
                            <span class="inline-flex items-center text-xs bg-green-500 text-white px-2 py-1 rounded mt-2">
                                <i class="fas fa-check-circle mr-1"></i>Email verificado
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Formulario -->
            <form method="POST" class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Nombre (solo lectura) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nombre Completo</label>
                        <input type="text"
                               value="<?= htmlspecialchars($clientData['nombre_completo'] ?? '') ?>"
                               disabled
                               class="w-full border border-gray-300 rounded-lg px-4 py-3 bg-gray-100 text-gray-500">
                        <p class="text-xs text-gray-500 mt-1">Vinculado a tu cuenta de Google</p>
                    </div>

                    <!-- Email (solo lectura) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                        <input type="email"
                               value="<?= htmlspecialchars($clientData['email'] ?? '') ?>"
                               disabled
                               class="w-full border border-gray-300 rounded-lg px-4 py-3 bg-gray-100 text-gray-500">
                        <p class="text-xs text-gray-500 mt-1">Vinculado a tu cuenta de Google</p>
                    </div>

                    <!-- Teléfono -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-phone mr-1 text-purple-600"></i>Teléfono
                        </label>
                        <input type="tel"
                               name="telefono"
                               value="<?= htmlspecialchars($clientData['telefono'] ?? '') ?>"
                               placeholder="8888-8888"
                               class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>

                    <!-- Cédula -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-id-card mr-1 text-purple-600"></i>Cédula
                        </label>
                        <input type="text"
                               name="cedula"
                               value="<?= htmlspecialchars($clientData['cedula'] ?? '') ?>"
                               placeholder="1-1234-5678"
                               class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>

                    <!-- Fecha de nacimiento -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-calendar mr-1 text-purple-600"></i>Fecha de Nacimiento
                        </label>
                        <input type="date"
                               name="fecha_nacimiento"
                               value="<?= htmlspecialchars($clientData['fecha_nacimiento'] ?? '') ?>"
                               class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>

                    <!-- Provincia -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-map-marker-alt mr-1 text-purple-600"></i>Provincia
                        </label>
                        <select name="provincia"
                                class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            <option value="">Seleccionar...</option>
                            <?php foreach ($provincias as $prov): ?>
                                <option value="<?= $prov ?>" <?= ($clientData['provincia'] ?? '') === $prov ? 'selected' : '' ?>>
                                    <?= $prov ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Cantón -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Cantón</label>
                        <input type="text"
                               name="canton"
                               value="<?= htmlspecialchars($clientData['canton'] ?? '') ?>"
                               placeholder="Ej: Escazú"
                               class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>

                    <!-- Distrito -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Distrito</label>
                        <input type="text"
                               name="distrito"
                               value="<?= htmlspecialchars($clientData['distrito'] ?? '') ?>"
                               placeholder="Ej: San Rafael"
                               class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>

                    <!-- Dirección -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-home mr-1 text-purple-600"></i>Dirección Completa
                        </label>
                        <textarea name="direccion"
                                  rows="3"
                                  placeholder="Dirección exacta para entrega de documentos"
                                  class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-purple-500 focus:border-transparent"><?= htmlspecialchars($clientData['direccion'] ?? '') ?></textarea>
                    </div>
                </div>

                <!-- Botones -->
                <div class="mt-8 flex justify-end gap-4">
                    <a href="/client/dashboard.php"
                       class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                        Cancelar
                    </a>
                    <button type="submit"
                            class="px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-semibold transition">
                        <i class="fas fa-save mr-2"></i>Guardar Cambios
                    </button>
                </div>
            </form>
        </div>

        <!-- Info adicional -->
        <div class="mt-6 bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-700">
            <i class="fas fa-info-circle mr-2"></i>
            Tu información personal es importante para brindarte un mejor servicio. Los datos de contacto nos permiten comunicarnos contigo sobre tus pólizas y pagos.
        </div>

        <!-- Último login -->
        <div class="mt-4 text-center text-sm text-gray-500">
            <p>Miembro desde: <?= $clientData['created_at'] ? date('d/m/Y', strtotime($clientData['created_at'])) : 'N/A' ?></p>
            <p>Último acceso: <?= $clientData['last_login'] ? date('d/m/Y H:i', strtotime($clientData['last_login'])) : 'N/A' ?></p>
        </div>
    </div>
</body>
</html>
