<?php
require_once __DIR__ . '/../../app/includes/db.php';
require_once __DIR__ . '/../../app/includes/auth.php';
require_once __DIR__ . '/../../app/middleware/require_login.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /CL_DEV/CampusLink/public/feed/index.php");
    exit;
}

$content = trim($_POST['content'] ?? '');
$userId = currentUserId();

if ($content === '') {
    header("Location: /CL_DEV/CampusLink/public/feed/index.php?error=empty_post");
    exit;
}

$stmt = $pdo->prepare("INSERT INTO posts (user_id, content) VALUES (?, ?)");
$stmt->execute([$userId, $content]);

header("Location: /CL_DEV/CampusLink/public/feed/index.php");
exit;