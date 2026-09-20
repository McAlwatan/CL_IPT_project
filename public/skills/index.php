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

    .cls-header { margin-bottom: 24px; }
    .cls-header h1 { font-size: 22px; font-weight: 700; margin: 0 0 4px; letter-spacing: -0.01em; color: var(--clf-ink); }
    .cls-header p { color: var(--clf-sub); margin: 0; font-size: 13.5px; }

    .clskill-panel { background: #fff; border: 1px solid var(--clf-line); border-radius: 8px; padding: 20px; margin-bottom: 24px; }
    .clskill-panel h3 {
        font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;
        color: var(--clf-sub); margin: 0 0 4px;
    }
    .clskill-panel > p { color: var(--clf-sub); font-size: 12.5px; margin: 0 0 16px; }

    .clskill-flash {
        color: #2e6b45; background: #eaf5ee; border: 1px solid #cfe8d8; border-radius: 6px;
        padding: 8px 12px; font-size: 13px; font-weight: 600; margin-bottom: 14px;
    }

    .clskill-tags { display: flex; flex-wrap: wrap; gap: 8px; }
    .clskill-tags form { margin: 0; }
    .clskill-toggle-btn {
        border: 1px solid var(--clf-line); background: #fff; color: var(--clf-ink);
        padding: 7px 15px; border-radius: 20px; font-size: 12.5px; font-weight: 600; cursor: pointer;
    }
    .clskill-toggle-btn:hover { border-color: var(--clf-ink); }
    .clskill-toggle-btn.active { background: var(--clf-ink); color: #fff; border-color: var(--clf-ink); }

    .clskill-filter-bar {
        margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;
        background: #fff; padding: 14px 18px; border-radius: 8px; border: 1px solid var(--clf-line); flex-wrap: wrap; gap: 12px;
    }
    .clskill-filter-bar h3 { color: var(--clf-ink); font-weight: 700; font-size: 14.5px; margin: 0; }
    .clskill-filter-bar form { display: flex; gap: 10px; align-items: center; }
    .clskill-filter-bar label { color: var(--clf-sub); font-size: 12.5px; white-space: nowrap; }
    .clskill-filter-bar select {
        padding: 7px 10px; background: #fff; border: 1px solid var(--clf-line); border-radius: 6px;
        color: var(--clf-ink); font-size: 12.5px; outline: none; cursor: pointer; font-family: inherit;
    }
    .clskill-clear { color: var(--clf-accent); text-decoration: none; font-size: 12.5px; font-weight: 600; }

    .clskill-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 14px; }
    .feed-empty {
        background: #fff; border: 1px dashed var(--clf-line); border-radius: 8px; padding: 40px;
        text-align: center; color: var(--clf-sub); font-size: 13.5px;
    }

    .partner-card {
        background: #fff; border: 1px solid var(--clf-line); border-radius: 8px; padding: 18px;
        display: flex; flex-direction: column; justify-content: space-between;
    }
    .partner-head { display: flex; align-items: center; gap: 11px; margin-bottom: 4px; text-decoration: none; }
    .partner-avatar { width: 36px; height: 36px; font-size: 14px; }
    .partner-name { color: var(--clf-ink); font-size: 15px; font-weight: 600; margin: 0; }
    .partner-head:hover .partner-name { color: var(--clf-accent); }
    .partner-course { color: var(--clf-sub); font-size: 11.5px; display: block; margin-top: 1px; }

    .partner-tags { display: flex; flex-wrap: wrap; gap: 6px; margin: 12px 0 18px; }
    .partner-tag {
        background: var(--clf-bg-soft); border: 1px solid var(--clf-line); color: var(--clf-ink);
        font-size: 11px; padding: 4px 10px; border-radius: 12px; font-weight: 500;
    }

    .btn-line {
        display: block; width: 100%; text-align: center; border: 1px solid var(--clf-line);
        background: #fff; color: var(--clf-ink); text-decoration: none; font-size: 13px;
        font-weight: 600; padding: 9px; border-radius: 6px;
    }
    .btn-line:hover { border-color: var(--clf-ink); }
</style>

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