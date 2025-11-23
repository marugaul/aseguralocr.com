<?php
declare(strict_types=1);

// hogar_procesar.php - Procesador seguro con config externa

// Cargar configuración desde archivo protegido
$config = require __DIR__ . '/../app/config/config.php';

// Extraer configuraciones
$DB_HOST = $config['db']['mysql']['host'];
$DB_NAME = $config['db']['mysql']['dbname'];
$DB_USER = $config['db']['mysql']['user'];
$DB_PASS = $config['db']['mysql']['pass'];
$DB_CHARSET = $config['db']['mysql']['charset'];

$SMTP_HOST = $config['mail']['host'];
$SMTP_PORT = $config['mail']['port'];
$SMTP_USER = $config['mail']['user'];
$SMTP_PASS = $config['mail']['pass'];
$SMTP_FROM = $config['mail']['from'][0];
$SMTP_FROM_NAME = $config['mail']['from'][1];
$EMAIL_DESTINO = $config['mail']['to'][0];
$EMAIL_COPIA = $config['mail']['bcc'][0] ?? null;

// Configurar sesión segura
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', '1');
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', '1');

session_start();
header('Content-Type: application/json; charset=utf-8');

function sanitize(string $s, int $max = 2000): string {
    $s = preg_replace('/<script[^>]*>.*?<\\/script>/is', '', $s);
    $s = preg_replace('/<iframe[^>]*>.*?<\\/iframe>/is', '', $s);
    $s = preg_replace('/javascript:/i', '', $s);
    $s = preg_replace('/on\\w+\\s*=/i', '', $s);
    return mb_substr(trim($s), 0, $max);
}
function log_err(string $msg): void {
    error_log('[hogar_procesar] ' . $msg);
}
function generate_hogar_pdf(array $clean, string $referencia, string $pdfPath): array {
    $result = ['ok' => false, 'error' => null];
    try {
    if (!class_exists('FPDF')) {
    throw new RuntimeException('FPDF no disponible');
    }

    $getVal = function(string $k) use ($clean) {
    $v = $clean[$k] ?? '';
    if (is_array($v)) $v = json_encode($v, JSON_UNESCAPED_UNICODE);
    $v = trim((string)$v);
    return $v !== '' ? $v : 'NO INDICADO';
    };
    $getConsent = function(string $k) use ($clean) {
    $v = strtolower($clean[$k] ?? '');
    if (in_array($v, ['on', 'si', 'yes', 'aceptado', 'true', '1'], true)) return 'Aceptado';
    if ($v === '') return 'NO INDICADO';
    return ucfirst($v);
    };
    $fmtMoney = function($amount, $moneda = 'colones') {
    if ($amount === null || $amount === '' || !is_numeric($amount)) return 'NO INDICADO';
    if ($moneda === 'dolares' || strtolower($moneda) === 'usd') {
    return '$' . number_format((float)$amount, 2, '.', ',');
    }
    return '₡' . number_format((float)$amount, 0, '.', ',');
    };

    $pdf = new FPDF();
    $pdf->SetAutoPageBreak(true, 20);
    $pdf->AddPage();

    // logo
    $logoPathCandidates = [
    $_SERVER['DOCUMENT_ROOT'] . '/IMAGENES/INSNEGRO.png',
    __DIR__ . '/../imagenes/INSNEGRO.png',
    __DIR__ . '/../imagenes/INSBLANCO.png',
    __DIR__ . '/INSNEGRO.png'
    ];
    foreach ($logoPathCandidates as $c) { if ($c && file_exists($c)) { $pdf->Image($c, 15, 10, 35); break; } }

    // Encabezado derecho
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(140, 12);
    $pdf->SetTextColor(41,128,185);
    $pdf->Cell(55, 5, utf8_decode('AseguraloCR'), 0, 2, 'R');
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetTextColor(80,80,80);
    $pdf->Cell(55, 4, 'info@aseguralocr.com', 0, 2, 'R');
    $pdf->Cell(55, 4, 'www.aseguralocr.com', 0, 2, 'R');

    $pdf->Ln(18);

    // Título principal
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->SetTextColor(33,37,41);
    $pdf->Cell(0, 8, utf8_decode('SOLICITUD DE SEGURO DE HOGAR - DETALLE'), 0, 1, 'C');
    $pdf->Ln(6);

    // Recuadro con padding y texto alineado correctamente
    $rectX = 15;
    $rectY = $pdf->GetY();
    $rectW = 180;
    $rectH = 22;
    $pdf->SetFillColor(240,248,255);
    $pdf->Rect($rectX, $rectY, $rectW, $rectH, 'F');

    // padding interno izquierdo/derecho
    $innerPaddingX = 6;
    $innerWidth = $rectW - ($innerPaddingX * 2);

    // título dentro del recuadro (alineado a la izquierda con padding)
    $pdf->SetXY($rectX + $innerPaddingX, $rectY + 4);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetTextColor(41,128,185);
    // usamos MultiCell para que haga wrapping si la referencia es larga
    $pdf->MultiCell($innerWidth, 5, utf8_decode('INFORMACIÓN DE LA SOLICITUD - Referencia: ' . $referencia), 0, 'L');

    // fecha en segunda línea dentro del recuadro con menor tamaño
    $pdf->SetXY($rectX + $innerPaddingX, $rectY + 4 + 7);
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetTextColor(80,80,80);
    $pdf->Cell($innerWidth, 5, 'Fecha: ' . date('d/m/Y H:i:s'), 0, 1, 'L');

    // mover cursor bajo el recuadro (y margen extra)
    $pdf->SetY($rectY + $rectH + 6);

    // Helper para imprimir secciones (igual que antes)
    $print_section = function(FPDF $pdf, string $title, array $fields, $getValFn, $getConsentFn, $fmtMoneyFn) {
    $pdf->Ln(0);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetTextColor(41,128,185);
    $pdf->Cell(0, 7, utf8_decode($title), 0, 1);
    $y = $pdf->GetY();
    $pdf->SetDrawColor(200,200,200);
    $pdf->SetLineWidth(0.2);
    $pdf->Line(15, $y, 195, $y);
    $pdf->Ln(3);

    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(60,60,60);
    foreach ($fields as $label => $meta) {
    if (is_array($meta)) {
    $key = $meta['key'];
    $type = $meta['type'] ?? 'text';
    } else {
    $key = $meta;
    $type = 'text';
    }

    if ($type === 'consent') {
    $val = $getConsentFn($key);
    } elseif ($type === 'money') {
    $val = $fmtMoneyFn($clean[$key] ?? '', $clean[$meta['monedaKey'] ?? 'moneda'] ?? 'colones');
    } else {
    $val = $getValFn($key);
    }

    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetTextColor(80,80,80);
    $pdf->Cell(60, 6, utf8_decode($label . ':'), 0, 0);
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(40,40,40);
    $pdf->MultiCell(0, 6, utf8_decode($val), 0, 'L');
    }
    $pdf->Ln(2);
    };

    // Sections (usa las mismas keys que ya tienes)
    $sections = [
    'DATOS PERSONALES' => [
    'Tipo de Identificación' => 'tipoId',
    'Número de Identificación' => 'numeroId',
    'Nombre Completo' => 'nombreCompleto',
    'Provincia' => 'provincia',
    'Cantón' => 'canton',
    'Distrito' => 'distrito',
    'País' => 'pais',
    'Dirección' => 'direccion',
    'Teléfono Celular' => 'telefonoCelular',
    'Teléfono Domicilio' => 'telefonoDomicilio',
    'Teléfono Oficina' => 'telefonoOficina',
    'Correo Electrónico' => 'correo'
    ],
    'DATOS DE LA PROPIEDAD / COORDENADAS' => [
    'Tipo de Propiedad (actividad)' => 'actividad',
    'Tipo de Propiedad (tipoPropiedad)' => 'tipoPropiedad',
    'Latitud' => 'latitud',
    'Longitud' => 'longitud',
    'Provincia (Propiedad)' => 'provinciaProp',
    'Cantón (Propiedad)' => 'cantonProp',
    'Distrito (Propiedad)' => 'distritoProp',
    'Localizado en esquina' => 'esquina',
    'Urbanización / Barrio' => 'urbanizacion',
    'Otras señas' => 'otrasSenas',
    'Folio Real / Finca' => 'folioReal'
    ],
    'CARACTERÍSTICAS DE CONSTRUCCIÓN' => [
    'Año de Construcción' => 'anoConst',
    'Área Construcción (m²)' => 'areaConstruccion',
    'Cantidad de Pisos' => 'cantidadPisos',
    'Área por piso igual' => 'areaPorPisoIgual',
    'Piso de Ubicación' => 'pisoUbicacion',
    'Sistema Eléctrico' => 'sistemaElectrico',
    'Tipo de Construcción' => 'tipoConstruccion',
    'Estado de Conservación' => 'estadoConservacion',
    'Modificaciones estructurales' => 'modificacionesEstructurales'
    ],
    'MEDIDAS DE SEGURIDAD' => [
    'Vigilancia' => 'vigilancia',
    'Horario Vigilancia' => 'horarioVigilancia',
    'Alarma' => 'alarma',
    'Cerraduras' => 'cerraduras',
    'Tapias' => 'tapias',
    'Altura Tapias (m)' => 'alturaTapias',
    'Material Tapias' => 'materialTapias',
    'Alambre Navaja' => 'alambreNavaja',
    'Tipo de Ventanas' => 'tipoVentanas',
    'Puertas Externas' => 'puertasExternas',
    'Propiedad permanece sola' => 'propiedadSola',
    'Horas aproximadas sola' => 'horasSola',
    'Otras medidas de seguridad' => 'otrasMedidasSeguridad'
    ],
    'COBERTURAS' => [
    'Cobertura V (Edificio)' => 'coberturaV',
    'Cobertura Contenidos' => 'coberturaContenidos',
    'Cobertura D (Desastres)' => 'coberturaD',
    'Cobertura K (Resp. Civil)' => 'coberturaK',
    'Cobertura P (Accidentes)' => 'coberturaP',
    'Monto Residencia' => ['key' => 'montoResidencia', 'type' => 'money', 'monedaKey' => 'moneda'],
    'Monto Contenido' => ['key' => 'montoContenido', 'type' => 'money', 'monedaKey' => 'moneda']
    ],
    'PLAN DE PAGO / OPCIONES' => [
    'Plan de Pago' => 'planPago',
    'Opción de Aseguramiento' => 'interesAseg'
    ],
    'CONSENTIMIENTOS' => [
    'Consentimiento: Información verídica' => ['key' => 'consentimientoInfo', 'type' => 'consent'],
    'Consentimiento: Grabación' => ['key' => 'consentimientoGrabacion', 'type' => 'consent'],
    'Consentimiento: Tratamiento de Datos' => ['key' => 'consentimientoDatos', 'type' => 'consent']
    ]
    ];

    foreach ($sections as $title => $fields) {
    $print_section($pdf, $title, $fields, $getVal, $getConsent, $fmtMoney);
    if ($pdf->GetY() > 250) $pdf->AddPage();
    }

    // footer
    $pdf->Ln(6);
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->SetTextColor(120,120,120);
    $pdf->Cell(0, 5, utf8_decode('La información completa de la solicitud se encuentra en este documento.'), 0, 1, 'C');
    $pdf->Cell(0, 5, utf8_decode('Generado por AseguraloCR - ' . date('d/m/Y H:i:s')), 0, 1, 'C');

    $pdf->Output('F', $pdfPath);
    $result['ok'] = is_file($pdfPath);
    if (!$result['ok']) $result['error'] = 'No se pudo guardar el PDF en disco';
    } catch (Throwable $e) {
    $result['error'] = $e->getMessage();
    }
    return $result;
}


try {
    $ctype = $_SERVER['CONTENT_TYPE'] ?? '';
    $isJson = stripos((string)$ctype, 'application/json') !== false;
    $raw = file_get_contents('php://input');
    $in = $isJson ? json_decode($raw, true) : $_POST;
    if (!is_array($in)) $in = [];

    if (!empty($in['website'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Solicitud bloqueada']);
    exit;
    }

    if (!$isJson) {
    if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], (string)($in['csrf'] ?? ''))) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'CSRF inválido']);
    exit;
    }
    }

    $now = microtime(true) * 1000;
    $last = $_SESSION['last_submit'] ?? 0;
    if (($now - $last) < 3000) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Demasiadas solicitudes']);
    exit;
    }
    $_SESSION['last_submit'] = $now;

    $nombre = sanitize((string)($in['nombreCompleto'] ?? ''));
    $correo = sanitize((string)($in['correo'] ?? ''));
    if ($nombre === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Nombre y correo son obligatorios']);
    exit;
    }

    $clean = [];
    foreach ($in as $k => $v) {
    $clean[$k] = is_string($v) ? sanitize($v) : $v;
    }

    // --- NORMALIZACIÓN: tomar canton/distrito del select o del fallback (si usuario escribió manualmente)
    $clean['canton'] = trim((string)($clean['canton'] ?? ($clean['canton_fallback'] ?? '')));
    $clean['distrito'] = trim((string)($clean['distrito'] ?? ($clean['distrito_fallback'] ?? '')));
    // Para la propiedad (si usas cantonProp/distritoProp)
    $clean['cantonProp'] = trim((string)($clean['cantonProp'] ?? ($clean['cantonProp_fallback'] ?? '')));
    $clean['distritoProp'] = trim((string)($clean['distritoProp'] ?? ($clean['distritoProp_fallback'] ?? '')));

    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (file_exists($autoload)) {
    require_once $autoload;
    log_err('vendor/autoload.php cargado.');
    } else {
    $phpmailerCandidates = [
    __DIR__ . '/../vendor/phpmailer/phpmailer/src',
    __DIR__ . '/../vendor/phpmailer/src',
    __DIR__ . '/../vendor/phpmailer/PHPMailer/src'
    ];
    $loadedPHPMailer = false;
    foreach ($phpmailerCandidates as $base) {
    $f1 = $base . '/Exception.php';
    $f2 = $base . '/PHPMailer.php';
    $f3 = $base . '/SMTP.php';
    if (file_exists($f2)) {
    if (file_exists($f1)) require_once $f1;
    require_once $f2;
    if (file_exists($f3)) require_once $f3;
    log_err("PHPMailer cargado desde $base");
    $loadedPHPMailer = true;
    break;
    }
    }
    if (!$loadedPHPMailer) {
    log_err('PHPMailer no encontrado en rutas previstas.');
    }

    $fpdfCandidates = [
    __DIR__ . '/../vendor/FPDF/FPDF.PHP',
    __DIR__ . '/../vendor/FPDF/FPDF.php',
    __DIR__ . '/../vendor/fpdf/fpdf.php',
    __DIR__ . '/../fpdf/fpdf.php',
    __DIR__ . '/../vendor/setasign/fpdf/fpdf.php'
    ];
    $loadedFPDF = false;
    foreach ($fpdfCandidates as $p) {
    if (file_exists($p)) {
    require_once $p;
    log_err("FPDF cargado desde $p");
    $loadedFPDF = true;
    break;
    }
    }
    if (!$loadedFPDF) {
    log_err('FPDF no encontrado en rutas previstas.');
    }
    }

    if (!class_exists('PDO')) {
    throw new RuntimeException('Extensión PDO no disponible.');
    }

    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    } catch (Throwable $dbErr) {
    log_err('DB connect error: ' . $dbErr->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error conectando BD']);
    exit;
    }

    $referencia = 'HOGAR-' . date('YmdHis') . '-' . bin2hex(random_bytes(3));
    $stmt = $pdo->prepare("INSERT INTO submissions (referencia,origen,payload,email,created_at,ip,user_agent)
    VALUES (:r,'hogar',CAST(:p AS JSON),:e,:c,:ip,:ua)");
    $stmt->execute([
    ':r' => $referencia,
    ':p' => json_encode($clean, JSON_UNESCAPED_UNICODE),
    ':e' => $correo,
    ':c' => date('Y-m-d H:i:s'),
    ':ip' => $_SERVER['REMOTE_ADDR'] ?? '',
    ':ua' => $_SERVER['HTTP_USER_AGENT'] ?? '',
    ]);

    $pdfDir = realpath(__DIR__ . '/../storage/pdfs') ?: (__DIR__ . '/../storage/pdfs');
    if (!is_dir($pdfDir)) {
    if (!mkdir($pdfDir, 0775, true) && !is_dir($pdfDir)) {
    throw new RuntimeException("No se pudo crear el directorio de PDFs: $pdfDir");
    }
    }
    $pdfPath = $pdfDir . '/' . $referencia . '.pdf';

    $gen = generate_hogar_pdf($clean, $referencia, $pdfPath);
    if (empty($gen['ok'])) {
    log_err('Error generando PDF: ' . ($gen['error'] ?? 'desconocido'));
    }

    $mailSent = false;
    $mailError = null;
    if (class_exists('PHPMailer\\PHPMailer\\PHPMailer') || class_exists('PHPMailer')) {
    try {
    if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
    $m = new \PHPMailer\PHPMailer\PHPMailer(true);
    } else {
    $m = new \PHPMailer(true);
    }

    $m->isSMTP();
    $m->Host = SMTP_HOST;
    $m->Port = (int)SMTP_PORT;
    $m->SMTPAuth = true;
    $m->SMTPSecure = 'tls';
    $m->Username = SMTP_USER;
    $m->Password = SMTP_PASS;
    $m->CharSet = 'UTF-8';

    $m->setFrom(SMTP_FROM, SMTP_FROM_NAME);
    $m->addAddress(EMAIL_DESTINO);
    if (defined('EMAIL_COPIA') && filter_var(EMAIL_COPIA, FILTER_VALIDATE_EMAIL)) {
    $m->addBCC(EMAIL_COPIA);
    }
    if (!empty($correo) && filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    $m->addReplyTo($correo, $nombre);
    }

    $m->isHTML(true);
    $m->Subject = "Nueva solicitud Hogar ($referencia)";

    $htmlBody = "<p>Se ha recibido una nueva solicitud de seguro de hogar.</p>";
    $htmlBody .= "<p><b>Referencia de Solicitud:</b> " . htmlspecialchars($referencia) . "</p>";
    $htmlBody .= "<p><b>Nombre del Solicitante:</b> " . htmlspecialchars($nombre) . "</p>";
    $htmlBody .= "<p><b>Correo Electrónico:</b> " . htmlspecialchars($correo) . "</p>";
    $htmlBody .= "<p>La información completa de la solicitud se encuentra en el documento PDF adjunto.</p>";
    $htmlBody .= "<hr>";
    $htmlBody .= "<p>Este es un mensaje automático. Por favor, no responda a este correo.</p>";

    $plain = "Nueva solicitud de seguro de hogar.\n";
    $plain .= "Referencia de Solicitud: " . $referencia . "\n";
    $plain .= "Nombre del Solicitante: " . $nombre . "\n";
    $plain .= "Correo Electrónico: " . $correo . "\n";
    $plain .= "La información completa de la solicitud se encuentra en el documento PDF adjunto.\n";
    $plain .= "Este es un mensaje automático. Por favor, no responda a este correo.\n";

    $m->Body = $htmlBody;
    $m->AltBody = $plain;

    if (is_file($pdfPath)) {
    $m->addAttachment($pdfPath, basename($pdfPath));
    }

    $mailSent = $m->send();
    log_err('Email enviado: ' . ($mailSent ? 'OK' : 'FALSE'));
    } catch (Throwable $mailEx) {
    $mailError = $mailEx->getMessage();
    log_err('Error enviando email: ' . $mailError);
    $mailSent = false;
    }
    } else {
    log_err('PHPMailer no disponible - omitiendo envío de email.');
    }

    $params = [
    'ref' => $referencia,
    'email' => $mailSent ? '1' : '0'
    ];

    $redirectUrl = '/enviarformularios/thank_you.php?' . http_build_query($params);
    header('Location: ' . $redirectUrl);
    exit;

} catch (Throwable $e) {
    log_err($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    if (method_exists($e, 'getTraceAsString')) log_err($e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno. Por favor intente nuevamente.']);
    exit;
}