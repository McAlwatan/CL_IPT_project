<?php
require_once __DIR__ . '/../../app/includes/db.php';
require_once __DIR__ . '/../../app/includes/auth.php';

$error = '';

if (isLoggedIn()) {
    if (empty($_SESSION['user']['university_id'])) {
        header("Location: /CL_DEV/CampusLink/public/onboarding.php");
    } else {
        header("Location: /CL_DEV/CampusLink/public/feed/index.php");
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

if ($user && password_verify($password, $user['password'])) {
    if ((int)$user['is_active'] === 0) {
        $error = "Please verify your email address before logging in.";
    } else {
        loginUser($user);
        $_SESSION['user']['university_id'] = $user['university_id'];
        if (empty($user['university_id'])) {
            header("Location: /CL_DEV/CampusLink/public/onboarding.php");
        } else {
            header("Location: /CL_DEV/CampusLink/public/feed/index.php");
        }
        exit;
    }
}

}
?>

<!DOCTYPE html>
<html>
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>CampusLink | Login</title>
        <link rel="stylesheet" href="../css/authStyles.css">
    </head>
    <body>
        <main>
            <div class="aside_1">
                <?php if (isset($_GET['registered']) && $_GET['registered'] == 1): ?>
                    <p style="color: #10b981; font-weight: 500; font-size: 14px; margin-bottom: 15px;">
                        Account created successfully! login below
                    </p>
                <?php endif; ?>

                <img class="clLogo" src="../images/favicon.png" alt="logo">
                <h2>Sign In</h2>
                <?php if ($error): ?>
                    <p style="color:red;"><?= htmlspecialchars($error) ?></p>
                <?php endif; ?>
                <form method="POST" action="login.php">
                    <input type="email" name="email" placeholder="University Email" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <button type="submit">Sign In</button>
                </form>
                <p>Need an account? <a href="register.php">Register</a></p>
            </div>
            <div class="aside_2">
                <h2>Welcome Back!</h2>
            </div>
        </main>
    </body>
</html>
