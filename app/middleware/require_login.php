<?php
require_once __DIR__ . '/../includes/auth.php';

// tunacheck kama user yuko logged in, kama hayuko logged in then itamredirect kwenda kwenye login page alogin
if(!isLoggedIn()){
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

if (isLoggedIn() && empty($_SESSION['user']['university_id'])) {
    // Only execute if they aren't already currently sitting on the onboarding script itself
    if (basename($_SERVER['PHP_SELF']) !== 'onboarding.php') {
        header("Location: /IPT_WEB_PROJECT/CampusLink/public/onboarding.php");
        exit;
    }
}