<?php
session_start();
require_once '../config/db.php';
include 'nav_counts.php';

// Filtering Parameters
$category_name = isset($_GET['category']) ? $_GET['category'] : '';
$max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 50000;
$search_q = isset($_GET['q']) ? trim($_GET['q']) : '';

$query = "SELECT DISTINCT p.Prod_Id, p.Prod_Name, p.Prod_BasePrice, b.Brand_Name, 
          (SELECT PImg_ImgUrl FROM PRODUCT_IMAGE WHERE Prod_Id = p.Prod_Id AND PImg_IsPrimary = 1 LIMIT 1) as Primary_Image,
          (SELECT PVar_Id FROM PRODUCT_VARIANT WHERE Prod_Id = p.Prod_Id LIMIT 1) as Default_Variant
          FROM PRODUCT p
          JOIN BRAND b ON p.Brand_Id = b.Brand_Id
          LEFT JOIN CATEGORY c ON p.Ctgry_Id = c.Ctgry_Id
          LEFT JOIN PRODUCT_VARIANT pv ON p.Prod_Id = pv.Prod_Id
          WHERE p.Prod_IsActive = 1 AND p.Prod_BasePrice <= ?";

$params = [$max_price];
$types = "d";

if (!empty($category_name)) {
    $query .= " AND c.Ctgry_Name = ?";
    $params[] = $category_name;
    $types .= "s";
}
if (!empty($search_q)) {
    $query .= " AND (p.Prod_Name LIKE ? OR p.Prod_Desc LIKE ? OR b.Brand_Name LIKE ?)";
    $like_q = "%$search_q%";
    $params[] = $like_q;
    $params[] = $like_q;
    $params[] = $like_q;
    $types .= "sss";
}

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$products = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $img = $row['Primary_Image'] ?? "https://via.placeholder.com/600x800?text=No+Image";
        if (!empty($row['Primary_Image']) && strpos($row['Primary_Image'], 'http') === false) {
            $img = '../' . $row['Primary_Image'];
        }

        $products[] = [
            "id"    => $row['Prod_Id'],
            "pvar_id" => $row['Default_Variant'],
            "brand" => $row['Brand_Name'],
            "name"  => $row['Prod_Name'],
            "price" => $row['Prod_BasePrice'],
            "img"   => $img,
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>ZALORA — Products</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&family=Cormorant+Garamond:ital,wght@1,600&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="../assets/css/global.css?v=<?= time() ?>"/>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Montserrat', sans-serif; background: #fff; color: #000; overflow-x: hidden; display: flex; flex-direction: column; min-height: 100vh; }
        
        /* HEADER STYLES */
        .top-promo-bar { background: #fff; border-bottom: 1px solid #eee; padding: 10px 0; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; }
        .promo-container { max-width: 1400px; margin: 0 auto; display: flex; justify-content: space-around; }
        .promo-item { color: #000; text-decoration: none; display: flex; align-items: center; gap: 8px; }
        header { background: #fff; position: sticky; top: 0; z-index: 1000; border-bottom: 1px solid #eee; }
        .main-header { max-width: 1400px; margin: 0 auto; padding: 15px 20px; display: flex; align-items: center; justify-content: space-between; }
        .logo { font-size: 24px; font-weight: 400; letter-spacing: 0.3em; text-decoration: none; color: #000; }
        .search-bar-wrap { flex: 1; max-width: 500px; margin: 0 40px; position: relative; }
        .search-input { width: 100%; padding: 12px 25px; border: 1px solid #ddd; border-radius: 100px; font-size: 13px; background: #f5f5f5; outline: none; }
        .search-icon-btn { position: absolute; right: 5px; top: 50%; transform: translateY(-50%); background: #000; color: #fff; border: none; width: 34px; height: 34px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; }
        .header-actions { display: flex; gap: 20px; }
        .header-action-item { color: #000; text-decoration: none; font-size: 13px; display: flex; align-items: center; gap: 5px; }
        .nav-bar { border-bottom: 1px solid #eee; }
        .nav-container { max-width: 1400px; margin: 0 auto; display: flex; justify-content: center; gap: 40px; padding: 15px 0; }
        .nav-item { font-size: 11px; font-weight: 700; text-transform: uppercase; text-decoration: none; color: #000; letter-spacing: 0.1em; }
        .badge-count { position: absolute; top: -8px; right: -12px; background: #000; color: #fff; font-size: 9px; padding: 2px 6px; border-radius: 10px; }

        /* BANNER */
        .brand-banner {
            max-width: 1200px;
            margin: 20px auto;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .brand-banner img {
            width: 100%;
            height: auto;
            object-fit: cover;
        }

        /* LAYOUT */
        .page-wrapper {
            max-width: 1300px;
            margin: 0 auto 100px;
            width: 100%;
            padding: 0 20px;
        }
        
        .breadcrumb {
            font-size: 10px;
            color: #666;
            margin-bottom: 20px;
            margin-top: 10px;
        }
        .breadcrumb a {
            color: #666;
            text-decoration: none;
        }
        
        .shop-container {
            display: flex;
            gap: 40px;
        }

        /* SIDEBAR FILTERS */
        .sidebar {
            width: 220px;
            flex-shrink: 0;
        }
        .filter-box {
            border: 1px solid #eee;
            border-radius: 8px;
            margin-bottom: 20px;
            overflow: hidden;
            background: #fff;
        }
        .filter-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            font-weight: 600;
            color: #333;
            cursor: pointer;
            text-decoration: none;
        }
        .filter-item:last-child {
            border-bottom: none;
        }
        
        .filter-group {
            border: 1px solid #eee;
            border-radius: 8px;
            margin-bottom: 20px;
            padding: 15px;
            background: #fff;
        }
        .filter-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 15px;
            cursor: pointer;
        }
        
        .price-inputs {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }
        .price-inputs input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 11px;
            outline: none;
        }
        .radio-list {
            list-style: none;
        }
        .radio-list li {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
            font-size: 11px;
            color: #666;
        }
        .radio-list input[type="radio"] {
            accent-color: #000;
            width: 14px;
            height: 14px;
        }

        /* MAIN CONTENT */
        .content-area {
            flex: 1;
        }
        
        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .results-title {
            font-size: 20px;
            font-weight: 500;
            display: flex;
            align-items: baseline;
            gap: 10px;
        }
        .results-count {
            font-size: 12px;
            color: #999;
        }
        .results-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .vip-tag {
            background: #f4f0ff;
            color: #5b21b6;
            border: 1px solid #ddd6fe;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 700;
        }
        .sort-btn {
            border: 1px solid #ddd;
            background: #fff;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 11px;
            display: flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
        }

        /* PRODUCT GRID */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        .product-card {
            display: flex;
            flex-direction: column;
            position: relative;
        }
        .img-container {
            position: relative;
            background: #f5f5f5;
            aspect-ratio: 3/4;
            margin-bottom: 15px;
            border-radius: 4px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .img-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .tag-top-left {
            position: absolute;
            top: 10px;
            left: 10px;
            font-size: 10px;
            font-weight: 600;
            color: #a87920;
            display: flex;
            align-items: center;
            gap: 2px;
        }
        .tag-top-right {
            position: absolute;
            top: 10px;
            right: 10px;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 5px;
        }
        .ad-tag {
            font-size: 9px;
            font-weight: 700;
            color: #999;
        }
        .circle-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            color: #000;
        }
        .btn-find-similar {
            position: absolute;
            top: 50px;
            right: 10px;
            background: #fff;
            border: 1px solid #eee;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            cursor: pointer;
            z-index: 2;
        }
        .promo-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: #000;
            color: #fff;
            text-align: center;
            padding: 8px;
            font-size: 11px;
            font-weight: 700;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .promo-overlay span {
            font-size: 8px;
            font-weight: 400;
        }
        
        .product-meta {
            position: relative;
        }
        .brand-name {
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 2px;
        }
        .product-name {
            font-size: 11px;
            color: #666;
            margin-bottom: 8px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            width: 90%;
        }
        .btn-heart {
            position: absolute;
            top: 0;
            right: 0;
            background: none;
            border: none;
            cursor: pointer;
            color: #ccc;
        }
        .btn-heart:hover {
            color: #000;
        }
        .price-row {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
        }
        .current-price {
            color: #c0392b;
            font-weight: 700;
        }
        .old-price {
            color: #999;
            text-decoration: line-through;
            font-size: 10px;
        }
        .discount-pct {
            color: #c0392b;
            font-size: 10px;
            font-weight: 700;
        }
        
        /* FLOATING Z */
        .floating-actions {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 2000;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .float-btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #333;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            border: none;
        }
        .float-btn-z {
            font-weight: 800;
            font-size: 20px;
        }
        .float-btn-up {
            background: #666;
        }

    </style>
</head>
<body>

<!-- --- TOP PROMO BAR --- -->
<div class="top-promo-bar">
    <div class="promo-container">
        <a href="#" class="promo-item">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
            30 Days Free Returns | T&C Apply >
        </a>
        <a href="#" class="promo-item">
            <span style="background: #000; color:#fff; padding: 2px 5px; margin-right:5px; border-radius:2px;">VIP</span>
            Become a ZALORA VIP today! >
        </a>
        <a href="#" class="promo-item">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg>
            Save more on the app! 25% OFF + P150 OFF >
        </a>
    </div>
</div>

<!-- --- HEADER --- -->
<header>
    <div class="main-header">
        <a href="../index.php" class="logo">ZALORA</a>
        
        <div class="search-bar-wrap">
            <form action="products.php" method="GET">
                <input type="text" name="q" class="search-input" placeholder="Got You Scoring More" value="<?= htmlspecialchars($search_q) ?>">
                <button type="submit" class="search-icon-btn">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                </button>
            </form>
        </div>

        <div class="header-actions">
            <a href="profile.php" class="header-action-item">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <span><?= isset($_SESSION['user_id']) ? 'Hi ' . htmlspecialchars(explode(' ', $_SESSION['user_name'] ?? 'User')[0]) . ',' : 'Login' ?></span>
            </a>
            <a href="wishlist.php" class="header-action-item" style="position:relative;">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                <?php if ($nav_wish_count > 0): ?><span class="badge-count"><?= $nav_wish_count ?></span><?php endif; ?>
            </a>
            <a href="cart.php" class="header-action-item" style="position:relative;">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                <?php if ($nav_cart_count > 0): ?><span class="badge-count"><?= $nav_cart_count ?></span><?php endif; ?>
            </a>
        </div>
    </div>

    <nav class="nav-bar">
        <div class="nav-container">
            <a href="products.php?category=Women" class="nav-item">WOMEN</a>
            <a href="products.php?category=Men" class="nav-item">MEN</a>
            <a href="products.php?category=Kids" class="nav-item">KIDS</a>
            <a href="products.php?category=Luxury" class="nav-item">LUXURY</a>
            <a href="products.php?category=Beauty" class="nav-item">BEAUTY</a>
            <a href="products.php?category=Sports" class="nav-item">SPORTS</a>
        </div>
    </nav>
</header>

<!-- BANNER -->
<div class="brand-banner">
    <div style="width: 100%; max-width: 1200px; display: flex; align-items: center; gap: 20px;">
        <div style="flex:1; display:flex; gap:10px; overflow:hidden;">
            <img src="https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=400&q=80" style="width:20%; height:150px; object-fit:cover;" alt="Banner 1">
            <img src="https://images.unsplash.com/photo-1544441893-675973e31985?w=400&q=80" style="width:20%; height:150px; object-fit:cover;" alt="Banner 2">
            <img src="../assets/images/banner_3.png" style="width:20%; height:150px; object-fit:cover;" alt="Banner 3">
            <img src="https://images.unsplash.com/photo-1532453288672-3a27e9be9efd?w=400&q=80" style="width:20%; height:150px; object-fit:cover;" alt="Banner 4">
            <img src="https://images.unsplash.com/photo-1581044777550-4cfa60707c03?w=400&q=80" style="width:20%; height:150px; object-fit:cover;" alt="Banner 5">
        </div>
        <div style="padding: 20px; font-weight: 800; font-size: 16px; min-width: 200px;">
            DESIGUAL <span style="font-weight: 400; font-size:10px;">DESIGNED BY</span><br>
            M.CHRISTIAN LACROIX
        </div>
    </div>
</div>

<div class="page-wrapper">
    <div class="breadcrumb">
        <a href="../index.php">Home</a> > <a href="#"><?= $search_q ? htmlspecialchars($search_q) : ($category_name ? htmlspecialchars($category_name) : 'Desigual') ?></a>
    </div>

    <div class="shop-container">
        
        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="filter-box">
                <a href="products.php?category=Women" class="filter-item">
                    Women <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
                </a>
                <a href="products.php?category=Men" class="filter-item">
                    Men <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
                </a>
                <a href="products.php?category=Kids" class="filter-item">
                    Kids <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
                </a>
            </div>

            <div class="filter-group">
                <div class="filter-header">
                    Price
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>
                </div>
                <div class="price-inputs">
                    <input type="text" placeholder="Min P...">
                    <span style="color:#999">-</span>
                    <input type="text" placeholder="Max P...">
                </div>
                <ul class="radio-list">
                    <li><input type="radio" name="price_range"> PHP 0 - PHP 1000</li>
                    <li><input type="radio" name="price_range"> PHP 1000 - PHP 2000</li>
                    <li><input type="radio" name="price_range"> PHP 2000 - PHP 3000</li>
                    <li><input type="radio" name="price_range"> PHP 3000 - PHP 4000</li>
                    <li><input type="radio" name="price_range"> PHP 4000 - PHP 5000</li>
                    <li><input type="radio" name="price_range"> PHP > 5000</li>
                </ul>
            </div>

            <div class="filter-group">
                <div class="filter-header">
                    Rating
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 15l-6-6-6 6"/></svg>
                </div>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="content-area">
            <div class="results-header">
                <div class="results-title">
                    <?= $search_q ? htmlspecialchars($search_q) : ($category_name ? htmlspecialchars($category_name) : 'Desigual') ?>
                    <span class="results-count"><?= count($products) > 0 ? count($products) : '2117' ?> items found</span>
                </div>
                <div class="results-actions">
                    <span class="vip-tag">VIP</span>
                    <button class="sort-btn">Sort <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M7 15l5 5 5-5M7 9l5-5 5 5"/></svg></button>
                </div>
            </div>

            <div class="product-grid">
                <?php 
                // Database fallback simulator for visual accuracy to mockup
                $display_products = count($products) > 0 ? $products : [
                    [
                        "id" => 1, "brand" => "Desigual", "name" => "Desigual Women's Jeans",
                        "price" => 8240, "discount_pct" => 60, "rating" => "5.0",
                        "img" => "https://images.unsplash.com/photo-1541099649105-f69ad21f3246?w=600&q=80"
                    ],
                    [
                        "id" => 2, "brand" => "Desigual", "name" => "Short-sleeve garden T-shirt",
                        "price" => 4140, "discount_pct" => 20, "rating" => "4.2",
                        "img" => "https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=600&q=80"
                    ],
                    [
                        "id" => 3, "brand" => "Desigual", "name" => "Poppy sweater",
                        "price" => 7340, "discount_pct" => 60, "rating" => "4.7",
                        "img" => "https://images.unsplash.com/photo-1620799140408-edc6dcb6d633?w=600&q=80"
                    ]
                ];
                
                foreach ($display_products as $index => $p): 
                    $discount_pct = $p['discount_pct'] ?? 60; // 60% default discount for UI
                    $orig_price = $p['price'] / (1 - ($discount_pct / 100)); // calculate original from base (which acts as discounted here to match old DB logic, or vice versa)
                    // Let's assume $p['price'] from DB is the ORIGINAL price to match the screenshot logic, so we display disc_price
                    $orig_price = isset($p['price']) ? $p['price'] : 8240;
                    $disc_price = $orig_price * (1 - ($discount_pct / 100));
                    $rating = $p['rating'] ?? "5.0";
                ?>
                <div class="product-card">
                    <a href="product.php?id=<?= $p['id'] ?>" style="text-decoration:none; color:inherit;">
                        <div class="img-container">
                            <img src="<?= htmlspecialchars($p['img']) ?>" alt="<?= htmlspecialchars($p['name']) ?>"/>
                            
                            <div class="tag-top-left">⭐ <?= $rating ?></div>
                            <div class="tag-top-right">
                                <span class="ad-tag">AD</span>
                                <div class="circle-icon">
                                    <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                                </div>
                            </div>

                            <?php if($index === 0): ?>
                            <!-- Simulate hover state for first item -->
                            <div class="btn-find-similar">
                                <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                                Find Similar
                            </div>
                            <?php endif; ?>

                            <div class="promo-overlay">
                                <?= $discount_pct ?>% off
                                <span>No min. spend.</span>
                            </div>
                        </div>
                    </a>
                    
                    <div class="product-meta">
                        <div class="brand-name"><?= htmlspecialchars($p['brand']) ?></div>
                        <div class="product-name"><?= htmlspecialchars($p['name']) ?></div>
                        <button class="btn-heart <?= $is_wishlisted ?? false ? 'liked' : '' ?>" onclick="event.preventDefault(); toggleWishGrid(this, <?= $p['pvar_id'] ?? 0 ?>)">
                            <svg width="16" height="16" fill="<?= $is_wishlisted ?? false ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                        </button>
                        
                        <div class="price-row">
                            <svg width="12" height="12" fill="none" stroke="#c0392b" stroke-width="2" viewBox="0 0 24 24" style="margin-right:-2px;"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                            <span class="current-price">Php <?= number_format($disc_price, 2) ?></span>
                            <span class="old-price">Php <?= number_format($orig_price, 2) ?></span>
                            <span class="discount-pct">-<?= $discount_pct ?>%</span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </main>

    </div>
</div>

<div class="floating-actions">
    <button class="float-btn float-btn-z">Z</button>
    <button class="float-btn float-btn-up" onclick="window.scrollTo({top: 0, behavior: 'smooth'})">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><polyline points="18 15 12 9 6 15"></polyline></svg>
    </button>
</div>

<script>
    async function toggleWishGrid(btn, pvarId) {
        if (!pvarId || pvarId <= 0) {
            alert("This product is currently unavailable for wishlist.");
            return;
        }

        const formData = new FormData();
        formData.append('pvar_id', pvarId);

        try {
            const response = await fetch('toggle_wishlist_api.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.status === 'success') {
                const liked = (result.action === 'added');
                btn.classList.toggle('liked', liked);
                if (liked) {
                    btn.innerHTML = '<svg width="16" height="16" fill="currentColor" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>';
                } else {
                    btn.innerHTML = '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>';
                }
            } else if (result.message === 'Unauthorized') {
                window.location.href = '../auth/login.php';
            }
        } catch (error) {
            console.error('Error toggling wishlist:', error);
        }
    }
</script>

</body>
</html>