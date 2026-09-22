<?php
$activePage = 'skills';
$pageTitle = 'Skills Directory';
require_once __DIR__ . '/../../app/includes/dashboard_head.php';

$userId = $_SESSION['user_id'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_skill'])) {
    $skillId = (int)$_POST['skill_id'];
    $action = $_POST['action'];

    if ($action === 'add') {
        $stmt = $pdo->prepare("INSERT IGNORE INTO skill_user (user_id, skill_id) VALUES (?, ?)");
        $stmt->execute([$userId, $skillId]);
        $message = "Skill added to your showcase profile!";
    } elseif ($action === 'remove') {
        $stmt = $pdo->prepare("DELETE FROM skill_user WHERE user_id = ? AND skill_id = ?");
        $stmt->execute([$userId, $skillId]);
        $message = "Skill removed from your profile.";
    }
}

$selectedFilterSkillId = isset($_GET['filter_skill']) ? (int)$_GET['filter_skill'] : null;

$stmt = $pdo->query("SELECT * FROM skills ORDER BY name ASC");
$allSkills = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT skill_id FROM skill_user WHERE user_id = ?");
$stmt->execute([$userId]);
$mySkillIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

if ($selectedFilterSkillId) {
    $stmt = $pdo->prepare("
        SELECT u.id, u.name, u.course, 
               GROUP_CONCAT(s.name SEPARATOR ', ') as skills_list
        FROM users u
        JOIN skill_user su ON u.id = su.user_id
        JOIN skills s ON su.skill_id = s.id
        WHERE u.id != ? AND u.id IN (
            SELECT user_id FROM skill_user WHERE skill_id = ?
        )
        GROUP BY u.id
        ORDER BY u.name ASC
    ");
    $stmt->execute([$userId, $selectedFilterSkillId]);
} else {
    $stmt = $pdo->prepare("
        SELECT u.id, u.name, u.course, 
               GROUP_CONCAT(s.name SEPARATOR ', ') as skills_list
        FROM users u
        JOIN skill_user su ON u.id = su.user_id
        JOIN skills s ON su.skill_id = s.id
        WHERE u.id != ?
        GROUP BY u.id
        ORDER BY u.name ASC
    ");
    $stmt->execute([$userId]);
}
$peerSkills = $stmt->fetchAll();

function clInitial($name) {
    $name = trim((string)$name);
    return htmlspecialchars($name === '' ? 'S' : mb_strtoupper(mb_substr($name, 0, 1)));
}
function clAvatarClass($seed) {
    $palette = ['rust', 'ink-blue', 'moss', 'plum'];
    return 'avatar-' . $palette[crc32((string)$seed) % count($palette)];
}
?>


<div class="cls-header">
    <h1>Skills directory</h1>
    <p>Register your expertise and discover peer study partners by skill.</p>
</div>

<section class="clskill-panel">
    <h3>Register your skills</h3>
    <p>Click a tag to add or remove it from your public showcase.</p>

    <?php if ($message): ?>
        <div class="clskill-flash">✓ <?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="clskill-tags">
        <?php foreach ($allSkills as $skill): ?>
            <?php $hasSkill = in_array($skill['id'], $mySkillIds); ?>
            <form method="POST" action="index.php">
                <input type="hidden" name="skill_id" value="<?= $skill['id'] ?>">
                <input type="hidden" name="toggle_skill" value="1">
                <input type="hidden" name="action" value="<?= $hasSkill ? 'remove' : 'add' ?>">
                <button type="submit" class="clskill-toggle-btn<?= $hasSkill ? ' active' : '' ?>">
                    <?= htmlspecialchars($skill['name']) ?> <?= $hasSkill ? '✕' : '+' ?>
                </button>
            </form>
        <?php endforeach; ?>
    </div>
</section>

<section class="clskill-filter-bar">
    <h3>Discover experts</h3>
    <form method="GET" action="index.php">
        <label>Filter by skill:</label>
        <select name="filter_skill" onchange="this.form.submit()">
            <option value="">Show all campus peers</option>
            <?php foreach ($allSkills as $skill): ?>
                <option value="<?= $skill['id'] ?>" <?= ($selectedFilterSkillId === (int)$skill['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($skill['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if ($selectedFilterSkillId): ?>
            <a href="index.php" class="clskill-clear">Clear ✕</a>
        <?php endif; ?>
    </form>
</section>

<div class="clskill-grid">
    <?php if (empty($peerSkills)): ?>
        <div class="feed-empty" style="grid-column: 1/-1;">
            <p>No campus peers match the selected skill filter yet.</p>
        </div>
    <?php else: ?>
        <?php foreach ($peerSkills as $peer): ?>
            <div class="partner-card">
                <div>
                    <a href="/IPT_WEB_PROJECT/CampusLink/public/profile.php?id=<?= $peer['id'] ?>" class="partner-head">
                        <span class="clf-avatar partner-avatar <?= clAvatarClass($peer['name']) ?>"><?= clInitial($peer['name']) ?></span>
                        <div>
                            <h4 class="partner-name"><?= htmlspecialchars($peer['name']) ?></h4>
                            <span class="partner-course"><?= htmlspecialchars($peer['course'] ?? 'Student') ?></span>
                        </div>
                    </a>

                    <div class="partner-tags">
                        <?php foreach (explode(', ', $peer['skills_list']) as $sTag): ?>
                            <span class="partner-tag">⚡ <?= htmlspecialchars($sTag) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>

                <a href="/IPT_WEB_PROJECT/CampusLink/public/profile.php?id=<?= $peer['id'] ?>" class="btn-line">View profile</a>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../app/includes/dashboard_foot.php'; ?>