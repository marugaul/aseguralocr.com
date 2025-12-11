<?php
// Configuración de seguridad
require_once __DIR__ . '/app/services/Security.php';

// Iniciar sesión segura y generar token CSRF
Security::start();
$csrf = Security::csrfToken();

// Cargar datos del cliente si está logueado
$clienteData = [
    'tipoId' => '',
    'cedula' => '',
    'nombre' => '',
    'correo' => '',
    'telefono' => ''
];

if (!empty($_SESSION['client_id'])) {
    try {
        $config = require __DIR__ . '/app/config/config.php';
        $pdo = new PDO(
            "mysql:host={$config['db']['mysql']['host']};dbname={$config['db']['mysql']['dbname']};charset={$config['db']['mysql']['charset']}",
            $config['db']['mysql']['user'],
            $config['db']['mysql']['pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $stmt = $pdo->prepare("SELECT cedula, nombre_completo, email, telefono FROM clients WHERE id = ?");
        $stmt->execute([$_SESSION['client_id']]);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($cliente) {
            $clienteData = [
                'tipoId' => 'cedula',
                'cedula' => $cliente['cedula'] ?? '',
                'nombre' => $cliente['nombre_completo'] ?? '',
                'correo' => $cliente['email'] ?? '',
                'telefono' => $cliente['telefono'] ?? ''
            ];
        }
    } catch (Exception $e) {
        error_log("Error loading client data: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotizar Seguro de Auto en Costa Rica | Seguro Vehicular INS | AseguraloCR</title>
    <meta name="description" content="Cotiza tu seguro de auto INS en Costa Rica. Cobertura completa, responsabilidad civil, asistencia vial 24/7.">
    <link rel="icon" type="image/svg+xml" href="/imagenes/favicon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
    * { font-family: 'Inter', sans-serif; }
    .gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    .step-indicator { transition: all 0.3s ease; }
    .step-indicator.active { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); transform: scale(1.1); }
    .step-indicator.completed { background: #10b981; }
    .form-section { display: none; animation: fadeIn 0.5s ease; }
    .form-section.active { display: block; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    .input-field { transition: all 0.3s ease; }
    .input-field:focus { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2); }
    .checkbox-custom { appearance: none; width: 20px; height: 20px; border: 2px solid #d1d5db; border-radius: 4px; cursor: pointer; position: relative; transition: all 0.3s ease; }
    .checkbox-custom:checked { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-color: #667eea; }
    .checkbox-custom:checked::after { content: '✓'; position: absolute; color: white; font-size: 14px; top: 50%; left: 50%; transform: translate(-50%, -50%); }
    .radio-custom { appearance: none; width: 20px; height: 20px; border: 2px solid #d1d5db; border-radius: 50%; cursor: pointer; position: relative; transition: all 0.3s ease; }
    .radio-custom:checked { border-color: #667eea; }
    .radio-custom:checked::after { content: ''; position: absolute; width: 10px; height: 10px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; top: 50%; left: 50%; transform: translate(-50%, -50%); }
    .progress-bar { transition: width 0.5s ease; }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-md sticky top-0 z-50">
    <div class="container mx-auto px-4 py-4">
    <div class="flex items-center justify-between">
    <div class="flex items-center space-x-3">
    <div class="w-12 h-12 gradient-bg rounded-lg flex items-center justify-center">
    <i class="fas fa-car text-white text-xl"></i>
    </div>
    <div>
    <h1 class="text-xl font-bold text-gray-800">Seguro de Automóvil</h1>
    <p class="text-xs text-gray-500">ASEGURALOCR.COM - Agente: 86611</p>
    </div>
    </div>
    <a href="/index.php" class="text-gray-600 hover:text-purple-600 transition">
    <i class="fas fa-arrow-left mr-2"></i>Volver
    </a>
    </div>
    </div>
    </header>

    <!-- Progress Bar -->
    <div class="bg-white border-b">
    <div class="container mx-auto px-4 py-4">
    <div class="flex justify-between items-center mb-4">
    <div class="flex-1 flex items-center" id="step-indicators"></div>
    </div>
    <div class="w-full bg-gray-200 rounded-full h-2">
    <div id="progress-bar" class="progress-bar h-2 rounded-full gradient-bg" style="width: 0%"></div>
    </div>
    <p class="text-center text-sm text-gray-600 mt-2">
    <span id="current-step-text">Paso 1 de 6</span>
    </p>
    </div>
    </div>

    <!-- Main Form Container -->
    <div class="container mx-auto px-4 py-8 max-w-5xl">
    <form id="insurance-form" class="bg-white rounded-2xl shadow-xl p-8" method="post" action="/enviarformularios/autos_procesar.php" novalidate>

    <!-- honeypot -->
    <div style="position:absolute;left:-9999px;opacity:0" aria-hidden="true">
      <label for="website">Website</label>
      <input type="text" name="website" id="website" tabindex="-1" autocomplete="off">
    </div>

    <!-- CSRF -->
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf,ENT_QUOTES) ?>">

    <!-- ========================================== -->
    <!-- PASO 1: Datos del Tomador + Asegurado -->
    <!-- ========================================== -->
    <div class="form-section active" data-step="1">
    <div class="mb-8">
    <h2 class="text-3xl font-bold text-gray-800 mb-2">
    <i class="fas fa-user text-purple-600 mr-3"></i>Datos del Tomador y Asegurado
    </h2>
    <p class="text-gray-600">Información del titular de la póliza</p>
    </div>

    <div class="space-y-6">
    <!-- TOMADOR -->
    <div class="bg-purple-50 border border-purple-200 rounded-lg p-6">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Datos del Tomador</h3>

    <div class="mb-4">
    <label class="block text-sm font-semibold text-gray-700 mb-2">Nombre / Razón Social <span class="text-red-500">*</span></label>
    <input type="text" name="tomador_nombre" value="<?= htmlspecialchars($clienteData['nombre']) ?>" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" required>
    </div>

    <div class="mb-4">
    <label class="block text-sm font-semibold text-gray-700 mb-2">Tipo de Identificación <span class="text-red-500">*</span></label>
    <div class="grid grid-cols-3 md:grid-cols-5 gap-2">
    <label class="flex items-center space-x-2 p-2 border rounded cursor-pointer hover:bg-purple-50">
    <input type="checkbox" name="cb_tomador_pj_nacional" value="1" class="checkbox-custom"><span class="text-xs">PJ Nacional</span>
    </label>
    <label class="flex items-center space-x-2 p-2 border rounded cursor-pointer hover:bg-purple-50">
    <input type="checkbox" name="cb_tomador_pj_gobierno" value="1" class="checkbox-custom"><span class="text-xs">Gobierno</span>
    </label>
    <label class="flex items-center space-x-2 p-2 border rounded cursor-pointer hover:bg-purple-50">
    <input type="checkbox" name="cb_tomador_pj_autonoma" value="1" class="checkbox-custom"><span class="text-xs">Autónoma</span>
    </label>
    <label class="flex items-center space-x-2 p-2 border rounded cursor-pointer hover:bg-purple-50">
    <input type="checkbox" name="cb_tomador_pj_extranjera" value="1" class="checkbox-custom"><span class="text-xs">PJ Extranjera</span>
    </label>
    <label class="flex items-center space-x-2 p-2 border rounded cursor-pointer hover:bg-purple-50">
    <input type="checkbox" name="cb_tomador_pf_cedula" value="1" class="checkbox-custom" checked><span class="text-xs">Cédula</span>
    </label>
    <label class="flex items-center space-x-2 p-2 border rounded cursor-pointer hover:bg-purple-50">
    <input type="checkbox" name="cb_tomador_pf_dimex" value="1" class="checkbox-custom"><span class="text-xs">DIMEX</span>
    </label>
    <label class="flex items-center space-x-2 p-2 border rounded cursor-pointer hover:bg-purple-50">
    <input type="checkbox" name="cb_tomador_pf_didi" value="1" class="checkbox-custom"><span class="text-xs">DIDI</span>
    </label>
    <label class="flex items-center space-x-2 p-2 border rounded cursor-pointer hover:bg-purple-50">
    <input type="checkbox" name="cb_tomador_pf_pasaporte" value="1" class="checkbox-custom"><span class="text-xs">Pasaporte</span>
    </label>
    <label class="flex items-center space-x-2 p-2 border rounded cursor-pointer hover:bg-purple-50">
    <input type="checkbox" name="cb_tomador_pf_otro" value="1" class="checkbox-custom"><span class="text-xs">Otro</span>
    </label>
    </div>
    </div>

    <div class="grid md:grid-cols-2 gap-4 mb-4">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Otro Tipo ID (si aplica)</label>
    <input type="text" name="tomador_otro_tipo" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Número de Identificación <span class="text-red-500">*</span></label>
    <input type="text" name="tomador_num_id" value="<?= htmlspecialchars($clienteData['cedula']) ?>" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" required>
    </div>
    </div>

    <div class="grid md:grid-cols-2 gap-4 mb-4">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Correo Electrónico <span class="text-red-500">*</span></label>
    <input type="email" name="tomador_correo" value="<?= htmlspecialchars($clienteData['correo']) ?>" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" required>
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Teléfono / Celular <span class="text-red-500">*</span></label>
    <input type="tel" name="tomador_telefono" value="<?= htmlspecialchars($clienteData['telefono']) ?>" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" required>
    </div>
    </div>

    <div class="mb-4">
    <label class="block text-sm font-semibold text-gray-700 mb-2">Domicilio</label>
    <textarea name="tomador_domicilio" rows="2" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none"></textarea>
    </div>

    <div class="grid md:grid-cols-3 gap-4">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Provincia</label>
    <select name="tomador_provincia" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    <option value="">Seleccione...</option>
    <option value="San José">San José</option>
    <option value="Alajuela">Alajuela</option>
    <option value="Cartago">Cartago</option>
    <option value="Heredia">Heredia</option>
    <option value="Guanacaste">Guanacaste</option>
    <option value="Puntarenas">Puntarenas</option>
    <option value="Limón">Limón</option>
    </select>
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Cantón</label>
    <input type="text" name="tomador_canton" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Distrito</label>
    <input type="text" name="tomador_distrito" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    </div>
    </div>
    </div>

    <!-- ASEGURADO -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Datos del Asegurado (si es diferente al tomador)</h3>

    <div class="mb-4">
    <label class="block text-sm font-semibold text-gray-700 mb-2">Nombre / Razón Social</label>
    <input type="text" name="asegurado_nombre" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    </div>

    <div class="mb-4">
    <label class="block text-sm font-semibold text-gray-700 mb-2">Tipo de Identificación</label>
    <div class="grid grid-cols-3 md:grid-cols-5 gap-2">
    <label class="flex items-center space-x-2 p-2 border rounded cursor-pointer hover:bg-blue-50">
    <input type="checkbox" name="cb_asegurado_pj_nacional" value="1" class="checkbox-custom"><span class="text-xs">PJ Nacional</span>
    </label>
    <label class="flex items-center space-x-2 p-2 border rounded cursor-pointer hover:bg-blue-50">
    <input type="checkbox" name="cb_asegurado_pj_gobierno" value="1" class="checkbox-custom"><span class="text-xs">Gobierno</span>
    </label>
    <label class="flex items-center space-x-2 p-2 border rounded cursor-pointer hover:bg-blue-50">
    <input type="checkbox" name="cb_asegurado_pj_autonoma" value="1" class="checkbox-custom"><span class="text-xs">Autónoma</span>
    </label>
    <label class="flex items-center space-x-2 p-2 border rounded cursor-pointer hover:bg-blue-50">
    <input type="checkbox" name="cb_asegurado_pj_extranjera" value="1" class="checkbox-custom"><span class="text-xs">PJ Extranjera</span>
    </label>
    <label class="flex items-center space-x-2 p-2 border rounded cursor-pointer hover:bg-blue-50">
    <input type="checkbox" name="cb_asegurado_pf_cedula" value="1" class="checkbox-custom"><span class="text-xs">Cédula</span>
    </label>
    <label class="flex items-center space-x-2 p-2 border rounded cursor-pointer hover:bg-blue-50">
    <input type="checkbox" name="cb_asegurado_pf_dimex" value="1" class="checkbox-custom"><span class="text-xs">DIMEX</span>
    </label>
    <label class="flex items-center space-x-2 p-2 border rounded cursor-pointer hover:bg-blue-50">
    <input type="checkbox" name="cb_asegurado_pf_didi" value="1" class="checkbox-custom"><span class="text-xs">DIDI</span>
    </label>
    <label class="flex items-center space-x-2 p-2 border rounded cursor-pointer hover:bg-blue-50">
    <input type="checkbox" name="cb_asegurado_pf_pasaporte" value="1" class="checkbox-custom"><span class="text-xs">Pasaporte</span>
    </label>
    <label class="flex items-center space-x-2 p-2 border rounded cursor-pointer hover:bg-blue-50">
    <input type="checkbox" name="cb_asegurado_pf_otro" value="1" class="checkbox-custom"><span class="text-xs">Otro</span>
    </label>
    </div>
    </div>

    <div class="grid md:grid-cols-2 gap-4 mb-4">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Otro Tipo ID</label>
    <input type="text" name="asegurado_otro_tipo" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Número de Identificación</label>
    <input type="text" name="asegurado_num_id" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    </div>
    </div>

    <div class="grid md:grid-cols-2 gap-4 mb-4">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Correo Electrónico</label>
    <input type="email" name="asegurado_correo" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Teléfono / Celular</label>
    <input type="tel" name="asegurado_telefono" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    </div>
    </div>

    <div class="mb-4">
    <label class="block text-sm font-semibold text-gray-700 mb-2">Domicilio</label>
    <textarea name="asegurado_domicilio" rows="2" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none"></textarea>
    </div>

    <div class="grid md:grid-cols-3 gap-4">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Provincia</label>
    <select name="asegurado_provincia" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    <option value="">Seleccione...</option>
    <option value="San José">San José</option>
    <option value="Alajuela">Alajuela</option>
    <option value="Cartago">Cartago</option>
    <option value="Heredia">Heredia</option>
    <option value="Guanacaste">Guanacaste</option>
    <option value="Puntarenas">Puntarenas</option>
    <option value="Limón">Limón</option>
    </select>
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Cantón</label>
    <input type="text" name="asegurado_canton" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Distrito</label>
    <input type="text" name="asegurado_distrito" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    </div>
    </div>
    </div>

    <!-- Notificaciones -->
    <div class="bg-green-50 border border-green-200 rounded-lg p-6">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Preferencias de Notificación</h3>
    <div class="grid md:grid-cols-2 gap-4 mb-4">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Notificar a:</label>
    <div class="flex space-x-4">
    <label class="flex items-center space-x-2"><input type="checkbox" name="cb_notif_tomador" value="1" class="checkbox-custom" checked><span>Tomador</span></label>
    <label class="flex items-center space-x-2"><input type="checkbox" name="cb_notif_asegurado" value="1" class="checkbox-custom"><span>Asegurado</span></label>
    </div>
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Medio de notificación:</label>
    <div class="flex flex-wrap gap-3">
    <label class="flex items-center space-x-2"><input type="checkbox" name="cb_notif_domicilio" value="1" class="checkbox-custom"><span>Domicilio</span></label>
    <label class="flex items-center space-x-2"><input type="checkbox" name="cb_notif_telefono" value="1" class="checkbox-custom"><span>Teléfono</span></label>
    <label class="flex items-center space-x-2"><input type="checkbox" name="cb_notif_correo" value="1" class="checkbox-custom" checked><span>Correo</span></label>
    </div>
    </div>
    </div>
    <div class="grid md:grid-cols-2 gap-4">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Apartado Postal</label>
    <input type="text" name="notif_apartado" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Fax</label>
    <input type="text" name="notif_fax" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    </div>
    </div>
    </div>
    </div>
    </div>

    <!-- ========================================== -->
    <!-- PASO 2: Datos del Vehículo -->
    <!-- ========================================== -->
    <div class="form-section" data-step="2">
    <div class="mb-8">
    <h2 class="text-3xl font-bold text-gray-800 mb-2">
    <i class="fas fa-car text-purple-600 mr-3"></i>Datos del Vehículo
    </h2>
    <p class="text-gray-600">Información completa del vehículo a asegurar</p>
    </div>

    <div class="space-y-6">
    <!-- Datos básicos vehículo -->
    <div class="bg-purple-50 border border-purple-200 rounded-lg p-6">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Identificación del Vehículo</h3>

    <div class="grid md:grid-cols-2 gap-4 mb-4">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Placa <span class="text-red-500">*</span></label>
    <input type="text" name="vehiculo_placa" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none uppercase" required>
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Tipo de Vehículo <span class="text-red-500">*</span></label>
    <input type="text" name="vehiculo_tipo" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="Ej: Automóvil, Pick Up, SUV" required>
    </div>
    </div>

    <div class="mb-4">
    <label class="block text-sm font-semibold text-gray-700 mb-2">Marca, Modelo y Serie <span class="text-red-500">*</span></label>
    <input type="text" name="vehiculo_marca_modelo" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="Ej: Toyota Corolla XLE" required>
    </div>

    <div class="grid md:grid-cols-3 gap-4 mb-4">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Año <span class="text-red-500">*</span></label>
    <input type="number" name="vehiculo_ano" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" min="1990" max="2026" required>
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Color <span class="text-red-500">*</span></label>
    <input type="text" name="vehiculo_color" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" required>
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Combustible</label>
    <select name="vehiculo_combustible" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    <option value="">Seleccione...</option>
    <option value="Gasolina">Gasolina</option>
    <option value="Diesel">Diésel</option>
    <option value="Hibrido">Híbrido</option>
    <option value="Electrico">Eléctrico</option>
    <option value="Gas">Gas (GLP)</option>
    </select>
    </div>
    </div>

    <div class="grid md:grid-cols-3 gap-4 mb-4">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Cilindraje (cc)</label>
    <input type="text" name="vehiculo_cilindraje" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Capacidad (personas)</label>
    <input type="text" name="vehiculo_capacidad" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Peso Bruto (kg)</label>
    <input type="text" name="vehiculo_peso_bruto" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    </div>
    </div>

    <div class="grid md:grid-cols-2 gap-4">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">N° Chasis / VIN <span class="text-red-500">*</span></label>
    <input type="text" name="vehiculo_vin" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none uppercase" maxlength="17" required>
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">N° Motor</label>
    <input type="text" name="vehiculo_motor" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none uppercase">
    </div>
    </div>
    </div>

    <!-- Tipo de Carga -->
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Tipo de Carga (si aplica)</h3>
    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
    <label class="flex items-center space-x-2 p-3 border rounded cursor-pointer hover:bg-yellow-100">
    <input type="checkbox" name="cb_carga_combustible" value="1" class="checkbox-custom"><span>Combustible</span>
    </label>
    <label class="flex items-center space-x-2 p-3 border rounded cursor-pointer hover:bg-yellow-100">
    <input type="checkbox" name="cb_carga_construccion" value="1" class="checkbox-custom"><span>Mat. Construcción</span>
    </label>
    <label class="flex items-center space-x-2 p-3 border rounded cursor-pointer hover:bg-yellow-100">
    <input type="checkbox" name="cb_carga_gas" value="1" class="checkbox-custom"><span>Gas Licuado</span>
    </label>
    <label class="flex items-center space-x-2 p-3 border rounded cursor-pointer hover:bg-yellow-100">
    <input type="checkbox" name="cb_carga_animales" value="1" class="checkbox-custom"><span>Animales</span>
    </label>
    <label class="flex items-center space-x-2 p-3 border rounded cursor-pointer hover:bg-yellow-100">
    <input type="checkbox" name="cb_carga_liquidos" value="1" class="checkbox-custom"><span>Líquidos</span>
    </label>
    <label class="flex items-center space-x-2 p-3 border rounded cursor-pointer hover:bg-yellow-100">
    <input type="checkbox" name="cb_carga_madera" value="1" class="checkbox-custom"><span>Madera</span>
    </label>
    </div>
    </div>

    <!-- Uso del Vehículo -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Uso del Vehículo</h3>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
    <label class="flex items-center space-x-2 p-3 border rounded cursor-pointer hover:bg-blue-100">
    <input type="checkbox" name="cb_uso_personal" value="1" class="checkbox-custom" checked><span>Personal</span>
    </label>
    <label class="flex items-center space-x-2 p-3 border rounded cursor-pointer hover:bg-blue-100">
    <input type="checkbox" name="cb_uso_personal_comercial" value="1" class="checkbox-custom"><span>Personal-Comercial</span>
    </label>
    <label class="flex items-center space-x-2 p-3 border rounded cursor-pointer hover:bg-blue-100">
    <input type="checkbox" name="cb_uso_comercial" value="1" class="checkbox-custom"><span>Comercial</span>
    </label>
    <label class="flex items-center space-x-2 p-3 border rounded cursor-pointer hover:bg-blue-100">
    <input type="checkbox" name="cb_uso_alquiler" value="1" class="checkbox-custom"><span>Alquiler</span>
    </label>
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Especificar Uso</label>
    <input type="text" name="uso_especificar" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    </div>
    </div>

    <!-- Ruta Bus (si aplica) -->
    <div class="bg-gray-50 border border-gray-200 rounded-lg p-6">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Ruta de Bus (si aplica)</h3>
    <div class="flex flex-wrap gap-4">
    <label class="flex items-center space-x-2 p-3 border rounded cursor-pointer hover:bg-gray-100">
    <input type="checkbox" name="cb_ruta_nacional" value="1" class="checkbox-custom"><span>Ruta Nacional</span>
    </label>
    <label class="flex items-center space-x-2 p-3 border rounded cursor-pointer hover:bg-gray-100">
    <input type="checkbox" name="cb_ruta_internacional" value="1" class="checkbox-custom"><span>Ruta Internacional</span>
    </label>
    <label class="flex items-center space-x-2 p-3 border rounded cursor-pointer hover:bg-gray-100">
    <input type="checkbox" name="cb_ruta_no_remunerado" value="1" class="checkbox-custom"><span>No Remunerado</span>
    </label>
    </div>
    </div>
    </div>
    </div>

    <!-- ========================================== -->
    <!-- PASO 3: Valor, Interés y Conductor -->
    <!-- ========================================== -->
    <div class="form-section" data-step="3">
    <div class="mb-8">
    <h2 class="text-3xl font-bold text-gray-800 mb-2">
    <i class="fas fa-dollar-sign text-purple-600 mr-3"></i>Valor, Interés y Conductor
    </h2>
    <p class="text-gray-600">Información financiera y de propiedad</p>
    </div>

    <div class="space-y-6">
    <!-- Valor del Vehículo -->
    <div class="bg-green-50 border border-green-200 rounded-lg p-6">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Valor del Vehículo</h3>
    <div class="grid md:grid-cols-2 gap-4 mb-4">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Valor en Colones (₡)</label>
    <input type="text" name="valor_vehiculo_colones" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Valor en Dólares ($)</label>
    <input type="text" name="valor_vehiculo_dolares" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    </div>
    </div>
    <div class="flex flex-wrap gap-4">
    <label class="flex items-center space-x-2"><input type="checkbox" name="cb_actualizacion_si" value="1" class="checkbox-custom"><span>Actualización Automática de Monto</span></label>
    </div>
    </div>

    <!-- Especiales -->
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Condiciones Especiales</h3>
    <div class="grid md:grid-cols-3 gap-4">
    <label class="flex items-center space-x-2 p-3 border rounded cursor-pointer hover:bg-yellow-100">
    <input type="checkbox" name="cb_modificado_si" value="1" class="checkbox-custom"><span>Vehículo Modificado</span>
    </label>
    <label class="flex items-center space-x-2 p-3 border rounded cursor-pointer hover:bg-yellow-100">
    <input type="checkbox" name="cb_exonerado_si" value="1" class="checkbox-custom"><span>Exonerado de Impuestos</span>
    </label>
    <label class="flex items-center space-x-2 p-3 border rounded cursor-pointer hover:bg-yellow-100">
    <input type="checkbox" name="cb_extraprima_si" value="1" class="checkbox-custom"><span>Extraprima Repuestos</span>
    </label>
    </div>
    </div>

    <!-- Interés Asegurable -->
    <div class="bg-purple-50 border border-purple-200 rounded-lg p-6">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Interés Asegurable</h3>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
    <label class="flex items-center space-x-2 p-2 border rounded cursor-pointer hover:bg-purple-100">
    <input type="checkbox" name="cb_interes_propietario" value="1" class="checkbox-custom" checked><span class="text-sm">Propietario</span>
    </label>
    <label class="flex items-center space-x-2 p-2 border rounded cursor-pointer hover:bg-purple-100">
    <input type="checkbox" name="cb_interes_accionista" value="1" class="checkbox-custom"><span class="text-sm">Accionista</span>
    </label>
    <label class="flex items-center space-x-2 p-2 border rounded cursor-pointer hover:bg-purple-100">
    <input type="checkbox" name="cb_interes_conyuge" value="1" class="checkbox-custom"><span class="text-sm">Cónyuge</span>
    </label>
    <label class="flex items-center space-x-2 p-2 border rounded cursor-pointer hover:bg-purple-100">
    <input type="checkbox" name="cb_interes_arrendatario" value="1" class="checkbox-custom"><span class="text-sm">Arrendatario</span>
    </label>
    <label class="flex items-center space-x-2 p-2 border rounded cursor-pointer hover:bg-purple-100">
    <input type="checkbox" name="cb_interes_depositario" value="1" class="checkbox-custom"><span class="text-sm">Depositario Judicial</span>
    </label>
    <label class="flex items-center space-x-2 p-2 border rounded cursor-pointer hover:bg-purple-100">
    <input type="checkbox" name="cb_interes_acreedor" value="1" class="checkbox-custom"><span class="text-sm">Acreedor Prendario</span>
    </label>
    <label class="flex items-center space-x-2 p-2 border rounded cursor-pointer hover:bg-purple-100">
    <input type="checkbox" name="cb_interes_comodatario" value="1" class="checkbox-custom"><span class="text-sm">Comodatario</span>
    </label>
    <label class="flex items-center space-x-2 p-2 border rounded cursor-pointer hover:bg-purple-100">
    <input type="checkbox" name="cb_interes_otro" value="1" class="checkbox-custom"><span class="text-sm">Otro</span>
    </label>
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Especificar Otro Interés</label>
    <input type="text" name="interes_otro_texto" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    </div>
    </div>

    <!-- Acreedor Prendario -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Acreedor Prendario (si aplica)</h3>
    <div class="grid md:grid-cols-2 gap-4 mb-4">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Nombre del Acreedor</label>
    <input type="text" name="acreedor_nombre" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Tipo ID Acreedor</label>
    <input type="text" name="acreedor_tipo_id" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    </div>
    </div>
    <div class="grid md:grid-cols-3 gap-4">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Identificación Acreedor</label>
    <input type="text" name="acreedor_id" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Monto Acreencia</label>
    <input type="text" name="acreedor_monto" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Porcentaje Acreencia (%)</label>
    <input type="text" name="acreedor_porcentaje" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    </div>
    </div>
    </div>

    <!-- Conductor Habitual -->
    <div class="bg-gray-50 border border-gray-200 rounded-lg p-6">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Conductor Habitual</h3>
    <div class="grid md:grid-cols-2 gap-4">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Nombre del Conductor</label>
    <input type="text" name="conductor_habitual" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Identificación del Conductor</label>
    <input type="text" name="conductor_id" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    </div>
    </div>
    </div>

    <!-- Tipo de Aseguramiento -->
    <div class="bg-orange-50 border border-orange-200 rounded-lg p-6">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Tipo de Aseguramiento</h3>
    <div class="flex flex-wrap gap-4">
    <label class="flex items-center space-x-2 p-3 border rounded cursor-pointer hover:bg-orange-100">
    <input type="checkbox" name="cb_valor_declarado" value="1" class="checkbox-custom"><span>Valor Declarado</span>
    </label>
    <label class="flex items-center space-x-2 p-3 border rounded cursor-pointer hover:bg-orange-100">
    <input type="checkbox" name="cb_primer_riesgo" value="1" class="checkbox-custom"><span>Primer Riesgo Absoluto</span>
    </label>
    <label class="flex items-center space-x-2 p-3 border rounded cursor-pointer hover:bg-orange-100">
    <input type="checkbox" name="cb_valor_convenido" value="1" class="checkbox-custom"><span>Valor Convenido</span>
    </label>
    </div>
    </div>
    </div>
    </div>

    <!-- ========================================== -->
    <!-- PASO 4: Coberturas Principales -->
    <!-- ========================================== -->
    <div class="form-section" data-step="4">
    <div class="mb-8">
    <h2 class="text-3xl font-bold text-gray-800 mb-2">
    <i class="fas fa-shield-alt text-purple-600 mr-3"></i>Coberturas Principales
    </h2>
    <p class="text-gray-600">Seleccione las coberturas básicas</p>
    </div>

    <div class="space-y-6">
    <!-- Coberturas A-G -->
    <div class="bg-purple-50 border border-purple-200 rounded-lg p-6">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Coberturas Básicas</h3>

    <!-- Cobertura A -->
    <div class="p-4 border rounded-lg mb-3 bg-white">
    <div class="flex items-start space-x-3">
    <input type="checkbox" name="cb_cob_a" value="1" class="checkbox-custom mt-1" checked>
    <div class="flex-1">
    <div class="font-semibold text-gray-800">A: Responsabilidad Civil por Lesión/Muerte</div>
    <div class="text-sm text-gray-600 mb-2">Cobertura obligatoria por daños a terceros</div>
    <div>
    <label class="text-sm text-gray-700">Monto Asegurado:</label>
    <input type="text" name="cob_a_monto" class="input-field w-full px-3 py-2 border rounded mt-1" placeholder="₡ o $">
    </div>
    </div>
    </div>
    </div>

    <!-- Cobertura B -->
    <div class="p-4 border rounded-lg mb-3 bg-white">
    <div class="flex items-start space-x-3">
    <input type="checkbox" name="cb_cob_b" value="1" class="checkbox-custom mt-1">
    <div class="flex-1">
    <div class="font-semibold text-gray-800">B: Servicios Médicos</div>
    <div class="text-sm text-gray-600 mb-2">Gastos médicos para ocupantes</div>
    <div>
    <label class="text-sm text-gray-700">Monto Asegurado:</label>
    <input type="text" name="cob_b_monto" class="input-field w-full px-3 py-2 border rounded mt-1" placeholder="₡ o $">
    </div>
    </div>
    </div>
    </div>

    <!-- Cobertura C -->
    <div class="p-4 border rounded-lg mb-3 bg-white">
    <div class="flex items-start space-x-3">
    <input type="checkbox" name="cb_cob_c" value="1" class="checkbox-custom mt-1" checked>
    <div class="flex-1">
    <div class="font-semibold text-gray-800">C: Responsabilidad Civil por Daños a Propiedad</div>
    <div class="text-sm text-gray-600 mb-2">Cobertura por daños a bienes de terceros</div>
    <div>
    <label class="text-sm text-gray-700">Monto Asegurado:</label>
    <input type="text" name="cob_c_monto" class="input-field w-full px-3 py-2 border rounded mt-1" placeholder="₡ o $">
    </div>
    </div>
    </div>
    </div>

    <!-- Cobertura D -->
    <div class="p-4 border rounded-lg mb-3 bg-white">
    <div class="flex items-start space-x-3">
    <input type="checkbox" name="cb_cob_d" value="1" class="checkbox-custom mt-1">
    <div class="flex-1">
    <div class="font-semibold text-gray-800">D: Colisión y Vuelco</div>
    <div class="text-sm text-gray-600">Daños propios por accidentes</div>
    </div>
    </div>
    </div>

    <!-- Cobertura E -->
    <div class="p-4 border rounded-lg mb-3 bg-white">
    <div class="flex items-start space-x-3">
    <input type="checkbox" name="cb_cob_e" value="1" class="checkbox-custom mt-1" checked>
    <div class="flex-1">
    <div class="font-semibold text-gray-800">E: Gastos Legales</div>
    <div class="text-sm text-gray-600">Defensa legal en caso de accidentes</div>
    </div>
    </div>
    </div>

    <!-- Cobertura F -->
    <div class="p-4 border rounded-lg mb-3 bg-white">
    <div class="flex items-start space-x-3">
    <input type="checkbox" name="cb_cob_f" value="1" class="checkbox-custom mt-1">
    <div class="flex-1">
    <div class="font-semibold text-gray-800">F: Robo y Hurto</div>
    <div class="text-sm text-gray-600 mb-2">Protección contra robo total o parcial</div>
    <div class="flex flex-wrap gap-3 mt-2">
    <label class="flex items-center space-x-2"><input type="checkbox" name="cb_dispositivo_si" value="1" class="checkbox-custom"><span class="text-sm">Tiene dispositivo de seguridad</span></label>
    </div>
    <div class="mt-2">
    <label class="text-sm text-gray-700">Tipo de dispositivo:</label>
    <input type="text" name="dispositivo_tipo" class="input-field w-full px-3 py-2 border rounded mt-1">
    </div>
    </div>
    </div>
    </div>

    <!-- Cobertura G -->
    <div class="p-4 border rounded-lg mb-3 bg-white">
    <div class="flex items-start space-x-3">
    <input type="checkbox" name="cb_cob_g" value="1" class="checkbox-custom mt-1" checked>
    <div class="flex-1">
    <div class="font-semibold text-gray-800">G: Multiasistencia Auto</div>
    <div class="text-sm text-gray-600">Asistencia vial 24/7 (grúa, cerrajería, etc.)</div>
    </div>
    </div>
    </div>
    </div>

    <!-- Coberturas H-P -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Coberturas Adicionales</h3>

    <!-- Cobertura H -->
    <div class="p-4 border rounded-lg mb-3 bg-white">
    <div class="flex items-start space-x-3">
    <input type="checkbox" name="cb_cob_h" value="1" class="checkbox-custom mt-1">
    <div class="flex-1">
    <div class="font-semibold text-gray-800">H: Riesgos Adicionales</div>
    <div class="text-sm text-gray-600">Inundación, terremoto, huelga, etc.</div>
    </div>
    </div>
    </div>

    <!-- Cobertura J -->
    <div class="p-4 border rounded-lg mb-3 bg-white">
    <div class="flex items-start space-x-3">
    <input type="checkbox" name="cb_cob_j" value="1" class="checkbox-custom mt-1">
    <div class="flex-1">
    <div class="font-semibold text-gray-800">J: Pérdida de Objetos Personales</div>
    <div class="text-sm text-gray-600 mb-2">Objetos dentro del vehículo</div>
    <div>
    <label class="text-sm text-gray-700">Monto por Evento:</label>
    <input type="text" name="cob_j_monto" class="input-field w-full px-3 py-2 border rounded mt-1" placeholder="₡ o $">
    </div>
    </div>
    </div>
    </div>

    <!-- Cobertura K -->
    <div class="p-4 border rounded-lg mb-3 bg-white">
    <div class="flex items-start space-x-3">
    <input type="checkbox" name="cb_cob_k" value="1" class="checkbox-custom mt-1">
    <div class="flex-1">
    <div class="font-semibold text-gray-800">K: Transporte Alternativo</div>
    <div class="text-sm text-gray-600 mb-2">Vehículo sustituto mientras el suyo está en reparación</div>
    <div class="grid md:grid-cols-2 gap-3">
    <div>
    <label class="text-sm text-gray-700">Días Asegurados:</label>
    <input type="text" name="cob_k_dias" class="input-field w-full px-3 py-2 border rounded mt-1">
    </div>
    <div>
    <label class="text-sm text-gray-700">Monto por Día:</label>
    <input type="text" name="cob_k_monto_dia" class="input-field w-full px-3 py-2 border rounded mt-1">
    </div>
    </div>
    </div>
    </div>
    </div>

    <!-- Cobertura M -->
    <div class="p-4 border rounded-lg mb-3 bg-white">
    <div class="flex items-start space-x-3">
    <input type="checkbox" name="cb_cob_m" value="1" class="checkbox-custom mt-1">
    <div class="flex-1">
    <div class="font-semibold text-gray-800">M: Multiasistencia Extendida</div>
    <div class="text-sm text-gray-600">Servicios adicionales de asistencia</div>
    </div>
    </div>
    </div>

    <!-- Cobertura N -->
    <div class="p-4 border rounded-lg mb-3 bg-white">
    <div class="flex items-start space-x-3">
    <input type="checkbox" name="cb_cob_n" value="1" class="checkbox-custom mt-1">
    <div class="flex-1">
    <div class="font-semibold text-gray-800">N: Exención de Deducible</div>
    <div class="text-sm text-gray-600 mb-2">Aplicar exención a coberturas:</div>
    <div class="flex flex-wrap gap-3">
    <label class="flex items-center space-x-2"><input type="checkbox" name="cb_cob_n_c" value="1" class="checkbox-custom"><span class="text-sm">Cob. C</span></label>
    <label class="flex items-center space-x-2"><input type="checkbox" name="cb_cob_n_d" value="1" class="checkbox-custom"><span class="text-sm">Cob. D</span></label>
    <label class="flex items-center space-x-2"><input type="checkbox" name="cb_cob_n_f" value="1" class="checkbox-custom"><span class="text-sm">Cob. F</span></label>
    <label class="flex items-center space-x-2"><input type="checkbox" name="cb_cob_n_h" value="1" class="checkbox-custom"><span class="text-sm">Cob. H</span></label>
    </div>
    </div>
    </div>
    </div>

    <!-- Cobertura P -->
    <div class="p-4 border rounded-lg mb-3 bg-white">
    <div class="flex items-start space-x-3">
    <input type="checkbox" name="cb_cob_p" value="1" class="checkbox-custom mt-1">
    <div class="flex-1">
    <div class="font-semibold text-gray-800">P: Gastos Médicos Ocupantes</div>
    <div class="text-sm text-gray-600 mb-2">Cobertura médica adicional para ocupantes</div>
    <div>
    <label class="text-sm text-gray-700">Monto Asegurado:</label>
    <input type="text" name="cob_p_monto" class="input-field w-full px-3 py-2 border rounded mt-1" placeholder="₡ o $">
    </div>
    </div>
    </div>
    </div>
    </div>
    </div>
    </div>

    <!-- ========================================== -->
    <!-- PASO 5: Coberturas Adicionales y Pago -->
    <!-- ========================================== -->
    <div class="form-section" data-step="5">
    <div class="mb-8">
    <h2 class="text-3xl font-bold text-gray-800 mb-2">
    <i class="fas fa-plus-circle text-purple-600 mr-3"></i>Coberturas Especiales y Pago
    </h2>
    <p class="text-gray-600">Coberturas adicionales, vigencia y forma de pago</p>
    </div>

    <div class="space-y-6">
    <!-- Coberturas Y, Z, IDD, IDP -->
    <div class="bg-purple-50 border border-purple-200 rounded-lg p-6">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Coberturas Especiales</h3>

    <!-- Cobertura Y -->
    <div class="p-4 border rounded-lg mb-3 bg-white">
    <div class="flex items-start space-x-3">
    <input type="checkbox" name="cb_cob_y" value="1" class="checkbox-custom mt-1">
    <div class="flex-1">
    <div class="font-semibold text-gray-800">Y: Extraterritorialidad</div>
    <div class="text-sm text-gray-600 mb-2">Cobertura fuera del territorio nacional</div>
    <div class="flex flex-wrap gap-3 mb-2">
    <label class="flex items-center space-x-2"><input type="checkbox" name="cb_y_permanente" value="1" class="checkbox-custom"><span class="text-sm">Permanente</span></label>
    <label class="flex items-center space-x-2"><input type="checkbox" name="cb_y_temporal" value="1" class="checkbox-custom"><span class="text-sm">Temporal</span></label>
    </div>
    <div class="grid md:grid-cols-3 gap-3">
    <div>
    <label class="text-sm text-gray-700">Destino:</label>
    <input type="text" name="y_destino" class="input-field w-full px-3 py-2 border rounded mt-1">
    </div>
    <div>
    <label class="text-sm text-gray-700">Desde:</label>
    <input type="date" name="y_desde" class="input-field w-full px-3 py-2 border rounded mt-1">
    </div>
    <div>
    <label class="text-sm text-gray-700">Hasta:</label>
    <input type="date" name="y_hasta" class="input-field w-full px-3 py-2 border rounded mt-1">
    </div>
    </div>
    </div>
    </div>
    </div>

    <!-- Cobertura Z -->
    <div class="p-4 border rounded-lg mb-3 bg-white">
    <div class="flex items-start space-x-3">
    <input type="checkbox" name="cb_cob_z" value="1" class="checkbox-custom mt-1">
    <div class="flex-1">
    <div class="font-semibold text-gray-800">Z: Riesgos Particulares</div>
    <div class="text-sm text-gray-600">Coberturas especiales bajo solicitud</div>
    </div>
    </div>
    </div>

    <!-- Cobertura IDD -->
    <div class="p-4 border rounded-lg mb-3 bg-white">
    <div class="flex items-start space-x-3">
    <input type="checkbox" name="cb_cob_idd" value="1" class="checkbox-custom mt-1">
    <div class="flex-1">
    <div class="font-semibold text-gray-800">IDD: Indemnización Deducible</div>
    <div class="text-sm text-gray-600 mb-2">Reembolso del deducible pagado</div>
    <div>
    <label class="text-sm text-gray-700">Monto:</label>
    <input type="text" name="cob_idd_monto" class="input-field w-full px-3 py-2 border rounded mt-1">
    </div>
    </div>
    </div>
    </div>

    <!-- Cobertura IDP -->
    <div class="p-4 border rounded-lg mb-3 bg-white">
    <div class="flex items-start space-x-3">
    <input type="checkbox" name="cb_cob_idp" value="1" class="checkbox-custom mt-1">
    <div class="flex-1">
    <div class="font-semibold text-gray-800">IDP: Indemnización Deducible Plus</div>
    <div class="text-sm text-gray-600 mb-2">Reembolso extendido del deducible</div>
    <div>
    <label class="text-sm text-gray-700">Monto:</label>
    <input type="text" name="cob_idp_monto" class="input-field w-full px-3 py-2 border rounded mt-1">
    </div>
    </div>
    </div>
    </div>
    </div>

    <!-- Otros Riesgos -->
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Otros Riesgos Especiales</h3>
    <div class="space-y-3">
    <label class="flex items-center space-x-2 p-3 border rounded bg-white">
    <input type="checkbox" name="cb_rc_alcohol" value="1" class="checkbox-custom"><span>RC Bajo Influencia de Alcohol</span>
    </label>
    <div class="p-3 border rounded bg-white">
    <label class="flex items-center space-x-2 mb-2">
    <input type="checkbox" name="cb_blindaje" value="1" class="checkbox-custom"><span>Blindaje</span>
    </label>
    <input type="text" name="blindaje_monto" class="input-field w-full px-3 py-2 border rounded" placeholder="Monto del blindaje">
    </div>
    <div class="p-3 border rounded bg-white">
    <label class="flex items-center space-x-2 mb-2">
    <input type="checkbox" name="cb_acople" value="1" class="checkbox-custom"><span>Acople de Vehículos</span>
    </label>
    <input type="text" name="acople_valor" class="input-field w-full px-3 py-2 border rounded" placeholder="Valor remolcado">
    </div>
    <div class="p-3 border rounded bg-white">
    <label class="flex items-center space-x-2 mb-2">
    <input type="checkbox" name="cb_equipo_especial" value="1" class="checkbox-custom"><span>Equipo Especial</span>
    </label>
    <input type="text" name="equipo_especial_monto" class="input-field w-full px-3 py-2 border rounded" placeholder="Monto equipo especial">
    </div>
    <label class="flex items-center space-x-2 p-3 border rounded bg-white">
    <input type="checkbox" name="cb_proteccion_flotilla" value="1" class="checkbox-custom"><span>Protección Flotilla</span>
    </label>
    </div>
    </div>

    <!-- Vigencia y Forma de Pago -->
    <div class="bg-green-50 border border-green-200 rounded-lg p-6">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Vigencia y Forma de Pago</h3>
    <div class="grid md:grid-cols-2 gap-6">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-3">Plazo de la Póliza</label>
    <div class="flex flex-wrap gap-3">
    <label class="flex items-center space-x-2 p-3 border rounded bg-white">
    <input type="checkbox" name="cb_plazo_anual" value="1" class="checkbox-custom" checked><span>Anual</span>
    </label>
    <label class="flex items-center space-x-2 p-3 border rounded bg-white">
    <input type="checkbox" name="cb_plazo_corto" value="1" class="checkbox-custom"><span>Corto Plazo</span>
    </label>
    </div>
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-3">Forma de Pago</label>
    <div class="flex flex-wrap gap-2">
    <label class="flex items-center space-x-2 p-2 border rounded bg-white">
    <input type="checkbox" name="cb_pago_anual" value="1" class="checkbox-custom"><span class="text-sm">Anual</span>
    </label>
    <label class="flex items-center space-x-2 p-2 border rounded bg-white">
    <input type="checkbox" name="cb_pago_semestral" value="1" class="checkbox-custom" checked><span class="text-sm">Semestral</span>
    </label>
    <label class="flex items-center space-x-2 p-2 border rounded bg-white">
    <input type="checkbox" name="cb_pago_trimestral" value="1" class="checkbox-custom"><span class="text-sm">Trimestral</span>
    </label>
    <label class="flex items-center space-x-2 p-2 border rounded bg-white">
    <input type="checkbox" name="cb_pago_mensual" value="1" class="checkbox-custom"><span class="text-sm">Mensual</span>
    </label>
    </div>
    <div class="flex flex-wrap gap-3 mt-3">
    <label class="flex items-center space-x-2"><input type="checkbox" name="cb_cobro_automatico" value="1" class="checkbox-custom"><span class="text-sm">Cargo Automático</span></label>
    <label class="flex items-center space-x-2"><input type="checkbox" name="cb_deduccion_mensual" value="1" class="checkbox-custom"><span class="text-sm">Deducción Mensual</span></label>
    </div>
    </div>
    </div>
    </div>

    <!-- Beneficiario -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Beneficiario</h3>
    <div class="grid md:grid-cols-4 gap-4">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Nombre</label>
    <input type="text" name="beneficiario1_nombre" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Tipo ID</label>
    <input type="text" name="beneficiario1_tipo_id" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">N° Identificación</label>
    <input type="text" name="beneficiario1_id" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Porcentaje (%)</label>
    <input type="text" name="beneficiario1_porcentaje" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="100">
    </div>
    </div>
    </div>
    </div>
    </div>

    <!-- ========================================== -->
    <!-- PASO 6: Observaciones y Confirmación -->
    <!-- ========================================== -->
    <div class="form-section" data-step="6">
    <div class="mb-8">
    <h2 class="text-3xl font-bold text-gray-800 mb-2">
    <i class="fas fa-check-circle text-green-600 mr-3"></i>Observaciones y Confirmación
    </h2>
    <p class="text-gray-600">Revisa y confirma tu solicitud</p>
    </div>

    <div class="space-y-6">
    <!-- Observaciones -->
    <div class="bg-gray-50 border border-gray-200 rounded-lg p-6">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Observaciones</h3>
    <textarea name="observaciones" rows="4" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="Notas adicionales sobre el vehículo o la póliza..."></textarea>

    <div class="mt-4 space-y-3">
    <label class="flex items-center space-x-2">
    <input type="checkbox" name="cb_seguro_otra_si" value="1" class="checkbox-custom">
    <span>¿Tiene seguro con otra aseguradora?</span>
    </label>
    <div>
    <label class="block text-sm text-gray-700 mb-1">¿Cuál compañía?</label>
    <input type="text" name="otra_aseguradora" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    </div>
    </div>

    <div class="mt-4">
    <label class="block text-sm font-semibold text-gray-700 mb-2">Fotos del vehículo:</label>
    <div class="flex flex-wrap gap-3">
    <label class="flex items-center space-x-2"><input type="checkbox" name="cb_fotos_si" value="1" class="checkbox-custom"><span>Adjuntaré fotos</span></label>
    <label class="flex items-center space-x-2"><input type="checkbox" name="cb_fotos_no" value="1" class="checkbox-custom"><span>No tengo fotos disponibles</span></label>
    </div>
    </div>
    </div>

    <!-- Firma -->
    <div class="bg-purple-50 border border-purple-200 rounded-lg p-6">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Firma Digital</h3>
    <div class="grid md:grid-cols-2 gap-6">
    <div>
    <h4 class="font-semibold text-gray-700 mb-3">Firma del Tomador</h4>
    <div class="space-y-3">
    <div>
    <label class="block text-sm text-gray-700 mb-1">Nombre:</label>
    <input type="text" name="firma_tomador_nombre" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    </div>
    <div>
    <label class="block text-sm text-gray-700 mb-1">N° Identificación:</label>
    <input type="text" name="firma_tomador_id" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    </div>
    <div>
    <label class="block text-sm text-gray-700 mb-1">Cargo (si es PJ):</label>
    <input type="text" name="firma_tomador_cargo" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    </div>
    </div>
    </div>
    <div>
    <h4 class="font-semibold text-gray-700 mb-3">Firma del Asegurado (si diferente)</h4>
    <div class="space-y-3">
    <div>
    <label class="block text-sm text-gray-700 mb-1">Nombre:</label>
    <input type="text" name="firma_asegurado_nombre" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    </div>
    <div>
    <label class="block text-sm text-gray-700 mb-1">N° Identificación:</label>
    <input type="text" name="firma_asegurado_id" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    </div>
    <div>
    <label class="block text-sm text-gray-700 mb-1">Cargo (si es PJ):</label>
    <input type="text" name="firma_asegurado_cargo" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    </div>
    </div>
    </div>
    </div>
    </div>

    <!-- Consentimientos -->
    <div class="space-y-4">
    <div class="bg-yellow-50 border border-yellow-300 rounded-lg p-4">
    <label class="flex items-start space-x-3 cursor-pointer">
    <input type="checkbox" name="consentimientoInfo" class="checkbox-custom mt-1" required>
    <div class="text-sm text-gray-700">
    <strong>Declaro que la información proporcionada es verídica.</strong> En caso de comprobarse cualquier omisión o falsa declaración, eximo al Instituto Nacional de Seguros de cualquier responsabilidad.
    </div>
    </label>
    </div>

    <div class="bg-blue-50 border border-blue-300 rounded-lg p-4">
    <label class="flex items-start space-x-3 cursor-pointer">
    <input type="checkbox" name="consentimientoGrabacion" class="checkbox-custom mt-1" required>
    <div class="text-sm text-gray-700">
    <strong>Consiento expresamente</strong> que el INS grabe y utilice las llamadas telefónicas como prueba para procesos administrativos y judiciales.
    </div>
    </label>
    </div>

    <div class="bg-green-50 border border-green-300 rounded-lg p-4">
    <label class="flex items-start space-x-3 cursor-pointer">
    <input type="checkbox" name="consentimientoDatos" class="checkbox-custom mt-1" required>
    <div class="text-sm text-gray-700">
    <strong>Autorizo al INS</strong> a incluir mi información en una base de datos para ejecutar el contrato y ofrecer productos o servicios adicionales.
    </div>
    </label>
    </div>
    </div>
    </div>
    </div>

    <!-- Botones de Navegación -->
    <div class="flex justify-between items-center mt-8 pt-6 border-t-2 border-gray-200">
    <button type="button" id="btn-prev" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300 transition flex items-center" style="display: none;">
    <i class="fas fa-arrow-left mr-2"></i>Anterior
    </button>
    <div></div>
    <button type="button" id="btn-next" class="px-8 py-3 gradient-bg text-white rounded-lg font-semibold hover:opacity-90 transition flex items-center">
    Siguiente<i class="fas fa-arrow-right ml-2"></i>
    </button>
    <button type="submit" id="btn-submit" class="px-8 py-3 bg-green-600 text-white rounded-lg font-bold hover:bg-green-700 transition flex items-center" style="display: none;">
    <i class="fas fa-paper-plane mr-2"></i>Enviar Solicitud
    </button>
    </div>
    </form>
    </div>

    <!-- Form Logic -->
    <script src="/assets/js/form-logic.js?v=2025-12-11"></script>
</body>
</html>
