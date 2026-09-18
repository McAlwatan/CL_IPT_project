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

<style>
    :root {
        --clf-ink: #1c1c1c; --clf-sub: #767676; --clf-line: #e4e4e4;
        --clf-bg-soft: #f6f6f4; --clf-accent: #b8441f;
    }

    .clg-back { color: var(--clf-sub); text-decoration: none; font-size: 13px; }
    .clg-back:hover { color: var(--clf-ink); }

    .group-header-block { border-bottom: 1px solid var(--clf-line); padding-bottom: 20px; margin: 16px 0 20px; }
    .group-header-block h1 { color: var(--clf-ink); font-size: 24px; font-weight: 700; margin: 0 0 8px; }
    .group-header-block p { color: var(--clf-sub); font-size: 14px; line-height: 1.6; max-width: 640px; margin: 0; }
    .owner-badge {
        display: inline-block; background: var(--clf-bg-soft); color: var(--clf-sub); font-size: 12px;
        padding: 5px 10px; border-radius: 6px; margin-top: 12px; border: 1px solid var(--clf-line);
    }

    .btn-primary {
        display: inline-block; background: var(--clf-ink); color: #fff; text-decoration: none;
        padding: 8px 16px; border-radius: 6px; font-weight: 600; font-size: 13px; border: none; cursor: pointer;
    }
    .btn-primary:hover { background: #000; }

    .clf-avatar {
        width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center;
        justify-content: center; font-weight: 600; font-size: 12.5px; color: #fff; flex-shrink: 0;
    }
    .avatar-rust { background: #a8481f; } .avatar-ink-blue { background: #2c3e5c; }
    .avatar-moss { background: #4a5e3a; } .avatar-plum { background: #5c3a54; }

    .feed-composer {
        background: #fff; border: 1px solid var(--clf-line); border-radius: 8px; padding: 14px 16px;
        display: flex; gap: 12px; margin: 24px 0;
    }
    .feed-composer-body { flex: 1; display: flex; flex-direction: column; gap: 10px; }
    .feed-composer textarea {
        width: 100%; border: none; resize: none; font-size: 14px; font-family: inherit;
        color: var(--clf-ink); padding: 6px 0 10px; min-height: 20px; border-bottom: 1px solid var(--clf-line); outline: none;
    }
    .feed-composer textarea:focus { border-bottom-color: var(--clf-accent); }
    .feed-composer-actions { display: flex; justify-content: flex-end; }

    .timeline-heading {
        font-size: 12.5px; text-transform: uppercase; letter-spacing: 0.06em;
        color: var(--clf-sub); font-weight: 700; border-bottom: 1px solid var(--clf-line);
        padding-bottom: 10px; margin: 0 0 16px;
    }
    .feed-empty { background: #fff; border: 1px dashed var(--clf-line); border-radius: 8px; padding: 28px; text-align: center; color: var(--clf-sub); font-size: 13.5px; }

    .announcements-timeline { display: flex; flex-direction: column; gap: 12px; }
    .post-card { background: #fff; border: 1px solid var(--clf-line); border-radius: 8px; padding: 16px; }
    .post-header { display: flex; gap: 10px; align-items: center; margin-bottom: 10px; }
    .post-author { font-weight: 600; font-size: 13.5px; margin: 0; color: var(--clf-ink); }
    .post-time { font-size: 12px; color: var(--clf-sub); margin: 1px 0 0; }
    .post-content { font-size: 14px; line-height: 1.5; margin: 0; color: var(--clf-ink); white-space: pre-wrap; }
</style>

<a href="/IPT_WEB_PROJECT/CampusLink/public/groups/index.php" class="clg-back">← Back to groups</a>

<div class="group-header-block">
    <h1><?= htmlspecialchars($group['name']) ?></h1>
    <p><?= htmlspecialchars($group['description']) ?></p>
    <span class="owner-badge">Owner: <?= htmlspecialchars($group['creator_name']) ?></span>
</div>

<a href="/IPT_WEB_PROJECT/CampusLink/public/groups/chat.php?group_id=<?= $group['id'] ?>" class="btn-primary"><i class="fa-regular fa-comment"></i>  Enter live chat</a>

<?php if ($canPost): ?>
    <form action="/IPT_WEB_PROJECT/CampusLink/public/groups/post.php" method="POST" class="feed-composer">
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