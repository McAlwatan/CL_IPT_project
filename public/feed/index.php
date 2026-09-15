<?php
$activePage = 'feed';
$pageTitle = 'Feed';
require_once __DIR__ . '/../../app/includes/dashboard_head.php';

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

function initials($name) {
    return htmlspecialchars(strtoupper(substr($name ?: 'S', 0, 1)));
}

function timeAgo($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    return floor($diff / 86400) . 'd ago';
}
?>

<div class="page-header">
    <h1>Feed</h1>
    <p>See what's happening across your campus.</p>
</div>

<?php if (isset($_GET['error'])): ?>
    <div class="alert-error">
        <?= $_GET['error'] === 'empty_post' ? 'Write something before posting.' : 'Write a comment before submitting.' ?>
    </div>
<?php endif; ?>

<form class="feed-composer" method="POST" action="/IPT_WEB_PROJECT/CampusLink/public/feed/create_post.php">
    <span class="topbar-avatar"><?= initials($currentUser['name'] ?? 'S') ?></span>
    <div class="feed-composer-body">
        <textarea name="content" placeholder="Share something with campus..." required></textarea>
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
                    <span class="topbar-avatar"><?= initials($post['author_name']) ?></span>
                    <div>
                        <p class="post-author"><?= htmlspecialchars($post['author_name']) ?></p>
                        <p class="post-time"><?= timeAgo($post['created_at']) ?></p>
                    </div>
                </div>

                <p class="post-content"><?= nl2br(htmlspecialchars($post['content'])) ?></p>

                <?php if (!empty($commentsByPost[$post['id']])): ?>
                    <div class="comment-list">
                        <?php foreach ($commentsByPost[$post['id']] as $comment): ?>
                            <div class="comment-row">
                                <span class="topbar-avatar small"><?= initials($comment['author_name']) ?></span>
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

<?php require_once __DIR__ . '/../../app/includes/dashboard_foot.php'; ?>