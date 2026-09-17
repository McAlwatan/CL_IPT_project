<?php
$activePage = 'settings';
$pageTitle = 'Account Settings';
require_once __DIR__ . '/../app/includes/dashboard_head.php';

$userId = $_SESSION['user_id'];
$profileSuccess = '';
$profileError = '';
$securitySuccess = '';
$securityError = '';

// Fetch fresh, up-to-date data for the logged-in student
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$userSettings = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. HANDLE PROFILE UPDATES ACTION
    if (isset($_POST['update_profile'])) {
        $headline = trim($_POST['headline'] ?? '');
        $bio = trim($_POST['bio'] ?? '');

        $update = $pdo->prepare("UPDATE users SET headline = ?, bio = ? WHERE id = ?");
        if ($update->execute([$headline, $bio, $userId])) {
            $profileSuccess = "Profile details updated successfully!";
            // Refresh local variable state
            $userSettings['headline'] = $headline;
            $userSettings['bio'] = $bio;
        } else {
            $profileError = "Failed to update profile statistics.";
        }
    }

    // 2. HANDLE SECURE PASSWORD CHANGE ACTION
    if (isset($_POST['update_security'])) {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $securityError = "All password fields are mandatory.";
        } elseif ($newPassword !== $confirmPassword) {
            $securityError = "New passwords do not match.";
        } elseif (strlen($newPassword) < 6) {
            $securityError = "New password must be at least 6 characters long.";
        } else {
            // Verify current password match against database hash tracking
            if (password_verify($currentPassword, $userSettings['password'])) {
                $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
                $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update->execute([$newHash, $userId]);
                $securitySuccess = "Password updated securely!";
            } else {
                $securityError = "Your current password statement is incorrect.";
            }
        }
    }
}
?>

<div class="page-header" style="margin-bottom: 40px;">
    <h1>Account Settings</h1>
    <p>Manage your campus profile information details and login security settings parameters.</p>
</div>

<div class="settings-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 32px; align-items: start;">

    <!-- 👤 PANEL 1: Public Profile Details -->
    <section style="background-color: #2a2a2a; border: 1px solid #3d3d3d; border-radius: 16px; padding: 30px;">
        <h3 style="color: #ffffff; font-size: 18px; font-weight: 600; margin-bottom: 6px;">Profile Card Info</h3>
        <p style="color: #757575; font-size: 13px; margin-bottom: 24px;">This content is visible to other student directory searches.</p>

        <?php if ($profileSuccess): ?> <p style="color: #10b981; font-size: 14px; font-weight: 600; margin-bottom: 16px;"><?= $profileSuccess ?></p> <?php endif; ?>
        <?php if ($profileError): ?> <p style="color: #ef4444; font-size: 14px; font-weight: 600; margin-bottom: 16px;"><?= $profileError ?></p> <?php endif; ?>

        <form method="POST" action="settings.php" style="display: flex; flex-direction: column; gap: 20px;">
            <input type="hidden" name="update_profile" value="1">
            
            <div>
                <label style="color: #cbd5e1; font-size: 14px; display: block; margin-bottom: 8px; font-weight: 500;">Headline</label>
                <input type="text" name="headline" value="<?= htmlspecialchars($userSettings['headline'] ?? '') ?>" placeholder="e.g., Computer Science sophomore | UI enthusiast" 
                       style="width:100%; padding:12px; background:#1e1e1e; border:1px solid #3d3d3d; border-radius:8px; color:white; font-size:15px;">
            </div>

            <div>
                <label style="color: #cbd5e1; font-size: 14px; display: block; margin-bottom: 8px; font-weight: 500;">Bio / Description</label>
                <textarea name="bio" placeholder="Tell campus peers a bit about yourself, your tracks, or your projects..." 
                          style="width:100%; height:120px; padding:12px; background:#1e1e1e; border:1px solid #3d3d3d; border-radius:8px; color:white; font-size:15px; resize:none; line-height:1.5;"><?= htmlspecialchars($userSettings['bio'] ?? '') ?></textarea>
            </div>

            <button type="submit" class="feed-submit-btn" style="align-self: flex-end;">Save Changes</button>
        </form>
    </section>

    <!-- 🔒 PANEL 2: Password & Authentication Credentials -->
    <section style="background-color: #2a2a2a; border: 1px solid #3d3d3d; border-radius: 16px; padding: 30px;">
        <h3 style="color: #ffffff; font-size: 18px; font-weight: 600; margin-bottom: 6px;">Update Password</h3>
        <p style="color: #757575; font-size: 13px; margin-bottom: 24px;">Ensure your account uses a secure password phrase structure.</p>

        <?php if ($securitySuccess): ?> <p style="color: #10b981; font-size: 14px; font-weight: 600; margin-bottom: 16px;"><?= $securitySuccess ?></p> <?php endif; ?>
        <?php if ($securityError): ?> <p style="color: #ef4444; font-size: 14px; font-weight: 600; margin-bottom: 16px;"><?= $securityError ?></p> <?php endif; ?>

        <form method="POST" action="settings.php" style="display: flex; flex-direction: column; gap: 20px;">
            <input type="hidden" name="update_security" value="1">
            
            <div>
                <label style="color: #cbd5e1; font-size: 14px; display: block; margin-bottom: 8px; font-weight: 500;">Current Password</label>
                <input type="password" name="current_password" required
                       style="width:100%; padding:12px; background:#1e1e1e; border:1px solid #3d3d3d; border-radius:8px; color:white; font-size:15px;">
            </div>

            <div>
                <label style="color: #cbd5e1; font-size: 14px; display: block; margin-bottom: 8px; font-weight: 500;">New Password</label>
                <input type="password" name="new_password" required
                       style="width:100%; padding:12px; background:#1e1e1e; border:1px solid #3d3d3d; border-radius:8px; color:white; font-size:15px;">
            </div>

            <div>
                <label style="color: #cbd5e1; font-size: 14px; display: block; margin-bottom: 8px; font-weight: 500;">Confirm New Password</label>
                <input type="password" name="confirm_password" required
                       style="width:100%; padding:12px; background:#1e1e1e; border:1px solid #3d3d3d; border-radius:8px; color:white; font-size:15px;">
            </div>

            <button type="submit" class="feed-submit-btn" style="align-self: flex-end; background-color: #ffffff; color: #121212;">Update Password</button>
        </form>
    </section>

</div>

<?php require_once __DIR__ . '/../app/includes/dashboard_foot.php'; ?>
