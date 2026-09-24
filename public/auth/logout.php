<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/includes/auth.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$_SESSION = []; // clearing

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
} // browser cookie clearing

session_destroy();

header("Location: /CL_DEV/CampusLink/public/auth/login.php");
exit;
