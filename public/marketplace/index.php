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
                            <span class="market-price">Tsh <?= number_format($item['price'], 2) ?></span>
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