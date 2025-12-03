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

    <!-- SEO Meta Tags -->
    <title>Cotizar Seguro de Auto en Costa Rica | Seguro Vehicular INS | AseguraloCR</title>
    <meta name="description" content="Cotiza tu seguro de auto INS en Costa Rica. Cobertura completa, responsabilidad civil, asistencia vial 24/7. Cotizacion rapida y 100% en linea.">
    <meta name="keywords" content="seguro de auto costa rica, seguro vehiculo, seguro carro, INS auto, seguro responsabilidad civil, asistencia vial, cotizar seguro auto">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://www.aseguralocr.com/autos.php">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://www.aseguralocr.com/autos.php">
    <meta property="og:title" content="Cotizar Seguro de Auto | INS Costa Rica">
    <meta property="og:description" content="Protege tu vehiculo con seguro INS. Cobertura completa y asistencia 24/7. Cotiza en minutos.">
    <meta property="og:image" content="https://www.aseguralocr.com/imagenes/og-image.jpg">

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
    <div class="flex-1 flex items-center" id="step-indicators">
    </div>
    </div>
    <div class="w-full bg-gray-200 rounded-full h-2">
    <div id="progress-bar" class="progress-bar h-2 rounded-full gradient-bg" style="width: 0%"></div>
    </div>
    <p class="text-center text-sm text-gray-600 mt-2">
    <span id="current-step-text">Paso 1 de 5</span>
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

    <!-- PASO 1: Datos del Tomador/Propietario -->
    <div class="form-section active" data-step="1">
    <div class="mb-8">
    <h2 class="text-3xl font-bold text-gray-800 mb-2">
    <i class="fas fa-user text-purple-600 mr-3"></i>Datos del Propietario
    </h2>
    <p class="text-gray-600">Información del titular de la póliza</p>
    </div>

    <div class="space-y-6">
    <!-- Tipo de Identificación -->
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Tipo de Identificación <span class="text-red-500">*</span>
    </label>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <label class="flex items-center space-x-2 cursor-pointer">
    <input type="radio" name="tipoId" value="cedula" class="radio-custom" required <?= $clienteData['tipoId'] === 'cedula' || $clienteData['tipoId'] === '' ? 'checked' : '' ?>>
    <span class="text-sm">Cédula Física</span>
    </label>
    <label class="flex items-center space-x-2 cursor-pointer">
    <input type="radio" name="tipoId" value="cedula-juridica" class="radio-custom" <?= $clienteData['tipoId'] === 'cedula-juridica' ? 'checked' : '' ?>>
    <span class="text-sm">Cédula Jurídica</span>
    </label>
    <label class="flex items-center space-x-2 cursor-pointer">
    <input type="radio" name="tipoId" value="dimex" class="radio-custom" <?= $clienteData['tipoId'] === 'dimex' ? 'checked' : '' ?>>
    <span class="text-sm">DIMEX</span>
    </label>
    <label class="flex items-center space-x-2 cursor-pointer">
    <input type="radio" name="tipoId" value="pasaporte" class="radio-custom" <?= $clienteData['tipoId'] === 'pasaporte' ? 'checked' : '' ?>>
    <span class="text-sm">Pasaporte</span>
    </label>
    </div>
    </div>

    <!-- Número de Identificación -->
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Número de Identificación <span class="text-red-500">*</span>
    </label>
    <input type="text" name="numeroId" value="<?= htmlspecialchars($clienteData['cedula']) ?>" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="Ej: 1-2345-6789" required>
    </div>

    <!-- Nombre Completo -->
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Nombre Completo / Razón Social <span class="text-red-500">*</span>
    </label>
    <input type="text" name="nombreCompleto" value="<?= htmlspecialchars($clienteData['nombre']) ?>" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="Nombre completo o razón social" required>
    </div>

    <!-- Ubicación -->
    <div class="grid md:grid-cols-2 gap-4">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Provincia <span class="text-red-500">*</span>
    </label>
    <select name="provincia" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" required>
    <option value="">Seleccione...</option>
    <option value="san-jose">San José</option>
    <option value="alajuela">Alajuela</option>
    <option value="cartago">Cartago</option>
    <option value="heredia">Heredia</option>
    <option value="guanacaste">Guanacaste</option>
    <option value="puntarenas">Puntarenas</option>
    <option value="limon">Limón</option>
    </select>
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Cantón <span class="text-red-500">*</span>
    </label>
    <select id="canton" name="canton" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" required>
    <option value="">Seleccione...</option>
    </select>
    </div>
    </div>

    <div class="grid md:grid-cols-2 gap-4">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Distrito <span class="text-red-500">*</span>
    </label>
    <select id="distrito" name="distrito" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" required>
    <option value="">Seleccione...</option>
    </select>
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    País <span class="text-red-500">*</span>
    </label>
    <input type="text" name="pais" value="Costa Rica" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" required>
    </div>
    </div>

    <!-- Dirección Exacta -->
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Dirección Exacta <span class="text-red-500">*</span>
    </label>
    <textarea name="direccion" rows="3" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="Ingrese su dirección completa..." required></textarea>
    </div>

    <!-- Teléfonos -->
    <div class="grid md:grid-cols-2 gap-4">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Teléfono Celular <span class="text-red-500">*</span>
    </label>
    <input type="tel" name="telefonoCelular" value="<?= htmlspecialchars($clienteData['telefono']) ?>" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="8888-8888" required>
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Teléfono Oficina/Casa
    </label>
    <input type="tel" name="telefonoFijo" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="2222-2222">
    </div>
    </div>

    <!-- Correo Electrónico -->
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Correo Electrónico <span class="text-red-500">*</span>
    </label>
    <input type="email" name="correo" value="<?= htmlspecialchars($clienteData['correo']) ?>" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="ejemplo@correo.com" required>
    <p class="text-xs text-gray-500 mt-1">A este correo se enviará la cotización</p>
    </div>
    </div>
    </div>

    <!-- PASO 2: Datos del Vehículo -->
    <div class="form-section" data-step="2">
    <div class="mb-8">
    <h2 class="text-3xl font-bold text-gray-800 mb-2">
    <i class="fas fa-car text-purple-600 mr-3"></i>Datos del Vehículo
    </h2>
    <p class="text-gray-600">Información del vehículo a asegurar</p>
    </div>

    <div class="space-y-6">
    <!-- Placa -->
    <div class="grid md:grid-cols-2 gap-4">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Número de Placa <span class="text-red-500">*</span>
    </label>
    <input type="text" name="placa" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none uppercase" placeholder="Ej: ABC-123" required>
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Tipo de Vehículo <span class="text-red-500">*</span>
    </label>
    <select name="tipoVehiculo" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" required>
    <option value="">Seleccione...</option>
    <option value="automovil">Automóvil</option>
    <option value="pickup">Pick Up</option>
    <option value="suv">SUV / Crossover</option>
    <option value="camion">Camión</option>
    <option value="motocicleta">Motocicleta</option>
    <option value="microbus">Microbús</option>
    <option value="bus">Bus</option>
    <option value="otro">Otro</option>
    </select>
    </div>
    </div>

    <!-- Marca y Modelo -->
    <div class="grid md:grid-cols-3 gap-4">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Marca <span class="text-red-500">*</span>
    </label>
    <input type="text" name="marca" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="Ej: Toyota" required>
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Línea / Modelo <span class="text-red-500">*</span>
    </label>
    <input type="text" name="modelo" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="Ej: Corolla" required>
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Estilo
    </label>
    <input type="text" name="estilo" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="Ej: XLE">
    </div>
    </div>

    <!-- Año y Color -->
    <div class="grid md:grid-cols-3 gap-4">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Año <span class="text-red-500">*</span>
    </label>
    <input type="number" name="ano" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="Ej: 2022" min="1990" max="2026" required>
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Color <span class="text-red-500">*</span>
    </label>
    <input type="text" name="color" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="Ej: Blanco" required>
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Cilindrada (cc)
    </label>
    <input type="number" name="cilindrada" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="Ej: 1800">
    </div>
    </div>

    <!-- VIN y Motor -->
    <div class="grid md:grid-cols-2 gap-4">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Número de VIN / Chasis <span class="text-red-500">*</span>
    </label>
    <input type="text" name="vin" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none uppercase" placeholder="17 caracteres" maxlength="17" required>
    <p class="text-xs text-gray-500 mt-1">Se encuentra en la tarjeta de circulación o en el vehículo</p>
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Número de Motor
    </label>
    <input type="text" name="numeroMotor" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none uppercase" placeholder="Número de motor">
    </div>
    </div>

    <!-- Combustible y Transmisión -->
    <div class="grid md:grid-cols-2 gap-4">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Tipo de Combustible <span class="text-red-500">*</span>
    </label>
    <select name="combustible" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" required>
    <option value="">Seleccione...</option>
    <option value="gasolina">Gasolina</option>
    <option value="diesel">Diésel</option>
    <option value="hibrido">Híbrido</option>
    <option value="electrico">Eléctrico</option>
    <option value="gas">Gas (GLP)</option>
    </select>
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Transmisión <span class="text-red-500">*</span>
    </label>
    <select name="transmision" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" required>
    <option value="">Seleccione...</option>
    <option value="automatica">Automática</option>
    <option value="manual">Manual</option>
    <option value="cvt">CVT</option>
    </select>
    </div>
    </div>

    <!-- Tracción y Puertas -->
    <div class="grid md:grid-cols-2 gap-4">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Tracción
    </label>
    <select name="traccion" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    <option value="">Seleccione...</option>
    <option value="2wd">2WD (Tracción simple)</option>
    <option value="4wd">4WD / 4x4</option>
    <option value="awd">AWD (Tracción integral)</option>
    </select>
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Cantidad de Puertas
    </label>
    <select name="puertas" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    <option value="">Seleccione...</option>
    <option value="2">2 puertas</option>
    <option value="3">3 puertas</option>
    <option value="4">4 puertas</option>
    <option value="5">5 puertas</option>
    </select>
    </div>
    </div>

    <!-- Extras -->
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-3">
    Equipamiento Adicional (seleccione todos los que apliquen)
    </label>
    <div class="grid md:grid-cols-3 gap-3">
    <label class="flex items-center space-x-2 p-3 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-purple-500 transition">
    <input type="checkbox" name="extras[]" value="aire-acondicionado" class="checkbox-custom">
    <span class="text-sm">Aire Acondicionado</span>
    </label>
    <label class="flex items-center space-x-2 p-3 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-purple-500 transition">
    <input type="checkbox" name="extras[]" value="vidrios-electricos" class="checkbox-custom">
    <span class="text-sm">Vidrios Eléctricos</span>
    </label>
    <label class="flex items-center space-x-2 p-3 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-purple-500 transition">
    <input type="checkbox" name="extras[]" value="alarma" class="checkbox-custom">
    <span class="text-sm">Alarma</span>
    </label>
    <label class="flex items-center space-x-2 p-3 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-purple-500 transition">
    <input type="checkbox" name="extras[]" value="airbags" class="checkbox-custom">
    <span class="text-sm">Airbags</span>
    </label>
    <label class="flex items-center space-x-2 p-3 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-purple-500 transition">
    <input type="checkbox" name="extras[]" value="abs" class="checkbox-custom">
    <span class="text-sm">Frenos ABS</span>
    </label>
    <label class="flex items-center space-x-2 p-3 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-purple-500 transition">
    <input type="checkbox" name="extras[]" value="camara-retroceso" class="checkbox-custom">
    <span class="text-sm">Cámara de Retroceso</span>
    </label>
    </div>
    </div>
    </div>
    </div>

    <!-- PASO 3: Uso del Vehículo e Historial -->
    <div class="form-section" data-step="3">
    <div class="mb-8">
    <h2 class="text-3xl font-bold text-gray-800 mb-2">
    <i class="fas fa-road text-purple-600 mr-3"></i>Uso del Vehículo
    </h2>
    <p class="text-gray-600">Información sobre el uso y estado del vehículo</p>
    </div>

    <div class="space-y-6">
    <!-- Uso del Vehículo -->
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-3">
    Uso Principal del Vehículo <span class="text-red-500">*</span>
    </label>
    <div class="grid md:grid-cols-2 gap-4">
    <label class="flex items-center space-x-3 p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-purple-500 transition">
    <input type="radio" name="usoVehiculo" value="particular" class="radio-custom" required>
    <div>
    <div class="font-semibold text-gray-800">Particular</div>
    <div class="text-xs text-gray-500">Uso personal y familiar</div>
    </div>
    </label>
    <label class="flex items-center space-x-3 p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-purple-500 transition">
    <input type="radio" name="usoVehiculo" value="comercial" class="radio-custom">
    <div>
    <div class="font-semibold text-gray-800">Comercial</div>
    <div class="text-xs text-gray-500">Negocios o empresa</div>
    </div>
    </label>
    <label class="flex items-center space-x-3 p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-purple-500 transition">
    <input type="radio" name="usoVehiculo" value="alquiler" class="radio-custom">
    <div>
    <div class="font-semibold text-gray-800">Alquiler / Rent a Car</div>
    <div class="text-xs text-gray-500">Servicio de alquiler</div>
    </div>
    </label>
    <label class="flex items-center space-x-3 p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-purple-500 transition">
    <input type="radio" name="usoVehiculo" value="taxi" class="radio-custom">
    <div>
    <div class="font-semibold text-gray-800">Taxi / Transporte Público</div>
    <div class="text-xs text-gray-500">Servicio de transporte</div>
    </div>
    </label>
    </div>
    </div>

    <!-- Kilometraje -->
    <div class="grid md:grid-cols-2 gap-4">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Kilometraje Actual (aproximado)
    </label>
    <input type="number" name="kilometraje" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="Ej: 50000" min="0">
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Lugar donde permanece el vehículo
    </label>
    <select name="lugarGuarda" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    <option value="">Seleccione...</option>
    <option value="cochera-cerrada">Cochera cerrada</option>
    <option value="cochera-abierta">Cochera abierta / Carport</option>
    <option value="parqueo-vigilado">Parqueo vigilado</option>
    <option value="calle">En la calle</option>
    </select>
    </div>
    </div>

    <!-- Financiamiento -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
    <h3 class="font-semibold text-gray-800 mb-3 flex items-center">
    <i class="fas fa-university text-blue-600 mr-2"></i>Información Financiera
    </h3>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    ¿El vehículo tiene financiamiento o gravamen?
    </label>
    <div class="flex space-x-6 mb-3">
    <label class="flex items-center space-x-2 cursor-pointer">
    <input type="radio" name="tieneFinanciamiento" value="si" class="radio-custom">
    <span class="text-sm">Sí</span>
    </label>
    <label class="flex items-center space-x-2 cursor-pointer">
    <input type="radio" name="tieneFinanciamiento" value="no" class="radio-custom">
    <span class="text-sm">No</span>
    </label>
    </div>
    <div id="datos-financiamiento" style="display: none;" class="mt-3">
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Nombre de la Entidad Financiera / Acreedor
    </label>
    <input type="text" name="entidadFinanciera" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="Ej: Banco Nacional de Costa Rica">
    </div>
    </div>
    </div>

    <!-- Historial -->
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-3">
    Historial del Vehículo
    </label>
    <div class="space-y-3">
    <div>
    <label class="block text-sm text-gray-600 mb-2">
    ¿El vehículo ha tenido siniestros en los últimos 3 años?
    </label>
    <div class="flex space-x-6">
    <label class="flex items-center space-x-2 cursor-pointer">
    <input type="radio" name="siniestrosPrevios" value="si" class="radio-custom">
    <span class="text-sm">Sí</span>
    </label>
    <label class="flex items-center space-x-2 cursor-pointer">
    <input type="radio" name="siniestrosPrevios" value="no" class="radio-custom">
    <span class="text-sm">No</span>
    </label>
    </div>
    </div>
    <div id="detalle-siniestros" style="display: none;">
    <label class="block text-sm text-gray-600 mb-2">Describa brevemente los siniestros:</label>
    <textarea name="detalleSiniestros" rows="2" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="Describa los siniestros anteriores..."></textarea>
    </div>
    </div>
    </div>

    <!-- Conductores -->
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    ¿Quiénes serán los conductores habituales?
    </label>
    <textarea name="conductoresHabituales" rows="2" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="Ej: Titular (45 años), Esposa (42 años), Hijo (20 años)"></textarea>
    <p class="text-xs text-gray-500 mt-1">Indique nombres y edades de los conductores principales</p>
    </div>
    </div>
    </div>

    <!-- PASO 4: Coberturas y Montos -->
    <div class="form-section" data-step="4">
    <div class="mb-8">
    <h2 class="text-3xl font-bold text-gray-800 mb-2">
    <i class="fas fa-shield-alt text-purple-600 mr-3"></i>Coberturas y Montos
    </h2>
    <p class="text-gray-600">Seleccione las coberturas deseadas</p>
    </div>

    <div class="space-y-6">
    <!-- Valor del Vehículo -->
    <div class="bg-green-50 border border-green-200 rounded-lg p-6">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Valor del Vehículo</h3>
    <div class="grid md:grid-cols-2 gap-4">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Valor a Asegurar <span class="text-red-500">*</span>
    </label>
    <div class="relative">
    <span class="absolute left-4 top-3 text-gray-500">$</span>
    <input type="number" name="valorVehiculo" class="input-field w-full pl-10 pr-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="Ej: 25000" min="0" required>
    </div>
    <p class="text-xs text-gray-500 mt-1">Valor de mercado del vehículo en dólares</p>
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Moneda <span class="text-red-500">*</span>
    </label>
    <select name="moneda" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" required>
    <option value="dolares">Dólares ($)</option>
    <option value="colones">Colones (₡)</option>
    </select>
    </div>
    </div>
    </div>

    <!-- Coberturas Principales -->
    <div class="bg-purple-50 border-2 border-purple-200 rounded-lg p-6">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Coberturas Principales</h3>

    <div class="space-y-4">
    <!-- Responsabilidad Civil -->
    <label class="flex items-start space-x-3 p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-purple-500 transition bg-white">
    <input type="checkbox" name="coberturaRC" value="si" class="checkbox-custom mt-1" checked>
    <div class="flex-1">
    <div class="font-semibold text-gray-800">Responsabilidad Civil</div>
    <div class="text-sm text-gray-600">Cubre daños a terceros (personas, vehículos o propiedades)</div>
    </div>
    </label>

    <!-- Daños Propios -->
    <label class="flex items-start space-x-3 p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-purple-500 transition bg-white">
    <input type="checkbox" name="coberturaDanosPropios" value="si" class="checkbox-custom mt-1">
    <div class="flex-1">
    <div class="font-semibold text-gray-800">Daños Propios (Colisión y Vuelco)</div>
    <div class="text-sm text-gray-600">Cubre daños a su vehículo por accidentes</div>
    </div>
    </label>

    <!-- Robo Total -->
    <label class="flex items-start space-x-3 p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-purple-500 transition bg-white">
    <input type="checkbox" name="coberturaRoboTotal" value="si" class="checkbox-custom mt-1">
    <div class="flex-1">
    <div class="font-semibold text-gray-800">Robo Total</div>
    <div class="text-sm text-gray-600">Cubre la pérdida total del vehículo por robo</div>
    </div>
    </label>

    <!-- Robo Parcial -->
    <label class="flex items-start space-x-3 p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-purple-500 transition bg-white">
    <input type="checkbox" name="coberturaRoboParcial" value="si" class="checkbox-custom mt-1">
    <div class="flex-1">
    <div class="font-semibold text-gray-800">Robo Parcial</div>
    <div class="text-sm text-gray-600">Cubre robo de partes y accesorios del vehículo</div>
    </div>
    </label>
    </div>
    </div>

    <!-- Coberturas Adicionales -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Coberturas Adicionales (Opcionales)</h3>

    <div class="space-y-4">
    <label class="flex items-start space-x-3 p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-blue-500 transition bg-white">
    <input type="checkbox" name="coberturaAsistencia" value="si" class="checkbox-custom mt-1">
    <div class="flex-1">
    <div class="font-semibold text-gray-800">Asistencia en Carretera</div>
    <div class="text-sm text-gray-600">Grúa, paso de corriente, llanta de repuesto, cerrajería</div>
    </div>
    </label>

    <label class="flex items-start space-x-3 p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-blue-500 transition bg-white">
    <input type="checkbox" name="coberturaGastosMedicos" value="si" class="checkbox-custom mt-1">
    <div class="flex-1">
    <div class="font-semibold text-gray-800">Gastos Médicos</div>
    <div class="text-sm text-gray-600">Cubre gastos médicos del conductor y pasajeros</div>
    </div>
    </label>

    <label class="flex items-start space-x-3 p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-blue-500 transition bg-white">
    <input type="checkbox" name="coberturaVehiculoSustituto" value="si" class="checkbox-custom mt-1">
    <div class="flex-1">
    <div class="font-semibold text-gray-800">Vehículo Sustituto</div>
    <div class="text-sm text-gray-600">Auto de reemplazo mientras el suyo está en reparación</div>
    </div>
    </label>

    <label class="flex items-start space-x-3 p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-blue-500 transition bg-white">
    <input type="checkbox" name="coberturaCatastrofe" value="si" class="checkbox-custom mt-1">
    <div class="flex-1">
    <div class="font-semibold text-gray-800">Eventos Catastróficos</div>
    <div class="text-sm text-gray-600">Terremoto, inundación, caída de árboles</div>
    </div>
    </label>

    <label class="flex items-start space-x-3 p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-blue-500 transition bg-white">
    <input type="checkbox" name="coberturaVidrios" value="si" class="checkbox-custom mt-1">
    <div class="flex-1">
    <div class="font-semibold text-gray-800">Rotura de Vidrios</div>
    <div class="text-sm text-gray-600">Parabrisas, ventanas y espejos</div>
    </div>
    </label>
    </div>
    </div>

    <!-- Deducible y Plan de Pago -->
    <div class="grid md:grid-cols-2 gap-4">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Deducible Preferido <span class="text-red-500">*</span>
    </label>
    <select name="deducible" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" required>
    <option value="">Seleccione...</option>
    <option value="0">Sin deducible (prima más alta)</option>
    <option value="200">$200</option>
    <option value="300">$300</option>
    <option value="500">$500</option>
    <option value="1000">$1,000 (prima más baja)</option>
    </select>
    <p class="text-xs text-gray-500 mt-1">Monto a pagar antes de que aplique el seguro</p>
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Plan de Pago <span class="text-red-500">*</span>
    </label>
    <select name="planPago" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" required>
    <option value="anual">Anual (mejor precio)</option>
    <option value="semestral">Semestral</option>
    <option value="trimestral">Trimestral</option>
    <option value="mensual">Mensual</option>
    </select>
    </div>
    </div>
    </div>
    </div>

    <!-- PASO 5: Resumen y Confirmación -->
    <div class="form-section" data-step="5">
    <div class="mb-8">
    <h2 class="text-3xl font-bold text-gray-800 mb-2">
    <i class="fas fa-check-circle text-green-600 mr-3"></i>Resumen de Solicitud
    </h2>
    <p class="text-gray-600">Revisa tu información antes de enviar</p>
    </div>

    <div id="resumen-contenido" class="space-y-6">
    <!-- El resumen se generará dinámicamente con JavaScript -->
    </div>

    <div class="flex justify-end space-x-3 mt-4">
    <button id="resumen-print" type="button" class="px-4 py-2 bg-gray-100 rounded-md text-sm hover:bg-gray-200">
    <i class="fas fa-print mr-2"></i>Imprimir
    </button>
    </div>

    <!-- Consentimientos -->
    <div class="mt-8 space-y-4">
    <div class="bg-yellow-50 border border-yellow-300 rounded-lg p-4">
    <label class="flex items-start space-x-3 cursor-pointer">
    <input type="checkbox" name="consentimientoInfo" class="checkbox-custom mt-1" required>
    <div class="text-sm text-gray-700">
    <strong>Declaro que la información detallada en este documento es verídica.</strong> En caso de comprobarse cualquier omisión o falsa declaración, eximo al Instituto Nacional de Seguros de cualquier responsabilidad, dando como resultado la terminación del contrato de seguros.
    </div>
    </label>
    </div>

    <div class="bg-blue-50 border border-blue-300 rounded-lg p-4">
    <label class="flex items-start space-x-3 cursor-pointer">
    <input type="checkbox" name="consentimientoGrabacion" class="checkbox-custom mt-1" required>
    <div class="text-sm text-gray-700">
    <strong>Consiento expresamente</strong> que el Instituto Nacional de Seguros grabe y utilice las llamadas telefónicas que se realicen a las líneas de servicio, como prueba para los procesos administrativos y judiciales.
    </div>
    </label>
    </div>

    <div class="bg-green-50 border border-green-300 rounded-lg p-4">
    <label class="flex items-start space-x-3 cursor-pointer">
    <input type="checkbox" name="consentimientoDatos" class="checkbox-custom mt-1" required>
    <div class="text-sm text-gray-700">
    <strong>Autorizo al INS</strong> a incluir mi información en una base de datos bajo su responsabilidad, con medidas de seguridad adecuadas, para ejecutar el contrato y ofrecer productos o servicios adicionales.
    </div>
    </label>
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

    <!-- Modal de Confirmación -->
    <div id="modal-confirmacion" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-8 text-center animate-fadeIn">
    <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
    <i class="fas fa-check text-green-600 text-4xl"></i>
    </div>
    <h3 class="text-2xl font-bold text-gray-800 mb-4">¡Solicitud Enviada!</h3>
    <p class="text-gray-600 mb-6">
    Hemos recibido tu solicitud de seguro de automóvil. Un agente se pondrá en contacto contigo en las próximas 24 horas.
    </p>
    <a href="/index.php" class="inline-block px-8 py-3 gradient-bg text-white rounded-lg font-semibold hover:opacity-90 transition">
    Volver al Inicio
    </a>
    </div>
    </div>

    <!-- Geo-module -->
    <script>
    (async function(){
      function normalizeKey(s){
        if(!s && s!==0) return '';
        try{
          return String(s).normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().replace(/[^a-z0-9]/g,'');
        }catch(e){
          return String(s).toLowerCase().replace(/\s+/g,'');
        }
      }

      try{
        const url = '/assets/data/cr-provincias.json?v=1';
        const resp = await fetch(url, {cache: 'no-store'});
        if(!resp.ok) return;
        const GEO = await resp.json();

        const normMap = {};
        Object.keys(GEO).forEach(k => { normMap[normalizeKey(k)] = k; });

        function findProvKey(rawProv){
          if(!rawProv) return null;
          const n = normalizeKey(rawProv);
          if(normMap[n]) return normMap[n];
          for(const nk of Object.keys(normMap)){
            if(nk.includes(n) || n.includes(nk)) return normMap[nk];
          }
          return null;
        }

        function populateCantones(provValue, cantonSel, distritoSel){
          cantonSel.innerHTML = '<option value="">Seleccione...</option>';
          distritoSel.innerHTML = '<option value="">Seleccione...</option>';
          if(!provValue) return;
          const matchedKey = findProvKey(provValue);
          if(!matchedKey) return;
          const cantonesObj = GEO[matchedKey];
          Object.keys(cantonesObj).forEach(c => {
            const opt = document.createElement('option'); opt.value = c; opt.textContent = c; cantonSel.appendChild(opt);
          });
        }

        function fillDistritos(cantonSel, distritoSel){
          const provSel = document.querySelector(`[name="${cantonSel.dataset.provName}"]`);
          const provVal = provSel ? provSel.value : null;
          distritoSel.innerHTML = '<option value="">Seleccione...</option>';
          if(!provVal) return;
          const matchedProvKey = findProvKey(provVal);
          if(!matchedProvKey) return;
          const lista = (GEO[matchedProvKey] && GEO[matchedProvKey][cantonSel.value]) || [];
          lista.forEach(d=>{
            const opt = document.createElement('option'); opt.value = d; opt.textContent = d; distritoSel.appendChild(opt);
          });
        }

        function setupPair(provName, cantonId, distritoId){
          const provSel = document.querySelector(`[name="${provName}"]`);
          const cantonSel = document.getElementById(cantonId);
          const distritoSel = document.getElementById(distritoId);
          if(!provSel || !cantonSel || !distritoSel) return;
          cantonSel.dataset.provName = provName;

          provSel.addEventListener('change', function(){
            populateCantones(this.value, cantonSel, distritoSel);
          });

          cantonSel.addEventListener('change', function(){
            fillDistritos(cantonSel, distritoSel);
          });
        }

        setupPair('provincia','canton','distrito');
      }catch(err){
        console.error('[Geo] error:', err);
      }
    })();
    </script>

    <!-- Form Logic -->
    <script src="/assets/js/form-logic.js?v=2025-11-07"></script>

    <!-- Toggle fields -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Financiamiento toggle
      const finRadios = document.querySelectorAll('input[name="tieneFinanciamiento"]');
      const datosFinanciamiento = document.getElementById('datos-financiamiento');
      finRadios.forEach(r => {
        r.addEventListener('change', function() {
          datosFinanciamiento.style.display = this.value === 'si' ? 'block' : 'none';
        });
      });

      // Siniestros toggle
      const sinRadios = document.querySelectorAll('input[name="siniestrosPrevios"]');
      const detalleSiniestros = document.getElementById('detalle-siniestros');
      sinRadios.forEach(r => {
        r.addEventListener('change', function() {
          detalleSiniestros.style.display = this.value === 'si' ? 'block' : 'none';
        });
      });
    });
    </script>

    <!-- Summary Generator -->
    <script>
    const fieldLabels = {
      tipoId: 'Tipo de Identificación',
      numeroId: 'Número de Identificación',
      nombreCompleto: 'Nombre Completo',
      provincia: 'Provincia',
      canton: 'Cantón',
      distrito: 'Distrito',
      pais: 'País',
      direccion: 'Dirección',
      telefonoCelular: 'Teléfono Celular',
      telefonoFijo: 'Teléfono Fijo',
      correo: 'Correo Electrónico',

      placa: 'Placa',
      tipoVehiculo: 'Tipo de Vehículo',
      marca: 'Marca',
      modelo: 'Modelo',
      estilo: 'Estilo',
      ano: 'Año',
      color: 'Color',
      cilindrada: 'Cilindrada',
      vin: 'VIN / Chasis',
      numeroMotor: 'Número de Motor',
      combustible: 'Combustible',
      transmision: 'Transmisión',
      traccion: 'Tracción',
      puertas: 'Puertas',
      extras: 'Equipamiento',

      usoVehiculo: 'Uso del Vehículo',
      kilometraje: 'Kilometraje',
      lugarGuarda: 'Lugar de Guarda',
      tieneFinanciamiento: 'Financiamiento',
      entidadFinanciera: 'Entidad Financiera',
      siniestrosPrevios: 'Siniestros Previos',
      conductoresHabituales: 'Conductores',

      valorVehiculo: 'Valor del Vehículo',
      moneda: 'Moneda',
      coberturaRC: 'Responsabilidad Civil',
      coberturaDanosPropios: 'Daños Propios',
      coberturaRoboTotal: 'Robo Total',
      coberturaRoboParcial: 'Robo Parcial',
      coberturaAsistencia: 'Asistencia Vial',
      coberturaGastosMedicos: 'Gastos Médicos',
      coberturaVehiculoSustituto: 'Vehículo Sustituto',
      coberturaCatastrofe: 'Eventos Catastróficos',
      coberturaVidrios: 'Rotura de Vidrios',
      deducible: 'Deducible',
      planPago: 'Plan de Pago',

      consentimientoInfo: 'Declaración Verídica',
      consentimientoGrabacion: 'Consentimiento Grabación',
      consentimientoDatos: 'Autorización Datos'
    };

    function readFieldValue(form, name) {
      const els = Array.from(form.querySelectorAll(`[name="${name}"], [name="${name}[]"]`));
      if (!els.length) return null;
      if (els.length === 1) {
        const el = els[0];
        if (el.type === 'checkbox') return el.checked ? (el.value || 'Sí') : null;
        if (el.type === 'radio') {
          const checked = form.querySelector(`[name="${name}"]:checked`);
          return checked ? checked.value : null;
        }
        return el.value || null;
      }
      const checked = els.filter(e => (e.type === 'checkbox' && e.checked) || (e.type === 'radio' && e.checked));
      if (checked.length === 0) return null;
      return checked.map(e => e.value || 'Sí').join(', ');
    }

    function formatCurrency(value, moneda) {
      if (!value) return '';
      const num = Number(value);
      if (isNaN(num)) return value;
      if (moneda === 'colones') {
        return '₡ ' + Math.round(num).toLocaleString();
      }
      return '$ ' + num.toLocaleString();
    }

    function buildSectionHTML(title, rows) {
      const rowsHtml = rows.map(r => {
        const safeValue = r.value ? String(r.value) : '<span class="text-gray-400">No indicado</span>';
        return `
        <div class="grid grid-cols-3 gap-x-4 py-2 border-b last:border-b-0">
          <div class="col-span-1 text-sm text-gray-600">${r.label}</div>
          <div class="col-span-2 text-sm text-gray-800">${safeValue}</div>
        </div>`;
      }).join('');
      return `
      <div class="bg-white shadow-sm rounded-lg border p-4">
        <h4 class="text-md font-semibold text-gray-800 mb-3">${title}</h4>
        <div class="border-t -mx-4 px-4">${rowsHtml}</div>
      </div>`;
    }

    function generateSummary() {
      const form = document.getElementById('insurance-form');
      const container = document.getElementById('resumen-contenido');
      if (!form || !container) return;

      const monedaVal = readFieldValue(form, 'moneda');

      const personalKeys = ['tipoId','numeroId','nombreCompleto','provincia','canton','distrito','direccion','telefonoCelular','telefonoFijo','correo'];
      const vehiculoKeys = ['placa','tipoVehiculo','marca','modelo','estilo','ano','color','cilindrada','vin','numeroMotor','combustible','transmision','traccion','puertas','extras'];
      const usoKeys = ['usoVehiculo','kilometraje','lugarGuarda','tieneFinanciamiento','entidadFinanciera','siniestrosPrevios','conductoresHabituales'];
      const coberturaKeys = ['coberturaRC','coberturaDanosPropios','coberturaRoboTotal','coberturaRoboParcial','coberturaAsistencia','coberturaGastosMedicos','coberturaVehiculoSustituto','coberturaCatastrofe','coberturaVidrios'];

      const personal = personalKeys.map(k => ({ label: fieldLabels[k] || k, value: readFieldValue(form,k) }));
      const vehiculo = vehiculoKeys.map(k => ({ label: fieldLabels[k] || k, value: readFieldValue(form,k) }));
      const uso = usoKeys.map(k => ({ label: fieldLabels[k] || k, value: readFieldValue(form,k) }));

      const valorRaw = readFieldValue(form, 'valorVehiculo');
      const coberturas = [
        { label: 'Valor del Vehículo', value: formatCurrency(valorRaw, monedaVal) },
        { label: 'Moneda', value: readFieldValue(form, 'moneda') },
        ...coberturaKeys.map(k => ({ label: fieldLabels[k] || k, value: readFieldValue(form,k) ? 'Sí' : 'No' })),
        { label: 'Deducible', value: readFieldValue(form, 'deducible') ? '$' + readFieldValue(form, 'deducible') : null },
        { label: 'Plan de Pago', value: readFieldValue(form, 'planPago') }
      ];

      const consentimientos = ['consentimientoInfo','consentimientoGrabacion','consentimientoDatos'].map(k => ({
        label: fieldLabels[k] || k,
        value: readFieldValue(form,k) ? 'Aceptado' : 'No aceptado'
      }));

      const sections = [
        { title: 'Datos del Propietario', rows: personal },
        { title: 'Datos del Vehículo', rows: vehiculo },
        { title: 'Uso del Vehículo', rows: uso },
        { title: 'Coberturas y Montos', rows: coberturas },
        { title: 'Consentimientos', rows: consentimientos }
      ];

      container.innerHTML = sections.map(s => buildSectionHTML(s.title, s.rows)).join('');
    }

    document.addEventListener('DOMContentLoaded', function () {
      const btnNext = document.getElementById('btn-next');
      const btnPrev = document.getElementById('btn-prev');

      function maybeGenerate() {
        const paso5 = document.querySelector('.form-section[data-step="5"].active');
        if (paso5) generateSummary();
      }

      if (btnNext) btnNext.addEventListener('click', () => setTimeout(maybeGenerate, 150));
      if (btnPrev) btnPrev.addEventListener('click', () => setTimeout(maybeGenerate, 150));
      document.addEventListener('stepChanged', maybeGenerate);
      maybeGenerate();

      const btnPrint = document.getElementById('resumen-print');
      if (btnPrint) btnPrint.addEventListener('click', () => window.print());
    });
    </script>

</body>
</html>
