<?php
require_once __DIR__ . '/../../app/includes/db.php';
require_once __DIR__ . '/../../app/includes/auth.php';

// forcing clean JSON headers so the JavaScript fetch() command can decode it instantly
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$groupId = $_GET['group_id'] ?? null;
$userId = $_SESSION['user_id'];

if ($groupId) {
    $stmt = $pdo->prepare("
        SELECT m.*, u.name as sender_name 
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE m.group_id = ?
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$groupId]);
    $rows = $stmt->fetchAll();

    $cleanMessages = [];
    foreach ($rows as $row) {
        $cleanMessages[] = [
            'is_me' => ((int)$row['sender_id'] === (int)$userId),
            'sender_name' => htmlspecialchars($row['sender_name']),
            'text' => htmlspecialchars($row['message_text']), // mapping directly to msg.text in JS
            'time' => date('h:i A', strtotime($row['created_at']))
        ];
    }
    
    echo json_encode($cleanMessages);
    exit;
}

echo json_encode([]); // fallback
exit;
