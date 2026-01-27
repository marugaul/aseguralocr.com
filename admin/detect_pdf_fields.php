<?php
// Script para detectar campos de formulario en PDF
require_once __DIR__ . '/../composer/vendor/autoload.php';

use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;

$pdfFile = __DIR__ . '/../formulariosbase/hogar/formulario_ins_v2.pdf';

echo "Analizando PDF: $pdfFile\n\n";

try {
    // Leer contenido raw del PDF
    $content = file_get_contents($pdfFile);

    // Buscar indicadores de campos de formulario
    if (strpos($content, '/AcroForm') !== false) {
        echo "✓ El PDF TIENE campos de formulario (AcroForm)\n";

        // Intentar extraer nombres de campos
        if (preg_match_all('/\/T\s*\(([^)]+)\)/', $content, $matches)) {
            echo "\nCampos encontrados:\n";
            foreach ($matches[1] as $fieldName) {
                echo "  - $fieldName\n";
            }
        }
    } else {
        echo "✗ El PDF NO tiene campos de formulario interactivos\n";
        echo "  Necesitamos escribir sobre coordenadas específicas\n";
    }

    // Información adicional
    echo "\nInformación del PDF:\n";
    $pdf = new Fpdi();
    $pageCount = $pdf->setSourceFile($pdfFile);
    echo "  - Páginas: $pageCount\n";
    echo "  - Tamaño: " . filesize($pdfFile) . " bytes\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
