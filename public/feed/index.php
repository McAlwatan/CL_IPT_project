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

// Deterministic, muted color per name - not a full rainbow, just enough
// variation to tell people apart at a glance.
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

<style>
    :root {
        --clf-ink: #1c1c1c;
        --clf-sub: #767676;
        --clf-line: #e4e4e4;
        --clf-bg-soft: #f6f6f4;
        --clf-accent: #b8441f;
    }

    .clf-layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 300px;
        gap: 24px;
        align-items: start;
        color: var(--clf-ink);
    }
    @media (max-width: 900px) {
        .clf-layout { grid-template-columns: 1fr; }
    }

    .clf-header { margin-bottom: 18px; }
    .clf-header h1 { font-size: 22px; font-weight: 700; margin: 0 0 4px; letter-spacing: -0.01em; }
    .clf-header p { color: var(--clf-sub); margin: 0; font-size: 13.5px; }

    .alert-error {
        background: #fbeae5;
        color: #9c3b1e;
        border: 1px solid #eccabf;
        border-radius: 6px;
        padding: 9px 12px;
        margin-bottom: 14px;
        font-size: 13.5px;
    }

    .clf-avatar {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 12.5px;
        color: #fff;
        flex-shrink: 0;
        letter-spacing: 0.02em;
    }
    .clf-avatar.small { width: 26px; height: 26px; font-size: 10.5px; }

    .avatar-rust     { background: #a8481f; }
    .avatar-ink-blue { background: #2c3e5c; }
    .avatar-moss     { background: #4a5e3a; }
    .avatar-plum     { background: #5c3a54; }

    .feed-composer {
        background: #fff;
        border: 1px solid var(--clf-line);
        border-radius: 8px;
        padding: 14px 16px;
        display: flex;
        gap: 12px;
        margin-bottom: 16px;
    }
    .feed-composer-body { flex: 1; }
    .feed-composer textarea {
        width: 100%;
        border: none;
        resize: none;
        font-size: 14px;
        font-family: inherit;
        color: var(--clf-ink);
        padding: 6px 0 10px;
        min-height: 20px;
        border-bottom: 1px solid var(--clf-line);
        outline: none;
    }
    .feed-composer textarea:focus { border-bottom-color: var(--clf-accent); }
    .feed-composer textarea::placeholder { color: #a3a3a3; }
    .feed-composer-actions {
        display: flex;
        justify-content: flex-end;
        margin-top: 10px;
    }
    .btn-primary {
        background: var(--clf-ink);
        color: #fff;
        border: none;
        border-radius: 6px;
        padding: 7px 16px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
    }
    .btn-primary:hover { background: #000; }

    .feed-empty {
        background: #fff;
        border: 1px dashed var(--clf-line);
        border-radius: 8px;
        padding: 28px;
        text-align: center;
        color: var(--clf-sub);
        font-size: 13.5px;
    }

    .feed-list { display: flex; flex-direction: column; gap: 12px; }

    .post-card {
        background: #fff;
        border: 1px solid var(--clf-line);
        border-radius: 8px;
        padding: 16px;
    }

    .post-header { display: flex; gap: 10px; align-items: center; margin-bottom: 10px; }
    .post-author { font-weight: 600; font-size: 13.5px; margin: 0; }
    .post-time { font-size: 12px; color: var(--clf-sub); margin: 1px 0 0; }

    .post-content { font-size: 14px; line-height: 1.5; margin: 0 0 12px; }
    .hashtag { color: var(--clf-accent); font-weight: 600; }

    .post-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-top: 1px solid var(--clf-line);
        padding-top: 10px;
        margin-bottom: 2px;
        font-size: 13px;
    }
    .post-stats { display: flex; align-items: center; gap: 16px; }
    .post-stat {
        color: var(--clf-sub);
        background: none;
        border: none;
        cursor: pointer;
        padding: 0;
        font: inherit;
    }
    .post-stat:hover { color: var(--clf-ink); }
    .post-share {
        color: var(--clf-sub);
        background: none;
        border: none;
        cursor: pointer;
        font: inherit;
        padding: 0;
    }
    .post-share:hover { color: var(--clf-ink); }

    .comment-list { display: flex; flex-direction: column; gap: 8px; margin-bottom: 12px; }
    .comment-row { display: flex; gap: 8px; align-items: flex-start; }
    .comment-bubble { background: var(--clf-bg-soft); border-radius: 6px; padding: 6px 10px; flex: 1; }
    .comment-author { font-size: 12px; font-weight: 600; margin: 0 0 1px; }
    .comment-text { font-size: 13px; margin: 0; color: var(--clf-ink); }

    .comment-form { display: flex; gap: 8px; margin-top: 8px; }
    .comment-form input[type="text"] {
        flex: 1;
        border: 1px solid var(--clf-line);
        border-radius: 6px;
        padding: 7px 10px;
        font-size: 13px;
        font-family: inherit;
        outline: none;
    }
    .comment-form input[type="text"]:focus { border-color: var(--clf-accent); }
    .comment-form button {
        border: 1px solid var(--clf-line);
        background: #fff;
        color: var(--clf-ink);
        font-weight: 600;
        font-size: 13px;
        padding: 7px 14px;
        border-radius: 6px;
        cursor: pointer;
    }
    .comment-form button:hover { border-color: var(--clf-ink); }

    /* Sidebar */
    .clf-sidebar { display: flex; flex-direction: column; gap: 22px; }
    .clf-sidebar h2 {
        font-size: 12.5px;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--clf-sub);
        font-weight: 700;
        margin: 0 0 10px;
    }

    .mini-card {
        background: #fff;
        border: 1px solid var(--clf-line);
        border-radius: 8px;
        padding: 12px;
        margin-bottom: 8px;
    }
    .mini-card:last-child { margin-bottom: 0; }

    .doc-badge {
        display: inline-block;
        background: #24406b;
        color: #fff;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.03em;
        padding: 2px 7px;
        border-radius: 4px;
        text-transform: uppercase;
    }
    .doc-title { font-size: 13.5px; font-weight: 600; margin: 7px 0 2px; line-height: 1.3; }
    .doc-meta { font-size: 12px; color: var(--clf-sub); margin: 0; }
    .doc-foot {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 9px;
    }
    .doc-size { font-size: 11.5px; color: var(--clf-sub); }

    .btn-line {
        border: 1px solid var(--clf-line);
        background: #fff;
        color: var(--clf-ink);
        text-decoration: none;
        font-size: 12px;
        font-weight: 600;
        padding: 5px 11px;
        border-radius: 6px;
        white-space: nowrap;
    }
    .btn-line:hover { border-color: var(--clf-ink); }
    .btn-line.solid { background: var(--clf-ink); color: #fff; border-color: var(--clf-ink); }
    .btn-line.solid:hover { background: #000; }

    .group-name { font-size: 13.5px; font-weight: 600; margin: 0 0 2px; }
    .group-desc { font-size: 12px; color: var(--clf-sub); margin: 0 0 8px; line-height: 1.4; }
    .group-foot { display: flex; justify-content: space-between; align-items: center; }
    .group-members { font-size: 11.5px; color: var(--clf-sub); }
    .group-joined { font-size: 11.5px; color: #4a5e3a; font-weight: 700; }

    .sidebar-empty { font-size: 12.5px; color: var(--clf-sub); }
    .sidebar-more { display: block; text-align: right; font-size: 12px; font-weight: 600; color: var(--clf-accent); text-decoration: none; margin-top: 6px; }
</style>

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

        <form class="feed-composer" method="POST" action="/IPT_WEB_PROJECT/CampusLink/public/feed/create_post.php">
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

                        <form class="comment-form" method="POST" action="/IPT_WEB_PROJECT/CampusLink/public/feed/comment.php">
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
                            <a class="btn-line" href="/IPT_WEB_PROJECT/CampusLink/public/documents/download.php?id=<?= $doc['id'] ?>">Download</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            <a class="sidebar-more" href="/IPT_WEB_PROJECT/CampusLink/public/documents/index.php">All documents →</a>
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
                                <a class="btn-line solid" href="/IPT_WEB_PROJECT/CampusLink/public/groups/join.php?id=<?= $group['id'] ?>">Join</a>
                            <?php else: ?>
                                <span class="group-joined">✓ <?= ucfirst($group['membership_role']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            <a class="sidebar-more" href="/IPT_WEB_PROJECT/CampusLink/public/groups/index.php">All groups →</a>
        </div>
    </aside>
</div>

<?php require_once __DIR__ . '/../../app/includes/dashboard_foot.php'; ?>