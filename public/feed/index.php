<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/includes/auth.php';
require_once __DIR__ . '/../../app/middleware/require_login.php';

$user = currentUserId();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CampusLink - Feed</title>
</head>
<body style="font-family: Arial, sans-serif; padding: 40px;">

    <h1>Welcome to the Feed Dashboard, <?= htmlspecialchars($user['name'] ?? 'Student') ?>!</h1>
    <p>Status: Your session is securely connected.</p>

    <p style="margin-top: 30px;">
        <a href="<?= BASE_URL ?>/auth/logout.php" style="display: inline-block; padding: 10px 20px; background-color: #dc3545; color: white; text-decoration: none; border-radius: 4px; font-weight: bold;">
            Log Out
        </a>
    </p>

</body>
</html>