<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../middleware/require_login.php';

$userId = currentUserId();
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$currentUser = $stmt->fetch();

$activePage = $activePage ?? '';
$pageTitle = $pageTitle ?? 'CampusLink';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <script src="https://kit.fontawesome.com/387650f35b.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/IPT_WEB_PROJECT/CampusLink/public/css/dashboard.css">
    <link rel="stylesheet" href="/IPT_WEB_PROJECT/Campuslink/public/css/profileStyle.css">
    <link rel="stylesheet" href="/IPT_WEB_PROJECT/Campuslink/public/css/settingsStyle.css">
    <link rel="stylesheet" href="/IPT_WEB_PROJECT/Campuslink/public/css/marketPlaceStyle.css">
    <link rel="stylesheet" href="/IPT_WEB_PROJECT/Campuslink/public/css/documentStyle.css">
    <link rel="stylesheet" href="/IPT_WEB_PROJECT/Campuslink/public/css/skillsStyles.css">
    <link rel="stylesheet" href="/IPT_WEB_PROJECT/Campuslink/public/css/topSideStyle.css">
    <link rel="stylesheet" href="/IPT_WEB_PROJECT/Campuslink/public/css/messagesStyle.css">
    <link rel="icon" type="image/icon" href="../../public/images/favicon.png">
    <title><?= htmlspecialchars($pageTitle) ?> | CampusLink</title>
</head>
<body>
    <div class="app-shell">
        <?php require_once __DIR__ . '/sidebar.php'; ?>

        <div class="app-main">
            <?php require_once __DIR__ . '/topbar.php'; ?>

            <main class="app-content">