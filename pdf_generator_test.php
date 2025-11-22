<?php
/**
 * pdf_generator_test.php
 * Test de generación de PDF usando loader-fpdi-fpdf.php y rutas absolutas.
 *
 * Ajusta PLANTILLA_PATH y OUT_PATH si usas otras rutas.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ajusta si tu ruta real es otra
$PROJECT_ROOT = '/home/asegural/public_html';

// Ruta a loader (suponiendo que lo colocaste en project root)
$loaderPath = $PROJECT_ROOT . '/loader-fpdi-fpdf.php';
if (!file_exists($loaderPath)) {
    http_response_code(500);
    echo "Loader no encontrado en: {$loaderPath}";
    exit;
}
require_once $loaderPath;

// Ruta a plantilla y salida (ajusta si es necesario)
$PLANTILLA_PATH = $PROJECT_ROOT . '/mappings/template.pdf';
$OUT_PATH = $PROJECT_ROOT . '/storage/generated/test_no_composer.pdf';

try {
    // Instancia FPDI (throw si no existe)
    $pdf = load_fpdi_instance();

    // Verificar plantilla
    if (!file_exists($PLANTILLA_PATH)) {
        throw new RuntimeException("Plantilla no encontrada: {$PLANTILLA_PATH}");
    }

    // Importar plantilla (página 1)
    $pageCount = $pdf->setSourceFile($PLANTILLA_PATH);
    $tplId = $pdf->importPage(1);
    $size = $pdf->getTemplateSize($tplId);

    $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
    $pdf->useTemplate($tplId);

    // Escribir ejemplo (ajusta coordenadas a tu plantilla)
    $items = [
        ['x' => 25, 'y' => 45, 'text' => 'Nombre: Prueba Usuario', 'fontSize' => 10],
        ['x' => 25, 'y' => 55, 'text' => 'Referencia: COT-XXXX', 'fontSize' => 10],
        ['x' => 25, 'y' => 65, 'text' => 'Monto: 45,000,000 colones', 'fontSize' => 10],
    ];

    foreach ($items as $it) {
        $fontSize = isset($it['fontSize']) ? intval($it['fontSize']) : 10;
        // Algunas instalaciones prefieren 'Arial'
        $fontName = 'Arial';
        // Si no existe Arial, FPDF suele mapearlo. Cambia a 'Helvetica' si prefieres.
        $pdf->SetFont($fontName, '', $fontSize);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY(floatval($it['x']), floatval($it['y']));
        $pdf->Write(0, $it['text']);
    }

    // Asegurar carpeta de salida
    $outDir = dirname($OUT_PATH);
    if (!is_dir($outDir)) {
        if (!@mkdir($outDir, 0755, true)) {
            throw new RuntimeException("No se pudo crear carpeta de salida: {$outDir}");
        }
    }

    // Guardar
    $pdf->Output('F', $OUT_PATH);

    echo "PDF generado correctamente en: " . htmlspecialchars($OUT_PATH);

} catch (Throwable $e) {
    // Mensaje claro
    http_response_code(500);
    echo "<h2>Error generando PDF</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    // Para debugging en servidor (opcional)
    error_log("pdf_generator_test error: " . $e->getMessage());
}