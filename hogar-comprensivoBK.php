<?php
// CSRF básico
session_start();
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
$csrf = $_SESSION['csrf'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Solicitud Seguro Hogar Comprensivo - INS</title>

  <!-- Tailwind (si luego compilas, reemplázalo por tu CSS local) -->
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>

  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
    *{font-family:Inter,system-ui,Arial,sans-serif}
    .gradient-bg{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%)}
    .step-indicator{transition:all .3s}
    .step-indicator.active{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);transform:scale(1.08)}
    .step-indicator.completed{background:#10b981}
    .form-section{display:none;animation:fadeIn .4s ease}
    .form-section.active{display:block}
    @keyframes fadeIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
    .input-field{transition:all .2s}
    .input-field:focus{transform:translateY(-1px);box-shadow:0 4px 12px rgba(102,126,234,.18)}
    .checkbox-custom{appearance:none;width:20px;height:20px;border:2px solid #d1d5db;border-radius:4px;cursor:pointer;position:relative;transition:all .2s}
    .checkbox-custom:checked{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);border-color:#667eea}
    .checkbox-custom:checked::after{content:'✓';position:absolute;color:#fff;font-size:14px;top:50%;left:50%;transform:translate(-50%,-50%)}
    .radio-custom{appearance:none;width:20px;height:20px;border:2px solid #d1d5db;border-radius:50%;cursor:pointer;position:relative;transition:all .2s}
    .radio-custom:checked{border-color:#667eea}
    .radio-custom:checked::after{content:'';position:absolute;width:10px;height:10px;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);border-radius:50%;top:50%;left:50%;transform:translate(-50%,-50%)}
    .progress-bar{transition:width .4s}
  </style>
</head>
<body class="bg-gray-50">

  <!-- Header -->
  <header class="bg-white shadow-md sticky top-0 z-50">
    <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
    <div class="flex items-center gap-3">
    <div class="w-12 h-12 gradient-bg rounded-lg flex items-center justify-center">
    <i class="fas fa-shield-alt text-white text-xl"></i>
    </div>
    <div>
    <h1 class="text-xl font-bold text-gray-800">Seguro Hogar Comprensivo</h1>
    <p class="text-xs text-gray-500">ASEGURALOCR.COM - Agente: 86611</p>
    </div>
    </div>
    <a href="/index.php" class="text-gray-600 hover:text-purple-600 transition">
    <i class="fas fa-arrow-left mr-2"></i>Volver
    </a>
    </div>
  </header>

  <!-- Barra de pasos -->
  <div class="bg-white border-b">
    <div class="max-w-6xl mx-auto px-4 py-4">
    <div id="step-indicators" class="flex-1 flex items-center mb-4"></div>
    <div class="w-full bg-gray-200 rounded-full h-2">
    <div id="progress-bar" class="progress-bar h-2 rounded-full gradient-bg" style="width:0%"></div>
    </div>
    <p class="text-center text-sm text-gray-600 mt-2"><span id="current-step-text">Paso 1 de 7</span></p>
    </div>
  </div>

  <!-- Formulario -->
  <div class="max-w-5xl mx-auto px-4 py-8">
    <form id="insurance-form" class="bg-white rounded-2xl shadow-xl p-8" method="post" action="/enviarformularios/hogar_procesar.php" novalidate>
    <!-- honeypot -->
    <div style="position:absolute;left:-9999px;opacity:0" aria-hidden="true">
    <label for="website">Website</label>
    <input type="text" name="website" id="website" tabindex="-1" autocomplete="off">
    </div>
    <!-- CSRF -->
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf,ENT_QUOTES) ?>">

    <!-- ==== PASO 1: Datos Personales (mismo contenido que tu HTML) ==== -->
    <div class="form-section active" data-step="1">
    <div class="mb-8">
    <h2 class="text-3xl font-bold text-gray-800 mb-2">
    <i class="fas fa-user text-purple-600 mr-3"></i>Datos Personales
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
    <input type="radio" name="tipoId" value="cedula" class="radio-custom" required>
    <span class="text-sm">Cédula</span>
    </label>
    <label class="flex items-center space-x-2 cursor-pointer">
    <input type="radio" name="tipoId" value="dimex" class="radio-custom">
    <span class="text-sm">DIMEX</span>
    </label>
    <label class="flex items-center space-x-2 cursor-pointer">
    <input type="radio" name="tipoId" value="didi" class="radio-custom">
    <span class="text-sm">DIDI</span>
    </label>
    <label class="flex items-center space-x-2 cursor-pointer">
    <input type="radio" name="tipoId" value="pasaporte" class="radio-custom">
    <span class="text-sm">Pasaporte</span>
    </label>
    </div>
    </div>

    <!-- Número de Identificación -->
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Número de Identificación <span class="text-red-500">*</span>
    </label>
    <input type="text" name="numeroId" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="Ej: 1-2345-6789" required>
    </div>

    <!-- Nombre Completo -->
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Nombre Completo <span class="text-red-500">*</span>
    </label>
    <input type="text" name="nombreCompleto" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="Primer Apellido, Segundo Apellido, Nombre" required>
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
    Dirección Exacta de Domicilio <span class="text-red-500">*</span>
    </label>
    <textarea name="direccion" rows="3" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="Ingrese su dirección completa..." required></textarea>
    </div>

    <!-- Teléfonos -->
    <div class="grid md:grid-cols-3 gap-4">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Teléfono Celular <span class="text-red-500">*</span>
    </label>
    <input type="tel" name="telefonoCelular" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="8888-8888" required>
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Teléfono Domicilio
    </label>
    <input type="tel" name="telefonoDomicilio" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="2222-2222">
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Teléfono Oficina
    </label>
    <input type="tel" name="telefonoOficina" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="2222-2222">
    </div>
    </div>

    <!-- Correo Electrónico -->
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Correo Electrónico <span class="text-red-500">*</span>
    </label>
    <input type="email" name="correo" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="ejemplo@correo.com" required>
    </div>
    </div>
    </div>

    <!-- PASOS 2..7 (respetando tu estructura) -->
    <!-- PASO 2 -->
    <div class="form-section" data-step="2">
    <div class="mb-8">
    <h2 class="text-3xl font-bold text-gray-800 mb-2">
    <i class="fas fa-home text-purple-600 mr-3"></i>Datos de la Propiedad
    </h2>
    <p class="text-gray-600">Información del inmueble a asegurar</p>
    </div>

    <div class="space-y-6">
    <!-- (Contenido de paso 2 simplificado para este ejemplo) -->
    <div class="grid md:grid-cols-2 gap-4">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Tipo de Propiedad <span class="text-red-500">*</span>
    </label>
    <select name="tipoPropiedad" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" required>
    <option value="">Seleccione...</option>
    <option value="casa">Casa de habitación</option>
    <option value="apartamento">Apartamento</option>
    </select>
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
    Latitud
    </label>
    <input type="text" name="latitud" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="9.9345678">
    </div>
    </div>

    <!-- Resto de campos del paso 2... -->
    <div class="grid md:grid-cols-2 gap-4">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Longitud</label>
    <input type="text" name="longitud" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="-84.0856789">
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Provincia (Propiedad)</label>
    <select name="provinciaProp" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
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
    </div>

    <div class="grid md:grid-cols-2 gap-4">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Cantón (Propiedad)</label>
    <select id="cantonProp" name="cantonProp" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
      <option value="">Seleccione...</option>
    </select>
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Distrito (Propiedad)</label>
    <select id="distritoProp" name="distritoProp" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
      <option value="">Seleccione...</option>
    </select>
    </div>
    </div>

    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Urbanización / Otras señas</label>
    <input type="text" name="urbanizacion" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="">
    <textarea name="otrasSenas" rows="2" class="input-field w-full mt-2 px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="Otras señas..."></textarea>
    </div>
    </div>
    </div>

    <!-- PASO 3: Características de Construcción -->
    <div class="form-section" data-step="3">
    <div class="mb-8">
    <h2 class="text-3xl font-bold text-gray-800 mb-2">
    <i class="fas fa-building text-purple-600 mr-3"></i>Características de Construcción
    </h2>
    <p class="text-gray-600">Detalles técnicos de la propiedad</p>
    </div>

    <div class="space-y-6">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-3">
    Rango de Año de Construcción <span class="text-red-500">*</span>
    </label>
    <select name="anoConst" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    <option value="">Seleccione...</option>
    <option value="antes-1974">Antes de 1974</option>
    <option value="1974-1985">1974-1985</option>
    <option value="1986-2001">1986-2001</option>
    <option value="2002-2009">2002-2009</option>
    <option value="2010-actual">2010 a la actualidad</option>
    </select>
    </div>

    <div class="grid md:grid-cols-2 gap-4">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Área Total de Construcción (m²)</label>
    <input type="number" name="areaConstruccion" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" min="1">
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Cantidad de Pisos</label>
    <input type="number" name="cantidadPisos" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" min="1" max="10">
    </div>
    </div>

    <!-- Agrega el resto de campos del paso 3 según tu archivo original -->
    </div>
    </div>

    <!-- PASO 4: Interés Asegurable y Actividad -->
    <div class="form-section" data-step="4">
    <div class="mb-8">
    <h2 class="text-3xl font-bold text-gray-800 mb-2">
    <i class="fas fa-briefcase text-purple-600 mr-3"></i>Interés Asegurable
    </h2>
    <p class="text-gray-600">Relación con la propiedad y uso</p>
    </div>

    <div class="space-y-6">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-3">Interés Asegurable del Solicitante</label>
    <div class="grid md:grid-cols-3 gap-3">
    <label class="flex items-center space-x-2 p-3 border-2 border-gray-200 rounded-lg cursor-pointer">
    <input type="radio" name="interesAseg" value="propietario" class="radio-custom">
    <span class="text-sm">Propietario</span>
    </label>
    <label class="flex items-center space-x-2 p-3 border-2 border-gray-200 rounded-lg cursor-pointer">
    <input type="radio" name="interesAseg" value="arrendatario" class="radio-custom">
    <span class="text-sm">Arrendatario</span>
    </label>
    <label class="flex items-center space-x-2 p-3 border-2 border-gray-200 rounded-lg cursor-pointer">
    <input type="radio" name="interesAseg" value="usufructuario" class="radio-custom">
    <span class="text-sm">Usufructuario</span>
    </label>
    </div>
    </div>

    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Actividad Desarrollada en el Inmueble</label>
    <select name="actividad" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    <option value="">Seleccione...</option>
    <option value="casa-habitacion">Casa de Habitación (100%)</option>
    <option value="casa-oficina">Casa de Habitación + Oficina</option>
    <option value="casa-comercio">Casa de Habitación + Comercio</option>
    </select>
    </div>
    </div>
    </div>

    <!-- PASO 5: Medidas de Seguridad -->
    <div class="form-section" data-step="5">
    <div class="mb-8">
    <h2 class="text-3xl font-bold text-gray-800 mb-2">
    <i class="fas fa-shield-alt text-purple-600 mr-3"></i>Medidas de Seguridad
    </h2>
    <p class="text-gray-600">Protección y seguridad de la propiedad</p>
    </div>

    <div class="space-y-6">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-3">Vigilancia</label>
    <div class="grid md:grid-cols-2 gap-3">
    <label class="flex items-center space-x-2">
    <input type="checkbox" name="vigilancia" value="interna" class="checkbox-custom">
    <span class="text-sm">Interna</span>
    </label>
    <label class="flex items-center space-x-2">
    <input type="checkbox" name="vigilancia" value="externa" class="checkbox-custom">
    <span class="text-sm">Externa</span>
    </label>
    </div>
    </div>

    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-3">Alarma Contra Robo</label>
    <div class="grid md:grid-cols-3 gap-3">
    <label class="flex items-center space-x-2 p-3 border-2 border-gray-200 rounded-lg cursor-pointer">
    <input type="radio" name="alarma" value="no-tiene" class="radio-custom">
    <span class="text-sm">No tiene</span>
    </label>
    <label class="flex items-center space-x-2 p-3 border-2 border-gray-200 rounded-lg cursor-pointer">
    <input type="radio" name="alarma" value="electronica" class="radio-custom">
    <span class="text-sm">Electrónica</span>
    </label>
    </div>
    </div>
    </div>
    </div>

    <!-- PASO 6: Coberturas y Rubros -->
    <div class="form-section" data-step="6">
    <div class="mb-8">
    <h2 class="text-3xl font-bold text-gray-800 mb-2">
    <i class="fas fa-umbrella text-purple-600 mr-3"></i>Coberturas y Montos
    </h2>
    <p class="text-gray-600">Seleccione las coberturas y montos a asegurar</p>
    </div>

    <div class="space-y-6">
    <div class="bg-purple-50 border-2 border-purple-200 rounded-lg p-6">
    <label class="flex items-start space-x-3 cursor-pointer">
    <input type="checkbox" name="coberturaV" value="si" class="checkbox-custom mt-1">
    <div>
    <div class="font-semibold text-gray-800">V: Daño Directo Bienes Inmuebles</div>
    <div class="text-sm text-gray-600">Protege la estructura de tu vivienda</div>
    </div>
    </label>
    </div>

    <div class="grid md:grid-cols-2 gap-4">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Residencia (Edificio)</label>
    <div class="relative">
    <span class="absolute left-4 top-3 text-gray-500">₡</span>
    <input type="number" name="montoResidencia" class="input-field w-full pl-10 pr-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="Ej: 50000" min="0">
    </div>
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Propiedad Personal (Contenido)</label>
    <div class="relative">
    <span class="absolute left-4 top-3 text-gray-500">₡</span>
    <input type="number" name="montoContenido" class="input-field w-full pl-10 pr-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none" placeholder="Ej: 150000" min="0">
    </div>
    </div>
    </div>

    <div class="grid md:grid-cols-2 gap-4">
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Moneda</label>
    <select name="moneda" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    <option value="colones">Colones (₡)</option>
    <option value="dolares">Dólares ($)</option>
    </select>
    </div>
    <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">Plan de Pago</label>
    <select name="planPago" class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
    <option value="anual">Anual</option>
    <option value="mensual">Mensual</option>
    </select>
    </div>
    </div>
    </div>
    </div>

    <!-- PASO 7: Resumen y Confirmación -->
    <div class="form-section" data-step="7">
    <div class="mb-8">
    <h2 class="text-3xl font-bold text-gray-800 mb-2">
    <i class="fas fa-check-circle text-green-600 mr-3"></i>Resumen de Solicitud
    </h2>
    <p class="text-gray-600">Revisa tu información antes de enviar</p>
    </div>

    <!-- Resumen estilizado -->
    <div id="resumen-contenido" class="space-y-6">
    <!-- Se llenará dinámicamente mediante JavaScript -->
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
    <strong>Declaro que la información detallada en este documento es verídica.</strong> En caso de comprobación de omisión o falsa declaración, se podrá terminar el contrato.
    </div>
    </label>
    </div>

    <div class="bg-blue-50 border border-blue-300 rounded-lg p-4">
    <label class="flex items-start space-x-3 cursor-pointer">
    <input type="checkbox" name="consentimientoGrabacion" class="checkbox-custom mt-1" required>
    <div class="text-sm text-gray-700">
    <strong>Consiento</strong> la grabación de llamadas para procesos administrativos y judiciales.
    </div>
    </label>
    </div>

    <div class="bg-green-50 border border-green-300 rounded-lg p-4">
    <label class="flex items-start space-x-3 cursor-pointer">
    <input type="checkbox" name="consentimientoDatos" class="checkbox-custom mt-1" required>
    <div class="text-sm text-gray-700">
    <strong>Autorizo</strong> al INS a procesar mis datos conforme a la legislación aplicable.
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

  <!-- Modal -->
  <div id="modal-confirmacion" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-8 text-center">
    <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
    <i class="fas fa-check text-green-600 text-4xl"></i>
    </div>
    <h3 class="text-2xl font-bold text-gray-800 mb-4">¡Solicitud Enviada!</h3>
    <p class="text-gray-600 mb-6">Hemos recibido tu solicitud. Te contactaremos pronto.</p>
    <a href="/index.php" class="inline-block px-8 py-3 gradient-bg text-white rounded-lg font-semibold hover:opacity-90 transition">Volver al Inicio</a>
    </div>
  </div>

  <!-- Módulo Geo: carga JSON con provincias->cantones->distritos y pobla selects -->
<script>
(async function(){
  function normalizeKey(s){
    if(!s && s!==0) return '';
    // quitar acentos, minúsculas, eliminar espacios y caracteres no alfanuméricos
    try{
      return String(s)
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '') // quitar diacríticos
        .toLowerCase()
        .replace(/[^a-z0-9]/g, '');
    }catch(e){
      return String(s).toLowerCase().replace(/\s+/g,'');
    }
  }

  try{
    const url = '/assets/data/cr-provincias.json?v=1';
    console.debug('[Geo] cargando JSON desde', url);
    const resp = await fetch(url, {cache: 'no-store'});
    if(!resp.ok){
      console.error('[Geo] fallo cargando cr-provincias.json:', resp.status, resp.statusText);
      return;
    }
    const GEO = await resp.json();
    console.debug('[Geo] JSON cargado. claves:', Object.keys(GEO).slice(0,20));

    // Build normalized map: normalizedKey -> originalKey
    const normMap = {};
    Object.keys(GEO).forEach(k=>{
      normMap[normalizeKey(k)] = k;
    });

    function findProvKey(rawProv){
      if(!rawProv) return null;
      // probar en varios formatos
      const candidates = [
        rawProv,
        rawProv.trim(),
        rawProv.toLowerCase(),
        normalizeKey(rawProv)
      ];
      for(const c of candidates){
        if(!c) continue;
        // directo en normMap (si c ya está normalizado)
        if(normMap[c]) return normMap[c];
      }
      // fallback: buscar por inclusión en normalized keys
      const n = normalizeKey(rawProv);
      for(const nk of Object.keys(normMap)){
        if(nk.includes(n) || n.includes(nk)){
          return normMap[nk];
        }
      }
      // nada encontrado
      return null;
    }

    function populateCantones(provValue, cantonSel, distritoSel, restoreSelected){
      cantonSel.innerHTML = '<option value="">Seleccione...</option>';
      distritoSel.innerHTML = '<option value="">Seleccione...</option>';
      if(!provValue){
        console.debug('[Geo] populateCantones: provincia vacía');
        return;
      }
      const matchedKey = findProvKey(provValue);
      if(!matchedKey){
        console.warn('[Geo] No se encontró provincia en JSON para:', provValue, ' (normalized:', normalizeKey(provValue), ')');
        // mostrar debugging mínimo en el select
        const opt = document.createElement('option'); opt.value = ''; opt.textContent = 'No hay cantones (provincia no encontrada)';
        cantonSel.appendChild(opt);
        return;
      }
      const cantonesObj = GEO[matchedKey];
      if(!cantonesObj || Object.keys(cantonesObj).length===0){
        console.warn('[Geo] Provincia encontrada pero sin cantones en JSON:', matchedKey);
        return;
      }
      Object.keys(cantonesObj).forEach(c => {
        const opt = document.createElement('option'); opt.value = c; opt.textContent = c;
        cantonSel.appendChild(opt);
      });
      if(restoreSelected && cantonSel.dataset.restoreValue){
        cantonSel.value = cantonSel.dataset.restoreValue;
        // forzar disparo de llenado de distritos
        fillDistritos(cantonSel, distritoSel, true);
      }
    }

    function fillDistritos(cantonSel, distritoSel, restoreSelected){
      const provSel = document.querySelector(`[name="${cantonSel.dataset.provName}"]`);
      const provVal = provSel ? provSel.value : null;
      distritoSel.innerHTML = '<option value="">Seleccione...</option>';
      if(!provVal){
        return;
      }
      const matchedProvKey = findProvKey(provVal);
      if(!matchedProvKey){
        console.warn('[Geo] fillDistritos: no matchedProvKey para:', provVal);
        return;
      }
      const lista = (GEO[matchedProvKey] && GEO[matchedProvKey][cantonSel.value]) || GEO[matchedProvKey][cantonSel.value] || [];
      if(!lista || lista.length===0){
        // Intenta buscar por normalización del nombre de cantón
        const normalizedCanton = normalizeKey(cantonSel.value);
        const possible = Object.keys(GEO[matchedProvKey]).find(k => normalizeKey(k) === normalizedCanton);
        if(possible){
          (GEO[matchedProvKey][possible] || []).forEach(d=>{
            const opt = document.createElement('option'); opt.value = d; opt.textContent = d; distritoSel.appendChild(opt);
          });
        } else {
          console.warn('[Geo] No se hallaron distritos para canton:', cantonSel.value, 'en provincia', matchedProvKey);
        }
        if(restoreSelected && distritoSel.dataset.restoreValue){
          distritoSel.value = distritoSel.dataset.restoreValue;
        }
        return;
      }
      lista.forEach(d=>{
        const opt = document.createElement('option'); opt.value = d; opt.textContent = d; distritoSel.appendChild(opt);
      });
      if(restoreSelected && distritoSel.dataset.restoreValue){
        distritoSel.value = distritoSel.dataset.restoreValue;
      }
    }

    function setupPair(provName, cantonId, distritoId){
      const provSel = document.querySelector(`[name="${provName}"]`);
      const cantonSel = document.getElementById(cantonId);
      const distritoSel = document.getElementById(distritoId);
      if(!provSel || !cantonSel || !distritoSel){
        console.debug('[Geo] setupPair: faltan elementos para', provName, cantonId, distritoId);
        return;
      }
      cantonSel.dataset.provName = provName;
      // Si el restore ya puso valores (localStorage), guardarlos para re-aplicar
      if(cantonSel.value) cantonSel.dataset.restoreValue = cantonSel.value;
      if(distritoSel.value) distritoSel.dataset.restoreValue = distritoSel.value;

      provSel.addEventListener('change', function(){
        console.debug('[Geo] provincia cambió a:', this.value);
        populateCantones(this.value, cantonSel, distritoSel, false);
        cantonSel.value = '';
        distritoSel.value = '';
      });

      cantonSel.addEventListener('change', function(){
        console.debug('[Geo] canton cambió a:', this.value);
        fillDistritos(cantonSel, distritoSel, false);
      });

      // Si ya hay provincia seleccionada al cargar, poblar cantones reestableciendo valores guardados
      if(provSel.value){
        console.debug('[Geo] provincia ya seleccionada al cargar:', provSel.value);
        populateCantones(provSel.value, cantonSel, distritoSel, true);
      }
    }

    // Inicializa pares
    setupPair('provincia','canton','distrito');
    setupPair('provinciaProp','cantonProp','distritoProp');

    console.debug('[Geo] inicializado correctamente');

  }catch(err){
    console.error('[Geo] error general:', err);
  }
})();
</script>
  
  

  <!-- Lógica principal (tu script existente) -->
  <script src="/assets/js/form-logic.js?v=2025-11-07"></script>

  <!-- Generador de resumen (sin botón Editar) -->
  <script>
  /* Mapa de nombres de campo a etiquetas legibles */
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
    telefonoDomicilio: 'Teléfono Domicilio',
    telefonoOficina: 'Teléfono Oficina',
    correo: 'Correo Electrónico',

    tipoPropiedad: 'Tipo de Propiedad',
    latitud: 'Latitud',
    longitud: 'Longitud',
    provinciaProp: 'Provincia (Propiedad)',
    cantonProp: 'Cantón (Propiedad)',
    distritoProp: 'Distrito (Propiedad)',
    esquina: 'Localizado en esquina',
    urbanizacion: 'Urbanización / Barrio',
    otrasSenas: 'Otras señas',
    folioReal: 'Folio Real / Finca',

    anoConst: 'Año de Construcción',
    areaConstruccion: 'Área Construcción (m²)',
    cantidadPisos: 'Cantidad de Pisos',
    areaPisoIgual: 'Área por piso igual',
    pisoUbicacion: 'Piso de Ubicación',
    sistemaElectrico: 'Sistema Eléctrico',
    tipoConstruccion: 'Tipo de Construcción',
    estadoConserv: 'Estado de Conservación',
    modificaciones: 'Modificaciones estructurales',

    interesAseg: 'Interés Asegurable',
    actividad: 'Actividad en Inmueble',
    porcCasa: '% Casa',
    porcOtras: '% Otras',
    detalleActividad: 'Detalle de la Actividad',
    ocupadoPor: 'Inmueble ocupado por',
    gasLP: 'Uso de Gas LP',

    vigilancia: 'Vigilancia',
    horarioVigilancia: 'Horario Vigilancia',
    alarma: 'Alarma',
    cerraduras: 'Cerraduras',
    tapias: 'Tapias',
    alturaTapias: 'Altura Tapias (m)',
    materialTapias: 'Material Tapias',
    alambreNavaja: 'Alambre Navaja',
    ventanas: 'Tipo de Ventanas',
    puertasExternas: 'Puertas Externas',
    propiedadSola: 'Propiedad permanece sola',
    horasSola: 'Horas aproximadas sola',
    otrasMedidasSeguridad: 'Otras medidas de seguridad',

    coberturaV: 'Cobertura V (Edificio)',
    coberturaContenido: 'Cobertura Contenidos',
    coberturaD: 'Cobertura D (Desastres)',
    coberturaK: 'Cobertura K (Resp. Civil)',
    coberturaP: 'Cobertura P (Accidentes)',
    montoResidencia: 'Monto Residencia',
    montoContenido: 'Monto Contenido',
    montoJoyeria: 'Monto Joyería',
    montoObrasArte: 'Monto Obras de Arte',
    moneda: 'Moneda',
    planPago: 'Plan de Pago',
    opcionAseguramiento: 'Opción de Aseguramiento',

    consentimientoInfo: 'Consentimiento: Información verídica',
    consentimientoGrabacion: 'Consentimiento: Grabación',
    consentimientoDatos: 'Consentimiento: Tratamiento de Datos'
  };

  function readFieldValue(form, name) {
    const els = Array.from(form.querySelectorAll(`[name="${name}"]`));
    if (!els.length) return null;
    if (els.length === 1) {
    const el = els[0];
    if (el.type === 'checkbox') return el.checked ? (el.value || 'Sí') : null;
    if (el.type === 'radio') {
    const checked = form.querySelector(`[name="${name}"]:checked`);
    return checked ? checked.value : null;
    }
    return el.value ? el.value : null;
    }
    // múltiples elementos con mismo name (checkbox group or radios)
    const checked = els.filter(e => (e.type === 'checkbox' && e.checked) || (e.type === 'radio' && e.checked));
    if (checked.length === 0) return null;
    const values = checked.map(e => e.value || 'Sí');
    return values.join(', ');
  }

  function formatCurrency(value, moneda) {
    if (!value) return '';
    const num = Number(value);
    if (isNaN(num)) return value;
    if (!moneda || moneda === 'colones') {
    try {
    return new Intl.NumberFormat('es-CR', { style: 'currency', currency: 'CRC', maximumFractionDigits: 0 }).format(num);
    } catch(e) {
    return '₡ ' + Math.round(num).toLocaleString();
    }
    } else {
    try {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', maximumFractionDigits: 2 }).format(num);
    } catch(e) {
    return '$ ' + Number(num).toFixed(2);
    }
    }
  }

  function buildSectionHTML(title, rows) {
    const rowsHtml = rows.map(r => {
    const safeValue = r.value ? String(r.value) : '<span class="text-gray-400">No indicado</span>';
    return `
    <div class="grid grid-cols-3 gap-x-4 py-2 border-b last:border-b-0">
    <div class="col-span-1 text-sm text-gray-600">${r.label}</div>
    <div class="col-span-2 text-sm text-gray-800">${safeValue}</div>
    </div>
    `;
    }).join('');

    return `
    <div class="bg-white shadow-sm rounded-lg border p-4">
    <div class="flex items-center justify-between mb-3">
    <h4 class="text-md font-semibold text-gray-800">${title}</h4>
    </div>
    <div class="border-t -mx-4 px-4">${rowsHtml}</div>
    </div>
    `;
  }

  function generateSummary() {
    const form = document.getElementById('insurance-form');
    const container = document.getElementById('resumen-contenido');
    if (!form || !container) return;

    const monedaVal = readFieldValue(form, 'moneda');

    const personalKeys = ['tipoId','numeroId','nombreCompleto','provincia','canton','distrito','pais','direccion','telefonoCelular','telefonoDomicilio','telefonoOficina','correo'];
    const propertyKeys = ['tipoPropiedad','latitud','longitud','provinciaProp','cantonProp','distritoProp','esquina','urbanizacion','otrasSenas','folioReal'];
    const construccionKeys = ['anoConst','areaConstruccion','cantidadPisos','areaPisoIgual','pisoUbicacion','sistemaElectrico','tipoConstruccion','estadoConserv','modificaciones'];
    const seguridadKeys = ['vigilancia','horarioVigilancia','alarma','cerraduras','tapias','alturaTapias','materialTapias','alambreNavaja','ventanas','puertasExternas','propiedadSola','horasSola','otrasMedidasSeguridad'];

    const personal = personalKeys.map(k => ({ label: fieldLabels[k] || k, value: readFieldValue(form,k) }));
    const propiedad = propertyKeys.map(k => ({ label: fieldLabels[k] || k, value: readFieldValue(form,k) }));
    const construccion = construccionKeys.map(k => ({ label: fieldLabels[k] || k, value: readFieldValue(form,k) }));
    const seguridad = seguridadKeys.map(k => ({ label: fieldLabels[k] || k, value: readFieldValue(form,k) }));

    // Coberturas con formateo de montos
    const coberturasList = [
    'coberturaV','coberturaContenido','coberturaD','coberturaK','coberturaP'
    ].map(k => ({ label: fieldLabels[k] || k, value: readFieldValue(form,k) }));

    const montos = [
    { key: 'montoResidencia', currency: true },
    { key: 'montoContenido', currency: true },
    { key: 'montoJoyeria', currency: true },
    { key: 'montoObrasArte', currency: true }
    ].map(item => {
    const raw = readFieldValue(form, item.key);
    return { label: fieldLabels[item.key] || item.key, value: item.currency ? formatCurrency(raw, monedaVal) : raw };
    });

    const otrosCoberturas = [
    'moneda','planPago','opcionAseguramiento'
    ].map(k => ({ label: fieldLabels[k] || k, value: readFieldValue(form,k) }));

    const consentimientos = ['consentimientoInfo','consentimientoGrabacion','consentimientoDatos']
    .map(k => ({ label: fieldLabels[k] || k, value: readFieldValue(form,k) ? 'Aceptado' : 'No aceptado' }));

    const sections = [
    { title: 'Datos Personales', rows: personal, step: 1 },
    { title: 'Datos de la Propiedad', rows: propiedad, step: 2 },
    { title: 'Características de Construcción', rows: construccion, step: 3 },
    { title: 'Medidas de Seguridad', rows: seguridad, step: 5 },
    { title: 'Coberturas (seleccionadas)', rows: coberturasList.concat(montos).concat(otrosCoberturas), step: 6 },
    { title: 'Consentimientos', rows: consentimientos, step: 7 }
    ];

    container.innerHTML = sections.map(s => buildSectionHTML(s.title, s.rows)).join('');
  }

  document.addEventListener('DOMContentLoaded', function () {
    const btnNext = document.getElementById('btn-next');
    const btnPrev = document.getElementById('btn-prev');

    function maybeGenerate() {
    const paso7 = document.querySelector('.form-section[data-step="7"].active');
    if (paso7) generateSummary();
    }

    if (btnNext) btnNext.addEventListener('click', () => setTimeout(maybeGenerate, 150));
    if (btnPrev) btnPrev.addEventListener('click', () => setTimeout(maybeGenerate, 150));

    // Escuchar evento personalizado por si tu form-logic.js emite stepChanged
    document.addEventListener('stepChanged', maybeGenerate);

    // Al cargar la página comprobamos
    maybeGenerate();

    // Imprimir
    const btnPrint = document.getElementById('resumen-print');
    if (btnPrint) btnPrint.addEventListener('click', () => window.print());

    // Botón descargar PDF (placeholder)
    const btnDownload = document.getElementById('resumen-download');
    if (btnDownload) btnDownload.addEventListener('click', function () {
    alert('Quieres que implemente la descarga a PDF? Puedo añadir jsPDF y generar un PDF del resumen. Dime si lo hago y lo implemento.');
    });
  });
  </script>

</body>
</html>