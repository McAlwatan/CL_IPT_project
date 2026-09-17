<?php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/includes/db.php';
require_once __DIR__ . '/../app/includes/auth.php';
require_once __DIR__ . '/../app/middleware/require_login.php';

$userId = $_SESSION['user_id'];
$errors = [];

// 1. Fetch available seeded universities for our dropdown select parameters map menu
$uniStmt = $pdo->query("SELECT * FROM universities ORDER BY name ASC");
$universities = $uniStmt->fetchAll();

// 2. Fetch fresh user state to see if they are already onboarded //
$userStmt = $pdo->prepare("SELECT university_id, course FROM users WHERE id = ?");
$userStmt->execute([$userId]);
$userCheck = $userStmt->fetch();

// Guard gate: If already onboarded, push straight to the feed
if ($userCheck && $userCheck['university_id'] !== null && $userCheck['course'] !== null) {
    header("Location: /IPT_WEB_PROJECT/Campuslink/public/feed/index.php");
    exit;
}

// 3. Process the Form Submission data payload details
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $universityId = $_POST['university_id'] ?? '';
    $course = trim($_POST['course'] ?? '');
    $yearOfStudy = (int)($_POST['year_of_study'] ?? 1);
    
    // Arrays converted into clean text strings before committing to database columns rows
    $interestsArray = $_POST['interests'] ?? [];
    $interestsString = !empty($interestsArray) ? implode(', ', array_map('trim', $interestsArray)) : null;

    if (empty($universityId) || empty($course)) {
        $errors[] = "Please select your University and specify your Course Track.";
    }

    if (empty($errors)) {
        $update = $pdo->prepare("
            UPDATE users 
            SET university_id = ?, course = ?, year_of_study = ?, interests = ? 
            WHERE id = ?
        ");
        
        if ($update->execute([$universityId, $course, $yearOfStudy, $interestsString, $userId])) {
            // Update session tracking parameters maps matching updated credentials states
            $_SESSION['user']['university_id'] = $universityId;
            
            // Onboarding complete! Fire a redirect straight into the home feed timeline stream
            header("Location: /IPT_WEB_PROJECT/Campuslink/public/feed/index.php");
            exit;
        } else {
            $errors[] = "Failed to update academic profile credentials mapping paths.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CampusLink — Onboarding</title>
    <link rel="preconnect" href="https://googleapis.com">
    <link rel="preconnect" href="https://gstatic.com" crossorigin>
    <link href="https://googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'DM Sans', sans-serif; }
        body { background-color: #121212; color: #ffffff; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; }
        .wizard-card { background-color: #1e1e1e; border: 1px solid #3d3d3d; border-radius: 24px; padding: 40px; width: 100%; max-width: 540px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        h2 { font-size: 28px; font-weight: 600; margin-bottom: 8px; letter-spacing: -0.5px; }
        .subtitle { color: #a0a0a0; font-size: 15px; margin-bottom: 32px; line-height: 1.5; }
        label { display: block; color: #cbd5e1; font-size: 14px; font-weight: 500; margin-bottom: 8px; }
        select, input[type="text"] { width: 100%; padding: 14px; background-color: #121212; border: 1px solid #3d3d3d; border-radius: 8px; color: #ffffff; font-size: 15px; margin-bottom: 24px; outline: none; transition: border 0.2s; }
        select:focus, input[type="text"]:focus { border-color: #ffffff; }
        .interests-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 32px; }
        .interest-item { display: flex; align-items: center; gap: 10px; background-color: #121212; border: 1px solid #3d3d3d; padding: 12px; border-radius: 8px; cursor: pointer; }
        .interest-item input { cursor: pointer; accent-color: #ffffff; }
        .interest-item span { font-size: 14px; color: #e0e0e0; }
        .submit-btn { width: 100%; padding: 14px; background-color: #ffffff; color: #121212; border: none; border-radius: 8px; font-weight: 600; font-size: 16px; cursor: pointer; transition: transform 0.1s ease; }
        .submit-btn:active { transform: scale(0.99); }
        .error-msg { color: #ef4444; font-size: 14px; margin-bottom: 16px; font-weight: 500; }
    </style>
</head>
<body>

<div class="wizard-card">
    <h2>Complete your profile</h2>
    <p class="subtitle">Tell us a bit about your academic track context so we can tailor your campus interaction dashboard experiences loop.</p>

    <?php foreach ($errors as $error): ?>
        <p class="error-msg">⚠️ <?= htmlspecialchars($error) ?></p>
    <?php endforeach; ?>

    <form method="POST" action="onboarding.php">
        
        <!-- Dropdown mapping to our seed database records -->
        <label for="university_id">Select Your University Campus</label>
        <select name="university_id" id="university_id" required>
            <option value="" disabled selected>Choose your campus room...</option>
            <?php foreach ($universities as $uni): ?>
                <option value="<?= $uni['id'] ?>"><?= htmlspecialchars($uni['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="course">Course Major / Track</label>
        <input type="text" name="course" id="course" placeholder="e.g., Bachelor of Computer Science" required>

        <label for="year_of_study">Current Academic Year</label>
        <select name="year_of_study" id="year_of_study" required>
            <option value="1">Year 1 (Freshman)</option>
            <option value="2">Year 2 (Sophomore)</option>
            <option value="3">Year 3 (Junior)</option>
            <option value="4">Year 4 (Senior)</option>
            <option value="5">Year 5+</option>
        </select>

        <label>Select Areas of Interest</label>
        <div class="interests-grid">
            <label class="interest-item">
                <input type="checkbox" name="interests[]" value="Software Engineering">
                <span>Coding & Dev</span>
            </label>
            <label class="interest-item">
                <input type="checkbox" name="interests[]" value="UI/UX Design">
                <span>UI/UX Design</span>
            </label>
            <label class="interest-item">
                <input type="checkbox" name="interests[]" value="Sports Clubs">
                <span>Campus Sports</span>
            </label>
            <label class="interest-item">
                <input type="checkbox" name="interests[]" value="Research & Writing">
                <span>Research Hubs</span>
            </label>
        </div>

        <button type="submit" class="submit-btn">Complete Setup & Enter Hub</button>
    </form>
</div>

<script>
    document.getElementById('university_id').addEventListener('focus', function() {
        this.setAttribute('size', '6'); // Transforms dropdown into an elegant expandable viewport tray when focused
    });
    document.getElementById('university_id').addEventListener('blur', function() {
        this.removeAttribute('size'); // Returns to a clean compact component state on select focus out
    });
    document.getElementById('university_id').addEventListener('change', function() {
        this.removeAttribute('size');
    });
</script>


</body>
</html>
