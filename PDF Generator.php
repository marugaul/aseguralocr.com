<?php
/**
 * PDF Generator.php
 * Ejemplo de uso que carga FPDI/FPDF con loader-fpdi-fpdf.php y genera un PDF de prueba
 *
 * Ajusta $BASE_DIR, $TEMPLATE_PATH y $OUT_PATH según tu proyecto.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Si loader está en otra ruta, ajusta require_once
require_once __DIR__ . '/loader-fpdi-fpdf.php';

// --- AJUSTA ESTAS RUTAS ---
// Base del proyecto (por ejemplo /home/tuusuario/public_html)
$BASE_DIR = realpath(__DIR__ . '/..');
if ($BASE_DIR === false) $BASE_DIR = __DIR__;

// Ruta a la plantilla PDF (ajusta según dónde tengas la plantilla)
$TEMPLATE_PATH = $BASE_DIR . '/mappings/template.pdf'; // AJUSTA si la plantilla está en otra ruta

// Ruta de salida del PDF generado
$OUT_PATH = $BASE_DIR . '/storage/generated/test_no_composer.pdf';

// FIN de ajustes

try {
    // Cargar instancia FPDI
    $pdf = load_fpdi_instance(['base_dir' => $BASE_DIR]);

    // Comprobar plantilla
    if (!file_exists($TEMPLATE_PATH)) {
        throw new RuntimeException("Plantilla no encontrada: {$TEMPLATE_PATH}");
    }

    // Importar plantilla (página 1)
    $pageCount = $pdf->setSourceFile($TEMPLATE_PATH);
    $tplId = $pdf->importPage(1);
    $size = $pdf->getTemplateSize($tplId);

    $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
    $pdf->useTemplate($tplId);

    // Ejemplo: datos que quieres escribir (ajusta coordenadas)
    $items = [
        ['x' => 30, 'y' => 50, 'text' => 'Nombre: Juan Pérez', 'fontSize' => 10],
        ['x' => 30, 'y' => 60, 'text' => 'Referencia: COT-20251108162333-344403', 'fontSize' => 10],
        ['x' => 30, 'y' => 70, 'text' => 'Monto: 45,000,000 colones', 'fontSize' => 10],
    ];

    foreach ($items as $it) {
        $fontSize = isset($it['fontSize']) ? intval($it['fontSize']) : 10;
        // Ajuste de fuente: FPDF built-in fonts
        // Si tu FPDI/FPDF no reconoce 'Helvetica', puedes usar 'Arial' o 'Courier' según estén disponibles.
        $fontName = 'Helvetica';
        // En legacy FPDF puede que 'Helvetica' no esté. Usa 'Arial' si da error.
        $pdf->SetFont($fontName, '', $fontSize);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY(floatval($it['x']), floatval($it['y']));
        $pdf->Write(0, $it['text']);
    }

    // Asegurar carpeta de salida
    $outDir = dirname($OUT_PATH);
    if (!is_dir($outDir)) {
        if (!@mkdir($outDir, 0755, true)) {
            throw new RuntimeException("No se pudo crear la carpeta de salida: {$outDir}");
        }
    }

    // Guardar PDF en archivo
    $pdf->Output('F', $OUT_PATH);

    echo "PDF generado correctamente: " . htmlspecialchars($OUT_PATH);

} catch (Throwable $e) {
    // Mensaje amigable y registro en error_log
    error_log("PDF Generator error: " . $e->getMessage());
    http_response_code(500);
    echo "<h2>Error generando PDF</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    // Opcional: mostrar rutas buscadas para debugging
    echo "<p>Comprueba que <code>vendor/fpdf/fpdf.php</code> existe y que los archivos FPDI estén en alguna de las rutas habituales.</p>";
}