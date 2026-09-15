<?php
require_once __DIR__ . '/../../app/includes/db.php';
require_once __DIR__ . '/../../app/includes/auth.php';
require_once __DIR__ . '/../../app/middleware/require_login.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /IPT_WEB_PROJECT/CampusLink/public/feed/index.php");
    exit;
}

$postId = (int)($_POST['post_id'] ?? 0);
$content = trim($_POST['content'] ?? '');
$userId = currentUserId();

if ($postId <= 0 || $content === '') {
    header("Location: /IPT_WEB_PROJECT/CampusLink/public/feed/index.php?error=empty_comment#post-$postId");
    exit;
}

$stmt = $pdo->prepare("INSERT INTO post_comments (post_id, user_id, comment) VALUES (?, ?, ?)");
$stmt->execute([$postId, $userId, $content]);

header("Location: /IPT_WEB_PROJECT/CampusLink/public/feed/index.php#post-$postId");
exit;