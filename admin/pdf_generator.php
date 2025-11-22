<?php
// admin/pdf_generator.php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

use setasign\Fpdi\Fpdi;

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

ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', $logFile);
error_reporting(E_ALL);

try {
    pdf_log("=== INICIO GENERACIÓN PDF ===");
    
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
        pdf_log("Cotización encontrada, payload: " . json_encode($payload));
        
    } else {
        pdf_log("ERROR: No se proporcionó ID");
        die("No se proporcionó cotización ni submission");
    }

    // Verificar que payload no sea null
    if (!$payload) {
        pdf_log("ERROR: Payload es null o inválido");
        die("Error: Datos JSON inválidos");
    }

    // mapping
    $mapPath = __DIR__ . '/../mappings/hogar_map.json';
    pdf_log("Buscando mapping en: $mapPath");
    
    if (!file_exists($mapPath)) {
        pdf_log("ERROR: Mapping no encontrado en $mapPath");
        die('Mapping no encontrado: mappings/hogar_map.json');
    }
    
    $mapContent = file_get_contents($mapPath);
    pdf_log("Mapping leído, size: " . strlen($mapContent));
    
    $map = json_decode($mapContent, true);
    if (!$map) {
        pdf_log("ERROR: Mapping JSON inválido - " . json_last_error_msg());
        die("Error: Mapping JSON inválido");
    }
    
    $template = __DIR__ . '/../' . ltrim($map['meta']['template'], '/');
    $output_dir = __DIR__ . '/../' . ltrim($map['meta']['output_dir'], '/');
    $agent = $map['meta']['agent_number'] ?? '110886';
    
    pdf_log("Template: $template");
    pdf_log("Output dir: $output_dir");

    // validar template
    if (!file_exists($template)) {
        pdf_log("ERROR: Template no encontrado en $template");
        die("Template PDF no encontrado: $template");
    }
    pdf_log("Template existe OK");

    // composer autoload
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
        pdf_log("ERROR: Autoload no encontrado en ninguna ubicación");
        pdf_log("Rutas probadas: " . implode(", ", $autoload_paths));
        die("Falta vendor/autoload.php");
    }

    require_once $autoload;
    pdf_log("Autoload cargado OK");

    if (!class_exists('setasign\Fpdi\Fpdi')) {
        pdf_log("ERROR: Clase FPDI no existe");
        die("Error: Librería FPDI no instalada");
    }
    pdf_log("Clase FPDI existe OK");

    $pdf = new Fpdi();
    pdf_log("Objeto FPDI creado");
    
    $pageCount = $pdf->setSourceFile($template);
    pdf_log("Páginas en template: $pageCount");

    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
        pdf_log("Procesando página $pageNo");
        
        $tplId = $pdf->importPage($pageNo);
        $size = $pdf->getTemplateSize($tplId);
        $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
        $pdf->useTemplate($tplId);

        $fieldsProcessed = 0;
        $checkboxesProcessed = 0;

        // Procesar campos de texto
        if (isset($map['fields'])) {
            foreach ($map['fields'] as $f) {
                if (($f['page'] ?? 1) != $pageNo) continue;

                $keys = explode('.', $f['payload_key']);
                $val = $payload;
                foreach ($keys as $k) {
                    if (is_array($val) && array_key_exists($k, $val)) {
                        $val = $val[$k];
                    } else {
                        $val = null;
                        break;
                    }
                }
                
                if ($val === null || $val === '') continue;

                $text = (string)$val;
                $pdf->SetFont($f['font'] ?? 'Helvetica', '', $f['size'] ?? $map['meta']['font_size'] ?? 10);
                $pdf->SetTextColor(0, 0, 0);
                
                if (!empty($f['width'])) {
                    $pdf->SetXY($f['x'], $f['y']);
                    $pdf->MultiCell($f['width'], $f['line_height'] ?? 5, $text);
                } else {
                    $pdf->SetXY($f['x'], $f['y']);
                    $pdf->Write(0, $text);
                }
                
                $fieldsProcessed++;
            }
        }

        // Procesar checkboxes
        if (isset($map['checkboxes'])) {
            foreach ($map['checkboxes'] as $f) {
                if (($f['page'] ?? 1) != $pageNo) continue;

                $keys = explode('.', $f['payload_key']);
                $val = $payload;
                foreach ($keys as $k) {
                    if (is_array($val) && array_key_exists($k, $val)) {
                        $val = $val[$k];
                    } else {
                        $val = null;
                        break;
                    }
                }
                
                if ($val === null || $val === '') continue;

                $checked = ($val == $f['value_when_checked']);
                
                if ($checked) {
                    $pdf->SetFont('Helvetica', 'B', $f['size'] ?? 12);
                    $pdf->SetTextColor(0, 0, 0);
                    $pdf->SetXY($f['x'], $f['y']);
                    $pdf->Write(0, 'X');
                    $checkboxesProcessed++;
                }
            }
        }

        pdf_log("Página $pageNo: $fieldsProcessed campos, $checkboxesProcessed checkboxes");
    }

    // crear dir salida
    if (!is_dir($output_dir)) {
        pdf_log("Creando directorio de salida: $output_dir");
        mkdir($output_dir, 0755, true);
    }

    $baseName = pathinfo($template, PATHINFO_FILENAME);
    $timestamp = date('Ymd_His');
    $outName = $baseName . "_{$agent}_{$timestamp}.pdf";
    $outPath = rtrim($output_dir, '/') . '/' . $outName;
    
    pdf_log("Guardando PDF en: $outPath");

    $pdf->Output('F', $outPath);
    pdf_log("PDF guardado exitosamente");

    // actualizar submissions.pdf_path
    if ($submission_id) {
        $u = $pdo->prepare("UPDATE submissions SET pdf_path = ? WHERE id = ?");
        $u->execute([$outPath, $submission_id]);
        pdf_log("submissions.pdf_path actualizado");
    }

    // guardar auditoría
    try {
        $ins = $pdo->prepare("INSERT INTO pdf_generations (submission_id, cotizacion_id, admin_id, file_path, created_at) VALUES (?, ?, ?, ?, NOW())");
        $ins->execute([
            $submission_id ?: null, 
            $cotizacion_id ?: null, 
            $_SESSION['admin_id'] ?? null, 
            $outPath
        ]);
        pdf_log("Auditoría guardada");
    } catch (\Throwable $e) {
        pdf_log("Advertencia: No se pudo guardar auditoría - " . $e->getMessage());
    }

    pdf_log("=== FIN GENERACIÓN PDF EXITOSA ===");

    if ($submission_id) {
        header('Location: /admin/view_submission.php?id=' . $submission_id . '&saved=1');
    } else {
        header('Location: /admin/view_cotizacion.php?id=' . $cotizacion_id . '&saved=1');
    }
    exit;

} catch (\Throwable $e) {
    $errorMsg = "EXCEPCIÓN: " . $e->getMessage() . "\n" .
                "Archivo: " . $e->getFile() . "\n" .
                "Línea: " . $e->getLine() . "\n" .
                "Trace: " . $e->getTraceAsString();
    pdf_log($errorMsg);
    
    http_response_code(500);
    die("Error al generar PDF. Revisa /logs/pdf_generator_errors.log");
}