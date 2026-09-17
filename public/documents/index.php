<?php
$activePage = 'documents';
$pageTitle = 'Documents Hub';
require_once __DIR__ . '/../../app/includes/dashboard_head.php';

$userId = $_SESSION['user_id'];
$message = '';
$error = '';

// 1. Handle Document Upload Post Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['document_file'])) {
    $title = trim($_POST['title'] ?? '');
    $courseCode = strtoupper(trim($_POST['course_code'] ?? ''));
    $file = $_FILES['document_file'];

    if (empty($title) || empty($courseCode) || $file['error'] !== 0) {
        $error = "All fields are required and file must be valid.";
    } else {
        // Enforce safe file extension restrictions (PDF, DOCX, TXT)
        $fileName = $file['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExtensions = ['pdf', 'docx', 'doc', 'txt', 'zip', 'pptx'];

        if (!in_array($fileExtension, $allowedExtensions)) {
            $error = "Invalid file type. Allowed formats: PDF, DOCX, PPTX, TXT, ZIP.";
        } else {
            // Generate unique file path to prevent collision overwrites
            $uniqueName = bin2hex(random_bytes(16)) . '.' . $fileExtension;
            $uploadDir = __DIR__ . '/../uploads/documents/';
            
            // Auto-create directory branch if missing on disk
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }

            $destination = $uploadDir . $uniqueName;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                // Save metadata directly to our database table schema
                $stmt = $pdo->prepare("INSERT INTO documents (user_id, title, course_code, file_path, file_size) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$userId, $title, $courseCode, $uniqueName, $file['size']]);
                $message = "Document uploaded successfully!";
            } else {
                $error = "Failed to save file to server storage directory.";
            }
        }
    }
}

// 2. Fetch all shared resources along with author metadata
$stmt = $pdo->query("
    SELECT d.*, u.name as uploader_name 
    FROM documents d
    JOIN users u ON d.user_id = u.id
    ORDER BY d.created_at DESC
");
$documents = $stmt->fetchAll();
?>

<div class="page-header" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 40px;">
    <div>
        <h1>Documents Directory</h1>
        <p>Access study guides, notes, and past examination references shared by your campus peers.</p>
    </div>
    <button onclick="document.getElementById('upload-card').style.display='block'" class="feed-submit-btn">
        ↑ Upload Document
    </button>
</div>

<!-- Upload Form Card Panel -->
<div id="upload-card" style="display: none; background-color: #2a2a2a; border: 1px solid #3d3d3d; border-radius: 16px; padding: 24px; margin-bottom: 40px;">
    <h3 style="color: #ffffff; margin-bottom: 16px;">Share a Study Resource</h3>
    
    <form method="POST" action="index.php" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 16px;">
        <input type="text" name="title" placeholder="Document Title (e.g., Intro to Algorithms Revision Guide)" required 
               style="width:100%; padding:12px; background:#1e1e1e; border:1px solid #3d3d3d; border-radius:8px; color:white;">
        
        <input type="text" name="course_code" placeholder="Course Code (e.g., CS101)" required 
               style="width:100%; padding:12px; background:#1e1e1e; border:1px solid #3d3d3d; border-radius:8px; color:white;">
        
        <div style="background:#1e1e1e; border:1px dashed #3d3d3d; border-radius:8px; padding:20px; text-align:center; position:relative;">
            <input type="file" name="document_file" required style="cursor:pointer; opacity:1; width:100%;">
            <p style="color:#757575; font-size:12px; margin-top:8px;">Max size 25MB (PDF, DOCX, PPTX, ZIP)</p>
        </div>

        <div style="display: flex; gap: 12px; justify-content: flex-end;">
            <button type="button" onclick="document.getElementById('upload-card').style.display='none'" 
                    style="background: transparent; color: white; border: none; cursor: pointer;">Cancel</button>
            <button type="submit" class="feed-submit-btn">Publish File</button>
        </div>
    </form>
</div>

<?php if ($message): ?> <p style="color: #10b981; margin-bottom:20px; font-weight:600;"><?= $message ?></p> <?php endif; ?>
<?php if ($error): ?> <p style="color: #ef4444; margin-bottom:20px; font-weight:600;"><?= $error ?></p> <?php endif; ?>

<!-- Resource List Stream Grid -->
<div class="documents-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 24px;">
    <?php if (empty($documents)): ?>
        <div class="feed-empty" style="grid-column: 1/-1;">
            <p>No document resources have been uploaded to the directory yet.</p>
        </div>
    <?php else: ?>
        <?php foreach ($documents as $doc): ?>
            <div class="doc-card" style="background-color: #2a2a2a; border: 1px solid #3d3d3d; border-radius: 16px; padding: 24px; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <span style="background-color: #2563eb; color: white; font-size: 11px; font-weight: 700; padding: 4px 8px; border-radius: 4px; text-transform: uppercase;">
                        <?= htmlspecialchars($doc['course_code']) ?>
                    </span>
                    <h3 style="color: #ffffff; font-size: 17px; margin-top: 12px; margin-bottom: 6px; font-weight:600;"><?= htmlspecialchars($doc['title']) ?></h3>
                    <p style="color: #757575; font-size: 13px;">Shared by: <?= htmlspecialchars($doc['uploader_name']) ?></p>
                </div>
                
                <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #3d3d3d; padding-top: 16px; margin-top: 20px;">
                    <span style="color: #a0a0a0; font-size: 12px;">💾 <?= round($doc['file_size'] / 1024 / 1024, 2) ?> MB</span>
                    
                    <!-- Secure Action Trigger hitting download gateway router -->
                    <a href="/IPT_WEB_PROJECT/CampusLink/public/documents/download.php?id=<?= $doc['id'] ?>" 
                       style="background-color: #ffffff; color: #121212; text-decoration: none; font-size: 13px; font-weight: 600; padding: 8px 16px; border-radius: 6px;">
                        Download
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../app/includes/dashboard_foot.php'; ?>
