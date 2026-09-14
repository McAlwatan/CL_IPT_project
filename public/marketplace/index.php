<?php
$activePage = 'feed';
$pageTitle = 'Feed';
require_once __DIR__ . '/../../app/includes/dashboard_head.php';
?>

<div class="page-header">
    <h1>Marketplace</h1>
    <p>See what's happening across your campus.</p>
</div>

<div class="feed-composer">
    <span class="topbar-avatar">
        <?= htmlspecialchars(strtoupper(substr($currentUser['name'] ?? 'S', 0, 1))) ?>
    </span>
    <textarea placeholder="Share something with campus..."></textarea>
</div>

<div class="feed-empty">
    
</div>

<?php require_once __DIR__ . '/../../app/includes/dashboard_foot.php'; ?>