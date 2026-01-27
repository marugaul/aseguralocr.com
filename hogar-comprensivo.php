<?php
require_once __DIR__ . '/app/services/Security.php';
Security::start();
$csrf = Security::csrfToken();
$clienteData = ['tipoId' => '', 'cedula' => '', 'nombre' => '', 'correo' => '', 'telefono' => ''];
if (!empty($_SESSION['client_id'])) {
    try {
        $config = require __DIR__ . '/app/config/config.php';
        $pdo = new PDO("mysql:host={$config['db']['mysql']['host']};dbname={$config['db']['mysql']['dbname']};charset={$config['db']['mysql']['charset']}", $config['db']['mysql']['user'], $config['db']['mysql']['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $stmt = $pdo->prepare("SELECT cedula, nombre_completo, email, telefono FROM clients WHERE id = ?");
        $stmt->execute([$_SESSION['client_id']]);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($cliente) { $clienteData = ['tipoId' => 'cedula', 'cedula' => $cliente['cedula'] ?? '', 'nombre' => $cliente['nombre_completo'] ?? '', 'correo' => $cliente['email'] ?? '', 'telefono' => $cliente['telefono'] ?? '']; }
    } catch (Exception $e) { error_log("Error loading client data: " . $e->getMessage()); }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seguro Hogar Comprensivo - AseguraLoCR</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .step-indicator { transition: all 0.3s ease; }
        .step-indicator.active { background-color: #2563eb; color: white; }
        .step-indicator.completed { background-color: #10b981; color: white; }
        .form-step { display: none; }
        .form-step.active { display: block; }
        .section-card { background: #f8fafc; border-radius: 0.5rem; padding: 1rem; margin-bottom: 1rem; border: 1px solid #e2e8f0; }
        .section-title { font-weight: 600; color: #1e40af; margin-bottom: 0.75rem; font-size: 1rem; border-bottom: 2px solid #3b82f6; padding-bottom: 0.5rem; }
        .subsection-title { font-weight: 500; color: #475569; margin: 0.75rem 0 0.5rem; font-size: 0.9rem; }
        .checkbox-group label { display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.25rem 0; }
        .checkbox-group input[type="checkbox"] { width: 1rem; height: 1rem; }
        input[type="text"], input[type="email"], input[type="tel"], input[type="number"], select, textarea { width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 0.875rem; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59,130,246,0.2); }
        .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.75rem; }
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.75rem; }
        .grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.5rem; }
        .field-group { margin-bottom: 1rem; }
        @media (max-width: 768px) { .grid-2, .grid-3, .grid-4 { grid-template-columns: 1fr; } }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <header class="bg-blue-600 text-white py-4 shadow-lg">
        <div class="container mx-auto px-4 flex justify-between items-center">
            <a href="/" class="text-2xl font-bold">AseguraLoCR</a>
            <a href="/" class="hover:text-blue-200">Inicio</a>
        </div>
    </header>
    <main class="container mx-auto px-4 py-8 max-w-5xl">
        <h1 class="text-3xl font-bold text-center text-gray-800 mb-2">Seguro de Hogar Comprensivo</h1>
        <p class="text-center text-gray-600 mb-6">Complete el formulario para cotizar su seguro del INS</p>
        <div class="flex justify-center mb-8 flex-wrap gap-2">
            <div class="step-indicator active rounded-full w-10 h-10 flex items-center justify-center text-sm font-bold" data-step="1">1</div>
            <div class="step-indicator bg-gray-300 rounded-full w-10 h-10 flex items-center justify-center text-sm font-bold" data-step="2">2</div>
            <div class="step-indicator bg-gray-300 rounded-full w-10 h-10 flex items-center justify-center text-sm font-bold" data-step="3">3</div>
            <div class="step-indicator bg-gray-300 rounded-full w-10 h-10 flex items-center justify-center text-sm font-bold" data-step="4">4</div>
            <div class="step-indicator bg-gray-300 rounded-full w-10 h-10 flex items-center justify-center text-sm font-bold" data-step="5">5</div>
            <div class="step-indicator bg-gray-300 rounded-full w-10 h-10 flex items-center justify-center text-sm font-bold" data-step="6">6</div>
        </div>
        <form id="hogarForm" action="/enviarformularios/hogar_procesar.php" method="POST" class="bg-white rounded-xl shadow-lg p-6">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="website" value="">

            <!-- PASO 1 -->
            <div class="form-step active" data-step="1">
                <h2 class="text-xl font-bold text-blue-700 mb-4"><i class="fas fa-user mr-2"></i>Paso 1: Tomador y Asegurado</h2>
                <div class="section-card">
                    <div class="section-title">Tipo de Trámite</div>
                    <div class="checkbox-group grid-3">
                        <label><input type="checkbox" name="cb_cotizacion" value="1"> Cotización</label>
                        <label><input type="checkbox" name="cb_inclusion" value="1"> Inclusión</label>
                        <label><input type="checkbox" name="cb_variacion" value="1"> Variación</label>
                    </div>
                    <div class="field-group mt-3"><label class="block text-sm font-medium text-gray-700 mb-1">Lugar</label><input type="text" name="lugar"></div>
                </div>
                <div class="section-card">
                    <div class="section-title"><i class="fas fa-user-tie mr-2"></i>Datos del Tomador</div>
                    <div class="subsection-title">Tipo de Identificación</div>
                    <div class="checkbox-group grid-4">
                        <label><input type="checkbox" name="cb_tomador_pf_cedula" value="1"> Cédula</label>
                        <label><input type="checkbox" name="cb_tomador_pf_dimex" value="1"> DIMEX</label>
                        <label><input type="checkbox" name="cb_tomador_pf_didi" value="1"> DIDI</label>
                        <label><input type="checkbox" name="cb_tomador_pf_pasaporte" value="1"> Pasaporte</label>
                        <label><input type="checkbox" name="cb_tomador_pj_nacional" value="1"> PJ Nacional</label>
                        <label><input type="checkbox" name="cb_tomador_pj_extranjera" value="1"> PJ Extranjera</label>
                        <label><input type="checkbox" name="cb_tomador_gobierno" value="1"> Gobierno</label>
                        <label><input type="checkbox" name="cb_tomador_inst_autonoma" value="1"> Inst. Autónoma</label>
                        <label><input type="checkbox" name="cb_tomador_otro" value="1"> Otro</label>
                    </div>
                    <div class="grid-2 mt-3">
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">N° ID *</label><input type="text" name="tomador_num_id" data-padron-cedula="tomador" required value="<?= htmlspecialchars($clienteData['cedula']) ?>"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Otro Tipo</label><input type="text" name="tomador_otro_tipo"></div>
                    </div>
                    <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label><input type="text" name="tomador_nombre" data-padron-nombre-completo="tomador" required value="<?= htmlspecialchars($clienteData['nombre']) ?>"></div>
                    <div class="subsection-title">Dirección</div>
                    <div class="grid-2">
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">País</label><input type="text" name="tomador_pais" value="Costa Rica"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Provincia</label><select name="tomador_provincia" data-geo-group="tomador" data-padron-provincia="tomador"><option value="">-- Seleccione --</option></select></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Cantón</label><select name="tomador_canton" data-geo-canton="tomador"><option value="">-- Seleccione --</option></select></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Distrito</label><select name="tomador_distrito" data-geo-distrito="tomador"><option value="">-- Seleccione --</option></select></div>
                    </div>
                    <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Dirección Exacta</label><textarea name="tomador_direccion" rows="2"></textarea></div>
                    <div class="subsection-title">Contacto</div>
                    <div class="grid-3">
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Tel. Oficina</label><input type="tel" name="tomador_tel_oficina"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Tel. Domicilio</label><input type="tel" name="tomador_tel_domicilio"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Celular *</label><input type="tel" name="tomador_tel_celular" required value="<?= htmlspecialchars($clienteData['telefono']) ?>"></div>
                    </div>
                    <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Correo *</label><input type="email" name="tomador_correo" required value="<?= htmlspecialchars($clienteData['correo']) ?>"></div>
                    <div class="subsection-title">Relación con Asegurado</div>
                    <div class="checkbox-group grid-4">
                        <label><input type="checkbox" name="cb_relacion_familiar" value="1"> Familiar</label>
                        <label><input type="checkbox" name="cb_relacion_comercial" value="1"> Comercial</label>
                        <label><input type="checkbox" name="cb_relacion_laboral" value="1"> Laboral</label>
                        <label><input type="checkbox" name="cb_relacion_otro" value="1"> Otro</label>
                    </div>
                    <div class="field-group mt-2"><label class="block text-sm font-medium text-gray-700 mb-1">Especificar</label><input type="text" name="relacion_otro_texto"></div>
                </div>
                <div class="section-card">
                    <div class="section-title"><i class="fas fa-user-shield mr-2"></i>Datos del Asegurado</div>
                    <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" id="copiarTomadorAsegurado" class="w-5 h-5 mr-3 text-blue-600">
                            <span class="font-medium text-blue-800">El asegurado es el mismo que el tomador</span>
                        </label>
                    </div>
                    <div class="subsection-title">Tipo de Identificación</div>
                    <div class="checkbox-group grid-4">
                        <label><input type="checkbox" name="cb_asegurado_pf_cedula" value="1"> Cédula</label>
                        <label><input type="checkbox" name="cb_asegurado_pf_dimex" value="1"> DIMEX</label>
                        <label><input type="checkbox" name="cb_asegurado_pf_didi" value="1"> DIDI</label>
                        <label><input type="checkbox" name="cb_asegurado_pf_pasaporte" value="1"> Pasaporte</label>
                        <label><input type="checkbox" name="cb_asegurado_pf_otro" value="1"> Otro</label>
                        <label><input type="checkbox" name="cb_asegurado_pj_nacional" value="1"> PJ Nacional</label>
                        <label><input type="checkbox" name="cb_asegurado_pj_gobierno" value="1"> Gobierno</label>
                        <label><input type="checkbox" name="cb_asegurado_pj_autonoma" value="1"> Inst. Autónoma</label>
                        <label><input type="checkbox" name="cb_asegurado_pj_extranjera" value="1"> PJ Extranjera</label>
                    </div>
                    <div class="grid-2 mt-3">
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">N° ID</label><input type="text" name="asegurado_num_id"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">País</label><input type="text" name="asegurado_pais" value="Costa Rica"></div>
                    </div>
                    <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label><input type="text" name="asegurado_nombre"></div>
                    <div class="subsection-title">Dirección</div>
                    <div class="grid-3">
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Provincia</label><select name="asegurado_provincia" data-geo-group="asegurado"><option value="">-- Seleccione --</option></select></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Cantón</label><select name="asegurado_canton" data-geo-canton="asegurado"><option value="">-- Seleccione --</option></select></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Distrito</label><select name="asegurado_distrito" data-geo-distrito="asegurado"><option value="">-- Seleccione --</option></select></div>
                    </div>
                    <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Dirección</label><textarea name="asegurado_direccion" rows="2"></textarea></div>
                    <div class="subsection-title">Contacto</div>
                    <div class="grid-3">
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Tel. Oficina</label><input type="tel" name="asegurado_tel_oficina"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Tel. Domicilio</label><input type="tel" name="asegurado_tel_domicilio"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Celular</label><input type="tel" name="asegurado_tel_celular"></div>
                    </div>
                    <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Correo</label><input type="email" name="asegurado_correo"></div>
                    <div class="subsection-title">Notificación</div>
                    <div class="checkbox-group grid-3">
                        <label><input type="checkbox" name="cb_notif_tomador" value="1"> Al Tomador</label>
                        <label><input type="checkbox" name="cb_notif_asegurado" value="1"> Al Asegurado</label>
                        <label><input type="checkbox" name="cb_notif_correo" value="1"> Por Correo</label>
                        <label><input type="checkbox" name="cb_notif_residencia" value="1"> En Residencia</label>
                        <label><input type="checkbox" name="cb_notif_otro" value="1"> Otro</label>
                    </div>
                </div>
                <div class="flex justify-end mt-6"><button type="button" onclick="nextStep(2)" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Siguiente <i class="fas fa-arrow-right ml-2"></i></button></div>
            </div>

            <!-- PASO 2 -->
            <div class="form-step" data-step="2">
                <h2 class="text-xl font-bold text-blue-700 mb-4"><i class="fas fa-home mr-2"></i>Paso 2: Propiedad</h2>
                <div class="section-card">
                    <div class="section-title"><i class="fas fa-map-marker-alt mr-2"></i>Ubicación</div>
                    <div class="subsection-title">Tipo</div>
                    <div class="checkbox-group grid-3">
                        <label><input type="checkbox" name="cb_tipo_casa" value="1"> Casa</label>
                        <label><input type="checkbox" name="cb_tipo_edificio" value="1"> Edificio</label>
                        <label><input type="checkbox" name="cb_tipo_condominio" value="1"> Condominio</label>
                    </div>
                    <div class="grid-2 mt-3">
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">País</label><input type="text" name="prop_pais" value="Costa Rica"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Provincia *</label><select name="prop_provincia" data-geo-group="prop" required><option value="">-- Seleccione --</option></select></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Cantón *</label><select name="prop_canton" data-geo-canton="prop" required><option value="">-- Seleccione --</option></select></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Distrito *</label><select name="prop_distrito" data-geo-distrito="prop" required><option value="">-- Seleccione --</option></select></div>
                    </div>
                    <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Urbanización</label><input type="text" name="prop_urbanizacion"></div>
                    <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Otras Señas *</label><textarea name="prop_otras_senas" rows="2" required></textarea></div>
                    <div class="grid-3">
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Folio Real</label><input type="text" name="prop_folio_real"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Latitud</label><input type="text" name="prop_latitud"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Longitud</label><input type="text" name="prop_longitud"></div>
                    </div>
                    <div class="checkbox-group mt-2"><span class="text-sm mr-4">¿Esquina?</span><label><input type="checkbox" name="cb_esquina_si" value="1"> Sí</label><label class="ml-4"><input type="checkbox" name="cb_esquina_no" value="1"> No</label></div>
                </div>
                <div class="section-card">
                    <div class="section-title"><i class="fas fa-building mr-2"></i>Construcción</div>
                    <div class="subsection-title">Año</div>
                    <div class="checkbox-group grid-3">
                        <label><input type="checkbox" name="cb_ano_antes1974" value="1"> Antes 1974</label>
                        <label><input type="checkbox" name="cb_ano_1974_1985" value="1"> 1974-1985</label>
                        <label><input type="checkbox" name="cb_ano_1986_2001" value="1"> 1986-2001</label>
                        <label><input type="checkbox" name="cb_ano_2002_2009" value="1"> 2002-2009</label>
                        <label><input type="checkbox" name="cb_ano_2010_actual" value="1"> 2010-Actual</label>
                    </div>
                    <div class="grid-3 mt-3">
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Área m² *</label><input type="number" name="area_construccion" required></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Pisos</label><input type="number" name="cantidad_pisos"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Ubicación Piso</label><input type="text" name="piso_ubicacion"></div>
                    </div>
                    <div class="checkbox-group mt-2"><span class="text-sm mr-4">Área igual?</span><label><input type="checkbox" name="cb_area_igual_si" value="1"> Sí</label><label class="ml-4"><input type="checkbox" name="cb_area_igual_no" value="1"> No</label></div>
                </div>
                <div class="section-card">
                    <div class="section-title"><i class="fas fa-hard-hat mr-2"></i>Tipo Construcción</div>
                    <div class="checkbox-group grid-2">
                        <label><input type="checkbox" name="cb_const_e1" value="1"> E1: Mampostería Ladrillo</label>
                        <label><input type="checkbox" name="cb_const_e2" value="1"> E2: Mampostería Block</label>
                        <label><input type="checkbox" name="cb_const_e3" value="1"> E3: Concreto Reforzado</label>
                        <label><input type="checkbox" name="cb_const_e4" value="1"> E4: Concreto Prefab.</label>
                        <label><input type="checkbox" name="cb_const_e5" value="1"> E5: Panelería Doble</label>
                        <label><input type="checkbox" name="cb_const_e6" value="1"> E6: Panelería Emparedado</label>
                        <label><input type="checkbox" name="cb_const_e7" value="1"> E7: Madera</label>
                        <label><input type="checkbox" name="cb_const_e9" value="1"> E9: Marcos Concreto</label>
                        <label><input type="checkbox" name="cb_const_e10" value="1"> E10: Marcos Acero</label>
                        <label><input type="checkbox" name="cb_const_e11" value="1"> E11: Sobrepuestas</label>
                        <label><input type="checkbox" name="cb_const_e12" value="1"> E12: Naves Mampostería</label>
                        <label><input type="checkbox" name="cb_const_e13" value="1"> E13: Naves Acero</label>
                        <label><input type="checkbox" name="cb_const_e14" value="1"> E14: Naves Concreto</label>
                    </div>
                    <div class="checkbox-group mt-3"><label><input type="checkbox" name="cb_elect_parcial" value="1"> Eléctrico Parcial</label></div>
                </div>
                <div class="section-card">
                    <div class="section-title"><i class="fas fa-compass mr-2"></i>Colindantes</div>
                    <div class="grid-2">
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Norte</label><input type="text" name="colindante_norte"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Sur</label><input type="text" name="colindante_sur"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Este</label><input type="text" name="colindante_este"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Oeste</label><input type="text" name="colindante_oeste"></div>
                    </div>
                </div>
                <div class="section-card">
                    <div class="section-title"><i class="fas fa-clipboard-check mr-2"></i>Estado</div>
                    <div class="checkbox-group grid-3">
                        <label><input type="checkbox" name="cb_estado_optimo" value="1"> Óptimo</label>
                        <label><input type="checkbox" name="cb_estado_muy_bueno" value="1"> Muy Bueno</label>
                        <label><input type="checkbox" name="cb_estado_bueno" value="1"> Bueno</label>
                        <label><input type="checkbox" name="cb_estado_regular" value="1"> Regular</label>
                        <label><input type="checkbox" name="cb_estado_malo" value="1"> Malo</label>
                        <label><input type="checkbox" name="cb_estado_muy_malo" value="1"> Muy Malo</label>
                    </div>
                    <div class="checkbox-group mt-3"><label><input type="checkbox" name="cb_modif_sobrepeso_si" value="1"> Sobrepeso</label></div>
                </div>
                <div class="section-card">
                    <div class="section-title"><i class="fas fa-water mr-2"></i>Cercanía Agua</div>
                    <div class="checkbox-group grid-3">
                        <label><input type="checkbox" name="cb_cerca_rio" value="1"> Río</label>
                        <label><input type="checkbox" name="cb_cerca_lago" value="1"> Lago</label>
                        <label><input type="checkbox" name="cb_cerca_pleamar" value="1"> Pleamar</label>
                        <label><input type="checkbox" name="cb_cerca_talud" value="1"> Talud</label>
                        <label><input type="checkbox" name="cb_cerca_pendiente" value="1"> Pendiente</label>
                        <label><input type="checkbox" name="cb_cerca_otro_agua" value="1"> Otro</label>
                    </div>
                    <div class="subsection-title">Distancia</div>
                    <div class="checkbox-group grid-3">
                        <label><input type="checkbox" name="cb_dist_0_5m" value="1"> 0-5m</label>
                        <label><input type="checkbox" name="cb_dist_6_10m" value="1"> 6-10m</label>
                        <label><input type="checkbox" name="cb_dist_11_20m" value="1"> 11-20m</label>
                        <label><input type="checkbox" name="cb_dist_21_49m" value="1"> 21-49m</label>
                        <label><input type="checkbox" name="cb_dist_50_100m" value="1"> 50-100m</label>
                        <label><input type="checkbox" name="cb_dist_mas_100m" value="1"> +100m</label>
                    </div>
                </div>
                <div class="flex justify-between mt-6">
                    <button type="button" onclick="prevStep(1)" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600"><i class="fas fa-arrow-left mr-2"></i> Anterior</button>
                    <button type="button" onclick="nextStep(3)" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Siguiente <i class="fas fa-arrow-right ml-2"></i></button>
                </div>
            </div>

            <!-- PASO 3 -->
            <div class="form-step" data-step="3">
                <h2 class="text-xl font-bold text-blue-700 mb-4"><i class="fas fa-shield-alt mr-2"></i>Paso 3: Seguridad</h2>
                <div class="section-card">
                    <div class="section-title"><i class="fas fa-file-contract mr-2"></i>Interés Asegurable</div>
                    <div class="checkbox-group grid-3">
                        <label><input type="checkbox" name="cb_interes_arrendatario" value="1"> Arrendatario</label>
                        <label><input type="checkbox" name="cb_interes_usufructuario" value="1"> Usufructuario</label>
                        <label><input type="checkbox" name="cb_interes_depositario" value="1"> Depositario</label>
                        <label><input type="checkbox" name="cb_interes_acreedor" value="1"> Acreedor</label>
                        <label><input type="checkbox" name="cb_interes_consignatario" value="1"> Consignatario</label>
                        <label><input type="checkbox" name="cb_interes_otro" value="1"> Otro</label>
                    </div>
                    <div class="field-group mt-2"><label class="block text-sm font-medium text-gray-700 mb-1">Otro</label><input type="text" name="interes_otro_texto"></div>
                    <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Actividad</label><input type="text" name="actividad_inmueble"></div>
                    <div class="grid-2">
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">% Habitación</label><input type="number" name="pct_casa_habitacion"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">% Otras</label><input type="number" name="pct_otras_ocupaciones"></div>
                    </div>
                    <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Detalle</label><textarea name="detalle_actividad" rows="2"></textarea></div>
                    <div class="checkbox-group"><label><input type="checkbox" name="cb_ocupado_inquilino" value="1"> Inquilino</label></div>
                    <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Propietario</label><input type="text" name="nombre_propietario"></div>
                    <div class="checkbox-group"><label><input type="checkbox" name="cb_gas_si" value="1"> Gas LP</label></div>
                    <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Inflamables</label><input type="text" name="sustancias_inflamables"></div>
                </div>
                <div class="section-card">
                    <div class="section-title"><i class="fas fa-user-shield mr-2"></i>Vigilancia</div>
                    <div class="checkbox-group grid-2">
                        <label><input type="checkbox" name="cb_vigilancia_interna" value="1"> Interna</label>
                        <label><input type="checkbox" name="cb_vigilancia_externa" value="1"> Externa</label>
                        <label><input type="checkbox" name="cb_horario_diurno" value="1"> Diurno</label>
                        <label><input type="checkbox" name="cb_horario_nocturno" value="1"> Nocturno</label>
                    </div>
                    <div class="checkbox-group mt-2"><label><input type="checkbox" name="cb_propiedad_sola_si" value="1"> Sola</label></div>
                    <div class="field-group mt-2"><label class="block text-sm font-medium text-gray-700 mb-1">Horas Sola</label><input type="text" name="propiedad_sola_horas"></div>
                </div>
                <div class="section-card">
                    <div class="section-title"><i class="fas fa-bell mr-2"></i>Alarma</div>
                    <div class="checkbox-group grid-2">
                        <label><input type="checkbox" name="cb_alarma_no_tiene" value="1"> No Tiene</label>
                        <label><input type="checkbox" name="cb_alarma_magnetica" value="1"> Magnética</label>
                        <label><input type="checkbox" name="cb_alarma_electronica" value="1"> Electrónica</label>
                        <label><input type="checkbox" name="cb_alarma_central" value="1"> Central</label>
                        <label><input type="checkbox" name="cb_cctv_jardines" value="1"> CCTV</label>
                        <label><input type="checkbox" name="cb_luces_infrarrojas" value="1"> Infrarrojas</label>
                    </div>
                </div>
                <div class="section-card">
                    <div class="section-title"><i class="fas fa-lock mr-2"></i>Cerraduras</div>
                    <div class="checkbox-group"><label><input type="checkbox" name="cb_llavin_sencillo" value="1"> Llavín</label></div>
                    <div class="field-group mt-2"><label class="block text-sm font-medium text-gray-700 mb-1">Otro</label><input type="text" name="cerradura_otro"></div>
                </div>
                <div class="section-card">
                    <div class="section-title"><i class="fas fa-dungeon mr-2"></i>Tapias</div>
                    <div class="checkbox-group"><span class="text-sm mr-4">¿Tiene?</span><label><input type="checkbox" name="cb_tapias_si" value="1"> Sí</label><label class="ml-4"><input type="checkbox" name="cb_tapias_no" value="1"> No</label></div>
                    <div class="grid-2 mt-2">
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Altura</label><input type="text" name="tapias_altura"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Material</label><input type="text" name="tapias_material"></div>
                    </div>
                    <div class="checkbox-group"><label><input type="checkbox" name="cb_alambre_navaja" value="1"> Alambre</label></div>
                    <div class="subsection-title">Frente</div>
                    <div class="checkbox-group grid-2">
                        <label><input type="checkbox" name="cb_frente_muros" value="1"> Muros</label>
                        <label><input type="checkbox" name="cb_frente_verjas" value="1"> Verjas</label>
                    </div>
                    <div class="field-group mt-2"><label class="block text-sm font-medium text-gray-700 mb-1">Otro</label><input type="text" name="frente_otro"></div>
                </div>
                <div class="section-card">
                    <div class="section-title"><i class="fas fa-window-maximize mr-2"></i>Ventanas</div>
                    <div class="checkbox-group grid-2">
                        <label><input type="checkbox" name="cb_ventana_frances" value="1"> Francés</label>
                        <label><input type="checkbox" name="cb_ventana_corriente" value="1"> Corriente</label>
                        <label><input type="checkbox" name="cb_ventana_celosias" value="1"> Celosías</label>
                        <label><input type="checkbox" name="cb_ventana_cortinas_metal" value="1"> Cortinas</label>
                        <label><input type="checkbox" name="cb_ventana_vidrio_seg" value="1"> Vidrio Seg.</label>
                    </div>
                    <div class="field-group mt-2"><label class="block text-sm font-medium text-gray-700 mb-1">Otro</label><input type="text" name="ventana_otro"></div>
                </div>
                <div class="section-card">
                    <div class="section-title"><i class="fas fa-door-open mr-2"></i>Puertas</div>
                    <div class="checkbox-group grid-2">
                        <label><input type="checkbox" name="cb_puerta_madera" value="1"> Madera</label>
                        <label><input type="checkbox" name="cb_puerta_vidrio" value="1"> Vidrio</label>
                        <label><input type="checkbox" name="cb_puerta_verjas" value="1"> Verjas</label>
                        <label><input type="checkbox" name="cb_puerta_contrapuerta" value="1"> Contrapuerta</label>
                        <label><input type="checkbox" name="cb_puerta_marco_seg" value="1"> Marco Seg.</label>
                    </div>
                    <div class="field-group mt-2"><label class="block text-sm font-medium text-gray-700 mb-1">Otro</label><input type="text" name="puerta_otro"></div>
                </div>
                <div class="section-card">
                    <div class="section-title"><i class="fas fa-shield-alt mr-2"></i>Otras Medidas</div>
                    <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Describa</label><textarea name="otra_medida_seguridad" rows="2"></textarea></div>
                </div>
                <div class="flex justify-between mt-6">
                    <button type="button" onclick="prevStep(2)" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600"><i class="fas fa-arrow-left mr-2"></i> Anterior</button>
                    <button type="button" onclick="nextStep(4)" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Siguiente <i class="fas fa-arrow-right ml-2"></i></button>
                </div>
            </div>

            <!-- PASO 4 -->
            <div class="form-step" data-step="4">
                <h2 class="text-xl font-bold text-blue-700 mb-4"><i class="fas fa-file-invoice-dollar mr-2"></i>Paso 4: Coberturas</h2>
                <div class="section-card">
                    <div class="section-title"><i class="fas fa-check-circle mr-2"></i>Básicas</div>
                    <div class="subsection-title">Y: Daño con robo</div>
                    <div class="checkbox-group"><label><input type="checkbox" name="cb_cob_y" value="1"> Incluir</label></div>
                    <div class="checkbox-group grid-2 mt-2">
                        <label><input type="checkbox" name="cb_cob_y_con_lista" value="1"> Con Lista</label>
                        <label><input type="checkbox" name="cb_cob_y_sin_lista" value="1"> Sin Lista</label>
                    </div>
                    <div class="checkbox-group grid-4 mt-2">
                        <label><input type="checkbox" name="cb_cob_y_grupo_1a" value="1"> 1A</label>
                        <label><input type="checkbox" name="cb_cob_y_grupo_1b" value="1"> 1B</label>
                        <label><input type="checkbox" name="cb_cob_y_grupo_2" value="1"> 2</label>
                        <label><input type="checkbox" name="cb_cob_y_grupo_3" value="1"> 3</label>
                    </div>
                    <div class="subsection-title">X: Daño sin robo</div>
                    <div class="checkbox-group"><label><input type="checkbox" name="cb_cob_x" value="1"> Incluir</label></div>
                    <div class="checkbox-group grid-2 mt-2">
                        <label><input type="checkbox" name="cb_cob_x_con_lista" value="1"> Con Lista</label>
                        <label><input type="checkbox" name="cb_cob_x_sin_lista" value="1"> Sin Lista</label>
                    </div>
                </div>
                <div class="section-card">
                    <div class="section-title"><i class="fas fa-plus-circle mr-2"></i>Adicionales</div>
                    <div class="subsection-title">D: Participación</div>
                    <div class="checkbox-group grid-3">
                        <label><input type="checkbox" name="cb_cob_d_particip_0" value="1"> 0%</label>
                        <label><input type="checkbox" name="cb_cob_d_particip_10" value="1"> 10%</label>
                        <label><input type="checkbox" name="cb_cob_d_particip_20" value="1"> 20%</label>
                    </div>
                    <div class="subsection-title">H: Pérdida Rentas</div>
                    <div class="checkbox-group"><label><input type="checkbox" name="cb_cob_h" value="1"> Incluir</label></div>
                    <div class="checkbox-group grid-3 mt-2">
                        <label><input type="checkbox" name="cb_cob_h_3meses" value="1"> 3m</label>
                        <label><input type="checkbox" name="cb_cob_h_4_6meses" value="1"> 4-6m</label>
                        <label><input type="checkbox" name="cb_cob_h_7_12meses" value="1"> 7-12m</label>
                    </div>
                    <div class="subsection-title">Otras</div>
                    <div class="checkbox-group grid-2">
                        <label><input type="checkbox" name="cb_cob_k" value="1"> K: RC</label>
                        <label><input type="checkbox" name="cb_cob_m" value="1"> M: RT</label>
                        <label><input type="checkbox" name="cb_cob_p" value="1"> P: AP</label>
                        <label><input type="checkbox" name="cb_cob_s" value="1"> S: Multi</label>
                    </div>
                    <div class="field-group mt-2"><label class="block text-sm font-medium text-gray-700 mb-1">S: Benef.</label><input type="text" name="cob_s_beneficiario"></div>
                </div>
                <div class="section-card">
                    <div class="section-title"><i class="fas fa-percentage mr-2"></i>Deducibles</div>
                    <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">D: Otro</label><input type="text" name="ded_d_otro"></div>
                    <div class="grid-2">
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">V Vientos</label><input type="text" name="ded_v_vientos_otro"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">V Riesgos</label><input type="text" name="ded_v_riesgos_otro"></div>
                    </div>
                    <div class="grid-3">
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">XY Vientos</label><input type="text" name="ded_xy_vientos_otro"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Y Robo</label><input type="text" name="ded_y_robo_otro"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">XY Riesgos</label><input type="text" name="ded_xy_riesgos_otro"></div>
                    </div>
                    <div class="grid-2">
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">H: Otro</label><input type="text" name="ded_h_otro"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">P: Otro</label><input type="text" name="ded_p_otro"></div>
                    </div>
                </div>
                <div class="section-card">
                    <div class="section-title"><i class="fas fa-tags mr-2"></i>Valor</div>
                    <div class="checkbox-group grid-2">
                        <label><input type="checkbox" name="cb_valor_real" value="1"> Real</label>
                        <label><input type="checkbox" name="cb_valor_otro" value="1"> Otro</label>
                    </div>
                    <div class="field-group mt-2"><label class="block text-sm font-medium text-gray-700 mb-1">Especificar</label><input type="text" name="valor_otro_texto"></div>
                </div>
                <div class="flex justify-between mt-6">
                    <button type="button" onclick="prevStep(3)" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600"><i class="fas fa-arrow-left mr-2"></i> Anterior</button>
                    <button type="button" onclick="nextStep(5)" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Siguiente <i class="fas fa-arrow-right ml-2"></i></button>
                </div>
            </div>

            <!-- PASO 5 -->
            <div class="form-step" data-step="5">
                <h2 class="text-xl font-bold text-blue-700 mb-4"><i class="fas fa-dollar-sign mr-2"></i>Paso 5: Rubros</h2>
                <div class="section-card">
                    <div class="section-title"><i class="fas fa-list-ol mr-2"></i>Rubros</div>
                    <div class="grid-2">
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Residencia Exp.</label><input type="text" name="rubro_residencia_expuesto"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Residencia Aseg.</label><input type="text" name="rubro_residencia_asegurado"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Prop. Personal Exp.</label><input type="text" name="rubro_prop_personal_expuesto"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Prop. Personal Aseg.</label><input type="text" name="rubro_prop_personal_asegurado"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Joyería Exp.</label><input type="text" name="rubro_joyeria_expuesto"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Joyería Aseg.</label><input type="text" name="rubro_joyeria_asegurado"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Arte Exp.</label><input type="text" name="rubro_arte_expuesto"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Arte Aseg.</label><input type="text" name="rubro_arte_asegurado"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Rentas</label><input type="text" name="rubro_rentas_asegurado"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">RC</label><input type="text" name="rubro_rc_asegurado"></div>
                    </div>
                    <div class="checkbox-group mt-3"><label><input type="checkbox" name="cb_opcion_coaseguro_80" value="1"> Coaseguro 80%</label></div>
                </div>
                <div class="section-card">
                    <div class="section-title"><i class="fas fa-warehouse mr-2"></i>Obras Compl.</div>
                    <div class="grid-2">
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Bodegas $</label><input type="text" name="obra_bodegas_monto"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Bodegas m²</label><input type="text" name="obra_bodegas_area"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Piscinas $</label><input type="text" name="obra_piscinas_monto"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Piscinas m²</label><input type="text" name="obra_piscinas_area"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Tapias $</label><input type="text" name="obra_tapias_monto"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Tapias m²</label><input type="text" name="obra_tapias_area"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Garajes $</label><input type="text" name="obra_garajes_monto"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Garajes m²</label><input type="text" name="obra_garajes_area"></div>
                    </div>
                    <div class="field-group mt-2"><label class="block text-sm font-medium text-gray-700 mb-1">Otros</label><textarea name="obra_otros" rows="2"></textarea></div>
                </div>
                <div class="section-card">
                    <div class="section-title"><i class="fas fa-file-alt mr-2"></i>Póliza</div>
                    <div class="checkbox-group"><label><input type="checkbox" name="cb_moneda_dolares" value="1" checked> Dólares</label></div>
                    <div class="subsection-title">Pago</div>
                    <div class="checkbox-group grid-3">
                        <label><input type="checkbox" name="cb_pago_anual" value="1"> Anual</label>
                        <label><input type="checkbox" name="cb_pago_cuatrimestral" value="1"> Cuatrim.</label>
                        <label><input type="checkbox" name="cb_pago_trimestral" value="1"> Trim.</label>
                        <label><input type="checkbox" name="cb_pago_bimestral" value="1"> Bim.</label>
                        <label><input type="checkbox" name="cb_pago_mensual" value="1"> Mensual</label>
                    </div>
                    <div class="subsection-title">Vía</div>
                    <div class="checkbox-group grid-2">
                        <label><input type="checkbox" name="cb_via_cargo_auto" value="1"> Cargo Auto.</label>
                        <label><input type="checkbox" name="cb_via_deduccion" value="1"> Deducción</label>
                    </div>
                    <div class="checkbox-group mt-3"><label><input type="checkbox" name="cb_poliza_otra_si" value="1"> Otra Aseg.</label></div>
                    <div class="grid-2 mt-2">
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Aseguradora</label><input type="text" name="otra_aseguradora_nombre"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">N° Póliza</label><input type="text" name="otra_aseguradora_poliza"></div>
                    </div>
                    <div class="checkbox-group mt-2"><label><input type="checkbox" name="cb_aseg_cuenta_tercero" value="1"> Cuenta Tercero</label></div>
                </div>
                <div class="section-card">
                    <div class="section-title"><i class="fas fa-user-friends mr-2"></i>Beneficiario</div>
                    <div class="checkbox-group grid-3">
                        <label><input type="checkbox" name="cb_benef_pf_cedula" value="1"> Cédula</label>
                        <label><input type="checkbox" name="cb_benef_pf_dimex" value="1"> DIMEX</label>
                        <label><input type="checkbox" name="cb_benef_pf_didi" value="1"> DIDI</label>
                        <label><input type="checkbox" name="cb_benef_pf_pasaporte" value="1"> Pasaporte</label>
                        <label><input type="checkbox" name="cb_benef_pf_otro" value="1"> Otro</label>
                        <label><input type="checkbox" name="cb_benef_pj_nacional" value="1"> PJ Nac.</label>
                        <label><input type="checkbox" name="cb_benef_pj_extranjera" value="1"> PJ Ext.</label>
                        <label><input type="checkbox" name="cb_benef_pj_gobierno" value="1"> Gobierno</label>
                        <label><input type="checkbox" name="cb_benef_pj_autonoma" value="1"> Autónoma</label>
                    </div>
                    <div class="grid-2 mt-3">
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">N° ID</label><input type="text" name="benef_num_id"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label><input type="text" name="benef_nombre"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Parentesco</label><input type="text" name="benef_parentesco"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">%</label><input type="text" name="benef_porcentaje"></div>
                    </div>
                </div>
                <div class="section-card">
                    <div class="section-title"><i class="fas fa-university mr-2"></i>Acreedor</div>
                    <div class="checkbox-group grid-3">
                        <label><input type="checkbox" name="cb_acreedor_pf_cedula" value="1"> Cédula</label>
                        <label><input type="checkbox" name="cb_acreedor_pf_dimex" value="1"> DIMEX</label>
                        <label><input type="checkbox" name="cb_acreedor_pf_didi" value="1"> DIDI</label>
                        <label><input type="checkbox" name="cb_acreedor_pf_pasaporte" value="1"> Pasaporte</label>
                        <label><input type="checkbox" name="cb_acreedor_pf_otro" value="1"> Otro</label>
                        <label><input type="checkbox" name="cb_acreedor_pj_nacional" value="1"> PJ Nac.</label>
                        <label><input type="checkbox" name="cb_acreedor_pj_extranjera" value="1"> PJ Ext.</label>
                        <label><input type="checkbox" name="cb_acreedor_pj_gobierno" value="1"> Gobierno</label>
                        <label><input type="checkbox" name="cb_acreedor_pj_autonoma" value="1"> Autónoma</label>
                    </div>
                    <div class="grid-2 mt-3">
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">N° ID</label><input type="text" name="acreedor_num_id"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label><input type="text" name="acreedor_nombre"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Monto</label><input type="text" name="acreedor_monto"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Grado</label><input type="text" name="acreedor_grado"></div>
                    </div>
                </div>
                <div class="flex justify-between mt-6">
                    <button type="button" onclick="prevStep(4)" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600"><i class="fas fa-arrow-left mr-2"></i> Anterior</button>
                    <button type="button" onclick="nextStep(6)" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Siguiente <i class="fas fa-arrow-right ml-2"></i></button>
                </div>
            </div>

            <!-- PASO 6 -->
            <div class="form-step" data-step="6">
                <h2 class="text-xl font-bold text-blue-700 mb-4"><i class="fas fa-star mr-2"></i>Paso 6: Especiales</h2>
                <div class="section-card">
                    <div class="section-title"><i class="fas fa-fire-extinguisher mr-2"></i>Incendio</div>
                    <div class="checkbox-group grid-2">
                        <label><input type="checkbox" name="cb_proteccion_auto" value="1"> Auto</label>
                        <label><input type="checkbox" name="cb_proteccion_manual" value="1"> Manual</label>
                        <label><input type="checkbox" name="cb_codigo_electrico" value="1"> Código Elec.</label>
                        <label><input type="checkbox" name="cb_detectores_humo" value="1"> Detectores</label>
                        <label><input type="checkbox" name="cb_alarma_sonora" value="1"> Alarma</label>
                    </div>
                </div>
                <div class="section-card">
                    <div class="section-title"><i class="fas fa-leaf mr-2"></i>Sostenibilidad</div>
                    <div class="checkbox-group"><label><input type="checkbox" name="cb_paneles_solares" value="1"> Paneles</label></div>
                </div>
                <div class="section-card">
                    <div class="section-title"><i class="fas fa-gavel mr-2"></i>RC Medidas</div>
                    <div class="subsection-title">Gradas</div>
                    <div class="checkbox-group grid-2">
                        <label><input type="checkbox" name="cb_gradas_antideslizante" value="1"> Antideslizante</label>
                        <label><input type="checkbox" name="cb_gradas_alfombras" value="1"> Alfombras</label>
                        <label><input type="checkbox" name="cb_gradas_cintas" value="1"> Cintas</label>
                        <label><input type="checkbox" name="cb_gradas_pasamanos_esc" value="1"> Pasamanos Esc.</label>
                        <label><input type="checkbox" name="cb_gradas_pasamanos_desn" value="1"> Pasamanos Desn.</label>
                    </div>
                    <div class="subsection-title">Piscina</div>
                    <div class="checkbox-group grid-2">
                        <label><input type="checkbox" name="cb_piscina_antideslizante" value="1"> Antideslizante</label>
                        <label><input type="checkbox" name="cb_piscina_banos" value="1"> Baños</label>
                        <label><input type="checkbox" name="cb_piscina_areas_exp" value="1"> Áreas Exp.</label>
                        <label><input type="checkbox" name="cb_piscina_salvavidas" value="1"> Salvavidas</label>
                        <label><input type="checkbox" name="cb_piscina_flotadores" value="1"> Flotadores</label>
                    </div>
                    <div class="field-group mt-2"><label class="block text-sm font-medium text-gray-700 mb-1">Otras</label><input type="text" name="piscina_otras_medidas"></div>
                    <div class="subsection-title">Animales</div>
                    <div class="checkbox-group"><label><input type="checkbox" name="cb_animales_si" value="1"> Posee</label></div>
                    <div class="checkbox-group grid-2 mt-2">
                        <label><input type="checkbox" name="cb_animales_dentro_si" value="1"> Dentro Sí</label>
                        <label><input type="checkbox" name="cb_animales_dentro_no" value="1"> Dentro No</label>
                        <label><input type="checkbox" name="cb_animales_avisos_si" value="1"> Avisos Sí</label>
                        <label><input type="checkbox" name="cb_animales_avisos_no" value="1"> Avisos No</label>
                        <label><input type="checkbox" name="cb_animales_seguridad_si" value="1"> Seg. Sí</label>
                        <label><input type="checkbox" name="cb_animales_seguridad_no" value="1"> Seg. No</label>
                    </div>
                </div>
                <div class="section-card">
                    <div class="section-title"><i class="fas fa-heartbeat mr-2"></i>AP (P)</div>
                    <div class="subsection-title">Asegurado</div>
                    <div class="grid-2">
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label><input type="text" name="ap_asegurado_nombre"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Cédula</label><input type="text" name="ap_asegurado_cedula"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Fecha Nac.</label><input type="text" name="ap_asegurado_fecha_nac"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Edad</label><input type="number" name="ap_asegurado_edad"></div>
                    </div>
                    <div class="checkbox-group mt-2"><span class="text-sm mr-4">¿Zurdo?</span><label><input type="checkbox" name="cb_ap_asegurado_zurdo_si" value="1"> Sí</label><label class="ml-4"><input type="checkbox" name="cb_ap_asegurado_zurdo_no" value="1"> No</label></div>
                    <div class="grid-3 mt-3">
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Muerte</label><input type="text" name="ap_asegurado_muerte"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Incapacidad</label><input type="text" name="ap_asegurado_incapacidad"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Gastos Méd.</label><input type="text" name="ap_asegurado_gastos_med"></div>
                    </div>
                    <div class="subsection-title">Cónyuge</div>
                    <div class="grid-3">
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label><input type="text" name="ap_conyuge_nombre"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Cédula</label><input type="text" name="ap_conyuge_cedula"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Fecha</label><input type="text" name="ap_conyuge_fecha_nac"></div>
                    </div>
                    <div class="subsection-title">Hijos</div>
                    <div class="grid-2">
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Hijo 1</label><input type="text" name="ap_hijo1_nombre"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Cédula 1</label><input type="text" name="ap_hijo1_cedula"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Hijo 2</label><input type="text" name="ap_hijo2_nombre"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Cédula 2</label><input type="text" name="ap_hijo2_cedula"></div>
                    </div>
                    <div class="field-group mt-3"><label class="block text-sm font-medium text-gray-700 mb-1">Recibe Indem.</label><input type="text" name="ap_indemnizacion_nombre"></div>
                    <div class="subsection-title">Defecto</div>
                    <div class="grid-2">
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label><input type="text" name="ap_defecto_nombre"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Parte</label><input type="text" name="ap_defecto_parte"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Causa</label><input type="text" name="ap_defecto_causa"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Fecha</label><input type="text" name="ap_defecto_fecha"></div>
                    </div>
                    <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Grado</label><input type="text" name="ap_defecto_grado"></div>
                </div>
                <div class="section-card">
                    <div class="section-title"><i class="fas fa-hard-hat mr-2"></i>RT (M)</div>
                    <div class="checkbox-group grid-3">
                        <label><input type="checkbox" name="cb_rt_opcion1" value="1"> 1 Trab.</label>
                        <label><input type="checkbox" name="cb_rt_opcion2" value="1"> 2 Trab.</label>
                        <label><input type="checkbox" name="cb_rt_opcion3" value="1"> 3+ Trab.</label>
                    </div>
                    <div class="subsection-title">Trab. 1</div>
                    <div class="grid-3">
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label><input type="text" name="rt_trab1_nombre"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Cédula</label><input type="text" name="rt_trab1_cedula"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Ocupación</label><input type="text" name="rt_trab1_ocupacion"></div>
                    </div>
                    <div class="subsection-title">Trab. 2</div>
                    <div class="grid-3">
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label><input type="text" name="rt_trab2_nombre"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Cédula</label><input type="text" name="rt_trab2_cedula"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Ocupación</label><input type="text" name="rt_trab2_ocupacion"></div>
                    </div>
                </div>
                <div class="section-card">
                    <div class="section-title"><i class="fas fa-signature mr-2"></i>Confirmación</div>
                    <div class="grid-2">
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label><input type="text" name="firma_tomador_nombre"></div>
                        <div class="field-group"><label class="block text-sm font-medium text-gray-700 mb-1">N° ID</label><input type="text" name="firma_tomador_id"></div>
                    </div>
                    <div class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <p class="text-sm text-yellow-800"><i class="fas fa-info-circle mr-2"></i><strong>Declaración:</strong> Información verídica.</p>
                    </div>
                </div>
                <div class="flex justify-between mt-6">
                    <button type="button" onclick="prevStep(5)" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600"><i class="fas fa-arrow-left mr-2"></i> Anterior</button>
                    <button type="submit" class="bg-green-600 text-white px-8 py-3 rounded-lg hover:bg-green-700 font-bold"><i class="fas fa-paper-plane mr-2"></i> Enviar</button>
                </div>
            </div>
        </form>
    </main>
    <footer class="bg-gray-800 text-white py-8 mt-12"><div class="container mx-auto px-4 text-center"><p>&copy; <?= date('Y') ?> AseguraLoCR</p></div></footer>
    <script src="/assets/js/cr-geo-selector.js"></script>
    <script src="/assets/js/padron-autocomplete.js"></script>
    <script>
        let currentStep = 1;
        function updateStepIndicators() { document.querySelectorAll('.step-indicator').forEach((ind, i) => { ind.classList.remove('active', 'completed'); if (i + 1 === currentStep) ind.classList.add('active'); else if (i + 1 < currentStep) ind.classList.add('completed'); }); }
        function showStep(step) { document.querySelectorAll('.form-step').forEach(s => s.classList.remove('active')); document.querySelector(`.form-step[data-step="${step}"]`).classList.add('active'); window.scrollTo({ top: 0, behavior: 'smooth' }); }
        function validateCurrentStep() {
            const currentStepEl = document.querySelector(`.form-step[data-step="${currentStep}"]`);
            const reqFields = currentStepEl.querySelectorAll('[required]');
            let valid = true;
            let firstInvalid = null;
            reqFields.forEach(f => {
                if (!f.value || !f.value.trim()) {
                    valid = false;
                    f.classList.add('border-red-500');
                    if (!firstInvalid) firstInvalid = f;
                } else {
                    f.classList.remove('border-red-500');
                }
            });
            if (!valid) {
                alert('Complete los campos obligatorios marcados con *');
                if (firstInvalid) firstInvalid.focus();
            }
            return valid;
        }
        function nextStep(step) { if (validateCurrentStep()) { currentStep = step; showStep(step); updateStepIndicators(); } }
        function prevStep(step) { currentStep = step; showStep(step); updateStepIndicators(); }
        document.getElementById('hogarForm').addEventListener('submit', function(e) { if (!validateCurrentStep()) { e.preventDefault(); } });

        // Copiar datos del tomador al asegurado
        document.getElementById('copiarTomadorAsegurado').addEventListener('change', function() {
            if (this.checked) {
                const campos = ['num_id', 'nombre', 'provincia', 'canton', 'distrito', 'direccion', 'tel_oficina', 'tel_domicilio', 'tel_celular', 'correo'];
                campos.forEach(campo => {
                    const tomador = document.querySelector(`[name="tomador_${campo}"]`);
                    const asegurado = document.querySelector(`[name="asegurado_${campo}"]`);
                    if (tomador && asegurado) {
                        asegurado.value = tomador.value;
                        if (tomador.tagName === 'SELECT') {
                            asegurado.dispatchEvent(new Event('change'));
                        }
                    }
                });
                // Copiar checkboxes de tipo ID
                ['pf_cedula', 'pf_dimex', 'pf_didi', 'pf_pasaporte'].forEach(tipo => {
                    const tomadorCb = document.querySelector(`[name="cb_tomador_${tipo}"]`);
                    const aseguradoCb = document.querySelector(`[name="cb_asegurado_${tipo}"]`);
                    if (tomadorCb && aseguradoCb) aseguradoCb.checked = tomadorCb.checked;
                });
            }
        });
    </script>
</body>
</html>
