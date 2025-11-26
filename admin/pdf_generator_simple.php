<?php
// admin/pdf_generator_simple.php
// Generador de PDF alternativo que crea el documento desde cero
// No requiere importar templates, elimina problemas de compatibilidad
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
    $entry = "[$time] [SIMPLE] $msg\n";
    @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
}

ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', $logFile);
error_reporting(E_ALL);

// Clase personalizada para el PDF
class SolicitudPDF extends \FPDF {
    protected $tipo_seguro = 'HOGAR';
    protected $agent_number = '110886';

    public function setTipoSeguro($tipo) {
        $this->tipo_seguro = strtoupper($tipo);
    }

    public function setAgentNumber($num) {
        $this->agent_number = $num;
    }

    // Encabezado
    function Header() {
        // Logo o nombre de la agencia
        $this->SetFont('Helvetica', 'B', 16);
        $this->Cell(0, 10, 'ASEGURA LO - Agencia de Seguros', 0, 1, 'C');

        $this->SetFont('Helvetica', '', 10);
        $this->Cell(0, 5, 'Agente Autorizado INS - No. ' . $this->agent_number, 0, 1, 'C');

        // Título del documento
        $this->Ln(5);
        $this->SetFont('Helvetica', 'B', 14);
        $this->SetFillColor(0, 51, 102);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0, 10, 'SOLICITUD DE SEGURO - ' . $this->tipo_seguro, 0, 1, 'C', true);
        $this->SetTextColor(0, 0, 0);
        $this->Ln(5);
    }

    // Pie de página
    function Footer() {
        $this->SetY(-25);
        $this->SetFont('Helvetica', 'I', 8);
        $this->SetTextColor(128, 128, 128);
        $this->Cell(0, 4, 'Documento generado por ASEGURA LO - www.aseguralocr.com', 0, 1, 'C');
        $this->Cell(0, 4, 'Este documento es para uso interno y debe ser presentado al INS para emision de poliza', 0, 1, 'C');
        $this->Cell(0, 4, 'Pagina ' . $this->PageNo() . ' - Generado: ' . date('d/m/Y H:i:s'), 0, 0, 'C');
    }

    // Sección con título
    function SeccionTitulo($titulo) {
        $this->Ln(3);
        $this->SetFont('Helvetica', 'B', 11);
        $this->SetFillColor(240, 240, 240);
        $this->Cell(0, 8, '  ' . $titulo, 0, 1, 'L', true);
        $this->Ln(2);
    }

    // Campo con etiqueta y valor
    function Campo($etiqueta, $valor, $ancho_etiqueta = 50) {
        $this->SetFont('Helvetica', 'B', 9);
        $this->Cell($ancho_etiqueta, 6, $etiqueta . ':', 0, 0, 'L');
        $this->SetFont('Helvetica', '', 9);

        // Si el valor es muy largo, usar MultiCell
        if (strlen($valor) > 60) {
            $x = $this->GetX();
            $y = $this->GetY();
            $this->MultiCell(0, 6, $valor, 0, 'L');
        } else {
            $this->Cell(0, 6, $valor, 0, 1, 'L');
        }
    }

    // Campo con checkbox
    function Checkbox($etiqueta, $marcado = false) {
        $this->SetFont('Helvetica', '', 9);
        $simbolo = $marcado ? '[X]' : '[ ]';
        $this->Cell(10, 6, $simbolo, 0, 0, 'L');
        $this->Cell(0, 6, $etiqueta, 0, 1, 'L');
    }

    // Dos columnas
    function DosColumnas($etiqueta1, $valor1, $etiqueta2, $valor2) {
        $this->SetFont('Helvetica', 'B', 9);
        $this->Cell(35, 6, $etiqueta1 . ':', 0, 0, 'L');
        $this->SetFont('Helvetica', '', 9);
        $this->Cell(55, 6, $valor1, 0, 0, 'L');

        $this->SetFont('Helvetica', 'B', 9);
        $this->Cell(35, 6, $etiqueta2 . ':', 0, 0, 'L');
        $this->SetFont('Helvetica', '', 9);
        $this->Cell(0, 6, $valor2, 0, 1, 'L');
    }

    // Línea separadora
    function LineaSeparadora() {
        $this->Ln(2);
        $this->SetDrawColor(200, 200, 200);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(2);
    }

    // Tabla simple
    function TablaSimple($headers, $data) {
        $this->SetFont('Helvetica', 'B', 9);
        $this->SetFillColor(230, 230, 230);

        $anchos = array_fill(0, count($headers), 190 / count($headers));

        foreach ($headers as $i => $header) {
            $this->Cell($anchos[$i], 7, $header, 1, 0, 'C', true);
        }
        $this->Ln();

        $this->SetFont('Helvetica', '', 9);
        foreach ($data as $row) {
            foreach ($row as $i => $cell) {
                $this->Cell($anchos[$i], 6, $cell, 1, 0, 'L');
            }
            $this->Ln();
        }
    }
}

try {
    pdf_log("=== INICIO GENERACIÓN PDF SIMPLE ===");

    require_admin();
    pdf_log("Auth OK");

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        pdf_log("Error: Método no es POST");
        header('Location: /admin/dashboard.php');
        exit;
    }

    $cotizacion_id = isset($_POST['cotizacion_id']) ? intval($_POST['cotizacion_id']) : 0;
    $submission_id = isset($_POST['submission_id']) ? intval($_POST['submission_id']) : 0;
    $tipo_seguro = isset($_POST['tipo_seguro']) ? $_POST['tipo_seguro'] : 'hogar';

    pdf_log("cotizacion_id: $cotizacion_id, submission_id: $submission_id, tipo: $tipo_seguro");

    $payload = null;
    $source = '';
    $source_ref = '';
    $email = '';
    $referencia = '';

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
        $email = $row['email'] ?? '';
        $referencia = $row['reference_number'] ?? '';
        $tipo_seguro = $row['form_type'] ?? $tipo_seguro;
        pdf_log("Submission encontrado");

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
        $tipo_seguro = $row['tipo_seguro'] ?? $tipo_seguro;
        pdf_log("Cotización encontrada");

    } else {
        pdf_log("ERROR: No se proporcionó ID");
        die("No se proporcionó cotización ni submission");
    }

    if (!$payload) {
        pdf_log("ERROR: Payload es null o inválido");
        die("Error: Datos JSON inválidos");
    }

    // Cargar autoload
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
            break;
        }
    }

    if (!$autoload) {
        pdf_log("ERROR: Autoload no encontrado");
        die("Falta vendor/autoload.php");
    }

    require_once $autoload;
    pdf_log("Autoload cargado");

    // Crear PDF
    $pdf = new SolicitudPDF('P', 'mm', 'Letter');
    $pdf->setTipoSeguro($tipo_seguro);
    $pdf->setAgentNumber('110886');
    $pdf->SetMargins(10, 10, 10);
    $pdf->SetAutoPageBreak(true, 30);
    $pdf->AddPage();

    // Número de referencia si existe
    if ($referencia) {
        $pdf->SetFont('Helvetica', 'B', 10);
        $pdf->Cell(0, 6, 'Referencia: ' . $referencia, 0, 1, 'R');
    }

    // Generar contenido según tipo de seguro
    switch (strtolower($tipo_seguro)) {
        case 'hogar':
            generarPDFHogar($pdf, $payload);
            break;
        case 'autos':
        case 'auto':
            generarPDFAutos($pdf, $payload);
            break;
        case 'rt':
        case 'riesgos_trabajo':
            generarPDFRT($pdf, $payload);
            break;
        default:
            generarPDFGenerico($pdf, $payload, $tipo_seguro);
    }

    // Sección de firma
    $pdf->Ln(15);
    $pdf->SeccionTitulo('FIRMAS');
    $pdf->Ln(10);

    $pdf->SetFont('Helvetica', '', 9);
    $pdf->Cell(90, 6, '________________________________', 0, 0, 'C');
    $pdf->Cell(90, 6, '________________________________', 0, 1, 'C');
    $pdf->Cell(90, 6, 'Firma del Solicitante', 0, 0, 'C');
    $pdf->Cell(90, 6, 'Firma del Agente', 0, 1, 'C');
    $pdf->Ln(5);
    $pdf->Cell(90, 6, 'Cedula: ________________', 0, 0, 'C');
    $pdf->Cell(90, 6, 'Agente No. 110886', 0, 1, 'C');

    // Guardar PDF
    $output_dir = __DIR__ . '/../formulariosparaemision/' . strtolower($tipo_seguro) . '/';
    if (!is_dir($output_dir)) {
        mkdir($output_dir, 0755, true);
    }

    $timestamp = date('Ymd_His');
    $outName = "solicitud_{$tipo_seguro}_110886_{$timestamp}.pdf";
    $outPath = $output_dir . $outName;

    pdf_log("Guardando PDF en: $outPath");
    $pdf->Output('F', $outPath);
    pdf_log("PDF guardado exitosamente");

    // Actualizar submissions.pdf_path
    if ($submission_id) {
        $u = $pdo->prepare("UPDATE submissions SET pdf_path = ? WHERE id = ?");
        $u->execute([$outPath, $submission_id]);
        pdf_log("submissions.pdf_path actualizado");
    }

    // Guardar auditoría
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

    pdf_log("=== FIN GENERACIÓN PDF SIMPLE EXITOSA ===");

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

// === FUNCIONES DE GENERACIÓN POR TIPO ===

function obtenerValor($payload, $key, $default = '') {
    $keys = explode('.', $key);
    $val = $payload;
    foreach ($keys as $k) {
        if (is_array($val) && array_key_exists($k, $val)) {
            $val = $val[$k];
        } else {
            return $default;
        }
    }
    return $val !== null ? $val : $default;
}

function generarPDFHogar($pdf, $payload) {
    // Datos del cliente
    $pdf->SeccionTitulo('DATOS DEL ASEGURADO');
    $pdf->Campo('Nombre completo', obtenerValor($payload, 'cliente.nombre', obtenerValor($payload, 'nombre', '')));
    $pdf->DosColumnas(
        'Cedula', obtenerValor($payload, 'cliente.cedula', obtenerValor($payload, 'cedula', '')),
        'Telefono', obtenerValor($payload, 'cliente.telefono', obtenerValor($payload, 'telefono', ''))
    );
    $pdf->Campo('Correo electronico', obtenerValor($payload, 'cliente.correo', obtenerValor($payload, 'correo', obtenerValor($payload, 'email', ''))));

    // Datos de la propiedad
    $pdf->SeccionTitulo('DATOS DE LA PROPIEDAD');
    $pdf->Campo('Direccion', obtenerValor($payload, 'propiedad.direccion', obtenerValor($payload, 'direccion', '')));
    $pdf->DosColumnas(
        'Tipo de propiedad', obtenerValor($payload, 'propiedad.tipo', obtenerValor($payload, 'tipo_propiedad', '')),
        'Uso', obtenerValor($payload, 'propiedad.uso', obtenerValor($payload, 'uso', 'Residencial'))
    );
    $pdf->DosColumnas(
        'Provincia', obtenerValor($payload, 'propiedad.provincia', obtenerValor($payload, 'provincia', '')),
        'Canton', obtenerValor($payload, 'propiedad.canton', obtenerValor($payload, 'canton', ''))
    );
    $pdf->Campo('Distrito', obtenerValor($payload, 'propiedad.distrito', obtenerValor($payload, 'distrito', '')));
    $pdf->DosColumnas(
        'Ano construccion', obtenerValor($payload, 'propiedad.ano_construccion', obtenerValor($payload, 'ano_construccion', '')),
        'Area (m2)', obtenerValor($payload, 'propiedad.area', obtenerValor($payload, 'area', ''))
    );

    // Coberturas
    $pdf->SeccionTitulo('COBERTURAS SOLICITADAS');
    $monto_edificio = obtenerValor($payload, 'cobertura.monto_edificio', obtenerValor($payload, 'monto_edificio', ''));
    $monto_contenido = obtenerValor($payload, 'cobertura.monto_contenido', obtenerValor($payload, 'monto_contenido', ''));

    if ($monto_edificio) {
        $pdf->Campo('Monto asegurado - Edificio', formatearMoneda($monto_edificio));
    }
    if ($monto_contenido) {
        $pdf->Campo('Monto asegurado - Contenido', formatearMoneda($monto_contenido));
    }

    // Opciones adicionales
    $pdf->Ln(3);
    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->Cell(0, 6, 'Coberturas adicionales:', 0, 1);

    $pdf->Checkbox('Terremoto', obtenerValor($payload, 'opciones.terremoto', false));
    $pdf->Checkbox('Inundacion', obtenerValor($payload, 'opciones.inundacion', false));
    $pdf->Checkbox('Robo', obtenerValor($payload, 'opciones.robo', false));
    $pdf->Checkbox('Responsabilidad civil', obtenerValor($payload, 'opciones.responsabilidad_civil', false));

    // Información adicional
    $observaciones = obtenerValor($payload, 'observaciones', '');
    if ($observaciones) {
        $pdf->SeccionTitulo('OBSERVACIONES');
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->MultiCell(0, 5, $observaciones);
    }
}

function generarPDFAutos($pdf, $payload) {
    // Datos del cliente
    $pdf->SeccionTitulo('DATOS DEL ASEGURADO');
    $pdf->Campo('Nombre completo', obtenerValor($payload, 'cliente.nombre', obtenerValor($payload, 'nombre', '')));
    $pdf->DosColumnas(
        'Cedula', obtenerValor($payload, 'cliente.cedula', obtenerValor($payload, 'cedula', '')),
        'Telefono', obtenerValor($payload, 'cliente.telefono', obtenerValor($payload, 'telefono', ''))
    );
    $pdf->Campo('Correo electronico', obtenerValor($payload, 'cliente.correo', obtenerValor($payload, 'correo', obtenerValor($payload, 'email', ''))));
    $pdf->Campo('Direccion', obtenerValor($payload, 'cliente.direccion', obtenerValor($payload, 'direccion', '')));

    // Datos del vehículo
    $pdf->SeccionTitulo('DATOS DEL VEHICULO');
    $pdf->DosColumnas(
        'Marca', obtenerValor($payload, 'vehiculo.marca', obtenerValor($payload, 'marca', '')),
        'Modelo', obtenerValor($payload, 'vehiculo.modelo', obtenerValor($payload, 'modelo', ''))
    );
    $pdf->DosColumnas(
        'Ano', obtenerValor($payload, 'vehiculo.ano', obtenerValor($payload, 'ano', '')),
        'Placa', obtenerValor($payload, 'vehiculo.placa', obtenerValor($payload, 'placa', ''))
    );
    $pdf->DosColumnas(
        'VIN/Chasis', obtenerValor($payload, 'vehiculo.vin', obtenerValor($payload, 'vin', obtenerValor($payload, 'chasis', ''))),
        'Motor', obtenerValor($payload, 'vehiculo.motor', obtenerValor($payload, 'motor', ''))
    );
    $pdf->DosColumnas(
        'Color', obtenerValor($payload, 'vehiculo.color', obtenerValor($payload, 'color', '')),
        'Tipo', obtenerValor($payload, 'vehiculo.tipo', obtenerValor($payload, 'tipo_vehiculo', ''))
    );
    $pdf->Campo('Valor del vehiculo', formatearMoneda(obtenerValor($payload, 'vehiculo.valor', obtenerValor($payload, 'valor', ''))));

    // Coberturas
    $pdf->SeccionTitulo('COBERTURAS SOLICITADAS');
    $pdf->Checkbox('Responsabilidad civil obligatoria', true);
    $pdf->Checkbox('Cobertura total (todo riesgo)', obtenerValor($payload, 'cobertura.total', false));
    $pdf->Checkbox('Robo total', obtenerValor($payload, 'cobertura.robo', false));
    $pdf->Checkbox('Danos a terceros', obtenerValor($payload, 'cobertura.terceros', true));
    $pdf->Checkbox('Asistencia en carretera', obtenerValor($payload, 'cobertura.asistencia', false));
    $pdf->Checkbox('Auto sustituto', obtenerValor($payload, 'cobertura.auto_sustituto', false));

    // Uso del vehículo
    $pdf->SeccionTitulo('USO DEL VEHICULO');
    $uso = obtenerValor($payload, 'uso', 'particular');
    $pdf->Checkbox('Particular', strtolower($uso) == 'particular');
    $pdf->Checkbox('Comercial', strtolower($uso) == 'comercial');
    $pdf->Checkbox('Taxi/Uber', strtolower($uso) == 'taxi' || strtolower($uso) == 'uber');
}

function generarPDFRT($pdf, $payload) {
    // Datos de la empresa
    $pdf->SeccionTitulo('DATOS DE LA EMPRESA');
    $pdf->Campo('Razon social', obtenerValor($payload, 'empresa.razon_social', obtenerValor($payload, 'razon_social', '')));
    $pdf->Campo('Nombre comercial', obtenerValor($payload, 'empresa.nombre_comercial', obtenerValor($payload, 'nombre_comercial', '')));
    $pdf->DosColumnas(
        'Cedula juridica', obtenerValor($payload, 'empresa.cedula_juridica', obtenerValor($payload, 'cedula_juridica', '')),
        'Telefono', obtenerValor($payload, 'empresa.telefono', obtenerValor($payload, 'telefono', ''))
    );
    $pdf->Campo('Correo electronico', obtenerValor($payload, 'empresa.correo', obtenerValor($payload, 'correo', obtenerValor($payload, 'email', ''))));
    $pdf->Campo('Direccion', obtenerValor($payload, 'empresa.direccion', obtenerValor($payload, 'direccion', '')));

    // Representante legal
    $pdf->SeccionTitulo('REPRESENTANTE LEGAL');
    $pdf->Campo('Nombre', obtenerValor($payload, 'representante.nombre', obtenerValor($payload, 'representante_nombre', '')));
    $pdf->Campo('Cedula', obtenerValor($payload, 'representante.cedula', obtenerValor($payload, 'representante_cedula', '')));

    // Actividad económica
    $pdf->SeccionTitulo('ACTIVIDAD ECONOMICA');
    $pdf->Campo('Actividad principal', obtenerValor($payload, 'actividad.descripcion', obtenerValor($payload, 'actividad', '')));
    $pdf->Campo('Codigo CIIU', obtenerValor($payload, 'actividad.codigo_ciiu', obtenerValor($payload, 'codigo_ciiu', '')));

    // Información de planilla
    $pdf->SeccionTitulo('INFORMACION DE PLANILLA');
    $pdf->DosColumnas(
        'Total empleados', obtenerValor($payload, 'planilla.total_empleados', obtenerValor($payload, 'total_empleados', '')),
        'Planilla mensual', formatearMoneda(obtenerValor($payload, 'planilla.monto_mensual', obtenerValor($payload, 'planilla_mensual', '')))
    );

    // Desglose de trabajadores si existe
    $trabajadores = obtenerValor($payload, 'planilla.desglose', []);
    if (is_array($trabajadores) && count($trabajadores) > 0) {
        $pdf->Ln(3);
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->Cell(0, 6, 'Desglose de trabajadores por categoria:', 0, 1);

        $headers = ['Categoria', 'Cantidad', 'Salario Promedio'];
        $data = [];
        foreach ($trabajadores as $t) {
            $data[] = [
                $t['categoria'] ?? '',
                $t['cantidad'] ?? '',
                formatearMoneda($t['salario'] ?? 0)
            ];
        }
        if (count($data) > 0) {
            $pdf->TablaSimple($headers, $data);
        }
    }

    // Historial de siniestralidad
    $pdf->SeccionTitulo('HISTORIAL DE SINIESTRALIDAD');
    $pdf->Checkbox('Sin accidentes laborales en los ultimos 3 anos', !obtenerValor($payload, 'historial.tiene_accidentes', false));
    $pdf->Checkbox('Con accidentes laborales previos', obtenerValor($payload, 'historial.tiene_accidentes', false));

    $cantidad_accidentes = obtenerValor($payload, 'historial.cantidad_accidentes', '');
    if ($cantidad_accidentes) {
        $pdf->Campo('Cantidad de accidentes', $cantidad_accidentes);
    }
}

function generarPDFGenerico($pdf, $payload, $tipo) {
    // Para tipos de seguro no especificados, mostrar todos los datos del payload
    $pdf->SeccionTitulo('DATOS DE LA SOLICITUD');

    mostrarArrayRecursivo($pdf, $payload, 0);
}

function mostrarArrayRecursivo($pdf, $array, $nivel) {
    foreach ($array as $key => $value) {
        $indent = str_repeat('  ', $nivel);

        if (is_array($value)) {
            $pdf->SetFont('Helvetica', 'B', 9);
            $pdf->Cell(0, 6, $indent . ucfirst(str_replace('_', ' ', $key)) . ':', 0, 1);
            mostrarArrayRecursivo($pdf, $value, $nivel + 1);
        } else {
            $label = ucfirst(str_replace('_', ' ', $key));
            $pdf->Campo($indent . $label, (string)$value);
        }
    }
}

function formatearMoneda($valor) {
    if (empty($valor)) return '';
    $numero = preg_replace('/[^0-9.]/', '', $valor);
    if (is_numeric($numero)) {
        return '₡ ' . number_format((float)$numero, 2, ',', '.');
    }
    return $valor;
}
