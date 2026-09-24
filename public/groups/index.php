<?php
$activePage = 'groups';
$pageTitle = 'Groups Hub';
require_once __DIR__ . '/../../app/includes/dashboard_head.php';

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_group'])) {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (!empty($name)) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO groups (name, description, creator_id) VALUES (?, ?, ?)");
            $stmt->execute([$name, $description, $userId]);
            $groupId = $pdo->lastInsertId();

            $stmt = $pdo->prepare("INSERT INTO group_members (group_id, user_id, role) VALUES (?, ?, 'admin')");
            $stmt->execute([$groupId, $userId]);

            $pdo->commit();
            header("Location: /CL_DEV/CampusLink/public/groups/view.php?id=" . $groupId);
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Failed to create group.";
        }
    }
}

$stmt = $pdo->prepare("
    SELECT g.*, 
           (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as total_members,
           (SELECT role FROM group_members WHERE group_id = g.id AND user_id = ?) as membership_role
    FROM groups g 
    ORDER BY g.created_at DESC
");
$stmt->execute([$userId]);
$groups = $stmt->fetchAll();

function clInitial($name) {
    $name = trim((string)$name);
    return htmlspecialchars($name === '' ? 'G' : mb_strtoupper(mb_substr($name, 0, 1)));
}
function clAvatarClass($seed) {
    $palette = ['rust', 'ink-blue', 'red', 'plum'];
    return 'avatar-' . $palette[crc32((string)$seed) % count($palette)];
}
?>

<div class="clg-header">
    <div>
        <h1>Campus groups</h1>
        <p>Join communities, module study teams, and interest clubs.</p>
    </div>
    <button onclick="document.getElementById('create-modal').style.display='block'" class="btn-primary">+ Create group</button>
</div>

<div id="create-modal" class="clg-modal">
    <h3>Launch a new community</h3>
    <form method="POST" action="index.php">
        <input type="hidden" name="create_group" value="1">
        <input type="text" name="name" placeholder="Group name (e.g., PHP Developers Club)" required>
        <textarea name="description" placeholder="What is this community about?" required></textarea>
        <div class="clg-modal-actions">
            <button type="button" onclick="document.getElementById('create-modal').style.display='none'" class="btn-plain">Cancel</button>
            <button type="submit" class="btn-primary">Launch group</button>
        </div>
    </form>
</div>

<div class="groups-grid">
    <?php if (empty($groups)): ?>
        <div class="feed-empty" style="grid-column: 1/-1;">
            <p>No active groups found on campus yet.</p>
        </div>
    <?php else: ?>
        <?php foreach ($groups as $group): ?>
            <div class="group-card">
                <div>
                    <div class="group-card-top">
                        <span class="clf-avatar <?= clAvatarClass($group['name']) ?>"><?= clInitial($group['name']) ?></span>
                        <h3><?= htmlspecialchars($group['name']) ?></h3>
                    </div>
                    <p class="desc"><?= htmlspecialchars(substr($group['description'], 0, 90)) ?><?= strlen($group['description']) > 90 ? '...' : '' ?></p>
                </div>
                <div class="group-card-foot">
                    <span class="group-members"><i class="fa-solid fa-user-group"></i> <?= $group['total_members'] ?> members</span>
                    <div class="group-actions">
                        <a href="/CL_DEV/CampusLink/public/groups/view.php?id=<?= $group['id'] ?>" class="btn-line">View</a>
                        <?php if (!$group['membership_role']): ?>
                            <a href="/CL_DEV/CampusLink/public/groups/join.php?id=<?= $group['id'] ?>" class="btn-line solid">Join</a>
                        <?php else: ?>
                            <span class="group-joined">✓ <?= ucfirst($group['membership_role']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../app/includes/dashboard_foot.php'; ?>