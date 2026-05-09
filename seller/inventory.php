<?php
session_start();
require_once '../config/db.php';

// Auth check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'seller') {
    header("Location: ../auth/login.php");
    exit;
}

$seller_id = $_SESSION['user_id'];

// Fetch products with images, category, and total stock for this seller
$query = "SELECT p.*, c.Ctgry_Name, 
          (SELECT PImg_ImgUrl FROM PRODUCT_IMAGE WHERE Prod_Id = p.Prod_Id AND PImg_IsPrimary = 1 LIMIT 1) as img,
          (SELECT SUM(PVar_StockQuantity) FROM product_variant WHERE Prod_Id = p.Prod_Id) as total_stock
          FROM product p
          LEFT JOIN category c ON p.Ctgry_Id = c.Ctgry_Id
          WHERE p.Sell_Id = ?
          ORDER BY p.Prod_CreatedAt DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$products = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Center - Inventory Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/seller.css">
    <style>
        .search-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .search-box { position: relative; width: 300px; }
        .search-box input { width: 100%; padding: 10px 10px 10px 40px; border: 1px solid #eee; font-size: 13px; outline: none; }
        .search-box svg { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #999; }
        
        .filter-tabs { display: flex; gap: 1px; background: #eee; border: 1px solid #eee; }
        .filter-tabs button { background: #fff; border: none; padding: 10px 20px; font-size: 11px; font-weight: 700; color: #666; cursor: pointer; text-transform: uppercase; }
        .filter-tabs button.active { background: #000; color: #fff; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-wrapper">
    <main class="main-content">
        
        <header class="page-header" style="align-items: center;">
            <div>
                <h2 class="page-title">INVENTORY MANAGEMENT</h2>
                <p class="page-subtitle">Manage your product catalog, stock levels, and publication status across all channels.</p>
            </div>
            <div class="header-actions">
                <button class="btn-export" style="display:flex; align-items:center; gap:8px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                    EXPORT CSV
                </button>
            </div>
        </header>

        <?php if(isset($_SESSION['success_msg'])): ?>
            <div class="alert" style="padding: 15px; margin-bottom: 2rem; font-size: 12px; font-weight: 600; border-left: 4px solid var(--accent-green); background: #f0fdf4; color: var(--accent-green);">
                <?= $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?>
            </div>
        <?php endif; ?>

        <div class="search-row">
            <div class="search-box">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path></svg>
                <input type="text" placeholder="SEARCH SKU, NAME OR CATEGORY">
            </div>
            <div class="filter-tabs">
                <button class="active">All Products (<?= $products->num_rows ?>)</button>
                <button>In Stock</button>
                <button>Low Stock</button>
                <button>Drafts</button>
            </div>
        </div>

        <div class="content-card" style="padding: 0;">
            <table class="inventory-table">
                <thead>
                    <tr>
                        <th style="width: 40px; padding-left: 2rem;"><input type="checkbox"></th>
                        <th>Product Details</th>
                        <th>SKU</th>
                        <th>Price</th>
                        <th>Stock Level</th>
                        <th>Status</th>
                        <th style="text-align: right; padding-right: 2rem;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($p = $products->fetch_assoc()): 
                        $stock = $p['total_stock'] ?? 0;
                        $stock_percent = min(($stock / 100) * 100, 100);
                        $status_class = "active";
                        $status_label = "ACTIVE";
                        
                        if ($stock <= 0) {
                            $status_class = "out";
                            $status_label = "OUT OF STOCK";
                        } elseif ($stock < 10) {
                            $status_class = "low";
                            $status_label = "LOW STOCK";
                        }
                        
                        $img_path = $p['img'] ?? 'https://via.placeholder.com/50';
                        if (!empty($p['img']) && strpos($p['img'], 'http') === false) {
                            $img_path = '../' . $p['img'];
                        }
                    ?>
                    <tr>
                        <td style="padding-left: 2rem;"><input type="checkbox"></td>
                        <td>
                            <div class="prod-details">
                                <img src="<?= $img_path ?>" class="prod-thumb">
                                <div class="prod-info">
                                    <h4><?= htmlspecialchars($p['Prod_Name']) ?></h4>
                                    <p><?= htmlspecialchars($p['Ctgry_Name'] ?? 'General') ?></p>
                                </div>
                            </div>
                        </td>
                        <td style="font-family: monospace; color: #666;">ZAL-<?= date('Y', strtotime($p['Prod_CreatedAt'])) ?>-<?= str_pad($p['Prod_Id'], 4, '0', STR_PAD_LEFT) ?></td>
                        <td style="font-weight: 600;">$<?= number_format($p['Prod_BasePrice'], 2) ?></td>
                        <td>
                            <div class="stock-bar-wrap">
                                <div class="stock-bar"><div class="stock-progress <?= $stock < 10 ? 'low' : '' ?>" style="width: <?= $stock_percent ?>%;"></div></div>
                                <p style="font-size: 10px; color: #999; margin: 0;"><?= $stock ?> Units</p>
                            </div>
                        </td>
                        <td><span class="status-tag <?= $status_class ?>"><?= $status_label ?></span></td>
                        <td style="text-align: right; padding-right: 2rem;">
                            <div style="display:flex; justify-content: flex-end; gap: 15px;">
                                <a href="edit_product.php?id=<?= $p['Prod_Id'] ?>" style="color: inherit;" title="Edit Product">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                                </a>
                                <button onclick="deleteProduct(<?= $p['Prod_Id'] ?>, '<?= addslashes($p['Prod_Name']) ?>')" style="background:none; border:none; cursor:pointer; color: #e74c3c;" title="Delete Product">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 2rem;">
            <p style="font-size: 12px; color: #999;">Showing 1 to <?= $products->num_rows ?> entries</p>
            <div style="display: flex; gap: 5px;">
                <button style="padding: 8px 12px; border: 1px solid #eee; background: #fff; font-size: 12px; cursor: pointer;"><</button>
                <button style="padding: 8px 12px; border: none; background: #000; color: #fff; font-size: 12px; cursor: pointer;">1</button>
                <button style="padding: 8px 12px; border: 1px solid #eee; background: #fff; font-size: 12px; cursor: pointer;">></button>
            </div>
        </div>

    </main>

    <!-- FOOTER -->
    <footer class="seller-footer">
        <div>
            <div class="footer-logo">ZALORA</div>
            <div class="footer-copy">© 2024 ZALORA ALL RIGHTS RESERVED</div>
        </div>
        <div class="footer-links">
            <a href="#">HELP & SUPPORT</a>
            <a href="#">SIZE GUIDE</a>
            <a href="#">RETURNS & REFUNDS</a>
            <a href="#">CONTACT US</a>
        </div>
    </footer>
</div>

<script>
function deleteProduct(id, name) {
    if (confirm(`Are you sure you want to delete "${name}"? This action cannot be undone.`)) {
        window.location.href = `delete_product.php?id=${id}`;
    }
}
</script>

</body>
</html>
