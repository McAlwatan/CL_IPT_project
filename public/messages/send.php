<?php
require_once __DIR__ . '/../../app/includes/db.php';
require_once __DIR__ . '/../../app/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $recipientId = $_POST['recipient_id'] ?? null;
    $message = trim($_POST['message'] ?? '');

    if ($recipientId && !empty($message)) {
        // Reuse chat_room_id column field parameters to anchor the target recipient user ID
        $stmt = $pdo->prepare("INSERT INTO messages (chat_room_id, group_id, sender_id, message_text) VALUES (?, NULL, ?, ?)");
        $stmt->execute([$recipientId, $userId, $message]);
        echo "success";
        exit;
    }
}
echo "error";
