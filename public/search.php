<?php
// Global search endpoint backing the topbar search box.
// Place this at /IPT_WEB_PROJECT/CampusLink/public/search.php — the topbar's
// JS calls it at that exact absolute path.
require_once __DIR__ . '/../app/includes/db.php';
require_once __DIR__ . '/../app/includes/auth.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['students' => [], 'groups' => [], 'documents' => []]);
    exit;
}

$query = trim($_GET['q'] ?? '');

if (mb_strlen($query) < 2) {
    echo json_encode(['students' => [], 'groups' => [], 'documents' => []]);
    exit;
}

$like = '%' . $query . '%';
$userId = $_SESSION['user_id'];

// Students (exclude the logged-in user from their own search results)
$stmt = $pdo->prepare("SELECT id, name FROM users WHERE name LIKE ? AND id != ? ORDER BY name ASC LIMIT 5");
$stmt->execute([$like, $userId]);
$students = $stmt->fetchAll();

// Groups
$stmt = $pdo->prepare("SELECT id, name FROM groups WHERE name LIKE ? ORDER BY name ASC LIMIT 5");
$stmt->execute([$like]);
$groups = $stmt->fetchAll();

// Documents (match on title or course code)
$stmt = $pdo->prepare("
    SELECT id, title, course_code
    FROM documents
    WHERE title LIKE ? OR course_code LIKE ?
    ORDER BY created_at DESC
    LIMIT 5
");
$stmt->execute([$like, $like]);
$documents = $stmt->fetchAll();

echo json_encode([
    'students'  => $students,
    'groups'    => $groups,
    'documents' => $documents,
]);
exit;