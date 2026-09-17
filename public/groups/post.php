<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/includes/db.php';
require_once __DIR__ . '/../../app/includes/auth.php';
require_once __DIR__ . '/../../app/middleware/require_login.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $groupId = $_POST['group_id'] ?? null;
    $content = trim($_POST['content'] ?? '');
    $userId = $_SESSION['user_id'];

    if ($groupId && !empty($content)) {
        // Enforce membership verification checklist before executing insert definitions
        $stmt = $pdo->prepare("
            SELECT g.is_open_posting, gm.role 
            FROM groups g
            LEFT JOIN group_members gm ON g.id = gm.group_id AND gm.user_id = ?
            WHERE g.id = ?
        ");
        $stmt->execute([$userId, $groupId]);
        $perms = $stmt->fetch();

        $canPost = ($perms && $perms['is_open_posting'] == 1 && $perms['role'] !== null) || ($perms && $perms['role'] === 'admin');

        if ($canPost) {
            $stmt = $pdo->prepare("INSERT INTO announcements (group_id, user_id, content) VALUES (?, ?, ?)");
            $stmt->execute([$groupId, $userId, $content]);
        }
    }
    
    header("Location: /IPT_WEB_PROJECT/CampusLink/public/groups/view.php?id=" . $groupId);
    exit;
}

header("Location: /IPT_WEB_PROJECT/CampusLink/public/groups/index.php");
exit;
