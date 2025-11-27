<?php
// admin/pdf_generator_v4_ins_precise.php - Generador con coordenadas precisas del INS
// VERSIÓN CORREGIDA - Coordenadas recalculadas para formulario INS Hogar
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
    pdf_log("=== INICIO GENERACIÓN PDF V4 (COORDENADAS CORREGIDAS) ===");

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
    function getVal($payload, $key, $default = '') {
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

    // Extraer datos del payload
    $nombreCompleto = strtoupper(getVal($payload, 'nombreCompleto', ''));
    $cedula = getVal($payload, 'numeroId', '');
    $correo = strtolower(getVal($payload, 'correo', ''));
    $telefono = getVal($payload, 'telefonoCelular', '');
    $telefonoOficina = getVal($payload, 'telefonoOficina', '');
    $telefonoDomicilio = getVal($payload, 'telefonoDomicilio', '');
    $direccion = strtoupper(getVal($payload, 'direccion', ''));
    $provincia = strtoupper(getVal($payload, 'provinciaProp', 'SAN JOSE'));
    $canton = strtoupper(getVal($payload, 'cantonProp', ''));
    $distrito = strtoupper(getVal($payload, 'distritoProp', ''));
    $pais = 'COSTA RICA';

    // ==================== PÁGINA 1 ====================
    $tplId = $pdf->importPage(1);
    $pdf->AddPage();
    $pdf->useTemplate($tplId);

    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(0, 0, 0);

    // ===== FECHA DE SOLICITUD (esquina superior derecha) =====
    // Basado en el formulario: DD | MM | AAAA
    $pdf->SetXY(168, 17);
    $pdf->Cell(8, 4, date('d'), 0, 0, 'C');
    $pdf->SetXY(183, 17);
    $pdf->Cell(8, 4, date('m'), 0, 0, 'C');
    $pdf->SetXY(195, 17);
    $pdf->Cell(12, 4, date('Y'), 0, 0, 'C');

    // LUGAR (debajo de fecha)
    $pdf->SetXY(168, 23);
    $pdf->Cell(40, 4, $canton, 0, 0);

    // HORA
    $pdf->SetXY(168, 29);
    $pdf->Cell(40, 4, date('H:i'), 0, 0);

    // ===== TIPO DE TRÁMITE =====
    // Checkbox Emisión (primer checkbox de la fila)
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(21, 39.5);
    $pdf->Cell(3, 3, 'X', 0, 0);
    $pdf->SetFont('Arial', '', 9);

    // ===== DATOS DEL TOMADOR =====
    // Nombre completo - en el campo después del label
    $pdf->SetXY(12, 58);
    $pdf->Cell(190, 4, $nombreCompleto, 0, 0);

    // Tipo de identificación - Checkbox Cédula (en la fila de Persona física)
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(98, 67);
    $pdf->Cell(3, 3, 'X', 0, 0);
    $pdf->SetFont('Arial', '', 9);

    // Número de identificación
    $pdf->SetXY(45, 73);
    $pdf->Cell(60, 4, $cedula, 0, 0);

    // País | Provincia | Cantón | Distrito
    $pdf->SetXY(20, 79);
    $pdf->Cell(30, 4, $pais, 0, 0);
    $pdf->SetXY(62, 79);
    $pdf->Cell(30, 4, $provincia, 0, 0);
    $pdf->SetXY(105, 79);
    $pdf->Cell(30, 4, $canton, 0, 0);
    $pdf->SetXY(150, 79);
    $pdf->Cell(30, 4, $distrito, 0, 0);

    // Dirección exacta de domicilio
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetXY(12, 85);
    $pdf->MultiCell(190, 3.5, $direccion, 0);
    $pdf->SetFont('Arial', '', 9);

    // Teléfonos: Oficina | Domicilio | Celular
    $pdf->SetXY(38, 94);
    $pdf->Cell(35, 4, $telefonoOficina, 0, 0);
    $pdf->SetXY(100, 94);
    $pdf->Cell(35, 4, $telefonoDomicilio, 0, 0);
    $pdf->SetXY(162, 94);
    $pdf->Cell(35, 4, $telefono, 0, 0);

    // Correo electrónico
    $pdf->SetXY(42, 100);
    $pdf->Cell(70, 4, $correo, 0, 0);

    // Relación con el asegurado - Checkbox "Otro" y escribir "MISMO"
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(188, 100);
    $pdf->Cell(3, 3, 'X', 0, 0);
    $pdf->SetFont('Arial', '', 7);
    $pdf->SetXY(193, 100);
    $pdf->Cell(10, 4, 'MISMO', 0, 0);
    $pdf->SetFont('Arial', '', 9);

    // ===== DATOS DEL ASEGURADO =====
    // Nombre completo
    $pdf->SetXY(12, 111);
    $pdf->Cell(190, 4, $nombreCompleto, 0, 0);

    // Tipo de identificación - Cédula
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(98, 120);
    $pdf->Cell(3, 3, 'X', 0, 0);
    $pdf->SetFont('Arial', '', 9);

    // Número de identificación
    $pdf->SetXY(45, 126);
    $pdf->Cell(60, 4, $cedula, 0, 0);

    // País | Provincia | Cantón | Distrito
    $pdf->SetXY(20, 132);
    $pdf->Cell(30, 4, $pais, 0, 0);
    $pdf->SetXY(62, 132);
    $pdf->Cell(30, 4, $provincia, 0, 0);
    $pdf->SetXY(105, 132);
    $pdf->Cell(30, 4, $canton, 0, 0);
    $pdf->SetXY(150, 132);
    $pdf->Cell(30, 4, $distrito, 0, 0);

    // Dirección exacta
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetXY(12, 138);
    $pdf->MultiCell(190, 3.5, $direccion, 0);
    $pdf->SetFont('Arial', '', 9);

    // Teléfonos
    $pdf->SetXY(38, 147);
    $pdf->Cell(35, 4, $telefonoOficina, 0, 0);
    $pdf->SetXY(100, 147);
    $pdf->Cell(35, 4, $telefonoDomicilio, 0, 0);
    $pdf->SetXY(162, 147);
    $pdf->Cell(35, 4, $telefono, 0, 0);

    // Correo electrónico
    $pdf->SetXY(42, 153);
    $pdf->Cell(100, 4, $correo, 0, 0);

    // Señale la persona y medio para notificación
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(22, 159);
    $pdf->Cell(3, 3, 'X', 0, 0);
    $pdf->SetXY(48, 159);
    $pdf->Cell(3, 3, 'X', 0, 0);
    $pdf->SetXY(84, 159);
    $pdf->Cell(3, 3, 'X', 0, 0);
    $pdf->SetFont('Arial', '', 9);

    // ===== DATOS DE LA PROPIEDAD A ASEGURAR (ZONA DE FUEGO) =====
    // Georreferencia: Latitud | Longitud
    $latitud = getVal($payload, 'latitud', '9.9345678');
    $longitud = getVal($payload, 'longitud', '-84.0856789');
    $pdf->SetXY(35, 172);
    $pdf->Cell(30, 4, $latitud, 0, 0);
    $pdf->SetXY(80, 172);
    $pdf->Cell(30, 4, $longitud, 0, 0);

    // ¿Está localizado en una esquina? - No
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(185, 172);
    $pdf->Cell(3, 3, 'X', 0, 0);
    $pdf->SetFont('Arial', '', 9);

    // País | Provincia | Cantón | Distrito de la propiedad
    $pdf->SetXY(20, 178);
    $pdf->Cell(28, 4, $pais, 0, 0);
    $pdf->SetXY(62, 178);
    $pdf->Cell(28, 4, $provincia, 0, 0);
    $pdf->SetXY(105, 178);
    $pdf->Cell(28, 4, $canton, 0, 0);
    $pdf->SetXY(150, 178);
    $pdf->Cell(28, 4, $distrito, 0, 0);

    // Urbanización/barrio/residencial
    $pdf->SetXY(12, 184);
    $pdf->Cell(90, 4, substr($direccion, 0, 50), 0, 0);

    // Tipo propiedad - Casa de habitación
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(150, 184);
    $pdf->Cell(3, 3, 'X', 0, 0);
    $pdf->SetFont('Arial', '', 9);

    // Otras señas
    $pdf->SetFont('Arial', '', 7);
    $pdf->SetXY(12, 190);
    $pdf->Cell(90, 4, strtoupper(getVal($payload, 'otrasSeñas', 'CASA PRINCIPAL')), 0, 0);
    $pdf->SetFont('Arial', '', 9);

    // Folio real
    $pdf->SetXY(155, 190);
    $pdf->Cell(40, 4, getVal($payload, 'folioReal', ''), 0, 0);

    // Año de construcción - marcar rango
    $anoConst = intval(getVal($payload, 'anoConst', date('Y')));
    $pdf->SetFont('Arial', 'B', 10);
    if ($anoConst < 1974) {
        $pdf->SetXY(53, 196);
    } elseif ($anoConst <= 1985) {
        $pdf->SetXY(78, 196);
    } elseif ($anoConst <= 2001) {
        $pdf->SetXY(103, 196);
    } elseif ($anoConst <= 2009) {
        $pdf->SetXY(128, 196);
    } else {
        $pdf->SetXY(160, 196);
    }
    $pdf->Cell(3, 3, 'X', 0, 0);
    $pdf->SetFont('Arial', '', 9);

    // Área de construcción
    $pdf->SetXY(62, 202);
    $pdf->Cell(20, 4, getVal($payload, 'areaConstruccion', '150'), 0, 0, 'C');

    // ¿Área por piso igual? - Sí
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(160, 202);
    $pdf->Cell(3, 3, 'X', 0, 0);
    $pdf->SetFont('Arial', '', 9);

    // Cantidad de pisos
    $pdf->SetXY(38, 208);
    $pdf->Cell(10, 4, getVal($payload, 'cantidadPisos', '1'), 0, 0, 'C');

    // ¿En qué piso se ubica?
    $pdf->SetXY(110, 208);
    $pdf->Cell(30, 4, getVal($payload, 'pisoUbicacion', '1'), 0, 0);

    // Sistema eléctrico checkboxes
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(90, 214);   // Entubado totalmente
    $pdf->Cell(3, 3, 'X', 0, 0);
    $pdf->SetXY(135, 214);  // Caja de breaker
    $pdf->Cell(3, 3, 'X', 0, 0);
    $pdf->SetXY(45, 220);   // Cuchilla principal
    $pdf->Cell(3, 3, 'X', 0, 0);
    $pdf->SetXY(90, 220);   // Breaker principal
    $pdf->Cell(3, 3, 'X', 0, 0);
    $pdf->SetXY(135, 220);  // Tomacorriente polarizado
    $pdf->Cell(3, 3, 'X', 0, 0);
    $pdf->SetFont('Arial', '', 9);

    pdf_log("Página 1 completada");

    // ==================== PÁGINA 2 ====================
    $tplId = $pdf->importPage(2);
    $pdf->AddPage();
    $pdf->useTemplate($tplId);
    $pdf->SetFont('Arial', '', 9);

    // Estado de conservación - Óptimo
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(48, 23);
    $pdf->Cell(3, 3, 'X', 0, 0);
    $pdf->SetFont('Arial', '', 9);

    // ¿Modificaciones? - No
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(192, 28);
    $pdf->Cell(3, 3, 'X', 0, 0);
    $pdf->SetFont('Arial', '', 9);

    // Interés asegurable - Propietario
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(22, 34);
    $pdf->Cell(3, 3, 'X', 0, 0);
    $pdf->SetFont('Arial', '', 9);

    // Actividad desarrollada
    $pdf->SetXY(72, 40);
    $pdf->Cell(120, 4, strtoupper(getVal($payload, 'actividad', 'RESIDENCIA')), 0, 0);

    // Detalle
    $pdf->SetXY(30, 50);
    $pdf->Cell(165, 4, strtoupper(getVal($payload, 'detalleActividad', 'N/A')), 0, 0);

    // Inmueble ocupado por - Propietario
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(22, 56);
    $pdf->Cell(3, 3, 'X', 0, 0);
    $pdf->SetFont('Arial', '', 9);

    // Nombre propietario
    $pdf->SetXY(12, 62);
    $pdf->Cell(190, 4, $nombreCompleto, 0, 0);

    // Gas LP - No
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(125, 68);
    $pdf->Cell(3, 3, 'X', 0, 0);
    $pdf->SetFont('Arial', '', 9);

    // Colindantes
    $pdf->SetXY(30, 80);
    $pdf->Cell(40, 4, strtoupper(getVal($payload, 'colindanteNorte', 'CALLE')), 0, 0);
    $pdf->SetXY(85, 80);
    $pdf->Cell(40, 4, strtoupper(getVal($payload, 'colindanteSur', 'VECINO')), 0, 0);
    $pdf->SetXY(140, 80);
    $pdf->Cell(40, 4, strtoupper(getVal($payload, 'colindanteEste', 'VECINO')), 0, 0);

    // Cerca de - Ninguna
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(130, 86);
    $pdf->Cell(3, 3, 'X', 0, 0);
    $pdf->SetFont('Arial', '', 9);

    // Tipo de construcción - E8 Mixto
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(12, 132);
    $pdf->Cell(3, 3, 'X', 0, 0);
    $pdf->SetFont('Arial', '', 9);

    // Prácticas sostenibles - LED
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(12, 208);
    $pdf->Cell(3, 3, 'X', 0, 0);
    $pdf->SetFont('Arial', '', 9);

    pdf_log("Página 2 completada");

    // ==================== PÁGINA 3 ====================
    $tplId = $pdf->importPage(3);
    $pdf->AddPage();
    $pdf->useTemplate($tplId);
    pdf_log("Página 3 completada");

    // ==================== PÁGINA 4 - DATOS DE LA PÓLIZA ====================
    $tplId = $pdf->importPage(4);
    $pdf->AddPage();
    $pdf->useTemplate($tplId);
    $pdf->SetFont('Arial', '', 9);

    // Vigencia
    $pdf->SetXY(55, 78);
    $pdf->Cell(50, 4, date('d/m/Y'), 0, 0);
    $pdf->SetXY(135, 78);
    $pdf->Cell(50, 4, date('d/m/Y', strtotime('+1 year')), 0, 0);

    // Moneda - Colones
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(30, 86);
    $pdf->Cell(3, 3, 'X', 0, 0);
    $pdf->SetFont('Arial', '', 9);

    // Plan de pago - Semestral
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(118, 90);
    $pdf->Cell(3, 3, 'X', 0, 0);
    $pdf->SetFont('Arial', '', 9);

    // Pólizas vigentes - No
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(185, 95);
    $pdf->Cell(3, 3, 'X', 0, 0);
    $pdf->SetFont('Arial', '', 9);

    // Forma de aseguramiento - Por cuenta propia
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(30, 104);
    $pdf->Cell(3, 3, 'X', 0, 0);
    $pdf->SetFont('Arial', '', 9);

    // Monto asegurado residencia
    $montoResidencia = getVal($payload, 'montoResidencia', '50000000');
    $pdf->SetXY(122, 126);
    $pdf->Cell(35, 4, number_format(floatval($montoResidencia), 0, ',', '.'), 0, 0, 'R');

    // Prima
    $pdf->SetXY(162, 126);
    $pdf->Cell(35, 4, '103,050', 0, 0, 'R');

    // Opción aseguramiento - 100%
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(100, 156);
    $pdf->Cell(3, 3, 'X', 0, 0);
    $pdf->SetFont('Arial', '', 9);

    // Prima total
    $pdf->SetXY(162, 172);
    $pdf->Cell(35, 4, '103,050', 0, 0, 'R');
    $pdf->SetXY(162, 184);
    $pdf->Cell(35, 4, '13,396', 0, 0, 'R');
    $pdf->SetXY(162, 194);
    $pdf->Cell(35, 4, '116,446', 0, 0, 'R');

    // Valor de reposición
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(45, 203);
    $pdf->Cell(3, 3, 'X', 0, 0);
    $pdf->SetFont('Arial', '', 9);

    pdf_log("Página 4 completada");

    // ==================== PÁGINA 5 - COBERTURAS ====================
    $tplId = $pdf->importPage(5);
    $pdf->AddPage();
    $pdf->useTemplate($tplId);
    $pdf->SetFont('Arial', 'B', 10);

    // V: Daño Directo Bienes Inmuebles
    $pdf->SetXY(12, 32);
    $pdf->Cell(3, 3, 'X', 0, 0);

    // D: Convulsiones de la Naturaleza
    $pdf->SetXY(12, 56);
    $pdf->Cell(3, 3, 'X', 0, 0);

    // T: Multiasistencia Hogar
    $pdf->SetXY(12, 72);
    $pdf->Cell(3, 3, 'X', 0, 0);

    // Protección inflación - No aplicar
    $pdf->SetXY(112, 84);
    $pdf->Cell(3, 3, 'X', 0, 0);

    $pdf->SetFont('Arial', '', 9);
    pdf_log("Página 5 completada");

    // ==================== PÁGINA 6 - FIRMAS ====================
    $tplId = $pdf->importPage(6);
    $pdf->AddPage();
    $pdf->useTemplate($tplId);
    $pdf->SetFont('Arial', '', 9);

    // Nombre asegurado en consentimiento
    $pdf->SetXY(15, 166);
    $pdf->Cell(180, 4, $nombreCompleto, 0, 0);

    // Cédula
    $pdf->SetXY(108, 173);
    $pdf->Cell(80, 4, $cedula, 0, 0);

    // Firma intermediario
    $pdf->SetXY(15, 245);
    $pdf->Cell(180, 4, 'MARCO UGARTE ULATE - AGENTE 110886', 0, 0);

    pdf_log("Página 6 completada");

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
