<?php
require_once __DIR__ . '/../../app/includes/db.php';
require_once __DIR__ . '/../../app/includes/auth.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$userId = $_SESSION['user_id'];
$partnerId = $_GET['partner_id'] ?? null;

if ($partnerId) {
    // Select messages exchanged specifically between you and your partner
    // We filter rows where group_id IS NULL to isolate DMs from public channels
    $stmt = $pdo->prepare("
        SELECT * FROM messages 
        WHERE group_id IS NULL 
          AND ((sender_id = ? AND chat_room_id = ?) OR (sender_id = ? AND chat_room_id = ?))
        ORDER BY created_at ASC
    ");
    $stmt->execute([$userId, $partnerId, $partnerId, $userId]);
    $rows = $stmt->fetchAll();

    $cleanDMs = [];
    foreach ($rows as $row) {
        $cleanDMs[] = [
            'is_me' => ((int)$row['sender_id'] === (int)$userId),
            'text' => htmlspecialchars($row['message_text']),
            'time' => date('h:i A', strtotime($row['created_at']))
        ];
    }
    echo json_encode($cleanDMs);
    exit;
}
echo json_encode([]);
