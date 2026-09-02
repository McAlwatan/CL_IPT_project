<?php
require_once __DIR__ . '/../includes/auth.php';

// tunacheck kama user yuko logged in, kama hayuko logged in then itamredirect kwenda kwenye login page alogin
if(!isLoggedIn()){
    header('Location: /login.php');
    exit;
}