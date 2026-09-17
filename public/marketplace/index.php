<?php
$activePage = 'marketplace';
$pageTitle = 'Campus Marketplace';
require_once __DIR__ . '/../../app/includes/dashboard_head.php';

$userId = $_SESSION['user_id'];
$message = '';
$error = '';

// 1. Handle Publishing a New Marketplace Listing Action
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

// 2. FETCH FIX: Explicitly select l.user_id as seller_id to generate routing links
$stmt = $pdo->query("
    SELECT l.*, u.id as seller_id, u.name as seller_name 
    FROM listings l
    JOIN users u ON l.user_id = u.id
    ORDER BY l.created_at DESC
");
$listings = $stmt->fetchAll();
?>

<div class="page-header" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 40px;">
    <div>
        <h1>Campus Marketplace</h1>
        <p>Buy, sell, or trade books, hardware components, and student essentials safely inside your community.</p>
    </div>
    <button onclick="document.getElementById('sell-card').style.display='block'" class="feed-submit-btn">
        + Sell Something
    </button>
</div>

<!-- Add New Item Creation Overlay Panel Box Shell Layout -->
<div id="sell-card" style="display: none; background-color: #2a2a2a; border: 1px solid #3d3d3d; border-radius: 16px; padding: 24px; margin-bottom: 40px;">
    <h3 style="color: #ffffff; margin-bottom: 16px;">List an Item for Sale</h3>
    
    <form method="POST" action="index.php" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 16px;">
        <input type="hidden" name="publish_item" value="1">
        <input type="text" name="title" placeholder="What are you selling? (e.g., MacBook Pro 16GB)" required 
               style="width:100%; padding:12px; background:#1e1e1e; border:1px solid #3d3d3d; border-radius:8px; color:white;">
        
        <div style="position: relative;">
            <span style="position: absolute; left: 12px; top: 12px; color: #757575;">$</span>
            <input type="number" name="price" step="0.01" placeholder="Price" required 
                   style="width:100%; padding:12px 12px 12px 28px; background:#1e1e1e; border:1px solid #3d3d3d; border-radius:8px; color:white;">
        </div>

        <textarea name="description" placeholder="Describe the item condition..." required 
                  style="width:100%; height:90px; padding:12px; background:#1e1e1e; border:1px solid #3d3d3d; border-radius:8px; color:white; resize:none;"></textarea>
        
        <div style="background:#1e1e1e; border:1px dashed #3d3d3d; border-radius:8px; padding:16px;">
            <label style="color:#a0a0a0; font-size:13px; display:block; margin-bottom:8px;">Add Item Photo (Optional)</label>
            <input type="file" name="item_image" style="color:white; font-size:13px; cursor:pointer;">
        </div>

        <div style="display: flex; gap: 12px; justify-content: flex-end;">
            <button type="button" onclick="document.getElementById('sell-card').style.display='none'" 
                    style="background: transparent; color: white; border: none; cursor: pointer;">Cancel</button>
            <button type="submit" class="feed-submit-btn">Publish Listing</button>
        </div>
    </form>
</div>

<?php if ($message): ?> <p style="color: #10b981; margin-bottom:20px; font-weight:600;"><?= $message ?></p> <?php endif; ?>
<?php if ($error): ?> <p style="color: #ef4444; margin-bottom:20px; font-weight:600;"><?= $error ?></p> <?php endif; ?>

<!-- Marketplace Listings Grid Layout Blocks -->
<div class="market-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 26px;">
    <?php if (empty($listings)): ?>
        <div class="feed-empty" style="grid-column: 1/-1;">
            <p>No listings match your parameters or have been posted on campus yet.</p>
        </div>
    <?php else: ?>
        <?php foreach ($listings as $item): ?>
            <div class="market-card" style="background-color: #2a2a2a; border: 1px solid #3d3d3d; border-radius: 16px; overflow: hidden; display: flex; flex-direction: column; justify-content: space-between;">
                
                <?php if ($item['image_url']): ?>
                    <div style="width: 100%; height: 180px; background-image: url('../uploads/post-images/<?= $item['image_url'] ?>'); background-size: cover; background-position: center; border-bottom: 1px solid #3d3d3d;"></div>
                <?php else: ?>
                    <div style="width: 100%; height: 140px; background: #1e1e1e; display: flex; align-items: center; justify-content: center; border-bottom: 1px solid #3d3d3d; color: #555555; font-size: 32px;">📦</div>
                <?php endif; ?>

                <div style="padding: 20px; flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; margin-bottom: 8px;">
                            <h3 style="color: #ffffff; font-size: 18px; font-weight: 600; margin: 0;"><?= htmlspecialchars($item['title']) ?></h3>
                            <span style="color: #10b981; font-weight: 700; font-size: 16px; white-space: nowrap;">$<?= number_format($item['price'], 2) ?></span>
                        </div>
                        <p style="color: #a0a0a0; font-size: 14px; line-height: 1.5; margin-bottom: 16px; white-space: pre-wrap;"><?= htmlspecialchars($item['description']) ?></p>
                    </div>

                    <div style="border-top: 1px solid #3d3d3d; padding-top: 14px; margin-top: 10px; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <span style="color: #757575; font-size: 11px; display: block;">Seller</span>
                            <span style="color: #ffffff; font-size: 13px; font-weight: 500;"><?= htmlspecialchars($item['seller_name']) ?></span>
                        </div>
                        
                        <!-- INTERACTION FIX: Routes straight to the vendor profile canvas view page -->
                        <a href="/IPT_WEB_PROJECT/CampusLink/public/profile.php?id=<?= $item['seller_id'] ?>" 
                           style="background-color: #ffffff; color: #121212; text-decoration: none; font-size: 13px; font-weight: 600; padding: 8px 14px; border-radius: 6px; text-align: center;">
                            Contact
                        </a>
                    </div>
                </div>

            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../app/includes/dashboard_foot.php'; ?>
