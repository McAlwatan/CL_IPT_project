<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/includes/auth.php';

logoutUser();
header("Location:" . BASE_URL . " /auth/login.php");
exit;
