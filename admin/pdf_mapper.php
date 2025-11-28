<?php
// admin/pdf_mapper.php - Herramienta visual para mapear campos a PDFs
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

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
    ['key' => 'fecha_dd', 'label' => 'Fecha DD', 'type' => 'text', 'section' => 'Encabezado', 'source' => 'sistema'],
    ['key' => 'fecha_mm', 'label' => 'Fecha MM', 'type' => 'text', 'section' => 'Encabezado', 'source' => 'sistema'],
    ['key' => 'fecha_aaaa', 'label' => 'Fecha AAAA', 'type' => 'text', 'section' => 'Encabezado', 'source' => 'sistema'],
    ['key' => 'lugar', 'label' => 'Lugar', 'type' => 'text', 'section' => 'Encabezado', 'source' => 'payload'],
    ['key' => 'hora', 'label' => 'Hora', 'type' => 'text', 'section' => 'Encabezado', 'source' => 'sistema'],

    // === TIPO DE TRÁMITE ===
    ['key' => 'cb_emision', 'label' => '☑ Emisión', 'type' => 'checkbox', 'section' => 'Tipo Trámite', 'source' => 'fijo'],
    ['key' => 'cb_inclusion', 'label' => '☑ Inclusión en colectiva', 'type' => 'checkbox', 'section' => 'Tipo Trámite', 'source' => 'payload'],
    ['key' => 'cb_variacion', 'label' => '☑ Variación', 'type' => 'checkbox', 'section' => 'Tipo Trámite', 'source' => 'payload'],
    ['key' => 'cb_cotizacion', 'label' => '☑ Cotización', 'type' => 'checkbox', 'section' => 'Tipo Trámite', 'source' => 'payload'],
    ['key' => 'num_poliza_colectiva', 'label' => 'N° Póliza Colectiva', 'type' => 'text', 'section' => 'Tipo Trámite', 'source' => 'ins'],
    ['key' => 'num_poliza_individual', 'label' => 'N° Póliza Individual', 'type' => 'text', 'section' => 'Tipo Trámite', 'source' => 'ins'],

    // === DATOS DEL TOMADOR ===
    ['key' => 'tomador_nombre', 'label' => 'Nombre Completo Tomador', 'type' => 'text', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'cb_tomador_pj_nacional', 'label' => '☑ PJ Nacional', 'type' => 'checkbox', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'cb_tomador_pj_extranjera', 'label' => '☑ PJ Extranjera', 'type' => 'checkbox', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'cb_tomador_gobierno', 'label' => '☑ Gobierno', 'type' => 'checkbox', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'cb_tomador_inst_autonoma', 'label' => '☑ Institución Autónoma', 'type' => 'checkbox', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'cb_tomador_pf_cedula', 'label' => '☑ PF Cédula', 'type' => 'checkbox', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'cb_tomador_pf_dimex', 'label' => '☑ PF DIMEX', 'type' => 'checkbox', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'cb_tomador_pf_didi', 'label' => '☑ PF DIDI', 'type' => 'checkbox', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'cb_tomador_pf_pasaporte', 'label' => '☑ PF Pasaporte', 'type' => 'checkbox', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'cb_tomador_otro', 'label' => '☑ Otro Tipo ID', 'type' => 'checkbox', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'tomador_otro_tipo', 'label' => 'Especificar Otro Tipo', 'type' => 'text', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'tomador_num_id', 'label' => 'N° Identificación Tomador', 'type' => 'text', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'tomador_pais', 'label' => 'País Tomador', 'type' => 'text', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'tomador_provincia', 'label' => 'Provincia Tomador', 'type' => 'text', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'tomador_canton', 'label' => 'Cantón Tomador', 'type' => 'text', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'tomador_distrito', 'label' => 'Distrito Tomador', 'type' => 'text', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'tomador_direccion', 'label' => 'Dirección Exacta Tomador', 'type' => 'text', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'tomador_tel_oficina', 'label' => 'Teléfono Oficina Tomador', 'type' => 'text', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'tomador_tel_domicilio', 'label' => 'Teléfono Domicilio Tomador', 'type' => 'text', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'tomador_tel_celular', 'label' => 'Teléfono Celular Tomador', 'type' => 'text', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'tomador_correo', 'label' => 'Correo Tomador', 'type' => 'text', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'cb_relacion_familiar', 'label' => '☑ Relación Familiar', 'type' => 'checkbox', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'cb_relacion_comercial', 'label' => '☑ Relación Comercial', 'type' => 'checkbox', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'cb_relacion_laboral', 'label' => '☑ Relación Laboral', 'type' => 'checkbox', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'cb_relacion_otro', 'label' => '☑ Relación Otro', 'type' => 'checkbox', 'section' => 'Datos Tomador', 'source' => 'payload'],
    ['key' => 'relacion_otro_texto', 'label' => 'Especificar Relación', 'type' => 'text', 'section' => 'Datos Tomador', 'source' => 'payload'],

    // === DATOS DEL ASEGURADO ===
    ['key' => 'asegurado_nombre', 'label' => 'Nombre Completo Asegurado', 'type' => 'text', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'cb_asegurado_pf_cedula', 'label' => '☑ Aseg. Cédula', 'type' => 'checkbox', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'cb_asegurado_pf_dimex', 'label' => '☑ Aseg. DIMEX', 'type' => 'checkbox', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'cb_asegurado_pf_didi', 'label' => '☑ Aseg. DIDI', 'type' => 'checkbox', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'cb_asegurado_pf_pasaporte', 'label' => '☑ Aseg. Pasaporte', 'type' => 'checkbox', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'asegurado_num_id', 'label' => 'N° Identificación Asegurado', 'type' => 'text', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'asegurado_pais', 'label' => 'País Asegurado', 'type' => 'text', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'asegurado_provincia', 'label' => 'Provincia Asegurado', 'type' => 'text', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'asegurado_canton', 'label' => 'Cantón Asegurado', 'type' => 'text', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'asegurado_distrito', 'label' => 'Distrito Asegurado', 'type' => 'text', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'asegurado_direccion', 'label' => 'Dirección Exacta Asegurado', 'type' => 'text', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'asegurado_tel_oficina', 'label' => 'Teléfono Oficina Asegurado', 'type' => 'text', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'asegurado_tel_domicilio', 'label' => 'Teléfono Domicilio Asegurado', 'type' => 'text', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'asegurado_tel_celular', 'label' => 'Teléfono Celular Asegurado', 'type' => 'text', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'asegurado_correo', 'label' => 'Correo Asegurado', 'type' => 'text', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'cb_notif_tomador', 'label' => '☑ Notificar Tomador', 'type' => 'checkbox', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'cb_notif_asegurado', 'label' => '☑ Notificar Asegurado', 'type' => 'checkbox', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'cb_notif_correo', 'label' => '☑ Notificar por Correo', 'type' => 'checkbox', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'cb_notif_residencia', 'label' => '☑ Notificar en Residencia', 'type' => 'checkbox', 'section' => 'Datos Asegurado', 'source' => 'payload'],
    ['key' => 'cb_notif_otro', 'label' => '☑ Notificar Otro Medio', 'type' => 'checkbox', 'section' => 'Datos Asegurado', 'source' => 'payload'],

    // === DATOS DE LA PROPIEDAD ===
    ['key' => 'prop_latitud', 'label' => 'Latitud Propiedad', 'type' => 'text', 'section' => 'Datos Propiedad', 'source' => 'payload'],
    ['key' => 'prop_longitud', 'label' => 'Longitud Propiedad', 'type' => 'text', 'section' => 'Datos Propiedad', 'source' => 'payload'],
    ['key' => 'cb_esquina_si', 'label' => '☑ En Esquina Sí', 'type' => 'checkbox', 'section' => 'Datos Propiedad', 'source' => 'payload'],
    ['key' => 'cb_esquina_no', 'label' => '☑ En Esquina No', 'type' => 'checkbox', 'section' => 'Datos Propiedad', 'source' => 'payload'],
    ['key' => 'prop_pais', 'label' => 'País Propiedad', 'type' => 'text', 'section' => 'Datos Propiedad', 'source' => 'payload'],
    ['key' => 'prop_provincia', 'label' => 'Provincia Propiedad', 'type' => 'text', 'section' => 'Datos Propiedad', 'source' => 'payload'],
    ['key' => 'prop_canton', 'label' => 'Cantón Propiedad', 'type' => 'text', 'section' => 'Datos Propiedad', 'source' => 'payload'],
    ['key' => 'prop_distrito', 'label' => 'Distrito Propiedad', 'type' => 'text', 'section' => 'Datos Propiedad', 'source' => 'payload'],
    ['key' => 'prop_urbanizacion', 'label' => 'Urbanización/Barrio', 'type' => 'text', 'section' => 'Datos Propiedad', 'source' => 'payload'],
    ['key' => 'cb_tipo_casa', 'label' => '☑ Casa Habitación', 'type' => 'checkbox', 'section' => 'Datos Propiedad', 'source' => 'payload'],
    ['key' => 'cb_tipo_edificio', 'label' => '☑ Edificio', 'type' => 'checkbox', 'section' => 'Datos Propiedad', 'source' => 'payload'],
    ['key' => 'cb_tipo_condominio', 'label' => '☑ Condominio', 'type' => 'checkbox', 'section' => 'Datos Propiedad', 'source' => 'payload'],
    ['key' => 'prop_otras_senas', 'label' => 'Otras Señas', 'type' => 'text', 'section' => 'Datos Propiedad', 'source' => 'payload'],
    ['key' => 'prop_folio_real', 'label' => 'N° Folio Real', 'type' => 'text', 'section' => 'Datos Propiedad', 'source' => 'payload'],

    // === CONSTRUCCIÓN ===
    ['key' => 'cb_ano_antes1974', 'label' => '☑ Antes 1974', 'type' => 'checkbox', 'section' => 'Construcción', 'source' => 'payload'],
    ['key' => 'cb_ano_1974_1985', 'label' => '☑ 1974-1985', 'type' => 'checkbox', 'section' => 'Construcción', 'source' => 'payload'],
    ['key' => 'cb_ano_1986_2001', 'label' => '☑ 1986-2001', 'type' => 'checkbox', 'section' => 'Construcción', 'source' => 'payload'],
    ['key' => 'cb_ano_2002_2009', 'label' => '☑ 2002-2009', 'type' => 'checkbox', 'section' => 'Construcción', 'source' => 'payload'],
    ['key' => 'cb_ano_2010_actual', 'label' => '☑ 2010-Actual', 'type' => 'checkbox', 'section' => 'Construcción', 'source' => 'payload'],
    ['key' => 'area_construccion', 'label' => 'Área Construcción m²', 'type' => 'text', 'section' => 'Construcción', 'source' => 'payload'],
    ['key' => 'cb_area_igual_si', 'label' => '☑ Área por Piso Igual Sí', 'type' => 'checkbox', 'section' => 'Construcción', 'source' => 'payload'],
    ['key' => 'cb_area_igual_no', 'label' => '☑ Área por Piso Igual No', 'type' => 'checkbox', 'section' => 'Construcción', 'source' => 'payload'],
    ['key' => 'cantidad_pisos', 'label' => 'Cantidad de Pisos', 'type' => 'text', 'section' => 'Construcción', 'source' => 'payload'],
    ['key' => 'piso_ubicacion', 'label' => 'En qué Piso se Ubica', 'type' => 'text', 'section' => 'Construcción', 'source' => 'payload'],

    // === SISTEMA ELÉCTRICO ===
    ['key' => 'cb_elect_entubado', 'label' => '☑ Entubado Totalmente', 'type' => 'checkbox', 'section' => 'Sist. Eléctrico', 'source' => 'fijo'],
    ['key' => 'cb_elect_parcial', 'label' => '☑ Entubado Parcialmente', 'type' => 'checkbox', 'section' => 'Sist. Eléctrico', 'source' => 'payload'],
    ['key' => 'cb_elect_cuchilla', 'label' => '☑ Cuchilla Principal', 'type' => 'checkbox', 'section' => 'Sist. Eléctrico', 'source' => 'fijo'],
    ['key' => 'cb_elect_breaker', 'label' => '☑ Breaker Principal', 'type' => 'checkbox', 'section' => 'Sist. Eléctrico', 'source' => 'fijo'],
    ['key' => 'cb_elect_caja', 'label' => '☑ Caja de Breaker', 'type' => 'checkbox', 'section' => 'Sist. Eléctrico', 'source' => 'fijo'],
    ['key' => 'cb_elect_polarizado', 'label' => '☑ Tomacorriente Polarizado', 'type' => 'checkbox', 'section' => 'Sist. Eléctrico', 'source' => 'fijo'],

    // === PÁGINA 2: ESTADO Y CONSERVACIÓN ===
    ['key' => 'cb_estado_optimo', 'label' => '☑ Estado Óptimo', 'type' => 'checkbox', 'section' => 'Estado', 'source' => 'fijo'],
    ['key' => 'cb_estado_muy_bueno', 'label' => '☑ Estado Muy Bueno', 'type' => 'checkbox', 'section' => 'Estado', 'source' => 'payload'],
    ['key' => 'cb_estado_bueno', 'label' => '☑ Estado Bueno', 'type' => 'checkbox', 'section' => 'Estado', 'source' => 'payload'],
    ['key' => 'cb_estado_regular', 'label' => '☑ Estado Regular', 'type' => 'checkbox', 'section' => 'Estado', 'source' => 'payload'],
    ['key' => 'cb_modif_si', 'label' => '☑ Modificaciones Sí', 'type' => 'checkbox', 'section' => 'Estado', 'source' => 'payload'],
    ['key' => 'cb_modif_no', 'label' => '☑ Modificaciones No', 'type' => 'checkbox', 'section' => 'Estado', 'source' => 'fijo'],

    // === INTERÉS ASEGURABLE ===
    ['key' => 'cb_interes_propietario', 'label' => '☑ Propietario', 'type' => 'checkbox', 'section' => 'Interés Asegurable', 'source' => 'fijo'],
    ['key' => 'cb_interes_arrendatario', 'label' => '☑ Arrendatario', 'type' => 'checkbox', 'section' => 'Interés Asegurable', 'source' => 'payload'],
    ['key' => 'cb_interes_usufructuario', 'label' => '☑ Usufructuario', 'type' => 'checkbox', 'section' => 'Interés Asegurable', 'source' => 'payload'],
    ['key' => 'actividad_inmueble', 'label' => 'Actividad en Inmueble', 'type' => 'text', 'section' => 'Interés Asegurable', 'source' => 'payload'],
    ['key' => 'detalle_actividad', 'label' => 'Detalle Actividad', 'type' => 'text', 'section' => 'Interés Asegurable', 'source' => 'payload'],
    ['key' => 'cb_ocupado_propietario', 'label' => '☑ Ocupado por Propietario', 'type' => 'checkbox', 'section' => 'Interés Asegurable', 'source' => 'fijo'],
    ['key' => 'cb_ocupado_inquilino', 'label' => '☑ Ocupado por Inquilino', 'type' => 'checkbox', 'section' => 'Interés Asegurable', 'source' => 'payload'],
    ['key' => 'nombre_propietario', 'label' => 'Nombre Propietario Inmueble', 'type' => 'text', 'section' => 'Interés Asegurable', 'source' => 'payload'],
    ['key' => 'cb_gas_si', 'label' => '☑ Usa Gas LP Sí', 'type' => 'checkbox', 'section' => 'Interés Asegurable', 'source' => 'payload'],
    ['key' => 'cb_gas_no', 'label' => '☑ Usa Gas LP No', 'type' => 'checkbox', 'section' => 'Interés Asegurable', 'source' => 'fijo'],

    // === COLINDANTES ===
    ['key' => 'colindante_norte', 'label' => 'Colindante Norte', 'type' => 'text', 'section' => 'Colindantes', 'source' => 'payload'],
    ['key' => 'colindante_sur', 'label' => 'Colindante Sur', 'type' => 'text', 'section' => 'Colindantes', 'source' => 'payload'],
    ['key' => 'colindante_este', 'label' => 'Colindante Este', 'type' => 'text', 'section' => 'Colindantes', 'source' => 'payload'],
    ['key' => 'colindante_oeste', 'label' => 'Colindante Oeste', 'type' => 'text', 'section' => 'Colindantes', 'source' => 'payload'],
    ['key' => 'cb_cerca_ninguna', 'label' => '☑ Cerca de Ninguna', 'type' => 'checkbox', 'section' => 'Colindantes', 'source' => 'fijo'],

    // === TIPO DE CONSTRUCCIÓN ===
    ['key' => 'cb_const_e3', 'label' => '☑ E3 Concreto Reforzado', 'type' => 'checkbox', 'section' => 'Tipo Construcción', 'source' => 'payload'],
    ['key' => 'cb_const_e8', 'label' => '☑ E8 Mixto', 'type' => 'checkbox', 'section' => 'Tipo Construcción', 'source' => 'fijo'],

    // === PRÁCTICAS SOSTENIBLES ===
    ['key' => 'cb_led', 'label' => '☑ Iluminación LED', 'type' => 'checkbox', 'section' => 'Sostenibilidad', 'source' => 'fijo'],

    // === PÁGINA 4: PÓLIZA ===
    ['key' => 'vigencia_desde', 'label' => 'Vigencia Desde', 'type' => 'text', 'section' => 'Póliza', 'source' => 'sistema'],
    ['key' => 'vigencia_hasta', 'label' => 'Vigencia Hasta', 'type' => 'text', 'section' => 'Póliza', 'source' => 'sistema'],
    ['key' => 'cb_moneda_colones', 'label' => '☑ Moneda Colones', 'type' => 'checkbox', 'section' => 'Póliza', 'source' => 'fijo'],
    ['key' => 'cb_moneda_dolares', 'label' => '☑ Moneda Dólares', 'type' => 'checkbox', 'section' => 'Póliza', 'source' => 'payload'],
    ['key' => 'cb_pago_anual', 'label' => '☑ Pago Anual', 'type' => 'checkbox', 'section' => 'Póliza', 'source' => 'payload'],
    ['key' => 'cb_pago_semestral', 'label' => '☑ Pago Semestral', 'type' => 'checkbox', 'section' => 'Póliza', 'source' => 'fijo'],
    ['key' => 'cb_pago_trimestral', 'label' => '☑ Pago Trimestral', 'type' => 'checkbox', 'section' => 'Póliza', 'source' => 'payload'],
    ['key' => 'cb_pago_mensual', 'label' => '☑ Pago Mensual', 'type' => 'checkbox', 'section' => 'Póliza', 'source' => 'payload'],
    ['key' => 'cb_polizas_no', 'label' => '☑ Tiene Otras Pólizas No', 'type' => 'checkbox', 'section' => 'Póliza', 'source' => 'fijo'],
    ['key' => 'cb_aseg_cuenta_propia', 'label' => '☑ Por Cuenta Propia', 'type' => 'checkbox', 'section' => 'Póliza', 'source' => 'fijo'],
    ['key' => 'monto_residencia', 'label' => 'Monto Asegurado Residencia', 'type' => 'text', 'section' => 'Póliza', 'source' => 'payload'],
    ['key' => 'prima_residencia', 'label' => 'Prima Residencia', 'type' => 'text', 'section' => 'Póliza', 'source' => 'ins'],
    ['key' => 'cb_opcion_100', 'label' => '☑ Opción 100%', 'type' => 'checkbox', 'section' => 'Póliza', 'source' => 'fijo'],
    ['key' => 'prima_total', 'label' => 'Prima Total', 'type' => 'text', 'section' => 'Póliza', 'source' => 'ins'],
    ['key' => 'iva', 'label' => 'IVA', 'type' => 'text', 'section' => 'Póliza', 'source' => 'ins'],
    ['key' => 'prima_total_iva', 'label' => 'Prima Total + IVA', 'type' => 'text', 'section' => 'Póliza', 'source' => 'ins'],
    ['key' => 'cb_valor_reposicion', 'label' => '☑ Valor Reposición', 'type' => 'checkbox', 'section' => 'Póliza', 'source' => 'fijo'],

    // === PÁGINA 5: COBERTURAS ===
    ['key' => 'cb_cob_dano_directo', 'label' => '☑ V: Daño Directo', 'type' => 'checkbox', 'section' => 'Coberturas', 'source' => 'fijo'],
    ['key' => 'cb_cob_convulsiones', 'label' => '☑ D: Convulsiones Naturaleza', 'type' => 'checkbox', 'section' => 'Coberturas', 'source' => 'fijo'],
    ['key' => 'cb_cob_multiasistencia', 'label' => '☑ T: Multiasistencia Hogar', 'type' => 'checkbox', 'section' => 'Coberturas', 'source' => 'fijo'],
    ['key' => 'cb_inflacion_no', 'label' => '☑ Protección Inflación No', 'type' => 'checkbox', 'section' => 'Coberturas', 'source' => 'fijo'],

    // === PÁGINA 6: FIRMAS ===
    ['key' => 'firma_asegurado_nombre', 'label' => 'Nombre Asegurado (Firma)', 'type' => 'text', 'section' => 'Firmas', 'source' => 'payload'],
    ['key' => 'firma_asegurado_cedula', 'label' => 'Cédula Asegurado (Firma)', 'type' => 'text', 'section' => 'Firmas', 'source' => 'payload'],
    ['key' => 'firma_agente', 'label' => 'Nombre Agente', 'type' => 'text', 'section' => 'Firmas', 'source' => 'fijo'],
];

// Seleccionar campos según tipo de póliza
$camposPorTipo = [
    'hogar' => $camposHogar,
    'autos' => [], // TODO: definir campos de autos
    'rt' => []     // TODO: definir campos de riesgos de trabajo
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
        .placed-field:hover { filter: brightness(0.8); }
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
        </div>
    </div>

    <script>
        let pdfDoc = null, pageNum = 1, pageCount = 0, scale = 1.2;
        let placedFields = {};
        let canvas = document.getElementById('pdfCanvas');
        let ctx = canvas.getContext('2d');
        const PIXELS_PER_MM = 72 / 25.4;
        const tipoPoliza = '<?= $tipoPoliza ?>';

        // Initialize canvas with default size
        canvas.width = 600;
        canvas.height = 800;
        ctx.fillStyle = '#f9fafb';
        ctx.fillRect(0, 0, 600, 800);
        ctx.fillStyle = '#9ca3af';
        ctx.font = '14px Arial';
        ctx.fillText('Selecciona un PDF para comenzar', 150, 400);

        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

        function loadPdf(filename) {
            if (!filename) return;
            const url = '/formulariosbase/<?= $tipoPoliza ?>/' + encodeURIComponent(filename);
            console.log('Loading PDF:', url);

            // Show loading
            ctx.fillStyle = '#f3f4f6';
            ctx.fillRect(0, 0, canvas.width || 600, canvas.height || 800);
            ctx.fillStyle = '#6b7280';
            ctx.font = '16px Arial';
            ctx.fillText('Cargando PDF...', 50, 100);

            pdfjsLib.getDocument(url).promise.then(function(pdf) {
                console.log('PDF loaded, pages:', pdf.numPages);
                pdfDoc = pdf;
                pageCount = pdf.numPages;
                document.getElementById('pageCount').textContent = pageCount;
                pageNum = 1;
                renderPage(pageNum);
                window.history.pushState({}, '', '?tipo=<?= $tipoPoliza ?>&pdf=' + encodeURIComponent(filename));
            }).catch(function(err) {
                console.error('PDF Error:', err);
                ctx.fillStyle = '#fef2f2';
                ctx.fillRect(0, 0, canvas.width || 600, canvas.height || 800);
                ctx.fillStyle = '#dc2626';
                ctx.font = '14px Arial';
                ctx.fillText('Error al cargar PDF:', 20, 50);
                ctx.fillText(err.message || 'Desconocido', 20, 80);
                ctx.fillText('URL: ' + url, 20, 110);
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

        function renderPlacedFields() {
            document.querySelectorAll('.placed-field').forEach(el => el.remove());
            for (const [id, field] of Object.entries(placedFields)) {
                if (field.page !== pageNum) continue;
                const div = document.createElement('div');
                div.className = 'placed-field source-' + field.source;
                div.textContent = field.label.substring(0, 20);
                div.style.left = (field.pixelX * scale) + 'px';
                div.style.top = (field.pixelY * scale) + 'px';
                div.ondblclick = () => { if(confirm('¿Eliminar?')) { delete placedFields[id]; renderPlacedFields(); updateMappedFieldsList(); }};
                document.getElementById('dropZone').appendChild(div);
            }
        }

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
            if (!pdfName) { alert('Selecciona PDF'); return; }
            fetch('/admin/pdf_mapper_save.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ pdf: pdfName, tipo: tipoPoliza, fields: placedFields })
            }).then(r => r.json()).then(d => alert(d.success ? '✅ Guardado' : '❌ ' + d.error));
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
