<?php
// admin/pdf_mapper.php - Herramienta visual para mapear campos a PDFs
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../app/services/Security.php';

// Usar Security::start() para manejo consistente de sesiones
Security::start();

// Verificar que esté logueado como admin
if (empty($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    header('Location: /admin/login.php');
    exit;
}

// Tipo de póliza seleccionado
$tipoPoliza = $_GET['tipo'] ?? 'hogar';
$tiposPoliza = ['hogar', 'autos', 'rt'];
if (!in_array($tipoPoliza, $tiposPoliza)) $tipoPoliza = 'hogar';

// Directorios según tipo
$pdfDirs = [
    'hogar' => __DIR__ . '/../formulariosbase/hogar/',
    'autos' => __DIR__ . '/../formulariosbase/autos/',
    'rt' => __DIR__ . '/../formulariosbase/rt/'
];
$pdfDir = $pdfDirs[$tipoPoliza];
$mappingsDir = __DIR__ . '/../mappings/';

// Crear directorios si no existen
foreach ($pdfDirs as $dir) {
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
}
if (!is_dir($mappingsDir)) @mkdir($mappingsDir, 0755, true);

// Listar PDFs disponibles
$pdfs = [];
if (is_dir($pdfDir)) {
    $files = scandir($pdfDir);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'pdf') {
            $pdfs[] = $file;
        }
    }
}

// =====================================================
// CAMPOS DEL FORMULARIO INS - HOGAR COMPRENSIVO
// Todos los campos del formulario oficial del INS
// =====================================================
$camposHogar = [
    // === ENCABEZADO ===
    ['key' => 'hogar_fecha_dd', 'label' => 'Fecha DD', 'type' => 'text', 'section' => 'Encabezado', 'source' => 'sistema'],
    ['key' => 'hogar_fecha_mm', 'label' => 'Fecha MM', 'type' => 'text', 'section' => 'Encabezado', 'source' => 'sistema'],
    ['key' => 'hogar_fecha_aaaa', 'label' => 'Fecha AAAA', 'type' => 'text', 'section' => 'Encabezado', 'source' => 'sistema'],
    ['key' => 'hogar_lugar', 'label' => 'Lugar', 'type' => 'text', 'section' => 'Encabezado', 'source' => 'payload'],
    ['key' => 'hogar_hora', 'label' => 'Hora', 'type' => 'text', 'section' => 'Encabezado', 'source' => 'sistema'],

    // === TIPO DE TRÁMITE ===
    ['key' => 'hogar_cb_emision', 'label' => '☑ Emisión', 'type' => 'checkbox', 'section' => 'Tipo Trámite', 'source' => 'fijo'],
    ['key' => 'hogar_cb_inclusion', 'label' => '☑ Inclusión en colectiva', 'type' => 'checkbox', 'section' => 'Tipo Trámite', 'source' => 'payload'],
    ['key' => 'hogar_cb_variacion', 'label' => '☑ Variación', 'type' => 'checkbox', 'section' => 'Tipo Trámite', 'source' => 'payload'],
    ['key' => 'hogar_cb_cotizacion', 'label' => '☑ Cotización', 'type' => 'checkbox', 'section' => 'Tipo Trámite', 'source' => 'payload'],
    ['key' => 'hogar_num_poliza_colectiva', 'label' => 'N° Póliza Colectiva', 'type' => 'text', 'section' => 'Tipo Trámite', 'source' => 'ins'],
    ['key' => 'hogar_num_poliza_individual', 'label' => 'N° Póliza Individual', 'type' => 'text', 'section' => 'Tipo Trámite', 'source' => 'ins'],

    // === DATOS DEL TOMADOR ===
    ['key' => 'hogar_tomador_nombre', 'label' => 'Nombre Completo Tomador', 'type' => 'text', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'hogar_cb_tomador_pj_nacional', 'label' => '☑ PJ Nacional', 'type' => 'checkbox', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'hogar_cb_tomador_pj_extranjera', 'label' => '☑ PJ Extranjera', 'type' => 'checkbox', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'hogar_cb_tomador_gobierno', 'label' => '☑ Gobierno', 'type' => 'checkbox', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'hogar_cb_tomador_inst_autonoma', 'label' => '☑ Institución Autónoma', 'type' => 'checkbox', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'hogar_cb_tomador_pf_cedula', 'label' => '☑ PF Cédula', 'type' => 'checkbox', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'hogar_cb_tomador_pf_dimex', 'label' => '☑ PF DIMEX', 'type' => 'checkbox', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'hogar_cb_tomador_pf_didi', 'label' => '☑ PF DIDI', 'type' => 'checkbox', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'hogar_cb_tomador_pf_pasaporte', 'label' => '☑ PF Pasaporte', 'type' => 'checkbox', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'hogar_cb_tomador_otro', 'label' => '☑ Otro Tipo ID', 'type' => 'checkbox', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'hogar_tomador_otro_tipo', 'label' => 'Especificar Otro Tipo', 'type' => 'text', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'hogar_tomador_num_id', 'label' => 'N° Identificación Tomador', 'type' => 'text', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'hogar_tomador_pais', 'label' => 'País Tomador', 'type' => 'text', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'hogar_tomador_provincia', 'label' => 'Provincia Tomador', 'type' => 'text', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'hogar_tomador_canton', 'label' => 'Cantón Tomador', 'type' => 'text', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'hogar_tomador_distrito', 'label' => 'Distrito Tomador', 'type' => 'text', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'hogar_tomador_direccion', 'label' => 'Dirección Exacta Tomador', 'type' => 'text', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'hogar_tomador_tel_oficina', 'label' => 'Teléfono Oficina Tomador', 'type' => 'text', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'hogar_tomador_tel_domicilio', 'label' => 'Teléfono Domicilio Tomador', 'type' => 'text', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'hogar_tomador_tel_celular', 'label' => 'Teléfono Celular Tomador', 'type' => 'text', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'hogar_tomador_correo', 'label' => 'Correo Tomador', 'type' => 'text', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'hogar_cb_relacion_familiar', 'label' => '☑ Relación Familiar', 'type' => 'checkbox', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'hogar_cb_relacion_comercial', 'label' => '☑ Relación Comercial', 'type' => 'checkbox', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'hogar_cb_relacion_laboral', 'label' => '☑ Relación Laboral', 'type' => 'checkbox', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'hogar_cb_relacion_otro', 'label' => '☑ Relación Otro', 'type' => 'checkbox', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'hogar_relacion_otro_texto', 'label' => 'Especificar Relación', 'type' => 'text', 'section' => 'Datos Tomador', 'source' => 'payload'],

    // === DATOS DEL ASEGURADO ===
    ['key' => 'hogar_asegurado_nombre', 'label' => 'Nombre Completo Asegurado', 'type' => 'text', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'hogar_cb_asegurado_pj_nacional', 'label' => '☑ Aseg. PJ Nacional', 'type' => 'checkbox', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'hogar_cb_asegurado_pj_gobierno', 'label' => '☑ Aseg. Gobierno', 'type' => 'checkbox', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'hogar_cb_asegurado_pj_autonoma', 'label' => '☑ Aseg. Inst. Autónoma', 'type' => 'checkbox', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'hogar_cb_asegurado_pj_extranjera', 'label' => '☑ Aseg. PJ Extranjera', 'type' => 'checkbox', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'hogar_cb_asegurado_pf_cedula', 'label' => '☑ Aseg. Cédula', 'type' => 'checkbox', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'hogar_cb_asegurado_pf_dimex', 'label' => '☑ Aseg. DIMEX', 'type' => 'checkbox', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'hogar_cb_asegurado_pf_didi', 'label' => '☑ Aseg. DIDI', 'type' => 'checkbox', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'hogar_cb_asegurado_pf_pasaporte', 'label' => '☑ Aseg. Pasaporte', 'type' => 'checkbox', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'hogar_cb_asegurado_pf_otro', 'label' => '☑ Aseg. Otro ID', 'type' => 'checkbox', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'hogar_asegurado_num_id', 'label' => 'N° Identificación Asegurado', 'type' => 'text', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'hogar_asegurado_pais', 'label' => 'País Asegurado', 'type' => 'text', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'hogar_asegurado_provincia', 'label' => 'Provincia Asegurado', 'type' => 'text', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'hogar_asegurado_canton', 'label' => 'Cantón Asegurado', 'type' => 'text', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'hogar_asegurado_distrito', 'label' => 'Distrito Asegurado', 'type' => 'text', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'hogar_asegurado_direccion', 'label' => 'Dirección Exacta Asegurado', 'type' => 'text', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'hogar_asegurado_tel_oficina', 'label' => 'Teléfono Oficina Asegurado', 'type' => 'text', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'hogar_asegurado_tel_domicilio', 'label' => 'Teléfono Domicilio Asegurado', 'type' => 'text', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'hogar_asegurado_tel_celular', 'label' => 'Teléfono Celular Asegurado', 'type' => 'text', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'hogar_asegurado_correo', 'label' => 'Correo Asegurado', 'type' => 'text', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'hogar_cb_notif_tomador', 'label' => '☑ Notificar Tomador', 'type' => 'checkbox', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'hogar_cb_notif_asegurado', 'label' => '☑ Notificar Asegurado', 'type' => 'checkbox', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'hogar_cb_notif_correo', 'label' => '☑ Notificar por Correo', 'type' => 'checkbox', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'hogar_cb_notif_residencia', 'label' => '☑ Notificar en Residencia', 'type' => 'checkbox', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'hogar_cb_notif_otro', 'label' => '☑ Notificar Otro Medio', 'type' => 'checkbox', 'section' => 'Datos Asegurado', 'source' => 'payload'],

    // === DATOS DE LA PROPIEDAD ===
    ['key' => 'hogar_prop_latitud', 'label' => 'Latitud Propiedad', 'type' => 'text', 'section' => 'Datos Propiedad', 'source' => 'payload'],
    ['key' => 'hogar_prop_longitud', 'label' => 'Longitud Propiedad', 'type' => 'text', 'section' => 'Datos Propiedad', 'source' => 'payload'],
    ['key' => 'hogar_cb_esquina_si', 'label' => '☑ En Esquina Sí', 'type' => 'checkbox', 'section' => 'Datos Propiedad', 'source' => 'payload'],
    ['key' => 'hogar_cb_esquina_no', 'label' => '☑ En Esquina No', 'type' => 'checkbox', 'section' => 'Datos Propiedad', 'source' => 'payload'],
    ['key' => 'hogar_prop_pais', 'label' => 'País Propiedad', 'type' => 'text', 'section' => 'Datos Propiedad', 'source' => 'payload'],
    ['key' => 'hogar_prop_provincia', 'label' => 'Provincia Propiedad', 'type' => 'text', 'section' => 'Datos Propiedad', 'source' => 'payload'],
    ['key' => 'hogar_prop_canton', 'label' => 'Cantón Propiedad', 'type' => 'text', 'section' => 'Datos Propiedad', 'source' => 'payload'],
    ['key' => 'hogar_prop_distrito', 'label' => 'Distrito Propiedad', 'type' => 'text', 'section' => 'Datos Propiedad', 'source' => 'payload'],
    ['key' => 'hogar_prop_urbanizacion', 'label' => 'Urbanización/Barrio', 'type' => 'text', 'section' => 'Datos Propiedad', 'source' => 'payload'],
    ['key' => 'hogar_cb_tipo_casa', 'label' => '☑ Casa Habitación', 'type' => 'checkbox', 'section' => 'Datos Propiedad', 'source' => 'payload'],
    ['key' => 'hogar_cb_tipo_edificio', 'label' => '☑ Edificio', 'type' => 'checkbox', 'section' => 'Datos Propiedad', 'source' => 'payload'],
    ['key' => 'hogar_cb_tipo_condominio', 'label' => '☑ Condominio', 'type' => 'checkbox', 'section' => 'Datos Propiedad', 'source' => 'payload'],
    ['key' => 'hogar_prop_otras_senas', 'label' => 'Otras Señas', 'type' => 'text', 'section' => 'Datos Propiedad', 'source' => 'payload'],
    ['key' => 'hogar_prop_folio_real', 'label' => 'N° Folio Real', 'type' => 'text', 'section' => 'Datos Propiedad', 'source' => 'payload'],

    // === CONSTRUCCIÓN ===
    ['key' => 'hogar_cb_ano_antes1974', 'label' => '☑ Antes 1974', 'type' => 'checkbox', 'section' => 'Construcción', 'source' => 'payload'],
    ['key' => 'hogar_cb_ano_1974_1985', 'label' => '☑ 1974-1985', 'type' => 'checkbox', 'section' => 'Construcción', 'source' => 'payload'],
    ['key' => 'hogar_cb_ano_1986_2001', 'label' => '☑ 1986-2001', 'type' => 'checkbox', 'section' => 'Construcción', 'source' => 'payload'],
    ['key' => 'hogar_cb_ano_2002_2009', 'label' => '☑ 2002-2009', 'type' => 'checkbox', 'section' => 'Construcción', 'source' => 'payload'],
    ['key' => 'hogar_cb_ano_2010_actual', 'label' => '☑ 2010-Actual', 'type' => 'checkbox', 'section' => 'Construcción', 'source' => 'payload'],
    ['key' => 'hogar_area_construccion', 'label' => 'Área Construcción m²', 'type' => 'text', 'section' => 'Construcción', 'source' => 'payload'],
    ['key' => 'hogar_cb_area_igual_si', 'label' => '☑ Área por Piso Igual Sí', 'type' => 'checkbox', 'section' => 'Construcción', 'source' => 'payload'],
    ['key' => 'hogar_cb_area_igual_no', 'label' => '☑ Área por Piso Igual No', 'type' => 'checkbox', 'section' => 'Construcción', 'source' => 'payload'],
    ['key' => 'hogar_cantidad_pisos', 'label' => 'Cantidad de Pisos', 'type' => 'text', 'section' => 'Construcción', 'source' => 'payload'],
    ['key' => 'hogar_piso_ubicacion', 'label' => 'En qué Piso se Ubica', 'type' => 'text', 'section' => 'Construcción', 'source' => 'payload'],

    // === SISTEMA ELÉCTRICO ===
    ['key' => 'hogar_cb_elect_entubado', 'label' => '☑ Entubado Totalmente', 'type' => 'checkbox', 'section' => 'Sist. Eléctrico', 'source' => 'fijo'],
    ['key' => 'hogar_cb_elect_parcial', 'label' => '☑ Entubado Parcialmente', 'type' => 'checkbox', 'section' => 'Sist. Eléctrico', 'source' => 'payload'],
    ['key' => 'hogar_cb_elect_cuchilla', 'label' => '☑ Cuchilla Principal', 'type' => 'checkbox', 'section' => 'Sist. Eléctrico', 'source' => 'fijo'],
    ['key' => 'hogar_cb_elect_breaker', 'label' => '☑ Breaker Principal', 'type' => 'checkbox', 'section' => 'Sist. Eléctrico', 'source' => 'fijo'],
    ['key' => 'hogar_cb_elect_caja', 'label' => '☑ Caja de Breaker', 'type' => 'checkbox', 'section' => 'Sist. Eléctrico', 'source' => 'fijo'],
    ['key' => 'hogar_cb_elect_polarizado', 'label' => '☑ Tomacorriente Polarizado', 'type' => 'checkbox', 'section' => 'Sist. Eléctrico', 'source' => 'fijo'],

    // ==================== PÁGINA 2 ====================
    // === ESTADO Y CONSERVACIÓN ===
    ['key' => 'hogar_cb_estado_optimo', 'label' => '☑ Estado Óptimo', 'type' => 'checkbox', 'section' => 'P2: Estado', 'source' => 'payload'],
    ['key' => 'hogar_cb_estado_muy_bueno', 'label' => '☑ Estado Muy Bueno', 'type' => 'checkbox', 'section' => 'P2: Estado', 'source' => 'payload'],
    ['key' => 'hogar_cb_estado_bueno', 'label' => '☑ Estado Bueno', 'type' => 'checkbox', 'section' => 'P2: Estado', 'source' => 'payload'],
    ['key' => 'hogar_cb_estado_regular', 'label' => '☑ Estado Regular', 'type' => 'checkbox', 'section' => 'P2: Estado', 'source' => 'payload'],
    ['key' => 'hogar_cb_estado_malo', 'label' => '☑ Estado Malo', 'type' => 'checkbox', 'section' => 'P2: Estado', 'source' => 'payload'],
    ['key' => 'hogar_cb_estado_muy_malo', 'label' => '☑ Estado Muy Malo', 'type' => 'checkbox', 'section' => 'P2: Estado', 'source' => 'payload'],
    ['key' => 'hogar_cb_modif_sobrepeso_si', 'label' => '☑ Modificaciones Sobrepeso Sí', 'type' => 'checkbox', 'section' => 'P2: Estado', 'source' => 'payload'],
    ['key' => 'hogar_cb_modif_sobrepeso_no', 'label' => '☑ Modificaciones Sobrepeso No', 'type' => 'checkbox', 'section' => 'P2: Estado', 'source' => 'fijo'],

    // === INTERÉS ASEGURABLE ===
    ['key' => 'hogar_cb_interes_propietario', 'label' => '☑ Propietario', 'type' => 'checkbox', 'section' => 'P2: Interés Asegurable', 'source' => 'fijo'],
    ['key' => 'hogar_cb_interes_arrendatario', 'label' => '☑ Arrendatario', 'type' => 'checkbox', 'section' => 'P2: Interés Asegurable', 'source' => 'payload'],
    ['key' => 'hogar_cb_interes_usufructuario', 'label' => '☑ Usufructuario', 'type' => 'checkbox', 'section' => 'P2: Interés Asegurable', 'source' => 'payload'],
    ['key' => 'hogar_cb_interes_depositario', 'label' => '☑ Depositario', 'type' => 'checkbox', 'section' => 'P2: Interés Asegurable', 'source' => 'payload'],
    ['key' => 'hogar_cb_interes_acreedor', 'label' => '☑ Acreedor', 'type' => 'checkbox', 'section' => 'P2: Interés Asegurable', 'source' => 'payload'],
    ['key' => 'hogar_cb_interes_consignatario', 'label' => '☑ Consignatario', 'type' => 'checkbox', 'section' => 'P2: Interés Asegurable', 'source' => 'payload'],
    ['key' => 'hogar_cb_interes_otro', 'label' => '☑ Otro Interés', 'type' => 'checkbox', 'section' => 'P2: Interés Asegurable', 'source' => 'payload'],
    ['key' => 'hogar_interes_otro_texto', 'label' => 'Especificar Otro Interés', 'type' => 'text', 'section' => 'P2: Interés Asegurable', 'source' => 'payload'],
    ['key' => 'hogar_actividad_inmueble', 'label' => 'Actividad en Inmueble', 'type' => 'text', 'section' => 'P2: Interés Asegurable', 'source' => 'payload'],
    ['key' => 'hogar_pct_casa_habitacion', 'label' => '% Casa Habitación', 'type' => 'text', 'section' => 'P2: Interés Asegurable', 'source' => 'payload'],
    ['key' => 'hogar_pct_otras_ocupaciones', 'label' => '% Otras Ocupaciones', 'type' => 'text', 'section' => 'P2: Interés Asegurable', 'source' => 'payload'],
    ['key' => 'hogar_detalle_actividad', 'label' => 'Detalle Actividad', 'type' => 'text', 'section' => 'P2: Interés Asegurable', 'source' => 'payload'],
    ['key' => 'hogar_cb_ocupado_propietario', 'label' => '☑ Ocupado por Propietario', 'type' => 'checkbox', 'section' => 'P2: Interés Asegurable', 'source' => 'fijo'],
    ['key' => 'hogar_cb_ocupado_inquilino', 'label' => '☑ Ocupado por Inquilino', 'type' => 'checkbox', 'section' => 'P2: Interés Asegurable', 'source' => 'payload'],
    ['key' => 'hogar_nombre_propietario', 'label' => 'Nombre Propietario Inmueble', 'type' => 'text', 'section' => 'P2: Interés Asegurable', 'source' => 'payload'],
    ['key' => 'hogar_cb_gas_si', 'label' => '☑ Usa Gas LP Sí', 'type' => 'checkbox', 'section' => 'P2: Interés Asegurable', 'source' => 'payload'],
    ['key' => 'hogar_cb_gas_no', 'label' => '☑ Usa Gas LP No', 'type' => 'checkbox', 'section' => 'P2: Interés Asegurable', 'source' => 'fijo'],
    ['key' => 'hogar_sustancias_inflamables', 'label' => 'Sustancias Inflamables (tipo/cantidad)', 'type' => 'text', 'section' => 'P2: Interés Asegurable', 'source' => 'payload'],

    // === CERCANÍA A CUERPOS DE AGUA ===
    ['key' => 'hogar_cb_cerca_rio', 'label' => '☑ Cerca de Río', 'type' => 'checkbox', 'section' => 'P2: Cercanía Agua', 'source' => 'payload'],
    ['key' => 'hogar_cb_cerca_pleamar', 'label' => '☑ Cerca Línea Pleamar', 'type' => 'checkbox', 'section' => 'P2: Cercanía Agua', 'source' => 'payload'],
    ['key' => 'hogar_cb_cerca_lago', 'label' => '☑ Cerca de Lago', 'type' => 'checkbox', 'section' => 'P2: Cercanía Agua', 'source' => 'payload'],
    ['key' => 'hogar_cb_cerca_talud', 'label' => '☑ Cerca de Talud', 'type' => 'checkbox', 'section' => 'P2: Cercanía Agua', 'source' => 'payload'],
    ['key' => 'hogar_cb_cerca_pendiente', 'label' => '☑ Cerca de Pendiente', 'type' => 'checkbox', 'section' => 'P2: Cercanía Agua', 'source' => 'payload'],
    ['key' => 'hogar_cb_cerca_otro_agua', 'label' => '☑ Otro Cuerpo de Agua', 'type' => 'checkbox', 'section' => 'P2: Cercanía Agua', 'source' => 'payload'],
    ['key' => 'hogar_cb_cerca_ninguna', 'label' => '☑ Ninguna de las Anteriores', 'type' => 'checkbox', 'section' => 'P2: Cercanía Agua', 'source' => 'fijo'],
    ['key' => 'hogar_cb_dist_0_5m', 'label' => '☑ Distancia 0-5m', 'type' => 'checkbox', 'section' => 'P2: Cercanía Agua', 'source' => 'payload'],
    ['key' => 'hogar_cb_dist_6_10m', 'label' => '☑ Distancia 6-10m', 'type' => 'checkbox', 'section' => 'P2: Cercanía Agua', 'source' => 'payload'],
    ['key' => 'hogar_cb_dist_11_20m', 'label' => '☑ Distancia 11-20m', 'type' => 'checkbox', 'section' => 'P2: Cercanía Agua', 'source' => 'payload'],
    ['key' => 'hogar_cb_dist_21_49m', 'label' => '☑ Distancia 21-49m', 'type' => 'checkbox', 'section' => 'P2: Cercanía Agua', 'source' => 'payload'],
    ['key' => 'hogar_cb_dist_50_100m', 'label' => '☑ Distancia 50-100m', 'type' => 'checkbox', 'section' => 'P2: Cercanía Agua', 'source' => 'payload'],
    ['key' => 'hogar_cb_dist_mas_100m', 'label' => '☑ Distancia +100m', 'type' => 'checkbox', 'section' => 'P2: Cercanía Agua', 'source' => 'payload'],

    // === COLINDANTES ===
    ['key' => 'hogar_colindante_norte', 'label' => 'Colindante Norte', 'type' => 'text', 'section' => 'P2: Colindantes', 'source' => 'payload'],
    ['key' => 'hogar_colindante_sur', 'label' => 'Colindante Sur', 'type' => 'text', 'section' => 'P2: Colindantes', 'source' => 'payload'],
    ['key' => 'hogar_colindante_este', 'label' => 'Colindante Este', 'type' => 'text', 'section' => 'P2: Colindantes', 'source' => 'payload'],
    ['key' => 'hogar_colindante_oeste', 'label' => 'Colindante Oeste', 'type' => 'text', 'section' => 'P2: Colindantes', 'source' => 'payload'],

    // === TIPO DE CONSTRUCCIÓN (E1-E14) ===
    ['key' => 'hogar_cb_const_e1', 'label' => '☑ E1 Mampostería Ladrillo', 'type' => 'checkbox', 'section' => 'P2: Tipo Construcción', 'source' => 'payload'],
    ['key' => 'hogar_cb_const_e2', 'label' => '☑ E2 Mampostería Block', 'type' => 'checkbox', 'section' => 'P2: Tipo Construcción', 'source' => 'payload'],
    ['key' => 'hogar_cb_const_e3', 'label' => '☑ E3 Concreto Reforzado', 'type' => 'checkbox', 'section' => 'P2: Tipo Construcción', 'source' => 'payload'],
    ['key' => 'hogar_cb_const_e4', 'label' => '☑ E4 Concreto Prefabricado', 'type' => 'checkbox', 'section' => 'P2: Tipo Construcción', 'source' => 'payload'],
    ['key' => 'hogar_cb_const_e5', 'label' => '☑ E5 Panelería Doble Forro', 'type' => 'checkbox', 'section' => 'P2: Tipo Construcción', 'source' => 'payload'],
    ['key' => 'hogar_cb_const_e6', 'label' => '☑ E6 Panelería Emparedado', 'type' => 'checkbox', 'section' => 'P2: Tipo Construcción', 'source' => 'payload'],
    ['key' => 'hogar_cb_const_e7', 'label' => '☑ E7 Madera', 'type' => 'checkbox', 'section' => 'P2: Tipo Construcción', 'source' => 'payload'],
    ['key' => 'hogar_cb_const_e8', 'label' => '☑ E8 Mixto Madera-Concreto', 'type' => 'checkbox', 'section' => 'P2: Tipo Construcción', 'source' => 'fijo'],
    ['key' => 'hogar_cb_const_e9', 'label' => '☑ E9 Marcos Concreto Muros Corte', 'type' => 'checkbox', 'section' => 'P2: Tipo Construcción', 'source' => 'payload'],
    ['key' => 'hogar_cb_const_e10', 'label' => '☑ E10 Marcos de Acero', 'type' => 'checkbox', 'section' => 'P2: Tipo Construcción', 'source' => 'payload'],
    ['key' => 'hogar_cb_const_e11', 'label' => '☑ E11 Estructuras Sobrepuestas', 'type' => 'checkbox', 'section' => 'P2: Tipo Construcción', 'source' => 'payload'],
    ['key' => 'hogar_cb_const_e12', 'label' => '☑ E12 Naves Mampostería', 'type' => 'checkbox', 'section' => 'P2: Tipo Construcción', 'source' => 'payload'],
    ['key' => 'hogar_cb_const_e13', 'label' => '☑ E13 Naves de Acero', 'type' => 'checkbox', 'section' => 'P2: Tipo Construcción', 'source' => 'payload'],
    ['key' => 'hogar_cb_const_e14', 'label' => '☑ E14 Naves Concreto Prefab.', 'type' => 'checkbox', 'section' => 'P2: Tipo Construcción', 'source' => 'payload'],

    // === MEDIDAS SEGURIDAD COB. Y (Robo) ===
    ['key' => 'hogar_cb_vigilancia_interna', 'label' => '☑ Vigilancia Interna', 'type' => 'checkbox', 'section' => 'P2: Seguridad Cob Y', 'source' => 'payload'],
    ['key' => 'hogar_cb_vigilancia_externa', 'label' => '☑ Vigilancia Externa', 'type' => 'checkbox', 'section' => 'P2: Seguridad Cob Y', 'source' => 'payload'],
    ['key' => 'hogar_cb_horario_diurno', 'label' => '☑ Horario Diurno', 'type' => 'checkbox', 'section' => 'P2: Seguridad Cob Y', 'source' => 'payload'],
    ['key' => 'hogar_cb_horario_nocturno', 'label' => '☑ Horario Nocturno', 'type' => 'checkbox', 'section' => 'P2: Seguridad Cob Y', 'source' => 'payload'],
    ['key' => 'hogar_cb_propiedad_sola_si', 'label' => '☑ Propiedad Sola Sí', 'type' => 'checkbox', 'section' => 'P2: Seguridad Cob Y', 'source' => 'payload'],
    ['key' => 'hogar_cb_propiedad_sola_no', 'label' => '☑ Propiedad Sola No', 'type' => 'checkbox', 'section' => 'P2: Seguridad Cob Y', 'source' => 'fijo'],
    ['key' => 'hogar_propiedad_sola_horas', 'label' => 'Cantidad Horas Sola', 'type' => 'text', 'section' => 'P2: Seguridad Cob Y', 'source' => 'payload'],
    ['key' => 'hogar_cb_alarma_no_tiene', 'label' => '☑ Alarma: No Tiene', 'type' => 'checkbox', 'section' => 'P2: Seguridad Cob Y', 'source' => 'payload'],
    ['key' => 'hogar_cb_alarma_magnetica', 'label' => '☑ Alarma Magnética', 'type' => 'checkbox', 'section' => 'P2: Seguridad Cob Y', 'source' => 'payload'],
    ['key' => 'hogar_cb_alarma_electronica', 'label' => '☑ Alarma Electrónica', 'type' => 'checkbox', 'section' => 'P2: Seguridad Cob Y', 'source' => 'payload'],
    ['key' => 'hogar_cb_alarma_central', 'label' => '☑ Conectada Central Seguridad', 'type' => 'checkbox', 'section' => 'P2: Seguridad Cob Y', 'source' => 'payload'],
    ['key' => 'hogar_cb_cctv_jardines', 'label' => '☑ CCTV en Jardines', 'type' => 'checkbox', 'section' => 'P2: Seguridad Cob Y', 'source' => 'payload'],
    ['key' => 'hogar_cb_luces_infrarrojas', 'label' => '☑ Luces Infrarrojas/Láser', 'type' => 'checkbox', 'section' => 'P2: Seguridad Cob Y', 'source' => 'payload'],
    ['key' => 'hogar_cb_llavin_sencillo', 'label' => '☑ Llavín Sencillo', 'type' => 'checkbox', 'section' => 'P2: Seguridad Cob Y', 'source' => 'payload'],
    ['key' => 'hogar_cb_llavin_doble_paso', 'label' => '☑ Llavín Doble Paso', 'type' => 'checkbox', 'section' => 'P2: Seguridad Cob Y', 'source' => 'fijo'],
    ['key' => 'hogar_cerradura_otro', 'label' => 'Cerradura Otro Tipo', 'type' => 'text', 'section' => 'P2: Seguridad Cob Y', 'source' => 'payload'],
    ['key' => 'hogar_cb_tapias_si', 'label' => '☑ Tiene Tapias Sí', 'type' => 'checkbox', 'section' => 'P2: Seguridad Cob Y', 'source' => 'payload'],
    ['key' => 'hogar_cb_tapias_no', 'label' => '☑ Tiene Tapias No', 'type' => 'checkbox', 'section' => 'P2: Seguridad Cob Y', 'source' => 'payload'],
    ['key' => 'hogar_tapias_altura', 'label' => 'Altura Tapias', 'type' => 'text', 'section' => 'P2: Seguridad Cob Y', 'source' => 'payload'],
    ['key' => 'hogar_tapias_material', 'label' => 'Material Tapias', 'type' => 'text', 'section' => 'P2: Seguridad Cob Y', 'source' => 'payload'],
    ['key' => 'hogar_cb_alambre_navaja', 'label' => '☑ Con Alambre Navaja', 'type' => 'checkbox', 'section' => 'P2: Seguridad Cob Y', 'source' => 'payload'],
    ['key' => 'hogar_cb_frente_muros', 'label' => '☑ Frente: Muros', 'type' => 'checkbox', 'section' => 'P2: Seguridad Cob Y', 'source' => 'payload'],
    ['key' => 'hogar_cb_frente_verjas', 'label' => '☑ Frente: Verjas', 'type' => 'checkbox', 'section' => 'P2: Seguridad Cob Y', 'source' => 'payload'],
    ['key' => 'hogar_frente_otro', 'label' => 'Frente: Otro', 'type' => 'text', 'section' => 'P2: Seguridad Cob Y', 'source' => 'payload'],
    ['key' => 'hogar_cb_ventana_frances', 'label' => '☑ Ventana Tipo Francés', 'type' => 'checkbox', 'section' => 'P2: Seguridad Cob Y', 'source' => 'payload'],
    ['key' => 'hogar_cb_ventana_corriente', 'label' => '☑ Ventana Corriente', 'type' => 'checkbox', 'section' => 'P2: Seguridad Cob Y', 'source' => 'payload'],
    ['key' => 'hogar_cb_ventana_celosias', 'label' => '☑ Ventana Con Celosías', 'type' => 'checkbox', 'section' => 'P2: Seguridad Cob Y', 'source' => 'payload'],
    ['key' => 'hogar_cb_ventana_verjas', 'label' => '☑ Ventana Verjas', 'type' => 'checkbox', 'section' => 'P2: Seguridad Cob Y', 'source' => 'fijo'],
    ['key' => 'hogar_cb_ventana_cortinas_metal', 'label' => '☑ Cortinas Metálicas', 'type' => 'checkbox', 'section' => 'P2: Seguridad Cob Y', 'source' => 'payload'],
    ['key' => 'hogar_cb_ventana_vidrio_seg', 'label' => '☑ Vidrio de Seguridad', 'type' => 'checkbox', 'section' => 'P2: Seguridad Cob Y', 'source' => 'payload'],
    ['key' => 'hogar_ventana_otro', 'label' => 'Ventana: Otro', 'type' => 'text', 'section' => 'P2: Seguridad Cob Y', 'source' => 'payload'],
    ['key' => 'hogar_cb_puerta_madera', 'label' => '☑ Puerta Madera', 'type' => 'checkbox', 'section' => 'P2: Seguridad Cob Y', 'source' => 'payload'],
    ['key' => 'hogar_cb_puerta_vidrio', 'label' => '☑ Puerta Vidrio', 'type' => 'checkbox', 'section' => 'P2: Seguridad Cob Y', 'source' => 'payload'],
    ['key' => 'hogar_cb_puerta_verjas', 'label' => '☑ Verjas o Anteportón', 'type' => 'checkbox', 'section' => 'P2: Seguridad Cob Y', 'source' => 'payload'],
    ['key' => 'hogar_cb_puerta_contrapuerta', 'label' => '☑ Contrapuerta', 'type' => 'checkbox', 'section' => 'P2: Seguridad Cob Y', 'source' => 'payload'],
    ['key' => 'hogar_cb_puerta_marco_seg', 'label' => '☑ Marco de Seguridad', 'type' => 'checkbox', 'section' => 'P2: Seguridad Cob Y', 'source' => 'payload'],
    ['key' => 'hogar_puerta_otro', 'label' => 'Puerta: Otro', 'type' => 'text', 'section' => 'P2: Seguridad Cob Y', 'source' => 'payload'],
    ['key' => 'hogar_otra_medida_seguridad', 'label' => 'Otra Medida de Seguridad', 'type' => 'text', 'section' => 'P2: Seguridad Cob Y', 'source' => 'payload'],

    // ==================== PÁGINA 3 ====================
    // === MEDIDAS SEGURIDAD INCENDIO ===
    ['key' => 'hogar_cb_proteccion_auto', 'label' => '☑ Protección Incendio Automático', 'type' => 'checkbox', 'section' => 'P3: Seguridad Incendio', 'source' => 'payload'],
    ['key' => 'hogar_cb_proteccion_manual', 'label' => '☑ Protección Incendio Manual', 'type' => 'checkbox', 'section' => 'P3: Seguridad Incendio', 'source' => 'payload'],
    ['key' => 'hogar_cb_codigo_electrico', 'label' => '☑ Implementación Código Eléctrico', 'type' => 'checkbox', 'section' => 'P3: Seguridad Incendio', 'source' => 'payload'],
    ['key' => 'hogar_cb_detectores_humo', 'label' => '☑ Detectores Humo/Llama/Térmicos', 'type' => 'checkbox', 'section' => 'P3: Seguridad Incendio', 'source' => 'payload'],
    ['key' => 'hogar_cb_alarma_sonora', 'label' => '☑ Alarma Sonora y/o Visual', 'type' => 'checkbox', 'section' => 'P3: Seguridad Incendio', 'source' => 'payload'],

    // === PRÁCTICAS SOSTENIBLES ===
    ['key' => 'hogar_cb_led_70', 'label' => '☑ LED >70% Residencia', 'type' => 'checkbox', 'section' => 'P3: Sostenibilidad', 'source' => 'fijo'],
    ['key' => 'hogar_cb_paneles_solares', 'label' => '☑ Paneles Solares', 'type' => 'checkbox', 'section' => 'P3: Sostenibilidad', 'source' => 'payload'],

    // === ACCIDENTES PERSONALES ===
    ['key' => 'hogar_ap_asegurado_nombre', 'label' => 'AP Asegurado: Nombre', 'type' => 'text', 'section' => 'P3: Accidentes Personales', 'source' => 'payload'],
    ['key' => 'hogar_ap_asegurado_cedula', 'label' => 'AP Asegurado: Cédula', 'type' => 'text', 'section' => 'P3: Accidentes Personales', 'source' => 'payload'],
    ['key' => 'hogar_ap_asegurado_fecha_nac', 'label' => 'AP Asegurado: Fecha Nac.', 'type' => 'text', 'section' => 'P3: Accidentes Personales', 'source' => 'payload'],
    ['key' => 'hogar_ap_asegurado_edad', 'label' => 'AP Asegurado: Edad', 'type' => 'text', 'section' => 'P3: Accidentes Personales', 'source' => 'payload'],
    ['key' => 'hogar_cb_ap_asegurado_zurdo_si', 'label' => '☑ AP Asegurado Zurdo Sí', 'type' => 'checkbox', 'section' => 'P3: Accidentes Personales', 'source' => 'payload'],
    ['key' => 'hogar_cb_ap_asegurado_zurdo_no', 'label' => '☑ AP Asegurado Zurdo No', 'type' => 'checkbox', 'section' => 'P3: Accidentes Personales', 'source' => 'payload'],
    ['key' => 'hogar_ap_asegurado_muerte', 'label' => 'AP Asegurado: Muerte Accidental', 'type' => 'text', 'section' => 'P3: Accidentes Personales', 'source' => 'payload'],
    ['key' => 'hogar_ap_asegurado_incapacidad', 'label' => 'AP Asegurado: Incapacidad', 'type' => 'text', 'section' => 'P3: Accidentes Personales', 'source' => 'payload'],
    ['key' => 'hogar_ap_asegurado_gastos_med', 'label' => 'AP Asegurado: Gastos Médicos', 'type' => 'text', 'section' => 'P3: Accidentes Personales', 'source' => 'payload'],
    ['key' => 'hogar_ap_conyuge_nombre', 'label' => 'AP Cónyuge: Nombre', 'type' => 'text', 'section' => 'P3: Accidentes Personales', 'source' => 'payload'],
    ['key' => 'hogar_ap_conyuge_cedula', 'label' => 'AP Cónyuge: Cédula', 'type' => 'text', 'section' => 'P3: Accidentes Personales', 'source' => 'payload'],
    ['key' => 'hogar_ap_conyuge_fecha_nac', 'label' => 'AP Cónyuge: Fecha Nac.', 'type' => 'text', 'section' => 'P3: Accidentes Personales', 'source' => 'payload'],
    ['key' => 'hogar_ap_hijo1_nombre', 'label' => 'AP Hijo 1: Nombre', 'type' => 'text', 'section' => 'P3: Accidentes Personales', 'source' => 'payload'],
    ['key' => 'hogar_ap_hijo1_cedula', 'label' => 'AP Hijo 1: Cédula', 'type' => 'text', 'section' => 'P3: Accidentes Personales', 'source' => 'payload'],
    ['key' => 'hogar_ap_hijo2_nombre', 'label' => 'AP Hijo 2: Nombre', 'type' => 'text', 'section' => 'P3: Accidentes Personales', 'source' => 'payload'],
    ['key' => 'hogar_ap_hijo2_cedula', 'label' => 'AP Hijo 2: Cédula', 'type' => 'text', 'section' => 'P3: Accidentes Personales', 'source' => 'payload'],
    ['key' => 'hogar_ap_indemnizacion_nombre', 'label' => 'Recibe Indemnización: Nombre', 'type' => 'text', 'section' => 'P3: Accidentes Personales', 'source' => 'payload'],
    ['key' => 'hogar_ap_defecto_nombre', 'label' => 'Defecto Físico: Nombre', 'type' => 'text', 'section' => 'P3: Accidentes Personales', 'source' => 'payload'],
    ['key' => 'hogar_ap_defecto_parte', 'label' => 'Defecto: Parte Afectada', 'type' => 'text', 'section' => 'P3: Accidentes Personales', 'source' => 'payload'],
    ['key' => 'hogar_ap_defecto_causa', 'label' => 'Defecto: Causa', 'type' => 'text', 'section' => 'P3: Accidentes Personales', 'source' => 'payload'],
    ['key' => 'hogar_ap_defecto_fecha', 'label' => 'Defecto: Fecha Suceso', 'type' => 'text', 'section' => 'P3: Accidentes Personales', 'source' => 'payload'],
    ['key' => 'hogar_ap_defecto_grado', 'label' => 'Defecto: Grado Pérdida', 'type' => 'text', 'section' => 'P3: Accidentes Personales', 'source' => 'payload'],

    // === COBERTURA RIESGOS DEL TRABAJO HOGAR ===
    ['key' => 'hogar_cb_rt_opcion1', 'label' => '☑ RT Opción 1: Un Trabajador', 'type' => 'checkbox', 'section' => 'P3: RT Hogar', 'source' => 'payload'],
    ['key' => 'hogar_cb_rt_opcion2', 'label' => '☑ RT Opción 2: Dos Trabajadores', 'type' => 'checkbox', 'section' => 'P3: RT Hogar', 'source' => 'payload'],
    ['key' => 'hogar_cb_rt_opcion3', 'label' => '☑ RT Opción 3: Tres o Más', 'type' => 'checkbox', 'section' => 'P3: RT Hogar', 'source' => 'payload'],
    ['key' => 'hogar_rt_trab1_nombre', 'label' => 'RT Trabajador 1: Nombre', 'type' => 'text', 'section' => 'P3: RT Hogar', 'source' => 'payload'],
    ['key' => 'hogar_rt_trab1_cedula', 'label' => 'RT Trabajador 1: Cédula', 'type' => 'text', 'section' => 'P3: RT Hogar', 'source' => 'payload'],
    ['key' => 'hogar_rt_trab1_ocupacion', 'label' => 'RT Trabajador 1: Ocupación', 'type' => 'text', 'section' => 'P3: RT Hogar', 'source' => 'payload'],
    ['key' => 'hogar_rt_trab2_nombre', 'label' => 'RT Trabajador 2: Nombre', 'type' => 'text', 'section' => 'P3: RT Hogar', 'source' => 'payload'],
    ['key' => 'hogar_rt_trab2_cedula', 'label' => 'RT Trabajador 2: Cédula', 'type' => 'text', 'section' => 'P3: RT Hogar', 'source' => 'payload'],
    ['key' => 'hogar_rt_trab2_ocupacion', 'label' => 'RT Trabajador 2: Ocupación', 'type' => 'text', 'section' => 'P3: RT Hogar', 'source' => 'payload'],

    // === RESPONSABILIDAD CIVIL ===
    ['key' => 'hogar_cb_gradas_antideslizante', 'label' => '☑ Gradas: Piso Antideslizante', 'type' => 'checkbox', 'section' => 'P3: Resp. Civil', 'source' => 'payload'],
    ['key' => 'hogar_cb_gradas_alfombras', 'label' => '☑ Gradas: Cubiertas Alfombras', 'type' => 'checkbox', 'section' => 'P3: Resp. Civil', 'source' => 'payload'],
    ['key' => 'hogar_cb_gradas_cintas', 'label' => '☑ Gradas: Cintas Antideslizantes', 'type' => 'checkbox', 'section' => 'P3: Resp. Civil', 'source' => 'payload'],
    ['key' => 'hogar_cb_gradas_pasamanos_esc', 'label' => '☑ Pasamanos en Escaleras', 'type' => 'checkbox', 'section' => 'P3: Resp. Civil', 'source' => 'payload'],
    ['key' => 'hogar_cb_gradas_pasamanos_desn', 'label' => '☑ Pasamanos en Desniveles', 'type' => 'checkbox', 'section' => 'P3: Resp. Civil', 'source' => 'payload'],
    ['key' => 'hogar_cb_piscina_antideslizante', 'label' => '☑ Piscina: Pisos Antideslizantes', 'type' => 'checkbox', 'section' => 'P3: Resp. Civil', 'source' => 'payload'],
    ['key' => 'hogar_cb_piscina_banos', 'label' => '☑ Piscina: Baños/Áreas Acceso', 'type' => 'checkbox', 'section' => 'P3: Resp. Civil', 'source' => 'payload'],
    ['key' => 'hogar_cb_piscina_areas_exp', 'label' => '☑ Piscina: Áreas Expuestas', 'type' => 'checkbox', 'section' => 'P3: Resp. Civil', 'source' => 'payload'],
    ['key' => 'hogar_cb_piscina_salvavidas', 'label' => '☑ Piscina: Salvavidas', 'type' => 'checkbox', 'section' => 'P3: Resp. Civil', 'source' => 'payload'],
    ['key' => 'hogar_cb_piscina_flotadores', 'label' => '☑ Piscina: Flotadores', 'type' => 'checkbox', 'section' => 'P3: Resp. Civil', 'source' => 'payload'],
    ['key' => 'hogar_piscina_otras_medidas', 'label' => 'Piscina: Otras Medidas', 'type' => 'text', 'section' => 'P3: Resp. Civil', 'source' => 'payload'],
    ['key' => 'hogar_cb_animales_si', 'label' => '☑ Posee Animales Sí', 'type' => 'checkbox', 'section' => 'P3: Resp. Civil', 'source' => 'payload'],
    ['key' => 'hogar_cb_animales_no', 'label' => '☑ Posee Animales No', 'type' => 'checkbox', 'section' => 'P3: Resp. Civil', 'source' => 'fijo'],
    ['key' => 'hogar_cb_animales_dentro_si', 'label' => '☑ Animales Dentro Predios Sí', 'type' => 'checkbox', 'section' => 'P3: Resp. Civil', 'source' => 'payload'],
    ['key' => 'hogar_cb_animales_dentro_no', 'label' => '☑ Animales Dentro Predios No', 'type' => 'checkbox', 'section' => 'P3: Resp. Civil', 'source' => 'payload'],
    ['key' => 'hogar_cb_animales_avisos_si', 'label' => '☑ Avisos Animales Sí', 'type' => 'checkbox', 'section' => 'P3: Resp. Civil', 'source' => 'payload'],
    ['key' => 'hogar_cb_animales_avisos_no', 'label' => '☑ Avisos Animales No', 'type' => 'checkbox', 'section' => 'P3: Resp. Civil', 'source' => 'payload'],
    ['key' => 'hogar_cb_animales_seguridad_si', 'label' => '☑ Animales Área Seguridad Sí', 'type' => 'checkbox', 'section' => 'P3: Resp. Civil', 'source' => 'payload'],
    ['key' => 'hogar_cb_animales_seguridad_no', 'label' => '☑ Animales Área Seguridad No', 'type' => 'checkbox', 'section' => 'P3: Resp. Civil', 'source' => 'payload'],

    // ==================== PÁGINA 4 ====================
    // === DESIGNACIÓN DE BENEFICIARIO ===
    ['key' => 'hogar_cb_benef_pj_nacional', 'label' => '☑ Benef. PJ Nacional', 'type' => 'checkbox', 'section' => 'P4: Beneficiario', 'source' => 'payload'],
    ['key' => 'hogar_cb_benef_pj_extranjera', 'label' => '☑ Benef. PJ Extranjera', 'type' => 'checkbox', 'section' => 'P4: Beneficiario', 'source' => 'payload'],
    ['key' => 'hogar_cb_benef_pj_gobierno', 'label' => '☑ Benef. Gobierno', 'type' => 'checkbox', 'section' => 'P4: Beneficiario', 'source' => 'payload'],
    ['key' => 'hogar_cb_benef_pj_autonoma', 'label' => '☑ Benef. Inst. Autónoma', 'type' => 'checkbox', 'section' => 'P4: Beneficiario', 'source' => 'payload'],
    ['key' => 'hogar_cb_benef_pf_cedula', 'label' => '☑ Benef. Cédula', 'type' => 'checkbox', 'section' => 'P4: Beneficiario', 'source' => 'payload'],
    ['key' => 'hogar_cb_benef_pf_dimex', 'label' => '☑ Benef. DIMEX', 'type' => 'checkbox', 'section' => 'P4: Beneficiario', 'source' => 'payload'],
    ['key' => 'hogar_cb_benef_pf_didi', 'label' => '☑ Benef. DIDI', 'type' => 'checkbox', 'section' => 'P4: Beneficiario', 'source' => 'payload'],
    ['key' => 'hogar_cb_benef_pf_pasaporte', 'label' => '☑ Benef. Pasaporte', 'type' => 'checkbox', 'section' => 'P4: Beneficiario', 'source' => 'payload'],
    ['key' => 'hogar_cb_benef_pf_otro', 'label' => '☑ Benef. Otro ID', 'type' => 'checkbox', 'section' => 'P4: Beneficiario', 'source' => 'payload'],
    ['key' => 'hogar_benef_num_id', 'label' => 'Beneficiario: N° ID', 'type' => 'text', 'section' => 'P4: Beneficiario', 'source' => 'payload'],
    ['key' => 'hogar_benef_nombre', 'label' => 'Beneficiario: Nombre', 'type' => 'text', 'section' => 'P4: Beneficiario', 'source' => 'payload'],
    ['key' => 'hogar_benef_parentesco', 'label' => 'Beneficiario: Parentesco', 'type' => 'text', 'section' => 'P4: Beneficiario', 'source' => 'payload'],
    ['key' => 'hogar_benef_porcentaje', 'label' => 'Beneficiario: Porcentaje', 'type' => 'text', 'section' => 'P4: Beneficiario', 'source' => 'payload'],

    // === DATOS DEL ACREEDOR ===
    ['key' => 'hogar_cb_acreedor_pj_nacional', 'label' => '☑ Acreedor PJ Nacional', 'type' => 'checkbox', 'section' => 'P4: Acreedor', 'source' => 'payload'],
    ['key' => 'hogar_cb_acreedor_pj_extranjera', 'label' => '☑ Acreedor PJ Extranjera', 'type' => 'checkbox', 'section' => 'P4: Acreedor', 'source' => 'payload'],
    ['key' => 'hogar_cb_acreedor_pj_gobierno', 'label' => '☑ Acreedor Gobierno', 'type' => 'checkbox', 'section' => 'P4: Acreedor', 'source' => 'payload'],
    ['key' => 'hogar_cb_acreedor_pj_autonoma', 'label' => '☑ Acreedor Inst. Autónoma', 'type' => 'checkbox', 'section' => 'P4: Acreedor', 'source' => 'payload'],
    ['key' => 'hogar_cb_acreedor_pf_cedula', 'label' => '☑ Acreedor Cédula', 'type' => 'checkbox', 'section' => 'P4: Acreedor', 'source' => 'payload'],
    ['key' => 'hogar_cb_acreedor_pf_dimex', 'label' => '☑ Acreedor DIMEX', 'type' => 'checkbox', 'section' => 'P4: Acreedor', 'source' => 'payload'],
    ['key' => 'hogar_cb_acreedor_pf_didi', 'label' => '☑ Acreedor DIDI', 'type' => 'checkbox', 'section' => 'P4: Acreedor', 'source' => 'payload'],
    ['key' => 'hogar_cb_acreedor_pf_pasaporte', 'label' => '☑ Acreedor Pasaporte', 'type' => 'checkbox', 'section' => 'P4: Acreedor', 'source' => 'payload'],
    ['key' => 'hogar_cb_acreedor_pf_otro', 'label' => '☑ Acreedor Otro ID', 'type' => 'checkbox', 'section' => 'P4: Acreedor', 'source' => 'payload'],
    ['key' => 'hogar_acreedor_num_id', 'label' => 'Acreedor: N° ID', 'type' => 'text', 'section' => 'P4: Acreedor', 'source' => 'payload'],
    ['key' => 'hogar_acreedor_nombre', 'label' => 'Acreedor: Nombre/Razón Social', 'type' => 'text', 'section' => 'P4: Acreedor', 'source' => 'payload'],
    ['key' => 'hogar_acreedor_monto', 'label' => 'Acreedor: Monto Acreencia', 'type' => 'text', 'section' => 'P4: Acreedor', 'source' => 'payload'],
    ['key' => 'hogar_acreedor_grado', 'label' => 'Acreedor: Grado Acreencia', 'type' => 'text', 'section' => 'P4: Acreedor', 'source' => 'payload'],

    // === DATOS DE LA PÓLIZA ===
    ['key' => 'hogar_vigencia_desde', 'label' => 'Vigencia Desde', 'type' => 'text', 'section' => 'P4: Datos Póliza', 'source' => 'sistema'],
    ['key' => 'hogar_vigencia_hasta', 'label' => 'Vigencia Hasta', 'type' => 'text', 'section' => 'P4: Datos Póliza', 'source' => 'sistema'],
    ['key' => 'hogar_cb_moneda_colones', 'label' => '☑ Moneda Colones', 'type' => 'checkbox', 'section' => 'P4: Datos Póliza', 'source' => 'fijo'],
    ['key' => 'hogar_cb_moneda_dolares', 'label' => '☑ Moneda Dólares', 'type' => 'checkbox', 'section' => 'P4: Datos Póliza', 'source' => 'payload'],
    ['key' => 'hogar_cb_pago_anual', 'label' => '☑ Pago Anual', 'type' => 'checkbox', 'section' => 'P4: Datos Póliza', 'source' => 'payload'],
    ['key' => 'hogar_cb_pago_cuatrimestral', 'label' => '☑ Pago Cuatrimestral', 'type' => 'checkbox', 'section' => 'P4: Datos Póliza', 'source' => 'payload'],
    ['key' => 'hogar_cb_pago_bimestral', 'label' => '☑ Pago Bimestral', 'type' => 'checkbox', 'section' => 'P4: Datos Póliza', 'source' => 'payload'],
    ['key' => 'hogar_cb_pago_semestral', 'label' => '☑ Pago Semestral', 'type' => 'checkbox', 'section' => 'P4: Datos Póliza', 'source' => 'fijo'],
    ['key' => 'hogar_cb_pago_trimestral', 'label' => '☑ Pago Trimestral', 'type' => 'checkbox', 'section' => 'P4: Datos Póliza', 'source' => 'payload'],
    ['key' => 'hogar_cb_pago_mensual', 'label' => '☑ Pago Mensual', 'type' => 'checkbox', 'section' => 'P4: Datos Póliza', 'source' => 'payload'],
    ['key' => 'hogar_cb_poliza_otra_si', 'label' => '☑ Póliza Otra Aseg. Sí', 'type' => 'checkbox', 'section' => 'P4: Datos Póliza', 'source' => 'payload'],
    ['key' => 'hogar_cb_poliza_otra_no', 'label' => '☑ Póliza Otra Aseg. No', 'type' => 'checkbox', 'section' => 'P4: Datos Póliza', 'source' => 'fijo'],
    ['key' => 'hogar_otra_aseguradora_nombre', 'label' => 'Otra Aseguradora: Nombre', 'type' => 'text', 'section' => 'P4: Datos Póliza', 'source' => 'payload'],
    ['key' => 'hogar_otra_aseguradora_poliza', 'label' => 'Otra Aseguradora: N° Póliza', 'type' => 'text', 'section' => 'P4: Datos Póliza', 'source' => 'payload'],
    ['key' => 'hogar_cb_aseg_cuenta_tercero', 'label' => '☑ Por Cuenta de Tercero', 'type' => 'checkbox', 'section' => 'P4: Datos Póliza', 'source' => 'payload'],
    ['key' => 'hogar_cb_aseg_cuenta_propia', 'label' => '☑ Por Cuenta Propia', 'type' => 'checkbox', 'section' => 'P4: Datos Póliza', 'source' => 'fijo'],
    ['key' => 'hogar_cb_via_cargo_auto', 'label' => '☑ Vía: Cargo Automático', 'type' => 'checkbox', 'section' => 'P4: Datos Póliza', 'source' => 'payload'],
    ['key' => 'hogar_cb_via_deduccion', 'label' => '☑ Vía: Deducción Mensual', 'type' => 'checkbox', 'section' => 'P4: Datos Póliza', 'source' => 'payload'],

    // === RUBROS ASEGURADOS ===
    ['key' => 'hogar_rubro_residencia_expuesto', 'label' => 'Residencia: Monto Expuesto', 'type' => 'text', 'section' => 'P4: Rubros', 'source' => 'payload'],
    ['key' => 'hogar_rubro_residencia_asegurado', 'label' => 'Residencia: Monto Asegurado', 'type' => 'text', 'section' => 'P4: Rubros', 'source' => 'payload'],
    ['key' => 'hogar_rubro_residencia_prima', 'label' => 'Residencia: Prima', 'type' => 'text', 'section' => 'P4: Rubros', 'source' => 'ins'],
    ['key' => 'hogar_rubro_prop_personal_expuesto', 'label' => 'Prop. Personal: Monto Expuesto', 'type' => 'text', 'section' => 'P4: Rubros', 'source' => 'payload'],
    ['key' => 'hogar_rubro_prop_personal_asegurado', 'label' => 'Prop. Personal: Monto Asegurado', 'type' => 'text', 'section' => 'P4: Rubros', 'source' => 'payload'],
    ['key' => 'hogar_rubro_prop_personal_prima', 'label' => 'Prop. Personal: Prima', 'type' => 'text', 'section' => 'P4: Rubros', 'source' => 'ins'],
    ['key' => 'hogar_rubro_joyeria_expuesto', 'label' => 'Joyería: Monto Expuesto', 'type' => 'text', 'section' => 'P4: Rubros', 'source' => 'payload'],
    ['key' => 'hogar_rubro_joyeria_asegurado', 'label' => 'Joyería: Monto Asegurado', 'type' => 'text', 'section' => 'P4: Rubros', 'source' => 'payload'],
    ['key' => 'hogar_rubro_joyeria_prima', 'label' => 'Joyería: Prima', 'type' => 'text', 'section' => 'P4: Rubros', 'source' => 'ins'],
    ['key' => 'hogar_rubro_arte_expuesto', 'label' => 'Obras Arte: Monto Expuesto', 'type' => 'text', 'section' => 'P4: Rubros', 'source' => 'payload'],
    ['key' => 'hogar_rubro_arte_asegurado', 'label' => 'Obras Arte: Monto Asegurado', 'type' => 'text', 'section' => 'P4: Rubros', 'source' => 'payload'],
    ['key' => 'hogar_rubro_arte_prima', 'label' => 'Obras Arte: Prima', 'type' => 'text', 'section' => 'P4: Rubros', 'source' => 'ins'],
    ['key' => 'hogar_rubro_rentas_asegurado', 'label' => 'Pérdida Rentas: Monto Asegurado', 'type' => 'text', 'section' => 'P4: Rubros', 'source' => 'payload'],
    ['key' => 'hogar_rubro_rentas_prima', 'label' => 'Pérdida Rentas: Prima', 'type' => 'text', 'section' => 'P4: Rubros', 'source' => 'ins'],
    ['key' => 'hogar_rubro_rc_asegurado', 'label' => 'Resp. Civil: Monto Asegurado', 'type' => 'text', 'section' => 'P4: Rubros', 'source' => 'payload'],
    ['key' => 'hogar_rubro_rc_prima', 'label' => 'Resp. Civil: Prima', 'type' => 'text', 'section' => 'P4: Rubros', 'source' => 'ins'],
    ['key' => 'hogar_cb_opcion_100', 'label' => '☑ Opción 100%', 'type' => 'checkbox', 'section' => 'P4: Rubros', 'source' => 'fijo'],
    ['key' => 'hogar_cb_opcion_coaseguro_80', 'label' => '☑ Coaseguro 80%', 'type' => 'checkbox', 'section' => 'P4: Rubros', 'source' => 'payload'],

    // === OBRAS COMPLEMENTARIAS ===
    ['key' => 'hogar_obra_bodegas_monto', 'label' => 'Bodegas: Monto', 'type' => 'text', 'section' => 'P4: Obras Compl.', 'source' => 'payload'],
    ['key' => 'hogar_obra_bodegas_area', 'label' => 'Bodegas: Área m²', 'type' => 'text', 'section' => 'P4: Obras Compl.', 'source' => 'payload'],
    ['key' => 'hogar_obra_piscinas_monto', 'label' => 'Piscinas: Monto', 'type' => 'text', 'section' => 'P4: Obras Compl.', 'source' => 'payload'],
    ['key' => 'hogar_obra_piscinas_area', 'label' => 'Piscinas: Área m²', 'type' => 'text', 'section' => 'P4: Obras Compl.', 'source' => 'payload'],
    ['key' => 'hogar_obra_tapias_monto', 'label' => 'Tapias: Monto', 'type' => 'text', 'section' => 'P4: Obras Compl.', 'source' => 'payload'],
    ['key' => 'hogar_obra_tapias_area', 'label' => 'Tapias: Área m²', 'type' => 'text', 'section' => 'P4: Obras Compl.', 'source' => 'payload'],
    ['key' => 'hogar_obra_garajes_monto', 'label' => 'Garajes: Monto', 'type' => 'text', 'section' => 'P4: Obras Compl.', 'source' => 'payload'],
    ['key' => 'hogar_obra_garajes_area', 'label' => 'Garajes: Área m²', 'type' => 'text', 'section' => 'P4: Obras Compl.', 'source' => 'payload'],
    ['key' => 'hogar_obra_otros', 'label' => 'Otros (especifique)', 'type' => 'text', 'section' => 'P4: Obras Compl.', 'source' => 'payload'],

    // === PRIMA TOTAL ===
    ['key' => 'hogar_prima_subtotal', 'label' => 'Prima', 'type' => 'text', 'section' => 'P4: Prima', 'source' => 'ins'],
    ['key' => 'hogar_prima_recargo', 'label' => 'Recargo Fraccionamiento', 'type' => 'text', 'section' => 'P4: Prima', 'source' => 'ins'],
    ['key' => 'hogar_prima_iva', 'label' => 'IVA', 'type' => 'text', 'section' => 'P4: Prima', 'source' => 'ins'],
    ['key' => 'hogar_prima_total', 'label' => 'Prima Total', 'type' => 'text', 'section' => 'P4: Prima', 'source' => 'ins'],
    ['key' => 'hogar_cb_valor_reposicion', 'label' => '☑ Condición: Valor Reposición', 'type' => 'checkbox', 'section' => 'P4: Prima', 'source' => 'fijo'],
    ['key' => 'hogar_cb_valor_real', 'label' => '☑ Condición: Valor Real Efectivo', 'type' => 'checkbox', 'section' => 'P4: Prima', 'source' => 'payload'],
    ['key' => 'hogar_cb_valor_otro', 'label' => '☑ Condición: Otro', 'type' => 'checkbox', 'section' => 'P4: Prima', 'source' => 'payload'],
    ['key' => 'hogar_valor_otro_texto', 'label' => 'Condición Otro: Especificar', 'type' => 'text', 'section' => 'P4: Prima', 'source' => 'payload'],

    // ==================== PÁGINA 5 ====================
    // === COBERTURAS BÁSICAS ===
    ['key' => 'hogar_cb_cob_v', 'label' => '☑ V: Daño Directo Bienes Inmuebles', 'type' => 'checkbox', 'section' => 'P5: Cob. Básicas', 'source' => 'fijo'],
    ['key' => 'hogar_cb_cob_y', 'label' => '☑ Y: Daño Directo Contenidos (robo)', 'type' => 'checkbox', 'section' => 'P5: Cob. Básicas', 'source' => 'payload'],
    ['key' => 'hogar_cb_cob_y_con_lista', 'label' => '☑ Y: Con Lista', 'type' => 'checkbox', 'section' => 'P5: Cob. Básicas', 'source' => 'payload'],
    ['key' => 'hogar_cb_cob_y_sin_lista', 'label' => '☑ Y: Sin Lista', 'type' => 'checkbox', 'section' => 'P5: Cob. Básicas', 'source' => 'payload'],
    ['key' => 'hogar_cb_cob_y_grupo_1a', 'label' => '☑ Y: Grupo 1A', 'type' => 'checkbox', 'section' => 'P5: Cob. Básicas', 'source' => 'payload'],
    ['key' => 'hogar_cb_cob_y_grupo_1b', 'label' => '☑ Y: Grupo 1B', 'type' => 'checkbox', 'section' => 'P5: Cob. Básicas', 'source' => 'payload'],
    ['key' => 'hogar_cb_cob_y_grupo_2', 'label' => '☑ Y: Grupo 2', 'type' => 'checkbox', 'section' => 'P5: Cob. Básicas', 'source' => 'payload'],
    ['key' => 'hogar_cb_cob_y_grupo_3', 'label' => '☑ Y: Grupo 3', 'type' => 'checkbox', 'section' => 'P5: Cob. Básicas', 'source' => 'payload'],
    ['key' => 'hogar_cb_cob_x', 'label' => '☑ X: Daño Directo Contenidos (sin robo)', 'type' => 'checkbox', 'section' => 'P5: Cob. Básicas', 'source' => 'payload'],
    ['key' => 'hogar_cb_cob_x_con_lista', 'label' => '☑ X: Con Lista', 'type' => 'checkbox', 'section' => 'P5: Cob. Básicas', 'source' => 'payload'],
    ['key' => 'hogar_cb_cob_x_sin_lista', 'label' => '☑ X: Sin Lista', 'type' => 'checkbox', 'section' => 'P5: Cob. Básicas', 'source' => 'payload'],

    // === COBERTURAS ADICIONALES ===
    ['key' => 'hogar_cb_cob_d', 'label' => '☑ D: Convulsiones Naturaleza', 'type' => 'checkbox', 'section' => 'P5: Cob. Adicionales', 'source' => 'fijo'],
    ['key' => 'hogar_cb_cob_d_particip_0', 'label' => '☑ D: Participación 0%', 'type' => 'checkbox', 'section' => 'P5: Cob. Adicionales', 'source' => 'payload'],
    ['key' => 'hogar_cb_cob_d_particip_10', 'label' => '☑ D: Participación 10%', 'type' => 'checkbox', 'section' => 'P5: Cob. Adicionales', 'source' => 'payload'],
    ['key' => 'hogar_cb_cob_d_particip_20', 'label' => '☑ D: Participación 20%', 'type' => 'checkbox', 'section' => 'P5: Cob. Adicionales', 'source' => 'payload'],
    ['key' => 'hogar_cb_cob_h', 'label' => '☑ H: Pérdida Rentas Arrendamiento', 'type' => 'checkbox', 'section' => 'P5: Cob. Adicionales', 'source' => 'payload'],
    ['key' => 'hogar_cb_cob_h_3meses', 'label' => '☑ H: Período 3 meses', 'type' => 'checkbox', 'section' => 'P5: Cob. Adicionales', 'source' => 'payload'],
    ['key' => 'hogar_cb_cob_h_4_6meses', 'label' => '☑ H: Período 4-6 meses', 'type' => 'checkbox', 'section' => 'P5: Cob. Adicionales', 'source' => 'payload'],
    ['key' => 'hogar_cb_cob_h_7_12meses', 'label' => '☑ H: Período 7-12 meses', 'type' => 'checkbox', 'section' => 'P5: Cob. Adicionales', 'source' => 'payload'],
    ['key' => 'hogar_cb_cob_k', 'label' => '☑ K: Responsabilidad Civil', 'type' => 'checkbox', 'section' => 'P5: Cob. Adicionales', 'source' => 'payload'],
    ['key' => 'hogar_cb_cob_m', 'label' => '☑ M: Riesgos del Trabajo Hogar', 'type' => 'checkbox', 'section' => 'P5: Cob. Adicionales', 'source' => 'payload'],
    ['key' => 'hogar_cb_cob_p', 'label' => '☑ P: Accidentes Personales', 'type' => 'checkbox', 'section' => 'P5: Cob. Adicionales', 'source' => 'payload'],
    ['key' => 'hogar_cb_cob_t', 'label' => '☑ T: Multiasistencia Hogar (gratuita)', 'type' => 'checkbox', 'section' => 'P5: Cob. Adicionales', 'source' => 'fijo'],
    ['key' => 'hogar_cb_cob_s', 'label' => '☑ S: Multiasistencia Extendida', 'type' => 'checkbox', 'section' => 'P5: Cob. Adicionales', 'source' => 'payload'],
    ['key' => 'hogar_cob_s_beneficiario', 'label' => 'S: Nombre Beneficiario PJ', 'type' => 'text', 'section' => 'P5: Cob. Adicionales', 'source' => 'payload'],

    // === PROTECCIÓN CONTRA INFLACIÓN ===
    ['key' => 'hogar_cb_pci_no_aplicar', 'label' => '☑ PCI: No Aplicar', 'type' => 'checkbox', 'section' => 'P5: Protección Inflación', 'source' => 'fijo'],

    // === DEDUCIBLES V (Bienes Inmuebles) ===
    ['key' => 'hogar_cb_ded_v_incendio_sin', 'label' => '☑ V Incendio: Sin Deducible', 'type' => 'checkbox', 'section' => 'P5: Deducibles V', 'source' => 'fijo'],
    ['key' => 'hogar_cb_ded_v_vientos_1pct', 'label' => '☑ V Vientos/Inund: 1% monto', 'type' => 'checkbox', 'section' => 'P5: Deducibles V', 'source' => 'fijo'],
    ['key' => 'hogar_ded_v_vientos_otro', 'label' => 'V Vientos/Inund: Otro', 'type' => 'text', 'section' => 'P5: Deducibles V', 'source' => 'payload'],
    ['key' => 'hogar_cb_ded_v_riesgos_fijo', 'label' => '☑ V Riesgos Varios: ¢62.500/$100', 'type' => 'checkbox', 'section' => 'P5: Deducibles V', 'source' => 'fijo'],
    ['key' => 'hogar_ded_v_riesgos_otro', 'label' => 'V Riesgos Varios: Otro', 'type' => 'text', 'section' => 'P5: Deducibles V', 'source' => 'payload'],

    // === DEDUCIBLES X/Y (Contenidos) ===
    ['key' => 'hogar_cb_ded_xy_incendio_sin', 'label' => '☑ X/Y Incendio: Sin Deducible', 'type' => 'checkbox', 'section' => 'P5: Deducibles X/Y', 'source' => 'fijo'],
    ['key' => 'hogar_cb_ded_xy_vientos_1pct', 'label' => '☑ X/Y Vientos: 1% monto', 'type' => 'checkbox', 'section' => 'P5: Deducibles X/Y', 'source' => 'fijo'],
    ['key' => 'hogar_ded_xy_vientos_otro', 'label' => 'X/Y Vientos: Otro', 'type' => 'text', 'section' => 'P5: Deducibles X/Y', 'source' => 'payload'],
    ['key' => 'hogar_cb_ded_y_robo_10pct', 'label' => '☑ Y Robo: 10% pérdida', 'type' => 'checkbox', 'section' => 'P5: Deducibles X/Y', 'source' => 'fijo'],
    ['key' => 'hogar_ded_y_robo_otro', 'label' => 'Y Robo: Otro', 'type' => 'text', 'section' => 'P5: Deducibles X/Y', 'source' => 'payload'],
    ['key' => 'hogar_cb_ded_xy_riesgos_fijo', 'label' => '☑ X/Y Riesgos: ¢62.500/$100', 'type' => 'checkbox', 'section' => 'P5: Deducibles X/Y', 'source' => 'fijo'],
    ['key' => 'hogar_ded_xy_riesgos_otro', 'label' => 'X/Y Riesgos: Otro', 'type' => 'text', 'section' => 'P5: Deducibles X/Y', 'source' => 'payload'],

    // === DEDUCIBLES D (Convulsiones) ===
    ['key' => 'hogar_cb_ded_d_1pct', 'label' => '☑ D: 1% monto afectado', 'type' => 'checkbox', 'section' => 'P5: Deducibles D', 'source' => 'fijo'],
    ['key' => 'hogar_ded_d_otro', 'label' => 'D: Otro', 'type' => 'text', 'section' => 'P5: Deducibles D', 'source' => 'payload'],

    // === DEDUCIBLES OTROS ===
    ['key' => 'hogar_cb_ded_h_3dias', 'label' => '☑ H: Mínimo 3 días', 'type' => 'checkbox', 'section' => 'P5: Deducibles Otros', 'source' => 'fijo'],
    ['key' => 'hogar_ded_h_otro', 'label' => 'H: Otro', 'type' => 'text', 'section' => 'P5: Deducibles Otros', 'source' => 'payload'],
    ['key' => 'hogar_cb_ded_k_sin', 'label' => '☑ K: Sin Deducible', 'type' => 'checkbox', 'section' => 'P5: Deducibles Otros', 'source' => 'fijo'],
    ['key' => 'hogar_cb_ded_m_sin', 'label' => '☑ M: Sin Deducible', 'type' => 'checkbox', 'section' => 'P5: Deducibles Otros', 'source' => 'fijo'],
    ['key' => 'hogar_cb_ded_p_10pct', 'label' => '☑ P: 10% gastos mín ¢20.000/$40', 'type' => 'checkbox', 'section' => 'P5: Deducibles Otros', 'source' => 'fijo'],
    ['key' => 'hogar_ded_p_otro', 'label' => 'P: Otro', 'type' => 'text', 'section' => 'P5: Deducibles Otros', 'source' => 'payload'],

    // ==================== PÁGINA 6 ====================
    // === FIRMAS ===
    ['key' => 'hogar_firma_tomador_nombre', 'label' => 'Firma Tomador: Nombre', 'type' => 'text', 'section' => 'P6: Firmas', 'source' => 'payload'],
    ['key' => 'hogar_firma_tomador_id', 'label' => 'Firma Tomador: N° ID', 'type' => 'text', 'section' => 'P6: Firmas', 'source' => 'payload'],
    ['key' => 'hogar_intermediario_nombre', 'label' => 'Intermediario: Nombre', 'type' => 'text', 'section' => 'P6: Firmas', 'source' => 'fijo'],
    ['key' => 'hogar_intermediario_numero', 'label' => 'Intermediario: Número', 'type' => 'text', 'section' => 'P6: Firmas', 'source' => 'fijo'],
    ['key' => 'hogar_sociedad_agencia', 'label' => 'Sociedad Agencia/Corredora', 'type' => 'text', 'section' => 'P6: Firmas', 'source' => 'fijo'],
    ['key' => 'hogar_riesgo_aceptado_por', 'label' => 'Riesgo Aceptado Por', 'type' => 'text', 'section' => 'P6: Firmas', 'source' => 'ins'],
    ['key' => 'hogar_revisado_por', 'label' => 'Revisado Por', 'type' => 'text', 'section' => 'P6: Firmas', 'source' => 'ins'],
];

// =====================================================
// CAMPOS DEL FORMULARIO INS - AUTOS (4 páginas)
// Basado en: AUTOS_FORMULARIO.pdf INS-F-1000417 09/2025
// =====================================================
$camposAutos = [
    // ==================== PÁGINA 1 ====================
    // === ENCABEZADO ===
    ['key' => 'autos_fecha_dd', 'label' => 'Fecha DD', 'type' => 'text', 'section' => 'Encabezado', 'source' => 'sistema'],
    ['key' => 'autos_fecha_mm', 'label' => 'Fecha MM', 'type' => 'text', 'section' => 'Encabezado', 'source' => 'sistema'],
    ['key' => 'autos_fecha_aa', 'label' => 'Fecha AA', 'type' => 'text', 'section' => 'Encabezado', 'source' => 'sistema'],
    ['key' => 'autos_lugar', 'label' => 'Lugar', 'type' => 'text', 'section' => 'Encabezado', 'source' => 'payload'],
    ['key' => 'autos_hora', 'label' => 'Hora', 'type' => 'text', 'section' => 'Encabezado', 'source' => 'sistema'],

    // === TIPO DE TRÁMITE ===
    ['key' => 'autos_cb_emision', 'label' => '☑ Emisión', 'type' => 'checkbox', 'section' => 'Tipo Trámite', 'source' => 'fijo'],
    ['key' => 'autos_cb_endoso', 'label' => '☑ Endoso', 'type' => 'checkbox', 'section' => 'Tipo Trámite', 'source' => 'payload'],
    ['key' => 'autos_num_poliza_colectiva', 'label' => 'N° Póliza Colectiva/Grupal', 'type' => 'text', 'section' => 'Tipo Trámite', 'source' => 'ins'],
    ['key' => 'autos_num_poliza_individual', 'label' => 'N° Póliza Individual', 'type' => 'text', 'section' => 'Tipo Trámite', 'source' => 'ins'],

    // === DATOS DEL TOMADOR ===
    ['key' => 'autos_tomador_nombre', 'label' => 'Nombre/Razón Social Tomador', 'type' => 'text', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'autos_cb_tomador_pj_nacional', 'label' => '☑ Tom. PJ Nacional', 'type' => 'checkbox', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'autos_cb_tomador_pj_gobierno', 'label' => '☑ Tom. Gobierno', 'type' => 'checkbox', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'autos_cb_tomador_pj_autonoma', 'label' => '☑ Tom. Inst. Autónoma', 'type' => 'checkbox', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'autos_cb_tomador_pj_extranjera', 'label' => '☑ Tom. PJ Extranjera', 'type' => 'checkbox', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'autos_cb_tomador_pf_cedula', 'label' => '☑ Tom. Cédula', 'type' => 'checkbox', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'autos_cb_tomador_pf_dimex', 'label' => '☑ Tom. DIMEX', 'type' => 'checkbox', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'autos_cb_tomador_pf_didi', 'label' => '☑ Tom. DIDI', 'type' => 'checkbox', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'autos_cb_tomador_pf_pasaporte', 'label' => '☑ Tom. Pasaporte', 'type' => 'checkbox', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'autos_cb_tomador_pf_otro', 'label' => '☑ Tom. Otro', 'type' => 'checkbox', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'autos_tomador_otro_tipo', 'label' => 'Tom. Otro Tipo ID', 'type' => 'text', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'autos_tomador_num_id', 'label' => 'N° ID/Cédula Tomador', 'type' => 'text', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'autos_tomador_correo', 'label' => 'Correo Tomador', 'type' => 'text', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'autos_tomador_domicilio', 'label' => 'Domicilio Tomador', 'type' => 'text', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'autos_tomador_provincia', 'label' => 'Provincia Tomador', 'type' => 'text', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'autos_tomador_canton', 'label' => 'Cantón Tomador', 'type' => 'text', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'autos_tomador_distrito', 'label' => 'Distrito Tomador', 'type' => 'text', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'autos_tomador_telefono', 'label' => 'Teléfono/Celular Tomador', 'type' => 'text', 'section' => 'Datos Tomador', 'source' => 'payload'],

    // === DATOS DEL ASEGURADO ===
    ['key' => 'autos_asegurado_nombre', 'label' => 'Nombre/Razón Social Asegurado', 'type' => 'text', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'autos_cb_asegurado_pj_nacional', 'label' => '☑ Aseg. PJ Nacional', 'type' => 'checkbox', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'autos_cb_asegurado_pj_gobierno', 'label' => '☑ Aseg. Gobierno', 'type' => 'checkbox', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'autos_cb_asegurado_pj_autonoma', 'label' => '☑ Aseg. Inst. Autónoma', 'type' => 'checkbox', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'autos_cb_asegurado_pj_extranjera', 'label' => '☑ Aseg. PJ Extranjera', 'type' => 'checkbox', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'autos_cb_asegurado_pf_cedula', 'label' => '☑ Aseg. Cédula', 'type' => 'checkbox', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'autos_cb_asegurado_pf_dimex', 'label' => '☑ Aseg. DIMEX', 'type' => 'checkbox', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'autos_cb_asegurado_pf_didi', 'label' => '☑ Aseg. DIDI', 'type' => 'checkbox', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'autos_cb_asegurado_pf_pasaporte', 'label' => '☑ Aseg. Pasaporte', 'type' => 'checkbox', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'autos_cb_asegurado_pf_otro', 'label' => '☑ Aseg. Otro', 'type' => 'checkbox', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'autos_asegurado_otro_tipo', 'label' => 'Aseg. Otro Tipo ID', 'type' => 'text', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'autos_asegurado_num_id', 'label' => 'N° ID/Cédula Asegurado', 'type' => 'text', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'autos_asegurado_correo', 'label' => 'Correo Asegurado', 'type' => 'text', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'autos_asegurado_domicilio', 'label' => 'Domicilio Asegurado', 'type' => 'text', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'autos_asegurado_provincia', 'label' => 'Provincia Asegurado', 'type' => 'text', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'autos_asegurado_canton', 'label' => 'Cantón Asegurado', 'type' => 'text', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'autos_asegurado_distrito', 'label' => 'Distrito Asegurado', 'type' => 'text', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'autos_asegurado_telefono', 'label' => 'Teléfono/Celular Asegurado', 'type' => 'text', 'section' => 'Datos Asegurado', 'source' => 'payload'],

    // === MEDIO DE NOTIFICACIÓN ===
    ['key' => 'autos_cb_notif_tomador', 'label' => '☑ Notificar Tomador', 'type' => 'checkbox', 'section' => 'Notificación', 'source' => 'payload'],
    ['key' => 'autos_cb_notif_asegurado', 'label' => '☑ Notificar Asegurado', 'type' => 'checkbox', 'section' => 'Notificación', 'source' => 'payload'],
    ['key' => 'autos_cb_notif_domicilio', 'label' => '☑ Medio: Domicilio', 'type' => 'checkbox', 'section' => 'Notificación', 'source' => 'payload'],
    ['key' => 'autos_cb_notif_telefono', 'label' => '☑ Medio: N° Telefónico', 'type' => 'checkbox', 'section' => 'Notificación', 'source' => 'payload'],
    ['key' => 'autos_cb_notif_correo', 'label' => '☑ Medio: Correo', 'type' => 'checkbox', 'section' => 'Notificación', 'source' => 'fijo'],
    ['key' => 'autos_notif_apartado', 'label' => 'Apartado Postal', 'type' => 'text', 'section' => 'Notificación', 'source' => 'payload'],
    ['key' => 'autos_notif_fax', 'label' => 'Fax', 'type' => 'text', 'section' => 'Notificación', 'source' => 'payload'],

    // === DATOS DEL RIESGO (VEHÍCULO) ===
    ['key' => 'autos_vehiculo_placa', 'label' => 'Placa', 'type' => 'text', 'section' => 'Datos Vehículo', 'source' => 'payload'],
    ['key' => 'autos_vehiculo_marca_modelo', 'label' => 'Marca, Modelo y Serie', 'type' => 'text', 'section' => 'Datos Vehículo', 'source' => 'payload'],
    ['key' => 'autos_vehiculo_combustible', 'label' => 'Combustible', 'type' => 'text', 'section' => 'Datos Vehículo', 'source' => 'payload'],
    ['key' => 'autos_vehiculo_ano', 'label' => 'Año', 'type' => 'text', 'section' => 'Datos Vehículo', 'source' => 'payload'],
    ['key' => 'autos_vehiculo_color', 'label' => 'Color', 'type' => 'text', 'section' => 'Datos Vehículo', 'source' => 'payload'],
    ['key' => 'autos_vehiculo_peso_bruto', 'label' => 'Peso Bruto', 'type' => 'text', 'section' => 'Datos Vehículo', 'source' => 'payload'],
    ['key' => 'autos_vehiculo_cilindraje', 'label' => 'Cilindraje', 'type' => 'text', 'section' => 'Datos Vehículo', 'source' => 'payload'],
    ['key' => 'autos_vehiculo_capacidad', 'label' => 'Capacidad', 'type' => 'text', 'section' => 'Datos Vehículo', 'source' => 'payload'],
    ['key' => 'autos_vehiculo_vin', 'label' => 'N° Chasis/VIN', 'type' => 'text', 'section' => 'Datos Vehículo', 'source' => 'payload'],
    ['key' => 'autos_vehiculo_motor', 'label' => 'N° Motor', 'type' => 'text', 'section' => 'Datos Vehículo', 'source' => 'payload'],
    ['key' => 'autos_vehiculo_tipo', 'label' => 'Tipo de Vehículo', 'type' => 'text', 'section' => 'Datos Vehículo', 'source' => 'payload'],

    // === CARGA (si aplica) ===
    ['key' => 'autos_cb_carga_combustible', 'label' => '☑ Carga: Combustible', 'type' => 'checkbox', 'section' => 'Carga', 'source' => 'payload'],
    ['key' => 'autos_cb_carga_construccion', 'label' => '☑ Carga: Mat. Construcción', 'type' => 'checkbox', 'section' => 'Carga', 'source' => 'payload'],
    ['key' => 'autos_cb_carga_gas', 'label' => '☑ Carga: Gas Licuado', 'type' => 'checkbox', 'section' => 'Carga', 'source' => 'payload'],
    ['key' => 'autos_cb_carga_animales', 'label' => '☑ Carga: Animales', 'type' => 'checkbox', 'section' => 'Carga', 'source' => 'payload'],
    ['key' => 'autos_cb_carga_liquidos', 'label' => '☑ Carga: Líquidos', 'type' => 'checkbox', 'section' => 'Carga', 'source' => 'payload'],
    ['key' => 'autos_cb_carga_madera', 'label' => '☑ Carga: Madera', 'type' => 'checkbox', 'section' => 'Carga', 'source' => 'payload'],

    // === USO DEL VEHÍCULO ===
    ['key' => 'autos_cb_uso_personal', 'label' => '☑ Uso Personal', 'type' => 'checkbox', 'section' => 'Uso Vehículo', 'source' => 'fijo'],
    ['key' => 'autos_cb_uso_personal_comercial', 'label' => '☑ Uso Personal-Comercial', 'type' => 'checkbox', 'section' => 'Uso Vehículo', 'source' => 'payload'],
    ['key' => 'autos_cb_uso_comercial', 'label' => '☑ Uso Comercial', 'type' => 'checkbox', 'section' => 'Uso Vehículo', 'source' => 'payload'],
    ['key' => 'autos_cb_uso_alquiler', 'label' => '☑ Uso Alquiler', 'type' => 'checkbox', 'section' => 'Uso Vehículo', 'source' => 'payload'],
    ['key' => 'autos_uso_especificar', 'label' => 'Especifique Uso', 'type' => 'text', 'section' => 'Uso Vehículo', 'source' => 'payload'],

    // === RUTA BUS/MICROBÚS ===
    ['key' => 'autos_cb_ruta_nacional', 'label' => '☑ Ruta Nacional', 'type' => 'checkbox', 'section' => 'Ruta Bus', 'source' => 'payload'],
    ['key' => 'autos_cb_ruta_internacional', 'label' => '☑ Ruta Internacional', 'type' => 'checkbox', 'section' => 'Ruta Bus', 'source' => 'payload'],
    ['key' => 'autos_cb_ruta_no_remunerado', 'label' => '☑ No Remunerado', 'type' => 'checkbox', 'section' => 'Ruta Bus', 'source' => 'payload'],

    // === VALOR VEHÍCULO ===
    ['key' => 'autos_valor_vehiculo_colones', 'label' => 'Valor Vehículo ₡', 'type' => 'text', 'section' => 'Valor', 'source' => 'payload'],
    ['key' => 'autos_valor_vehiculo_dolares', 'label' => 'Valor Vehículo $', 'type' => 'text', 'section' => 'Valor', 'source' => 'payload'],
    ['key' => 'autos_cb_actualizacion_si', 'label' => '☑ Actualización Monto Sí', 'type' => 'checkbox', 'section' => 'Valor', 'source' => 'payload'],
    ['key' => 'autos_cb_actualizacion_no', 'label' => '☑ Actualización Monto No', 'type' => 'checkbox', 'section' => 'Valor', 'source' => 'fijo'],

    // === CARACTERÍSTICAS ESPECIALES ===
    ['key' => 'autos_cb_modificado_si', 'label' => '☑ Vehículo Modificado Sí', 'type' => 'checkbox', 'section' => 'Especiales', 'source' => 'payload'],
    ['key' => 'autos_cb_modificado_no', 'label' => '☑ Vehículo Modificado No', 'type' => 'checkbox', 'section' => 'Especiales', 'source' => 'fijo'],
    ['key' => 'autos_cb_exonerado_si', 'label' => '☑ Exonerado Impuestos Sí', 'type' => 'checkbox', 'section' => 'Especiales', 'source' => 'payload'],
    ['key' => 'autos_cb_exonerado_no', 'label' => '☑ Exonerado Impuestos No', 'type' => 'checkbox', 'section' => 'Especiales', 'source' => 'fijo'],
    ['key' => 'autos_cb_extraprima_si', 'label' => '☑ Extraprima Repuestos Sí', 'type' => 'checkbox', 'section' => 'Especiales', 'source' => 'payload'],
    ['key' => 'autos_cb_extraprima_no', 'label' => '☑ Extraprima Repuestos No', 'type' => 'checkbox', 'section' => 'Especiales', 'source' => 'fijo'],

    // === INTERÉS ASEGURABLE ===
    ['key' => 'autos_cb_interes_accionista', 'label' => '☑ Accionista (Propietario)', 'type' => 'checkbox', 'section' => 'Interés Asegurable', 'source' => 'payload'],
    ['key' => 'autos_cb_interes_propietario', 'label' => '☑ Propietario', 'type' => 'checkbox', 'section' => 'Interés Asegurable', 'source' => 'fijo'],
    ['key' => 'autos_cb_interes_conyuge', 'label' => '☑ Cónyuge', 'type' => 'checkbox', 'section' => 'Interés Asegurable', 'source' => 'payload'],
    ['key' => 'autos_cb_interes_arrendatario', 'label' => '☑ Arrendatario', 'type' => 'checkbox', 'section' => 'Interés Asegurable', 'source' => 'payload'],
    ['key' => 'autos_cb_interes_depositario', 'label' => '☑ Depositario Judicial', 'type' => 'checkbox', 'section' => 'Interés Asegurable', 'source' => 'payload'],
    ['key' => 'autos_cb_interes_acreedor', 'label' => '☑ Acreedor Prendario', 'type' => 'checkbox', 'section' => 'Interés Asegurable', 'source' => 'payload'],
    ['key' => 'autos_cb_interes_comodatario', 'label' => '☑ Comodatario', 'type' => 'checkbox', 'section' => 'Interés Asegurable', 'source' => 'payload'],
    ['key' => 'autos_cb_interes_otro', 'label' => '☑ Otro Interés', 'type' => 'checkbox', 'section' => 'Interés Asegurable', 'source' => 'payload'],
    ['key' => 'autos_interes_otro_texto', 'label' => 'Especificar Otro', 'type' => 'text', 'section' => 'Interés Asegurable', 'source' => 'payload'],

    // === ACREEDOR PRENDARIO ===
    ['key' => 'autos_acreedor_nombre', 'label' => 'Acreedor Prendario', 'type' => 'text', 'section' => 'Acreedor', 'source' => 'payload'],
    ['key' => 'autos_acreedor_id', 'label' => 'Identificación Acreedor', 'type' => 'text', 'section' => 'Acreedor', 'source' => 'payload'],
    ['key' => 'autos_acreedor_tipo_id', 'label' => 'Tipo ID Acreedor', 'type' => 'text', 'section' => 'Acreedor', 'source' => 'payload'],
    ['key' => 'autos_acreedor_monto', 'label' => 'Monto Acreencia', 'type' => 'text', 'section' => 'Acreedor', 'source' => 'payload'],
    ['key' => 'autos_acreedor_porcentaje', 'label' => 'Porcentaje Acreencia', 'type' => 'text', 'section' => 'Acreedor', 'source' => 'payload'],

    // === CONDUCTOR HABITUAL ===
    ['key' => 'autos_conductor_habitual', 'label' => 'Conductor Habitual', 'type' => 'text', 'section' => 'Conductor', 'source' => 'payload'],
    ['key' => 'autos_conductor_id', 'label' => 'Identificación Conductor', 'type' => 'text', 'section' => 'Conductor', 'source' => 'payload'],

    // === FORMAS DE ASEGURAMIENTO ===
    ['key' => 'autos_cb_valor_declarado', 'label' => '☑ Valor Declarado', 'type' => 'checkbox', 'section' => 'Aseguramiento', 'source' => 'payload'],
    ['key' => 'autos_cb_primer_riesgo', 'label' => '☑ Primer Riesgo Absoluto', 'type' => 'checkbox', 'section' => 'Aseguramiento', 'source' => 'payload'],
    ['key' => 'autos_cb_valor_convenido', 'label' => '☑ Valor Convenido', 'type' => 'checkbox', 'section' => 'Aseguramiento', 'source' => 'payload'],

    // ==================== PÁGINA 2 ====================
    // === PLAZO DE VIGENCIA ===
    ['key' => 'autos_vigencia_desde', 'label' => 'Vigencia Desde', 'type' => 'text', 'section' => 'Vigencia', 'source' => 'sistema'],
    ['key' => 'autos_vigencia_hasta', 'label' => 'Vigencia Hasta', 'type' => 'text', 'section' => 'Vigencia', 'source' => 'sistema'],
    ['key' => 'autos_cb_plazo_anual', 'label' => '☑ Plazo Anual', 'type' => 'checkbox', 'section' => 'Vigencia', 'source' => 'payload'],
    ['key' => 'autos_cb_plazo_corto', 'label' => '☑ Corto Plazo', 'type' => 'checkbox', 'section' => 'Vigencia', 'source' => 'payload'],

    // === FORMA DE PAGO ===
    ['key' => 'autos_cb_pago_anual', 'label' => '☑ Pago Anual', 'type' => 'checkbox', 'section' => 'Forma Pago', 'source' => 'payload'],
    ['key' => 'autos_cb_pago_semestral', 'label' => '☑ Pago Semestral', 'type' => 'checkbox', 'section' => 'Forma Pago', 'source' => 'fijo'],
    ['key' => 'autos_cb_pago_trimestral', 'label' => '☑ Pago Trimestral', 'type' => 'checkbox', 'section' => 'Forma Pago', 'source' => 'payload'],
    ['key' => 'autos_cb_pago_mensual', 'label' => '☑ Pago Mensual', 'type' => 'checkbox', 'section' => 'Forma Pago', 'source' => 'payload'],
    ['key' => 'autos_cb_cobro_automatico', 'label' => '☑ Cargo Automático', 'type' => 'checkbox', 'section' => 'Forma Pago', 'source' => 'payload'],
    ['key' => 'autos_cb_deduccion_mensual', 'label' => '☑ Deducción Mensual', 'type' => 'checkbox', 'section' => 'Forma Pago', 'source' => 'payload'],

    // === COBERTURAS (Códigos INS) ===
    ['key' => 'autos_cb_cob_a', 'label' => '☑ A: RC Lesión/Muerte', 'type' => 'checkbox', 'section' => 'Coberturas', 'source' => 'fijo'],
    ['key' => 'autos_cob_a_monto', 'label' => 'A: Monto Asegurado', 'type' => 'text', 'section' => 'Coberturas', 'source' => 'payload'],
    ['key' => 'autos_cob_a_prima', 'label' => 'A: Prima', 'type' => 'text', 'section' => 'Coberturas', 'source' => 'ins'],
    ['key' => 'autos_cb_cob_b', 'label' => '☑ B: Servicios Médicos', 'type' => 'checkbox', 'section' => 'Coberturas', 'source' => 'payload'],
    ['key' => 'autos_cob_b_monto', 'label' => 'B: Monto Asegurado', 'type' => 'text', 'section' => 'Coberturas', 'source' => 'payload'],
    ['key' => 'autos_cob_b_prima', 'label' => 'B: Prima', 'type' => 'text', 'section' => 'Coberturas', 'source' => 'ins'],
    ['key' => 'autos_cb_cob_c', 'label' => '☑ C: RC Daños Propiedad', 'type' => 'checkbox', 'section' => 'Coberturas', 'source' => 'fijo'],
    ['key' => 'autos_cob_c_monto', 'label' => 'C: Monto Asegurado', 'type' => 'text', 'section' => 'Coberturas', 'source' => 'payload'],
    ['key' => 'autos_cob_c_prima', 'label' => 'C: Prima', 'type' => 'text', 'section' => 'Coberturas', 'source' => 'ins'],
    ['key' => 'autos_cb_cob_d', 'label' => '☑ D: Colisión/Vuelco', 'type' => 'checkbox', 'section' => 'Coberturas', 'source' => 'payload'],
    ['key' => 'autos_cob_d_prima', 'label' => 'D: Prima', 'type' => 'text', 'section' => 'Coberturas', 'source' => 'ins'],
    ['key' => 'autos_cb_cob_e', 'label' => '☑ E: Gastos Legales', 'type' => 'checkbox', 'section' => 'Coberturas', 'source' => 'fijo'],
    ['key' => 'autos_cob_e_prima', 'label' => 'E: Prima', 'type' => 'text', 'section' => 'Coberturas', 'source' => 'ins'],
    ['key' => 'autos_cb_cob_f', 'label' => '☑ F: Robo/Hurto', 'type' => 'checkbox', 'section' => 'Coberturas', 'source' => 'payload'],
    ['key' => 'autos_cob_f_prima', 'label' => 'F: Prima', 'type' => 'text', 'section' => 'Coberturas', 'source' => 'ins'],
    ['key' => 'autos_cb_dispositivo_si', 'label' => '☑ Dispositivo Seguridad Sí', 'type' => 'checkbox', 'section' => 'Coberturas', 'source' => 'payload'],
    ['key' => 'autos_cb_dispositivo_no', 'label' => '☑ Dispositivo Seguridad No', 'type' => 'checkbox', 'section' => 'Coberturas', 'source' => 'fijo'],
    ['key' => 'autos_dispositivo_tipo', 'label' => 'Tipo Dispositivo', 'type' => 'text', 'section' => 'Coberturas', 'source' => 'payload'],
    ['key' => 'autos_dispositivo_descuento', 'label' => 'Descuento Aplicable', 'type' => 'text', 'section' => 'Coberturas', 'source' => 'ins'],
    ['key' => 'autos_cb_cob_g', 'label' => '☑ G: Multiasistencia Auto', 'type' => 'checkbox', 'section' => 'Coberturas', 'source' => 'fijo'],
    ['key' => 'autos_cob_g_prima', 'label' => 'G: Prima', 'type' => 'text', 'section' => 'Coberturas', 'source' => 'ins'],
    ['key' => 'autos_cb_cob_h', 'label' => '☑ H: Riesgos Adicionales', 'type' => 'checkbox', 'section' => 'Coberturas', 'source' => 'payload'],
    ['key' => 'autos_cob_h_prima', 'label' => 'H: Prima', 'type' => 'text', 'section' => 'Coberturas', 'source' => 'ins'],
    ['key' => 'autos_cb_cob_j', 'label' => '☑ J: Pérdida Objetos', 'type' => 'checkbox', 'section' => 'Coberturas', 'source' => 'payload'],
    ['key' => 'autos_cob_j_monto', 'label' => 'J: Monto Evento', 'type' => 'text', 'section' => 'Coberturas', 'source' => 'payload'],
    ['key' => 'autos_cob_j_prima', 'label' => 'J: Prima', 'type' => 'text', 'section' => 'Coberturas', 'source' => 'ins'],
    ['key' => 'autos_cb_cob_k', 'label' => '☑ K: Transporte Alternativo', 'type' => 'checkbox', 'section' => 'Coberturas', 'source' => 'payload'],
    ['key' => 'autos_cob_k_dias', 'label' => 'K: Días Asegurados', 'type' => 'text', 'section' => 'Coberturas', 'source' => 'payload'],
    ['key' => 'autos_cob_k_monto_dia', 'label' => 'K: Monto por Día', 'type' => 'text', 'section' => 'Coberturas', 'source' => 'payload'],
    ['key' => 'autos_cob_k_prima', 'label' => 'K: Prima', 'type' => 'text', 'section' => 'Coberturas', 'source' => 'ins'],
    ['key' => 'autos_cb_cob_m', 'label' => '☑ M: Multiasistencia Ext.', 'type' => 'checkbox', 'section' => 'Coberturas', 'source' => 'payload'],
    ['key' => 'autos_cob_m_prima', 'label' => 'M: Prima', 'type' => 'text', 'section' => 'Coberturas', 'source' => 'ins'],
    ['key' => 'autos_cb_cob_n', 'label' => '☑ N: Exención Deducible', 'type' => 'checkbox', 'section' => 'Coberturas', 'source' => 'payload'],
    ['key' => 'autos_cb_cob_n_c', 'label' => '☑ N para Cob. C', 'type' => 'checkbox', 'section' => 'Coberturas', 'source' => 'payload'],
    ['key' => 'autos_cb_cob_n_d', 'label' => '☑ N para Cob. D', 'type' => 'checkbox', 'section' => 'Coberturas', 'source' => 'payload'],
    ['key' => 'autos_cb_cob_n_f', 'label' => '☑ N para Cob. F', 'type' => 'checkbox', 'section' => 'Coberturas', 'source' => 'payload'],
    ['key' => 'autos_cb_cob_n_h', 'label' => '☑ N para Cob. H', 'type' => 'checkbox', 'section' => 'Coberturas', 'source' => 'payload'],
    ['key' => 'autos_cob_n_prima', 'label' => 'N: Prima', 'type' => 'text', 'section' => 'Coberturas', 'source' => 'ins'],
    ['key' => 'autos_cb_cob_p', 'label' => '☑ P: Gastos Médicos Ocupantes', 'type' => 'checkbox', 'section' => 'Coberturas', 'source' => 'payload'],
    ['key' => 'autos_cob_p_monto', 'label' => 'P: Monto Asegurado', 'type' => 'text', 'section' => 'Coberturas', 'source' => 'payload'],
    ['key' => 'autos_cob_p_prima', 'label' => 'P: Prima', 'type' => 'text', 'section' => 'Coberturas', 'source' => 'ins'],

    // === BENEFICIARIOS ===
    ['key' => 'autos_beneficiario1_nombre', 'label' => 'Beneficiario 1 Nombre', 'type' => 'text', 'section' => 'Beneficiarios', 'source' => 'payload'],
    ['key' => 'autos_beneficiario1_id', 'label' => 'Beneficiario 1 ID', 'type' => 'text', 'section' => 'Beneficiarios', 'source' => 'payload'],
    ['key' => 'autos_beneficiario1_tipo_id', 'label' => 'Beneficiario 1 Tipo ID', 'type' => 'text', 'section' => 'Beneficiarios', 'source' => 'payload'],
    ['key' => 'autos_beneficiario1_porcentaje', 'label' => 'Beneficiario 1 %', 'type' => 'text', 'section' => 'Beneficiarios', 'source' => 'payload'],

    // ==================== PÁGINA 3 ====================
    // === COBERTURAS ADICIONALES ===
    ['key' => 'autos_cb_cob_y', 'label' => '☑ Y: Extraterritorialidad', 'type' => 'checkbox', 'section' => 'Cob. Adicionales', 'source' => 'payload'],
    ['key' => 'autos_cb_y_permanente', 'label' => '☑ Y Permanente', 'type' => 'checkbox', 'section' => 'Cob. Adicionales', 'source' => 'payload'],
    ['key' => 'autos_cb_y_temporal', 'label' => '☑ Y Temporal', 'type' => 'checkbox', 'section' => 'Cob. Adicionales', 'source' => 'payload'],
    ['key' => 'autos_y_destino', 'label' => 'Y: Destino', 'type' => 'text', 'section' => 'Cob. Adicionales', 'source' => 'payload'],
    ['key' => 'autos_y_desde', 'label' => 'Y: Desde', 'type' => 'text', 'section' => 'Cob. Adicionales', 'source' => 'payload'],
    ['key' => 'autos_y_hasta', 'label' => 'Y: Hasta', 'type' => 'text', 'section' => 'Cob. Adicionales', 'source' => 'payload'],
    ['key' => 'autos_cob_y_prima', 'label' => 'Y: Prima', 'type' => 'text', 'section' => 'Cob. Adicionales', 'source' => 'ins'],
    ['key' => 'autos_cb_cob_z', 'label' => '☑ Z: Riesgos Particulares', 'type' => 'checkbox', 'section' => 'Cob. Adicionales', 'source' => 'payload'],
    ['key' => 'autos_cob_z_prima', 'label' => 'Z: Prima', 'type' => 'text', 'section' => 'Cob. Adicionales', 'source' => 'ins'],
    ['key' => 'autos_cb_cob_idd', 'label' => '☑ IDD: Indemnización Deducible', 'type' => 'checkbox', 'section' => 'Cob. Adicionales', 'source' => 'payload'],
    ['key' => 'autos_cob_idd_monto', 'label' => 'IDD: Monto', 'type' => 'text', 'section' => 'Cob. Adicionales', 'source' => 'payload'],
    ['key' => 'autos_cob_idd_prima', 'label' => 'IDD: Prima', 'type' => 'text', 'section' => 'Cob. Adicionales', 'source' => 'ins'],
    ['key' => 'autos_cb_cob_idp', 'label' => '☑ IDP: Indemnización Ded. Plus', 'type' => 'checkbox', 'section' => 'Cob. Adicionales', 'source' => 'payload'],
    ['key' => 'autos_cob_idp_monto', 'label' => 'IDP: Monto', 'type' => 'text', 'section' => 'Cob. Adicionales', 'source' => 'payload'],
    ['key' => 'autos_cob_idp_prima', 'label' => 'IDP: Prima', 'type' => 'text', 'section' => 'Cob. Adicionales', 'source' => 'ins'],

    // === OTROS BIENES Y RIESGOS ===
    ['key' => 'autos_cb_rc_alcohol', 'label' => '☑ RC Bajo Alcohol', 'type' => 'checkbox', 'section' => 'Otros Riesgos', 'source' => 'payload'],
    ['key' => 'autos_cb_blindaje', 'label' => '☑ Blindaje', 'type' => 'checkbox', 'section' => 'Otros Riesgos', 'source' => 'payload'],
    ['key' => 'autos_blindaje_monto', 'label' => 'Blindaje: Monto', 'type' => 'text', 'section' => 'Otros Riesgos', 'source' => 'payload'],
    ['key' => 'autos_cb_acople', 'label' => '☑ Acople Vehículos', 'type' => 'checkbox', 'section' => 'Otros Riesgos', 'source' => 'payload'],
    ['key' => 'autos_acople_valor', 'label' => 'Valor Remolcado', 'type' => 'text', 'section' => 'Otros Riesgos', 'source' => 'payload'],
    ['key' => 'autos_cb_equipo_especial', 'label' => '☑ Equipo Especial', 'type' => 'checkbox', 'section' => 'Otros Riesgos', 'source' => 'payload'],
    ['key' => 'autos_equipo_especial_monto', 'label' => 'Equipo Especial: Monto', 'type' => 'text', 'section' => 'Otros Riesgos', 'source' => 'payload'],
    ['key' => 'autos_cb_proteccion_flotilla', 'label' => '☑ Protección Flotilla', 'type' => 'checkbox', 'section' => 'Otros Riesgos', 'source' => 'payload'],

    // === PRIMA DEL SEGURO ===
    ['key' => 'autos_prima_subtotal', 'label' => 'Sub Total', 'type' => 'text', 'section' => 'Prima', 'source' => 'ins'],
    ['key' => 'autos_prima_experiencia', 'label' => 'Experiencia Siniestral', 'type' => 'text', 'section' => 'Prima', 'source' => 'ins'],
    ['key' => 'autos_prima_otro', 'label' => 'Otro (aclare)', 'type' => 'text', 'section' => 'Prima', 'source' => 'ins'],
    ['key' => 'autos_prima_iva', 'label' => 'I.V.A.', 'type' => 'text', 'section' => 'Prima', 'source' => 'ins'],
    ['key' => 'autos_prima_total', 'label' => 'Total', 'type' => 'text', 'section' => 'Prima', 'source' => 'ins'],

    // === OBSERVACIONES ===
    ['key' => 'autos_observaciones', 'label' => 'Observaciones', 'type' => 'text', 'section' => 'Observaciones', 'source' => 'payload'],
    ['key' => 'autos_cb_seguro_otra_si', 'label' => '☑ Seguro Otra Aseg. Sí', 'type' => 'checkbox', 'section' => 'Observaciones', 'source' => 'payload'],
    ['key' => 'autos_cb_seguro_otra_no', 'label' => '☑ Seguro Otra Aseg. No', 'type' => 'checkbox', 'section' => 'Observaciones', 'source' => 'fijo'],
    ['key' => 'autos_otra_aseguradora', 'label' => '¿Cuál Compañía?', 'type' => 'text', 'section' => 'Observaciones', 'source' => 'payload'],
    ['key' => 'autos_cb_fotos_si', 'label' => '☑ Fotos Adjuntas Sí', 'type' => 'checkbox', 'section' => 'Observaciones', 'source' => 'payload'],
    ['key' => 'autos_cb_fotos_no', 'label' => '☑ Fotos Adjuntas No', 'type' => 'checkbox', 'section' => 'Observaciones', 'source' => 'payload'],
    ['key' => 'autos_cb_fotos_no_requiere', 'label' => '☑ No se Requieren Fotos', 'type' => 'checkbox', 'section' => 'Observaciones', 'source' => 'fijo'],
    ['key' => 'autos_consecutivo_web', 'label' => 'N° Consecutivo Web', 'type' => 'text', 'section' => 'Observaciones', 'source' => 'sistema'],

    // ==================== PÁGINA 4 ====================
    // === FIRMAS ===
    ['key' => 'autos_firma_tomador_nombre', 'label' => 'Firma Tomador: Nombre', 'type' => 'text', 'section' => 'Firmas', 'source' => 'payload'],
    ['key' => 'autos_firma_tomador_id', 'label' => 'Firma Tomador: N° ID', 'type' => 'text', 'section' => 'Firmas', 'source' => 'payload'],
    ['key' => 'autos_firma_tomador_cargo', 'label' => 'Firma Tomador: Cargo PJ', 'type' => 'text', 'section' => 'Firmas', 'source' => 'payload'],
    ['key' => 'autos_firma_asegurado_nombre', 'label' => 'Firma Asegurado: Nombre', 'type' => 'text', 'section' => 'Firmas', 'source' => 'payload'],
    ['key' => 'autos_firma_asegurado_id', 'label' => 'Firma Asegurado: N° ID', 'type' => 'text', 'section' => 'Firmas', 'source' => 'payload'],
    ['key' => 'autos_firma_asegurado_cargo', 'label' => 'Firma Asegurado: Cargo PJ', 'type' => 'text', 'section' => 'Firmas', 'source' => 'payload'],
    ['key' => 'autos_intermediario_nombre', 'label' => 'Intermediario: Nombre', 'type' => 'text', 'section' => 'Firmas', 'source' => 'fijo'],
    ['key' => 'autos_intermediario_codigo', 'label' => 'Intermediario: Código', 'type' => 'text', 'section' => 'Firmas', 'source' => 'fijo'],
    ['key' => 'autos_intermediario_fecha', 'label' => 'Intermediario: Fecha', 'type' => 'text', 'section' => 'Firmas', 'source' => 'sistema'],
    ['key' => 'autos_acople_valor', 'label' => 'Acople Valor', 'type' => 'text', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_acreedor_id', 'label' => 'Acreedor Id', 'type' => 'text', 'section' => 'Acreedores', 'source' => 'payload'],
    ['key' => 'autos_acreedor_monto', 'label' => 'Acreedor Monto', 'type' => 'text', 'section' => 'Acreedores', 'source' => 'payload'],
    ['key' => 'autos_acreedor_nombre', 'label' => 'Acreedor Nombre', 'type' => 'text', 'section' => 'Acreedores', 'source' => 'payload'],
    ['key' => 'autos_acreedor_porcentaje', 'label' => 'Acreedor Porcentaje', 'type' => 'text', 'section' => 'Acreedores', 'source' => 'payload'],
    ['key' => 'autos_acreedor_tipo_id', 'label' => 'Acreedor Tipo Id', 'type' => 'text', 'section' => 'Acreedores', 'source' => 'payload'],
    ['key' => 'autos_asegurado_canton', 'label' => 'Asegurado Canton', 'type' => 'text', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'autos_asegurado_correo', 'label' => 'Asegurado Correo', 'type' => 'text', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'autos_asegurado_distrito', 'label' => 'Asegurado Distrito', 'type' => 'text', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'autos_asegurado_domicilio', 'label' => 'Asegurado Domicilio', 'type' => 'text', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'autos_asegurado_nombre', 'label' => 'Asegurado Nombre', 'type' => 'text', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'autos_asegurado_num_id', 'label' => 'Asegurado N° Id', 'type' => 'text', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'autos_asegurado_otro_tipo', 'label' => 'Asegurado Otro Tipo', 'type' => 'text', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'autos_asegurado_provincia', 'label' => 'Asegurado Provincia', 'type' => 'text', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'autos_asegurado_telefono', 'label' => 'Asegurado Telefono', 'type' => 'text', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'autos_beneficiario1_id', 'label' => 'Beneficiario1 Id', 'type' => 'text', 'section' => 'Beneficiarios', 'source' => 'payload'],
    ['key' => 'autos_beneficiario1_nombre', 'label' => 'Beneficiario1 Nombre', 'type' => 'text', 'section' => 'Beneficiarios', 'source' => 'payload'],
    ['key' => 'autos_beneficiario1_porcentaje', 'label' => 'Beneficiario1 Porcentaje', 'type' => 'text', 'section' => 'Beneficiarios', 'source' => 'payload'],
    ['key' => 'autos_beneficiario1_tipo_id', 'label' => 'Beneficiario1 Tipo Id', 'type' => 'text', 'section' => 'Beneficiarios', 'source' => 'payload'],
    ['key' => 'autos_blindaje_monto', 'label' => 'Blindaje Monto', 'type' => 'text', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_cb_acople', 'label' => '☑ Acople', 'type' => 'checkbox', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_cb_actualizacion_si', 'label' => '☑ Actualizacion Si', 'type' => 'checkbox', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_cb_asegurado_pf_cedula', 'label' => '☑ Asegurado Pf Cedula', 'type' => 'checkbox', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'autos_cb_asegurado_pf_didi', 'label' => '☑ Asegurado Pf Didi', 'type' => 'checkbox', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'autos_cb_asegurado_pf_dimex', 'label' => '☑ Asegurado Pf Dimex', 'type' => 'checkbox', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'autos_cb_asegurado_pf_otro', 'label' => '☑ Asegurado Pf Otro', 'type' => 'checkbox', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'autos_cb_asegurado_pf_pasaporte', 'label' => '☑ Asegurado Pf Pasaporte', 'type' => 'checkbox', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'autos_cb_asegurado_pj_autonoma', 'label' => '☑ Asegurado Pj Autonoma', 'type' => 'checkbox', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'autos_cb_asegurado_pj_extranjera', 'label' => '☑ Asegurado Pj Extranjera', 'type' => 'checkbox', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'autos_cb_asegurado_pj_gobierno', 'label' => '☑ Asegurado Pj Gobierno', 'type' => 'checkbox', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'autos_cb_asegurado_pj_nacional', 'label' => '☑ Asegurado Pj Nacional', 'type' => 'checkbox', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'autos_cb_blindaje', 'label' => '☑ Blindaje', 'type' => 'checkbox', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_cb_carga_animales', 'label' => '☑ Carga Animales', 'type' => 'checkbox', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_cb_carga_combustible', 'label' => '☑ Carga Combustible', 'type' => 'checkbox', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_cb_carga_construccion', 'label' => '☑ Carga Construccion', 'type' => 'checkbox', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_cb_carga_gas', 'label' => '☑ Carga Gas', 'type' => 'checkbox', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_cb_carga_liquidos', 'label' => '☑ Carga Liquidos', 'type' => 'checkbox', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_cb_carga_madera', 'label' => '☑ Carga Madera', 'type' => 'checkbox', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_cb_cob_a', 'label' => '☑ Cob A', 'type' => 'checkbox', 'section' => 'Coberturas', 'source' => 'payload'],
    ['key' => 'autos_cb_cob_b', 'label' => '☑ Cob B', 'type' => 'checkbox', 'section' => 'Coberturas', 'source' => 'payload'],
    ['key' => 'autos_cb_cob_c', 'label' => '☑ Cob C', 'type' => 'checkbox', 'section' => 'Coberturas', 'source' => 'payload'],
    ['key' => 'autos_cb_cob_d', 'label' => '☑ Cob D', 'type' => 'checkbox', 'section' => 'Coberturas', 'source' => 'payload'],
    ['key' => 'autos_cb_cob_e', 'label' => '☑ Cob E', 'type' => 'checkbox', 'section' => 'Coberturas', 'source' => 'payload'],
    ['key' => 'autos_cb_cob_f', 'label' => '☑ Cob F', 'type' => 'checkbox', 'section' => 'Coberturas', 'source' => 'payload'],
    ['key' => 'autos_cb_cob_g', 'label' => '☑ Cob G', 'type' => 'checkbox', 'section' => 'Coberturas', 'source' => 'payload'],
    ['key' => 'autos_cb_cob_h', 'label' => '☑ Cob H', 'type' => 'checkbox', 'section' => 'Coberturas', 'source' => 'payload'],
    ['key' => 'autos_cb_cob_idd', 'label' => '☑ Cob Idd', 'type' => 'checkbox', 'section' => 'Coberturas', 'source' => 'payload'],
    ['key' => 'autos_cb_cob_idp', 'label' => '☑ Cob Idp', 'type' => 'checkbox', 'section' => 'Coberturas', 'source' => 'payload'],
    ['key' => 'autos_cb_cob_j', 'label' => '☑ Cob J', 'type' => 'checkbox', 'section' => 'Coberturas', 'source' => 'payload'],
    ['key' => 'autos_cb_cob_k', 'label' => '☑ Cob K', 'type' => 'checkbox', 'section' => 'Coberturas', 'source' => 'payload'],
    ['key' => 'autos_cb_cob_m', 'label' => '☑ Cob M', 'type' => 'checkbox', 'section' => 'Coberturas', 'source' => 'payload'],
    ['key' => 'autos_cb_cob_n', 'label' => '☑ Cob N', 'type' => 'checkbox', 'section' => 'Coberturas', 'source' => 'payload'],
    ['key' => 'autos_cb_cob_n_c', 'label' => '☑ Cob N C', 'type' => 'checkbox', 'section' => 'Coberturas', 'source' => 'payload'],
    ['key' => 'autos_cb_cob_n_d', 'label' => '☑ Cob N D', 'type' => 'checkbox', 'section' => 'Coberturas', 'source' => 'payload'],
    ['key' => 'autos_cb_cob_n_f', 'label' => '☑ Cob N F', 'type' => 'checkbox', 'section' => 'Coberturas', 'source' => 'payload'],
    ['key' => 'autos_cb_cob_n_h', 'label' => '☑ Cob N H', 'type' => 'checkbox', 'section' => 'Coberturas', 'source' => 'payload'],
    ['key' => 'autos_cb_cob_p', 'label' => '☑ Cob P', 'type' => 'checkbox', 'section' => 'Coberturas', 'source' => 'payload'],
    ['key' => 'autos_cb_cob_y', 'label' => '☑ Cob Y', 'type' => 'checkbox', 'section' => 'Coberturas', 'source' => 'payload'],
    ['key' => 'autos_cb_cob_z', 'label' => '☑ Cob Z', 'type' => 'checkbox', 'section' => 'Coberturas', 'source' => 'payload'],
    ['key' => 'autos_cb_cobro_automatico', 'label' => '☑ Cobro Automatico', 'type' => 'checkbox', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_cb_deduccion_mensual', 'label' => '☑ Deduccion Mensual', 'type' => 'checkbox', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_cb_dispositivo_si', 'label' => '☑ Dispositivo Si', 'type' => 'checkbox', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_cb_equipo_especial', 'label' => '☑ Equipo Especial', 'type' => 'checkbox', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_cb_exonerado_si', 'label' => '☑ Exonerado Si', 'type' => 'checkbox', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_cb_extraprima_si', 'label' => '☑ Extraprima Si', 'type' => 'checkbox', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_cb_fotos_no', 'label' => '☑ Fotos No', 'type' => 'checkbox', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_cb_fotos_si', 'label' => '☑ Fotos Si', 'type' => 'checkbox', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_cb_interes_accionista', 'label' => '☑ Interes Accionista', 'type' => 'checkbox', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_cb_interes_acreedor', 'label' => '☑ Interes Acreedor', 'type' => 'checkbox', 'section' => 'Acreedores', 'source' => 'payload'],
    ['key' => 'autos_cb_interes_arrendatario', 'label' => '☑ Interes Arrendatario', 'type' => 'checkbox', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_cb_interes_comodatario', 'label' => '☑ Interes Comodatario', 'type' => 'checkbox', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_cb_interes_conyuge', 'label' => '☑ Interes Conyuge', 'type' => 'checkbox', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_cb_interes_depositario', 'label' => '☑ Interes Depositario', 'type' => 'checkbox', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_cb_interes_otro', 'label' => '☑ Interes Otro', 'type' => 'checkbox', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_cb_interes_propietario', 'label' => '☑ Interes Propietario', 'type' => 'checkbox', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_cb_modificado_si', 'label' => '☑ Modificado Si', 'type' => 'checkbox', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_cb_notif_asegurado', 'label' => '☑ Notif Asegurado', 'type' => 'checkbox', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'autos_cb_notif_correo', 'label' => '☑ Notif Correo', 'type' => 'checkbox', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_cb_notif_domicilio', 'label' => '☑ Notif Domicilio', 'type' => 'checkbox', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_cb_notif_telefono', 'label' => '☑ Notif Telefono', 'type' => 'checkbox', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_cb_notif_tomador', 'label' => '☑ Notif Tomador', 'type' => 'checkbox', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'autos_cb_pago_anual', 'label' => '☑ Pago Anual', 'type' => 'checkbox', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_cb_pago_mensual', 'label' => '☑ Pago Mensual', 'type' => 'checkbox', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_cb_pago_semestral', 'label' => '☑ Pago Semestral', 'type' => 'checkbox', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_cb_pago_trimestral', 'label' => '☑ Pago Trimestral', 'type' => 'checkbox', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_cb_plazo_anual', 'label' => '☑ Plazo Anual', 'type' => 'checkbox', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_cb_plazo_corto', 'label' => '☑ Plazo Corto', 'type' => 'checkbox', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_cb_primer_riesgo', 'label' => '☑ Primer Riesgo', 'type' => 'checkbox', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_cb_proteccion_flotilla', 'label' => '☑ Proteccion Flotilla', 'type' => 'checkbox', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_cb_rc_alcohol', 'label' => '☑ Rc Alcohol', 'type' => 'checkbox', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_cb_ruta_internacional', 'label' => '☑ Ruta Internacional', 'type' => 'checkbox', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_cb_ruta_nacional', 'label' => '☑ Ruta Nacional', 'type' => 'checkbox', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_cb_ruta_no_remunerado', 'label' => '☑ Ruta No Remunerado', 'type' => 'checkbox', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_cb_seguro_otra_si', 'label' => '☑ Seguro Otra Si', 'type' => 'checkbox', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_cb_tomador_pf_cedula', 'label' => '☑ Tomador Pf Cedula', 'type' => 'checkbox', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'autos_cb_tomador_pf_didi', 'label' => '☑ Tomador Pf Didi', 'type' => 'checkbox', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'autos_cb_tomador_pf_dimex', 'label' => '☑ Tomador Pf Dimex', 'type' => 'checkbox', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'autos_cb_tomador_pf_otro', 'label' => '☑ Tomador Pf Otro', 'type' => 'checkbox', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'autos_cb_tomador_pf_pasaporte', 'label' => '☑ Tomador Pf Pasaporte', 'type' => 'checkbox', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'autos_cb_tomador_pj_autonoma', 'label' => '☑ Tomador Pj Autonoma', 'type' => 'checkbox', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'autos_cb_tomador_pj_extranjera', 'label' => '☑ Tomador Pj Extranjera', 'type' => 'checkbox', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'autos_cb_tomador_pj_gobierno', 'label' => '☑ Tomador Pj Gobierno', 'type' => 'checkbox', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'autos_cb_tomador_pj_nacional', 'label' => '☑ Tomador Pj Nacional', 'type' => 'checkbox', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'autos_cb_uso_alquiler', 'label' => '☑ Uso Alquiler', 'type' => 'checkbox', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_cb_uso_comercial', 'label' => '☑ Uso Comercial', 'type' => 'checkbox', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_cb_uso_personal', 'label' => '☑ Uso Personal', 'type' => 'checkbox', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_cb_uso_personal_comercial', 'label' => '☑ Uso Personal Comercial', 'type' => 'checkbox', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_cb_valor_convenido', 'label' => '☑ Valor Convenido', 'type' => 'checkbox', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_cb_valor_declarado', 'label' => '☑ Valor Declarado', 'type' => 'checkbox', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_cb_y_permanente', 'label' => '☑ Y Permanente', 'type' => 'checkbox', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_cb_y_temporal', 'label' => '☑ Y Temporal', 'type' => 'checkbox', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_cob_a_monto', 'label' => 'Cob A Monto', 'type' => 'text', 'section' => 'Coberturas', 'source' => 'payload'],
    ['key' => 'autos_cob_b_monto', 'label' => 'Cob B Monto', 'type' => 'text', 'section' => 'Coberturas', 'source' => 'payload'],
    ['key' => 'autos_cob_c_monto', 'label' => 'Cob C Monto', 'type' => 'text', 'section' => 'Coberturas', 'source' => 'payload'],
    ['key' => 'autos_cob_idd_monto', 'label' => 'Cob Idd Monto', 'type' => 'text', 'section' => 'Coberturas', 'source' => 'payload'],
    ['key' => 'autos_cob_idp_monto', 'label' => 'Cob Idp Monto', 'type' => 'text', 'section' => 'Coberturas', 'source' => 'payload'],
    ['key' => 'autos_cob_j_monto', 'label' => 'Cob J Monto', 'type' => 'text', 'section' => 'Coberturas', 'source' => 'payload'],
    ['key' => 'autos_cob_k_dias', 'label' => 'Cob K Dias', 'type' => 'text', 'section' => 'Coberturas', 'source' => 'payload'],
    ['key' => 'autos_cob_k_monto_dia', 'label' => 'Cob K Monto Dia', 'type' => 'text', 'section' => 'Coberturas', 'source' => 'payload'],
    ['key' => 'autos_cob_p_monto', 'label' => 'Cob P Monto', 'type' => 'text', 'section' => 'Coberturas', 'source' => 'payload'],
    ['key' => 'autos_conductor_habitual', 'label' => 'Conductor Habitual', 'type' => 'text', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_conductor_id', 'label' => 'Conductor Id', 'type' => 'text', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_consentimientoDatos', 'label' => 'ConsentimientoDatos', 'type' => 'text', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_consentimientoGrabacion', 'label' => 'ConsentimientoGrabacion', 'type' => 'text', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_consentimientoInfo', 'label' => 'ConsentimientoInfo', 'type' => 'text', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_csrf', 'label' => 'Csrf', 'type' => 'text', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_description', 'label' => 'Description', 'type' => 'text', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_dispositivo_tipo', 'label' => 'Dispositivo Tipo', 'type' => 'text', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_equipo_especial_monto', 'label' => 'Equipo Especial Monto', 'type' => 'text', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_firma_asegurado_cargo', 'label' => 'Firma Asegurado Cargo', 'type' => 'text', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'autos_firma_asegurado_id', 'label' => 'Firma Asegurado Id', 'type' => 'text', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'autos_firma_asegurado_nombre', 'label' => 'Firma Asegurado Nombre', 'type' => 'text', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'autos_firma_tomador_cargo', 'label' => 'Firma Tomador Cargo', 'type' => 'text', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'autos_firma_tomador_id', 'label' => 'Firma Tomador Id', 'type' => 'text', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'autos_firma_tomador_nombre', 'label' => 'Firma Tomador Nombre', 'type' => 'text', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'autos_interes_otro_texto', 'label' => 'Interes Otro Texto', 'type' => 'text', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_notif_apartado', 'label' => 'Notif Apartado', 'type' => 'text', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_notif_fax', 'label' => 'Notif Fax', 'type' => 'text', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_observaciones', 'label' => 'Observaciones', 'type' => 'text', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_otra_aseguradora', 'label' => 'Otra Aseguradora', 'type' => 'text', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'autos_tomador_canton', 'label' => 'Tomador Canton', 'type' => 'text', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'autos_tomador_correo', 'label' => 'Tomador Correo', 'type' => 'text', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'autos_tomador_distrito', 'label' => 'Tomador Distrito', 'type' => 'text', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'autos_tomador_domicilio', 'label' => 'Tomador Domicilio', 'type' => 'text', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'autos_tomador_nombre', 'label' => 'Tomador Nombre', 'type' => 'text', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'autos_tomador_num_id', 'label' => 'Tomador N° Id', 'type' => 'text', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'autos_tomador_otro_tipo', 'label' => 'Tomador Otro Tipo', 'type' => 'text', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'autos_tomador_provincia', 'label' => 'Tomador Provincia', 'type' => 'text', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'autos_tomador_telefono', 'label' => 'Tomador Telefono', 'type' => 'text', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'autos_uso_especificar', 'label' => 'Uso Especificar', 'type' => 'text', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_valor_vehiculo_colones', 'label' => 'Valor Vehiculo Colones', 'type' => 'text', 'section' => 'Datos Vehículo', 'source' => 'payload'],
    ['key' => 'autos_valor_vehiculo_dolares', 'label' => 'Valor Vehiculo Dolares', 'type' => 'text', 'section' => 'Datos Vehículo', 'source' => 'payload'],
    ['key' => 'autos_vehiculo_ano', 'label' => 'Vehiculo Ano', 'type' => 'text', 'section' => 'Datos Vehículo', 'source' => 'payload'],
    ['key' => 'autos_vehiculo_capacidad', 'label' => 'Vehiculo Capacidad', 'type' => 'text', 'section' => 'Datos Vehículo', 'source' => 'payload'],
    ['key' => 'autos_vehiculo_cilindraje', 'label' => 'Vehiculo Cilindraje', 'type' => 'text', 'section' => 'Datos Vehículo', 'source' => 'payload'],
    ['key' => 'autos_vehiculo_color', 'label' => 'Vehiculo Color', 'type' => 'text', 'section' => 'Datos Vehículo', 'source' => 'payload'],
    ['key' => 'autos_vehiculo_combustible', 'label' => 'Vehiculo Combustible', 'type' => 'text', 'section' => 'Datos Vehículo', 'source' => 'payload'],
    ['key' => 'autos_vehiculo_marca_modelo', 'label' => 'Vehiculo Marca Modelo', 'type' => 'text', 'section' => 'Datos Vehículo', 'source' => 'payload'],
    ['key' => 'autos_vehiculo_motor', 'label' => 'Vehiculo Motor', 'type' => 'text', 'section' => 'Datos Vehículo', 'source' => 'payload'],
    ['key' => 'autos_vehiculo_peso_bruto', 'label' => 'Vehiculo Peso Bruto', 'type' => 'text', 'section' => 'Datos Vehículo', 'source' => 'payload'],
    ['key' => 'autos_vehiculo_placa', 'label' => 'Vehiculo Placa', 'type' => 'text', 'section' => 'Datos Vehículo', 'source' => 'payload'],
    ['key' => 'autos_vehiculo_tipo', 'label' => 'Vehiculo Tipo', 'type' => 'text', 'section' => 'Datos Vehículo', 'source' => 'payload'],
    ['key' => 'autos_vehiculo_vin', 'label' => 'Vehiculo Vin', 'type' => 'text', 'section' => 'Datos Vehículo', 'source' => 'payload'],
    ['key' => 'autos_viewport', 'label' => 'Viewport', 'type' => 'text', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_website', 'label' => 'Website', 'type' => 'text', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_y_desde', 'label' => 'Y Desde', 'type' => 'text', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_y_destino', 'label' => 'Y Destino', 'type' => 'text', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'autos_y_hasta', 'label' => 'Y Hasta', 'type' => 'text', 'section' => 'General', 'source' => 'payload'],
];

// =====================================================
// CAMPOS DEL FORMULARIO INS - RIESGOS DEL TRABAJO (RT)
// =====================================================
// ====================================================================================
// CAMPOS RT - RIESGOS DEL TRABAJO (INS-F-D0190 09/2025) - 3 PÁGINAS
// ====================================================================================
$camposRT = [
    // ==================== PÁGINA 1 ====================

    // === 1. REGISTRO ===
    ['key' => 'registro_fecha_dd', 'label' => '1. Fecha Día', 'type' => 'text', 'section' => 'P1 Registro', 'source' => 'sistema'],
    ['key' => 'registro_fecha_mm', 'label' => '1. Fecha Mes', 'type' => 'text', 'section' => 'P1 Registro', 'source' => 'sistema'],
    ['key' => 'registro_fecha_aaaa', 'label' => '1. Fecha Año', 'type' => 'text', 'section' => 'P1 Registro', 'source' => 'sistema'],
    ['key' => 'registro_hora', 'label' => '1. Hora', 'type' => 'text', 'section' => 'P1 Registro', 'source' => 'sistema'],
    ['key' => 'registro_lugar', 'label' => '2. Lugar', 'type' => 'text', 'section' => 'P1 Registro', 'source' => 'fijo'],

    // === 3. TIPO DE TRÁMITE SOLICITADO ===
    ['key' => 'cb_tramite_emision', 'label' => '☑ 3. Emisión', 'type' => 'checkbox', 'section' => 'P1 Tipo Trámite', 'source' => 'fijo'],
    ['key' => 'cb_tramite_rehabilitacion', 'label' => '☑ 3. Rehabilitación', 'type' => 'checkbox', 'section' => 'P1 Tipo Trámite', 'source' => 'payload'],
    ['key' => 'poliza_numero', 'label' => 'Póliza N°', 'type' => 'text', 'section' => 'P1 Tipo Trámite', 'source' => 'ins'],

    // === DATOS DE LA PERSONA TOMADORA DEL SEGURO ===
    // 4. Tipo de identificación
    ['key' => 'cb_tomador_cedula_juridica', 'label' => '☑ 4. Cédula Jurídica', 'type' => 'checkbox', 'section' => 'P1 Tomador ID', 'source' => 'payload'],
    ['key' => 'cb_tomador_cedula_fisica', 'label' => '☑ 4. Cédula Física', 'type' => 'checkbox', 'section' => 'P1 Tomador ID', 'source' => 'payload'],
    ['key' => 'cb_tomador_dimex_didi', 'label' => '☑ 4. DIMEX/DIDI', 'type' => 'checkbox', 'section' => 'P1 Tomador ID', 'source' => 'payload'],
    ['key' => 'cb_tomador_pasaporte', 'label' => '☑ 4. Pasaporte', 'type' => 'checkbox', 'section' => 'P1 Tomador ID', 'source' => 'payload'],

    // 5-7. Identificación y nombre
    ['key' => 'tomador_num_identificacion', 'label' => '5. Número de identificación', 'type' => 'text', 'section' => 'P1 Tomador Datos', 'source' => 'payload'],
    ['key' => 'tomador_nacionalidad', 'label' => '6. Nacionalidad', 'type' => 'text', 'section' => 'P1 Tomador Datos', 'source' => 'payload'],
    ['key' => 'tomador_nombre_razon_social', 'label' => '7. Nombre o Razón Social', 'type' => 'text', 'section' => 'P1 Tomador Datos', 'source' => 'payload'],

    // 8. Fecha de nacimiento o constitución
    ['key' => 'tomador_nacimiento_dd', 'label' => '8. Fecha Nac/Const Día', 'type' => 'text', 'section' => 'P1 Tomador Datos', 'source' => 'payload'],
    ['key' => 'tomador_nacimiento_mm', 'label' => '8. Fecha Nac/Const Mes', 'type' => 'text', 'section' => 'P1 Tomador Datos', 'source' => 'payload'],
    ['key' => 'tomador_nacimiento_aaaa', 'label' => '8. Fecha Nac/Const Año', 'type' => 'text', 'section' => 'P1 Tomador Datos', 'source' => 'payload'],

    // 9. Género
    ['key' => 'cb_tomador_femenino', 'label' => '☑ 9. Femenino', 'type' => 'checkbox', 'section' => 'P1 Tomador Datos', 'source' => 'payload'],
    ['key' => 'cb_tomador_masculino', 'label' => '☑ 9. Masculino', 'type' => 'checkbox', 'section' => 'P1 Tomador Datos', 'source' => 'payload'],

    // 10-11. Profesión y domicilio
    ['key' => 'tomador_profesion_ocupacion', 'label' => '10. Profesión u Ocupación', 'type' => 'text', 'section' => 'P1 Tomador Datos', 'source' => 'payload'],
    ['key' => 'tomador_domicilio_senas', 'label' => '11. Domicilio físico (por señas)', 'type' => 'text', 'section' => 'P1 Tomador Datos', 'source' => 'payload'],

    // 12-15. Ubicación
    ['key' => 'tomador_provincia', 'label' => '12. Provincia', 'type' => 'text', 'section' => 'P1 Tomador Ubicación', 'source' => 'payload'],
    ['key' => 'tomador_canton', 'label' => '13. Cantón', 'type' => 'text', 'section' => 'P1 Tomador Ubicación', 'source' => 'payload'],
    ['key' => 'tomador_distrito', 'label' => '14. Distrito', 'type' => 'text', 'section' => 'P1 Tomador Ubicación', 'source' => 'payload'],
    ['key' => 'tomador_apartado_postal', 'label' => '15. Apartado postal', 'type' => 'text', 'section' => 'P1 Tomador Ubicación', 'source' => 'payload'],

    // 16-18. Contacto
    ['key' => 'tomador_fax', 'label' => '16. Fax o Facsímil', 'type' => 'text', 'section' => 'P1 Tomador Contacto', 'source' => 'payload'],
    ['key' => 'tomador_tel_celular', 'label' => '17. Teléfono Celular', 'type' => 'text', 'section' => 'P1 Tomador Contacto', 'source' => 'payload'],
    ['key' => 'tomador_tel_domicilio', 'label' => '17. Teléfono Domicilio', 'type' => 'text', 'section' => 'P1 Tomador Contacto', 'source' => 'payload'],
    ['key' => 'tomador_tel_oficina', 'label' => '17. Teléfono Oficina', 'type' => 'text', 'section' => 'P1 Tomador Contacto', 'source' => 'payload'],
    ['key' => 'tomador_correo', 'label' => '18. Correo electrónico', 'type' => 'text', 'section' => 'P1 Tomador Contacto', 'source' => 'payload'],

    // 19. Medio de notificación
    ['key' => 'cb_notif_correo', 'label' => '☑ 19. Notif. Correo electrónico', 'type' => 'checkbox', 'section' => 'P1 Notificación', 'source' => 'payload'],
    ['key' => 'cb_notif_fax', 'label' => '☑ 19. Notif. Fax o Facsímil', 'type' => 'checkbox', 'section' => 'P1 Notificación', 'source' => 'payload'],
    ['key' => 'cb_notif_apartado', 'label' => '☑ 19. Notif. Apartado postal', 'type' => 'checkbox', 'section' => 'P1 Notificación', 'source' => 'payload'],
    ['key' => 'cb_notif_domicilio', 'label' => '☑ 19. Notif. Domicilio físico', 'type' => 'checkbox', 'section' => 'P1 Notificación', 'source' => 'payload'],

    // === 20. MODALIDADES DE ASEGURAMIENTO ===
    // Pólizas de Periodo Corto
    ['key' => 'cb_poliza_rt_construccion_corto', 'label' => '☑ 20. RT-Construcción (Corto)', 'type' => 'checkbox', 'section' => 'P1 Modalidad Corto', 'source' => 'payload'],
    ['key' => 'cb_poliza_rt_cosechas_corto', 'label' => '☑ 20. RT-Cosechas (Corto)', 'type' => 'checkbox', 'section' => 'P1 Modalidad Corto', 'source' => 'payload'],
    ['key' => 'cb_poliza_rt_general_corto', 'label' => '☑ 20. RT-General (Corto)', 'type' => 'checkbox', 'section' => 'P1 Modalidad Corto', 'source' => 'payload'],
    ['key' => 'cb_poliza_rt_formacion_dual_corto', 'label' => '☑ 20. RT-Especial Form. Técnica Dual (Corto)', 'type' => 'checkbox', 'section' => 'P1 Modalidad Corto', 'source' => 'payload'],

    // Pólizas Permanentes
    ['key' => 'cb_poliza_rt_adolescente', 'label' => '☑ 20. RT-Adolescente (Perm)', 'type' => 'checkbox', 'section' => 'P1 Modalidad Perm', 'source' => 'payload'],
    ['key' => 'cb_poliza_rt_agricola', 'label' => '☑ 20. RT-Agrícola (Perm)', 'type' => 'checkbox', 'section' => 'P1 Modalidad Perm', 'source' => 'payload'],
    ['key' => 'cb_poliza_rt_formacion_dual_perm', 'label' => '☑ 20. RT-Especial Form. Técnica Dual (Perm)', 'type' => 'checkbox', 'section' => 'P1 Modalidad Perm', 'source' => 'payload'],
    ['key' => 'cb_poliza_rt_general_perm', 'label' => '☑ 20. RT-General (Perm)', 'type' => 'checkbox', 'section' => 'P1 Modalidad Perm', 'source' => 'payload'],
    ['key' => 'cb_poliza_rt_hogar', 'label' => '☑ 20. RT-Hogar (Perm)', 'type' => 'checkbox', 'section' => 'P1 Modalidad Perm', 'source' => 'payload'],
    ['key' => 'cb_poliza_rt_ocasional', 'label' => '☑ 20. RT-Ocasional (Perm)', 'type' => 'checkbox', 'section' => 'P1 Modalidad Perm', 'source' => 'payload'],
    ['key' => 'cb_poliza_rt_sector_publico', 'label' => '☑ 20. RT-Sector Público (Perm)', 'type' => 'checkbox', 'section' => 'P1 Modalidad Perm', 'source' => 'payload'],

    // ==================== PÁGINA 2 ====================

    // === DATOS GENERALES DEL SEGURO ===
    ['key' => 'seguro_actividad_economica', 'label' => '21. Trabajo o actividad económica', 'type' => 'text', 'section' => 'P2 Datos Seguro', 'source' => 'payload'],
    ['key' => 'seguro_direccion_trabajo', 'label' => '22. Dirección donde se ejecutará el trabajo', 'type' => 'text', 'section' => 'P2 Datos Seguro', 'source' => 'payload'],
    ['key' => 'seguro_provincia', 'label' => '23. Provincia', 'type' => 'text', 'section' => 'P2 Datos Seguro', 'source' => 'payload'],
    ['key' => 'seguro_canton', 'label' => '24. Cantón', 'type' => 'text', 'section' => 'P2 Datos Seguro', 'source' => 'payload'],
    ['key' => 'seguro_distrito', 'label' => '25. Distrito', 'type' => 'text', 'section' => 'P2 Datos Seguro', 'source' => 'payload'],

    // 26. Fecha de ejecución
    ['key' => 'seguro_fecha_inicia_dd', 'label' => '26. Fecha Inicia Día', 'type' => 'text', 'section' => 'P2 Fechas Ejecución', 'source' => 'payload'],
    ['key' => 'seguro_fecha_inicia_mm', 'label' => '26. Fecha Inicia Mes', 'type' => 'text', 'section' => 'P2 Fechas Ejecución', 'source' => 'payload'],
    ['key' => 'seguro_fecha_inicia_aaaa', 'label' => '26. Fecha Inicia Año', 'type' => 'text', 'section' => 'P2 Fechas Ejecución', 'source' => 'payload'],
    ['key' => 'seguro_fecha_finaliza_dd', 'label' => '26. Fecha Finaliza Día', 'type' => 'text', 'section' => 'P2 Fechas Ejecución', 'source' => 'payload'],
    ['key' => 'seguro_fecha_finaliza_mm', 'label' => '26. Fecha Finaliza Mes', 'type' => 'text', 'section' => 'P2 Fechas Ejecución', 'source' => 'payload'],
    ['key' => 'seguro_fecha_finaliza_aaaa', 'label' => '26. Fecha Finaliza Año', 'type' => 'text', 'section' => 'P2 Fechas Ejecución', 'source' => 'payload'],

    // 27. Tipo de calendario de planillas
    ['key' => 'cb_calendario_mensual', 'label' => '☑ 27. Calendario Mensual', 'type' => 'checkbox', 'section' => 'P2 Calendario Planillas', 'source' => 'payload'],
    ['key' => 'cb_calendario_especial', 'label' => '☑ 27. Calendario Especial', 'type' => 'checkbox', 'section' => 'P2 Calendario Planillas', 'source' => 'payload'],
    ['key' => 'cb_calendario_no_presenta', 'label' => '☑ 27. No presenta', 'type' => 'checkbox', 'section' => 'P2 Calendario Planillas', 'source' => 'payload'],

    // 28. Forma de pago de la prima
    ['key' => 'cb_pago_anual', 'label' => '☑ 28. Pago Anual', 'type' => 'checkbox', 'section' => 'P2 Forma Pago', 'source' => 'payload'],
    ['key' => 'cb_pago_semestral', 'label' => '☑ 28. Pago Semestral', 'type' => 'checkbox', 'section' => 'P2 Forma Pago', 'source' => 'payload'],
    ['key' => 'cb_pago_trimestral', 'label' => '☑ 28. Pago Trimestral', 'type' => 'checkbox', 'section' => 'P2 Forma Pago', 'source' => 'payload'],
    ['key' => 'cb_pago_mensual', 'label' => '☑ 28. Pago Mensual', 'type' => 'checkbox', 'section' => 'P2 Forma Pago', 'source' => 'payload'],

    // 29-30. Planilla
    ['key' => 'seguro_monto_planilla_mensual', 'label' => '29. Monto estimado planilla mensual', 'type' => 'text', 'section' => 'P2 Planilla', 'source' => 'payload'],
    ['key' => 'cb_adjunta_planilla_si', 'label' => '☑ 30. Adjunta Planilla SI', 'type' => 'checkbox', 'section' => 'P2 Planilla', 'source' => 'payload'],
    ['key' => 'cb_adjunta_planilla_no', 'label' => '☑ 30. Adjunta Planilla NO', 'type' => 'checkbox', 'section' => 'P2 Planilla', 'source' => 'payload'],

    // === CONSTRUCCIÓN ===
    ['key' => 'cb_doc_permiso_municipal', 'label' => '☑ 31. Permiso Municipal', 'type' => 'checkbox', 'section' => 'P2 Construcción', 'source' => 'payload'],
    ['key' => 'construccion_permiso_municipal_no', 'label' => '31. Permiso Municipal No.', 'type' => 'text', 'section' => 'P2 Construcción', 'source' => 'payload'],
    ['key' => 'cb_doc_contrato_cfia', 'label' => '☑ 31. Contrato CFIA', 'type' => 'checkbox', 'section' => 'P2 Construcción', 'source' => 'payload'],
    ['key' => 'construccion_contrato_cfia_no', 'label' => '31. Contrato del CFIA No.', 'type' => 'text', 'section' => 'P2 Construcción', 'source' => 'payload'],
    ['key' => 'cb_doc_copia_contrato', 'label' => '☑ 31. Copia del contrato entre las Partes', 'type' => 'checkbox', 'section' => 'P2 Construcción', 'source' => 'payload'],
    ['key' => 'cb_interes_social_no', 'label' => '☑ 32. Declaración Interés Social No', 'type' => 'checkbox', 'section' => 'P2 Construcción', 'source' => 'payload'],
    ['key' => 'cb_interes_social_si', 'label' => '☑ 32. Declaración Interés Social Si', 'type' => 'checkbox', 'section' => 'P2 Construcción', 'source' => 'payload'],
    ['key' => 'construccion_valor_obra', 'label' => '33. Valor total de la obra', 'type' => 'text', 'section' => 'P2 Construcción', 'source' => 'payload'],

    // === RT-COSECHAS ===
    ['key' => 'cosechas_fruto_producto', 'label' => '34. Fruto o producto a recolectar', 'type' => 'text', 'section' => 'P2 RT-Cosechas', 'source' => 'payload'],
    ['key' => 'cosechas_unidad_medida', 'label' => '35. Unidad de medida a utilizar', 'type' => 'text', 'section' => 'P2 RT-Cosechas', 'source' => 'payload'],
    ['key' => 'cosechas_cantidad_unidades', 'label' => '36. Cantidad de unidades a recolectar', 'type' => 'text', 'section' => 'P2 RT-Cosechas', 'source' => 'payload'],
    ['key' => 'cosechas_precio_unidad', 'label' => '37. Precio a pagar por unidad', 'type' => 'text', 'section' => 'P2 RT-Cosechas', 'source' => 'payload'],

    // === RT-HOGAR ===
    ['key' => 'cb_hogar_opcion1_un_trabajador', 'label' => '☑ 38. Opción 1: Un trabajador', 'type' => 'checkbox', 'section' => 'P2 RT-Hogar', 'source' => 'payload'],
    ['key' => 'cb_hogar_opcion2_dos_trabajadores', 'label' => '☑ 38. Opción 2: Dos trabajadores', 'type' => 'checkbox', 'section' => 'P2 RT-Hogar', 'source' => 'payload'],
    ['key' => 'cb_hogar_opcion3_tres_o_mas', 'label' => '☑ 38. Opción 3: Tres o más trabajadores', 'type' => 'checkbox', 'section' => 'P2 RT-Hogar', 'source' => 'payload'],

    // === ACEPTACIÓN DEL SEGURO ===
    ['key' => 'firma_tomador', 'label' => '39. Firma de la Persona Tomadora', 'type' => 'text', 'section' => 'P2 Aceptación', 'source' => 'payload'],
    ['key' => 'representante_nombre', 'label' => '40. Nombre completo Representante', 'type' => 'text', 'section' => 'P2 Aceptación', 'source' => 'payload'],
    ['key' => 'representante_identificacion', 'label' => '40. Identificación Representante', 'type' => 'text', 'section' => 'P2 Aceptación', 'source' => 'payload'],
    ['key' => 'representante_puesto', 'label' => '40. Puesto del Representante', 'type' => 'text', 'section' => 'P2 Aceptación', 'source' => 'payload'],

    // === USO EXCLUSIVO INTERMEDIARIO ===
    ['key' => 'intermediario_monto_asegurado', 'label' => '41. Monto Asegurado', 'type' => 'text', 'section' => 'P2 Intermediario', 'source' => 'ins'],
    ['key' => 'intermediario_codigo_actividad', 'label' => '42. Código actividad', 'type' => 'text', 'section' => 'P2 Intermediario', 'source' => 'ins'],
    ['key' => 'intermediario_tarifa', 'label' => '43. Tarifa', 'type' => 'text', 'section' => 'P2 Intermediario', 'source' => 'ins'],
    ['key' => 'intermediario_prima_anual', 'label' => '44. Prima Anual Estimada', 'type' => 'text', 'section' => 'P2 Intermediario', 'source' => 'ins'],
    ['key' => 'intermediario_nombre_codigo', 'label' => '45. Nombre y código Intermediario', 'type' => 'text', 'section' => 'P2 Intermediario', 'source' => 'fijo'],
    ['key' => 'intermediario_firma_identificacion', 'label' => '46. Firma e ID Intermediario', 'type' => 'text', 'section' => 'P2 Intermediario', 'source' => 'fijo'],

    // ==================== PÁGINA 3 - PLANILLA DE EMISIÓN ====================
    ['key' => 'rt_planilla_poliza_numero', 'label' => 'Planilla Póliza N°', 'type' => 'text', 'section' => 'P3 Encabezado', 'source' => 'ins'],

    // === TRABAJADOR 1 ===
    ['key' => 'rt_trab1_tipo_id', 'label' => 'Trab 1 - Tipo ID (CN/DU/NP/NT)', 'type' => 'text', 'section' => 'P3 Trabajador 1', 'source' => 'payload'],
    ['key' => 'rt_trab1_nacionalidad', 'label' => 'Trab 1 - Nacionalidad', 'type' => 'text', 'section' => 'P3 Trabajador 1', 'source' => 'payload'],
    ['key' => 'rt_trab1_num_id', 'label' => 'Trab 1 - N° Identificación', 'type' => 'text', 'section' => 'P3 Trabajador 1', 'source' => 'payload'],
    ['key' => 'rt_trab1_nombre', 'label' => 'Trab 1 - Nombre', 'type' => 'text', 'section' => 'P3 Trabajador 1', 'source' => 'payload'],
    ['key' => 'rt_trab1_apellido1', 'label' => 'Trab 1 - Primer Apellido', 'type' => 'text', 'section' => 'P3 Trabajador 1', 'source' => 'payload'],
    ['key' => 'rt_trab1_apellido2', 'label' => 'Trab 1 - Segundo Apellido', 'type' => 'text', 'section' => 'P3 Trabajador 1', 'source' => 'payload'],
    ['key' => 'rt_trab1_fecha_nacimiento', 'label' => 'Trab 1 - F. Nacimiento', 'type' => 'text', 'section' => 'P3 Trabajador 1', 'source' => 'payload'],
    ['key' => 'rt_trab1_sexo', 'label' => 'Trab 1 - Sexo (M/F)', 'type' => 'text', 'section' => 'P3 Trabajador 1', 'source' => 'payload'],
    ['key' => 'rt_trab1_tipo_jornada', 'label' => 'Trab 1 - Tipo Jornada (TC/TM/OD/OH)', 'type' => 'text', 'section' => 'P3 Trabajador 1', 'source' => 'payload'],
    ['key' => 'rt_trab1_salario_mensual', 'label' => 'Trab 1 - Salario Mensual', 'type' => 'text', 'section' => 'P3 Trabajador 1', 'source' => 'payload'],
    ['key' => 'rt_trab1_dias', 'label' => 'Trab 1 - Días', 'type' => 'text', 'section' => 'P3 Trabajador 1', 'source' => 'payload'],
    ['key' => 'rt_trab1_horas', 'label' => 'Trab 1 - Horas', 'type' => 'text', 'section' => 'P3 Trabajador 1', 'source' => 'payload'],
    ['key' => 'rt_trab1_ocupacion', 'label' => 'Trab 1 - Ocupación', 'type' => 'text', 'section' => 'P3 Trabajador 1', 'source' => 'payload'],

    // === TRABAJADOR 2 ===
    ['key' => 'rt_trab2_tipo_id', 'label' => 'Trab 2 - Tipo ID (CN/DU/NP/NT)', 'type' => 'text', 'section' => 'P3 Trabajador 2', 'source' => 'payload'],
    ['key' => 'rt_trab2_nacionalidad', 'label' => 'Trab 2 - Nacionalidad', 'type' => 'text', 'section' => 'P3 Trabajador 2', 'source' => 'payload'],
    ['key' => 'rt_trab2_num_id', 'label' => 'Trab 2 - N° Identificación', 'type' => 'text', 'section' => 'P3 Trabajador 2', 'source' => 'payload'],
    ['key' => 'rt_trab2_nombre', 'label' => 'Trab 2 - Nombre', 'type' => 'text', 'section' => 'P3 Trabajador 2', 'source' => 'payload'],
    ['key' => 'rt_trab2_apellido1', 'label' => 'Trab 2 - Primer Apellido', 'type' => 'text', 'section' => 'P3 Trabajador 2', 'source' => 'payload'],
    ['key' => 'rt_trab2_apellido2', 'label' => 'Trab 2 - Segundo Apellido', 'type' => 'text', 'section' => 'P3 Trabajador 2', 'source' => 'payload'],
    ['key' => 'rt_trab2_fecha_nacimiento', 'label' => 'Trab 2 - F. Nacimiento', 'type' => 'text', 'section' => 'P3 Trabajador 2', 'source' => 'payload'],
    ['key' => 'rt_trab2_sexo', 'label' => 'Trab 2 - Sexo (M/F)', 'type' => 'text', 'section' => 'P3 Trabajador 2', 'source' => 'payload'],
    ['key' => 'rt_trab2_tipo_jornada', 'label' => 'Trab 2 - Tipo Jornada (TC/TM/OD/OH)', 'type' => 'text', 'section' => 'P3 Trabajador 2', 'source' => 'payload'],
    ['key' => 'rt_trab2_salario_mensual', 'label' => 'Trab 2 - Salario Mensual', 'type' => 'text', 'section' => 'P3 Trabajador 2', 'source' => 'payload'],
    ['key' => 'rt_trab2_dias', 'label' => 'Trab 2 - Días', 'type' => 'text', 'section' => 'P3 Trabajador 2', 'source' => 'payload'],
    ['key' => 'rt_trab2_horas', 'label' => 'Trab 2 - Horas', 'type' => 'text', 'section' => 'P3 Trabajador 2', 'source' => 'payload'],
    ['key' => 'rt_trab2_ocupacion', 'label' => 'Trab 2 - Ocupación', 'type' => 'text', 'section' => 'P3 Trabajador 2', 'source' => 'payload'],

    // === TRABAJADOR 3 ===
    ['key' => 'rt_trab3_tipo_id', 'label' => 'Trab 3 - Tipo ID (CN/DU/NP/NT)', 'type' => 'text', 'section' => 'P3 Trabajador 3', 'source' => 'payload'],
    ['key' => 'rt_trab3_nacionalidad', 'label' => 'Trab 3 - Nacionalidad', 'type' => 'text', 'section' => 'P3 Trabajador 3', 'source' => 'payload'],
    ['key' => 'rt_trab3_num_id', 'label' => 'Trab 3 - N° Identificación', 'type' => 'text', 'section' => 'P3 Trabajador 3', 'source' => 'payload'],
    ['key' => 'rt_trab3_nombre', 'label' => 'Trab 3 - Nombre', 'type' => 'text', 'section' => 'P3 Trabajador 3', 'source' => 'payload'],
    ['key' => 'rt_trab3_apellido1', 'label' => 'Trab 3 - Primer Apellido', 'type' => 'text', 'section' => 'P3 Trabajador 3', 'source' => 'payload'],
    ['key' => 'rt_trab3_apellido2', 'label' => 'Trab 3 - Segundo Apellido', 'type' => 'text', 'section' => 'P3 Trabajador 3', 'source' => 'payload'],
    ['key' => 'rt_trab3_fecha_nacimiento', 'label' => 'Trab 3 - F. Nacimiento', 'type' => 'text', 'section' => 'P3 Trabajador 3', 'source' => 'payload'],
    ['key' => 'rt_trab3_sexo', 'label' => 'Trab 3 - Sexo (M/F)', 'type' => 'text', 'section' => 'P3 Trabajador 3', 'source' => 'payload'],
    ['key' => 'rt_trab3_tipo_jornada', 'label' => 'Trab 3 - Tipo Jornada (TC/TM/OD/OH)', 'type' => 'text', 'section' => 'P3 Trabajador 3', 'source' => 'payload'],
    ['key' => 'rt_trab3_salario_mensual', 'label' => 'Trab 3 - Salario Mensual', 'type' => 'text', 'section' => 'P3 Trabajador 3', 'source' => 'payload'],
    ['key' => 'rt_trab3_dias', 'label' => 'Trab 3 - Días', 'type' => 'text', 'section' => 'P3 Trabajador 3', 'source' => 'payload'],
    ['key' => 'rt_trab3_horas', 'label' => 'Trab 3 - Horas', 'type' => 'text', 'section' => 'P3 Trabajador 3', 'source' => 'payload'],
    ['key' => 'rt_trab3_ocupacion', 'label' => 'Trab 3 - Ocupación', 'type' => 'text', 'section' => 'P3 Trabajador 3', 'source' => 'payload'],

    // === TRABAJADOR 4 ===
    ['key' => 'rt_trab4_tipo_id', 'label' => 'Trab 4 - Tipo ID (CN/DU/NP/NT)', 'type' => 'text', 'section' => 'P3 Trabajador 4', 'source' => 'payload'],
    ['key' => 'rt_trab4_nacionalidad', 'label' => 'Trab 4 - Nacionalidad', 'type' => 'text', 'section' => 'P3 Trabajador 4', 'source' => 'payload'],
    ['key' => 'rt_trab4_num_id', 'label' => 'Trab 4 - N° Identificación', 'type' => 'text', 'section' => 'P3 Trabajador 4', 'source' => 'payload'],
    ['key' => 'rt_trab4_nombre', 'label' => 'Trab 4 - Nombre', 'type' => 'text', 'section' => 'P3 Trabajador 4', 'source' => 'payload'],
    ['key' => 'rt_trab4_apellido1', 'label' => 'Trab 4 - Primer Apellido', 'type' => 'text', 'section' => 'P3 Trabajador 4', 'source' => 'payload'],
    ['key' => 'rt_trab4_apellido2', 'label' => 'Trab 4 - Segundo Apellido', 'type' => 'text', 'section' => 'P3 Trabajador 4', 'source' => 'payload'],
    ['key' => 'rt_trab4_fecha_nacimiento', 'label' => 'Trab 4 - F. Nacimiento', 'type' => 'text', 'section' => 'P3 Trabajador 4', 'source' => 'payload'],
    ['key' => 'rt_trab4_sexo', 'label' => 'Trab 4 - Sexo (M/F)', 'type' => 'text', 'section' => 'P3 Trabajador 4', 'source' => 'payload'],
    ['key' => 'rt_trab4_tipo_jornada', 'label' => 'Trab 4 - Tipo Jornada (TC/TM/OD/OH)', 'type' => 'text', 'section' => 'P3 Trabajador 4', 'source' => 'payload'],
    ['key' => 'rt_trab4_salario_mensual', 'label' => 'Trab 4 - Salario Mensual', 'type' => 'text', 'section' => 'P3 Trabajador 4', 'source' => 'payload'],
    ['key' => 'rt_trab4_dias', 'label' => 'Trab 4 - Días', 'type' => 'text', 'section' => 'P3 Trabajador 4', 'source' => 'payload'],
    ['key' => 'rt_trab4_horas', 'label' => 'Trab 4 - Horas', 'type' => 'text', 'section' => 'P3 Trabajador 4', 'source' => 'payload'],
    ['key' => 'rt_trab4_ocupacion', 'label' => 'Trab 4 - Ocupación', 'type' => 'text', 'section' => 'P3 Trabajador 4', 'source' => 'payload'],

    // === TRABAJADOR 5 ===
    ['key' => 'rt_trab5_tipo_id', 'label' => 'Trab 5 - Tipo ID (CN/DU/NP/NT)', 'type' => 'text', 'section' => 'P3 Trabajador 5', 'source' => 'payload'],
    ['key' => 'rt_trab5_nacionalidad', 'label' => 'Trab 5 - Nacionalidad', 'type' => 'text', 'section' => 'P3 Trabajador 5', 'source' => 'payload'],
    ['key' => 'rt_trab5_num_id', 'label' => 'Trab 5 - N° Identificación', 'type' => 'text', 'section' => 'P3 Trabajador 5', 'source' => 'payload'],
    ['key' => 'rt_trab5_nombre', 'label' => 'Trab 5 - Nombre', 'type' => 'text', 'section' => 'P3 Trabajador 5', 'source' => 'payload'],
    ['key' => 'rt_trab5_apellido1', 'label' => 'Trab 5 - Primer Apellido', 'type' => 'text', 'section' => 'P3 Trabajador 5', 'source' => 'payload'],
    ['key' => 'rt_trab5_apellido2', 'label' => 'Trab 5 - Segundo Apellido', 'type' => 'text', 'section' => 'P3 Trabajador 5', 'source' => 'payload'],
    ['key' => 'rt_trab5_fecha_nacimiento', 'label' => 'Trab 5 - F. Nacimiento', 'type' => 'text', 'section' => 'P3 Trabajador 5', 'source' => 'payload'],
    ['key' => 'rt_trab5_sexo', 'label' => 'Trab 5 - Sexo (M/F)', 'type' => 'text', 'section' => 'P3 Trabajador 5', 'source' => 'payload'],
    ['key' => 'rt_trab5_tipo_jornada', 'label' => 'Trab 5 - Tipo Jornada (TC/TM/OD/OH)', 'type' => 'text', 'section' => 'P3 Trabajador 5', 'source' => 'payload'],
    ['key' => 'rt_trab5_salario_mensual', 'label' => 'Trab 5 - Salario Mensual', 'type' => 'text', 'section' => 'P3 Trabajador 5', 'source' => 'payload'],
    ['key' => 'rt_trab5_dias', 'label' => 'Trab 5 - Días', 'type' => 'text', 'section' => 'P3 Trabajador 5', 'source' => 'payload'],
    ['key' => 'rt_trab5_horas', 'label' => 'Trab 5 - Horas', 'type' => 'text', 'section' => 'P3 Trabajador 5', 'source' => 'payload'],
    ['key' => 'rt_trab5_ocupacion', 'label' => 'Trab 5 - Ocupación', 'type' => 'text', 'section' => 'P3 Trabajador 5', 'source' => 'payload'],

    // === TRABAJADORES 6-10 (campos adicionales para más trabajadores) ===
    ['key' => 'rt_trab6_tipo_id', 'label' => 'Trab 6 - Tipo ID', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab6_nacionalidad', 'label' => 'Trab 6 - Nacionalidad', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab6_num_id', 'label' => 'Trab 6 - N° ID', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab6_nombre', 'label' => 'Trab 6 - Nombre', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab6_apellido1', 'label' => 'Trab 6 - Apellido 1', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab6_apellido2', 'label' => 'Trab 6 - Apellido 2', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab6_fecha_nacimiento', 'label' => 'Trab 6 - F. Nac', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab6_sexo', 'label' => 'Trab 6 - Sexo', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab6_tipo_jornada', 'label' => 'Trab 6 - T. Jornada', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab6_salario_mensual', 'label' => 'Trab 6 - Salario', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab6_dias', 'label' => 'Trab 6 - Días', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab6_horas', 'label' => 'Trab 6 - Horas', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab6_ocupacion', 'label' => 'Trab 6 - Ocupación', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],

    ['key' => 'rt_trab7_tipo_id', 'label' => 'Trab 7 - Tipo ID', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab7_nacionalidad', 'label' => 'Trab 7 - Nacionalidad', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab7_num_id', 'label' => 'Trab 7 - N° ID', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab7_nombre', 'label' => 'Trab 7 - Nombre', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab7_apellido1', 'label' => 'Trab 7 - Apellido 1', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab7_apellido2', 'label' => 'Trab 7 - Apellido 2', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab7_fecha_nacimiento', 'label' => 'Trab 7 - F. Nac', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab7_sexo', 'label' => 'Trab 7 - Sexo', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab7_tipo_jornada', 'label' => 'Trab 7 - T. Jornada', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab7_salario_mensual', 'label' => 'Trab 7 - Salario', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab7_dias', 'label' => 'Trab 7 - Días', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab7_horas', 'label' => 'Trab 7 - Horas', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab7_ocupacion', 'label' => 'Trab 7 - Ocupación', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],

    ['key' => 'rt_trab8_tipo_id', 'label' => 'Trab 8 - Tipo ID', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab8_nacionalidad', 'label' => 'Trab 8 - Nacionalidad', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab8_num_id', 'label' => 'Trab 8 - N° ID', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab8_nombre', 'label' => 'Trab 8 - Nombre', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab8_apellido1', 'label' => 'Trab 8 - Apellido 1', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab8_apellido2', 'label' => 'Trab 8 - Apellido 2', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab8_fecha_nacimiento', 'label' => 'Trab 8 - F. Nac', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab8_sexo', 'label' => 'Trab 8 - Sexo', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab8_tipo_jornada', 'label' => 'Trab 8 - T. Jornada', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab8_salario_mensual', 'label' => 'Trab 8 - Salario', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab8_dias', 'label' => 'Trab 8 - Días', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab8_horas', 'label' => 'Trab 8 - Horas', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab8_ocupacion', 'label' => 'Trab 8 - Ocupación', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],

    ['key' => 'rt_trab9_tipo_id', 'label' => 'Trab 9 - Tipo ID', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab9_nacionalidad', 'label' => 'Trab 9 - Nacionalidad', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab9_num_id', 'label' => 'Trab 9 - N° ID', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab9_nombre', 'label' => 'Trab 9 - Nombre', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab9_apellido1', 'label' => 'Trab 9 - Apellido 1', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab9_apellido2', 'label' => 'Trab 9 - Apellido 2', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab9_fecha_nacimiento', 'label' => 'Trab 9 - F. Nac', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab9_sexo', 'label' => 'Trab 9 - Sexo', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab9_tipo_jornada', 'label' => 'Trab 9 - T. Jornada', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab9_salario_mensual', 'label' => 'Trab 9 - Salario', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab9_dias', 'label' => 'Trab 9 - Días', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab9_horas', 'label' => 'Trab 9 - Horas', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab9_ocupacion', 'label' => 'Trab 9 - Ocupación', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],

    ['key' => 'rt_trab10_tipo_id', 'label' => 'Trab 10 - Tipo ID', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab10_nacionalidad', 'label' => 'Trab 10 - Nacionalidad', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab10_num_id', 'label' => 'Trab 10 - N° ID', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab10_nombre', 'label' => 'Trab 10 - Nombre', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab10_apellido1', 'label' => 'Trab 10 - Apellido 1', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab10_apellido2', 'label' => 'Trab 10 - Apellido 2', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab10_fecha_nacimiento', 'label' => 'Trab 10 - F. Nac', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab10_sexo', 'label' => 'Trab 10 - Sexo', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab10_tipo_jornada', 'label' => 'Trab 10 - T. Jornada', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab10_salario_mensual', 'label' => 'Trab 10 - Salario', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab10_dias', 'label' => 'Trab 10 - Días', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab10_horas', 'label' => 'Trab 10 - Horas', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],
    ['key' => 'rt_trab10_ocupacion', 'label' => 'Trab 10 - Ocupación', 'type' => 'text', 'section' => 'P3 Trabajadores 6-10', 'source' => 'payload'],

    // === TOTALES Y FIRMAS P3 ===
    ['key' => 'rt_planilla_total_trabajadores', 'label' => 'Total de Trabajadores', 'type' => 'text', 'section' => 'P3 Totales', 'source' => 'payload'],
    ['key' => 'rt_planilla_total_salarios', 'label' => 'Total de Salarios', 'type' => 'text', 'section' => 'P3 Totales', 'source' => 'payload'],
    ['key' => 'rt_planilla_firma_tomador', 'label' => 'Firma del Tomador del Seguro', 'type' => 'text', 'section' => 'P3 Firmas', 'source' => 'payload'],
    ['key' => 'rt_planilla_representante_nombre', 'label' => 'Nombre Representante (P. Jurídicas)', 'type' => 'text', 'section' => 'P3 Firmas', 'source' => 'payload'],
    ['key' => 'rt_planilla_representante_id', 'label' => 'Identificación Representante', 'type' => 'text', 'section' => 'P3 Firmas', 'source' => 'payload'],
    ['key' => 'rt_planilla_representante_puesto', 'label' => 'Puesto del Representante', 'type' => 'text', 'section' => 'P3 Firmas', 'source' => 'payload'],
    ['key' => 'rt_cb_adjunta_planilla_no', 'label' => '☑ Adjunta Planilla No', 'type' => 'checkbox', 'section' => 'Planilla', 'source' => 'payload'],
    ['key' => 'rt_cb_adjunta_planilla_si', 'label' => '☑ Adjunta Planilla Si', 'type' => 'checkbox', 'section' => 'Planilla', 'source' => 'payload'],
    ['key' => 'rt_cb_calendario_especial', 'label' => '☑ Calendario Especial', 'type' => 'checkbox', 'section' => 'Calendario', 'source' => 'payload'],
    ['key' => 'rt_cb_calendario_mensual', 'label' => '☑ Calendario Mensual', 'type' => 'checkbox', 'section' => 'Calendario', 'source' => 'payload'],
    ['key' => 'rt_cb_calendario_no_presenta', 'label' => '☑ Calendario No Presenta', 'type' => 'checkbox', 'section' => 'Calendario', 'source' => 'payload'],
    ['key' => 'rt_cb_doc_contrato_cfia', 'label' => '☑ Doc Contrato Cfia', 'type' => 'checkbox', 'section' => 'Documentos Adjuntos', 'source' => 'payload'],
    ['key' => 'rt_cb_doc_copia_contrato', 'label' => '☑ Doc Copia Contrato', 'type' => 'checkbox', 'section' => 'Documentos Adjuntos', 'source' => 'payload'],
    ['key' => 'rt_cb_doc_permiso_municipal', 'label' => '☑ Doc Permiso Municipal', 'type' => 'checkbox', 'section' => 'Documentos Adjuntos', 'source' => 'payload'],
    ['key' => 'rt_cb_hogar_opcion1_un_trabajador', 'label' => '☑ Hogar Opcion1 Un Trabajador', 'type' => 'checkbox', 'section' => 'Hogar', 'source' => 'payload'],
    ['key' => 'rt_cb_hogar_opcion2_dos_trabajadores', 'label' => '☑ Hogar Opcion2 Dos Trabajadores', 'type' => 'checkbox', 'section' => 'Hogar', 'source' => 'payload'],
    ['key' => 'rt_cb_hogar_opcion3_tres_o_mas', 'label' => '☑ Hogar Opcion3 Tres O Mas', 'type' => 'checkbox', 'section' => 'Hogar', 'source' => 'payload'],
    ['key' => 'rt_cb_interes_social_no', 'label' => '☑ Interes Social No', 'type' => 'checkbox', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'rt_cb_interes_social_si', 'label' => '☑ Interes Social Si', 'type' => 'checkbox', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'rt_cb_notif_apartado', 'label' => '☑ Notif Apartado', 'type' => 'checkbox', 'section' => 'Notificaciones', 'source' => 'payload'],
    ['key' => 'rt_cb_notif_correo', 'label' => '☑ Notif Correo', 'type' => 'checkbox', 'section' => 'Notificaciones', 'source' => 'payload'],
    ['key' => 'rt_cb_notif_domicilio', 'label' => '☑ Notif Domicilio', 'type' => 'checkbox', 'section' => 'Notificaciones', 'source' => 'payload'],
    ['key' => 'rt_cb_notif_fax', 'label' => '☑ Notif Fax', 'type' => 'checkbox', 'section' => 'Notificaciones', 'source' => 'payload'],
    ['key' => 'rt_cb_pago_anual', 'label' => '☑ Pago Anual', 'type' => 'checkbox', 'section' => 'Forma de Pago', 'source' => 'payload'],
    ['key' => 'rt_cb_pago_mensual', 'label' => '☑ Pago Mensual', 'type' => 'checkbox', 'section' => 'Forma de Pago', 'source' => 'payload'],
    ['key' => 'rt_cb_pago_semestral', 'label' => '☑ Pago Semestral', 'type' => 'checkbox', 'section' => 'Forma de Pago', 'source' => 'payload'],
    ['key' => 'rt_cb_pago_trimestral', 'label' => '☑ Pago Trimestral', 'type' => 'checkbox', 'section' => 'Forma de Pago', 'source' => 'payload'],
    ['key' => 'rt_cb_poliza_rt_adolescente', 'label' => '☑ Poliza Rt Adolescente', 'type' => 'checkbox', 'section' => 'Tipo de Póliza', 'source' => 'payload'],
    ['key' => 'rt_cb_poliza_rt_agricola', 'label' => '☑ Poliza Rt Agricola', 'type' => 'checkbox', 'section' => 'Tipo de Póliza', 'source' => 'payload'],
    ['key' => 'rt_cb_poliza_rt_construccion_corto', 'label' => '☑ Poliza Rt Construccion Corto', 'type' => 'checkbox', 'section' => 'Tipo de Póliza', 'source' => 'payload'],
    ['key' => 'rt_cb_poliza_rt_cosechas_corto', 'label' => '☑ Poliza Rt Cosechas Corto', 'type' => 'checkbox', 'section' => 'Tipo de Póliza', 'source' => 'payload'],
    ['key' => 'rt_cb_poliza_rt_formacion_dual_corto', 'label' => '☑ Poliza Rt Formacion Dual Corto', 'type' => 'checkbox', 'section' => 'Tipo de Póliza', 'source' => 'payload'],
    ['key' => 'rt_cb_poliza_rt_formacion_dual_perm', 'label' => '☑ Poliza Rt Formacion Dual Perm', 'type' => 'checkbox', 'section' => 'Tipo de Póliza', 'source' => 'payload'],
    ['key' => 'rt_cb_poliza_rt_general_corto', 'label' => '☑ Poliza Rt General Corto', 'type' => 'checkbox', 'section' => 'Tipo de Póliza', 'source' => 'payload'],
    ['key' => 'rt_cb_poliza_rt_general_perm', 'label' => '☑ Poliza Rt General Perm', 'type' => 'checkbox', 'section' => 'Tipo de Póliza', 'source' => 'payload'],
    ['key' => 'rt_cb_poliza_rt_hogar', 'label' => '☑ Poliza Rt Hogar', 'type' => 'checkbox', 'section' => 'Tipo de Póliza', 'source' => 'payload'],
    ['key' => 'rt_cb_poliza_rt_ocasional', 'label' => '☑ Poliza Rt Ocasional', 'type' => 'checkbox', 'section' => 'Tipo de Póliza', 'source' => 'payload'],
    ['key' => 'rt_cb_poliza_rt_sector_publico', 'label' => '☑ Poliza Rt Sector Publico', 'type' => 'checkbox', 'section' => 'Tipo de Póliza', 'source' => 'payload'],
    ['key' => 'rt_cb_tomador_cedula_fisica', 'label' => '☑ Tomador Cedula Fisica', 'type' => 'checkbox', 'section' => 'Tomador', 'source' => 'payload'],
    ['key' => 'rt_cb_tomador_cedula_juridica', 'label' => '☑ Tomador Cedula Juridica', 'type' => 'checkbox', 'section' => 'Tomador', 'source' => 'payload'],
    ['key' => 'rt_cb_tomador_dimex_didi', 'label' => '☑ Tomador Dimex Didi', 'type' => 'checkbox', 'section' => 'Tomador', 'source' => 'payload'],
    ['key' => 'rt_cb_tomador_femenino', 'label' => '☑ Tomador Femenino', 'type' => 'checkbox', 'section' => 'Tomador', 'source' => 'payload'],
    ['key' => 'rt_cb_tomador_masculino', 'label' => '☑ Tomador Masculino', 'type' => 'checkbox', 'section' => 'Tomador', 'source' => 'payload'],
    ['key' => 'rt_cb_tomador_pasaporte', 'label' => '☑ Tomador Pasaporte', 'type' => 'checkbox', 'section' => 'Tomador', 'source' => 'payload'],
    ['key' => 'rt_cb_tramite_emision', 'label' => '☑ Tramite Emision', 'type' => 'checkbox', 'section' => 'Tipo Trámite', 'source' => 'payload'],
    ['key' => 'rt_cb_tramite_rehabilitacion', 'label' => '☑ Tramite Rehabilitacion', 'type' => 'checkbox', 'section' => 'Tipo Trámite', 'source' => 'payload'],
    ['key' => 'rt_consentimientoDatos', 'label' => 'ConsentimientoDatos', 'type' => 'text', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'rt_consentimientoGrabacion', 'label' => 'ConsentimientoGrabacion', 'type' => 'text', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'rt_consentimientoInfo', 'label' => 'ConsentimientoInfo', 'type' => 'text', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'rt_construccion_contrato_cfia_no', 'label' => 'Construccion Contrato Cfia No', 'type' => 'text', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'rt_construccion_permiso_municipal_no', 'label' => 'Construccion Permiso Municipal No', 'type' => 'text', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'rt_construccion_valor_obra', 'label' => 'Construccion Valor Obra', 'type' => 'text', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'rt_cosechas_cantidad_unidades', 'label' => 'Cosechas Cantidad Unidades', 'type' => 'text', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'rt_cosechas_fruto_producto', 'label' => 'Cosechas Fruto Producto', 'type' => 'text', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'rt_cosechas_precio_unidad', 'label' => 'Cosechas Precio Unidad', 'type' => 'text', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'rt_cosechas_unidad_medida', 'label' => 'Cosechas Unidad Medida', 'type' => 'text', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'rt_csrf', 'label' => 'Csrf', 'type' => 'text', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'rt_description', 'label' => 'Description', 'type' => 'text', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'rt_firma_tomador', 'label' => 'Firma Tomador', 'type' => 'text', 'section' => 'Tomador', 'source' => 'payload'],
    ['key' => 'rt_representante_identificacion', 'label' => 'Representante Identificacion', 'type' => 'text', 'section' => 'Representante Legal', 'source' => 'payload'],
    ['key' => 'rt_representante_nombre', 'label' => 'Representante Nombre', 'type' => 'text', 'section' => 'Representante Legal', 'source' => 'payload'],
    ['key' => 'rt_representante_puesto', 'label' => 'Representante Puesto', 'type' => 'text', 'section' => 'Representante Legal', 'source' => 'payload'],
    ['key' => 'rt_seguro_actividad_economica', 'label' => 'Seguro Actividad Economica', 'type' => 'text', 'section' => 'Actividad Económica', 'source' => 'payload'],
    ['key' => 'rt_seguro_canton', 'label' => 'Seguro Canton', 'type' => 'text', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'rt_seguro_direccion_trabajo', 'label' => 'Seguro Direccion Trabajo', 'type' => 'text', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'rt_seguro_distrito', 'label' => 'Seguro Distrito', 'type' => 'text', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'rt_seguro_fecha_finaliza_aaaa', 'label' => 'Seguro Fecha Finaliza Aaaa', 'type' => 'text', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'rt_seguro_fecha_finaliza_dd', 'label' => 'Seguro Fecha Finaliza Dd', 'type' => 'text', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'rt_seguro_fecha_finaliza_mm', 'label' => 'Seguro Fecha Finaliza Mm', 'type' => 'text', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'rt_seguro_fecha_inicia_aaaa', 'label' => 'Seguro Fecha Inicia Aaaa', 'type' => 'text', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'rt_seguro_fecha_inicia_dd', 'label' => 'Seguro Fecha Inicia Dd', 'type' => 'text', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'rt_seguro_fecha_inicia_mm', 'label' => 'Seguro Fecha Inicia Mm', 'type' => 'text', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'rt_seguro_monto_planilla_mensual', 'label' => 'Seguro Monto Planilla Mensual', 'type' => 'text', 'section' => 'Planilla', 'source' => 'payload'],
    ['key' => 'rt_seguro_provincia', 'label' => 'Seguro Provincia', 'type' => 'text', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'rt_tomador_apartado_postal', 'label' => 'Tomador Apartado Postal', 'type' => 'text', 'section' => 'Tomador', 'source' => 'payload'],
    ['key' => 'rt_tomador_canton', 'label' => 'Tomador Canton', 'type' => 'text', 'section' => 'Tomador', 'source' => 'payload'],
    ['key' => 'rt_tomador_correo', 'label' => 'Tomador Correo', 'type' => 'text', 'section' => 'Tomador', 'source' => 'payload'],
    ['key' => 'rt_tomador_distrito', 'label' => 'Tomador Distrito', 'type' => 'text', 'section' => 'Tomador', 'source' => 'payload'],
    ['key' => 'rt_tomador_domicilio_senas', 'label' => 'Tomador Domicilio Senas', 'type' => 'text', 'section' => 'Tomador', 'source' => 'payload'],
    ['key' => 'rt_tomador_fax', 'label' => 'Tomador Fax', 'type' => 'text', 'section' => 'Tomador', 'source' => 'payload'],
    ['key' => 'rt_tomador_nacimiento_aaaa', 'label' => 'Tomador Nacimiento Aaaa', 'type' => 'text', 'section' => 'Tomador', 'source' => 'payload'],
    ['key' => 'rt_tomador_nacimiento_dd', 'label' => 'Tomador Nacimiento Dd', 'type' => 'text', 'section' => 'Tomador', 'source' => 'payload'],
    ['key' => 'rt_tomador_nacimiento_mm', 'label' => 'Tomador Nacimiento Mm', 'type' => 'text', 'section' => 'Tomador', 'source' => 'payload'],
    ['key' => 'rt_tomador_nacionalidad', 'label' => 'Tomador Nacionalidad', 'type' => 'text', 'section' => 'Tomador', 'source' => 'payload'],
    ['key' => 'rt_tomador_nombre_razon_social', 'label' => 'Tomador Nombre Razon Social', 'type' => 'text', 'section' => 'Tomador', 'source' => 'payload'],
    ['key' => 'rt_tomador_num_identificacion', 'label' => 'Tomador N° Identificacion', 'type' => 'text', 'section' => 'Tomador', 'source' => 'payload'],
    ['key' => 'rt_tomador_profesion_ocupacion', 'label' => 'Tomador Profesion Ocupacion', 'type' => 'text', 'section' => 'Tomador', 'source' => 'payload'],
    ['key' => 'rt_tomador_provincia', 'label' => 'Tomador Provincia', 'type' => 'text', 'section' => 'Tomador', 'source' => 'payload'],
    ['key' => 'rt_tomador_tel_celular', 'label' => 'Tomador Tel Celular', 'type' => 'text', 'section' => 'Tomador', 'source' => 'payload'],
    ['key' => 'rt_tomador_tel_domicilio', 'label' => 'Tomador Tel Domicilio', 'type' => 'text', 'section' => 'Tomador', 'source' => 'payload'],
    ['key' => 'rt_tomador_tel_oficina', 'label' => 'Tomador Tel Oficina', 'type' => 'text', 'section' => 'Tomador', 'source' => 'payload'],
    ['key' => 'rt_trab<?= $i ?>_apellido1', 'label' => 'Trab<?= $i ?> Apellido1', 'type' => 'text', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'rt_trab<?= $i ?>_apellido2', 'label' => 'Trab<?= $i ?> Apellido2', 'type' => 'text', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'rt_trab<?= $i ?>_dias', 'label' => 'Trab<?= $i ?> Dias', 'type' => 'text', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'rt_trab<?= $i ?>_fecha_nacimiento', 'label' => 'Trab<?= $i ?> Fecha Nacimiento', 'type' => 'text', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'rt_trab<?= $i ?>_horas', 'label' => 'Trab<?= $i ?> Horas', 'type' => 'text', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'rt_trab<?= $i ?>_nacionalidad', 'label' => 'Trab<?= $i ?> Nacionalidad', 'type' => 'text', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'rt_trab<?= $i ?>_nombre', 'label' => 'Trab<?= $i ?> Nombre', 'type' => 'text', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'rt_trab<?= $i ?>_num_id', 'label' => 'Trab<?= $i ?> N° Id', 'type' => 'text', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'rt_trab<?= $i ?>_ocupacion', 'label' => 'Trab<?= $i ?> Ocupacion', 'type' => 'text', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'rt_trab<?= $i ?>_salario_mensual', 'label' => 'Trab<?= $i ?> Salario Mensual', 'type' => 'text', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'rt_trab<?= $i ?>_sexo', 'label' => 'Trab<?= $i ?> Sexo', 'type' => 'text', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'rt_trab<?= $i ?>_tipo_id', 'label' => 'Trab<?= $i ?> Tipo Id', 'type' => 'text', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'rt_trab<?= $i ?>_tipo_jornada', 'label' => 'Trab<?= $i ?> Tipo Jornada', 'type' => 'text', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'rt_viewport', 'label' => 'Viewport', 'type' => 'text', 'section' => 'General', 'source' => 'payload'],
    ['key' => 'rt_website', 'label' => 'Website', 'type' => 'text', 'section' => 'General', 'source' => 'payload'],
];

// Seleccionar campos según tipo de póliza
$camposPorTipo = [
    'hogar' => $camposHogar,
    'autos' => $camposAutos,
    'rt' => $camposRT
];
$campos = $camposPorTipo[$tipoPoliza];

// Agrupar por sección
$camposPorSeccion = [];
foreach ($campos as $campo) {
    $section = $campo['section'] ?? 'General';
    if (!isset($camposPorSeccion[$section])) {
        $camposPorSeccion[$section] = [];
    }
    $camposPorSeccion[$section][] = $campo;
}

// Colores por source
$sourceColors = [
    'payload' => ['bg' => 'blue', 'text' => 'Dato del formulario web'],
    'sistema' => ['bg' => 'green', 'text' => 'Generado por sistema'],
    'fijo' => ['bg' => 'purple', 'text' => 'Valor fijo/predeterminado'],
    'ins' => ['bg' => 'orange', 'text' => 'Llenado por INS (no tocar)']
];

// Cargar mapeo existente
$currentMapping = [];
$selectedPdf = $_GET['pdf'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF Field Mapper - <?= ucfirst($tipoPoliza) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <style>
        .field-item { cursor: grab; user-select: none; font-size: 11px; }
        .field-item:active { cursor: grabbing; }
        .field-item.source-ins { opacity: 0.5; cursor: not-allowed; }
        .pdf-container { position: relative; display: inline-block; }
        .placed-field {
            position: absolute;
            color: white;
            padding: 1px 4px;
            border-radius: 2px;
            font-size: 9px;
            cursor: move;
            white-space: nowrap;
            z-index: 10;
            border: 1px solid rgba(255,255,255,0.5);
        }
        .placed-field.source-payload { background: rgba(59, 130, 246, 0.85); }
        .placed-field.source-sistema { background: rgba(16, 185, 129, 0.85); }
        .placed-field.source-fijo { background: rgba(147, 51, 234, 0.85); }
        .placed-field.source-ins { background: rgba(249, 115, 22, 0.85); }
        .placed-field:hover { filter: brightness(0.8); cursor: pointer; }
        .placed-field.selected { outline: 2px solid #fff; box-shadow: 0 0 0 4px rgba(255,0,0,0.8); z-index: 10; }
        .drop-zone { position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 5; }
        #pdfCanvas { border: 2px solid #e5e7eb; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .section-header { font-size: 11px; font-weight: 600; padding: 4px 8px; background: #f3f4f6; margin-top: 8px; border-radius: 4px; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="flex h-screen">
        <!-- Panel izquierdo: Campos -->
        <div class="w-80 bg-white shadow-lg p-3 overflow-y-auto">
            <h2 class="text-lg font-bold mb-2 text-gray-800">📋 PDF Field Mapper</h2>

            <!-- Selector de tipo de póliza -->
            <div class="mb-3 flex gap-1">
                <?php foreach ($tiposPoliza as $tipo): ?>
                    <a href="?tipo=<?= $tipo ?>"
                       class="flex-1 text-center py-1 px-2 rounded text-sm <?= $tipo === $tipoPoliza ? 'bg-blue-600 text-white' : 'bg-gray-200 hover:bg-gray-300' ?>">
                        <?= ucfirst($tipo) ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Selector de PDF -->
            <div class="mb-3">
                <select id="pdfSelector" onchange="loadPdf(this.value)" class="w-full border rounded p-2 text-sm">
                    <option value="">-- Seleccionar PDF --</option>
                    <?php foreach ($pdfs as $pdf): ?>
                        <option value="<?= htmlspecialchars($pdf) ?>" <?= $selectedPdf === $pdf ? 'selected' : '' ?>>
                            <?= htmlspecialchars($pdf) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Navegación páginas -->
            <div class="mb-3 flex items-center gap-2 text-sm">
                <button onclick="prevPage()" class="px-2 py-1 bg-gray-200 rounded hover:bg-gray-300">◀</button>
                <span>Pág <span id="pageNum">1</span>/<span id="pageCount">1</span></span>
                <button onclick="nextPage()" class="px-2 py-1 bg-gray-200 rounded hover:bg-gray-300">▶</button>
                <input type="range" id="scaleSlider" min="0.8" max="2" step="0.1" value="1.2" onchange="changeScale(this.value)" class="flex-1">
            </div>

            <!-- Leyenda de colores -->
            <div class="mb-3 text-xs border rounded p-2 bg-gray-50">
                <div class="font-semibold mb-1">Leyenda:</div>
                <div class="flex flex-wrap gap-1">
                    <span class="px-1 bg-blue-500 text-white rounded">Payload</span>
                    <span class="px-1 bg-green-500 text-white rounded">Sistema</span>
                    <span class="px-1 bg-purple-500 text-white rounded">Fijo</span>
                    <span class="px-1 bg-orange-500 text-white rounded">INS ⚠</span>
                </div>
            </div>

            <!-- Lista de campos agrupados -->
            <div class="border-t pt-2 max-h-[50vh] overflow-y-auto">
                <?php foreach ($camposPorSeccion as $section => $camposSeccion): ?>
                    <div class="section-header"><?= $section ?></div>
                    <div class="space-y-1 py-1">
                        <?php foreach ($camposSeccion as $campo):
                            $source = $campo['source'] ?? 'payload';
                            $bgColor = $source === 'ins' ? 'orange' : ($source === 'fijo' ? 'purple' : ($source === 'sistema' ? 'green' : 'blue'));
                            $isIns = $source === 'ins';
                        ?>
                            <div class="field-item bg-<?= $bgColor ?>-100 text-<?= $bgColor ?>-800 px-2 py-1 rounded border border-<?= $bgColor ?>-200 <?= $isIns ? 'source-ins' : '' ?>"
                                 draggable="<?= $isIns ? 'false' : 'true' ?>"
                                 data-key="<?= $campo['key'] ?>"
                                 data-type="<?= $campo['type'] ?>"
                                 data-source="<?= $source ?>"
                                 <?= $isIns ? '' : 'ondragstart="dragStart(event)"' ?>
                                 title="<?= $sourceColors[$source]['text'] ?>">
                                <?= $campo['label'] ?>
                                <?= $isIns ? ' ⚠️' : '' ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Acciones -->
            <div class="mt-4 space-y-2 border-t pt-3">
                <button onclick="saveMapping()" class="w-full bg-green-600 text-white py-2 rounded text-sm font-semibold hover:bg-green-700">
                    💾 Guardar Mapeo
                </button>
                <button onclick="exportCode()" class="w-full bg-purple-600 text-white py-2 rounded text-sm font-semibold hover:bg-purple-700">
                    📝 Exportar PHP
                </button>
                <button onclick="clearAll()" class="w-full bg-red-600 text-white py-2 rounded text-sm font-semibold hover:bg-red-700">
                    🗑️ Limpiar
                </button>
                <button onclick="runDiagnostics()" class="w-full bg-yellow-600 text-white py-2 rounded text-sm font-semibold hover:bg-yellow-700">
                    🔧 Diagnóstico
                </button>
                <a href="/admin/dashboard.php" class="block w-full bg-gray-600 text-white py-2 rounded text-sm font-semibold hover:bg-gray-700 text-center">
                    ← Volver
                </a>
            </div>
        </div>

        <!-- Panel central: PDF -->
        <div class="flex-1 p-4 overflow-auto bg-gray-200">
            <div class="pdf-container" id="pdfContainer">
                <canvas id="pdfCanvas"></canvas>
                <div class="drop-zone" id="dropZone" ondragover="dragOver(event)" ondrop="drop(event)"></div>
            </div>
        </div>

        <!-- Panel derecho: Info -->
        <div class="w-64 bg-white shadow-lg p-3 overflow-y-auto">
            <h3 class="font-bold text-gray-800 mb-2">📍 Campos Mapeados</h3>
            <div id="mappedFields" class="space-y-1 text-xs max-h-[60vh] overflow-y-auto">
                <p class="text-gray-500">Arrastra campos al PDF</p>
            </div>
            <div class="mt-4 border-t pt-3">
                <div class="text-xs font-semibold text-gray-600 mb-1">Cursor:</div>
                <div class="bg-gray-100 p-2 rounded font-mono text-xs">
                    X: <span id="cursorX">0</span> | Y: <span id="cursorY">0</span>
                </div>
            </div>

            <div class="mt-3 border-t pt-3">
                <div class="text-xs font-semibold text-gray-600 mb-1">⌨️ Controles:</div>
                <div class="text-xs text-gray-500 space-y-1">
                    <div>• Click en campo = seleccionar</div>
                    <div>• Flechas = mover ±0.1mm</div>
                    <div>• Shift+Flechas = ±0.5mm</div>
                    <div>• Delete = eliminar</div>
                    <div>• Doble click = eliminar</div>
                    <div>• Esc = deseleccionar</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let pdfDoc = null, pageNum = 1, pageCount = 0, scale = 1.2;
        let placedFields = {};
        let canvas = document.getElementById('pdfCanvas');
        let ctx = canvas.getContext('2d');
        const PIXELS_PER_MM = 72 / 25.4;
        const tipoPoliza = '<?= $tipoPoliza ?>';

        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

        function loadPdf(filename) {
            if (!filename) return;
            const url = '/formulariosbase/<?= $tipoPoliza ?>/' + filename;
            const loadingTask = pdfjsLib.getDocument({
                url: url,
                standardFontDataUrl: 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/standard_fonts/',
                cMapUrl: 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/cmaps/',
                cMapPacked: true
            });
            loadingTask.promise.then(function(pdf) {
                pdfDoc = pdf;
                pageCount = pdf.numPages;
                document.getElementById('pageCount').textContent = pageCount;
                pageNum = 1;

                // Cargar mapeo existente si existe
                loadExistingMapping(filename);

                renderPage(pageNum);
                window.history.pushState({}, '', '?tipo=<?= $tipoPoliza ?>&pdf=' + encodeURIComponent(filename));
            }).catch(err => alert('Error: ' + err.message));
        }

        // Cargar mapeo existente del servidor
        function loadExistingMapping(filename) {
            const mappingName = filename.replace('.pdf', '') + '_mapping.json';
            fetch('/admin/get_mapping.php?file=' + encodeURIComponent(mappingName), { credentials: 'same-origin' })
                .then(r => {
                    if (!r.ok) {
                        console.log('No existe mapeo previo para este PDF');
                        placedFields = {};
                        updateMappedFieldsList();
                        return null;
                    }
                    return r.json();
                })
                .then(data => {
                    if (data && data.fields) {
                        // Convertir array de campos a objeto placedFields
                        placedFields = {};
                        data.fields.forEach(field => {
                            const id = field.id || (field.key + '_' + field.page);
                            placedFields[id] = {
                                key: field.key,
                                label: field.label,
                                type: field.type,
                                source: field.source || 'payload',
                                page: field.page,
                                x: field.x,
                                y: field.y,
                                pixelX: field.x * PIXELS_PER_MM,
                                pixelY: field.y * PIXELS_PER_MM
                            };
                        });
                        console.log('Mapeo cargado:', Object.keys(placedFields).length, 'campos');
                        updateMappedFieldsList();
                        renderPlacedFields();
                    }
                })
                .catch(err => {
                    console.log('Sin mapeo previo:', err.message);
                    placedFields = {};
                });
        }

        function renderPage(num) {
            pdfDoc.getPage(num).then(function(page) {
                const viewport = page.getViewport({ scale: scale });
                canvas.height = viewport.height;
                canvas.width = viewport.width;
                page.render({ canvasContext: ctx, viewport: viewport }).promise.then(() => {
                    document.getElementById('pageNum').textContent = num;
                    renderPlacedFields();
                });
            });
        }

        function prevPage() { if (pageNum > 1) { pageNum--; renderPage(pageNum); } }
        function nextPage() { if (pageNum < pageCount) { pageNum++; renderPage(pageNum); } }
        function changeScale(v) { scale = parseFloat(v); if (pdfDoc) renderPage(pageNum); }

        let draggedField = null;
        function dragStart(e) {
            if (e.target.dataset.source === 'ins') { e.preventDefault(); return; }
            draggedField = {
                key: e.target.dataset.key,
                type: e.target.dataset.type,
                source: e.target.dataset.source,
                label: e.target.textContent.trim()
            };
        }

        function dragOver(e) {
            e.preventDefault();
            const rect = canvas.getBoundingClientRect();
            const x = (e.clientX - rect.left) / scale;
            const y = (e.clientY - rect.top) / scale;
            document.getElementById('cursorX').textContent = (x / PIXELS_PER_MM).toFixed(1);
            document.getElementById('cursorY').textContent = (y / PIXELS_PER_MM).toFixed(1);
        }

        function drop(e) {
            e.preventDefault();
            if (!draggedField) return;
            const rect = canvas.getBoundingClientRect();
            const x = (e.clientX - rect.left) / scale;
            const y = (e.clientY - rect.top) / scale;
            const xMm = x / PIXELS_PER_MM;
            const yMm = y / PIXELS_PER_MM;

            const fieldId = draggedField.key + '_p' + pageNum;
            placedFields[fieldId] = {
                ...draggedField,
                page: pageNum,
                x: Math.round(xMm * 10) / 10,
                y: Math.round(yMm * 10) / 10,
                pixelX: x,
                pixelY: y
            };
            renderPlacedFields();
            updateMappedFieldsList();
            draggedField = null;
        }

        let selectedFieldId = null;

        function renderPlacedFields() {
            document.querySelectorAll('.placed-field').forEach(el => el.remove());
            for (const [id, field] of Object.entries(placedFields)) {
                if (field.page !== pageNum) continue;
                const div = document.createElement('div');
                div.className = 'placed-field source-' + field.source;
                if (id === selectedFieldId) div.classList.add('selected');
                div.textContent = field.label.substring(0, 20);
                div.style.left = (field.pixelX * scale) + 'px';
                div.style.top = (field.pixelY * scale) + 'px';
                div.dataset.fieldId = id;

                // Click para seleccionar (ajuste con flechas)
                div.onclick = (e) => {
                    e.stopPropagation();
                    selectedFieldId = id;
                    renderPlacedFields();
                };

                // Doble click para eliminar
                div.ondblclick = (e) => {
                    e.stopPropagation();
                    if(confirm('¿Eliminar campo?')) {
                        delete placedFields[id];
                        selectedFieldId = null;
                        renderPlacedFields();
                        updateMappedFieldsList();
                    }
                };

                document.getElementById('dropZone').appendChild(div);
            }
        }

        // Ajuste fino con flechas del teclado
        document.addEventListener('keydown', function(e) {
            if (!selectedFieldId || !placedFields[selectedFieldId]) return;

            const step = e.shiftKey ? 0.5 : 0.1; // Shift = pasos más grandes
            const field = placedFields[selectedFieldId];
            let moved = false;

            switch(e.key) {
                case 'ArrowLeft':
                    field.x = Math.round((field.x - step) * 10) / 10;
                    field.pixelX = field.x * PIXELS_PER_MM;
                    moved = true;
                    break;
                case 'ArrowRight':
                    field.x = Math.round((field.x + step) * 10) / 10;
                    field.pixelX = field.x * PIXELS_PER_MM;
                    moved = true;
                    break;
                case 'ArrowUp':
                    field.y = Math.round((field.y - step) * 10) / 10;
                    field.pixelY = field.y * PIXELS_PER_MM;
                    moved = true;
                    break;
                case 'ArrowDown':
                    field.y = Math.round((field.y + step) * 10) / 10;
                    field.pixelY = field.y * PIXELS_PER_MM;
                    moved = true;
                    break;
                case 'Escape':
                    selectedFieldId = null;
                    renderPlacedFields();
                    break;
                case 'Delete':
                case 'Backspace':
                    if(confirm('¿Eliminar campo seleccionado?')) {
                        delete placedFields[selectedFieldId];
                        selectedFieldId = null;
                        updateMappedFieldsList();
                    }
                    moved = true;
                    break;
            }

            if (moved) {
                e.preventDefault();
                renderPlacedFields();
                updateMappedFieldsList();
            }
        });

        // Deseleccionar al hacer click en el fondo
        document.getElementById('dropZone').addEventListener('click', function(e) {
            if (e.target === this) {
                selectedFieldId = null;
                renderPlacedFields();
            }
        });

        function updateMappedFieldsList() {
            const container = document.getElementById('mappedFields');
            if (!Object.keys(placedFields).length) { container.innerHTML = '<p class="text-gray-500">Sin campos</p>'; return; }
            let html = '';
            for (const [id, f] of Object.entries(placedFields)) {
                html += `<div class="bg-gray-100 p-1 rounded text-xs"><b>${f.label.substring(0,15)}</b><br>P${f.page} X:${f.x} Y:${f.y}</div>`;
            }
            container.innerHTML = html;
        }

        function saveMapping() {
            const pdfName = document.getElementById('pdfSelector').value;
            if (!pdfName) { alert('Selecciona un PDF primero'); return; }

            const fieldCount = Object.keys(placedFields).length;
            if (fieldCount === 0) {
                if (!confirm('No hay campos colocados. ¿Deseas guardar un mapeo vacío?')) return;
            }

            const data = { pdf: pdfName, tipo: tipoPoliza, fields: placedFields };
            console.log('=== GUARDANDO MAPEO ===');
            console.log('PDF:', pdfName);
            console.log('Tipo:', tipoPoliza);
            console.log('Campos:', fieldCount);
            console.log('Data:', JSON.stringify(data, null, 2));

            // Mostrar indicador de carga
            const saveBtn = document.querySelector('button[onclick="saveMapping()"]');
            const originalText = saveBtn.innerHTML;
            saveBtn.innerHTML = '⏳ Guardando...';
            saveBtn.disabled = true;

            fetch('/admin/pdf_mapper_save.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify(data)
            })
            .then(r => {
                console.log('Response status:', r.status);
                return r.text().then(text => {
                    console.log('Response body:', text);

                    // Restaurar botón
                    saveBtn.innerHTML = originalText;
                    saveBtn.disabled = false;

                    if (!r.ok) {
                        // Si es 401, la sesión expiró
                        if (r.status === 401) {
                            alert('⚠️ Tu sesión ha expirado.\n\nRecarga la página e inicia sesión nuevamente.');
                            return null;
                        }
                        // Intentar parsear como JSON para mostrar error específico
                        try {
                            const errData = JSON.parse(text);
                            throw new Error(errData.error || 'Error HTTP ' + r.status);
                        } catch(e) {
                            if (e.message.includes('Error HTTP') || e.message.includes('sesión')) throw e;
                            throw new Error('Error del servidor: ' + (text.substring(0, 200) || 'Sin respuesta'));
                        }
                    }

                    // Verificar que sea JSON válido
                    try {
                        return JSON.parse(text);
                    } catch(e) {
                        console.error('Respuesta no es JSON:', text);
                        throw new Error('Respuesta inválida del servidor. Revisa la consola.');
                    }
                });
            })
            .then(d => {
                if (!d) return; // Sesión expirada, ya se mostró mensaje
                if (d.success) {
                    const action = d.is_update ? '📝 Actualizado' : '✅ Creado';
                    alert(action + '\n' + d.fields_count + ' campos mapeados\nArchivo: ' + d.file);
                } else {
                    alert('❌ Error: ' + d.error);
                }
            })
            .catch(err => {
                // Restaurar botón en caso de error
                saveBtn.innerHTML = originalText;
                saveBtn.disabled = false;
                console.error('Error guardando:', err);
                alert('❌ Error al guardar:\n\n' + err.message + '\n\nRevisa la consola (F12) para más detalles.');
            });
        }

        function exportCode() {
            if (!Object.keys(placedFields).length) { alert('Sin campos'); return; }
            let code = "// Código PHP - PDF Mapper\\n\\n";
            const byPage = {};
            for (const [id, f] of Object.entries(placedFields)) {
                if (!byPage[f.page]) byPage[f.page] = [];
                byPage[f.page].push(f);
            }
            for (const [page, fields] of Object.entries(byPage)) {
                code += `// === PÁGINA ${page} ===\\n`;
                for (const f of fields) {
                    if (f.type === 'checkbox') {
                        code += `\$pdf->SetFont('Arial', 'B', 10);\\n\$pdf->SetXY(${f.x}, ${f.y}); // ${f.label}\\n\$pdf->Cell(3, 3, 'X', 0, 0);\\n\$pdf->SetFont('Arial', '', 9);\\n\\n`;
                    } else {
                        code += `// ${f.label}\\n\$pdf->SetXY(${f.x}, ${f.y});\\n\$pdf->Cell(50, 4, \$${f.key}, 0, 0);\\n\\n`;
                    }
                }
            }
            const m = document.createElement('div');
            m.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.7);display:flex;align-items:center;justify-content:center;z-index:9999';
            m.innerHTML = `<div style="background:white;padding:20px;border-radius:10px;max-width:700px;max-height:80vh;overflow:auto"><h3 style="font-weight:bold;margin-bottom:10px">Código PHP</h3><textarea style="width:100%;height:350px;font-family:monospace;font-size:11px">${code}</textarea><div style="margin-top:10px;text-align:right"><button onclick="navigator.clipboard.writeText(this.parentElement.previousElementSibling.value);alert('Copiado!')" style="background:#10b981;color:white;padding:6px 12px;border-radius:5px;margin-right:5px">Copiar</button><button onclick="this.closest('div').parentElement.remove()" style="background:#6b7280;color:white;padding:6px 12px;border-radius:5px">Cerrar</button></div></div>`;
            document.body.appendChild(m);
        }

        function clearAll() { if(confirm('¿Limpiar todo?')) { placedFields = {}; renderPlacedFields(); updateMappedFieldsList(); }}

        function runDiagnostics() {
            fetch('/admin/pdf_mapper_debug.php', { credentials: 'same-origin' })
                .then(r => r.json())
                .then(data => {
                    let msg = '=== DIAGNÓSTICO ===\n\n';

                    // Sesión
                    msg += '📦 SESIÓN:\n';
                    msg += '  Estado: ' + data.session.status + '\n';
                    msg += '  Admin logueado: ' + (data.session.admin_logged ? 'SÍ ✅' : 'NO ❌') + '\n\n';

                    // Directorios
                    msg += '📁 DIRECTORIOS:\n';
                    msg += '  Externo:\n';
                    msg += '    - Existe: ' + (data.directories.external.exists ? 'SÍ' : 'NO') + '\n';
                    msg += '    - Escribible: ' + (data.directories.external.writable ? 'SÍ ✅' : 'NO ❌') + '\n';
                    msg += '  Local:\n';
                    msg += '    - Existe: ' + (data.directories.local.exists ? 'SÍ' : 'NO') + '\n';
                    msg += '    - Escribible: ' + (data.directories.local.writable ? 'SÍ' : 'NO') + '\n\n';

                    // Test de escritura
                    msg += '✍️ TEST ESCRITURA: ' + (data.write_test.success ? 'OK ✅' : 'FALLO ❌') + '\n\n';

                    // Mapeos existentes
                    msg += '📋 MAPEOS GUARDADOS: ' + data.mappings.length + '\n';
                    data.mappings.forEach(m => {
                        msg += '  - ' + m.file + ' (' + m.dir + ', ' + m.size + ' bytes)\n';
                    });

                    // HTTPS
                    msg += '\n🔒 HTTPS: ' + (data.php.https ? 'SÍ' : 'NO') + '\n';

                    alert(msg);
                    console.log('Diagnóstico completo:', data);
                })
                .catch(err => {
                    alert('Error al ejecutar diagnóstico:\n' + err.message);
                    console.error('Error diagnóstico:', err);
                });
        }

        document.getElementById('dropZone').addEventListener('mousemove', function(e) {
            const rect = canvas.getBoundingClientRect();
            document.getElementById('cursorX').textContent = ((e.clientX - rect.left) / scale / PIXELS_PER_MM).toFixed(1);
            document.getElementById('cursorY').textContent = ((e.clientY - rect.top) / scale / PIXELS_PER_MM).toFixed(1);
        });

        <?php if ($selectedPdf): ?>
        window.onload = () => loadPdf('<?= addslashes($selectedPdf) ?>');
        <?php endif; ?>
    </script>
</body>
</html>
