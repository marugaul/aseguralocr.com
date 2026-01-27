<?php
class Security {
  public static function start(): void {
    if (session_status() === PHP_SESSION_NONE) {
      // Configuración segura de sesiones
      session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax'
      ]);
      session_start();
    }
  }

  public static function csrfToken(): string {
    self::start();
    if (empty($_SESSION['csrf'])) {
      $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
  }

  public static function validateCsrf(?string $t): bool {
    self::start();
    return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $t ?? '');
  }

  public static function checkRateLimit(int $ms = 3000): bool {
    self::start();
    $now = (int)(microtime(true) * 1000);
    $last = $_SESSION['last_submit'] ?? 0;
    if (($now - $last) < $ms) return false;
    $_SESSION['last_submit'] = $now;
    return true;
  }

  public static function sanitize(string $s, int $max = 2000): string {
    $s = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $s);
    $s = preg_replace('/<iframe[^>]*>.*?<\/iframe>/is', '', $s);
    $s = preg_replace('/javascript:/i', '', $s);
    $s = preg_replace('/on\w+\s*=/i', '', $s);
    return mb_substr(trim($s), 0, $max);
  }

  public static function requireHttps(): void {
    // Si quieres forzar HTTPS, habilita este bloque:
    // if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
    //   header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], true, 301);
    //   exit;
    // }
  }
}
