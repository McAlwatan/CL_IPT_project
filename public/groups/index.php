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
            header("Location: /IPT_WEB_PROJECT/CampusLink/public/groups/view.php?id=" . $groupId);
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

    .clg-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; gap: 16px; }
    .clg-header h1 { font-size: 22px; font-weight: 700; margin: 0 0 4px; letter-spacing: -0.01em; color: var(--clf-ink); }
    .clg-header p { color: var(--clf-sub); margin: 0; font-size: 13.5px; }

    .btn-primary {
        background: var(--clf-ink); color: #fff; border: none; border-radius: 6px;
        padding: 8px 16px; font-size: 13px; font-weight: 600; cursor: pointer; white-space: nowrap;
    }
    .btn-primary:hover { background: #000; }

    .clg-modal {
        display: none; background: #fff; border: 1px solid var(--clf-line);
        border-radius: 8px; padding: 20px; margin-bottom: 28px;
    }
    .clg-modal h3 { font-size: 15px; font-weight: 700; margin: 0 0 14px; color: var(--clf-ink); }
    .clg-modal form { display: flex; flex-direction: column; gap: 12px; }
    .clg-modal input[type="text"], .clg-modal textarea {
        width: 100%; padding: 10px 12px; background: #fff; border: 1px solid var(--clf-line);
        border-radius: 6px; color: var(--clf-ink); font-family: inherit; font-size: 13.5px; outline: none;
    }
    .clg-modal input[type="text"]:focus, .clg-modal textarea:focus { border-color: var(--clf-accent); }
    .clg-modal textarea { height: 80px; resize: none; }
    .clg-modal-actions { display: flex; gap: 10px; justify-content: flex-end; }
    .btn-plain { background: none; border: none; color: var(--clf-sub); cursor: pointer; font-size: 13px; font-family: inherit; }
    .btn-plain:hover { color: var(--clf-ink); }

    .clf-avatar {
        width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center;
        justify-content: center; font-weight: 600; font-size: 14px; color: #fff; flex-shrink: 0;
    }
    .avatar-rust { background: #a8481f; } .avatar-ink-blue { background: #2c3e5c; }
    .avatar-moss { background: #4a5e3a; } .avatar-plum { background: #5c3a54; }

    .groups-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(270px, 1fr)); gap: 14px; }

    .group-card {
        background: #fff; border: 1px solid var(--clf-line); border-radius: 8px; padding: 16px;
        display: flex; flex-direction: column; justify-content: space-between;
    }
    .group-card-top { display: flex; gap: 10px; align-items: flex-start; margin-bottom: 10px; }
    .group-card h3 { color: var(--clf-ink); font-size: 15px; font-weight: 600; margin: 0 0 4px; }
    .group-card p.desc { color: var(--clf-sub); font-size: 12.5px; line-height: 1.5; margin: 0; min-height: 38px; }

    .group-card-foot {
        display: flex; justify-content: space-between; align-items: center;
        border-top: 1px solid var(--clf-line); padding-top: 12px; margin-top: 14px;
    }
    .group-members { color: var(--clf-sub); font-size: 12.5px; }
    .group-actions { display: flex; gap: 8px; }
    .btn-line {
        border: 1px solid var(--clf-line); background: #fff; color: var(--clf-ink); text-decoration: none;
        font-size: 12.5px; font-weight: 600; padding: 6px 12px; border-radius: 6px; white-space: nowrap;
    }
    .btn-line:hover { border-color: var(--clf-ink); }
    .btn-line.solid { background: var(--clf-ink); color: #fff; border-color: var(--clf-ink); }
    .btn-line.solid:hover { background: #000; }
    .group-joined { color: #4a5e3a; font-size: 12.5px; font-weight: 700; align-self: center; }

    .feed-empty { background: #fff; border: 1px dashed var(--clf-line); border-radius: 8px; padding: 28px; text-align: center; color: var(--clf-sub); font-size: 13.5px; }
</style>

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
                        <a href="/IPT_WEB_PROJECT/CampusLink/public/groups/view.php?id=<?= $group['id'] ?>" class="btn-line">View</a>
                        <?php if (!$group['membership_role']): ?>
                            <a href="/IPT_WEB_PROJECT/CampusLink/public/groups/join.php?id=<?= $group['id'] ?>" class="btn-line solid">Join</a>
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