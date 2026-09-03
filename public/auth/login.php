<?php
require_once __DIR__ . '/../../app/includes/db.php';
require_once __DIR__ . '/../../app/includes/auth.php';

$error = '';

if (isLoggedIn()) {
    header("Location: /feed/index.php");
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
            header("Location: /IPT_WEB_PROJECT/CampusLink/public/feed/index.php");
            exit;
        }
    } else {
        $error = "Invalid email or password.";
    }
}

?>

<!DOCTYPE html>
<html>
    <head>
        <title>CampusLink - Login</title>
        <link rel="stylesheet" href="../css/styles.css">
    </head>
    <body>
        <main>
            <div class="aside_1">
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
