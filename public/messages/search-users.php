<?php
require_once __DIR__ . '/../../app/includes/db.php';
require_once __DIR__ . '/../../app/includes/auth.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$currentUserId = $_SESSION['user_id'];
$query = trim($_GET['q'] ?? '');

if (strlen($query) >= 2) {
    // Look up students whose name matches the input, excluding the active user
    $stmt = $pdo->prepare("SELECT id, name FROM users WHERE id != ? AND name LIKE ? ORDER BY name ASC LIMIT 8");
    $stmt->execute([$currentUserId, "%" . $query . "%"]);
    $users = $stmt->fetchAll();

    echo json_encode($users);
    exit;
}

echo json_encode([]);
exit;
