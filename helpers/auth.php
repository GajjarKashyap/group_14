<?php
// helpers/auth.php - Session Authentication & Security Helpers
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_user_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

function get_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    if (empty($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        die("Security Check Failed: Invalid CSRF Token.");
    }
}
?>
