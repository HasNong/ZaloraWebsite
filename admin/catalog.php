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
    $prod_id = intval($_POST['prod_id']);
    $action = $_POST['action'];
    $status = ($action === 'approve') ? 1 : 0;
    
    $stmt = $conn->prepare("UPDATE PRODUCT SET Prod_IsActive = ? WHERE Prod_Id = ?");
    $stmt->bind_param("ii", $status, $prod_id);
    if ($stmt->execute()) {
        $_SESSION['success'] = ($action === 'approve') ? "Product approved!" : "Product de-activated.";
    }
    header("Location: catalog.php");
    exit;
}

// Handle Review Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rview_id'])) {
    $rview_id = intval($_POST['rview_id']);
    $action = $_POST['action'];
    
    if ($action === 'approve') {
        $stmt = $conn->prepare("UPDATE review SET Rview_IsApproved = 1 WHERE Rview_Id = ?");
        $stmt->bind_param("i", $rview_id);
        $stmt->execute();
        $_SESSION['success'] = "Review approved!";
    } else {
        $stmt = $conn->prepare("DELETE FROM review WHERE Rview_Id = ?");
        $stmt->bind_param("i", $rview_id);
        $stmt->execute();
        $_SESSION['success'] = "Review rejected and deleted.";
    }
    header("Location: catalog.php?tab=reviews");
    exit;
}

// Fetch Data
$pending_prod = $conn->query("SELECT p.*, s.Sell_FirstName, s.Sell_LastName, b.Brand_Name, (SELECT PImg_ImgUrl FROM PRODUCT_IMAGE WHERE Prod_Id = p.Prod_Id LIMIT 1) as img FROM PRODUCT p JOIN seller s ON p.Sell_Id = s.Sell_Id JOIN BRAND b ON p.Brand_Id = b.Brand_Id WHERE p.Prod_IsActive = 0");
$active_prod = $conn->query("SELECT p.*, s.Sell_FirstName, s.Sell_LastName, b.Brand_Name, (SELECT PImg_ImgUrl FROM PRODUCT_IMAGE WHERE Prod_Id = p.Prod_Id LIMIT 1) as img FROM PRODUCT p JOIN seller s ON p.Sell_Id = s.Sell_Id JOIN BRAND b ON p.Brand_Id = b.Brand_Id WHERE p.Prod_IsActive = 1 LIMIT 50");
$pending_reviews = $conn->query("SELECT r.*, c.Cust_FirstName, c.Cust_LastName, p.Prod_Name FROM review r JOIN customer c ON r.Cust_Id = c.Cust_Id JOIN PRODUCT p ON r.Prod_Id = p.Prod_Id WHERE r.Rview_IsApproved = 0");

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'pending';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Catalog & Reviews</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .catalog-container { padding: 40px; }
        .tabs { display: flex; gap: 20px; border-bottom: 1px solid #eee; margin-bottom: 30px; }
        .tab { padding: 10px 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; cursor: pointer; color: #999; border-bottom: 2px solid transparent; }
        .tab.active { color: #000; border-color: #000; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px; }
        .card { background: #fff; border: 1px solid #eee; overflow: hidden; display: flex; flex-direction: column; }
        .card-img { width: 100%; height: 200px; object-fit: cover; background: #fafafa; }
        .card-info { padding: 20px; flex-grow: 1; }
        .card-brand { font-size: 10px; font-weight: 800; color: #999; text-transform: uppercase; margin-bottom: 5px; }
        .card-name { font-size: 14px; font-weight: 700; margin-bottom: 10px; }
        .card-meta { font-size: 11px; color: #666; margin-bottom: 15px; }
        .card-actions { padding: 15px; border-top: 1px solid #eee; display: flex; gap: 10px; }
        .btn-black { flex: 1; background: #000; color: #fff; border: none; padding: 10px; font-size: 10px; font-weight: 700; text-transform: uppercase; cursor: pointer; }
        .btn-white { flex: 1; background: #fff; color: #000; border: 1px solid #000; padding: 10px; font-size: 10px; font-weight: 700; text-transform: uppercase; cursor: pointer; }
        .review-card { padding: 20px; border-bottom: 1px solid #eee; background: #fff; margin-bottom: 15px; }
        .rev-rating { color: #000; font-size: 12px; margin-bottom: 10px; }
    </style>
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
                    <div class="tab <?= $active_tab == 'pending' ? 'active' : '' ?>" onclick="location.href='?tab=pending'">Pending Products (<?= $pending_prod->num_rows ?>)</div>
                    <div class="tab <?= $active_tab == 'active' ? 'active' : '' ?>" onclick="location.href='?tab=active'">Active Catalog</div>
                    <div class="tab <?= $active_tab == 'reviews' ? 'active' : '' ?>" onclick="location.href='?tab=reviews'">Pending Reviews (<?= $pending_reviews->num_rows ?>)</div>
                </div>

                <!-- PENDING PRODUCTS -->
                <?php if ($active_tab == 'pending'): ?>
                    <div class="grid">
                        <?php while($p = $pending_prod->fetch_assoc()): ?>
                            <div class="card">
                                <img src="../<?= htmlspecialchars($p['img'] ?? 'assets/images/placeholder.jpg') ?>" class="card-img">
                                <div class="card-info">
                                    <div class="card-brand"><?= htmlspecialchars($p['Brand_Name']) ?></div>
                                    <div class="card-name"><?= htmlspecialchars($p['Prod_Name']) ?></div>
                                    <div class="card-meta">Seller: <?= htmlspecialchars($p['Sell_FirstName'] . ' ' . $p['Sell_LastName']) ?></div>
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
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>

                <!-- ACTIVE PRODUCTS -->
                <?php if ($active_tab == 'active'): ?>
                    <div class="grid">
                        <?php while($p = $active_prod->fetch_assoc()): ?>
                            <div class="card">
                                <img src="../<?= htmlspecialchars($p['img'] ?? 'assets/images/placeholder.jpg') ?>" class="card-img">
                                <div class="card-info">
                                    <div class="card-brand"><?= htmlspecialchars($p['Brand_Name']) ?></div>
                                    <div class="card-name"><?= htmlspecialchars($p['Prod_Name']) ?></div>
                                    <div class="card-meta">Seller: <?= htmlspecialchars($p['Sell_FirstName'] . ' ' . $p['Sell_LastName']) ?></div>
                                    <div style="font-weight:700;">$<?= number_format($p['Prod_BasePrice'], 2) ?></div>
                                </div>
                                <div class="card-actions">
                                    <form method="POST" style="width: 100%;">
                                        <input type="hidden" name="prod_id" value="<?= $p['Prod_Id'] ?>">
                                        <button type="submit" name="action" value="reject" class="btn-white" style="width: 100%;">Deactivate</button>
                                    </form>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>

                <!-- PENDING REVIEWS -->
                <?php if ($active_tab == 'reviews'): ?>
                    <div>
                        <?php while($r = $pending_reviews->fetch_assoc()): ?>
                            <div class="review-card">
                                <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                                    <div>
                                        <div class="rev-rating"><?= str_repeat('★', $r['Rview_Rating']) . str_repeat('☆', 5-$r['Rview_Rating']) ?></div>
                                        <div style="font-size: 13px; font-weight: 700; margin-bottom: 5px;"><?= htmlspecialchars($r['Cust_FirstName'] . ' ' . $r['Cust_LastName']) ?> on <?= htmlspecialchars($r['Prod_Name']) ?></div>
                                        <p style="font-size: 13px; color: #444; line-height: 1.6;"><?= htmlspecialchars($r['Rview_Txt']) ?></p>
                                        <?php if ($r['Rview_PicUrl']): ?>
                                            <img src="../<?= htmlspecialchars($r['Rview_PicUrl']) ?>" style="width: 100px; height: 100px; object-fit: cover; margin-top: 15px; border: 1px solid #eee;" onclick="window.open(this.src)">
                                        <?php endif; ?>
                                    </div>
                                    <div style="display:flex; flex-direction:column; gap: 10px; width: 150px;">
                                        <form method="POST">
                                            <input type="hidden" name="rview_id" value="<?= $r['Rview_Id'] ?>">
                                            <button type="submit" name="action" value="approve" class="btn-black" style="width: 100%;">Approve</button>
                                            <button type="submit" name="action" value="reject" class="btn-white" style="width: 100%; margin-top: 10px;">Reject</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                        <?php if ($pending_reviews->num_rows == 0): ?>
                            <div style="text-align: center; padding: 80px; background: #fafafa; border: 1px dashed #ddd; color: #999; font-size: 13px;">No pending reviews.</div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            </div>
        </main>
    </div>
</body>
</html>
