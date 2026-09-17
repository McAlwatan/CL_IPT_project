<?php
$activePage = 'groups';
$pageTitle = 'Groups Hub';
require_once __DIR__ . '/../../app/includes/dashboard_head.php';

$userId = $_SESSION['user_id'];

// Handle group creation quickly if form is submitted to this same file
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_group'])) {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (!empty($name)) {
        $pdo->beginTransaction();
        try {
            // 1. Insert Group definition
            $stmt = $pdo->prepare("INSERT INTO groups (name, description, creator_id) VALUES (?, ?, ?)");
            $stmt->execute([$name, $description, $userId]);
            $groupId = $pdo->lastInsertId();

            // 2. Automatically make the creator an Admin member of the group
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

// Fetch all available groups alongside a flag indicating if the current user has joined
$stmt = $pdo->prepare("
    SELECT g.*, 
           (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as total_members,
           (SELECT role FROM group_members WHERE group_id = g.id AND user_id = ?) as membership_role
    FROM groups g 
    ORDER BY g.created_at DESC
");
$stmt->execute([$userId]);
$groups = $stmt->fetchAll();
?>

<div class="page-header" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 40px;">
    <div>
        <h1>Campus Groups</h1>
        <p>Join communities, modules study teams, and interest clubs.</p>
    </div>
    <!-- Simple trigger button to reveal creation card -->
    <button onclick="document.getElementById('create-modal').style.display='block'" class="feed-submit-btn">
        + Create Group
    </button>
</div>

<!-- Hidden Creation Form Overlay Sheet -->
<div id="create-modal" style="display: none; background-color: #2a2a2a; border: 1px solid #3d3d3d; border-radius: 16px; padding: 24px; margin-bottom: 40px;">
    <h3 style="color: #ffffff; margin-bottom: 16px;">Launch a New Community</h3>
    <form method="POST" action="index.php" style="display: flex; flex-direction: column; gap: 16px;">
        <input type="hidden" name="create_group" value="1">
        <input type="text" name="name" placeholder="Group Name (e.g., PHP Developers Club)" required 
               style="width:100%; padding:12px; background:#1e1e1e; border:1px solid #3d3d3d; border-radius:8px; color:white;">
        <textarea name="description" placeholder="What is this community group about?" required 
                  style="width:100%; height:80px; padding:12px; background:#1e1e1e; border:1px solid #3d3d3d; border-radius:8px; color:white; resize:none;"></textarea>
        <div style="display: flex; gap: 12px; justify-content: flex-end;">
            <button type="button" onclick="document.getElementById('create-modal').style.display='none'" 
                    style="background: transparent; color: white; border: none; cursor: pointer;">Cancel</button>
            <button type="submit" class="feed-submit-btn">Launch Group</button>
        </div>
    </form>
</div>

<!-- Group Listings Directory Grid -->
<div class="groups-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 24px;">
    <?php if (empty($groups)): ?>
        <div class="feed-empty" style="grid-column: 1/-1;">
            <p>No active groups found on campus yet.</p>
        </div>
    <?php else: ?>
        <?php foreach ($groups as $group): ?>
            <div class="group-card" style="background-color: #2a2a2a; border: 1px solid #3d3d3d; border-radius: 16px; padding: 24px; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <h3 style="color: #ffffff; font-size: 18px; margin-bottom: 8px;"><?= htmlspecialchars($group['name']) ?></h3>
                    <p style="color: #a0a0a0; font-size: 14px; line-height: 1.5; margin-bottom: 16px; min-height: 42px;">
                        <?= htmlspecialchars(substr($group['description'], 0, 90)) ?><?= strlen($group['description']) > 90 ? '...' : '' ?>
                    </p>
                </div>
                
                <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #3d3d3d; padding-top: 16px; margin-top: 12px;">
                    <span style="color: #757575; font-size: 13px;">👥 <?= $group['total_members'] ?> members</span>
                    
                    <div style="display: flex; gap: 8px;">
                        <a href="/IPT_WEB_PROJECT/CampusLink/public/groups/view.php?id=<?= $group['id'] ?>" 
                           style="color: #ffffff; text-decoration: none; font-size: 14px; font-weight: 500; padding: 8px 14px; border: 1px solid #3d3d3d; border-radius: 6px;">
                            View
                        </a>
                        
                        <?php if (!$group['membership_role']): ?>
                            <a href="/IPT_WEB_PROJECT/CampusLink/public/groups/join.php?id=<?= $group['id'] ?>" 
                               style="background-color: #ffffff; color: #121212; text-decoration: none; font-size: 14px; font-weight: 600; padding: 8px 14px; border-radius: 6px;">
                                Join
                            </a>
                        <?php else: ?>
                            <span style="color: #10b981; font-size: 13px; font-weight: 600; align-self: center; padding-left: 4px;">
                                ✓ <?= ucfirst($group['membership_role']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../app/includes/dashboard_foot.php'; ?>
