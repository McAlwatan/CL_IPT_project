<?php
$activePage = 'groups';
$pageTitle = 'Community Space';
require_once __DIR__ . '/../../app/includes/dashboard_head.php';

$groupId = $_GET['id'] ?? null;
$userId = $_SESSION['user_id'];

if (!$groupId) {
    header("Location: /CL_DEV/CampusLink/public/groups/index.php");
    exit;
}

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

$stmt = $pdo->prepare("
    SELECT a.*, u.name as sender_name 
    FROM announcements a
    JOIN users u ON a.user_id = u.id
    WHERE a.group_id = ?
    ORDER BY a.created_at DESC
");
$stmt->execute([$groupId]);
$announcements = $stmt->fetchAll();

$canPost = ($group['is_open_posting'] == 1 && $group['user_role'] !== null) || $group['user_role'] === 'admin';

function clInitial($name) {
    $name = trim((string)$name);
    if ($name === '') return 'S';
    $parts = preg_split('/\s+/', $name);
    $first = mb_substr($parts[0], 0, 1);
    $second = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';
    return htmlspecialchars(mb_strtoupper($first . $second));
}
function clAvatarClass($seed) {
    $palette = ['rust', 'ink-blue', 'moss', 'plum'];
    return 'avatar-' . $palette[crc32((string)$seed) % count($palette)];
}
function clTimeAgo($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    return floor($diff / 86400) . 'd ago';
}
?>

<a href="/CL_DEV/CampusLink/public/groups/index.php" class="clg-back">← Back to groups</a>

<div class="group-header-block">
    <h1><?= htmlspecialchars($group['name']) ?></h1>
    <p><?= htmlspecialchars($group['description']) ?></p>
    <span class="owner-badge">Owner: <?= htmlspecialchars($group['creator_name']) ?></span>
</div>

<a href="/CL_DEV/CampusLink/public/groups/chat.php?group_id=<?= $group['id'] ?>" class="btn-primary"><i class="fa-regular fa-comment"></i>  Enter live chat</a>

<?php if ($canPost): ?>
    <form action="/CL_DEV/CampusLink/public/groups/post.php" method="POST" class="feed-composer">
        <input type="hidden" name="group_id" value="<?= $group['id'] ?>">
        <span class="clf-avatar <?= clAvatarClass($currentUser['name'] ?? 'S') ?>"><?= clInitial($currentUser['name'] ?? 'S') ?></span>
        <div class="feed-composer-body">
            <textarea name="content" placeholder="Post an announcement to this group..." required></textarea>
            <div class="feed-composer-actions">
                <button type="submit" class="btn-primary">Broadcast</button>
            </div>
        </div>
    </form>
<?php endif; ?>

<div style="margin-top: 28px;">
    <h3 class="timeline-heading">Notice board</h3>
    <div class="announcements-timeline">
        <?php if (empty($announcements)): ?>
            <div class="feed-empty">
                <p>No announcements posted here yet.</p>
            </div>
        <?php else: ?>
            <?php foreach ($announcements as $ann): ?>
                <div class="post-card">
                    <div class="post-header">
                        <span class="clf-avatar <?= clAvatarClass($ann['sender_name']) ?>"><?= clInitial($ann['sender_name']) ?></span>
                        <div>
                            <p class="post-author"><?= htmlspecialchars($ann['sender_name']) ?></p>
                            <p class="post-time"><?= clTimeAgo($ann['created_at']) ?></p>
                        </div>
                    </div>
                    <p class="post-content"><?= htmlspecialchars($ann['content']) ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../app/includes/dashboard_foot.php'; ?>