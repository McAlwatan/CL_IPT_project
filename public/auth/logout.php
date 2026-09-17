<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/includes/auth.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// 1. Clear session variables out of server memory
$_SESSION = [];

// 2. Clear the actual session cookie out of the browser memory
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Destroy the session on the server completely
session_destroy();

header("Location: /IPT_WEB_PROJECT/CampusLink/public/auth/login.php");
exit;
