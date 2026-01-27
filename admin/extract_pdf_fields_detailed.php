<?php
//Script para extraer campos con más detalle
$pdfFile = __DIR__ . '/../formulariosbase/hogar/formulario_ins_v2.pdf';
$content = file_get_contents($pdfFile);

echo "Extrayendo campos del formulario INS...\n\n";

// Buscar patrones de campos de texto con sus nombres
preg_match_all('/\/T\s*\(([^)]+)\).*?\/Rect\s*\[([^\]]+)\]/s', $content, $matches, PREG_SET_ORDER);

$fields = [];
foreach ($matches as $match) {
    $fieldName = $match[1];
    $rect = $match[2];

    // Limpiar caracteres especiales
    $fieldName = iconv('ISO-8859-1', 'UTF-8//IGNORE', $fieldName);

    // Solo mostrar campos con nombres significativos (no solo números)
    if (!is_numeric($fieldName) && strlen($fieldName) > 2) {
        echo "Campo: $fieldName\n";
        echo "  Posición: [$rect]\n\n";
        $fields[] = $fieldName;
    }
}

echo "\n=== Resumen de campos importantes ===\n";
print_r(array_unique($fields));

echo "\nTotal de campos con nombres significativos: " . count(array_unique($fields)) . "\n";
