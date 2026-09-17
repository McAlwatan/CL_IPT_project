<?php
require_once __DIR__ . '/../../app/includes/db.php';
require_once __DIR__ . '/../../app/includes/auth.php';

// Force clean JSON headers so the JavaScript fetch() command can decode it instantly
header('Content-Type: application/json');

// Stop unauthenticated guests from hitting this API endpoint script
if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$groupId = $_GET['group_id'] ?? null;
$userId = $_SESSION['user_id'];

if ($groupId) {
    // Fetch group chat entries along with the sender's readable name text tag
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
            // Evaluates true if the logged-in student sent the message row block
            'is_me' => ((int)$row['sender_id'] === (int)$userId),
            'sender_name' => htmlspecialchars($row['sender_name']),
            'text' => htmlspecialchars($row['message_text']), // Maps directly to msg.text in JS
            'time' => date('h:i A', strtotime($row['created_at']))
        ];
    }
    
    echo json_encode($cleanMessages);
    exit;
}

// Fallback empty array array if no group ID parameter was passed
echo json_encode([]);
exit;
