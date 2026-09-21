<?php
require_once __DIR__ . '/../../app/includes/db.php';
require_once __DIR__ . '/../../app/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $groupId = $_POST['group_id'] ?? null;
    $message = trim($_POST['message'] ?? '');
    $userId = $_SESSION['user_id'];

    if ($groupId && !empty($message)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM group_members WHERE group_id = ? AND user_id = ?");
            $stmt->execute([$groupId, $userId]);
            
            if (!$stmt->fetch()) {
                $joinStmt = $pdo->prepare("INSERT INTO group_members (group_id, user_id, role) VALUES (?, ?, 'member')");
                $joinStmt->execute([$groupId, $userId]);
            }
            $stmt = $pdo->prepare("INSERT INTO messages (chat_room_id, group_id, sender_id, message_text) VALUES (1, ?, ?, ?)");
            $stmt->execute([$groupId, $userId, $message]);
            
            echo json_encode(['status' => 'success']);
            exit;
            
        } catch (PDOException $e) {
            error_log("CampusLink Chat Database Error: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Database constraint barrier hit']);
            exit;
        }
    }
}
echo json_encode(['status' => 'error', 'message' => 'Invalid request payload']);
exit;
