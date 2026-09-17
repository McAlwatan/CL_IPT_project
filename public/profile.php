<?php
$activePage = 'profile'; 
$pageTitle = 'Student Profile';
require_once __DIR__ . '/../app/includes/dashboard_head.php';

// 1. Resolve absolute profile ID targets
$profileId = isset($_GET['id']) ? (int)$_GET['id'] : (int)$_SESSION['user_id'];

// 2. QUERY FIX: Join tables to extract the readable university name instead of just the ID number
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
?>

<div style="background-color: #2a2a2a; border: 1px solid #3d3d3d; border-radius: 16px; padding: 40px; max-width: 560px; margin: 40px auto; color: #ffffff;">
    
    <!-- Profile Visual Avatar Section -->
    <div style="text-align: center; margin-bottom: 24px;">
        <span style="width: 80px; height: 80px; background: #ffffff; color: #121212; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-weight: 600; font-size: 32px; margin: 0 auto 16px;">
            <?= htmlspecialchars(strtoupper(substr($profileUser['name'], 0, 1))) ?>
        </span>
        <h2 style="color: #ffffff; font-size: 26px; font-weight: 600; margin-bottom: 4px;"><?= htmlspecialchars($profileUser['name']) ?></h2>
        <p style="color: #757575; font-size: 14px;"><?= htmlspecialchars($profileUser['email']) ?></p>
    </div>

    <!-- 🎓 Academic Information Dashboard Panel Block (Populated from Onboarding Phase) -->
    <div style="background-color: #1e1e1e; border: 1px solid #3d3d3d; border-radius: 12px; padding: 20px; margin-bottom: 24px;">
        <h3 style="color: #ffffff; font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px; border-bottom: 1px solid #3d3d3d; padding-bottom: 6px;">Academic Credentials</h3>
        
        <p style="font-size: 15px; color: #e0e0e0; margin-bottom: 8px;">
            <strong style="color: #cbd5e1;">Institution:</strong> 
            <?= htmlspecialchars($profileUser['university_name'] ?? 'Not Specified (Complete Onboarding)') ?>
        </p>
        
        <p style="font-size: 15px; color: #e0e0e0; margin-bottom: 8px;">
            <strong style="color: #cbd5e1;">Course Track:</strong> 
            <?= htmlspecialchars($profileUser['course'] ?? 'Not Specified') ?>
        </p>
        
        <p style="font-size: 15px; color: #e0e0e0;">
            <strong style="color: #cbd5e1;">Year of Study:</strong> 
            Year <?= htmlspecialchars($profileUser['year_of_study'] ?? '1') ?>
        </p>
    </div>

    <div style="margin-bottom: 32px;">
        <h3 style="color: #ffffff; font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px;">Interests</h3>
        <div style="display: flex; flex-wrap: wrap; gap: 8px;">
            <?php if (!empty($profileUser['interests'])): ?>
                <?php foreach (explode(', ', $profileUser['interests']) as $interest): ?>
                    <span style="background-color: #1e1e1e; border: 1px solid #3d3d3d; color: #ffffff; font-size: 13px; padding: 6px 14px; border-radius: 20px; font-weight: 500;">
                        <?= htmlspecialchars($interest) ?>
                    </span>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color: #757575; font-size: 14px; font-style: italic;">No specific interests selected yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Dynamic Contextual Action Routing Triggers -->
    <div style="text-align: center; border-top: 1px solid #3d3d3d; padding-top: 24px;">
        <?php if (!$isOwnProfile): ?>
            <a href="/IPT_WEB_PROJECT/CampusLink/public/messages/index.php?user_id=<?= $profileUser['id'] ?>" 
               style="display: inline-block; background-color: #ffffff; color: #121212; text-decoration: none; padding: 12px 32px; border-radius: 8px; font-weight: 600; font-size: 15px;">
               Message Student
            </a>
        <?php else: ?>
            <a href="/IPT_WEB_PROJECT/CampusLink/public/settings.php" 
               style="display: inline-block; background-color: #1e1e1e; color: #ffffff; text-decoration: none; padding: 10px 24px; border-radius: 8px; border: 1px solid #3d3d3d; font-size: 14px; font-weight: 500;">
               Update Profile Info
            </a>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../app/includes/dashboard_foot.php'; ?>
