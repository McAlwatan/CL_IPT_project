<?php
$activePage = 'marketplace';
$pageTitle = 'Campus Marketplace';
require_once __DIR__ . '/../../app/includes/dashboard_head.php';

$userId = $_SESSION['user_id'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['publish_item'])) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = trim($_POST['price'] ?? '');

    $imageUploaded = isset($_FILES['item_image']) && $_FILES['item_image']['error'] === 0;
    $imageName = null;

    if (empty($title) || empty($description) || empty($price)) {
        $error = "All textual fields are required.";
    } elseif (!is_numeric($price) || $price < 0) {
        $error = "Please provide a valid price figure value.";
    } else {
        if ($imageUploaded) {
            $file = $_FILES['item_image'];
            $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($fileExtension, $allowedExtensions)) {
                $error = "Invalid format. Images must be JPG, JPEG, PNG, or WEBP.";
            } else {
                $imageName = bin2hex(random_bytes(16)) . '.' . $fileExtension;
                $uploadDir = __DIR__ . '/../uploads/post-images/';

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0775, true);
                }

                if (!move_uploaded_file($file['tmp_name'], $uploadDir . $imageName)) {
                    $imageName = null;
                }
            }
        }

        if (empty($error)) {
            $stmt = $pdo->prepare("INSERT INTO listings (user_id, title, description, price, image_url) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $title, $description, $price, $imageName]);
            $message = "Listing published onto the campus board successfully!";
        }
    }
}

$stmt = $pdo->query("
    SELECT l.*, u.id as seller_id, u.name as seller_name 
    FROM listings l
    JOIN users u ON l.user_id = u.id
    ORDER BY l.created_at DESC
");
$listings = $stmt->fetchAll();

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

    .clm-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; gap: 16px; }
    .clm-header h1 { font-size: 22px; font-weight: 700; margin: 0 0 4px; letter-spacing: -0.01em; color: var(--clf-ink); }
    .clm-header p { color: var(--clf-sub); margin: 0; font-size: 13.5px; max-width: 560px; }

    .btn-primary {
        background: var(--clf-ink); color: #fff; border: none; border-radius: 6px;
        padding: 8px 16px; font-size: 13px; font-weight: 600; cursor: pointer; white-space: nowrap;
    }
    .btn-primary:hover { background: #000; }

    .clm-modal {
        display: none; background: #fff; border: 1px solid var(--clf-line);
        border-radius: 8px; padding: 20px; margin-bottom: 28px;
    }
    .clm-modal h3 { font-size: 15px; font-weight: 700; margin: 0 0 14px; color: var(--clf-ink); }
    .clm-modal form { display: flex; flex-direction: column; gap: 12px; }
    .clm-modal input[type="text"], .clm-modal input[type="number"], .clm-modal textarea {
        width: 100%; padding: 10px 12px; background: #fff; border: 1px solid var(--clf-line);
        border-radius: 6px; color: var(--clf-ink); font-family: inherit; font-size: 13.5px; outline: none;
    }
    .clm-modal input:focus, .clm-modal textarea:focus { border-color: var(--clf-accent); }
    .clm-modal textarea { height: 90px; resize: none; }
    .clm-price-field { position: relative; }
    .clm-price-field span { position: absolute; left: 12px; top: 10px; color: var(--clf-sub); font-size: 13.5px; }
    .clm-price-field input { padding-left: 26px; }
    .clm-dropzone {
        background: var(--clf-bg-soft); border: 1px dashed var(--clf-line); border-radius: 6px; padding: 14px;
    }
    .clm-dropzone label { color: var(--clf-sub); font-size: 12.5px; display: block; margin-bottom: 8px; }
    .clm-dropzone input[type="file"] { font-size: 13px; cursor: pointer; }
    .clm-modal-actions { display: flex; gap: 10px; justify-content: flex-end; }
    .btn-plain { background: none; border: none; color: var(--clf-sub); cursor: pointer; font-size: 13px; font-family: inherit; }
    .btn-plain:hover { color: var(--clf-ink); }

    .clm-flash { font-size: 13.5px; font-weight: 600; margin-bottom: 18px; padding: 9px 12px; border-radius: 6px; }
    .clm-flash.ok { color: #2e6b45; background: #eaf5ee; border: 1px solid #cfe8d8; }
    .clm-flash.err { color: #9c3b1e; background: #fbeae5; border: 1px solid #eccabf; }

    .clf-avatar {
        width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center;
        justify-content: center; font-weight: 600; font-size: 11px; color: #fff; flex-shrink: 0;
    }
    .avatar-rust { background: #a8481f; } .avatar-ink-blue { background: #2c3e5c; }
    .avatar-moss { background: #4a5e3a; } .avatar-plum { background: #5c3a54; }

    .market-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 14px; }

    .market-card {
        background: #fff; border: 1px solid var(--clf-line); border-radius: 8px; overflow: hidden;
        display: flex; flex-direction: column; justify-content: space-between;
    }
    .market-image { width: 100%; height: 150px; background-size: cover; background-position: center; border-bottom: 1px solid var(--clf-line); }
    .market-image.placeholder {
        background: var(--clf-bg-soft); display: flex; align-items: center; justify-content: center;
        color: #c2c2c2; font-size: 28px;
    }

    .market-body { padding: 14px 16px; flex: 1; display: flex; flex-direction: column; justify-content: space-between; }
    .market-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; margin-bottom: 6px; }
    .market-title { color: var(--clf-ink); font-size: 15px; font-weight: 600; margin: 0; }
    .market-price { color: var(--clf-accent); font-weight: 700; font-size: 14.5px; white-space: nowrap; }
    .market-desc { color: var(--clf-sub); font-size: 12.5px; line-height: 1.5; margin: 0 0 14px; white-space: pre-wrap; }

    .market-foot {
        border-top: 1px solid var(--clf-line); padding-top: 12px; margin-top: 8px;
        display: flex; justify-content: space-between; align-items: center;
    }
    .market-seller { display: flex; align-items: center; gap: 8px; }
    .market-seller span.name { color: var(--clf-ink); font-size: 12.5px; font-weight: 500; }
    .btn-line {
        border: 1px solid var(--clf-line); background: #fff; color: var(--clf-ink); text-decoration: none;
        font-size: 12.5px; font-weight: 600; padding: 6px 13px; border-radius: 6px;
    }
    .btn-line:hover { border-color: var(--clf-ink); }
    .btn-line.solid { background: var(--clf-ink); color: #fff; border-color: var(--clf-ink); }
    .btn-line.solid:hover { background: #000; }

    .feed-empty { background: #fff; border: 1px dashed var(--clf-line); border-radius: 8px; padding: 28px; text-align: center; color: var(--clf-sub); font-size: 13.5px; }
</style>

<div class="clm-header">
    <div>
        <h1>Campus marketplace</h1>
        <p>Buy, sell, or trade books, hardware components, and student essentials safely inside your community.</p>
    </div>
    <button onclick="document.getElementById('sell-card').style.display='block'" class="btn-primary">+ Sell something</button>
</div>

<div id="sell-card" class="clm-modal">
    <h3>List an item for sale</h3>
    <form method="POST" action="index.php" enctype="multipart/form-data">
        <input type="hidden" name="publish_item" value="1">
        <input type="text" name="title" placeholder="What are you selling? (e.g., MacBook Pro 16GB)" required>

        <div class="clm-price-field">
            <span>$</span>
            <input type="number" name="price" step="0.01" placeholder="Price" required>
        </div>

        <textarea name="description" placeholder="Describe the item condition..." required></textarea>

        <div class="clm-dropzone">
            <label>Add item photo (optional)</label>
            <input type="file" name="item_image">
        </div>

        <div class="clm-modal-actions">
            <button type="button" onclick="document.getElementById('sell-card').style.display='none'" class="btn-plain">Cancel</button>
            <button type="submit" class="btn-primary">Publish listing</button>
        </div>
    </form>
</div>

<?php if ($message): ?><div class="clm-flash ok"><?= $message ?></div><?php endif; ?>
<?php if ($error): ?><div class="clm-flash err"><?= $error ?></div><?php endif; ?>

<div class="market-grid">
    <?php if (empty($listings)): ?>
        <div class="feed-empty" style="grid-column: 1/-1;">
            <p>No listings match your parameters or have been posted on campus yet.</p>
        </div>
    <?php else: ?>
        <?php foreach ($listings as $item): ?>
            <div class="market-card">
                <?php if ($item['image_url']): ?>
                    <div class="market-image" style="background-image: url('../uploads/post-images/<?= $item['image_url'] ?>');"></div>
                <?php else: ?>
                    <div class="market-image placeholder">📦</div>
                <?php endif; ?>

                <div class="market-body">
                    <div>
                        <div class="market-top">
                            <h3 class="market-title"><?= htmlspecialchars($item['title']) ?></h3>
                            <span class="market-price">$<?= number_format($item['price'], 2) ?></span>
                        </div>
                        <p class="market-desc"><?= htmlspecialchars($item['description']) ?></p>
                    </div>

                    <div class="market-foot">
                        <div class="market-seller">
                            <span class="clf-avatar <?= clAvatarClass($item['seller_name']) ?>"><?= clInitial($item['seller_name']) ?></span>
                            <span class="name"><?= htmlspecialchars($item['seller_name']) ?></span>
                        </div>
                        <a href="/IPT_WEB_PROJECT/CampusLink/public/profile.php?id=<?= $item['seller_id'] ?>" class="btn-line solid">Contact</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../app/includes/dashboard_foot.php'; ?>