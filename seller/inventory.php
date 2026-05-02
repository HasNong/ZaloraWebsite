<?php
session_start();
require_once '../config/db.php';

// Fetch products for this seller (assuming all for now since seller logic isn't fully separated)
$query = "SELECT p.*, c.Ctgry_Name, b.Brand_Name, 
          (SELECT PImg_ImgUrl FROM PRODUCT_IMAGE WHERE Prod_Id = p.Prod_Id AND PImg_IsPrimary = 1 LIMIT 1) as img
          FROM PRODUCT p
          LEFT JOIN CATEGORY c ON p.Ctgry_Id = c.Ctgry_Id
          LEFT JOIN BRAND b ON p.Brand_Id = b.Brand_Id
          ORDER BY p.Prod_AddedAt DESC";
$products = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Center - Inventory</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/seller.css">
    <style>
        .inv-table { width: 100%; border-collapse: collapse; background: white; border: 1px solid #eee; }
        .inv-table th { text-align: left; padding: 15px; font-size: 11px; font-weight: 700; color: #888; text-transform: uppercase; border-bottom: 2px solid #f5f5f5; }
        .inv-table td { padding: 15px; border-bottom: 1px solid #f5f5f5; font-size: 13px; }
        .prod-cell { display: flex; align-items: center; gap: 15px; }
        .prod-img { width: 40px; height: 50px; object-fit: cover; background: #f9f9f9; }
        .status-badge { padding: 4px 8px; border-radius: 4px; font-size: 10px; font-weight: 700; text-transform: uppercase; }
        .status-active { background: #eafaf1; color: #27ae60; }
        .btn-edit { color: #000; font-weight: 600; text-decoration: none; font-size: 11px; }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-header">
        <h1>SELLER CENTER</h1>
        <p>GLOBAL FASHION LTD.</p>
    </div>
    <ul class="sidebar-nav">
        <li><a href="dashboard.php">DASHBOARD</a></li>
        <li><a href="inventory.php" class="active">INVENTORY</a></li>
        <li><a href="orders.php">ORDERS</a></li>
        <li><a href="profile.php">PROFILE</a></li>
    </ul>
    <div class="sidebar-footer">
        <a href="add_product.php" class="btn-add-product">ADD NEW PRODUCT</a>
    </div>
</aside>

<div class="main-wrapper">
    <main class="main-content">
        <header class="page-header">
            <div>
                <h2 class="page-title">PRODUCT INVENTORY</h2>
                <p class="page-subtitle">Manage your product listings and stock levels.</p>
            </div>
            <div class="header-actions">
                <a href="add_product.php" class="btn-export" style="text-decoration:none;">ADD PRODUCT</a>
            </div>
        </header>

        <table class="inv-table">
            <thead>
                <tr>
                    <th>Product Details</th>
                    <th>Category</th>
                    <th>Brand</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($p = $products->fetch_assoc()): ?>
                <tr>
                    <td>
                        <div class="prod-cell">
                            <img src="<?= $p['img'] ?? 'https://via.placeholder.com/40' ?>" class="prod-img">
                            <div>
                                <p style="font-weight:600; margin-bottom:2px;"><?= htmlspecialchars($p['Prod_Name']) ?></p>
                                <p style="font-size:11px; color:#999;">ID: <?= $p['Prod_Id'] ?></p>
                            </div>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($p['Ctgry_Name'] ?? 'Uncategorized') ?></td>
                    <td><?= htmlspecialchars($p['Brand_Name'] ?? 'No Brand') ?></td>
                    <td style="font-weight:600;">$<?= number_format($p['Prod_BasePrice'], 2) ?></td>
                    <td><span class="status-badge status-active">Active</span></td>
                    <td><a href="#" class="btn-edit">EDIT</a></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </main>
</div>

</body>
</html>
