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
        $stmt = $pdo->prepare("SELECT tipo_id, cedula, nombre, correo, telefono FROM clients WHERE id = ?");
        $stmt->execute([$_SESSION['client_id']]);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($cliente) {
            $clienteData = [
                'tipoId' => $cliente['tipo_id'] ?? 'cedula',
                'cedula' => $cliente['cedula'] ?? '',
                'nombre' => $cliente['nombre'] ?? '',
                'correo' => $cliente['correo'] ?? '',
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
    <title>Seguro de Riesgos del Trabajo - AseguraloCR</title>
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
    <i class="fas fa-hard-hat text-white text-xl"></i>
    </div>
    <div>
    <h1 class="text-xl font-bold text-gray-800">Seguro de Riesgos del Trabajo</h1>
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
    <form id="insurance-form" class="bg-white rounded-2xl shadow-xl p-8" method="post" action="/enviarformularios/rt_procesar.php" novalidate>

    <!-- honeypot -->
    <div style="position:absolute;left:-9999px;opacity:0" aria-hidden="true">
      <label for="website">Website</label>
      <input type="text" name="website" id="website" tabindex="-1" autocomplete="off">
    </div>

    <!-- CSRF -->
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf,ENT_QUOTES) ?>">

    <!-- PASO 1: Datos del Solicitante y Patrono -->
    <div class="form-section active" data-step="1">

    <!-- Sección: Datos del Solicitante -->
    <div class="mb-8">
    <h2 class="text-3xl font-bold text-gray-800 mb-2">
    <i class="fas fa-user text-purple-600 mr-3"></i>Datos del Solicitante
    </h2>
    <p class="text-gray-600">Información de la persona que solicita la cotización</p>
    </div>

    <div class="space-y-6 mb-10">
    <!-- Tipo de Identificación del Solicitante -->
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Tipo de Identificación <span class="text-red-500">*</span>
    </label>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <label class="flex items-center space-x-2 cursor-pointer">
    <input type="radio" name="solicitanteTipoId" value="cedula" class="radio-custom" required <?= $clienteData['tipoId'] === 'cedula' || $clienteData['tipoId'] === '' ? 'checked' : '' ?>>
    <span class="text-sm">Cédula</span>
    </label>
    <label class="flex items-center space-x-2 cursor-pointer">
    <input type="radio" name="solicitanteTipoId" value="dimex" class="radio-custom" <?= $clienteData['tipoId'] === 'dimex' ? 'checked' : '' ?>>
    <span class="text-sm">DIMEX</span>
    </label>
    <label class="flex items-center space-x-2 cursor-pointer">
    <input type="radio" name="solicitanteTipoId" value="pasaporte" class="radio-custom" <?= $clienteData['tipoId'] === 'pasaporte' ? 'checked' : '' ?>>
    <span class="text-sm">Pasaporte</span>
    </label>
    </div>
    </div>

    <!-- Número de Identificación del Solicitante -->
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Número de Identificación <span class="text-red-500">*</span>
    </label>
    <input type="text" name="solicitanteNumeroId" value="<?= htmlspecialchars($clienteData['cedula']) ?>" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="Ej: 1-2345-6789" required>
    </div>

    <!-- Nombre Completo del Solicitante -->
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Nombre Completo <span class="text-red-500">*</span>
    </label>
    <input type="text" name="solicitanteNombre" value="<?= htmlspecialchars($clienteData['nombre']) ?>" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="Nombre completo del solicitante" required>
    </div>

    <!-- Teléfono y Correo del Solicitante -->
    <div class="grid md:grid-cols-2 gap-4">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Teléfono Celular <span class="text-red-500">*</span>
    </label>
    <input type="tel" name="solicitanteTelefono" value="<?= htmlspecialchars($clienteData['telefono']) ?>" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="8888-8888" required>
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Correo Electrónico <span class="text-red-500">*</span>
    </label>
    <input type="email" name="solicitanteCorreo" value="<?= htmlspecialchars($clienteData['correo']) ?>" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="correo@ejemplo.com" required>
    <p class="text-xs text-gray-500 mt-1">A este correo se enviará la cotización</p>
    </div>
    </div>

    <!-- ¿Es usted el patrono? -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
    <label class="flex items-center space-x-3 cursor-pointer">
    <input type="checkbox" name="solicitanteEsPatrono" id="solicitanteEsPatrono" value="si" class="checkbox-custom">
    <div>
    <span class="font-semibold text-gray-800">Soy el patrono / representante legal</span>
    <p class="text-xs text-gray-500">Marque si usted es el patrono o representante de la empresa</p>
    </div>
    </label>
    </div>
    </div>

    <!-- Separador -->
    <hr class="border-gray-300 my-8">

    <!-- Sección: Datos del Patrono -->
    <div class="mb-8">
    <h2 class="text-3xl font-bold text-gray-800 mb-2">
    <i class="fas fa-building text-purple-600 mr-3"></i>Datos del Patrono / Empresa
    </h2>
    <p class="text-gray-600">Información de la empresa o patrono a asegurar</p>
    </div>

    <div class="space-y-6">
    <!-- Tipo de Persona -->
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Tipo de Persona <span class="text-red-500">*</span>
    </label>
    <div class="grid grid-cols-2 gap-4">
    <label class="flex items-center space-x-3 p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-purple-500 transition">
    <input type="radio" name="tipoPersona" value="juridica" class="radio-custom" required>
    <div>
    <div class="font-semibold text-gray-800">Persona Jurídica</div>
    <div class="text-xs text-gray-500">Empresa, sociedad, asociación</div>
    </div>
    </label>
    <label class="flex items-center space-x-3 p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-purple-500 transition">
    <input type="radio" name="tipoPersona" value="fisica" class="radio-custom">
    <div>
    <div class="font-semibold text-gray-800">Persona Física</div>
    <div class="text-xs text-gray-500">Patrono individual</div>
    </div>
    </label>
    </div>
    </div>

    <!-- Número de Identificación -->
    <div class="grid md:grid-cols-2 gap-4">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Cédula Jurídica / Cédula Física <span class="text-red-500">*</span>
    </label>
    <input type="text" name="numeroId" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="Ej: 3-101-123456" required>
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Número Patronal CCSS
    </label>
    <input type="text" name="numeroPatronal" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="Ej: 2-03101234567-001-001">
    <p class="text-xs text-gray-500 mt-1">Si ya está inscrito en la CCSS</p>
    </div>
    </div>

    <!-- Nombre / Razón Social -->
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Razón Social / Nombre Completo <span class="text-red-500">*</span>
    </label>
    <input type="text" name="razonSocial" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="Nombre de la empresa o persona física" required>
    </div>

    <!-- Nombre Comercial -->
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Nombre Comercial (si aplica)
    </label>
    <input type="text" name="nombreComercial" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="Nombre con el que opera el negocio">
    </div>

    <!-- Representante Legal -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
    <h3 class="font-semibold text-gray-800 mb-3 flex items-center">
    <i class="fas fa-user-tie text-blue-600 mr-2"></i>Representante Legal
    </h3>
    <div class="grid md:grid-cols-2 gap-4">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Nombre del Representante <span class="text-red-500">*</span>
    </label>
    <input type="text" name="representanteLegal" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="Nombre completo" required>
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Cédula del Representante <span class="text-red-500">*</span>
    </label>
    <input type="text" name="cedulaRepresentante" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="1-2345-6789" required>
    </div>
    </div>
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
    Dirección Exacta del Centro de Trabajo <span class="text-red-500">*</span>
    </label>
    <textarea name="direccion" rows="3" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="Dirección completa donde se desarrollan las labores..." required></textarea>
    </div>

    <!-- Contacto -->
    <div class="grid md:grid-cols-3 gap-4">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Teléfono Principal <span class="text-red-500">*</span>
    </label>
    <input type="tel" name="telefonoPrincipal" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="2222-2222" required>
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Teléfono Celular
    </label>
    <input type="tel" name="telefonoCelular" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="8888-8888">
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Fax
    </label>
    <input type="tel" name="fax" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="2222-2222">
    </div>
    </div>

    <!-- Correo -->
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Correo Electrónico <span class="text-red-500">*</span>
    </label>
    <input type="email" name="correo" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="correo@empresa.com" required>
    </div>
    </div>
    </div>

    <!-- PASO 2: Actividad Económica -->
    <div class="form-section" data-step="2">
    <div class="mb-8">
    <h2 class="text-3xl font-bold text-gray-800 mb-2">
    <i class="fas fa-industry text-purple-600 mr-3"></i>Actividad Económica
    </h2>
    <p class="text-gray-600">Descripción de las actividades de la empresa</p>
    </div>

    <div class="space-y-6">
    <!-- Actividad Principal -->
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Actividad Económica Principal <span class="text-red-500">*</span>
    </label>
    <select name="actividadPrincipal" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" required>
    <option value="">Seleccione la actividad...</option>
    <optgroup label="Agricultura y Ganadería">
    <option value="agricultura">Agricultura en general</option>
    <option value="ganaderia">Ganadería</option>
    <option value="avicultura">Avicultura</option>
    <option value="pesca">Pesca</option>
    </optgroup>
    <optgroup label="Industria y Manufactura">
    <option value="manufactura">Manufactura en general</option>
    <option value="alimentos">Procesamiento de alimentos</option>
    <option value="textil">Industria textil</option>
    <option value="metalmecanica">Metalmecánica</option>
    <option value="construccion">Construcción</option>
    </optgroup>
    <optgroup label="Comercio">
    <option value="comercio-mayorista">Comercio mayorista</option>
    <option value="comercio-minorista">Comercio minorista</option>
    <option value="supermercado">Supermercado / Pulpería</option>
    <option value="ferreteria">Ferretería</option>
    </optgroup>
    <optgroup label="Servicios">
    <option value="restaurante">Restaurante / Soda</option>
    <option value="hotel">Hotel / Hospedaje</option>
    <option value="transporte">Transporte</option>
    <option value="limpieza">Servicios de limpieza</option>
    <option value="seguridad">Servicios de seguridad</option>
    <option value="salud">Servicios de salud</option>
    <option value="educacion">Educación</option>
    <option value="profesionales">Servicios profesionales</option>
    <option value="tecnologia">Tecnología / Informática</option>
    <option value="call-center">Call Center</option>
    </optgroup>
    <optgroup label="Otros">
    <option value="otro">Otra actividad</option>
    </optgroup>
    </select>
    </div>

    <!-- Descripción detallada -->
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Descripción Detallada de la Actividad <span class="text-red-500">*</span>
    </label>
    <textarea name="descripcionActividad" rows="4" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="Describa en detalle las actividades que realiza la empresa, procesos, maquinaria utilizada, etc." required></textarea>
    </div>

    <!-- Riesgos específicos -->
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-3">
    Riesgos Específicos del Trabajo (seleccione todos los que apliquen)
    </label>
    <div class="grid md:grid-cols-2 gap-3">
    <label class="flex items-center space-x-2 p-3 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-purple-500 transition">
    <input type="checkbox" name="riesgos[]" value="maquinaria" class="checkbox-custom">
    <span class="text-sm">Uso de maquinaria pesada</span>
    </label>
    <label class="flex items-center space-x-2 p-3 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-purple-500 transition">
    <input type="checkbox" name="riesgos[]" value="altura" class="checkbox-custom">
    <span class="text-sm">Trabajo en alturas</span>
    </label>
    <label class="flex items-center space-x-2 p-3 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-purple-500 transition">
    <input type="checkbox" name="riesgos[]" value="quimicos" class="checkbox-custom">
    <span class="text-sm">Manejo de químicos</span>
    </label>
    <label class="flex items-center space-x-2 p-3 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-purple-500 transition">
    <input type="checkbox" name="riesgos[]" value="electrico" class="checkbox-custom">
    <span class="text-sm">Riesgo eléctrico</span>
    </label>
    <label class="flex items-center space-x-2 p-3 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-purple-500 transition">
    <input type="checkbox" name="riesgos[]" value="vehiculos" class="checkbox-custom">
    <span class="text-sm">Conducción de vehículos</span>
    </label>
    <label class="flex items-center space-x-2 p-3 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-purple-500 transition">
    <input type="checkbox" name="riesgos[]" value="biologico" class="checkbox-custom">
    <span class="text-sm">Riesgo biológico</span>
    </label>
    <label class="flex items-center space-x-2 p-3 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-purple-500 transition">
    <input type="checkbox" name="riesgos[]" value="ergonomico" class="checkbox-custom">
    <span class="text-sm">Riesgo ergonómico</span>
    </label>
    <label class="flex items-center space-x-2 p-3 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-purple-500 transition">
    <input type="checkbox" name="riesgos[]" value="ruido" class="checkbox-custom">
    <span class="text-sm">Exposición a ruido</span>
    </label>
    </div>
    </div>

    <!-- Otros riesgos -->
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Otros Riesgos (especifique)
    </label>
    <textarea name="otrosRiesgos" rows="2" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="Describa otros riesgos no mencionados anteriormente..."></textarea>
    </div>

    <!-- Horario de trabajo -->
    <div class="grid md:grid-cols-2 gap-4">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Horario de Trabajo
    </label>
    <select name="horarioTrabajo" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    <option value="">Seleccione...</option>
    <option value="diurno">Diurno (6am - 6pm)</option>
    <option value="nocturno">Nocturno (6pm - 6am)</option>
    <option value="mixto">Mixto</option>
    <option value="turnos">Por turnos rotativos</option>
    <option value="24horas">24 horas</option>
    </select>
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Días de Operación
    </label>
    <select name="diasOperacion" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    <option value="">Seleccione...</option>
    <option value="lunes-viernes">Lunes a Viernes</option>
    <option value="lunes-sabado">Lunes a Sábado</option>
    <option value="todos">Todos los días</option>
    </select>
    </div>
    </div>
    </div>
    </div>

    <!-- PASO 3: Información de Planilla -->
    <div class="form-section" data-step="3">
    <div class="mb-8">
    <h2 class="text-3xl font-bold text-gray-800 mb-2">
    <i class="fas fa-users text-purple-600 mr-3"></i>Información de Planilla
    </h2>
    <p class="text-gray-600">Datos de los trabajadores y salarios</p>
    </div>

    <div class="space-y-6">
    <!-- Cantidad de Trabajadores -->
    <div class="bg-purple-50 border-2 border-purple-200 rounded-lg p-6">
    <h3 class="text-lg font-bold text-gray-800 mb-4">Cantidad de Trabajadores</h3>
    <div class="grid md:grid-cols-3 gap-4">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Trabajadores Permanentes <span class="text-red-500">*</span>
    </label>
    <input type="number" name="trabajadoresPermanentes" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="0" min="0" required>
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Trabajadores Temporales
    </label>
    <input type="number" name="trabajadoresTemporales" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="0" min="0">
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Total de Trabajadores <span class="text-red-500">*</span>
    </label>
    <input type="number" name="totalTrabajadores" id="totalTrabajadores" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none bg-gray-100" placeholder="0" min="1" required readonly>
    </div>
    </div>
    </div>

    <!-- Desglose por tipo -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
    <h3 class="text-lg font-bold text-gray-800 mb-4">Desglose por Tipo de Trabajador</h3>
    <div class="grid md:grid-cols-2 gap-4">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Administrativos / Oficina
    </label>
    <input type="number" name="trabajadoresAdmin" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="0" min="0">
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Operativos / Producción
    </label>
    <input type="number" name="trabajadoresOperativos" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="0" min="0">
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Conductores / Transporte
    </label>
    <input type="number" name="trabajadoresConductores" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="0" min="0">
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Vendedores / Externos
    </label>
    <input type="number" name="trabajadoresVendedores" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="0" min="0">
    </div>
    </div>
    </div>

    <!-- Planilla Mensual -->
    <div class="bg-green-50 border border-green-200 rounded-lg p-6">
    <h3 class="text-lg font-bold text-gray-800 mb-4">Planilla Mensual</h3>
    <div class="grid md:grid-cols-2 gap-4">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Monto Total de Planilla Mensual <span class="text-red-500">*</span>
    </label>
    <div class="relative">
    <span class="absolute left-4 top-3 text-gray-500">₡</span>
    <input type="number" name="planillaMensual" class="input-field w-full pl-10 pr-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="Ej: 5000000" min="0" required>
    </div>
    <p class="text-xs text-gray-500 mt-1">Suma de todos los salarios brutos mensuales</p>
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Planilla Anual Estimada <span class="text-red-500">*</span>
    </label>
    <div class="relative">
    <span class="absolute left-4 top-3 text-gray-500">₡</span>
    <input type="number" name="planillaAnual" id="planillaAnual" class="input-field w-full pl-10 pr-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none bg-gray-100" placeholder="0" min="0" required readonly>
    </div>
    <p class="text-xs text-gray-500 mt-1">Se calcula automáticamente (12 meses)</p>
    </div>
    </div>
    </div>

    <!-- Observaciones de planilla -->
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Observaciones sobre la Planilla
    </label>
    <textarea name="observacionesPlanilla" rows="3" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="Indique si hay variaciones estacionales, contrataciones temporales frecuentes, etc."></textarea>
    </div>
    </div>
    </div>

    <!-- PASO 4: Coberturas y Plan de Pago -->
    <div class="form-section" data-step="4">
    <div class="mb-8">
    <h2 class="text-3xl font-bold text-gray-800 mb-2">
    <i class="fas fa-shield-alt text-purple-600 mr-3"></i>Coberturas y Plan de Pago
    </h2>
    <p class="text-gray-600">Información sobre la póliza</p>
    </div>

    <div class="space-y-6">
    <!-- Info importante -->
    <div class="bg-yellow-50 border border-yellow-300 rounded-lg p-4">
    <div class="flex items-start">
    <i class="fas fa-info-circle text-yellow-600 mt-1 mr-3"></i>
    <div>
    <h4 class="font-semibold text-gray-800 mb-1">Información Importante</h4>
    <p class="text-sm text-gray-600">
    El Seguro de Riesgos del Trabajo es <strong>obligatorio por ley</strong> para todo patrono en Costa Rica.
    La póliza cubre accidentes laborales y enfermedades profesionales de todos los trabajadores.
    </p>
    </div>
    </div>
    </div>

    <!-- Tipo de Póliza -->
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-3">
    Tipo de Póliza <span class="text-red-500">*</span>
    </label>
    <div class="grid md:grid-cols-2 gap-4">
    <label class="flex items-start space-x-3 p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-purple-500 transition">
    <input type="radio" name="tipoPoliza" value="nueva" class="radio-custom mt-1" required>
    <div>
    <div class="font-semibold text-gray-800">Póliza Nueva</div>
    <div class="text-xs text-gray-500">Primera vez que asegura a sus trabajadores</div>
    </div>
    </label>
    <label class="flex items-start space-x-3 p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-purple-500 transition">
    <input type="radio" name="tipoPoliza" value="traslado" class="radio-custom mt-1">
    <div>
    <div class="font-semibold text-gray-800">Traslado de Otra Aseguradora</div>
    <div class="text-xs text-gray-500">Ya tiene póliza y desea trasladarla</div>
    </div>
    </label>
    </div>
    </div>

    <!-- Datos de póliza anterior (si aplica) -->
    <div id="datos-poliza-anterior" style="display: none;">
    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
    <h4 class="font-semibold text-gray-800 mb-3">Datos de Póliza Anterior</h4>
    <div class="grid md:grid-cols-2 gap-4">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Aseguradora Actual
    </label>
    <input type="text" name="aseguradoraActual" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="Nombre de la aseguradora">
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Número de Póliza Actual
    </label>
    <input type="text" name="polizaActual" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="Número de póliza">
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Fecha de Vencimiento
    </label>
    <input type="date" name="vencimientoPoliza" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Prima Actual Anual
    </label>
    <div class="relative">
    <span class="absolute left-4 top-3 text-gray-500">₡</span>
    <input type="number" name="primaActual" class="input-field w-full pl-10 pr-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="Monto">
    </div>
    </div>
    </div>
    </div>
    </div>

    <!-- Plan de Pago -->
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Plan de Pago Preferido <span class="text-red-500">*</span>
    </label>
    <select name="planPago" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" required>
    <option value="">Seleccione...</option>
    <option value="anual">Anual (mejor precio)</option>
    <option value="semestral">Semestral</option>
    <option value="trimestral">Trimestral</option>
    <option value="mensual">Mensual</option>
    </select>
    </div>

    <!-- Fecha de inicio deseada -->
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Fecha de Inicio Deseada
    </label>
    <input type="date" name="fechaInicio" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    <p class="text-xs text-gray-500 mt-1">Deje en blanco para inicio inmediato</p>
    </div>

    <!-- Observaciones adicionales -->
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Comentarios o Solicitudes Especiales
    </label>
    <textarea name="comentarios" rows="3" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="Indique cualquier información adicional relevante..."></textarea>
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
    <!-- El resumen se generará dinámicamente -->
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
    <strong>Declaro que la información proporcionada es verídica y completa.</strong> Entiendo que cualquier omisión o falsa declaración puede resultar en la terminación del contrato o la denegación de reclamos.
    </div>
    </label>
    </div>

    <div class="bg-blue-50 border border-blue-300 rounded-lg p-4">
    <label class="flex items-start space-x-3 cursor-pointer">
    <input type="checkbox" name="consentimientoGrabacion" class="checkbox-custom mt-1" required>
    <div class="text-sm text-gray-700">
    <strong>Consiento expresamente</strong> que el Instituto Nacional de Seguros grabe y utilice las llamadas telefónicas realizadas a sus líneas de servicio como prueba para procesos administrativos y judiciales.
    </div>
    </label>
    </div>

    <div class="bg-green-50 border border-green-300 rounded-lg p-4">
    <label class="flex items-start space-x-3 cursor-pointer">
    <input type="checkbox" name="consentimientoDatos" class="checkbox-custom mt-1" required>
    <div class="text-sm text-gray-700">
    <strong>Autorizo al INS</strong> a incluir mi información en una base de datos bajo su responsabilidad, con las medidas de seguridad adecuadas, para la ejecución del contrato y para ofrecer productos o servicios relacionados.
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
    Hemos recibido tu solicitud de Seguro de Riesgos del Trabajo. Un agente se pondrá en contacto contigo en las próximas 24 horas.
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

    <!-- Calculations and Toggles -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Calculate total workers
      const permInput = document.querySelector('input[name="trabajadoresPermanentes"]');
      const tempInput = document.querySelector('input[name="trabajadoresTemporales"]');
      const totalInput = document.getElementById('totalTrabajadores');

      function updateTotal() {
        const perm = parseInt(permInput.value) || 0;
        const temp = parseInt(tempInput.value) || 0;
        totalInput.value = perm + temp;
      }

      if (permInput) permInput.addEventListener('input', updateTotal);
      if (tempInput) tempInput.addEventListener('input', updateTotal);

      // Calculate annual payroll
      const mensualInput = document.querySelector('input[name="planillaMensual"]');
      const anualInput = document.getElementById('planillaAnual');

      function updateAnual() {
        const mensual = parseFloat(mensualInput.value) || 0;
        anualInput.value = Math.round(mensual * 12);
      }

      if (mensualInput) mensualInput.addEventListener('input', updateAnual);

      // Toggle previous policy details
      const tipoPolizaRadios = document.querySelectorAll('input[name="tipoPoliza"]');
      const datosPolizaAnterior = document.getElementById('datos-poliza-anterior');

      tipoPolizaRadios.forEach(r => {
        r.addEventListener('change', function() {
          datosPolizaAnterior.style.display = this.value === 'traslado' ? 'block' : 'none';
        });
      });
    });
    </script>

    <!-- Summary Generator -->
    <script>
    const fieldLabels = {
      // Datos del Solicitante
      solicitanteTipoId: 'Tipo de Identificación',
      solicitanteNumeroId: 'Número de Identificación',
      solicitanteNombre: 'Nombre del Solicitante',
      solicitanteTelefono: 'Teléfono del Solicitante',
      solicitanteCorreo: 'Correo del Solicitante',
      solicitanteEsPatrono: 'Es el Patrono',

      // Datos del Patrono
      tipoPersona: 'Tipo de Persona',
      numeroId: 'Cédula',
      numeroPatronal: 'Número Patronal',
      razonSocial: 'Razón Social',
      nombreComercial: 'Nombre Comercial',
      representanteLegal: 'Representante Legal',
      cedulaRepresentante: 'Cédula Representante',
      provincia: 'Provincia',
      canton: 'Cantón',
      distrito: 'Distrito',
      direccion: 'Dirección',
      telefonoPrincipal: 'Teléfono',
      telefonoCelular: 'Celular',
      correo: 'Correo',

      actividadPrincipal: 'Actividad Principal',
      descripcionActividad: 'Descripción',
      riesgos: 'Riesgos',
      horarioTrabajo: 'Horario',
      diasOperacion: 'Días de Operación',

      trabajadoresPermanentes: 'Trabajadores Permanentes',
      trabajadoresTemporales: 'Trabajadores Temporales',
      totalTrabajadores: 'Total Trabajadores',
      planillaMensual: 'Planilla Mensual',
      planillaAnual: 'Planilla Anual',

      tipoPoliza: 'Tipo de Póliza',
      planPago: 'Plan de Pago',
      fechaInicio: 'Fecha de Inicio',

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

    function formatCurrency(value) {
      if (!value) return '';
      const num = Number(value);
      if (isNaN(num)) return value;
      return '₡ ' + Math.round(num).toLocaleString();
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

      // Datos del Solicitante
      const solicitanteKeys = ['solicitanteTipoId','solicitanteNumeroId','solicitanteNombre','solicitanteTelefono','solicitanteCorreo'];
      const solicitante = solicitanteKeys.map(k => ({ label: fieldLabels[k] || k, value: readFieldValue(form,k) }));
      // Agregar si es patrono
      const esPatrono = readFieldValue(form, 'solicitanteEsPatrono');
      solicitante.push({ label: fieldLabels['solicitanteEsPatrono'], value: esPatrono ? 'Sí' : 'No' });

      const patronoKeys = ['tipoPersona','numeroId','numeroPatronal','razonSocial','nombreComercial','representanteLegal','cedulaRepresentante','provincia','canton','distrito','direccion','telefonoPrincipal','telefonoCelular','correo'];
      const actividadKeys = ['actividadPrincipal','descripcionActividad','riesgos','horarioTrabajo','diasOperacion'];
      const planillaKeys = ['trabajadoresPermanentes','trabajadoresTemporales','totalTrabajadores'];

      const patrono = patronoKeys.map(k => ({ label: fieldLabels[k] || k, value: readFieldValue(form,k) }));
      const actividad = actividadKeys.map(k => ({ label: fieldLabels[k] || k, value: readFieldValue(form,k) }));
      const planilla = [
        ...planillaKeys.map(k => ({ label: fieldLabels[k] || k, value: readFieldValue(form,k) })),
        { label: 'Planilla Mensual', value: formatCurrency(readFieldValue(form, 'planillaMensual')) },
        { label: 'Planilla Anual', value: formatCurrency(readFieldValue(form, 'planillaAnual')) }
      ];
      const poliza = [
        { label: 'Tipo de Póliza', value: readFieldValue(form, 'tipoPoliza') },
        { label: 'Plan de Pago', value: readFieldValue(form, 'planPago') },
        { label: 'Fecha de Inicio', value: readFieldValue(form, 'fechaInicio') }
      ];
      const consentimientos = ['consentimientoInfo','consentimientoGrabacion','consentimientoDatos'].map(k => ({
        label: fieldLabels[k] || k,
        value: readFieldValue(form,k) ? 'Aceptado' : 'No aceptado'
      }));

      const sections = [
        { title: 'Datos del Solicitante', rows: solicitante },
        { title: 'Datos del Patrono / Empresa', rows: patrono },
        { title: 'Actividad Económica', rows: actividad },
        { title: 'Información de Planilla', rows: planilla },
        { title: 'Datos de la Póliza', rows: poliza },
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

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-6 mt-12">
        <div class="container mx-auto px-4 text-center text-gray-400 text-sm">
            <p>&copy; 2025 - AGENTE INS AUTORIZADO: 110886. Todos los derechos reservados.</p>
            <div class="mt-3 space-x-4">
                <a href="/privacidad.php" class="hover:text-white transition">Política de Privacidad</a>
                <span>|</span>
                <a href="/terminos.php" class="hover:text-white transition">Términos y Condiciones</a>
            </div>
        </div>
    </footer>

</body>
</html>
