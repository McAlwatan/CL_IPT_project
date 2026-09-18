<?php
require_once __DIR__ . '/../../app/includes/db.php';
require_once __DIR__ . '/../../app/includes/auth.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$partnerId = isset($_GET['partner_id']) ? (int)$_GET['partner_id'] : null;

if ($partnerId) {
    // Locate the unique direct room shared between the active user and the partner
    $stmt = $pdo->prepare("
        SELECT cr.id 
        FROM chat_rooms cr
        JOIN chat_members cm1 ON cr.id = cm1.chat_room_id AND cm1.user_id = ?
        JOIN chat_members cm2 ON cr.id = cm2.chat_room_id AND cm2.user_id = ?
        WHERE cr.type = 'direct'
        LIMIT 1
    ");
    $stmt->execute([$userId, $partnerId]);
    $room = $stmt->fetch();

    if ($room) {
        // Pull all messages belonging strictly to this shared room id
        $stmt = $pdo->prepare("
            SELECT * FROM messages 
            WHERE chat_room_id = ? AND group_id IS NULL 
            ORDER BY created_at ASC
        ");
        $stmt->execute([$room['id']]);
        $rows = $stmt->fetchAll();

        $cleanDMs = [];
        foreach ($rows as $row) {
            $cleanDMs[] = [
                'is_me' => ((int)$row['sender_id'] === $userId),
                'text' => htmlspecialchars($row['message_text']),
                'time' => date('h:i A', strtotime($row['created_at']))
            ];
        }
        echo json_encode($cleanDMs);
        exit;
    }
}

echo json_encode([]);
exit;
