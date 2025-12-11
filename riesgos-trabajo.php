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
    <title>Seguro Riesgos del Trabajo Costa Rica | Póliza RT INS | AseguraloCR</title>
    <meta name="description" content="Cotiza seguro de riesgos del trabajo (RT) INS en Costa Rica. Protege a tus empleados, cumple la ley.">
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
    <div class="flex-1 flex items-center" id="step-indicators"></div>
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

    <!-- ========================================== -->
    <!-- PASO 1: Datos del Tomador -->
    <!-- ========================================== -->
    <div class="form-section active" data-step="1">
    <div class="mb-8">
    <h2 class="text-3xl font-bold text-gray-800 mb-2">
    <i class="fas fa-user text-purple-600 mr-3"></i>Datos de la Persona Tomadora del Seguro
    </h2>
    <p class="text-gray-600">Información del patrono o empresa</p>
    </div>

    <div class="space-y-6">
    <!-- Tipo de Trámite -->
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
    <h3 class="font-semibold text-gray-800 mb-3">Tipo de Trámite</h3>
    <div class="flex flex-wrap gap-4">
    <label class="flex items-center space-x-2"><input type="checkbox" name="cb_tramite_emision" value="1" class="checkbox-custom" checked><span>Emisión</span></label>
    <label class="flex items-center space-x-2"><input type="checkbox" name="cb_tramite_rehabilitacion" value="1" class="checkbox-custom"><span>Rehabilitación</span></label>
    </div>
    </div>

    <!-- Tipo de Identificación -->
    <div class="bg-purple-50 border border-purple-200 rounded-lg p-6">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Tipo de Identificación</h3>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
    <label class="flex items-center space-x-2 p-3 border rounded cursor-pointer hover:bg-purple-100">
    <input type="checkbox" name="cb_tomador_cedula_juridica" value="1" class="checkbox-custom"><span>Cédula Jurídica</span>
    </label>
    <label class="flex items-center space-x-2 p-3 border rounded cursor-pointer hover:bg-purple-100">
    <input type="checkbox" name="cb_tomador_cedula_fisica" value="1" class="checkbox-custom" checked><span>Cédula Física</span>
    </label>
    <label class="flex items-center space-x-2 p-3 border rounded cursor-pointer hover:bg-purple-100">
    <input type="checkbox" name="cb_tomador_dimex_didi" value="1" class="checkbox-custom"><span>DIMEX/DIDI</span>
    </label>
    <label class="flex items-center space-x-2 p-3 border rounded cursor-pointer hover:bg-purple-100">
    <input type="checkbox" name="cb_tomador_pasaporte" value="1" class="checkbox-custom"><span>Pasaporte</span>
    </label>
    </div>

    <div class="grid md:grid-cols-2 gap-4 mb-4">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Número de Identificación <span class="text-red-500">*</span></label>
    <input type="text" name="tomador_num_identificacion" value="<?= htmlspecialchars($clienteData['cedula']) ?>" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" required>
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Nacionalidad</label>
    <input type="text" name="tomador_nacionalidad" value="Costarricense" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    </div>
    </div>

    <div class="mb-4">
    <label class="block text-sm font-semibold text-gray-700 mb-2">Nombre o Razón Social <span class="text-red-500">*</span></label>
    <input type="text" name="tomador_nombre_razon_social" value="<?= htmlspecialchars($clienteData['nombre']) ?>" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" required>
    </div>

    <div class="grid md:grid-cols-4 gap-4 mb-4">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">F. Nacimiento/Constitución - Día</label>
    <input type="text" name="tomador_nacimiento_dd" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="DD" maxlength="2">
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Mes</label>
    <input type="text" name="tomador_nacimiento_mm" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="MM" maxlength="2">
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Año</label>
    <input type="text" name="tomador_nacimiento_aaaa" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="AAAA" maxlength="4">
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Género (si PF)</label>
    <div class="flex gap-3 mt-2">
    <label class="flex items-center space-x-2"><input type="checkbox" name="cb_tomador_femenino" value="1" class="checkbox-custom"><span>F</span></label>
    <label class="flex items-center space-x-2"><input type="checkbox" name="cb_tomador_masculino" value="1" class="checkbox-custom"><span>M</span></label>
    </div>
    </div>
    </div>

    <div class="mb-4">
    <label class="block text-sm font-semibold text-gray-700 mb-2">Profesión u Ocupación</label>
    <input type="text" name="tomador_profesion_ocupacion" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    </div>

    <div class="mb-4">
    <label class="block text-sm font-semibold text-gray-700 mb-2">Domicilio Físico (por señas)</label>
    <textarea name="tomador_domicilio_senas" rows="2" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none"></textarea>
    </div>

    <div class="grid md:grid-cols-4 gap-4 mb-4">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Provincia</label>
    <select name="tomador_provincia" data-geo-group="tomador" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    <option value="">-- Seleccione --</option>
    </select>
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Cantón</label>
    <select name="tomador_canton" data-geo-canton="tomador" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    <option value="">-- Seleccione --</option>
    </select>
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Distrito</label>
    <select name="tomador_distrito" data-geo-distrito="tomador" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    <option value="">-- Seleccione --</option>
    </select>
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Apartado Postal</label>
    <input type="text" name="tomador_apartado_postal" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    </div>
    </div>

    <div class="grid md:grid-cols-4 gap-4 mb-4">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Fax</label>
    <input type="text" name="tomador_fax" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Tel. Celular <span class="text-red-500">*</span></label>
    <input type="tel" name="tomador_tel_celular" value="<?= htmlspecialchars($clienteData['telefono']) ?>" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" required>
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Tel. Domicilio</label>
    <input type="tel" name="tomador_tel_domicilio" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Tel. Oficina</label>
    <input type="tel" name="tomador_tel_oficina" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    </div>
    </div>

    <div class="mb-4">
    <label class="block text-sm font-semibold text-gray-700 mb-2">Correo Electrónico <span class="text-red-500">*</span></label>
    <input type="email" name="tomador_correo" value="<?= htmlspecialchars($clienteData['correo']) ?>" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" required>
    </div>
    </div>

    <!-- Medio de Notificación -->
    <div class="bg-green-50 border border-green-200 rounded-lg p-6">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Medio de Notificación Preferido</h3>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
    <label class="flex items-center space-x-2 p-3 border rounded cursor-pointer hover:bg-green-100">
    <input type="checkbox" name="cb_notif_correo" value="1" class="checkbox-custom" checked><span>Correo Electrónico</span>
    </label>
    <label class="flex items-center space-x-2 p-3 border rounded cursor-pointer hover:bg-green-100">
    <input type="checkbox" name="cb_notif_fax" value="1" class="checkbox-custom"><span>Fax</span>
    </label>
    <label class="flex items-center space-x-2 p-3 border rounded cursor-pointer hover:bg-green-100">
    <input type="checkbox" name="cb_notif_apartado" value="1" class="checkbox-custom"><span>Apartado Postal</span>
    </label>
    <label class="flex items-center space-x-2 p-3 border rounded cursor-pointer hover:bg-green-100">
    <input type="checkbox" name="cb_notif_domicilio" value="1" class="checkbox-custom"><span>Domicilio Físico</span>
    </label>
    </div>
    </div>
    </div>
    </div>

    <!-- ========================================== -->
    <!-- PASO 2: Modalidad y Datos del Seguro -->
    <!-- ========================================== -->
    <div class="form-section" data-step="2">
    <div class="mb-8">
    <h2 class="text-3xl font-bold text-gray-800 mb-2">
    <i class="fas fa-clipboard-list text-purple-600 mr-3"></i>Modalidad de Aseguramiento
    </h2>
    <p class="text-gray-600">Seleccione el tipo de póliza y datos del trabajo</p>
    </div>

    <div class="space-y-6">
    <!-- Modalidades de Periodo Corto -->
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Pólizas de Periodo Corto</h3>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
    <label class="flex items-center space-x-2 p-3 border rounded cursor-pointer hover:bg-yellow-100">
    <input type="checkbox" name="cb_poliza_rt_construccion_corto" value="1" class="checkbox-custom"><span class="text-sm">RT-Construcción</span>
    </label>
    <label class="flex items-center space-x-2 p-3 border rounded cursor-pointer hover:bg-yellow-100">
    <input type="checkbox" name="cb_poliza_rt_cosechas_corto" value="1" class="checkbox-custom"><span class="text-sm">RT-Cosechas</span>
    </label>
    <label class="flex items-center space-x-2 p-3 border rounded cursor-pointer hover:bg-yellow-100">
    <input type="checkbox" name="cb_poliza_rt_general_corto" value="1" class="checkbox-custom"><span class="text-sm">RT-General</span>
    </label>
    <label class="flex items-center space-x-2 p-3 border rounded cursor-pointer hover:bg-yellow-100">
    <input type="checkbox" name="cb_poliza_rt_formacion_dual_corto" value="1" class="checkbox-custom"><span class="text-sm">RT-Form. Técnica Dual</span>
    </label>
    </div>
    </div>

    <!-- Modalidades Permanentes -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Pólizas Permanentes</h3>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
    <label class="flex items-center space-x-2 p-3 border rounded cursor-pointer hover:bg-blue-100">
    <input type="checkbox" name="cb_poliza_rt_adolescente" value="1" class="checkbox-custom"><span class="text-sm">RT-Adolescente</span>
    </label>
    <label class="flex items-center space-x-2 p-3 border rounded cursor-pointer hover:bg-blue-100">
    <input type="checkbox" name="cb_poliza_rt_agricola" value="1" class="checkbox-custom"><span class="text-sm">RT-Agrícola</span>
    </label>
    <label class="flex items-center space-x-2 p-3 border rounded cursor-pointer hover:bg-blue-100">
    <input type="checkbox" name="cb_poliza_rt_formacion_dual_perm" value="1" class="checkbox-custom"><span class="text-sm">RT-Form. Técnica Dual</span>
    </label>
    <label class="flex items-center space-x-2 p-3 border rounded cursor-pointer hover:bg-blue-100">
    <input type="checkbox" name="cb_poliza_rt_general_perm" value="1" class="checkbox-custom" checked><span class="text-sm">RT-General</span>
    </label>
    <label class="flex items-center space-x-2 p-3 border rounded cursor-pointer hover:bg-blue-100">
    <input type="checkbox" name="cb_poliza_rt_hogar" value="1" class="checkbox-custom"><span class="text-sm">RT-Hogar</span>
    </label>
    <label class="flex items-center space-x-2 p-3 border rounded cursor-pointer hover:bg-blue-100">
    <input type="checkbox" name="cb_poliza_rt_ocasional" value="1" class="checkbox-custom"><span class="text-sm">RT-Ocasional</span>
    </label>
    <label class="flex items-center space-x-2 p-3 border rounded cursor-pointer hover:bg-blue-100">
    <input type="checkbox" name="cb_poliza_rt_sector_publico" value="1" class="checkbox-custom"><span class="text-sm">RT-Sector Público</span>
    </label>
    </div>
    </div>

    <!-- Datos Generales del Seguro -->
    <div class="bg-purple-50 border border-purple-200 rounded-lg p-6">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Datos Generales del Seguro</h3>

    <div class="mb-4">
    <label class="block text-sm font-semibold text-gray-700 mb-2">Trabajo o Actividad Económica <span class="text-red-500">*</span></label>
    <input type="text" name="seguro_actividad_economica" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" required>
    </div>

    <div class="mb-4">
    <label class="block text-sm font-semibold text-gray-700 mb-2">Dirección donde se ejecutará el trabajo</label>
    <textarea name="seguro_direccion_trabajo" rows="2" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none"></textarea>
    </div>

    <div class="grid md:grid-cols-3 gap-4 mb-4">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Provincia</label>
    <select name="seguro_provincia" data-geo-group="seguro" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    <option value="">-- Seleccione --</option>
    </select>
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Cantón</label>
    <select name="seguro_canton" data-geo-canton="seguro" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    <option value="">-- Seleccione --</option>
    </select>
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Distrito</label>
    <select name="seguro_distrito" data-geo-distrito="seguro" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    <option value="">-- Seleccione --</option>
    </select>
    </div>
    </div>

    <div class="grid md:grid-cols-2 gap-4 mb-4">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Fecha de Ejecución - Inicia</label>
    <div class="grid grid-cols-3 gap-2">
    <input type="text" name="seguro_fecha_inicia_dd" class="input-field w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="DD" maxlength="2">
    <input type="text" name="seguro_fecha_inicia_mm" class="input-field w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="MM" maxlength="2">
    <input type="text" name="seguro_fecha_inicia_aaaa" class="input-field w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="AAAA" maxlength="4">
    </div>
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Fecha de Ejecución - Finaliza</label>
    <div class="grid grid-cols-3 gap-2">
    <input type="text" name="seguro_fecha_finaliza_dd" class="input-field w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="DD" maxlength="2">
    <input type="text" name="seguro_fecha_finaliza_mm" class="input-field w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="MM" maxlength="2">
    <input type="text" name="seguro_fecha_finaliza_aaaa" class="input-field w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="AAAA" maxlength="4">
    </div>
    </div>
    </div>
    </div>

    <!-- Calendario y Forma de Pago -->
    <div class="bg-green-50 border border-green-200 rounded-lg p-6">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Calendario y Forma de Pago</h3>
    <div class="grid md:grid-cols-2 gap-6">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-3">Tipo de Calendario de Planillas</label>
    <div class="space-y-2">
    <label class="flex items-center space-x-2"><input type="checkbox" name="cb_calendario_mensual" value="1" class="checkbox-custom" checked><span>Mensual</span></label>
    <label class="flex items-center space-x-2"><input type="checkbox" name="cb_calendario_especial" value="1" class="checkbox-custom"><span>Especial</span></label>
    <label class="flex items-center space-x-2"><input type="checkbox" name="cb_calendario_no_presenta" value="1" class="checkbox-custom"><span>No presenta</span></label>
    </div>
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-3">Forma de Pago de la Prima</label>
    <div class="space-y-2">
    <label class="flex items-center space-x-2"><input type="checkbox" name="cb_pago_anual" value="1" class="checkbox-custom"><span>Anual</span></label>
    <label class="flex items-center space-x-2"><input type="checkbox" name="cb_pago_semestral" value="1" class="checkbox-custom" checked><span>Semestral</span></label>
    <label class="flex items-center space-x-2"><input type="checkbox" name="cb_pago_trimestral" value="1" class="checkbox-custom"><span>Trimestral</span></label>
    <label class="flex items-center space-x-2"><input type="checkbox" name="cb_pago_mensual" value="1" class="checkbox-custom"><span>Mensual</span></label>
    </div>
    </div>
    </div>

    <div class="grid md:grid-cols-2 gap-4 mt-4">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Monto Estimado Planilla Mensual <span class="text-red-500">*</span></label>
    <input type="text" name="seguro_monto_planilla_mensual" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="₡" required>
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-3">¿Adjunta Planilla de Trabajadores?</label>
    <div class="flex gap-4">
    <label class="flex items-center space-x-2"><input type="checkbox" name="cb_adjunta_planilla_si" value="1" class="checkbox-custom"><span>Sí</span></label>
    <label class="flex items-center space-x-2"><input type="checkbox" name="cb_adjunta_planilla_no" value="1" class="checkbox-custom"><span>No</span></label>
    </div>
    </div>
    </div>
    </div>
    </div>
    </div>

    <!-- ========================================== -->
    <!-- PASO 3: Datos Específicos por Modalidad -->
    <!-- ========================================== -->
    <div class="form-section" data-step="3">
    <div class="mb-8">
    <h2 class="text-3xl font-bold text-gray-800 mb-2">
    <i class="fas fa-cogs text-purple-600 mr-3"></i>Datos Específicos por Modalidad
    </h2>
    <p class="text-gray-600">Complete según el tipo de póliza seleccionada</p>
    </div>

    <div class="space-y-6">
    <!-- RT-Construcción -->
    <div class="bg-orange-50 border border-orange-200 rounded-lg p-6">
    <h3 class="text-xl font-bold text-gray-800 mb-4">RT-Construcción (si aplica)</h3>
    <div class="grid md:grid-cols-2 gap-4 mb-4">
    <div>
    <label class="flex items-center space-x-2 mb-2"><input type="checkbox" name="cb_doc_permiso_municipal" value="1" class="checkbox-custom"><span>Permiso Municipal</span></label>
    <input type="text" name="construccion_permiso_municipal_no" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="N° de Permiso">
    </div>
    <div>
    <label class="flex items-center space-x-2 mb-2"><input type="checkbox" name="cb_doc_contrato_cfia" value="1" class="checkbox-custom"><span>Contrato CFIA</span></label>
    <input type="text" name="construccion_contrato_cfia_no" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="N° de Contrato">
    </div>
    </div>
    <div class="mb-4">
    <label class="flex items-center space-x-2"><input type="checkbox" name="cb_doc_copia_contrato" value="1" class="checkbox-custom"><span>Copia del contrato entre las Partes</span></label>
    </div>
    <div class="grid md:grid-cols-2 gap-4">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Declaración de Interés Social</label>
    <div class="flex gap-4">
    <label class="flex items-center space-x-2"><input type="checkbox" name="cb_interes_social_si" value="1" class="checkbox-custom"><span>Sí</span></label>
    <label class="flex items-center space-x-2"><input type="checkbox" name="cb_interes_social_no" value="1" class="checkbox-custom"><span>No</span></label>
    </div>
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Valor Total de la Obra</label>
    <input type="text" name="construccion_valor_obra" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="₡">
    </div>
    </div>
    </div>

    <!-- RT-Cosechas -->
    <div class="bg-green-50 border border-green-200 rounded-lg p-6">
    <h3 class="text-xl font-bold text-gray-800 mb-4">RT-Cosechas (si aplica)</h3>
    <div class="grid md:grid-cols-2 gap-4">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Fruto o Producto a Recolectar</label>
    <input type="text" name="cosechas_fruto_producto" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Unidad de Medida</label>
    <input type="text" name="cosechas_unidad_medida" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="Ej: Cajuelas, Kilos">
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Cantidad de Unidades a Recolectar</label>
    <input type="text" name="cosechas_cantidad_unidades" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Precio a Pagar por Unidad</label>
    <input type="text" name="cosechas_precio_unidad" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="₡">
    </div>
    </div>
    </div>

    <!-- RT-Hogar -->
    <div class="bg-purple-50 border border-purple-200 rounded-lg p-6">
    <h3 class="text-xl font-bold text-gray-800 mb-4">RT-Hogar (si aplica)</h3>
    <p class="text-sm text-gray-600 mb-4">Seleccione la opción según cantidad de trabajadores domésticos:</p>
    <div class="grid md:grid-cols-3 gap-4">
    <label class="flex items-center space-x-2 p-4 border rounded cursor-pointer hover:bg-purple-100">
    <input type="checkbox" name="cb_hogar_opcion1_un_trabajador" value="1" class="checkbox-custom"><span>Opción 1: Un trabajador</span>
    </label>
    <label class="flex items-center space-x-2 p-4 border rounded cursor-pointer hover:bg-purple-100">
    <input type="checkbox" name="cb_hogar_opcion2_dos_trabajadores" value="1" class="checkbox-custom"><span>Opción 2: Dos trabajadores</span>
    </label>
    <label class="flex items-center space-x-2 p-4 border rounded cursor-pointer hover:bg-purple-100">
    <input type="checkbox" name="cb_hogar_opcion3_tres_o_mas" value="1" class="checkbox-custom"><span>Opción 3: Tres o más</span>
    </label>
    </div>
    </div>
    </div>
    </div>

    <!-- ========================================== -->
    <!-- PASO 4: Planilla de Trabajadores -->
    <!-- ========================================== -->
    <div class="form-section" data-step="4">
    <div class="mb-8">
    <h2 class="text-3xl font-bold text-gray-800 mb-2">
    <i class="fas fa-users text-purple-600 mr-3"></i>Planilla de Trabajadores
    </h2>
    <p class="text-gray-600">Ingrese los datos de cada trabajador a asegurar</p>
    </div>

    <div class="space-y-6">
    <!-- Trabajador 1 -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
    <h3 class="text-lg font-bold text-gray-800 mb-4">Trabajador 1</h3>
    <div class="grid md:grid-cols-4 gap-3 mb-3">
    <div>
    <label class="block text-xs font-semibold text-gray-700 mb-1">Tipo ID (CN/DU/NP/NT)</label>
    <input type="text" name="trab1_tipo_id" class="input-field w-full px-3 py-2 border rounded text-sm" placeholder="CN">
    </div>
    <div>
    <label class="block text-xs font-semibold text-gray-700 mb-1">Nacionalidad</label>
    <input type="text" name="trab1_nacionalidad" class="input-field w-full px-3 py-2 border rounded text-sm" value="CR">
    </div>
    <div>
    <label class="block text-xs font-semibold text-gray-700 mb-1">N° Identificación</label>
    <input type="text" name="trab1_num_id" class="input-field w-full px-3 py-2 border rounded text-sm">
    </div>
    <div>
    <label class="block text-xs font-semibold text-gray-700 mb-1">Nombre</label>
    <input type="text" name="trab1_nombre" class="input-field w-full px-3 py-2 border rounded text-sm">
    </div>
    </div>
    <div class="grid md:grid-cols-4 gap-3 mb-3">
    <div>
    <label class="block text-xs font-semibold text-gray-700 mb-1">Primer Apellido</label>
    <input type="text" name="trab1_apellido1" class="input-field w-full px-3 py-2 border rounded text-sm">
    </div>
    <div>
    <label class="block text-xs font-semibold text-gray-700 mb-1">Segundo Apellido</label>
    <input type="text" name="trab1_apellido2" class="input-field w-full px-3 py-2 border rounded text-sm">
    </div>
    <div>
    <label class="block text-xs font-semibold text-gray-700 mb-1">F. Nacimiento</label>
    <input type="text" name="trab1_fecha_nacimiento" class="input-field w-full px-3 py-2 border rounded text-sm" placeholder="DD/MM/AAAA">
    </div>
    <div>
    <label class="block text-xs font-semibold text-gray-700 mb-1">Sexo (M/F)</label>
    <input type="text" name="trab1_sexo" class="input-field w-full px-3 py-2 border rounded text-sm" maxlength="1">
    </div>
    </div>
    <div class="grid md:grid-cols-5 gap-3">
    <div>
    <label class="block text-xs font-semibold text-gray-700 mb-1">Tipo Jornada</label>
    <input type="text" name="trab1_tipo_jornada" class="input-field w-full px-3 py-2 border rounded text-sm" placeholder="TC/TM/OD/OH">
    </div>
    <div>
    <label class="block text-xs font-semibold text-gray-700 mb-1">Salario Mensual</label>
    <input type="text" name="trab1_salario_mensual" class="input-field w-full px-3 py-2 border rounded text-sm">
    </div>
    <div>
    <label class="block text-xs font-semibold text-gray-700 mb-1">Días</label>
    <input type="text" name="trab1_dias" class="input-field w-full px-3 py-2 border rounded text-sm">
    </div>
    <div>
    <label class="block text-xs font-semibold text-gray-700 mb-1">Horas</label>
    <input type="text" name="trab1_horas" class="input-field w-full px-3 py-2 border rounded text-sm">
    </div>
    <div>
    <label class="block text-xs font-semibold text-gray-700 mb-1">Ocupación</label>
    <input type="text" name="trab1_ocupacion" class="input-field w-full px-3 py-2 border rounded text-sm">
    </div>
    </div>
    </div>

    <!-- Trabajador 2 -->
    <div class="bg-green-50 border border-green-200 rounded-lg p-6">
    <h3 class="text-lg font-bold text-gray-800 mb-4">Trabajador 2</h3>
    <div class="grid md:grid-cols-4 gap-3 mb-3">
    <div><label class="block text-xs font-semibold text-gray-700 mb-1">Tipo ID</label><input type="text" name="trab2_tipo_id" class="input-field w-full px-3 py-2 border rounded text-sm"></div>
    <div><label class="block text-xs font-semibold text-gray-700 mb-1">Nacionalidad</label><input type="text" name="trab2_nacionalidad" class="input-field w-full px-3 py-2 border rounded text-sm"></div>
    <div><label class="block text-xs font-semibold text-gray-700 mb-1">N° ID</label><input type="text" name="trab2_num_id" class="input-field w-full px-3 py-2 border rounded text-sm"></div>
    <div><label class="block text-xs font-semibold text-gray-700 mb-1">Nombre</label><input type="text" name="trab2_nombre" class="input-field w-full px-3 py-2 border rounded text-sm"></div>
    </div>
    <div class="grid md:grid-cols-4 gap-3 mb-3">
    <div><label class="block text-xs font-semibold text-gray-700 mb-1">Apellido 1</label><input type="text" name="trab2_apellido1" class="input-field w-full px-3 py-2 border rounded text-sm"></div>
    <div><label class="block text-xs font-semibold text-gray-700 mb-1">Apellido 2</label><input type="text" name="trab2_apellido2" class="input-field w-full px-3 py-2 border rounded text-sm"></div>
    <div><label class="block text-xs font-semibold text-gray-700 mb-1">F. Nac</label><input type="text" name="trab2_fecha_nacimiento" class="input-field w-full px-3 py-2 border rounded text-sm"></div>
    <div><label class="block text-xs font-semibold text-gray-700 mb-1">Sexo</label><input type="text" name="trab2_sexo" class="input-field w-full px-3 py-2 border rounded text-sm" maxlength="1"></div>
    </div>
    <div class="grid md:grid-cols-5 gap-3">
    <div><label class="block text-xs font-semibold text-gray-700 mb-1">Jornada</label><input type="text" name="trab2_tipo_jornada" class="input-field w-full px-3 py-2 border rounded text-sm"></div>
    <div><label class="block text-xs font-semibold text-gray-700 mb-1">Salario</label><input type="text" name="trab2_salario_mensual" class="input-field w-full px-3 py-2 border rounded text-sm"></div>
    <div><label class="block text-xs font-semibold text-gray-700 mb-1">Días</label><input type="text" name="trab2_dias" class="input-field w-full px-3 py-2 border rounded text-sm"></div>
    <div><label class="block text-xs font-semibold text-gray-700 mb-1">Horas</label><input type="text" name="trab2_horas" class="input-field w-full px-3 py-2 border rounded text-sm"></div>
    <div><label class="block text-xs font-semibold text-gray-700 mb-1">Ocupación</label><input type="text" name="trab2_ocupacion" class="input-field w-full px-3 py-2 border rounded text-sm"></div>
    </div>
    </div>

    <!-- Trabajador 3 -->
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
    <h3 class="text-lg font-bold text-gray-800 mb-4">Trabajador 3</h3>
    <div class="grid md:grid-cols-4 gap-3 mb-3">
    <div><label class="block text-xs font-semibold text-gray-700 mb-1">Tipo ID</label><input type="text" name="trab3_tipo_id" class="input-field w-full px-3 py-2 border rounded text-sm"></div>
    <div><label class="block text-xs font-semibold text-gray-700 mb-1">Nacionalidad</label><input type="text" name="trab3_nacionalidad" class="input-field w-full px-3 py-2 border rounded text-sm"></div>
    <div><label class="block text-xs font-semibold text-gray-700 mb-1">N° ID</label><input type="text" name="trab3_num_id" class="input-field w-full px-3 py-2 border rounded text-sm"></div>
    <div><label class="block text-xs font-semibold text-gray-700 mb-1">Nombre</label><input type="text" name="trab3_nombre" class="input-field w-full px-3 py-2 border rounded text-sm"></div>
    </div>
    <div class="grid md:grid-cols-4 gap-3 mb-3">
    <div><label class="block text-xs font-semibold text-gray-700 mb-1">Apellido 1</label><input type="text" name="trab3_apellido1" class="input-field w-full px-3 py-2 border rounded text-sm"></div>
    <div><label class="block text-xs font-semibold text-gray-700 mb-1">Apellido 2</label><input type="text" name="trab3_apellido2" class="input-field w-full px-3 py-2 border rounded text-sm"></div>
    <div><label class="block text-xs font-semibold text-gray-700 mb-1">F. Nac</label><input type="text" name="trab3_fecha_nacimiento" class="input-field w-full px-3 py-2 border rounded text-sm"></div>
    <div><label class="block text-xs font-semibold text-gray-700 mb-1">Sexo</label><input type="text" name="trab3_sexo" class="input-field w-full px-3 py-2 border rounded text-sm" maxlength="1"></div>
    </div>
    <div class="grid md:grid-cols-5 gap-3">
    <div><label class="block text-xs font-semibold text-gray-700 mb-1">Jornada</label><input type="text" name="trab3_tipo_jornada" class="input-field w-full px-3 py-2 border rounded text-sm"></div>
    <div><label class="block text-xs font-semibold text-gray-700 mb-1">Salario</label><input type="text" name="trab3_salario_mensual" class="input-field w-full px-3 py-2 border rounded text-sm"></div>
    <div><label class="block text-xs font-semibold text-gray-700 mb-1">Días</label><input type="text" name="trab3_dias" class="input-field w-full px-3 py-2 border rounded text-sm"></div>
    <div><label class="block text-xs font-semibold text-gray-700 mb-1">Horas</label><input type="text" name="trab3_horas" class="input-field w-full px-3 py-2 border rounded text-sm"></div>
    <div><label class="block text-xs font-semibold text-gray-700 mb-1">Ocupación</label><input type="text" name="trab3_ocupacion" class="input-field w-full px-3 py-2 border rounded text-sm"></div>
    </div>
    </div>

    <!-- Trabajadores 4-5 (colapsados) -->
    <details class="bg-gray-50 border border-gray-200 rounded-lg">
    <summary class="p-4 cursor-pointer font-semibold text-gray-700">Trabajadores 4 y 5 (clic para expandir)</summary>
    <div class="p-6 space-y-6">
    <!-- Trabajador 4 -->
    <div class="border-t pt-4">
    <h4 class="font-bold text-gray-700 mb-3">Trabajador 4</h4>
    <div class="grid md:grid-cols-6 gap-2 mb-2">
    <input type="text" name="trab4_tipo_id" class="input-field px-2 py-1 border rounded text-xs" placeholder="Tipo ID">
    <input type="text" name="trab4_nacionalidad" class="input-field px-2 py-1 border rounded text-xs" placeholder="Nac">
    <input type="text" name="trab4_num_id" class="input-field px-2 py-1 border rounded text-xs" placeholder="N° ID">
    <input type="text" name="trab4_nombre" class="input-field px-2 py-1 border rounded text-xs" placeholder="Nombre">
    <input type="text" name="trab4_apellido1" class="input-field px-2 py-1 border rounded text-xs" placeholder="Apellido 1">
    <input type="text" name="trab4_apellido2" class="input-field px-2 py-1 border rounded text-xs" placeholder="Apellido 2">
    </div>
    <div class="grid md:grid-cols-6 gap-2">
    <input type="text" name="trab4_fecha_nacimiento" class="input-field px-2 py-1 border rounded text-xs" placeholder="F. Nac">
    <input type="text" name="trab4_sexo" class="input-field px-2 py-1 border rounded text-xs" placeholder="M/F" maxlength="1">
    <input type="text" name="trab4_tipo_jornada" class="input-field px-2 py-1 border rounded text-xs" placeholder="Jornada">
    <input type="text" name="trab4_salario_mensual" class="input-field px-2 py-1 border rounded text-xs" placeholder="Salario">
    <input type="text" name="trab4_dias" class="input-field px-2 py-1 border rounded text-xs" placeholder="Días">
    <input type="text" name="trab4_horas" class="input-field px-2 py-1 border rounded text-xs" placeholder="Horas">
    </div>
    <input type="text" name="trab4_ocupacion" class="input-field w-full px-2 py-1 border rounded text-xs mt-2" placeholder="Ocupación">
    </div>
    <!-- Trabajador 5 -->
    <div class="border-t pt-4">
    <h4 class="font-bold text-gray-700 mb-3">Trabajador 5</h4>
    <div class="grid md:grid-cols-6 gap-2 mb-2">
    <input type="text" name="trab5_tipo_id" class="input-field px-2 py-1 border rounded text-xs" placeholder="Tipo ID">
    <input type="text" name="trab5_nacionalidad" class="input-field px-2 py-1 border rounded text-xs" placeholder="Nac">
    <input type="text" name="trab5_num_id" class="input-field px-2 py-1 border rounded text-xs" placeholder="N° ID">
    <input type="text" name="trab5_nombre" class="input-field px-2 py-1 border rounded text-xs" placeholder="Nombre">
    <input type="text" name="trab5_apellido1" class="input-field px-2 py-1 border rounded text-xs" placeholder="Apellido 1">
    <input type="text" name="trab5_apellido2" class="input-field px-2 py-1 border rounded text-xs" placeholder="Apellido 2">
    </div>
    <div class="grid md:grid-cols-6 gap-2">
    <input type="text" name="trab5_fecha_nacimiento" class="input-field px-2 py-1 border rounded text-xs" placeholder="F. Nac">
    <input type="text" name="trab5_sexo" class="input-field px-2 py-1 border rounded text-xs" placeholder="M/F" maxlength="1">
    <input type="text" name="trab5_tipo_jornada" class="input-field px-2 py-1 border rounded text-xs" placeholder="Jornada">
    <input type="text" name="trab5_salario_mensual" class="input-field px-2 py-1 border rounded text-xs" placeholder="Salario">
    <input type="text" name="trab5_dias" class="input-field px-2 py-1 border rounded text-xs" placeholder="Días">
    <input type="text" name="trab5_horas" class="input-field px-2 py-1 border rounded text-xs" placeholder="Horas">
    </div>
    <input type="text" name="trab5_ocupacion" class="input-field w-full px-2 py-1 border rounded text-xs mt-2" placeholder="Ocupación">
    </div>
    </div>
    </details>

    <!-- Trabajadores 6-10 (colapsados) -->
    <details class="bg-gray-50 border border-gray-200 rounded-lg">
    <summary class="p-4 cursor-pointer font-semibold text-gray-700">Trabajadores 6 a 10 (clic para expandir)</summary>
    <div class="p-6 space-y-4">
    <?php for($i = 6; $i <= 10; $i++): ?>
    <div class="border-b pb-3">
    <h4 class="font-bold text-gray-700 mb-2 text-sm">Trabajador <?= $i ?></h4>
    <div class="grid grid-cols-6 gap-1 mb-1">
    <input type="text" name="trab<?= $i ?>_tipo_id" class="input-field px-1 py-1 border rounded text-xs" placeholder="ID">
    <input type="text" name="trab<?= $i ?>_nacionalidad" class="input-field px-1 py-1 border rounded text-xs" placeholder="Nac">
    <input type="text" name="trab<?= $i ?>_num_id" class="input-field px-1 py-1 border rounded text-xs" placeholder="N°">
    <input type="text" name="trab<?= $i ?>_nombre" class="input-field px-1 py-1 border rounded text-xs" placeholder="Nom">
    <input type="text" name="trab<?= $i ?>_apellido1" class="input-field px-1 py-1 border rounded text-xs" placeholder="Ap1">
    <input type="text" name="trab<?= $i ?>_apellido2" class="input-field px-1 py-1 border rounded text-xs" placeholder="Ap2">
    </div>
    <div class="grid grid-cols-7 gap-1">
    <input type="text" name="trab<?= $i ?>_fecha_nacimiento" class="input-field px-1 py-1 border rounded text-xs" placeholder="Nac">
    <input type="text" name="trab<?= $i ?>_sexo" class="input-field px-1 py-1 border rounded text-xs" placeholder="S">
    <input type="text" name="trab<?= $i ?>_tipo_jornada" class="input-field px-1 py-1 border rounded text-xs" placeholder="Jor">
    <input type="text" name="trab<?= $i ?>_salario_mensual" class="input-field px-1 py-1 border rounded text-xs" placeholder="Sal">
    <input type="text" name="trab<?= $i ?>_dias" class="input-field px-1 py-1 border rounded text-xs" placeholder="D">
    <input type="text" name="trab<?= $i ?>_horas" class="input-field px-1 py-1 border rounded text-xs" placeholder="H">
    <input type="text" name="trab<?= $i ?>_ocupacion" class="input-field px-1 py-1 border rounded text-xs" placeholder="Ocup">
    </div>
    </div>
    <?php endfor; ?>
    </div>
    </details>
    </div>
    </div>

    <!-- ========================================== -->
    <!-- PASO 5: Firma y Confirmación -->
    <!-- ========================================== -->
    <div class="form-section" data-step="5">
    <div class="mb-8">
    <h2 class="text-3xl font-bold text-gray-800 mb-2">
    <i class="fas fa-check-circle text-green-600 mr-3"></i>Aceptación y Firma
    </h2>
    <p class="text-gray-600">Complete la firma y confirme la solicitud</p>
    </div>

    <div class="space-y-6">
    <!-- Firma del Tomador -->
    <div class="bg-purple-50 border border-purple-200 rounded-lg p-6">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Firma de la Persona Tomadora</h3>
    <div class="mb-4">
    <label class="block text-sm font-semibold text-gray-700 mb-2">Firma del Tomador (nombre completo)</label>
    <input type="text" name="firma_tomador" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    </div>
    </div>

    <!-- Representante Legal -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Representante Legal (si es Persona Jurídica)</h3>
    <div class="grid md:grid-cols-3 gap-4">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Nombre Completo</label>
    <input type="text" name="representante_nombre" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Identificación</label>
    <input type="text" name="representante_identificacion" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Puesto</label>
    <input type="text" name="representante_puesto" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    </div>
    </div>
    </div>

    <!-- Consentimientos -->
    <div class="space-y-4">
    <div class="bg-yellow-50 border border-yellow-300 rounded-lg p-4">
    <label class="flex items-start space-x-3 cursor-pointer">
    <input type="checkbox" name="consentimientoInfo" class="checkbox-custom mt-1" required>
    <div class="text-sm text-gray-700">
    <strong>Declaro que la información proporcionada es verídica y completa.</strong> Entiendo que cualquier omisión o falsa declaración puede resultar en la terminación del contrato o denegación de reclamos.
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
    <strong>Autorizo al INS</strong> a incluir mi información en una base de datos para ejecutar el contrato y ofrecer productos o servicios relacionados.
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

    <!-- Geo Selector -->
    <script src="/assets/js/cr-geo-selector.js"></script>
    <!-- Form Logic -->
    <script src="/assets/js/form-logic.js?v=2025-12-11"></script>
</body>
</html>
