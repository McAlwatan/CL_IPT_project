<?php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/includes/db.php';
require_once __DIR__ . '/../app/includes/auth.php';
require_once __DIR__ . '/../app/middleware/require_login.php';

$userId = $_SESSION['user_id'];
$errors = [];

$uniStmt = $pdo->query("SELECT * FROM universities ORDER BY name ASC");
$universities = $uniStmt->fetchAll();

$userStmt = $pdo->prepare("SELECT university_id, course FROM users WHERE id = ?");
$userStmt->execute([$userId]);
$userCheck = $userStmt->fetch();

if ($userCheck && $userCheck['university_id'] !== null && $userCheck['course'] !== null) {
    header("Location: /IPT_WEB_PROJECT/CampusLink/public/feed/index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $universityId = $_POST['university_id'] ?? '';
    $course = trim($_POST['course'] ?? '');
    $yearOfStudy = (int)($_POST['year_of_study'] ?? 1);

    $interestsArray = $_POST['interests'] ?? [];
    $interestsString = !empty($interestsArray) ? implode(', ', array_map('trim', $interestsArray)) : null;

    if (empty($universityId) || empty($course)) {
        $errors[] = "Please select your university and specify your course track.";
    }

    if (empty($errors)) {
        $update = $pdo->prepare("
            UPDATE users 
            SET university_id = ?, course = ?, year_of_study = ?, interests = ? 
            WHERE id = ?
        ");

        if ($update->execute([$universityId, $course, $yearOfStudy, $interestsString, $userId])) {
            $_SESSION['user']['university_id'] = $universityId;
            header("Location: /IPT_WEB_PROJECT/CampusLink/public/feed/index.php");
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --clf-ink: #1c1c1c;
            --clf-sub: #767676;
            --clf-line: #e4e4e4;
            --clf-bg-soft: #f6f6f4;
            --clf-accent: #b8441f;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'DM Sans', sans-serif; }
        body {
            background-color: var(--clf-bg-soft); color: var(--clf-ink);
            display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px;
        }
        .wizard-card {
            background-color: #fff; border: 1px solid var(--clf-line); border-radius: 10px;
            padding: 36px; width: 100%; max-width: 520px;
        }
        h2 { font-size: 23px; font-weight: 700; margin-bottom: 6px; letter-spacing: -0.01em; }
        .subtitle { color: var(--clf-sub); font-size: 13.5px; margin-bottom: 26px; line-height: 1.5; }

        label { display: block; color: var(--clf-ink); font-size: 13px; font-weight: 600; margin-bottom: 7px; }
        select, input[type="text"] {
            width: 100%; padding: 11px 12px; background-color: #fff; border: 1px solid var(--clf-line);
            border-radius: 6px; color: var(--clf-ink); font-size: 14px; margin-bottom: 20px; outline: none;
            font-family: inherit;
        }
        select:focus, input[type="text"]:focus { border-color: var(--clf-accent); }

        .interests-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 26px; }
        .interest-item {
            display: flex; align-items: center; gap: 9px; background-color: #fff; border: 1px solid var(--clf-line);
            padding: 10px 12px; border-radius: 6px; cursor: pointer;
        }
        .interest-item:has(input:checked) { border-color: var(--clf-ink); background: var(--clf-bg-soft); }
        .interest-item input { cursor: pointer; accent-color: var(--clf-ink); }
        .interest-item span { font-size: 13.5px; color: var(--clf-ink); }

        .submit-btn {
            width: 100%; padding: 12px; background-color: var(--clf-ink); color: #fff; border: none;
            border-radius: 6px; font-weight: 600; font-size: 14.5px; cursor: pointer;
        }
        .submit-btn:hover { background-color: #000; }

        .error-msg {
            color: #9c3b1e; background: #fbeae5; border: 1px solid #eccabf; border-radius: 6px;
            padding: 9px 12px; font-size: 13px; margin-bottom: 14px; font-weight: 600;
        }
    </style>
</head>
<body>

<div class="wizard-card">
    <h2>Complete your profile</h2>
    <p class="subtitle">Tell us a bit about your academic track so we can tailor your campus experience.</p>

    <?php foreach ($errors as $error): ?>
        <p class="error-msg">⚠️ <?= htmlspecialchars($error) ?></p>
    <?php endforeach; ?>

    <form method="POST" action="onboarding.php">
        <label for="university_id">Select your university</label>
        <select name="university_id" id="university_id" required>
            <option value="" disabled selected>Choose your campus...</option>
            <?php foreach ($universities as $uni): ?>
                <option value="<?= $uni['id'] ?>"><?= htmlspecialchars($uni['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="course">Course major / track</label>
        <input type="text" name="course" id="course" placeholder="e.g., Bachelor of Computer Science" required>

        <label for="year_of_study">Current academic year</label>
        <select name="year_of_study" id="year_of_study" required>
            <option value="1">Year 1 (Freshman)</option>
            <option value="2">Year 2 (Sophomore)</option>
            <option value="3">Year 3 (Junior)</option>
            <option value="4">Year 4 (Senior)</option>
            <option value="5">Year 5+</option>
        </select>

        <label>Select areas of interest</label>
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

        <button type="submit" class="submit-btn">Complete setup & enter hub</button>
    </form>
</div>

</body>
</html>