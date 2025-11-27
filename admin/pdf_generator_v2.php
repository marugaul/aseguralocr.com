<?php
// admin/pdf_generator_v2.php - Generador de PDF sin dependencia de template
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

ini_set('display_errors', '1'); // Mostrar errores para debug
ini_set('log_errors', '1');
ini_set('error_log', $logFile);
error_reporting(E_ALL);

// Capturar todos los errores
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    pdf_log("PHP ERROR [$errno]: $errstr en $errfile:$errline");
    return false;
});

set_exception_handler(function($e) {
    pdf_log("EXCEPTION NO CAPTURADA: " . $e->getMessage() . " en " . $e->getFile() . ":" . $e->getLine());
    die("Error fatal: " . $e->getMessage());
});

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        pdf_log("FATAL ERROR: " . $error['message'] . " en " . $error['file'] . ":" . $error['line']);
    }
});

try {
    pdf_log("=== INICIO GENERACIÓN PDF V2 (sin template) ===");

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
        '/home/asegural/public_html/composer/vendor/autoload.php',
        __DIR__ . '/../../composer/vendor/autoload.php',
        __DIR__ . '/../composer/vendor/autoload.php',
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

    // Usar FPDF directamente para generar desde cero
    try {
        $pdf = new FPDF();
        pdf_log("FPDF instanciado");
        $pdf->SetAutoPageBreak(true, 15);
        pdf_log("AutoPageBreak configurado");
        $pdf->AddPage();
        pdf_log("Página agregada");
    } catch (Exception $e) {
        pdf_log("ERROR creando PDF: " . $e->getMessage());
        throw $e;
    }

    pdf_log("Generando PDF desde cero");

    // === HEADER DEL FORMULARIO ===
    pdf_log("Configurando colores...");
    $pdf->SetFillColor(102, 126, 234);
    pdf_log("Dibujando rectángulo header...");
    $pdf->Rect(0, 0, 210, 40, 'F');

    pdf_log("Configurando texto header...");
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 18);
    $pdf->SetXY(10, 10);
    pdf_log("Escribiendo título INS...");
    $pdf->Cell(0, 10, 'INSTITUTO NACIONAL DE SEGUROS', 0, 1, 'C');

    $pdf->SetFont('Arial', 'B', 14);
    $pdf->SetXY(10, 22);
    $pdf->Cell(0, 8, 'Solicitud de Seguro Hogar Comprensivo - Poliza Individual', 0, 1, 'C');

    $pdf->SetFont('Arial', '', 10);
    $pdf->SetXY(10, 32);
    $pdf->Cell(0, 6, 'Formulario No. 1008157', 0, 1, 'C');

    $pdf->SetTextColor(0, 0, 0);

    // === INFORMACIÓN DEL AGENTE ===
    $y = 50;
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(10, $y);
    $pdf->Cell(0, 6, 'AGENTE AUTORIZADO: 110886', 0, 1, 'R');

    $pdf->SetFont('Arial', '', 9);
    $pdf->SetXY(10, $y + 6);
    $pdf->Cell(0, 5, 'Fecha: ' . date('d/m/Y'), 0, 1, 'R');

    // === DATOS DEL ASEGURADO ===
    $y += 20;
    $pdf->SetFillColor(240, 240, 240);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetXY(10, $y);
    $pdf->Cell(190, 8, 'DATOS DEL ASEGURADO', 1, 1, 'L', true);

    $y += 10;
    $pdf->SetFont('Arial', '', 10);

    // Helper para extraer valores del payload
    function getPayloadValue($payload, $key, $default = '') {
        $keys = explode('.', $key);
        $val = $payload;
        foreach ($keys as $k) {
            if (is_array($val) && array_key_exists($k, $val)) {
                $val = $val[$k];
            } else {
                return $default;
            }
        }
        return $val ?? $default;
    }

    // Nombre
    $pdf->SetXY(10, $y);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(40, 6, 'Nombre Completo:', 0, 0);
    $pdf->SetFont('Arial', '', 9);
    $pdf->Cell(150, 6, getPayloadValue($payload, 'cliente.nombre'), 'B', 1);

    $y += 8;

    // Cédula
    $pdf->SetXY(10, $y);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(40, 6, 'Cedula:', 0, 0);
    $pdf->SetFont('Arial', '', 9);
    $pdf->Cell(60, 6, getPayloadValue($payload, 'cliente.cedula'), 'B', 0);

    $pdf->SetX(120);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(30, 6, 'Telefono:', 0, 0);
    $pdf->SetFont('Arial', '', 9);
    $pdf->Cell(40, 6, getPayloadValue($payload, 'cliente.telefono'), 'B', 1);

    $y += 8;

    // Email
    $pdf->SetXY(10, $y);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(40, 6, 'Correo Electronico:', 0, 0);
    $pdf->SetFont('Arial', '', 9);
    $pdf->Cell(150, 6, getPayloadValue($payload, 'cliente.correo'), 'B', 1);

    // === UBICACIÓN DE LA PROPIEDAD ===
    $y += 15;
    $pdf->SetFillColor(240, 240, 240);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetXY(10, $y);
    $pdf->Cell(190, 8, 'UBICACION DE LA PROPIEDAD', 1, 1, 'L', true);

    $y += 10;

    // Provincia, Cantón, Distrito
    $pdf->SetXY(10, $y);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(20, 6, 'Provincia:', 0, 0);
    $pdf->SetFont('Arial', '', 9);
    $pdf->Cell(50, 6, getPayloadValue($payload, 'propiedad.provincia'), 'B', 0);

    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(20, 6, 'Canton:', 0, 0);
    $pdf->SetFont('Arial', '', 9);
    $pdf->Cell(45, 6, getPayloadValue($payload, 'propiedad.canton'), 'B', 0);

    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(20, 6, 'Distrito:', 0, 0);
    $pdf->SetFont('Arial', '', 9);
    $pdf->Cell(35, 6, getPayloadValue($payload, 'propiedad.distrito'), 'B', 1);

    $y += 8;

    // Dirección exacta
    $pdf->SetXY(10, $y);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(40, 6, 'Direccion Exacta:', 0, 1);
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetXY(10, $y + 6);
    $pdf->MultiCell(190, 5, getPayloadValue($payload, 'propiedad.direccion'), 'B');

    $y += 18;

    // Tipo de propiedad
    $pdf->SetXY(10, $y);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(40, 6, 'Tipo de Propiedad:', 0, 0);
    $pdf->SetFont('Arial', '', 9);
    $pdf->Cell(150, 6, getPayloadValue($payload, 'propiedad.tipo'), 'B', 1);

    // === DETALLES DE LA COBERTURA ===
    $y += 15;
    $pdf->SetFillColor(240, 240, 240);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetXY(10, $y);
    $pdf->Cell(190, 8, 'DETALLES DE LA COBERTURA', 1, 1, 'L', true);

    $y += 10;

    // Monto edificio
    $pdf->SetXY(10, $y);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(60, 6, 'Monto Asegurado Edificio:', 0, 0);
    $pdf->SetFont('Arial', '', 9);
    $montoEdificio = (float)getPayloadValue($payload, 'cobertura.monto_edificio', 0);
    $pdf->Cell(60, 6, 'CRC ' . number_format($montoEdificio, 2), 'B', 1);

    $y += 8;

    // Monto contenido
    $pdf->SetXY(10, $y);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(60, 6, 'Monto Asegurado Contenido:', 0, 0);
    $pdf->SetFont('Arial', '', 9);
    $montoContenido = (float)getPayloadValue($payload, 'cobertura.monto_contenido', 0);
    $pdf->Cell(60, 6, 'CRC ' . number_format($montoContenido, 2), 'B', 1);

    // === COBERTURAS ADICIONALES ===
    $y += 15;
    $pdf->SetFillColor(240, 240, 240);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetXY(10, $y);
    $pdf->Cell(190, 8, 'COBERTURAS ADICIONALES', 1, 1, 'L', true);

    $y += 10;

    // Checkboxes
    $terremoto = getPayloadValue($payload, 'opciones.terremoto') ? '[X]' : '[ ]';
    $inundacion = getPayloadValue($payload, 'opciones.inundacion') ? '[X]' : '[ ]';
    $robo = getPayloadValue($payload, 'opciones.robo') ? '[X]' : '[ ]';

    $pdf->SetFont('Arial', '', 10);
    $pdf->SetXY(10, $y);
    $pdf->Cell(10, 6, $terremoto, 0, 0);
    $pdf->Cell(60, 6, 'Terremoto', 0, 1);

    $y += 7;
    $pdf->SetXY(10, $y);
    $pdf->Cell(10, 6, $inundacion, 0, 0);
    $pdf->Cell(60, 6, 'Inundacion', 0, 1);

    $y += 7;
    $pdf->SetXY(10, $y);
    $pdf->Cell(10, 6, $robo, 0, 0);
    $pdf->Cell(60, 6, 'Robo', 0, 1);

    // === FOOTER ===
    $y = 260;
    $pdf->SetXY(10, $y);
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->MultiCell(190, 4, 'Este documento es una solicitud de seguro y debe ser revisado por el Instituto Nacional de Seguros. La emision de la poliza esta sujeta a la aprobacion de la aseguradora.', 0, 'C');

    $y += 15;
    $pdf->SetXY(10, $y);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Arial', '', 9);
    $pdf->Cell(80, 6, '_________________________', 0, 0, 'C');
    $pdf->SetX(120);
    $pdf->Cell(80, 6, '_________________________', 0, 1, 'C');

    $pdf->SetXY(10, $y + 6);
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(80, 5, 'Firma del Asegurado', 0, 0, 'C');
    $pdf->SetX(120);
    $pdf->Cell(80, 5, 'Agente INS 110886', 0, 1, 'C');

    // === GUARDAR PDF ===
    $filename = 'hogar_' . $source . '_' . $source_ref . '_' . date('Ymd_His') . '.pdf';
    $output_path = $output_dir . $filename;

    pdf_log("Guardando PDF en: $output_path");
    try {
        $pdf->Output('F', $output_path);
        pdf_log("PDF Output ejecutado");
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
    if ($source === 'submission') {
        $stmt = $pdo->prepare("UPDATE submissions SET pdf_path = ?, status = 'pdf_generated' WHERE id = ?");
        $stmt->execute([$output_path, $source_ref]);
        pdf_log("Submission actualizado con pdf_path");
    } elseif ($source === 'cotizacion') {
        $stmt = $pdo->prepare("UPDATE cotizaciones SET pdf_path = ? WHERE id = ?");
        $stmt->execute([$output_path, $source_ref]);
        pdf_log("Cotización actualizada con pdf_path");
    }

    // Redirigir al dashboard con éxito
    pdf_log("=== FIN GENERACIÓN PDF V2 EXITOSA ===");
    header('Location: /admin/dashboard.php?pdf_generated=' . urlencode($filename));
    exit;

} catch (Exception $e) {
    pdf_log("EXCEPCIÓN: " . $e->getMessage());
    pdf_log("Archivo: " . $e->getFile());
    pdf_log("Línea: " . $e->getLine());
    pdf_log("Trace: " . $e->getTraceAsString());

    die("Error al generar PDF: " . htmlspecialchars($e->getMessage()));
}
