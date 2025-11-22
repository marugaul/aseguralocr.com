<?php
/**
 * Client Authentication Middleware
 * Ensures client is logged in before accessing dashboard pages
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_client_logged_in(): bool {
    return !empty($_SESSION['client_logged_in']) && !empty($_SESSION['client_id']);
}

function require_client_login(): void {
    if (!is_client_logged_in()) {
        header('Location: /client/login.php');
        exit;
    }
}

function get_current_client_id(): ?int {
    return $_SESSION['client_id'] ?? null;
}

function get_client_data(): array {
    if (!is_client_logged_in()) {
        return [];
    }

    require_once __DIR__ . '/../../includes/db.php';
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
    $stmt->execute([$_SESSION['client_id']]);
    return $stmt->fetch() ?: [];
}

function client_logout(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    header('Location: /client/login.php');
    exit;
}
