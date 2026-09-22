<?php
$activePage = 'settings';
$pageTitle = 'Account Settings';
require_once __DIR__ . '/../app/includes/dashboard_head.php';

$userId = $_SESSION['user_id'];
$profileSuccess = '';
$profileError = '';
$securitySuccess = '';
$securityError = '';

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$userSettings = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['update_profile'])) {
        $headline = trim($_POST['headline'] ?? '');
        $bio = trim($_POST['bio'] ?? '');

        $update = $pdo->prepare("UPDATE users SET headline = ?, bio = ? WHERE id = ?");
        if ($update->execute([$headline, $bio, $userId])) {
            $profileSuccess = "Profile details updated successfully!";
            $userSettings['headline'] = $headline;
            $userSettings['bio'] = $bio;
        } else {
            $profileError = "Failed to update profile statistics.";
        }
    }

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


<div class="cls-header">
    <h1>Account settings</h1>
    <p>Manage your campus profile information and login security.</p>
</div>

<div class="settings-grid">

    <section class="cls-panel">
        <h3>Profile card info</h3>
        <p>This content is visible to other students in directory searches.</p>

        <?php if ($profileSuccess): ?><div class="cls-flash ok"><?= $profileSuccess ?></div><?php endif; ?>
        <?php if ($profileError): ?><div class="cls-flash err"><?= $profileError ?></div><?php endif; ?>

        <form method="POST" action="settings.php">
            <input type="hidden" name="update_profile" value="1">

            <div class="cls-field">
                <label>Headline</label>
                <input type="text" name="headline" value="<?= htmlspecialchars($userSettings['headline'] ?? '') ?>" placeholder="e.g., Computer Science sophomore | UI enthusiast">
            </div>

            <div class="cls-field">
                <label>Bio / description</label>
                <textarea name="bio" placeholder="Tell campus peers a bit about yourself, your tracks, or your projects..."><?= htmlspecialchars($userSettings['bio'] ?? '') ?></textarea>
            </div>

            <div class="cls-panel-actions">
                <button type="submit" class="btn-primary">Save changes</button>
            </div>
        </form>
    </section>

    <section class="cls-panel">
        <h3>Update password</h3>
        <p>Make sure your account uses a secure password.</p>

        <?php if ($securitySuccess): ?><div class="cls-flash ok"><?= $securitySuccess ?></div><?php endif; ?>
        <?php if ($securityError): ?><div class="cls-flash err"><?= $securityError ?></div><?php endif; ?>

        <form method="POST" action="settings.php">
            <input type="hidden" name="update_security" value="1">

            <div class="cls-field">
                <label>Current password</label>
                <input type="password" name="current_password" required>
            </div>

            <div class="cls-field">
                <label>New password</label>
                <input type="password" name="new_password" required>
            </div>

            <div class="cls-field">
                <label>Confirm new password</label>
                <input type="password" name="confirm_password" required>
            </div>

            <div class="cls-panel-actions">
                <button type="submit" class="btn-outline">Update password</button>
            </div>
        </form>
    </section>

</div>

<?php require_once __DIR__ . '/../app/includes/dashboard_foot.php'; ?>