<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/includes/db.php';
require_once __DIR__ . '/../../app/includes/auth.php';
require_once __DIR__ . '/../../app/middleware/require_login.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /CL_DEV/CampusLink/public/marketplace/index.php");
    exit;
}

$listingId = (int)($_POST['listing_id'] ?? 0);
$userId = currentUserId();

if ($listingId <= 0) {
    header("Location: /CL_DEV/CampusLink/public/marketplace/index.php?error=invalid_listing");
    exit;
}

// fetch the listing first so ownership can be confirmed before anything is removed
$stmt = $pdo->prepare("SELECT id, user_id, image_url FROM listings WHERE id = ?");
$stmt->execute([$listingId]);
$listing = $stmt->fetch();

if (!$listing || (int)$listing['user_id'] !== (int)$userId) {
    header("Location: /CL_DEV/CampusLink/public/marketplace/index.php?error=not_allowed");
    exit;
}

$stmt = $pdo->prepare("DELETE FROM listings WHERE id = ? AND user_id = ?");
$stmt->execute([$listingId, $userId]);

// clean up the item photo from disk so orphaned upload files don't pile up
if (!empty($listing['image_url'])) {
    $imagePath = __DIR__ . '/../uploads/post-images/' . basename($listing['image_url']);
    if (is_file($imagePath)) {
        unlink($imagePath);
    }
}

header("Location: /CL_DEV/CampusLink/public/marketplace/index.php?deleted=1");
exit;
