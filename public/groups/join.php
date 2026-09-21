<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/includes/db.php';
require_once __DIR__ . '/../../app/includes/auth.php';
require_once __DIR__ . '/../../app/middleware/require_login.php';

$groupId = $_GET['id'] ?? null;
$userId = $_SESSION['user_id'];

if ($groupId) {
    $stmt = $pdo->prepare("SELECT id FROM group_members WHERE group_id = ? AND user_id = ?");
    $stmt->execute([$groupId, $userId]);
    
    if (!$stmt->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO group_members (group_id, user_id, role) VALUES (?, ?, 'member')");
        $stmt->execute([$groupId, $userId]);
    }
    
    header("Location: /IPT_WEB_PROJECT/CampusLink/public/groups/view.php?id=" . $groupId);
    exit;
}

header("Location: /IPT_WEB_PROJECT/CampusLink/public/groups/index.php");
exit;
