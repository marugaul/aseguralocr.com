<?php
declare(strict_types=1);

// rt_procesar.php - Procesador de solicitudes de Seguro de Riesgos del Trabajo

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
    error_log('[rt_procesar] ' . $msg);
}

function generate_rt_pdf(array $clean, string $referencia, string $pdfPath): array {
    $result = ['ok' => false, 'error' => null];
    try {
        if (!class_exists('FPDF')) {
            throw new RuntimeException('FPDF no disponible');
        }

        $getVal = function(string $k) use ($clean) {
            $v = $clean[$k] ?? '';
            if (is_array($v)) $v = implode(', ', $v);
            $v = trim((string)$v);
            return $v !== '' ? $v : 'NO INDICADO';
        };

        $getConsent = function(string $k) use ($clean) {
            $v = strtolower($clean[$k] ?? '');
            if (in_array($v, ['on', 'si', 'yes', 'aceptado', 'true', '1'], true)) return 'Aceptado';
            if ($v === '') return 'NO INDICADO';
            return ucfirst($v);
        };

        $fmtMoney = function($amount) {
            if ($amount === null || $amount === '' || !is_numeric($amount)) return 'NO INDICADO';
            return '₡' . number_format((float)$amount, 0, '.', ',');
        };

        $pdf = new FPDF();
        $pdf->SetAutoPageBreak(true, 20);
        $pdf->AddPage();

        // Logo
        $logoPathCandidates = [
            $_SERVER['DOCUMENT_ROOT'] . '/IMAGENES/INSNEGRO.png',
            __DIR__ . '/../imagenes/INSNEGRO.png',
            __DIR__ . '/../imagenes/INSBLANCO.png',
            __DIR__ . '/INSNEGRO.png'
        ];
        foreach ($logoPathCandidates as $c) {
            if ($c && file_exists($c)) {
                $pdf->Image($c, 15, 10, 35);
                break;
            }
        }

        // Encabezado derecho
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetXY(140, 12);
        $pdf->SetTextColor(41, 128, 185);
        $pdf->Cell(55, 5, utf8_decode('AseguraloCR'), 0, 2, 'R');
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(80, 80, 80);
        $pdf->Cell(55, 4, 'info@aseguralocr.com', 0, 2, 'R');
        $pdf->Cell(55, 4, 'www.aseguralocr.com', 0, 2, 'R');

        $pdf->Ln(18);

        // Título principal
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->SetTextColor(33, 37, 41);
        $pdf->Cell(0, 8, utf8_decode('SOLICITUD DE SEGURO DE RIESGOS DEL TRABAJO'), 0, 1, 'C');
        $pdf->Ln(6);

        // Recuadro info
        $rectX = 15;
        $rectY = $pdf->GetY();
        $rectW = 180;
        $rectH = 22;
        $pdf->SetFillColor(240, 248, 255);
        $pdf->Rect($rectX, $rectY, $rectW, $rectH, 'F');

        $innerPaddingX = 6;
        $innerWidth = $rectW - ($innerPaddingX * 2);

        $pdf->SetXY($rectX + $innerPaddingX, $rectY + 4);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetTextColor(41, 128, 185);
        $pdf->MultiCell($innerWidth, 5, utf8_decode('Referencia: ' . $referencia), 0, 'L');

        $pdf->SetXY($rectX + $innerPaddingX, $rectY + 4 + 7);
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(80, 80, 80);
        $pdf->Cell($innerWidth, 5, 'Fecha: ' . date('d/m/Y H:i:s'), 0, 1, 'L');

        $pdf->SetY($rectY + $rectH + 6);

        // Helper para imprimir secciones
        $print_section = function(FPDF $pdf, string $title, array $fields, $getValFn, $getConsentFn, $fmtMoneyFn) use ($clean) {
            $pdf->Ln(0);
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->SetTextColor(41, 128, 185);
            $pdf->Cell(0, 7, utf8_decode($title), 0, 1);
            $y = $pdf->GetY();
            $pdf->SetDrawColor(200, 200, 200);
            $pdf->SetLineWidth(0.2);
            $pdf->Line(15, $y, 195, $y);
            $pdf->Ln(3);

            $pdf->SetFont('Arial', '', 10);
            $pdf->SetTextColor(60, 60, 60);

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
                    $val = $fmtMoneyFn($clean[$key] ?? '');
                } else {
                    $val = $getValFn($key);
                }

                $pdf->SetFont('Arial', 'B', 9);
                $pdf->SetTextColor(80, 80, 80);
                $pdf->Cell(60, 6, utf8_decode($label . ':'), 0, 0);
                $pdf->SetFont('Arial', '', 9);
                $pdf->SetTextColor(40, 40, 40);
                $pdf->MultiCell(0, 6, utf8_decode($val), 0, 'L');
            }
            $pdf->Ln(2);
        };

        // Secciones del PDF
        $sections = [
            'DATOS DEL SOLICITANTE' => [
                'Tipo de Identificación' => 'solicitanteTipoId',
                'Número de Identificación' => 'solicitanteNumeroId',
                'Nombre Completo' => 'solicitanteNombre',
                'Teléfono' => 'solicitanteTelefono',
                'Correo Electrónico' => 'solicitanteCorreo',
                '¿Es el Patrono?' => 'solicitanteEsPatrono'
            ],
            'DATOS DEL PATRONO / EMPRESA' => [
                'Tipo de Persona' => 'tipoPersona',
                'Cédula' => 'numeroId',
                'Número Patronal CCSS' => 'numeroPatronal',
                'Razón Social' => 'razonSocial',
                'Nombre Comercial' => 'nombreComercial',
                'Representante Legal' => 'representanteLegal',
                'Cédula Representante' => 'cedulaRepresentante',
                'Provincia' => 'provincia',
                'Cantón' => 'canton',
                'Distrito' => 'distrito',
                'País' => 'pais',
                'Dirección' => 'direccion',
                'Teléfono Principal' => 'telefonoPrincipal',
                'Teléfono Celular' => 'telefonoCelular',
                'Fax' => 'fax',
                'Correo Electrónico' => 'correo'
            ],
            'ACTIVIDAD ECONÓMICA' => [
                'Actividad Principal' => 'actividadPrincipal',
                'Descripción de la Actividad' => 'descripcionActividad',
                'Riesgos del Trabajo' => 'riesgos',
                'Otros Riesgos' => 'otrosRiesgos',
                'Horario de Trabajo' => 'horarioTrabajo',
                'Días de Operación' => 'diasOperacion'
            ],
            'INFORMACIÓN DE PLANILLA' => [
                'Trabajadores Permanentes' => 'trabajadoresPermanentes',
                'Trabajadores Temporales' => 'trabajadoresTemporales',
                'Total de Trabajadores' => 'totalTrabajadores',
                'Administrativos' => 'trabajadoresAdmin',
                'Operativos' => 'trabajadoresOperativos',
                'Conductores' => 'trabajadoresConductores',
                'Vendedores' => 'trabajadoresVendedores',
                'Planilla Mensual' => ['key' => 'planillaMensual', 'type' => 'money'],
                'Planilla Anual' => ['key' => 'planillaAnual', 'type' => 'money'],
                'Observaciones Planilla' => 'observacionesPlanilla'
            ],
            'DATOS DE LA PÓLIZA' => [
                'Tipo de Póliza' => 'tipoPoliza',
                'Aseguradora Actual' => 'aseguradoraActual',
                'Póliza Actual' => 'polizaActual',
                'Vencimiento Póliza' => 'vencimientoPoliza',
                'Prima Actual' => ['key' => 'primaActual', 'type' => 'money'],
                'Plan de Pago' => 'planPago',
                'Fecha de Inicio' => 'fechaInicio',
                'Comentarios' => 'comentarios'
            ],
            'CONSENTIMIENTOS' => [
                'Declaración Verídica' => ['key' => 'consentimientoInfo', 'type' => 'consent'],
                'Consentimiento Grabación' => ['key' => 'consentimientoGrabacion', 'type' => 'consent'],
                'Autorización Datos' => ['key' => 'consentimientoDatos', 'type' => 'consent']
            ]
        ];

        foreach ($sections as $title => $fields) {
            $print_section($pdf, $title, $fields, $getVal, $getConsent, $fmtMoney);
            if ($pdf->GetY() > 250) $pdf->AddPage();
        }

        // Footer
        $pdf->Ln(6);
        $pdf->SetFont('Arial', 'I', 8);
        $pdf->SetTextColor(120, 120, 120);
        $pdf->Cell(0, 5, utf8_decode('Documento generado por AseguraloCR - ' . date('d/m/Y H:i:s')), 0, 1, 'C');

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

    // Honeypot check
    if (!empty($in['website'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Solicitud bloqueada']);
        exit;
    }

    // CSRF validation
    if (!$isJson) {
        if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], (string)($in['csrf'] ?? ''))) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'CSRF inválido']);
            exit;
        }
    }

    // Rate limiting
    $now = microtime(true) * 1000;
    $last = $_SESSION['last_submit'] ?? 0;
    if (($now - $last) < 3000) {
        http_response_code(429);
        echo json_encode(['success' => false, 'message' => 'Demasiadas solicitudes']);
        exit;
    }
    $_SESSION['last_submit'] = $now;

    // Validate required fields - Datos del Solicitante
    $solicitanteNombre = sanitize((string)($in['solicitanteNombre'] ?? ''));
    $solicitanteCorreo = sanitize((string)($in['solicitanteCorreo'] ?? ''));
    $solicitanteNumeroId = sanitize((string)($in['solicitanteNumeroId'] ?? ''));
    $solicitanteTipoId = sanitize((string)($in['solicitanteTipoId'] ?? 'cedula'));
    $solicitanteTelefono = sanitize((string)($in['solicitanteTelefono'] ?? ''));

    // Datos del Patrono
    $razonSocial = sanitize((string)($in['razonSocial'] ?? ''));
    $correo = sanitize((string)($in['correo'] ?? ''));
    $numeroId = sanitize((string)($in['numeroId'] ?? ''));

    // Validar campos del solicitante
    if ($solicitanteNombre === '' || !filter_var($solicitanteCorreo, FILTER_VALIDATE_EMAIL) || $solicitanteNumeroId === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Nombre, correo y cédula del solicitante son obligatorios']);
        exit;
    }

    // Validar datos del patrono
    if ($razonSocial === '' || $numeroId === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Razón social y cédula del patrono son obligatorios']);
        exit;
    }

    // Sanitize all input
    $clean = [];
    foreach ($in as $k => $v) {
        if (is_array($v)) {
            $clean[$k] = array_map(function($item) { return is_string($item) ? sanitize($item) : $item; }, $v);
        } else {
            $clean[$k] = is_string($v) ? sanitize($v) : $v;
        }
    }

    // Normalize canton/distrito
    $clean['canton'] = trim((string)($clean['canton'] ?? ($clean['canton_fallback'] ?? '')));
    $clean['distrito'] = trim((string)($clean['distrito'] ?? ($clean['distrito_fallback'] ?? '')));

    // Load dependencies
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (file_exists($autoload)) {
        require_once $autoload;
        log_err('vendor/autoload.php cargado.');
    } else {
        // PHPMailer
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

        // FPDF
        $fpdfCandidates = [
            __DIR__ . '/../vendor/FPDF/FPDF.PHP',
            __DIR__ . '/../vendor/FPDF/FPDF.php',
            __DIR__ . '/../vendor/fpdf/fpdf.php',
            __DIR__ . '/../fpdf/fpdf.php',
            __DIR__ . '/../vendor/setasign/fpdf/fpdf.php'
        ];
        foreach ($fpdfCandidates as $p) {
            if (file_exists($p)) {
                require_once $p;
                log_err("FPDF cargado desde $p");
                break;
            }
        }
    }

    if (!class_exists('PDO')) {
        throw new RuntimeException('Extensión PDO no disponible.');
    }

    // Database connection
    $dsn = "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=$DB_CHARSET";
    try {
        $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    } catch (Throwable $dbErr) {
        log_err('DB connect error: ' . $dbErr->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error conectando BD']);
        exit;
    }

    // Generate reference and save to database
    $referencia = 'RT-' . date('YmdHis') . '-' . bin2hex(random_bytes(3));
    $stmt = $pdo->prepare("INSERT INTO submissions (referencia, origen, payload, email, created_at, ip, user_agent)
        VALUES (:r, 'riesgos-trabajo', CAST(:p AS JSON), :e, :c, :ip, :ua)");
    $stmt->execute([
        ':r' => $referencia,
        ':p' => json_encode($clean, JSON_UNESCAPED_UNICODE),
        ':e' => $solicitanteCorreo, // Usar correo del solicitante
        ':c' => date('Y-m-d H:i:s'),
        ':ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ':ua' => $_SERVER['HTTP_USER_AGENT'] ?? '',
    ]);

    // Generate PDF
    $pdfDir = realpath(__DIR__ . '/../storage/pdfs') ?: (__DIR__ . '/../storage/pdfs');
    if (!is_dir($pdfDir)) {
        if (!mkdir($pdfDir, 0775, true) && !is_dir($pdfDir)) {
            throw new RuntimeException("No se pudo crear el directorio de PDFs: $pdfDir");
        }
    }
    $pdfPath = $pdfDir . '/' . $referencia . '.pdf';

    $gen = generate_rt_pdf($clean, $referencia, $pdfPath);
    if (empty($gen['ok'])) {
        log_err('Error generando PDF: ' . ($gen['error'] ?? 'desconocido'));
    }

    // Crear o actualizar cliente con los datos del solicitante
    $clientId = null;
    try {
        // Buscar si ya existe un cliente con esa cédula
        $stmtClient = $pdo->prepare("SELECT id FROM clients WHERE cedula = ?");
        $stmtClient->execute([$solicitanteNumeroId]);
        $existingClient = $stmtClient->fetch();

        if ($existingClient) {
            // Actualizar cliente existente
            $clientId = $existingClient['id'];
            $updateStmt = $pdo->prepare("UPDATE clients SET nombre = ?, correo = ?, telefono = ?, tipo_id = ?, updated_at = NOW() WHERE id = ?");
            $updateStmt->execute([$solicitanteNombre, $solicitanteCorreo, $solicitanteTelefono, $solicitanteTipoId, $clientId]);
            log_err("Cliente actualizado ID: $clientId");
        } else {
            // Crear nuevo cliente
            $insertStmt = $pdo->prepare("INSERT INTO clients (tipo_id, cedula, nombre, correo, telefono, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $insertStmt->execute([$solicitanteTipoId, $solicitanteNumeroId, $solicitanteNombre, $solicitanteCorreo, $solicitanteTelefono]);
            $clientId = $pdo->lastInsertId();
            log_err("Nuevo cliente creado ID: $clientId");
        }
    } catch (Throwable $e) {
        log_err("Error creando/actualizando cliente: " . $e->getMessage());
    }

    // Vincular cotización con cliente
    require_once __DIR__ . '/../app/services/QuoteService.php';
    $quoteService = new QuoteService($pdo);
    // Usar el correo del solicitante para vincular
    $linkResult = $quoteService->linkQuoteToClient($solicitanteCorreo, $referencia, 'riesgos-trabajo', $clean, $pdfPath);
    if ($linkResult['linked']) {
        log_err('Cotización vinculada al cliente ID: ' . $linkResult['client_id'] . ' - Quote: ' . ($linkResult['numero_cotizacion'] ?? 'N/A'));
    } else {
        log_err('Cotización no vinculada: ' . $linkResult['message']);
    }

    // Send email
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
            $m->Host = $SMTP_HOST;
            $m->Port = (int)$SMTP_PORT;
            $m->SMTPAuth = true;
            // Puerto 465 usa SSL, puerto 587 usa TLS
            $m->SMTPSecure = ((int)$SMTP_PORT === 465) ? 'ssl' : 'tls';
            $m->Username = $SMTP_USER;
            $m->Password = $SMTP_PASS;
            $m->CharSet = 'UTF-8';

            $m->setFrom($SMTP_FROM, $SMTP_FROM_NAME);

            // Enviar TO al solicitante (quien llena el formulario)
            if (!empty($solicitanteCorreo) && filter_var($solicitanteCorreo, FILTER_VALIDATE_EMAIL)) {
                $m->addAddress($solicitanteCorreo, $solicitanteNombre);
                log_err("Email enviado a solicitante: $solicitanteCorreo");
            }

            // Copia al admin/empresa
            $m->addCC($EMAIL_DESTINO);

            if ($EMAIL_COPIA && filter_var($EMAIL_COPIA, FILTER_VALIDATE_EMAIL)) {
                $m->addBCC($EMAIL_COPIA);
            }
            if (!empty($correo) && filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                $m->addReplyTo($correo, $razonSocial);
            }

            $m->isHTML(true);
            $m->Subject = "Nueva solicitud Riesgos del Trabajo ($referencia)";

            $totalTrab = $clean['totalTrabajadores'] ?? 'N/A';
            $planilla = $clean['planillaMensual'] ?? 'N/A';
            if (is_numeric($planilla)) {
                $planilla = '₡' . number_format((float)$planilla, 0, '.', ',');
            }

            $htmlBody = "<p>Se ha recibido una nueva solicitud de Seguro de Riesgos del Trabajo.</p>";
            $htmlBody .= "<p><b>Referencia:</b> " . htmlspecialchars($referencia) . "</p>";
            $htmlBody .= "<p><b>Patrono:</b> " . htmlspecialchars($razonSocial) . "</p>";
            $htmlBody .= "<p><b>Cédula:</b> " . htmlspecialchars($numeroId) . "</p>";
            $htmlBody .= "<p><b>Correo:</b> " . htmlspecialchars($correo) . "</p>";
            $htmlBody .= "<p><b>Total Trabajadores:</b> " . htmlspecialchars((string)$totalTrab) . "</p>";
            $htmlBody .= "<p><b>Planilla Mensual:</b> " . htmlspecialchars($planilla) . "</p>";
            $htmlBody .= "<hr><p>La información completa se encuentra en el PDF adjunto.</p>";

            $plain = "Nueva solicitud de Seguro de Riesgos del Trabajo.\n";
            $plain .= "Referencia: $referencia\n";
            $plain .= "Patrono: $razonSocial\n";
            $plain .= "Cédula: $numeroId\n";
            $plain .= "Total Trabajadores: $totalTrab\n";
            $plain .= "Planilla Mensual: $planilla\n";

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
        log_err('PHPMailer no disponible');
    }

    // Redirect to thank you page
    $params = [
        'ref' => $referencia,
        'email' => $mailSent ? '1' : '0',
        'tipo' => 'rt'
    ];

    $redirectUrl = '/enviarformularios/thank_you.php?' . http_build_query($params);
    header('Location: ' . $redirectUrl);
    exit;

} catch (Throwable $e) {
    log_err($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno. Por favor intente nuevamente.']);
    exit;
}
