<?php
require_once __DIR__ . '/../../app/includes/db.php';
require_once __DIR__ . '/../../app/includes/auth.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // 1. Basic validation
    if (empty($name) || empty($email) || empty($password)) {
        $errors[] = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    // 2. Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $errors[] = "An account with this email already exists.";
    }

    // 3. Register user if no errors
    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $verificationToken = bin2hex(random_bytes(32));

        // We pass NULL or a default value for university_id since we removed the domain check
        $insert = $pdo->prepare("
            INSERT INTO users (name, email, password, university_id, verification_token, is_active) 
            VALUES (?, ?, ?, NULL, ?, 0)
        ");
        
        if ($insert->execute([$name, $email, $hashedPassword, $verificationToken])) {
            $projectName = '/IPT_WEB_PROJECT/CampusLink/public';
            $verifyLink = "http://" . $_SERVER['HTTP_HOST'] . $projectName . "/auth/verify.php?token=" . $verificationToken;

            
            die("Registration successful! Check email to verify account. <br> Dev Link: <a href='$verifyLink'>$verifyLink</a>");
        }
    }
}
?>


<!DOCTYPE html>
<html>
    <head>
        <title>CampusLink - Register</title>
    </head>
    <body>
        <h2>Sign Up</h2>
        <?php foreach ($errors as $error): ?>
            <p style="color:red;"><?= htmlspecialchars($error) ?></p>
        <?php endforeach; ?>
        <form method="POST" action="register.php">
            <input type="text" name="name" placeholder="Full Name" required><br><br>
            <input type="email" name="email" placeholder="student@university.edu" required><br><br>
            <input type="password" name="password" placeholder="Password" required><br><br>
            <button type="submit">Sign Up</button>
        </form>
        <p>Already have an account? <a href="login.php">Log In</a></p>
    </body>
</html>