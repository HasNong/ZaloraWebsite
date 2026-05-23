<?php
session_start();
require_once '../config/db.php';
include 'nav_counts.php';

// Filtering Parameters
$category_name = isset($_GET['category']) ? $_GET['category'] : '';
$max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 50000;
$search_q = isset($_GET['q']) ? trim($_GET['q']) : '';

// Fetch all necessary data from Firebase Realtime Database
$allProducts = $database->getReference('product')->getSnapshot()->getValue() ?: [];
$allBrands = $database->getReference('brand')->getSnapshot()->getValue() ?: [];
$allCategories = $database->getReference('category')->getSnapshot()->getValue() ?: [];
$allImages = $database->getReference('product_image')->getSnapshot()->getValue() ?: [];
$allVariants = $database->getReference('product_variant')->getSnapshot()->getValue() ?: [];

// Build indices for faster lookup
$brandIndex = [];
foreach ($allBrands as $b) {
    if (isset($b['Brand_Id'])) $brandIndex[$b['Brand_Id']] = $b['Brand_Name'] ?? '';
}

$categoryIndex = [];
foreach ($allCategories as $c) {
    if (isset($c['Ctgry_Id'])) $categoryIndex[$c['Ctgry_Id']] = $c['Ctgry_Name'] ?? '';
}

$products = [];

foreach ($allProducts as $p) {
    if (!isset($p['Prod_IsActive']) || $p['Prod_IsActive'] != 1) continue;
    if (isset($p['Prod_BasePrice']) && floatval($p['Prod_BasePrice']) > $max_price) continue;

    $prodId = $p['Prod_Id'] ?? '';
    $brandId = $p['Brand_Id'] ?? '';
    $ctgryId = $p['Ctgry_Id'] ?? '';

    $brandName = $brandIndex[$brandId] ?? '';
    $catName = $categoryIndex[$ctgryId] ?? '';

    // Category filter
    if (!empty($category_name) && strtolower($catName) !== strtolower($category_name)) {
        continue;
    }

    // Search filter
    if (!empty($search_q)) {
        $sq = strtolower($search_q);
        if (strpos(strtolower($p['Prod_Name'] ?? ''), $sq) === false && 
            strpos(strtolower($p['Prod_Desc'] ?? ''), $sq) === false &&
            strpos(strtolower($brandName), $sq) === false) {
            continue;
        }
    }

    // Primary Image
    $primaryImg = '';
    foreach ($allImages as $img) {
        if (($img['Prod_Id'] ?? '') == $prodId && ($img['PImg_IsPrimary'] ?? 0) == 1) {
            $primaryImg = $img['PImg_ImgUrl'] ?? '';
            break;
        }
    }

    // Default Variant
    $defVarId = '';
    foreach ($allVariants as $var) {
        if (($var['Prod_Id'] ?? '') == $prodId) {
            $defVarId = $var['PVar_Id'] ?? '';
            break;
        }
    }

    $imgUrl = $primaryImg ?: "https://via.placeholder.com/600x800?text=No+Image";
    if (!empty($primaryImg) && strpos($primaryImg, 'http') === false) {
        $imgUrl = '../' . $primaryImg;
    }

    $products[] = [
        "id" => $prodId,
        "pvar_id" => $defVarId,
        "brand" => $brandName,
        "name" => $p['Prod_Name'] ?? '',
        "price" => $p['Prod_BasePrice'] ?? 0,
        "img" => $imgUrl,
    ];
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
    <link rel="stylesheet" href="../assets/css/products.css?v=<?= time() ?>"/>
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
                        <button class="btn-heart <?= $is_wishlisted ?? false ? 'liked' : '' ?>" onclick="event.preventDefault(); toggleWishGrid(this, '<?= $p['pvar_id'] ?? '' ?>')">
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