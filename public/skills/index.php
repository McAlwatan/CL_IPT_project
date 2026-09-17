<?php
$activePage = 'skills';
$pageTitle = 'Skills Directory';
require_once __DIR__ . '/../../app/includes/dashboard_head.php';

$userId = $_SESSION['user_id'];
$message = '';

// 1. Handle adding or removing a skill for the logged-in student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_skill'])) {
    $skillId = (int)$_POST['skill_id'];
    $action = $_POST['action']; // 'add' or 'remove'

    if ($action === 'add') {
        $stmt = $pdo->prepare("INSERT IGNORE INTO skill_user (user_id, skill_id) VALUES (?, ?)");
        $stmt->execute([$userId, $skillId]);
        $message = "Skill added to your profile!";
    } elseif ($action === 'remove') {
        $stmt = $pdo->prepare("DELETE FROM skill_user WHERE user_id = ? AND skill_id = ?");
        $stmt->execute([$userId, $skillId]);
        $message = "Skill removed from your profile!";
    }
}

// 2. Fetch all system seed skills to let the logged-in user check/uncheck their skills
$stmt = $pdo->query("SELECT * FROM skills ORDER BY name ASC");
$allSkills = $stmt->fetchAll();

// 3. Fetch the logged-in user's active skills to pre-populate form check states
$stmt = $pdo->prepare("SELECT skill_id FROM skill_user WHERE user_id = ?");
$stmt->execute([$userId]);
$mySkillIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

// 4. Fetch all other student profiles along with their mapped skills strings
$stmt = $pdo->prepare("
    SELECT u.id, u.name, GROUP_CONCAT(s.name SEPARATOR ', ') as skills_list
    FROM users u
    JOIN skill_user su ON u.id = su.user_id
    JOIN skills s ON su.skill_id = s.id
    WHERE u.id != ?
    GROUP BY u.id
    ORDER BY u.name ASC
");
$stmt->execute([$userId]);
$peerSkills = $stmt->fetchAll();
?>

<div class="page-header" style="margin-bottom: 40px;">
    <h1>Skills & Study Partners</h1>
    <p>Find peer study buddies, resource tutors, or group up for upcoming module project milestones.</p>
</div>

<!-- Logged-in Student Skills Dashboard Widget -->
<section style="background-color: #2a2a2a; border: 1px solid #3d3d3d; border-radius: 16px; padding: 24px; margin-bottom: 40px;">
    <h3 style="color: #ffffff; margin-bottom: 8px; font-weight: 600;">Your Skill Tags</h3>
    <p style="color: #a0a0a0; font-size: 14px; margin-bottom: 20px;">Check or uncheck skill fields to update your profile card so campus peers can discover you.</p>
    
    <?php if ($message): ?> <p style="color: #10b981; margin-bottom:16px; font-size:14px; font-weight:600;"><?= $message ?></p> <?php endif; ?>

    <div style="display: flex; flex-wrap: wrap; gap: 12px;">
        <?php foreach ($allSkills as $skill): ?>
            <?php $hasSkill = in_array($skill['id'], $mySkillIds); ?>
            <form method="POST" action="index.php" style="margin: 0;">
                <input type="hidden" name="skill_id" value="<?= $skill['id'] ?>">
                <input type="hidden" name="toggle_skill" value="1">
                <input type="hidden" name="action" value="<?= $hasSkill ? 'remove' : 'add' ?>">
                
                <button type="submit" style="
                    background-color: <?= $hasSkill ? '#ffffff' : '#1e1e1e' ?>;
                    color: <?= $hasSkill ? '#121212' : '#ffffff' ?>;
                    border: 1px solid #3d3d3d;
                    padding: 8px 16px;
                    border-radius: 20px;
                    font-size: 14px;
                    font-weight: 500;
                    cursor: pointer;
                    transition: all 0.2s ease;">
                    <?= htmlspecialchars($skill['name']) ?> <?= $hasSkill ? '✕' : '+' ?>
                </button>
            </form>
        <?php endforeach; ?>
    </div>
</section>

<!-- Peers Discovery Directory List Grid -->
<section>
    <h3 style="color: #ffffff; margin-bottom: 20px; font-weight: 600;">Discover Study Partners</h3>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 24px;">
        <?php if (empty($peerSkills)): ?>
            <div class="feed-empty" style="grid-column: 1/-1;">
                <p>No other students have listed skills yet. Be the first to share yours!</p>
            </div>
        <?php else: ?>
            <?php foreach ($peerSkills as $peer): ?>
                <div class="partner-card" style="background-color: #2a2a2a; border: 1px solid #3d3d3d; border-radius: 16px; padding: 24px; display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 14px;">
                            <span style="width: 36px; height: 36px; background: #ffffff; color: #121212; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-weight: 600; font-size: 15px;">
                                <?= htmlspecialchars(strtoupper(substr($peer['name'], 0, 1))) ?>
                            </span>
                            <h4 style="color: #ffffff; font-size: 17px; font-weight: 600; margin: 0;"><?= htmlspecialchars($peer['name']) ?></h4>
                        </div>
                        
                        <!-- Loop out skills as separate tag blocks -->
                        <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 16px;">
                            <?php foreach (explode(', ', $peer['skills_list']) as $sTag): ?>
                                <span style="background-color: #1e1e1e; border: 1px solid #3d3d3d; color: #cbd5e1; font-size: 11px; padding: 4px 10px; border-radius: 12px;">
                                    <?= htmlspecialchars($sTag) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Direct absolute link leading to student's profile card view -->
                    <a href="/IPT_WEB_PROJECT/CampusLink/public/profile.php?id=<?= $peer['id'] ?>" 
                       style="display: block; width: 100%; text-align: center; background-color: #ffffff; color: #121212; text-decoration: none; font-size: 14px; font-weight: 600; padding: 10px; border-radius: 8px; margin-top: 10px;">
                        View Profile
                    </a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/../../app/includes/dashboard_foot.php'; ?>
