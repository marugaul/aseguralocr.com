<?php
// admin/pdf_generator_v4_ins_precise.php - Generador con coordenadas precisas del INS
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// === LOGGING SETUP ===
$logDir = __DIR__ . '/../logs';
$logFile = $logDir . '/pdf_generator_errors.log';
if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
if (!file_exists($logFile)) @file_put_contents($logFile, "");

function pdf_log($msg) {
    global $logFile;
    $time = date('Y-m-d H:i:s');
    $entry = "[$time] $msg\n";
    @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
}

ini_set('display_errors', '1');
ini_set('log_errors', '1');
ini_set('error_log', $logFile);
error_reporting(E_ALL);

try {
    pdf_log("=== INICIO GENERACIÓN PDF V4 (COORDENADAS PRECISAS) ===");

    require_admin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: /admin/dashboard.php');
        exit;
    }

    $cotizacion_id = isset($_POST['cotizacion_id']) ? intval($_POST['cotizacion_id']) : 0;
    $submission_id = isset($_POST['submission_id']) ? intval($_POST['submission_id']) : 0;

    $payload = null;
    $source = '';
    $source_ref = '';

    if ($submission_id) {
        $stmt = $pdo->prepare("SELECT * FROM submissions WHERE id = ?");
        $stmt->execute([$submission_id]);
        $row = $stmt->fetch();
        if (!$row) die("Submission no encontrado");
        $payload = json_decode($row['payload'], true);
        $source = 'submission';
        $source_ref = $submission_id;
    } elseif ($cotizacion_id) {
        $stmt = $pdo->prepare("SELECT * FROM cotizaciones WHERE id = ?");
        $stmt->execute([$cotizacion_id]);
        $row = $stmt->fetch();
        if (!$row) die("Cotización no encontrada");
        $payload = json_decode($row['payload'], true);
        $source = 'cotizacion';
        $source_ref = $cotizacion_id;
    } else {
        die("No se proporcionó cotización ni submission");
    }

    if (!$payload) die("Error: Datos JSON inválidos");

    // Output directory
    $output_dir = __DIR__ . '/../formulariosparaemision/hogar/';
    if (!is_dir($output_dir)) @mkdir($output_dir, 0755, true);

    // Composer autoload
    $autoload_paths = [
        __DIR__ . '/../composer/vendor/autoload.php',
        '/home/asegural/public_html/composer/vendor/autoload.php',
        __DIR__ . '/../../composer/vendor/autoload.php',
        __DIR__ . '/../vendor/autoload.php'
    ];

    $autoload = null;
    foreach ($autoload_paths as $path) {
        if (file_exists($path)) {
            $autoload = $path;
            break;
        }
    }

    if (!$autoload) die("Falta vendor/autoload.php");
    require_once $autoload;

    // Helper para extraer valores del payload
    function getPayloadValue($payload, $key, $default = '') {
        return isset($payload[$key]) && $payload[$key] !== '' ? $payload[$key] : $default;
    }

    // Template del INS
    $template_path = __DIR__ . '/../formulariosbase/hogar/formulario_ins_v2.pdf';
    if (!file_exists($template_path)) die("Template del INS no encontrado");

    // Crear PDF con FPDI
    $pdf = new setasign\Fpdi\Fpdi();
    $pdf->SetAutoPageBreak(false);

    // Importar template
    $pageCount = $pdf->setSourceFile($template_path);
    pdf_log("Template INS cargado - $pageCount páginas");

    // ==================== PÁGINA 1 ====================
    $tplId = $pdf->importPage(1);
    $pdf->AddPage();
    $pdf->useTemplate($tplId);

    $pdf->SetFont('Arial', '', 8);
    $pdf->SetTextColor(0, 0, 0);

    // FECHA DE SOLICITUD (esquina superior derecha)
    $fecha = date('d/m/Y');
    $pdf->SetXY(127, 14);
    $pdf->Cell(10, 4, date('d'), 0, 0, 'C');  // DD
    $pdf->SetXY(142, 14);
    $pdf->Cell(10, 4, date('m'), 0, 0, 'C');  // MM
    $pdf->SetXY(157, 14);
    $pdf->Cell(15, 4, date('Y'), 0, 0, 'C');  // AAAA

    // LUGAR
    $pdf->SetXY(128, 19);
    $pdf->Cell(70, 4, strtoupper(getPayloadValue($payload, 'cantonProp', 'SAN JOSE')), 0, 0);

    // HORA
    $pdf->SetXY(128, 24);
    $pdf->Cell(70, 4, date('h:i A'), 0, 0);

    // TIPO DE TRÁMITE - Emisión (checkbox)
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetXY(16, 37);
    $pdf->Cell(4, 4, 'X', 0, 0);  // Marcar Emisión

    $pdf->SetFont('Arial', '', 8);

    // ========== DATOS DEL TOMADOR ==========
    $nombreTomador = strtoupper(getPayloadValue($payload, 'nombreCompleto'));
    $pdf->SetXY(44, 48);
    $pdf->Cell(155, 4, $nombreTomador, 0, 0);

    // Tipo de identificación - Persona física Cédula
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(91, 53);
    $pdf->Cell(3, 3, 'X', 0, 0);  // Marcar Cédula
    $pdf->SetFont('Arial', '', 8);

    // Número de identificación del Tomador
    $cedulaTomador = getPayloadValue($payload, 'numeroId');
    $pdf->SetXY(44, 58);
    $pdf->Cell(80, 4, $cedulaTomador, 0, 0);

    // País, Provincia, Cantón, Distrito del Tomador
    $pdf->SetXY(20, 63);
    $pdf->Cell(40, 4, strtoupper(getPayloadValue($payload, 'pais', 'COSTA RICA')), 0, 0);

    $pdf->SetXY(75, 63);
    $pdf->Cell(40, 4, strtoupper(getPayloadValue($payload, 'provinciaProp', 'SAN JOSE')), 0, 0);

    $pdf->SetXY(118, 63);
    $pdf->Cell(40, 4, strtoupper(getPayloadValue($payload, 'cantonProp')), 0, 0);

    $pdf->SetXY(163, 63);
    $pdf->Cell(35, 4, strtoupper(getPayloadValue($payload, 'distritoProp')), 0, 0);

    // Dirección exacta de domicilio del Tomador
    $direccionTomador = strtoupper(getPayloadValue($payload, 'direccion'));
    $pdf->SetXY(15, 69);
    $pdf->MultiCell(183, 3.5, $direccionTomador, 0);

    // Teléfonos del Tomador
    $pdf->SetXY(35, 78);
    $pdf->Cell(50, 4, getPayloadValue($payload, 'telefonoOficina', ''), 0, 0);

    $pdf->SetXY(95, 78);
    $pdf->Cell(50, 4, getPayloadValue($payload, 'telefonoDomicilio', ''), 0, 0);

    $pdf->SetXY(155, 78);
    $pdf->Cell(43, 4, getPayloadValue($payload, 'telefonoCelular'), 0, 0);

    // Correo electrónico del Tomador
    $pdf->SetXY(38, 83);
    $pdf->Cell(80, 4, strtolower(getPayloadValue($payload, 'correo')), 0, 0);

    // Relación con el asegurado
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(182, 83);  // Checkbox "Otro"
    $pdf->Cell(3, 3, 'X', 0, 0);
    $pdf->SetFont('Arial', '', 7);
    $pdf->SetXY(189, 83);
    $pdf->Cell(10, 4, 'MISMO', 0, 0);

    $pdf->SetFont('Arial', '', 8);

    // ========== DATOS DEL ASEGURADO ==========
    $nombreAsegurado = strtoupper(getPayloadValue($payload, 'nombreCompleto'));
    $pdf->SetXY(44, 96);
    $pdf->Cell(155, 4, $nombreAsegurado, 0, 0);

    // Tipo de identificación Asegurado - Persona física Cédula
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(91, 101);
    $pdf->Cell(3, 3, 'X', 0, 0);
    $pdf->SetFont('Arial', '', 8);

    // Número de identificación del Asegurado
    $pdf->SetXY(44, 106);
    $pdf->Cell(80, 4, $cedulaTomador, 0, 0);

    // País, Provincia, Cantón, Distrito del Asegurado
    $pdf->SetXY(20, 111);
    $pdf->Cell(40, 4, strtoupper(getPayloadValue($payload, 'pais', 'COSTA RICA')), 0, 0);

    $pdf->SetXY(75, 111);
    $pdf->Cell(40, 4, strtoupper(getPayloadValue($payload, 'provinciaProp', 'SAN JOSE')), 0, 0);

    $pdf->SetXY(118, 111);
    $pdf->Cell(40, 4, strtoupper(getPayloadValue($payload, 'cantonProp')), 0, 0);

    $pdf->SetXY(163, 111);
    $pdf->Cell(35, 4, strtoupper(getPayloadValue($payload, 'distritoProp')), 0, 0);

    // Dirección exacta del Asegurado
    $pdf->SetXY(15, 117);
    $pdf->MultiCell(183, 3.5, $direccionTomador, 0);

    // Teléfonos del Asegurado
    $pdf->SetXY(35, 126);
    $pdf->Cell(50, 4, getPayloadValue($payload, 'telefonoOficina', ''), 0, 0);

    $pdf->SetXY(95, 126);
    $pdf->Cell(50, 4, getPayloadValue($payload, 'telefonoDomicilio', ''), 0, 0);

    $pdf->SetXY(155, 126);
    $pdf->Cell(43, 4, getPayloadValue($payload, 'telefonoCelular'), 0, 0);

    // Correo electrónico del Asegurado
    $pdf->SetXY(38, 131);
    $pdf->Cell(120, 4, strtolower(getPayloadValue($payload, 'correo')), 0, 0);

    // Señale la persona y el medio por el cual poder ser notificado
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(18, 136);  // Tomador
    $pdf->Cell(3, 3, 'X', 0, 0);
    $pdf->SetXY(42, 136);  // Asegurado
    $pdf->Cell(3, 3, 'X', 0, 0);
    $pdf->SetXY(72, 136);  // Correo electrónico
    $pdf->Cell(3, 3, 'X', 0, 0);
    $pdf->SetFont('Arial', '', 8);

    // ========== DATOS DE LA PROPIEDAD A ASEGURAR ==========
    // Georreferencia
    $pdf->SetXY(42, 152);
    $pdf->Cell(35, 4, getPayloadValue($payload, 'latitud', '9.936889'), 0, 0);

    $pdf->SetXY(82, 152);
    $pdf->Cell(35, 4, getPayloadValue($payload, 'longitud', '-84.046194'), 0, 0);

    // ¿Está localizado en una esquina? - No
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(181, 152);
    $pdf->Cell(3, 3, 'X', 0, 0);  // No
    $pdf->SetFont('Arial', '', 8);

    // País, Provincia, Cantón, Distrito de la propiedad
    $pdf->SetXY(22, 158);
    $pdf->Cell(35, 4, strtoupper(getPayloadValue($payload, 'pais', 'COSTA RICA')), 0, 0);

    $pdf->SetXY(70, 158);
    $pdf->Cell(35, 4, strtoupper(getPayloadValue($payload, 'provinciaProp', 'SAN JOSE')), 0, 0);

    $pdf->SetXY(110, 158);
    $pdf->Cell(35, 4, strtoupper(getPayloadValue($payload, 'cantonProp')), 0, 0);

    $pdf->SetXY(155, 158);
    $pdf->Cell(43, 4, strtoupper(getPayloadValue($payload, 'distritoProp')), 0, 0);

    // Urbanización, barrio, residencial
    $urbanizacion = strtoupper(getPayloadValue($payload, 'urbanizacion', getPayloadValue($payload, 'direccion')));
    $pdf->SetXY(15, 164);
    $pdf->Cell(120, 4, substr($urbanizacion, 0, 60), 0, 0);

    // Tipo de propiedad - Casa de habitación
    $tipoPropiedad = getPayloadValue($payload, 'tipoPropiedad', 'casa');
    if (stripos($tipoPropiedad, 'casa') !== false || stripos($tipoPropiedad, 'habitacion') !== false) {
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetXY(145, 164);
        $pdf->Cell(3, 3, 'X', 0, 0);
        $pdf->SetFont('Arial', '', 8);
    }

    // Otras señas
    $pdf->SetFont('Arial', '', 7);
    $pdf->SetXY(15, 169);
    $pdf->MultiCell(120, 3, strtoupper(getPayloadValue($payload, 'otrasSeñas', 'CASA PRINCIPAL')), 0);
    $pdf->SetFont('Arial', '', 8);

    // N° de folio real o finca
    $pdf->SetXY(155, 169);
    $pdf->Cell(43, 4, getPayloadValue($payload, 'folioReal', ''), 0, 0);

    // Rangos de año de construcción
    $anoConst = intval(getPayloadValue($payload, 'anoConst', '2000'));
    $pdf->SetFont('Arial', 'B', 10);
    if ($anoConst < 1974) {
        $pdf->SetXY(50, 175);
        $pdf->Cell(3, 3, 'X', 0, 0);
    } elseif ($anoConst >= 1974 && $anoConst <= 1985) {
        $pdf->SetXY(75, 175);
        $pdf->Cell(3, 3, 'X', 0, 0);
    } elseif ($anoConst >= 1986 && $anoConst <= 2001) {
        $pdf->SetXY(100, 175);
        $pdf->Cell(3, 3, 'X', 0, 0);
    } elseif ($anoConst >= 2002 && $anoConst <= 2009) {
        $pdf->SetXY(125, 175);
        $pdf->Cell(3, 3, 'X', 0, 0);
    } else {
        $pdf->SetXY(155, 175);
        $pdf->Cell(3, 3, 'X', 0, 0);
    }
    $pdf->SetFont('Arial', '', 8);

    // Área total de construcción
    $pdf->SetXY(60, 180);
    $pdf->Cell(25, 4, getPayloadValue($payload, 'areaConstruccion', '148'), 0, 0, 'C');

    // ¿El área de construcción por piso es igual? - Sí
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(155, 180);
    $pdf->Cell(3, 3, 'X', 0, 0);
    $pdf->SetFont('Arial', '', 8);

    // Cantidad de pisos
    $pdf->SetXY(35, 185);
    $pdf->Cell(10, 4, getPayloadValue($payload, 'cantidadPisos', '2'), 0, 0, 'C');

    // ¿En qué piso se ubica el bien?
    $pdf->SetXY(105, 185);
    $pdf->Cell(50, 4, getPayloadValue($payload, 'pisoUbicacion', '1 y 2'), 0, 0);

    // Sistema eléctrico - marcar checkboxes
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(85, 191);  // Entubado totalmente
    $pdf->Cell(3, 3, 'X', 0, 0);
    $pdf->SetXY(40, 196);  // Cuchilla principal
    $pdf->Cell(3, 3, 'X', 0, 0);
    $pdf->SetXY(85, 196);  // Breaker principal
    $pdf->Cell(3, 3, 'X', 0, 0);
    $pdf->SetXY(130, 191);  // Caja de Breaker
    $pdf->Cell(3, 3, 'X', 0, 0);
    $pdf->SetXY(130, 196);  // Tomacorriente polarizado
    $pdf->Cell(3, 3, 'X', 0, 0);
    $pdf->SetFont('Arial', '', 8);

    // ==================== PÁGINA 2 ====================
    $tplId = $pdf->importPage(2);
    $pdf->AddPage();
    $pdf->useTemplate($tplId);
    $pdf->SetFont('Arial', '', 8);

    // Estado de conservación
    $estadoConserv = strtolower(getPayloadValue($payload, 'estadoConserv', 'optimo'));
    $pdf->SetFont('Arial', 'B', 10);
    if (strpos($estadoConserv, 'optimo') !== false || strpos($estadoConserv, 'óptimo') !== false) {
        $pdf->SetXY(43, 19);
        $pdf->Cell(3, 3, 'X', 0, 0);
    } elseif (strpos($estadoConserv, 'muy bueno') !== false) {
        $pdf->SetXY(69, 19);
        $pdf->Cell(3, 3, 'X', 0, 0);
    } elseif (strpos($estadoConserv, 'bueno') !== false) {
        $pdf->SetXY(95, 19);
        $pdf->Cell(3, 3, 'X', 0, 0);
    }
    $pdf->SetFont('Arial', '', 8);

    // ¿Se han realizado modificaciones? - No
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(187, 24);
    $pdf->Cell(3, 3, 'X', 0, 0);
    $pdf->SetFont('Arial', '', 8);

    // INTERÉS ASEGURABLE - Propietario
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(18, 30);
    $pdf->Cell(3, 3, 'X', 0, 0);
    $pdf->SetFont('Arial', '', 8);

    // Actividad desarrollada en el inmueble
    $pdf->SetXY(70, 35);
    $pdf->Cell(128, 4, strtoupper(getPayloadValue($payload, 'actividad', 'ALQUILER')), 0, 0);

    // Detalle
    $pdf->SetXY(28, 44);
    $pdf->Cell(170, 4, strtoupper(getPayloadValue($payload, 'detalleActividad', 'N/A')), 0, 0);

    // Inmueble ocupado por - Inquilino
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(66, 49);
    $pdf->Cell(3, 3, 'X', 0, 0);
    $pdf->SetFont('Arial', '', 8);

    // Nombre del propietario del inmueble
    $propietarioNombre = strtoupper(getPayloadValue($payload, 'nombrePropietario', $nombreAsegurado));
    $pdf->SetFont('Arial', '', 7);
    $pdf->SetXY(15, 54);
    $pdf->MultiCell(183, 3, $propietarioNombre, 0);
    $pdf->SetFont('Arial', '', 8);

    // ¿Utiliza Gas LP? - No marcado por defecto
    // COLINDANTES
    $pdf->SetXY(28, 72);
    $pdf->Cell(48, 4, strtoupper(getPayloadValue($payload, 'colindanteNorte', 'Calle pública')), 0, 0);

    $pdf->SetXY(82, 72);
    $pdf->Cell(48, 4, strtoupper(getPayloadValue($payload, 'colindanteSur', '')), 0, 0);

    $pdf->SetXY(136, 72);
    $pdf->Cell(48, 4, strtoupper(getPayloadValue($payload, 'colindanteEste', '')), 0, 0);

    $pdf->SetXY(190, 72);
    $pdf->Cell(8, 4, '', 0, 0);  // Oeste - muy pequeño

    // Si la propiedad está cerca de - Ninguna de las anteriores
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(126, 78);
    $pdf->Cell(3, 3, 'X', 0, 0);
    $pdf->SetFont('Arial', '', 8);

    // DETALLES Y VARIABLES DEL TIPO DE CONSTRUCCIÓN
    $tipoConstruccion = strtolower(getPayloadValue($payload, 'tipoConstruccion', 'mixto'));
    $pdf->SetFont('Arial', 'B', 10);
    if (strpos($tipoConstruccion, 'e3') !== false || strpos($tipoConstruccion, 'concreto reforzado') !== false) {
        $pdf->SetXY(10, 96);
        $pdf->Cell(3, 3, 'X', 0, 0);
    } elseif (strpos($tipoConstruccion, 'e8') !== false || strpos($tipoConstruccion, 'mixto') !== false) {
        $pdf->SetXY(10, 124);
        $pdf->Cell(3, 3, 'X', 0, 0);
    }
    $pdf->SetFont('Arial', '', 8);

    // Prácticas sostenibles - LED
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(10, 200);
    $pdf->Cell(3, 3, 'X', 0, 0);
    $pdf->SetFont('Arial', '', 8);

    // ==================== PÁGINA 3 ====================
    $tplId = $pdf->importPage(3);
    $pdf->AddPage();
    $pdf->useTemplate($tplId);

    // ==================== PÁGINA 4 - DATOS DE LA PÓLIZA ====================
    $tplId = $pdf->importPage(4);
    $pdf->AddPage();
    $pdf->useTemplate($tplId);
    $pdf->SetFont('Arial', '', 8);

    // VIGENCIA DEL SEGURO
    $fechaDesde = date('d/m/Y');
    $fechaHasta = date('d/m/Y', strtotime('+1 year'));

    $pdf->SetXY(52, 70);
    $pdf->Cell(60, 4, $fechaDesde, 0, 0);

    $pdf->SetXY(130, 70);
    $pdf->Cell(60, 4, $fechaHasta, 0, 0);

    // MONEDA - Colones
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(27, 78);
    $pdf->Cell(3, 3, 'X', 0, 0);
    $pdf->SetFont('Arial', '', 8);

    // PLAN DE PAGO - Semestral
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(114, 82);
    $pdf->Cell(3, 3, 'X', 0, 0);
    $pdf->SetFont('Arial', '', 8);

    // INDIQUE SI TIENE PÓLIZAS - No
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(181, 87);
    $pdf->Cell(3, 3, 'X', 0, 0);
    $pdf->SetFont('Arial', '', 8);

    // FORMA DE ASEGURAMIENTO - Por cuenta de un tercero
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(27, 96);
    $pdf->Cell(3, 3, 'X', 0, 0);
    $pdf->SetFont('Arial', '', 8);

    // RUBROS ASEGURADOS
    // Residencia - Monto asegurado
    $montoResidencia = getPayloadValue($payload, 'montoResidencia', '50000000');
    $pdf->SetXY(118, 118);
    $pdf->Cell(38, 4, number_format($montoResidencia, 0, ',', ''), 0, 0, 'R');

    // Prima (ejemplo)
    $pdf->SetXY(158, 118);
    $pdf->Cell(38, 4, '103050', 0, 0, 'R');

    // Opción de Aseguramiento - Al 100%
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(97, 148);
    $pdf->Cell(3, 3, 'X', 0, 0);
    $pdf->SetFont('Arial', '', 8);

    // Prima, IVA, Prima Total
    $pdf->SetXY(158, 164);
    $pdf->Cell(38, 4, '103050', 0, 0, 'R');

    $pdf->SetXY(158, 176);
    $pdf->Cell(38, 4, '13396.5', 0, 0, 'R');

    $pdf->SetXY(158, 186);
    $pdf->Cell(38, 4, '116447', 0, 0, 'R');

    // Condición de aseguramiento - Valor de Reposición
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(42, 195);
    $pdf->Cell(3, 3, 'X', 0, 0);
    $pdf->SetFont('Arial', '', 8);

    // ==================== PÁGINA 5 - COBERTURAS ====================
    $tplId = $pdf->importPage(5);
    $pdf->AddPage();
    $pdf->useTemplate($tplId);
    $pdf->SetFont('Arial', '', 8);

    // COBERTURAS BÁSICAS
    // V: Daño Directo Bienes Inmuebles
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(10, 26);
    $pdf->Cell(3, 3, 'X', 0, 0);
    $pdf->SetFont('Arial', '', 8);

    // COBERTURAS ADICIONALES
    // D: Convulsiones de la Naturaleza
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(10, 50);
    $pdf->Cell(3, 3, 'X', 0, 0);
    $pdf->SetFont('Arial', '', 8);

    // T: Multiasistencia Hogar (GRATUITA)
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(10, 66);
    $pdf->Cell(3, 3, 'X', 0, 0);
    $pdf->SetFont('Arial', '', 8);

    // PROTECCIÓN CONTRA LA INFLACIÓN - No aplicar
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(108, 78);
    $pdf->Cell(3, 3, 'X', 0, 0);
    $pdf->SetFont('Arial', '', 8);

    // ==================== PÁGINA 6 - FIRMAS ====================
    $tplId = $pdf->importPage(6);
    $pdf->AddPage();
    $pdf->useTemplate($tplId);
    $pdf->SetFont('Arial', '', 8);

    // Nombre del asegurado en consentimiento informado
    $pdf->SetXY(15, 158);
    $pdf->Cell(180, 4, strtoupper($nombreAsegurado), 0, 0);

    // Cédula
    $pdf->SetXY(105, 165);
    $pdf->Cell(90, 4, $cedulaTomador, 0, 0);

    // Firma del intermediario
    $pdf->SetXY(15, 237);
    $pdf->Cell(180, 4, 'MARCO UGARTE ULATE', 0, 0);

    pdf_log("Todas las páginas completadas");

    // === GUARDAR PDF ===
    $filename = 'hogar_ins_' . $source . '_' . $source_ref . '_' . date('Ymd_His') . '.pdf';
    $output_path = $output_dir . $filename;
    $web_path = '/formulariosparaemision/hogar/' . $filename;

    $pdf->Output('F', $output_path);

    if (!file_exists($output_path)) {
        throw new Exception("Error al guardar el PDF");
    }

    pdf_log("PDF creado exitosamente: $filename");

    // Actualizar registro en base de datos
    if ($source === 'submission') {
        $stmt = $pdo->prepare("UPDATE submissions SET pdf_path = ? WHERE id = ?");
        $stmt->execute([$web_path, $source_ref]);
    } elseif ($source === 'cotizacion') {
        $stmt = $pdo->prepare("UPDATE cotizaciones SET pdf_path = ? WHERE id = ?");
        $stmt->execute([$web_path, $source_ref]);
    }

    // Redirigir
    pdf_log("=== FIN GENERACIÓN PDF V4 EXITOSA ===");
    header('Location: /admin/dashboard.php?pdf_generated=' . urlencode($filename));
    exit;

} catch (Exception $e) {
    pdf_log("ERROR: " . $e->getMessage());
    die("Error al generar PDF: " . htmlspecialchars($e->getMessage()));
}
