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
            <a href="/CL_DEV/CampusLink/public/messages/index.php?user_id=<?= $profileUser['id'] ?>" class="btn-primary">Message student</a>
        <?php else: ?>
            <a href="/CL_DEV/CampusLink/public/settings.php" class="btn-outline">Update profile info</a>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../app/includes/dashboard_foot.php'; ?>