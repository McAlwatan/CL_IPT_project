<?php
require_once __DIR__ . '/../../app/includes/auth.php';

logoutUser();
header("Location: /auth/login.php");
exit;
