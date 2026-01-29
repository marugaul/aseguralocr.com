<?php
// admin/pdf_generator_v3_ins.php - Generador con template INS oficial
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

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    pdf_log("PHP ERROR [$errno]: $errstr en $errfile:$errline");
    return false;
});

set_exception_handler(function($e) {
    pdf_log("EXCEPTION: " . $e->getMessage() . " en " . $e->getFile() . ":" . $e->getLine());
    die("Error fatal: " . $e->getMessage());
});

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        pdf_log("FATAL ERROR: " . $error['message'] . " en " . $error['file'] . ":" . $error['line']);
    }
});

try {
    pdf_log("=== INICIO GENERACIÓN PDF V3 (CON TEMPLATE INS) ===");

    require_admin();
    pdf_log("Auth OK");

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        pdf_log("Error: Método no es POST");
        header('Location: /admin/dashboard.php');
        exit;
    }

    $cotizacion_id = isset($_POST['cotizacion_id']) ? intval($_POST['cotizacion_id']) : 0;
    $submission_id = isset($_POST['submission_id']) ? intval($_POST['submission_id']) : 0;

    pdf_log("cotizacion_id: $cotizacion_id, submission_id: $submission_id");

    $payload = null;
    $source = '';
    $source_ref = '';

    if ($submission_id) {
        pdf_log("Buscando submission #$submission_id");
        $stmt = $pdo->prepare("SELECT * FROM submissions WHERE id = ?");
        $stmt->execute([$submission_id]);
        $row = $stmt->fetch();
        if (!$row) {
            pdf_log("ERROR: Submission no encontrado");
            die("Submission no encontrado");
        }
        $payload = json_decode($row['payload'], true);
        $source = 'submission';
        $source_ref = $submission_id;
        pdf_log("Submission encontrado, payload size: " . strlen($row['payload']));

    } elseif ($cotizacion_id) {
        pdf_log("Buscando cotización #$cotizacion_id");
        $stmt = $pdo->prepare("SELECT * FROM cotizaciones WHERE id = ?");
        $stmt->execute([$cotizacion_id]);
        $row = $stmt->fetch();
        if (!$row) {
            pdf_log("ERROR: Cotización no encontrada");
            die("Cotización no encontrada");
        }
        $payload = json_decode($row['payload'], true);
        $source = 'cotizacion';
        $source_ref = $cotizacion_id;
        pdf_log("Cotización encontrada");

    } else {
        pdf_log("ERROR: No se proporcionó ID");
        die("No se proporcionó cotización ni submission");
    }

    if (!$payload) {
        pdf_log("ERROR: Payload es null o inválido");
        die("Error: Datos JSON inválidos");
    }

    // Output directory
    $output_dir = __DIR__ . '/../formulariosparaemision/hogar/';
    if (!is_dir($output_dir)) {
        @mkdir($output_dir, 0755, true);
        pdf_log("Directorio de salida creado: $output_dir");
    }

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
            pdf_log("Autoload encontrado en: $path");
            break;
        }
    }

    if (!$autoload) {
        pdf_log("ERROR: Autoload no encontrado");
        die("Falta vendor/autoload.php");
    }

    require_once $autoload;
    pdf_log("Autoload cargado OK");

    // Helper para extraer valores del payload
    function getPayloadValue($payload, $key, $default = '') {
        return isset($payload[$key]) ? $payload[$key] : $default;
    }

    // Template del INS
    $template_path = __DIR__ . '/../formulariosbase/hogar/formulario_ins_v2.pdf';

    if (!file_exists($template_path)) {
        pdf_log("ERROR: Template INS no encontrado en: $template_path");
        die("Template del INS no encontrado");
    }

    pdf_log("Template encontrado: $template_path");

    // Crear PDF con FPDI
    try {
        $pdf = new setasign\Fpdi\Fpdi();
        pdf_log("FPDI instanciado");

        // Importar template
        $pageCount = $pdf->setSourceFile($template_path);
        pdf_log("Template INS cargado - $pageCount páginas");

        // Importar primera página
        $tplId = $pdf->importPage(1);
        $pdf->AddPage();
        $pdf->useTemplate($tplId);
        pdf_log("Página 1 del template importada");

        // Configurar fuente para escribir sobre el template
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(0, 0, 0);

        // === ESCRIBIR DATOS SOBRE EL TEMPLATE ===
        // Nota: Las coordenadas son aproximadas y pueden necesitar ajuste

        // DATOS DEL ASEGURADO (aproximadamente a 1/3 desde arriba)
        $y_base = 72;  // Aprox 72 puntos = 2.5cm desde arriba

        // Nombre Completo
        $pdf->SetXY(45, $y_base);
        $pdf->Cell(150, 5, getPayloadValue($payload, 'nombreCompleto'), 0, 0);

        // Cédula
        $pdf->SetXY(35, $y_base + 8);
        $pdf->Cell(80, 5, getPayloadValue($payload, 'numeroId'), 0, 0);

        // Teléfono
        $pdf->SetXY(130, $y_base + 8);
        $pdf->Cell(70, 5, getPayloadValue($payload, 'telefonoCelular'), 0, 0);

        // Email
        $pdf->SetXY(55, $y_base + 16);
        $pdf->Cell(145, 5, getPayloadValue($payload, 'correo'), 0, 0);

        // UBICACIÓN DE LA PROPIEDAD
        $y_ubicacion = $y_base + 32;

        // Provincia, Cantón, Distrito
        $pdf->SetXY(35, $y_ubicacion);
        $pdf->Cell(45, 5, ucwords(getPayloadValue($payload, 'provinciaProp')), 0, 0);

        $pdf->SetXY(90, $y_ubicacion);
        $pdf->Cell(45, 5, getPayloadValue($payload, 'cantonProp'), 0, 0);

        $pdf->SetXY(145, $y_ubicacion);
        $pdf->Cell(45, 5, getPayloadValue($payload, 'distritoProp'), 0, 0);

        // Dirección exacta
        $pdf->SetXY(15, $y_ubicacion + 8);
        $pdf->MultiCell(180, 4, getPayloadValue($payload, 'direccion'), 0);

        // Tipo de propiedad
        $pdf->SetXY(55, $y_ubicacion + 20);
        $pdf->Cell(135, 5, ucwords(getPayloadValue($payload, 'tipoPropiedad')), 0, 0);

        // DETALLES DE COBERTURA
        $y_cobertura = $y_ubicacion + 40;

        // Monto Edificio
        $montoEdificio = (float)str_replace(',', '', getPayloadValue($payload, 'montoResidencia', '0'));
        $moneda = getPayloadValue($payload, 'moneda') === 'colones' ? 'CRC' : 'USD';

        $pdf->SetXY(70, $y_cobertura);
        $pdf->Cell(120, 5, $moneda . ' ' . number_format($montoEdificio, 2), 0, 0);

        // Monto Contenido
        $montoContenido = (float)str_replace(',', '', getPayloadValue($payload, 'montoContenido', '0'));

        $pdf->SetXY(70, $y_cobertura + 8);
        $pdf->Cell(120, 5, $moneda . ' ' . number_format($montoContenido, 2), 0, 0);

        // DETALLES ADICIONALES DE LA PROPIEDAD
        $y_detalles = $y_cobertura + 28;

        // Tipo Construcción
        $pdf->SetXY(60, $y_detalles);
        $pdf->Cell(50, 5, getPayloadValue($payload, 'tipoConstruccion'), 0, 0);

        // Área
        $pdf->SetXY(135, $y_detalles);
        $pdf->Cell(50, 5, getPayloadValue($payload, 'areaConstruccion'), 0, 0);

        // Año construcción
        $pdf->SetXY(60, $y_detalles + 8);
        $pdf->Cell(50, 5, getPayloadValue($payload, 'anoConst'), 0, 0);

        // Estado
        $pdf->SetXY(135, $y_detalles + 8);
        $pdf->Cell(50, 5, ucwords(getPayloadValue($payload, 'estadoConserv')), 0, 0);

        // Ocupado por
        $pdf->SetXY(45, $y_detalles + 16);
        $pdf->Cell(65, 5, ucwords(getPayloadValue($payload, 'ocupadoPor')), 0, 0);

        // Interés Aseg
        $pdf->SetXY(145, $y_detalles + 16);
        $pdf->Cell(50, 5, ucwords(getPayloadValue($payload, 'interesAseg')), 0, 0);

        // MEDIDAS DE SEGURIDAD
        $y_seguridad = $y_detalles + 32;

        // Alarma
        $pdf->SetXY(30, $y_seguridad);
        $pdf->Cell(70, 5, ucwords(str_replace('-', ' ', getPayloadValue($payload, 'alarma'))), 0, 0);

        // Cerraduras
        $pdf->SetXY(120, $y_seguridad);
        $pdf->Cell(70, 5, ucwords(str_replace('-', ' ', getPayloadValue($payload, 'cerraduras'))), 0, 0);

        // Vigilancia
        $pdf->SetXY(35, $y_seguridad + 8);
        $pdf->Cell(65, 5, ucwords(getPayloadValue($payload, 'vigilancia')), 0, 0);

        // Ventanas
        $pdf->SetXY(120, $y_seguridad + 8);
        $pdf->Cell(70, 5, ucwords(getPayloadValue($payload, 'ventanas')), 0, 0);

        // COBERTURAS ADICIONALES (checkboxes)
        $y_coberturas_adic = $y_seguridad + 24;

        $coberturaD = getPayloadValue($payload, 'coberturaD') === 'si' ? '[X]' : '[ ]';
        $coberturaV = getPayloadValue($payload, 'coberturaV') === 'si' ? '[X]' : '[ ]';
        $coberturaContenido = getPayloadValue($payload, 'coberturaContenido') === 'Y' ? '[X]' : '[ ]';

        $pdf->SetXY(15, $y_coberturas_adic);
        $pdf->Cell(5, 5, $coberturaD, 0, 0);
        $pdf->Cell(50, 5, 'Cobertura D', 0, 1);

        $pdf->SetXY(15, $y_coberturas_adic + 7);
        $pdf->Cell(5, 5, $coberturaV, 0, 0);
        $pdf->Cell(50, 5, 'Cobertura V', 0, 1);

        $pdf->SetXY(15, $y_coberturas_adic + 14);
        $pdf->Cell(5, 5, $coberturaContenido, 0, 0);
        $pdf->Cell(50, 5, 'Cobertura Contenido', 0, 1);

        // Importar el resto de páginas del template (sin modificar)
        for ($i = 2; $i <= $pageCount; $i++) {
            $tplId = $pdf->importPage($i);
            $pdf->AddPage();
            $pdf->useTemplate($tplId);
            pdf_log("Página $i del template importada");
        }

        pdf_log("Todas las páginas del template importadas y datos escritos");

    } catch (Exception $e) {
        pdf_log("ERROR creando PDF con FPDI: " . $e->getMessage());
        throw $e;
    }

    // === GUARDAR PDF ===
    $filename = 'hogar_' . $source . '_' . $source_ref . '_' . date('Ymd_His') . '.pdf';
    $output_path = $output_dir . $filename;
    $web_path = '/formulariosparaemision/hogar/' . $filename;

    pdf_log("Guardando PDF en: $output_path");
    try {
        $pdf->Output('F', $output_path);
        pdf_log("PDF guardado exitosamente");
    } catch (Exception $e) {
        pdf_log("ERROR en Output: " . $e->getMessage());
        throw $e;
    }

    if (!file_exists($output_path)) {
        pdf_log("ERROR: No se pudo crear el archivo PDF");
        throw new Exception("Error al guardar el PDF");
    }

    pdf_log("PDF creado exitosamente: $filename");

    // Actualizar registro en base de datos
    try {
        if ($source === 'submission') {
            $stmt = $pdo->prepare("UPDATE submissions SET pdf_path = ? WHERE id = ?");
            $stmt->execute([$web_path, $source_ref]);
            pdf_log("Submission actualizado con pdf_path: $web_path");
        } elseif ($source === 'cotizacion') {
            $stmt = $pdo->prepare("UPDATE cotizaciones SET pdf_path = ? WHERE id = ?");
            $stmt->execute([$web_path, $source_ref]);
            pdf_log("Cotización actualizada con pdf_path: $web_path");
        }
    } catch (PDOException $e) {
        pdf_log("ADVERTENCIA: No se pudo actualizar pdf_path en BD: " . $e->getMessage());
    }

    // Redirigir al dashboard
    pdf_log("=== FIN GENERACIÓN PDF V3 EXITOSA ===");
    header('Location: /admin/dashboard.php?pdf_generated=' . urlencode($filename));
    exit;

} catch (Exception $e) {
    pdf_log("EXCEPCIÓN: " . $e->getMessage());
    pdf_log("Archivo: " . $e->getFile());
    pdf_log("Línea: " . $e->getLine());
    pdf_log("Trace: " . $e->getTraceAsString());

    die("Error al generar PDF: " . htmlspecialchars($e->getMessage()));
}
