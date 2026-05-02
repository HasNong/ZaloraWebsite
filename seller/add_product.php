<?php
session_start();
require_once '../config/db.php';

// Fetch Categories for dropdown
$categories = $conn->query("SELECT Ctgry_Id, Ctgry_Name FROM CATEGORY WHERE Ctgry_IsActive = 1 ORDER BY Ctgry_Name");

// Fetch Brands for dropdown
$brands = $conn->query("SELECT Brand_Id, Brand_Name FROM BRAND ORDER BY Brand_Name");

$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $desc = $_POST['desc'] ?? '';
    $price = $_POST['price'] ?? 0;
    $ctgry_id = $_POST['category_id'] ?? 0;
    $brand_id = $_POST['brand_id'] ?? 0;
    $img_url = $_POST['img_url'] ?? '';

    if ($name && $price > 0 && $ctgry_id > 0) {
        // Generate manual Prod_Id (matching existing pattern)
        $max_res = $conn->query("SELECT MAX(Prod_Id) as max_id FROM PRODUCT");
        $prod_id = ($max_res->fetch_assoc()['max_id'] ?? 0) + 1;

        $stmt = $conn->prepare("INSERT INTO PRODUCT (Prod_Id, Brand_Id, Ctgry_Id, Prod_Name, Prod_Desc, Prod_BasePrice, Prod_IsActive, Prod_AddedAt) VALUES (?, ?, ?, ?, ?, ?, 1, NOW())");
        $stmt->bind_param("iiissd", $prod_id, $brand_id, $ctgry_id, $name, $desc, $price);
        
        if ($stmt->execute()) {
            // Also add primary image if URL provided
            if (!empty($img_url)) {
                $max_img = $conn->query("SELECT MAX(PImg_Id) as max_id FROM PRODUCT_IMAGE");
                $pimg_id = ($max_img->fetch_assoc()['max_id'] ?? 0) + 1;
                
                $img_stmt = $conn->prepare("INSERT INTO PRODUCT_IMAGE (PImg_Id, Prod_Id, PImg_ImgUrl, PImg_IsPrimary) VALUES (?, ?, ?, 1)");
                $img_stmt->bind_param("iis", $pimg_id, $prod_id, $img_url);
                $img_stmt->execute();
            }
            $msg = "Product successfully added!";
        } else {
            $msg = "Error: " . $conn->error;
        }
    } else {
        $msg = "Please fill in all required fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Center - Add Product</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/seller.css">
    <style>
        .form-card { background: white; padding: 2.5rem; border: 1px solid #eee; max-width: 800px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; font-size: 11px; font-weight: 700; letter-spacing: 0.1em; color: #888; margin-bottom: 8px; text-transform: uppercase; }
        .form-group input, .form-group select, .form-group textarea { 
            width: 100%; padding: 12px; border: 1px solid #e0e0e0; font-family: 'Inter', sans-serif; font-size: 13px; outline: none; transition: border-color 0.2s;
        }
        .form-group input:focus { border-color: #000; }
        .alert { padding: 15px; margin-bottom: 2rem; font-size: 12px; font-weight: 600; border-left: 4px solid #000; background: #f9f9f9; }
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
        <li><a href="inventory.php">INVENTORY</a></li>
        <li><a href="orders.php">ORDERS</a></li>
        <li><a href="profile.php">PROFILE</a></li>
    </ul>
    <div class="sidebar-footer">
        <a href="add_product.php" class="btn-add-product active">ADD NEW PRODUCT</a>
    </div>
</aside>

<div class="main-wrapper">
    <main class="main-content">
        <header class="page-header">
            <div>
                <h2 class="page-title">ADD NEW PRODUCT</h2>
                <p class="page-subtitle">List a new item in your global catalog.</p>
            </div>
        </header>

        <?php if($msg): ?>
            <div class="alert"><?= $msg ?></div>
        <?php endif; ?>

        <div class="form-card">
            <form method="POST">
                <div class="form-group">
                    <label>Product Name *</label>
                    <input type="text" name="name" placeholder="e.g. Silk Minimalist Dress" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Category *</label>
                        <select name="category_id" required>
                            <option value="">Select Category</option>
                            <?php while($c = $categories->fetch_assoc()): ?>
                                <option value="<?= $c['Ctgry_Id'] ?>"><?= htmlspecialchars($c['Ctgry_Name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Brand</label>
                        <select name="brand_id">
                            <option value="">Select Brand</option>
                            <?php while($b = $brands->fetch_assoc()): ?>
                                <option value="<?= $b['Brand_Id'] ?>"><?= htmlspecialchars($b['Brand_Name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Base Price ($) *</label>
                        <input type="number" step="0.01" name="price" placeholder="0.00" required>
                    </div>
                    <div class="form-group">
                        <label>Primary Image URL</label>
                        <input type="url" name="img_url" placeholder="https://images.unsplash.com/...">
                    </div>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="desc" rows="5" placeholder="Detailed product description..."></textarea>
                </div>

                <button type="submit" class="btn-add-product" style="width: 200px; height: 50px; border: none; cursor: pointer;">SAVE PRODUCT</button>
            </form>
        </div>
    </main>
</div>

</body>
</html>
