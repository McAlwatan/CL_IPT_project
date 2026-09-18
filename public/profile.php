<?php
$activePage = 'profile';
$pageTitle = 'Student Profile';
require_once __DIR__ . '/../app/includes/dashboard_head.php';

$profileId = isset($_GET['id']) ? (int)$_GET['id'] : (int)$_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT u.*, uni.name as university_name 
    FROM users u
    LEFT JOIN universities uni ON u.university_id = uni.id
    WHERE u.id = ?
");
$stmt->execute([$profileId]);
$profileUser = $stmt->fetch();

if (!$profileUser) {
    die("<div class='feed-empty'><p>Student profile not found.</p></div>");
}

$isOwnProfile = ((int)$profileUser['id'] === (int)$_SESSION['user_id']);

function clInitial($name) {
    $name = trim((string)$name);
    return htmlspecialchars($name === '' ? 'S' : mb_strtoupper(mb_substr($name, 0, 1)));
}
function clAvatarClass($seed) {
    $palette = ['rust', 'ink-blue', 'moss', 'plum'];
    return 'avatar-' . $palette[crc32((string)$seed) % count($palette)];
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

    .clf-avatar {
        border-radius: 50%; display: flex; align-items: center; justify-content: center;
        font-weight: 600; color: #fff; flex-shrink: 0;
    }
    .avatar-rust { background: #a8481f; } .avatar-ink-blue { background: #2c3e5c; }
    .avatar-moss { background: #4a5e3a; } .avatar-plum { background: #5c3a54; }

    .clp-card {
        background: #fff; border: 1px solid var(--clf-line); border-radius: 10px;
        padding: 32px; max-width: 520px; margin: 24px auto; color: var(--clf-ink);
    }

    .clp-top { text-align: center; margin-bottom: 22px; }
    .clp-avatar { width: 68px; height: 68px; font-size: 26px; margin: 0 auto 14px; }
    .clp-name { font-size: 21px; font-weight: 700; margin: 0 0 3px; }
    .clp-email { color: var(--clf-sub); font-size: 13.5px; margin: 0; }

    .clp-panel { background: var(--clf-bg-soft); border: 1px solid var(--clf-line); border-radius: 8px; padding: 16px 18px; margin-bottom: 20px; }
    .clp-panel h3 {
        font-size: 11.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;
        color: var(--clf-sub); margin: 0 0 10px; border-bottom: 1px solid var(--clf-line); padding-bottom: 8px;
    }
    .clp-panel p { font-size: 13.5px; color: var(--clf-ink); margin: 0 0 6px; }
    .clp-panel p:last-child { margin-bottom: 0; }
    .clp-panel strong { color: var(--clf-sub); font-weight: 600; }

    .clp-interests-block { margin-bottom: 26px; }
    .clp-interests-block h3 {
        font-size: 11.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;
        color: var(--clf-sub); margin: 0 0 10px;
    }
    .clp-interests { display: flex; flex-wrap: wrap; gap: 7px; }
    .clp-interest-tag {
        background: #fff; border: 1px solid var(--clf-line); color: var(--clf-ink);
        font-size: 12.5px; padding: 5px 12px; border-radius: 20px; font-weight: 500;
    }
    .clp-empty-note { color: var(--clf-sub); font-size: 13px; font-style: italic; }

    .clp-actions { text-align: center; border-top: 1px solid var(--clf-line); padding-top: 20px; }
    .btn-primary {
        display: inline-block; background: var(--clf-ink); color: #fff; text-decoration: none;
        padding: 11px 28px; border-radius: 6px; font-weight: 600; font-size: 14px; border: none;
    }
    .btn-primary:hover { background: #000; }
    .btn-outline {
        display: inline-block; background: #fff; color: var(--clf-ink); text-decoration: none;
        padding: 9px 22px; border-radius: 6px; border: 1px solid var(--clf-line); font-size: 13.5px; font-weight: 600;
    }
    .btn-outline:hover { border-color: var(--clf-ink); }
</style>

<div class="clp-card">
    <div class="clp-top">
        <span class="clf-avatar clp-avatar <?= clAvatarClass($profileUser['name']) ?>"><?= clInitial($profileUser['name']) ?></span>
        <h2 class="clp-name"><?= htmlspecialchars($profileUser['name']) ?></h2>
        <p class="clp-email"><?= htmlspecialchars($profileUser['email']) ?></p>
    </div>

    <div class="clp-panel">
        <h3>Academic credentials</h3>
        <p><strong>Institution:</strong> <?= htmlspecialchars($profileUser['university_name'] ?? 'Not specified (complete onboarding)') ?></p>
        <p><strong>Course track:</strong> <?= htmlspecialchars($profileUser['course'] ?? 'Not specified') ?></p>
        <p><strong>Year of study:</strong> Year <?= htmlspecialchars($profileUser['year_of_study'] ?? '1') ?></p>
    </div>

    <div class="clp-interests-block">
        <h3>Interests</h3>
        <div class="clp-interests">
            <?php if (!empty($profileUser['interests'])): ?>
                <?php foreach (explode(', ', $profileUser['interests']) as $interest): ?>
                    <span class="clp-interest-tag"><?= htmlspecialchars($interest) ?></span>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="clp-empty-note">No specific interests selected yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="clp-actions">
        <?php if (!$isOwnProfile): ?>
            <a href="/IPT_WEB_PROJECT/CampusLink/public/messages/index.php?user_id=<?= $profileUser['id'] ?>" class="btn-primary">Message student</a>
        <?php else: ?>
            <a href="/IPT_WEB_PROJECT/CampusLink/public/settings.php" class="btn-outline">Update profile info</a>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../app/includes/dashboard_foot.php'; ?>