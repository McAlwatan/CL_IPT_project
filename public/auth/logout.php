<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/includes/auth.php';

logoutUser();
header("Location: /IPT_WEB_PROJECT/CampusLink/public/auth/login.php");
exit;
