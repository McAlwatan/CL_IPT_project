<?php
$activePage = 'feed';
$pageTitle = 'Feed';
require_once __DIR__ . '/../../app/includes/dashboard_head.php';

$userId = function_exists('currentUserId') ? currentUserId() : ($currentUser['id'] ?? null);

$posts = $pdo->query("
    SELECT posts.*, users.name AS author_name
    FROM posts
    JOIN users ON users.id = posts.user_id
    ORDER BY posts.created_at DESC
")->fetchAll();

$commentsByPost = [];
if (!empty($posts)) {
    $postIds = array_column($posts, 'id');
    $placeholders = implode(',', array_fill(0, count($postIds), '?'));
    $stmt = $pdo->prepare("
        SELECT post_comments.*, users.name AS author_name
        FROM post_comments
        JOIN users ON users.id = post_comments.user_id
        WHERE post_id IN ($placeholders)
        ORDER BY post_comments.created_at ASC
    ");
    $stmt->execute($postIds);
    foreach ($stmt->fetchAll() as $comment) {
        $commentsByPost[$comment['post_id']][] = $comment;
    }
}

$sidebarGroups = [];
try {
    $stmt = $pdo->prepare("
        SELECT g.*,
               (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as total_members,
               (SELECT role FROM group_members WHERE group_id = g.id AND user_id = ?) as membership_role
        FROM groups g
        ORDER BY g.created_at DESC
        LIMIT 3
    ");
    $stmt->execute([$userId]);
    $sidebarGroups = $stmt->fetchAll();
} catch (Exception $e) {
    $sidebarGroups = [];
}

$sidebarDocuments = [];
try {
    $sidebarDocuments = $pdo->query("
        SELECT d.*, u.name as uploader_name
        FROM documents d
        JOIN users u ON d.user_id = u.id
        ORDER BY d.created_at DESC
        LIMIT 3
    ")->fetchAll();
} catch (Exception $e) {
    $sidebarDocuments = [];
}

function initials($name) {
    $name = trim((string)$name);
    if ($name === '') return 'S';
    $parts = preg_split('/\s+/', $name);
    $first = mb_substr($parts[0], 0, 1);
    $second = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';
    return htmlspecialchars(mb_strtoupper($first . $second));
}

function avatarColorClass($name) {
    $palette = ['rust', 'ink-blue', 'moss', 'plum'];
    $index = crc32((string)$name) % count($palette);
    return 'avatar-' . $palette[$index];
}

function timeAgo($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    return floor($diff / 86400) . 'd ago';
}

function renderContentWithHashtags($content) {
    $escaped = nl2br(htmlspecialchars($content));
    return preg_replace('/(#[\w]+)/u', '<span class="hashtag">$1</span>', $escaped);
}
?>

<div class="clf-layout">
    <div>
        <div class="clf-header page-header">
            <h1>Your feed</h1>
            <p>See what's happening across your campus.</p>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert-error">
                <?= $_GET['error'] === 'empty_post' ? 'Write something before posting.' : 'Write a comment before submitting.' ?>
            </div>
        <?php endif; ?>

        <form class="feed-composer" method="POST" action="/CL_DEV/CampusLink/public/feed/create_post.php">
            <span class="clf-avatar <?= avatarColorClass($currentUser['name'] ?? 'S') ?>"><?= initials($currentUser['name'] ?? 'S') ?></span>
            <div class="feed-composer-body">
                <textarea name="content" placeholder="What's on your mind, <?= htmlspecialchars(explode(' ', $currentUser['name'] ?? 'there')[0]) ?>?" required></textarea>
                <div class="feed-composer-actions">
                    <button type="submit" class="btn-primary">Post</button>
                </div>
            </div>
        </form>

        <?php if (empty($posts)): ?>
            <div class="feed-empty">
                <p>No posts yet — be the first to share something.</p>
            </div>
        <?php else: ?>
            <div class="feed-list">
                <?php foreach ($posts as $post): ?>
                    <article class="post-card" id="post-<?= $post['id'] ?>">
                        <div class="post-header">
                            <span class="clf-avatar <?= avatarColorClass($post['author_name']) ?>"><?= initials($post['author_name']) ?></span>
                            <div>
                                <p class="post-author"><?= htmlspecialchars($post['author_name']) ?></p>
                                <p class="post-time"><?= timeAgo($post['created_at']) ?></p>
                            </div>
                        </div>

                        <p class="post-content"><?= renderContentWithHashtags($post['content']) ?></p>

                        <div class="post-footer">
                            <div class="post-stats">
                                <button type="button" class="post-stat" title="Like"><i class="fa-regular fa-thumbs-up"></i> Like</button>
                                <span class="post-stat" style="cursor:default;"><i class="fa-regular fa-comment"></i> <?= count($commentsByPost[$post['id']] ?? []) ?> comment<?= count($commentsByPost[$post['id']] ?? []) === 1 ? '' : 's' ?></span>
                            </div>
                            <button type="button" class="post-share" onclick="navigator.clipboard.writeText(window.location.origin + window.location.pathname + '#post-<?= $post['id'] ?>')">🔗 Share</button>
                        </div>

                        <?php if (!empty($commentsByPost[$post['id']])): ?>
                            <div class="comment-list">
                                <?php foreach ($commentsByPost[$post['id']] as $comment): ?>
                                    <div class="comment-row">
                                        <span class="clf-avatar small <?= avatarColorClass($comment['author_name']) ?>"><?= initials($comment['author_name']) ?></span>
                                        <div class="comment-bubble">
                                            <p class="comment-author"><?= htmlspecialchars($comment['author_name']) ?></p>
                                            <p class="comment-text"><?= htmlspecialchars($comment['comment']) ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <form class="comment-form" method="POST" action="/CL_DEV/CampusLink/public/feed/comment.php">
                            <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                            <input type="text" name="content" placeholder="Write a comment..." required>
                            <button type="submit">Send</button>
                        </form>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <aside class="clf-sidebar">
        <div>
            <h2>Recent documents</h2>
            <?php if (empty($sidebarDocuments)): ?>
                <div class="mini-card sidebar-empty">Nothing shared yet.</div>
            <?php else: ?>
                <?php foreach ($sidebarDocuments as $doc): ?>
                    <div class="mini-card">
                        <span class="doc-badge"><?= htmlspecialchars($doc['course_code']) ?></span>
                        <p class="doc-title"><?= htmlspecialchars($doc['title']) ?></p>
                        <p class="doc-meta">by <?= htmlspecialchars($doc['uploader_name']) ?></p>
                        <div class="doc-foot">
                            <span class="doc-size"><i class="fa-solid fa-download"></i> <?= round($doc['file_size'] / 1024 / 1024, 2) ?> MB</span>
                            <a class="btn-line" href="/CL_DEV/CampusLink/public/documents/download.php?id=<?= $doc['id'] ?>">Download</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            <a class="sidebar-more" href="/CL_DEV/CampusLink/public/documents/index.php">All documents →</a>
        </div>

        <div>
            <h2>Groups</h2>
            <?php if (empty($sidebarGroups)): ?>
                <div class="mini-card sidebar-empty">No groups yet.</div>
            <?php else: ?>
                <?php foreach ($sidebarGroups as $group): ?>
                    <div class="mini-card">
                        <p class="group-name"><?= htmlspecialchars($group['name']) ?></p>
                        <p class="group-desc"><?= htmlspecialchars(substr($group['description'], 0, 70)) ?><?= strlen($group['description']) > 70 ? '...' : '' ?></p>
                        <div class="group-foot">
                            <span class="group-members"><i class="fa-solid fa-user-group"></i> <?= $group['total_members'] ?></span>
                            <?php if (!$group['membership_role']): ?>
                                <a class="btn-line solid" href="/CL_DEV/CampusLink/public/groups/join.php?id=<?= $group['id'] ?>">Join</a>
                            <?php else: ?>
                                <span class="group-joined">✓ <?= ucfirst($group['membership_role']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            <a class="sidebar-more" href="/CL_DEV/CampusLink/public/groups/index.php">All groups →</a>
        </div>
    </aside>
</div>

<?php require_once __DIR__ . '/../../app/includes/dashboard_foot.php'; ?>