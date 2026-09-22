<?php
require_once __DIR__ . '/../../app/includes/db.php';
require_once __DIR__ . '/../../app/includes/auth.php';

$errors = []; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        $errors[] = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $errors[] = "An account with this email already exists.";
    }

    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $insert = $pdo->prepare("
            INSERT INTO users (name, email, password, university_id, verification_token, is_active) 
            VALUES (?, ?, ?, NULL, NULL, 1)
        ");
        
        if ($insert->execute([$name, $email, $hashedPassword])) {
            header("Location: /IPT_WEB_PROJECT/CampusLink/public/auth/login.php?registered=1");
            exit;
        } else {
            $errors[] = "Something went wrong during account registration. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>CampusLink | Register</title>
        <link rel="stylesheet" href="../css/authStyles.css">
    </head>
    <body>
        <main>
            <div class="aside_1">
                <img class="clLogo" src="../images/favicon.png" alt="logo">
                <h2>Sign Up</h2>
                <?php foreach ($errors as $error): ?>
                    <p style="color:red; font-size: 14px; margin-bottom: 10px;"><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
                <form method="POST" action="register.php">
                    <input type="text" name="name" placeholder="Full Name" required>
                    <input type="email" name="email" placeholder="student@university.edu" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <button type="submit">Sign Up</button>
                </form>
                <p>Already have an account? <a href="login.php">Log In</a></p>
            </div>
            <div class="aside_2">
                <h2>Welcome to CampusLink</h2>
            </div>
        </main>
    </body>
</html>
