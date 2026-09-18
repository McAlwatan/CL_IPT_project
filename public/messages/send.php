<?php
require_once __DIR__ . '/../../app/includes/db.php';
require_once __DIR__ . '/../../app/includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $userId = (int)$_SESSION['user_id'];
    $recipientId = isset($_POST['recipient_id']) ? (int)$_POST['recipient_id'] : null;
    $message = trim($_POST['message'] ?? '');

    if ($recipientId && !empty($message)) {
        try {
            $pdo->beginTransaction();

            // 1. Look for an existing 'direct' chat room shared between these two users
            $stmt = $pdo->prepare("
                SELECT cr.id 
                FROM chat_rooms cr
                JOIN chat_members cm1 ON cr.id = cm1.chat_room_id AND cm1.user_id = ?
                JOIN chat_members cm2 ON cr.id = cm2.chat_room_id AND cm2.user_id = ?
                WHERE cr.type = 'direct'
                LIMIT 1
            ");
            $stmt->execute([$userId, $recipientId]);
            $room = $stmt->fetch();

            if ($room) {
                $roomId = (int)$room['id'];
            } else {
                // 2. If no room exists, create a new direct chat room record dynamically
                $stmt = $pdo->prepare("INSERT INTO chat_rooms (type) VALUES ('direct')");
                $stmt->execute();
                $roomId = (int)$pdo->lastInsertId();

                // Register both participants into the chat room members lookup table
                $stmt = $pdo->prepare("INSERT INTO chat_members (chat_room_id, user_id) VALUES (?, ?), (?, ?)");
                $stmt->execute([$roomId, $userId, $roomId, $recipientId]);
            }

            // 3. Insert the private message linked safely to the resolved chat_room_id
            // group_id is explicitly set to NULL to cleanly separate this from public channels
            $stmt = $pdo->prepare("
                INSERT INTO messages (chat_room_id, group_id, sender_id, message_text) 
                VALUES (?, NULL, ?, ?)
            ");
            $stmt->execute([$roomId, $userId, $message]);

            $pdo->commit();
            echo json_encode(['status' => 'success']);
            exit;

        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("CampusLink Direct Message Insert Crash: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Database constraint barrier hit']);
            exit;
        }
    }
}
echo json_encode(['status' => 'error', 'message' => 'Invalid request payload']);
exit;
