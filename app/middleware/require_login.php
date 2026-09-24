<?php
require_once __DIR__ . '/../includes/auth.php';

// tunacheck kama user yuko logged in, kama hayuko logged in then itamredirect kwenda kwenye login page alogin
if(!isLoggedIn()){
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

if (isLoggedIn()) {
    // If the tracking session variable is missing or empty, verify the live DB state before detouring
    if (empty($_SESSION['user']['university_id'])) {
        
        // Double-check the actual database columns mapping directly
        $guardStmt = $pdo->prepare("SELECT university_id FROM users WHERE id = ?");
        $guardStmt->execute([$_SESSION['user_id']]);
        $liveUser = $guardStmt->fetch();
        
        if ($liveUser && !empty($liveUser['university_id'])) {
            // Keep the active session array updated dynamically
            $_SESSION['user']['university_id'] = $liveUser['university_id'];
        } else {
            // Detour ONLY if they are truly missing academic metadata entries on disk
            if (basename($_SERVER['PHP_SELF']) !== 'onboarding.php') {
                header("Location: /CL_DEV/CampusLink/public/onboarding.php");
                exit;
            }
        }
    }
}
