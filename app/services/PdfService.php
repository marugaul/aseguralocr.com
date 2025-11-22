<?php
require_once __DIR__ . '/../../vendor/fpdf/fpdf.php';

class PdfService {
  public static function generar(array $data, string $referencia): string {
    $dir = __DIR__ . '/../../storage/pdfs';
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    $path = "$dir/$referencia.pdf";

    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',16);
    $pdf->Cell(0,10,'Solicitud Hogar - '.$referencia,0,1);
    $pdf->SetFont('Arial','',11);

    foreach ($data as $k => $v) {
      if (is_array($v)) $v = json_encode($v, JSON_UNESCAPED_UNICODE);
      $line = sprintf('%s: %s', $k, (string)$v);
      $pdf->MultiCell(0, 7, $line);
    }

    $pdf->Output('F', $path);
    return $path;
  }
}
