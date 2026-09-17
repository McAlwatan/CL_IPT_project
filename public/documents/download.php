<?php
require_once __DIR__ . '/../../app/includes/db.php';
require_once __DIR__ . '/../../app/includes/auth.php';

// Stop unauthenticated visitors from running direct file streams
if (!isset($_SESSION['user_id'])) {
    die("Authorization denied.");
}

$id = $_GET['id'] ?? null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM documents WHERE id = ?");
    $stmt->execute([$id]);
    $doc = $stmt->fetch();

    if ($doc) {
        $filePath = __DIR__ . '/../uploads/documents/' . $doc['file_path'];

        if (file_exists($filePath)) {
            // Force strict browser download disposition attachments streams
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($doc['title'] . '.' . pathinfo($doc['file_path'], PATHINFO_EXTENSION)) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filePath));
            
            flush(); // Clear system output buffers
            readfile($filePath);
            exit;
        }
    }
}

die("File does not exist or access execution failed.");
