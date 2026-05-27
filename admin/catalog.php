<?php
session_start();
require_once '../config/db.php';

// Auth check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Handle Product Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['prod_id'])) {
    $prod_id = $_POST['prod_id'];
    $action = $_POST['action'];
    
    if ($action === 'approve') {
        $status = 1;
        $msg = "Product approved and live!";
    } elseif ($action === 'reject') {
        $status = 2;
        $msg = "Product rejected and hidden.";
    } else {
        $status = 0;
        $msg = "Product de-activated and moved to pending.";
    }
    
    // Update both uppercase and lowercase paths just in case
    $db = $database->getReference();
    $prodRef = $db->getChild("product")->orderByChild("Prod_Id")->equalTo($prod_id)->getSnapshot();
    if ($prodRef->hasChildren()) {
        $key = array_key_first($prodRef->getValue());
        $db->getChild("product/$key")->update(['Prod_IsActive' => $status]);
    } else {
        $prodRef = $db->getChild("product")->orderByChild("Prod_Id")->equalTo($prod_id)->getSnapshot();
        if ($prodRef->hasChildren()) {
            $key = array_key_first($prodRef->getValue());
            $db->getChild("PRODUCT/$key")->update(['Prod_IsActive' => $status]);
        }
    }
    
    $_SESSION['success'] = $msg;
    header("Location: catalog.php?tab=" . ($_GET['tab'] ?? 'pending'));
    exit;
}

// Handle Review Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rview_id'])) {
    $rview_id = $_POST['rview_id'];
    $action = $_POST['action'];
    
    $db = $database->getReference();
    $revRef = $db->getChild("review")->orderByChild("Rview_Id")->equalTo($rview_id)->getSnapshot();
    
    if ($revRef->hasChildren()) {
        $key = array_key_first($revRef->getValue());
        if ($action === 'approve') {
            $db->getChild("review/$key")->update(['Rview_IsApproved' => 1]);
            $_SESSION['success'] = "Review approved!";
        } else {
            $db->getChild("review/$key")->remove();
            $_SESSION['success'] = "Review rejected and deleted.";
        }
    }
    
    header("Location: catalog.php?tab=reviews");
    exit;
}

function resolve_img_url($url) {
    if (!$url) return '../assets/images/placeholder.jpg';
    if (strpos($url, 'data:') === 0 || strpos($url, 'http') === 0) {
        return $url;
    }
    return '../' . $url;
}

// Fetch Data from Firebase
$db = $database->getReference();
$all_products = array_merge(
    $db->getChild("product")->getSnapshot()->getValue() ?: [],
    $db->getChild("product")->getSnapshot()->getValue() ?: []
);
$all_sellers = array_merge(
    $db->getChild("seller")->getSnapshot()->getValue() ?: [],
    $db->getChild("seller")->getSnapshot()->getValue() ?: []
);
$all_brands = array_merge(
    $db->getChild("brand")->getSnapshot()->getValue() ?: [],
    $db->getChild("brand")->getSnapshot()->getValue() ?: []
);
$all_images = array_merge(
    $db->getChild("product_image")->getSnapshot()->getValue() ?: [],
    $db->getChild("product_image")->getSnapshot()->getValue() ?: []
);
$all_reviews = $db->getChild("review")->getSnapshot()->getValue() ?: [];
$all_customers = array_merge(
    $db->getChild("customer")->getSnapshot()->getValue() ?: [],
    $db->getChild("customer")->getSnapshot()->getValue() ?: []
);

// Map sellers, brands, customers, images
$seller_map = []; foreach ($all_sellers as $s) { $seller_map[$s['Sell_Id'] ?? ''] = $s['Sell_BusinessName'] ?? ''; }
$brand_map = []; foreach ($all_brands as $b) { $brand_map[$b['Brand_Id'] ?? ''] = $b['Brand_Name'] ?? ''; }
$cust_map = []; foreach ($all_customers as $c) { $cust_map[$c['Cust_Id'] ?? ''] = ($c['Cust_FirstName'] ?? '') . ' ' . ($c['Cust_LastName'] ?? ''); }
$img_map = []; foreach ($all_images as $i) { 
    if (!isset($img_map[$i['Prod_Id'] ?? '']) || ($i['PImg_IsPrimary'] ?? 0) == 1) {
        $img_map[$i['Prod_Id'] ?? ''] = $i['PImg_ImgUrl'] ?? ''; 
    }
}
$prod_name_map = []; foreach ($all_products as $p) { $prod_name_map[$p['Prod_Id'] ?? ''] = $p['Prod_Name'] ?? ''; }

$pending_prod = [];
$active_prod = [];
$rejected_prod = [];
$pending_reviews = [];

foreach ($all_products as $p) {
    $p['Sell_BusinessName'] = $seller_map[$p['Sell_Id'] ?? ''] ?? 'Unknown Seller';
    $p['Brand_Name'] = $brand_map[$p['Brand_Id'] ?? ''] ?? 'Unknown Brand';
    $p['img'] = $img_map[$p['Prod_Id'] ?? ''] ?? null;
    
    $status = $p['Prod_IsActive'] ?? 0;
    if ($status == 0) $pending_prod[] = $p;
    elseif ($status == 1 && count($active_prod) < 50) $active_prod[] = $p;
    elseif ($status == 2) $rejected_prod[] = $p;
}

foreach ($all_reviews as $r) {
    if (($r['Rview_IsApproved'] ?? 0) == 0) {
        $r['Cust_Name'] = $cust_map[$r['Cust_Id'] ?? ''] ?? 'Unknown Customer';
        $r['Prod_Name'] = $prod_name_map[$r['Prod_Id'] ?? ''] ?? 'Unknown Product';
        $pending_reviews[] = $r;
    }
}

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'pending';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Catalog & Reviews</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/admin-catalog.css?v=<?= time() ?>">
</head>
<body class="admin-body">
    <div class="admin-layout">
        <?php include 'sidebar.php'; ?>
        
        <main class="main-content">
            <div class="catalog-container">
                <h1 class="page-title">Catalog & Reviews</h1>
                
                <?php if (isset($_SESSION['success'])): ?>
                    <div style="background: #e6fffa; color: #2c7a7b; padding: 15px; margin-bottom: 20px; font-size: 12px; font-weight: 600;">
                        <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>

                <div class="tabs">
                    <div class="tab <?= $active_tab == 'pending' ? 'active' : '' ?>" onclick="location.href='?tab=pending'">Pending (<?= count($pending_prod) ?>)</div>
                    <div class="tab <?= $active_tab == 'active' ? 'active' : '' ?>" onclick="location.href='?tab=active'">Active Catalog</div>
                    <div class="tab <?= $active_tab == 'rejected' ? 'active' : '' ?>" onclick="location.href='?tab=rejected'">Rejected (<?= count($rejected_prod) ?>)</div>
                    <div class="tab <?= $active_tab == 'reviews' ? 'active' : '' ?>" onclick="location.href='?tab=reviews'">Reviews (<?= count($pending_reviews) ?>)</div>
                </div>

                <!-- PENDING PRODUCTS -->
                <?php if ($active_tab == 'pending'): ?>
                    <div class="grid">
                        <?php foreach($pending_prod as $p): ?>
                            <div class="card">
                                <img src="<?= htmlspecialchars(resolve_img_url($p['img'] ?? '')) ?>" class="card-img">
                                <div class="card-info">
                                    <div class="card-brand"><?= htmlspecialchars($p['Brand_Name']) ?></div>
                                    <div class="card-name"><?= htmlspecialchars($p['Prod_Name']) ?></div>
                                    <div class="card-meta">Seller: <?= htmlspecialchars($p['Sell_BusinessName']) ?></div>
                                    <div style="font-weight:700;">$<?= number_format($p['Prod_BasePrice'], 2) ?></div>
                                </div>
                                <div class="card-actions">
                                    <form method="POST" style="width: 100%; display: flex; gap: 10px;">
                                        <input type="hidden" name="prod_id" value="<?= $p['Prod_Id'] ?>">
                                        <button type="submit" name="action" value="approve" class="btn-black">Approve</button>
                                        <button type="submit" name="action" value="reject" class="btn-white">Reject</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- ACTIVE PRODUCTS -->
                <?php if ($active_tab == 'active'): ?>
                    <div class="grid">
                        <?php foreach($active_prod as $p): ?>
                            <div class="card">
                                <img src="<?= htmlspecialchars(resolve_img_url($p['img'] ?? '')) ?>" class="card-img">
                                <div class="card-info">
                                    <div class="card-brand"><?= htmlspecialchars($p['Brand_Name']) ?></div>
                                    <div class="card-name"><?= htmlspecialchars($p['Prod_Name']) ?></div>
                                    <div class="card-meta">Seller: <?= htmlspecialchars($p['Sell_BusinessName']) ?></div>
                                    <div style="font-weight:700;">$<?= number_format($p['Prod_BasePrice'], 2) ?></div>
                                </div>
                                <div class="card-actions">
                                    <form method="POST" style="width: 100%;">
                                        <input type="hidden" name="prod_id" value="<?= $p['Prod_Id'] ?>">
                                        <button type="submit" name="action" value="deactivate" class="btn-white" style="width: 100%;">Deactivate</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- REJECTED PRODUCTS -->
                <?php if ($active_tab == 'rejected'): ?>
                    <div class="grid">
                        <?php foreach($rejected_prod as $p): ?>
                            <div class="card" style="opacity: 0.7;">
                                <img src="<?= htmlspecialchars(resolve_img_url($p['img'] ?? '')) ?>" class="card-img">
                                <div class="card-info">
                                    <div class="card-brand"><?= htmlspecialchars($p['Brand_Name']) ?></div>
                                    <div class="card-name"><?= htmlspecialchars($p['Prod_Name']) ?></div>
                                    <div class="card-meta">Seller: <?= htmlspecialchars($p['Sell_BusinessName']) ?></div>
                                    <div style="font-weight:700;">$<?= number_format($p['Prod_BasePrice'], 2) ?></div>
                                </div>
                                <div class="card-actions">
                                    <form method="POST" style="width: 100%;">
                                        <input type="hidden" name="prod_id" value="<?= $p['Prod_Id'] ?>">
                                        <button type="submit" name="action" value="approve" class="btn-black" style="width: 100%;">Re-Approve</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- PENDING REVIEWS -->
                <?php if ($active_tab == 'reviews'): ?>
                    <div>
                        <?php foreach($pending_reviews as $r): ?>
                            <div class="review-card">
                                <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                                    <div>
                                        <div class="rev-rating"><?= str_repeat('★', $r['Rview_Rating'] ?? 5) . str_repeat('☆', 5-($r['Rview_Rating'] ?? 5)) ?></div>
                                        <div style="font-size: 13px; font-weight: 700; margin-bottom: 5px;"><?= htmlspecialchars($r['Cust_Name']) ?> on <?= htmlspecialchars($r['Prod_Name']) ?></div>
                                        <p style="font-size: 13px; color: #444; line-height: 1.6;"><?= htmlspecialchars($r['Rview_Txt'] ?? '') ?></p>
                                        <?php if (!empty($r['Rview_PicUrl'])): ?>
                                            <img src="<?= htmlspecialchars(resolve_img_url($r['Rview_PicUrl'])) ?>" style="width: 100px; height: 100px; object-fit: cover; margin-top: 15px; border: 1px solid #eee;" onclick="window.open(this.src)">
                                        <?php endif; ?>
                                    </div>
                                    <div style="display:flex; flex-direction:column; gap: 10px; width: 150px;">
                                        <form method="POST">
                                            <input type="hidden" name="rview_id" value="<?= $r['Rview_Id'] ?>">
                                            <button type="submit" name="action" value="approve" class="btn-black" style="width: 100%;">Approve</button>
                                            <button type="submit" name="action" value="reject" class="btn-white" style="width: 100%; margin-top: 10px%;">Reject</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (count($pending_reviews) == 0): ?>
                            <div style="text-align: center; padding: 80px; background: #fafafa; border: 1px dashed #ddd; color: #999; font-size: 13px;">No pending reviews.</div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            </div>
        </main>
    </div>
</body>
</html>
