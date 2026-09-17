<?php
$activePage = 'groups';
$pageTitle = 'Community Space';
require_once __DIR__ . '/../../app/includes/dashboard_head.php';

$groupId = $_GET['id'] ?? null;
$userId = $_SESSION['user_id'];

if (!$groupId) {
    header("Location: /IPT_WEB_PROJECT/CampusLink/public/groups/index.php");
    exit;
}

// 1. Fetch exact structural group data matching URL parameter ID
$stmt = $pdo->prepare("
    SELECT g.*, u.name as creator_name,
           (SELECT role FROM group_members WHERE group_id = g.id AND user_id = ?) as user_role
    FROM groups g
    JOIN users u ON g.creator_id = u.id
    WHERE g.id = ?
");
$stmt->execute([$userId, $groupId]);
$group = $stmt->fetch();

if (!$group) {
    die("<div class='feed-empty'><p>Group not found.</p></div>");
}

// 2. Fetch all community announcements compiled inside this specific platform room
$stmt = $pdo->prepare("
    SELECT a.*, u.name as sender_name 
    FROM announcements a
    JOIN users u ON a.user_id = u.id
    WHERE a.group_id = ?
    ORDER BY a.created_at DESC
");
$stmt->execute([$groupId]);
$announcements = $stmt->fetchAll();

// Determine if the logged-in student has rights to post announcements
$canPost = ($group['is_open_posting'] == 1 && $group['user_role'] !== null) || $group['user_role'] === 'admin';
?>

<div style="margin-bottom: 32px;">
    <a href="/IPT_WEB_PROJECT/CampusLink/public/groups/index.php" style="color: #a0a0a0; text-decoration: none; font-size: 14px;">← Back to Hub</a>
</div>


<div class="group-header-block" style="border-bottom: 1px solid #3d3d3d; padding-bottom: 32px; margin-bottom: 40px;">
    <h1 style="color: #ffffff; font-size: 32px; margin-bottom: 8px;"><?= htmlspecialchars($group['name']) ?></h1>
    <p style="color: #a0a0a0; font-size: 16px; line-height: 1.6; max-width: 700px;"><?= htmlspecialchars($group['description']) ?></p>
    <span style="display: inline-block; background-color: #2a2a2a; color: #757575; font-size: 12px; padding: 6px 12px; border-radius: 4px; margin-top: 12px;">
        Owner: <?= htmlspecialchars($group['creator_name']) ?>
    </span>
</div>

<div style="margin-top: 16px;">
    <a href="/IPT_WEB_PROJECT/CampusLink/public/groups/chat.php?group_id=<?= $group['id'] ?>" 
       style="display: inline-block; background-color: #2563eb; color: white; text-decoration: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 14px;">
       💬 Enter Live Group Chat Room
    </a>
</div>


<?php if ($canPost): ?>
    <form action="/IPT_WEB_PROJECT/CampusLink/public/groups/post.php" method="POST" class="feed-composer">
        <input type="hidden" name="group_id" value="<?= $group['id'] ?>">
        <span class="topbar-avatar">📣</span>
        <div style="flex: 1; display: flex; flex-direction: column; gap: 16px;">
            <textarea name="content" placeholder="Broadcast an announcement to this group room stream..." required></textarea>
            <button type="submit" class="feed-submit-btn" style="align-self: flex-end;">Broadcast</button>
        </div>
    </form>
<?php endif; ?>

<!-- Internal Group Board Timeline Feed Stream Layout -->
<div class="announcements-timeline" style="display: flex; flex-direction: column; gap: 24px;">
    <h3 style="color: #ffffff; border-bottom: 1px solid #2a2a2a; padding-bottom: 12px;">Notice Board</h3>
    
    <?php if (empty($announcements)): ?>
        <div class="feed-empty">
            <p>No active announcements broadcasted here yet.</p>
        </div>
    <?php else: ?>
        <?php foreach ($announcements as $ann): ?>
            <div class="post-card" style="background-color: #2a2a2a; border: 1px solid #3d3d3d; border-radius: 16px; padding: 24px;">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                    <span class="avatar-sm" style="width: 32px; height: 32px; background: #ffffff; color: #121212; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-weight: 600; font-size: 14px;">
                        <?= htmlspecialchars(strtoupper(substr($ann['sender_name'], 0, 1))) ?>
                    </span>
                    <div>
                        <strong style="color: #ffffff; display: block; font-size: 15px;"><?= htmlspecialchars($ann['sender_name']) ?></strong>
                        <span style="color: #757575; font-size: 12px;"><?= date('M d, Y • h:i A', strtotime($ann['created_at'])) ?></span>
                    </div>
                </div>
                <p style="color: #e0e0e0; font-size: 16px; line-height: 1.5; white-space: pre-wrap;"><?= htmlspecialchars($ann['content']) ?></p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../app/includes/dashboard_foot.php'; ?>
