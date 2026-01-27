<?php
/**
 * OtpService - Servicio para generación y validación de OTP
 *
 * Genera códigos de 6 dígitos, los almacena en sesión con tiempo de expiración,
 * y los envía por email usando PHPMailer.
 */

class OtpService
{
    private const OTP_LENGTH = 6;
    private const OTP_EXPIRY_MINUTES = 10;

    private ?PDO $pdo;
    private array $mailConfig;

    public function __construct(?PDO $pdo = null, array $mailConfig = [])
    {
        $this->pdo = $pdo;
        $this->mailConfig = $mailConfig;
    }

    /**
     * Genera un nuevo OTP para un email
     *
     * @param string $email
     * @return string El código OTP generado
     */
    public function generateOtp(string $email): string
    {
        // Generar código de 6 dígitos
        $otp = str_pad((string) random_int(0, 999999), self::OTP_LENGTH, '0', STR_PAD_LEFT);

        // Guardar en sesión
        $_SESSION['otp_data'] = [
            'code' => password_hash($otp, PASSWORD_DEFAULT),
            'email' => strtolower(trim($email)),
            'expires_at' => time() + (self::OTP_EXPIRY_MINUTES * 60),
            'attempts' => 0
        ];

        return $otp;
    }

    /**
     * Verifica si un OTP es válido
     *
     * @param string $email
     * @param string $code
     * @return array ['valid' => bool, 'message' => string]
     */
    public function verifyOtp(string $email, string $code): array
    {
        $email = strtolower(trim($email));

        // Verificar que existe OTP en sesión
        if (empty($_SESSION['otp_data'])) {
            return ['valid' => false, 'message' => 'No hay código pendiente. Solicita uno nuevo.'];
        }

        $otpData = $_SESSION['otp_data'];

        // Verificar email
        if ($otpData['email'] !== $email) {
            return ['valid' => false, 'message' => 'El email no coincide.'];
        }

        // Verificar expiración
        if (time() > $otpData['expires_at']) {
            unset($_SESSION['otp_data']);
            return ['valid' => false, 'message' => 'El código ha expirado. Solicita uno nuevo.'];
        }

        // Verificar intentos (máximo 5)
        if ($otpData['attempts'] >= 5) {
            unset($_SESSION['otp_data']);
            return ['valid' => false, 'message' => 'Demasiados intentos fallidos. Solicita un código nuevo.'];
        }

        // Incrementar intentos
        $_SESSION['otp_data']['attempts']++;

        // Verificar código
        if (!password_verify($code, $otpData['code'])) {
            $remaining = 5 - $_SESSION['otp_data']['attempts'];
            return ['valid' => false, 'message' => "Código incorrecto. Te quedan $remaining intentos."];
        }

        // Código válido - limpiar sesión
        unset($_SESSION['otp_data']);

        return ['valid' => true, 'message' => 'Código verificado correctamente.'];
    }

    /**
     * Envía el OTP por email
     *
     * @param string $email
     * @param string $otp
     * @param string $nombre Nombre del destinatario (opcional)
     * @return bool
     */
    public function sendOtpEmail(string $email, string $otp, string $nombre = ''): bool
    {
        try {
            // Cargar PHPMailer
            $this->loadMailer();

            if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer') && !class_exists('PHPMailer')) {
                error_log('[OtpService] PHPMailer no disponible');
                return false;
            }

            if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            } else {
                $mail = new \PHPMailer(true);
            }

            // Configuración SMTP
            $mail->isSMTP();
            $mail->Host = $this->mailConfig['host'] ?? '';
            $mail->Port = (int) ($this->mailConfig['port'] ?? 587);
            $mail->SMTPAuth = true;
            $mail->SMTPSecure = 'tls';
            $mail->Username = $this->mailConfig['user'] ?? '';
            $mail->Password = $this->mailConfig['pass'] ?? '';
            $mail->CharSet = 'UTF-8';

            // Remitente y destinatario
            $fromEmail = $this->mailConfig['from'][0] ?? 'noreply@aseguralocr.com';
            $fromName = $this->mailConfig['from'][1] ?? 'AseguraloCR';
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($email, $nombre);

            // Contenido
            $mail->isHTML(true);
            $mail->Subject = "Tu código de acceso - AseguraloCR";

            $saludo = $nombre ? "Hola $nombre," : "Hola,";

            $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 500px; margin: 0 auto; padding: 20px;'>
                <div style='text-align: center; margin-bottom: 30px;'>
                    <h1 style='color: #667eea; margin: 0;'>AseguraloCR</h1>
                    <p style='color: #666; margin: 5px 0;'>Portal de Clientes</p>
                </div>

                <p style='color: #333;'>$saludo</p>

                <p style='color: #333;'>Tu código de verificación es:</p>

                <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; padding: 25px; text-align: center; margin: 20px 0;'>
                    <span style='font-size: 36px; font-weight: bold; color: white; letter-spacing: 8px;'>$otp</span>
                </div>

                <p style='color: #666; font-size: 14px;'>
                    Este código expira en " . self::OTP_EXPIRY_MINUTES . " minutos.<br>
                    Si no solicitaste este código, puedes ignorar este mensaje.
                </p>

                <hr style='border: none; border-top: 1px solid #eee; margin: 30px 0;'>

                <p style='color: #999; font-size: 12px; text-align: center;'>
                    AseguraloCR - Tu socio en seguros<br>
                    <a href='https://www.aseguralocr.com' style='color: #667eea;'>www.aseguralocr.com</a>
                </p>
            </div>
            ";

            $mail->AltBody = "$saludo\n\nTu código de verificación es: $otp\n\nEste código expira en " . self::OTP_EXPIRY_MINUTES . " minutos.\n\nAseguraloCR";

            return $mail->send();

        } catch (Throwable $e) {
            error_log('[OtpService] Error enviando email: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Busca datos de cotizaciones previas por email
     *
     * @param string $email
     * @return array|null Datos del cliente de submissions o null
     */
    public function findPreviousSubmissions(string $email): ?array
    {
        if (!$this->pdo) {
            return null;
        }

        try {
            $stmt = $this->pdo->prepare("
                SELECT
                    COUNT(*) as total_cotizaciones,
                    MAX(created_at) as ultima_cotizacion,
                    MAX(JSON_UNQUOTE(JSON_EXTRACT(payload, '$.nombreCompleto'))) as nombre,
                    MAX(JSON_UNQUOTE(JSON_EXTRACT(payload, '$.razonSocial'))) as razon_social,
                    MAX(JSON_UNQUOTE(JSON_EXTRACT(payload, '$.telefonoCelular'))) as telefono,
                    MAX(JSON_UNQUOTE(JSON_EXTRACT(payload, '$.telefonoPrincipal'))) as telefono_alt
                FROM submissions
                WHERE email = ?
                GROUP BY email
            ");
            $stmt->execute([strtolower(trim($email))]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result && $result['total_cotizaciones'] > 0) {
                // Determinar el nombre (puede ser nombreCompleto o razonSocial)
                $nombre = $result['nombre'] ?: $result['razon_social'] ?: '';
                $telefono = $result['telefono'] ?: $result['telefono_alt'] ?: '';

                return [
                    'total_cotizaciones' => (int) $result['total_cotizaciones'],
                    'ultima_cotizacion' => $result['ultima_cotizacion'],
                    'nombre' => $nombre,
                    'telefono' => $telefono
                ];
            }

            return null;

        } catch (Throwable $e) {
            error_log('[OtpService] Error buscando submissions: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Carga PHPMailer
     */
    private function loadMailer(): void
    {
        $autoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
        if (file_exists($autoload)) {
            require_once $autoload;
            return;
        }

        $candidates = [
            dirname(__DIR__, 2) . '/vendor/phpmailer/phpmailer/src',
            dirname(__DIR__, 2) . '/vendor/phpmailer/src',
        ];

        foreach ($candidates as $base) {
            $f1 = $base . '/Exception.php';
            $f2 = $base . '/PHPMailer.php';
            $f3 = $base . '/SMTP.php';
            if (file_exists($f2)) {
                if (file_exists($f1)) require_once $f1;
                require_once $f2;
                if (file_exists($f3)) require_once $f3;
                break;
            }
        }
    }
}
