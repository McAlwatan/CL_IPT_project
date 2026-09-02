<?php
require_once __DIR__ . '/../../app/includes/db.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    die("Invalid verification link.");
}

$stmt = $pdo->prepare("SELECT id FROM users WHERE verification_token = ? AND is_active = 0");
$stmt->execute([$token]);
$user = $stmt->fetch();

if ($user) {
    $update = $pdo->prepare("UPDATE users SET is_active = 1, verification_token = NULL WHERE id = ?");
    $update->execute([$user['id']]);
    echo "Account verified! You can now <a href='login.php'>Login</a>.";
} else {
    echo "Invalid or expired verification token.";
}
