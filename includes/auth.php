<?php
// includes/auth.php

// Configurar sesión de forma consistente
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',  // Mismo dominio
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

function is_admin_logged_in() {
    return !empty($_SESSION['admin_logged']) && $_SESSION['admin_logged'] === true;
}

function require_admin() {
    if (!is_admin_logged_in()) {
        header('Location: /admin/login.php');
        exit;
    }
}