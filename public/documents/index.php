<?php
$activePage = 'documents';
$pageTitle = 'Documents Hub';
require_once __DIR__ . '/../../app/includes/dashboard_head.php';

$userId = $_SESSION['user_id'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['document_file'])) {
    $title = trim($_POST['title'] ?? '');
    $courseCode = strtoupper(trim($_POST['course_code'] ?? ''));
    $file = $_FILES['document_file'];

    if (empty($title) || empty($courseCode) || $file['error'] !== 0) {
        $error = "All fields are required and file must be valid.";
    } else {
        $fileName = $file['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExtensions = ['pdf', 'docx', 'doc', 'txt', 'zip', 'pptx'];

        if (!in_array($fileExtension, $allowedExtensions)) {
            $error = "Invalid file type. Allowed formats: PDF, DOCX, PPTX, TXT, ZIP.";
        } else {
            $uniqueName = bin2hex(random_bytes(16)) . '.' . $fileExtension;
            $uploadDir = __DIR__ . '/../uploads/documents/';

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }

            $destination = $uploadDir . $uniqueName;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $stmt = $pdo->prepare("INSERT INTO documents (user_id, title, course_code, file_path, file_size) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$userId, $title, $courseCode, $uniqueName, $file['size']]);
                $message = "Document uploaded successfully!";
            } else {
                $error = "Failed to save file to server storage directory.";
            }
        }
    }
}

$stmt = $pdo->query("
    SELECT d.*, u.name as uploader_name 
    FROM documents d
    JOIN users u ON d.user_id = u.id
    ORDER BY d.created_at DESC
");
$documents = $stmt->fetchAll();

function clInitial($name) {
    $name = trim((string)$name);
    if ($name === '') return 'S';
    $parts = preg_split('/\s+/', $name);
    $first = mb_substr($parts[0], 0, 1);
    $second = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';
    return htmlspecialchars(mb_strtoupper($first . $second));
}
function clAvatarClass($seed) {
    $palette = ['rust', 'ink-blue', 'moss', 'plum'];
    return 'avatar-' . $palette[crc32((string)$seed) % count($palette)];
}
?>


<div class="cld-header">
    <div>
        <h1>Documents directory</h1>
        <p>Access study guides, notes, and past exam references shared by your campus peers.</p>
    </div>
    <button onclick="document.getElementById('upload-card').style.display='block'" class="btn-primary">↑ Upload document</button>
</div>

<div id="upload-card" class="cld-modal">
    <h3>Share a study resource</h3>
    <form method="POST" action="index.php" enctype="multipart/form-data">
        <input type="text" name="title" placeholder="Document title (e.g., Intro to Algorithms revision guide)" required>
        <input type="text" name="course_code" placeholder="Course code (e.g., CS101)" required>
        <div class="cld-dropzone">
            <input type="file" name="document_file" required>
            <p>Max size 25MB (PDF, DOCX, PPTX, TXT, ZIP)</p>
        </div>
        <div class="cld-modal-actions">
            <button type="button" onclick="document.getElementById('upload-card').style.display='none'" class="btn-plain">Cancel</button>
            <button type="submit" class="btn-primary">Publish file</button>
        </div>
    </form>
</div>

<?php if ($message): ?><div class="cld-flash ok"><?= $message ?></div><?php endif; ?>
<?php if ($error): ?><div class="cld-flash err"><?= $error ?></div><?php endif; ?>

<div class="documents-grid">
    <?php if (empty($documents)): ?>
        <div class="feed-empty" style="grid-column: 1/-1;">
            <p>No document resources have been uploaded to the directory yet.</p>
        </div>
    <?php else: ?>
        <?php foreach ($documents as $doc): ?>
            <div class="doc-card">
                <div>
                    <span class="doc-badge"><?= htmlspecialchars($doc['course_code']) ?></span>
                    <h3><?= htmlspecialchars($doc['title']) ?></h3>
                    <div class="doc-uploader">
                        <span class="clf-avatar <?= clAvatarClass($doc['uploader_name']) ?>"><?= clInitial($doc['uploader_name']) ?></span>
                        <span class="name"><?= htmlspecialchars($doc['uploader_name']) ?></span>
                    </div>
                </div>
                <div class="doc-card-foot">
                    <span class="doc-size"><i class="fa-solid fa-download"></i> <?= round($doc['file_size'] / 1024 / 1024, 2) ?> MB</span>
                    <a href="/IPT_WEB_PROJECT/CampusLink/public/documents/download.php?id=<?= $doc['id'] ?>" class="btn-line solid">Download</a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../app/includes/dashboard_foot.php'; ?>