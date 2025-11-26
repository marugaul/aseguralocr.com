<?php
/**
 * API para autenticación por Email + OTP
 *
 * Endpoints:
 * - POST action=check_email: Verifica si hay cotizaciones previas
 * - POST action=send_otp: Envía código OTP al email
 * - POST action=verify_otp: Verifica el código y crea/vincula cuenta
 */

declare(strict_types=1);

// Configurar sesión para todo el dominio
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '.aseguralocr.com',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

header('Content-Type: application/json; charset=utf-8');

// Rate limiting simple
$rateLimitKey = 'email_auth_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
$currentMinute = date('YmdHi');
if (!isset($_SESSION[$rateLimitKey])) {
    $_SESSION[$rateLimitKey] = ['minute' => $currentMinute, 'count' => 0];
}
if ($_SESSION[$rateLimitKey]['minute'] !== $currentMinute) {
    $_SESSION[$rateLimitKey] = ['minute' => $currentMinute, 'count' => 0];
}
$_SESSION[$rateLimitKey]['count']++;
if ($_SESSION[$rateLimitKey]['count'] > 10) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Demasiadas solicitudes. Espera un minuto.']);
    exit;
}

// Cargar configuración
try {
    $config = require dirname(__DIR__, 2) . '/app/config/config.php';
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de configuración']);
    exit;
}

// Conexión a BD
try {
    $dsn = "mysql:host={$config['db']['mysql']['host']};dbname={$config['db']['mysql']['dbname']};charset={$config['db']['mysql']['charset']}";
    $pdo = new PDO($dsn, $config['db']['mysql']['user'], $config['db']['mysql']['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

// Cargar servicios
require_once dirname(__DIR__, 2) . '/app/services/OtpService.php';
require_once dirname(__DIR__, 2) . '/app/services/QuoteService.php';

$otpService = new OtpService($pdo, $config['mail']);
$quoteService = new QuoteService($pdo);

// Obtener acción
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$action = $input['action'] ?? '';

switch ($action) {

    case 'check_email':
        // Verificar si hay cotizaciones previas para este email
        $email = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);
        if (!$email) {
            echo json_encode(['success' => false, 'message' => 'Email inválido']);
            exit;
        }

        // Verificar si ya existe como cliente
        $client = $quoteService->findClientByEmail($email);

        // Buscar cotizaciones previas
        $prevSubmissions = $otpService->findPreviousSubmissions($email);

        echo json_encode([
            'success' => true,
            'has_account' => $client !== null,
            'has_previous_quotes' => $prevSubmissions !== null,
            'quote_count' => $prevSubmissions['total_cotizaciones'] ?? 0,
            'suggested_name' => $prevSubmissions['nombre'] ?? ''
        ]);
        break;

    case 'send_otp':
        // Enviar código OTP
        $email = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);
        if (!$email) {
            echo json_encode(['success' => false, 'message' => 'Email inválido']);
            exit;
        }

        // Buscar info previa para personalizar el email
        $prevSubmissions = $otpService->findPreviousSubmissions($email);
        $nombre = $prevSubmissions['nombre'] ?? '';

        // Generar y enviar OTP
        $otp = $otpService->generateOtp($email);
        $sent = $otpService->sendOtpEmail($email, $otp, $nombre);

        if ($sent) {
            echo json_encode([
                'success' => true,
                'message' => 'Código enviado a tu correo',
                'email_masked' => maskEmail($email)
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error enviando el código. Intenta de nuevo.'
            ]);
        }
        break;

    case 'verify_otp':
        // Verificar código OTP y crear/vincular cuenta
        $email = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $code = preg_replace('/[^0-9]/', '', $input['code'] ?? '');

        if (!$email || strlen($code) !== 6) {
            echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
            exit;
        }

        // Verificar OTP
        $verification = $otpService->verifyOtp($email, $code);

        if (!$verification['valid']) {
            echo json_encode(['success' => false, 'message' => $verification['message']]);
            exit;
        }

        // OTP válido - crear o vincular cuenta
        $client = $quoteService->findClientByEmail($email);

        if ($client) {
            // Cliente ya existe - solo hacer login
            $_SESSION['client_id'] = $client['id'];
            $_SESSION['client_email'] = $email;
            $_SESSION['client_name'] = $client['nombre_completo'];

            // Actualizar last_login
            $pdo->prepare("UPDATE clients SET last_login = NOW() WHERE id = ?")->execute([$client['id']]);

            echo json_encode([
                'success' => true,
                'message' => 'Bienvenido de vuelta',
                'redirect' => '/client/dashboard.php'
            ]);
        } else {
            // Crear nuevo cliente
            $prevSubmissions = $otpService->findPreviousSubmissions($email);
            $nombre = $prevSubmissions['nombre'] ?? '';
            $telefono = $prevSubmissions['telefono'] ?? '';

            $stmt = $pdo->prepare("
                INSERT INTO clients (email, nombre_completo, telefono, email_verified, status, created_at, last_login)
                VALUES (?, ?, ?, TRUE, 'active', NOW(), NOW())
            ");
            $stmt->execute([$email, $nombre, $telefono]);
            $clientId = (int) $pdo->lastInsertId();

            // Vincular todas las cotizaciones previas
            if ($prevSubmissions && $prevSubmissions['total_cotizaciones'] > 0) {
                linkPreviousSubmissions($pdo, $email, $clientId);
            }

            // Hacer login
            $_SESSION['client_id'] = $clientId;
            $_SESSION['client_email'] = $email;
            $_SESSION['client_name'] = $nombre;

            $hasQuotes = ($prevSubmissions['total_cotizaciones'] ?? 0) > 0;

            echo json_encode([
                'success' => true,
                'message' => $hasQuotes
                    ? 'Cuenta creada. Encontramos ' . $prevSubmissions['total_cotizaciones'] . ' cotización(es) vinculadas.'
                    : 'Cuenta creada exitosamente',
                'redirect' => '/client/dashboard.php',
                'linked_quotes' => $prevSubmissions['total_cotizaciones'] ?? 0
            ]);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}

/**
 * Vincula submissions y crea quotes para un cliente recién creado
 */
function linkPreviousSubmissions(PDO $pdo, string $email, int $clientId): void
{
    try {
        // Actualizar client_id en submissions
        $stmt = $pdo->prepare("UPDATE submissions SET client_id = ? WHERE email = ? AND client_id IS NULL");
        $stmt->execute([$clientId, $email]);

        // Obtener submissions para crear quotes
        $stmt = $pdo->prepare("SELECT id, referencia, origen, payload, created_at FROM submissions WHERE email = ?");
        $stmt->execute([$email]);
        $submissions = $stmt->fetchAll();

        $quoteService = new QuoteService($pdo);

        foreach ($submissions as $sub) {
            // Verificar si ya existe quote para esta submission
            $checkStmt = $pdo->prepare("SELECT id FROM quotes WHERE submission_id = ?");
            $checkStmt->execute([$sub['id']]);
            if ($checkStmt->fetch()) {
                continue; // Ya existe
            }

            $payload = json_decode($sub['payload'], true) ?: [];
            $pdfPath = dirname(__DIR__, 2) . '/storage/pdfs/' . $sub['referencia'] . '.pdf';

            // Usar QuoteService para crear el quote
            $quoteService->linkQuoteToClient(
                $email,
                $sub['referencia'],
                $sub['origen'],
                $payload,
                file_exists($pdfPath) ? $pdfPath : null
            );
        }

    } catch (Throwable $e) {
        error_log('[email-auth] Error vinculando submissions: ' . $e->getMessage());
    }
}

/**
 * Enmascara un email para mostrar parcialmente
 */
function maskEmail(string $email): string
{
    $parts = explode('@', $email);
    if (count($parts) !== 2) return '***@***';

    $local = $parts[0];
    $domain = $parts[1];

    $localMasked = substr($local, 0, 2) . str_repeat('*', max(strlen($local) - 2, 3));
    $domainParts = explode('.', $domain);
    $domainMasked = substr($domainParts[0], 0, 2) . '***.' . end($domainParts);

    return $localMasked . '@' . $domainMasked;
}
