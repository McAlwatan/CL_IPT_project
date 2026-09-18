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

<style>
    :root {
        --clf-ink: #1c1c1c;
        --clf-sub: #767676;
        --clf-line: #e4e4e4;
        --clf-bg-soft: #f6f6f4;
        --clf-accent: #b8441f;
    }

    .cld-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; gap: 16px; }
    .cld-header h1 { font-size: 22px; font-weight: 700; margin: 0 0 4px; letter-spacing: -0.01em; color: var(--clf-ink); }
    .cld-header p { color: var(--clf-sub); margin: 0; font-size: 13.5px; }

    .btn-primary {
        background: var(--clf-ink); color: #fff; border: none; border-radius: 6px;
        padding: 8px 16px; font-size: 13px; font-weight: 600; cursor: pointer; white-space: nowrap;
    }
    .btn-primary:hover { background: #000; }

    .cld-modal {
        display: none; background: #fff; border: 1px solid var(--clf-line);
        border-radius: 8px; padding: 20px; margin-bottom: 28px;
    }
    .cld-modal h3 { font-size: 15px; font-weight: 700; margin: 0 0 14px; color: var(--clf-ink); }
    .cld-modal form { display: flex; flex-direction: column; gap: 12px; }
    .cld-modal input[type="text"] {
        width: 100%; padding: 10px 12px; background: #fff; border: 1px solid var(--clf-line);
        border-radius: 6px; color: var(--clf-ink); font-family: inherit; font-size: 13.5px; outline: none;
    }
    .cld-modal input[type="text"]:focus { border-color: var(--clf-accent); }
    .cld-dropzone {
        background: var(--clf-bg-soft); border: 1px dashed var(--clf-line); border-radius: 6px;
        padding: 16px; text-align: center;
    }
    .cld-dropzone input[type="file"] { width: 100%; cursor: pointer; font-size: 13px; }
    .cld-dropzone p { color: var(--clf-sub); font-size: 12px; margin: 8px 0 0; }
    .cld-modal-actions { display: flex; gap: 10px; justify-content: flex-end; }
    .btn-plain { background: none; border: none; color: var(--clf-sub); cursor: pointer; font-size: 13px; font-family: inherit; }
    .btn-plain:hover { color: var(--clf-ink); }

    .cld-flash { font-size: 13.5px; font-weight: 600; margin-bottom: 18px; padding: 9px 12px; border-radius: 6px; }
    .cld-flash.ok { color: #2e6b45; background: #eaf5ee; border: 1px solid #cfe8d8; }
    .cld-flash.err { color: #9c3b1e; background: #fbeae5; border: 1px solid #eccabf; }

    .clf-avatar {
        width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center;
        justify-content: center; font-weight: 600; font-size: 11.5px; color: #fff; flex-shrink: 0;
    }
    .avatar-rust { background: #a8481f; } .avatar-ink-blue { background: #2c3e5c; }
    .avatar-moss { background: #4a5e3a; } .avatar-plum { background: #5c3a54; }

    .documents-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(270px, 1fr)); gap: 14px; }

    .doc-card {
        background: #fff; border: 1px solid var(--clf-line); border-radius: 8px; padding: 16px;
        display: flex; flex-direction: column; justify-content: space-between;
    }
    .doc-badge {
        display: inline-block; background: #24406b; color: #fff; font-size: 10px; font-weight: 700;
        padding: 3px 8px; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.03em;
    }
    .doc-card h3 { color: var(--clf-ink); font-size: 15px; font-weight: 600; margin: 10px 0 8px; }
    .doc-uploader { display: flex; align-items: center; gap: 8px; }
    .doc-uploader span.name { color: var(--clf-sub); font-size: 12.5px; }

    .doc-card-foot {
        display: flex; justify-content: space-between; align-items: center;
        border-top: 1px solid var(--clf-line); padding-top: 12px; margin-top: 16px;
    }
    .doc-size { color: var(--clf-sub); font-size: 12px; }
    .btn-line {
        border: 1px solid var(--clf-line); background: #fff; color: var(--clf-ink); text-decoration: none;
        font-size: 12.5px; font-weight: 600; padding: 6px 13px; border-radius: 6px;
    }
    .btn-line:hover { border-color: var(--clf-ink); }
    .btn-line.solid { background: var(--clf-ink); color: #fff; border-color: var(--clf-ink); }
    .btn-line.solid:hover { background: #000; }

    .feed-empty { background: #fff; border: 1px dashed var(--clf-line); border-radius: 8px; padding: 28px; text-align: center; color: var(--clf-sub); font-size: 13.5px; }
</style>

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