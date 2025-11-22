<?php
// includes/auth.php
session_start();

function is_admin_logged_in() {
    return !empty($_SESSION['admin_logged']) && $_SESSION['admin_logged'] === true;
}

function require_admin() {
    if (!is_admin_logged_in()) {
        header('Location: /admin/login.php');
        exit;
    }
}