<?php
session_start();
require_once '../config/db.php';
include 'nav_counts.php';

$prod_id = $_GET['id'] ?? null;

if (!$prod_id) {
    header("Location: products.php");
    exit();
}

// Fetch Product Details
$products = $database->getReference('product')->orderByChild('Prod_Id')->equalTo($prod_id)->getSnapshot()->getValue();
$product_data = $products ? reset($products) : null;

if (!$product_data || ($product_data['Prod_IsActive'] ?? 0) != 1) {
    // If not found, just use a dummy for the UI showcase
    $product_data = [
        'Ctgry_Name' => 'Jeans',
        'Brand_Name' => 'Desigual',
        'Prod_Name' => "Desigual Women's Jeans",
        'Prod_BasePrice' => 8240,
        'Prod_Desc' => 'Premium women\'s jeans designed by M.Christian Lacroix.',
        'Ctgry_Id' => 1
    ];
} else {
    $brandId = $product_data['Brand_Id'] ?? '';
    $brands = $database->getReference('brand')->orderByChild('Brand_Id')->equalTo($brandId)->getSnapshot()->getValue();
    $product_data['Brand_Name'] = $brands ? reset($brands)['Brand_Name'] : '';

    $catId = $product_data['Ctgry_Id'] ?? '';
    $categories = $database->getReference('category')->orderByChild('Ctgry_Id')->equalTo($catId)->getSnapshot()->getValue();
    $product_data['Ctgry_Name'] = $categories ? reset($categories)['Ctgry_Name'] : '';
}

// Fetch Variants
$variants_res = $database->getReference('product_variant')->orderByChild('Prod_Id')->equalTo($prod_id)->getSnapshot()->getValue() ?: [];
$variants = [];
$sizes = [];
$colors = [];

foreach ($variants_res as $v) {
    $variants[] = $v;
    if (!empty($v['PVar_Size']) && !in_array($v['PVar_Size'], $sizes)) $sizes[] = $v['PVar_Size'];
    if (!empty($v['PVar_Color']) && !in_array($v['PVar_Color'], $colors)) $colors[] = $v['PVar_Color'];
}

// Ensure dummy data if empty (to match mockup)
if (empty($sizes)) $sizes = ['34', '36', '38', '40', '42', '44'];
if (empty($colors)) $colors = ['white'];

// Fetch Images
$images_res = $database->getReference('product_image')->orderByChild('Prod_Id')->equalTo($prod_id)->getSnapshot()->getValue() ?: [];
// Sort by IsPrimary DESC
$images_res_array = array_values($images_res);
usort($images_res_array, function($a, $b) {
    return ($b['PImg_IsPrimary'] ?? 0) <=> ($a['PImg_IsPrimary'] ?? 0);
});

$images = [];
foreach ($images_res_array as $img) {
    $img_url = $img['PImg_ImgUrl'] ?? '';
    if (!empty($img_url) && strpos($img_url, 'http') === false) {
        $img_url = '../' . $img_url;
    }
    $images[] = $img_url;
}

if (empty($images)) {
    // Use the exact images from the screenshot to match the mockup perfectly
    $images = [
        "https://images.unsplash.com/photo-1541099649105-f69ad21f3246?w=800&q=80",
        "https://images.unsplash.com/photo-1541099649105-f69ad21f3246?w=800&q=80" // duplicate for back view simulation
    ];
}
 
$is_wishlisted = false;

// Discount Logic for UI Matching
$discount_pct = 60;
$orig_price = $product_data['Prod_BasePrice'];
$disc_price = $orig_price * (1 - ($discount_pct / 100));

// Recommended Products (dummy for UI)
$complete_look = [
    ["id" => 1, "brand" => "Desigual", "name" => "White Jeans", "img" => "https://images.unsplash.com/photo-1541099649105-f69ad21f3246?w=400&q=80", "price" => 3296],
    ["id" => 2, "brand" => "Desigual", "name" => "Light Denim", "img" => "https://images.unsplash.com/photo-1584370848010-d7fe6bc767ec?w=400&q=80", "price" => 4500],
    ["id" => 3, "brand" => "Desigual", "name" => "Dark Denim", "img" => "https://images.unsplash.com/photo-1604176354204-9268737828e4?w=400&q=80", "price" => 5200],
    ["id" => 4, "brand" => "Desigual", "name" => "Cargo Jeans", "img" => "https://images.unsplash.com/photo-1542272604-787c3835535d?w=400&q=80", "price" => 6100],
    ["id" => 5, "brand" => "Desigual", "name" => "Red Jacket", "img" => "https://images.unsplash.com/photo-1551028719-0125fd6b7eb8?w=400&q=80", "price" => 8000],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>ZALORA — <?= htmlspecialchars($product_data['Prod_Name']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="../assets/css/global.css?v=<?= time() ?>"/>
    <link rel="stylesheet" href="../assets/css/product.css?v=<?= time() ?>"/>
</head>
<body>

<!-- --- TOP PROMO BAR --- -->
<div class="top-promo-bar">
    <div class="promo-container-top">
        <a href="#" class="promo-item-top">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
            30 Days Free Returns | T&C Apply >
        </a>
        <a href="#" class="promo-item-top">
            <span style="background: #000; color:#fff; padding: 2px 5px; margin-right:5px; border-radius:2px;">VIP</span>
            Become a ZALORA VIP today! >
        </a>
        <a href="#" class="promo-item-top">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg>
            Save more on the app! 25% OFF + P150 OFF >
        </a>
    </div>
</div>

<!-- HEADER -->
<header>
    <div class="main-header">
        <a href="../index.php" class="logo">ZALORA</a>
        <div class="search-bar-wrap">
            <form action="products.php" method="GET">
                <input type="text" name="q" class="search-input" placeholder="Got You Scoring More">
                <button type="submit" class="search-icon-btn"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg></button>
            </form>
        </div>
        <div class="header-actions">
            <a href="profile.php" class="header-action-item">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <span><?= isset($_SESSION['user_id']) ? 'Hi ' . htmlspecialchars(explode(' ', $_SESSION['user_name'] ?? 'User')[0]) . ',' : 'Login' ?></span>
            </a>
            <a href="wishlist.php" class="header-action-item">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                <?php if ($nav_wish_count > 0): ?><span class="badge-count"><?= $nav_wish_count ?></span><?php endif; ?>
            </a>
            <a href="cart.php" class="header-action-item">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                <?php if ($nav_cart_count > 0): ?><span class="badge-count"><?= $nav_cart_count ?></span><?php endif; ?>
            </a>
        </div>
    </div>
    <nav class="nav-bar">
        <div class="nav-container">
            <a href="products.php?category=Women" class="nav-item active">WOMEN</a>
            <a href="products.php?category=Men" class="nav-item">MEN</a>
            <a href="products.php?category=Kids" class="nav-item">KIDS</a>
            <a href="products.php?category=Luxury" class="nav-item">LUXURY</a>
            <a href="products.php?category=Beauty" class="nav-item">BEAUTY</a>
            <a href="products.php?category=Sports" class="nav-item">SPORTS</a>
        </div>
    </nav>
</header>

<div class="breadcrumb">
    <a href="../index.php">Home</a> > <a href="#">Women</a> > <a href="#">Women's Clothing</a> > <strong>Jeans</strong>
</div>

<div class="product-layout">
    
    <!-- LEFT: IMAGES -->
    <div class="left-images">
        <?php foreach ($images as $index => $img): ?>
        <div class="img-box">
            <?php if ($index === 0): ?>
                <div class="trending-tag">⚡ Trending</div>
            <?php endif; ?>
            <?php if ($index === 1): ?>
                <div class="zoom-icon"><svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg></div>
            <?php endif; ?>
            <img src="<?= htmlspecialchars($img) ?>" alt="Product Image">
        </div>
        <?php endforeach; ?>
        <?php if(count($images) < 2): ?>
        <div class="img-box">
             <div class="zoom-icon"><svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg></div>
             <img src="<?= htmlspecialchars($images[0]) ?>" alt="Product Image Back">
        </div>
        <?php endif; ?>
    </div>

    <!-- RIGHT: PRODUCT INFO -->
    <div class="right-info">
        <div class="brand-row">
            <div class="brand-title"><?= htmlspecialchars($product_data['Brand_Name']) ?></div>
            <div class="rating-pill">
                5.0 <span class="stars">⭐</span> 1
            </div>
        </div>
        <div class="prod-name"><?= htmlspecialchars($product_data['Prod_Name']) ?></div>

        <div class="price-section">
            <svg class="fast-ship" viewBox="0 0 24 24" fill="none" stroke="#c0392b" stroke-width="1.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            <span class="current-price">Php <?= number_format($disc_price, 2) ?></span>
            <span class="old-price">Php <?= number_format($orig_price, 2) ?></span>
            <span class="discount-tag">-<?= $discount_pct ?>%</span>
        </div>
        <div class="installment">
            3 payments of <span>Php <?= number_format($disc_price/3, 2) ?></span>, 0% interest with <span style="background:#f16122; color:#fff; padding:1px 4px; border-radius:2px; font-size:9px;">Z</span>
        </div>

        <div class="promo-container">
            <div class="promo-box">
                <h4>60% off</h4>
                <p>No min. spend.</p>
                <div class="ends">Ends in 11 hours. <span style="text-decoration:underline; color:#666;">T&C</span></div>
                <div class="promo-tags">
                    <span class="promo-code">DESIGUAL17</span>
                    <span class="promo-type">Select items only</span>
                </div>
            </div>
            <div class="promo-more">
                + 1 more
                <span>View all</span>
            </div>
        </div>

        <form action="add_to_cart.php" method="POST">
            <input type="hidden" name="pvar_id" id="selected-pvar-id" value="<?= !empty($variants) ? $variants[0]['PVar_Id'] : '' ?>">
            <input type="hidden" name="quantity" value="1">

            <div class="section-title">Variations <span><?= htmlspecialchars($colors[0]) ?></span></div>
            <div class="var-thumb">
                <img src="<?= htmlspecialchars($images[0]) ?>" alt="Variant">
            </div>

            <div class="size-header-row">
                <div class="section-title" style="margin-bottom:0;">Size <span id="size-label">EU 44</span></div>
                <div class="size-guide-btn"><svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 9h16M4 15h16M10 9v6M14 9v6"/></svg> Size Guide</div>
                <div class="new-badge">NEW</div>
            </div>
            
            <div class="size-tabs">
                <div class="size-tab active">EU</div>
                <div class="size-tab">UK</div>
                <div class="size-tab">US</div>
            </div>

            <div class="size-grid">
                <?php foreach($sizes as $idx => $s): ?>
                <div class="size-btn <?= $idx === 5 ? 'active' : '' ?>" data-size="<?= htmlspecialchars($s) ?>" onclick="selectSize(this)">
                    <div class="size-btn-top">More v</div>
                    <?= htmlspecialchars($s) ?>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="stock-warning">Hurry! Only 3 items left</div>

            <div class="action-row">
                <button type="submit" class="btn-add">Add to Bag</button>
                <button type="button" class="btn-wish-sq <?= $is_wishlisted ? 'liked' : '' ?>" onclick="toggleWish(this)">
                    <?php if ($is_wishlisted): ?>
                        <svg width="20" height="20" fill="currentColor" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                    <?php else: ?>
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                    <?php endif; ?>
                </button>
            </div>
        </form>

        <div class="delivery-box">
            <h4>Delivery</h4>
            <p><a href="#">Select a location</a> to get delivery time and price</p>
        </div>

        <div class="love-header">
            <h4>Why you'll love shopping with ZALORA</h4>
            <a href="#">Learn more</a>
        </div>
        <div class="love-cards">
            <div class="love-card love-card-1">
                100% authentic products
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg>
            </div>
            <div class="love-card love-card-2">
                30 day free* return/exchanges | T&C Apply
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
            </div>
            <div class="love-card love-card-3">
                Fast & reliable delivery
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/><path d="M2 12h4M2 8h3M2 16h3"/></svg>
            </div>
        </div>

        <div class="info-accordion">
            <div class="acc-item">
                <div class="acc-header">
                    Product Information
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>
                </div>
                <div class="acc-body">
                    <h4 style="font-size:13px; font-weight:700; margin-bottom:15px;">Material & Care</h4>
                    <div class="acc-grid">
                        <div class="acc-label"><svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:5px; vertical-align:middle;"><path d="M6 6l12 12M6 18L18 6"/></svg> Material</div>
                        <div>100% COTTON</div>
                    </div>
                    <div class="acc-grid">
                        <div class="acc-label"><svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:5px; vertical-align:middle;"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3"/></svg> Care Label</div>
                        <div>Machine Wash Cold Inside Out, Mild Wash; "Low Iron"; "Do Not Tumble Dry"; "Do not bleach"; "Do Not Dry Clean</div>
                    </div>

                    <h4 style="font-size:13px; font-weight:700; margin-bottom:15px;">Details</h4>
                    <div style="margin-bottom:8px;"><strong>SKU:</strong> CE207AA4B213F3GS</div>
                    <div><strong>Color:</strong> white</div>
                </div>
            </div>
            <div class="acc-item">
                <div class="acc-header">
                    About the Product
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- BOTTOM REVIEWS & SELLER (Aligned Right per screenshot logic, but contained) -->
<div class="bottom-section">
    <div class="bottom-right">
        
        <div class="rating-overview">
            <h3>Ratings & Reviews</h3>
            <div style="display:flex; align-items:center;">
                <span class="score">5/5</span>
                <span class="stars-lg">★★★★★</span>
                <span class="rev-count">(1 reviews)</span>
            </div>
        </div>

        <h4 style="font-size:11px; font-weight:700; margin-bottom:15px;">Reviews</h4>
        <div class="rev-item">
            <div class="rev-author">
                <span class="rev-stars">★★★★★</span>
                <span class="rev-date">06 Aug 2025</span>
            </div>
            <div class="rev-author" style="margin-bottom:8px; color:#000;">By N*O</div>
            <div class="rev-tags">
                <span class="rev-tag">True to size</span>
                <span class="rev-tag">Match the description</span>
            </div>
            <div class="rev-size">Size: EU 34 | Purchased on: 31 Jul 2025</div>
        </div>

        <div class="seller-section">
            <h3>Meet the Seller</h3>
            <div class="seller-banner-top">
                <strong>Desigual</strong>
                <a href="#">Visit store</a>
            </div>
            <div class="seller-banner">
                <img src="https://images.unsplash.com/photo-1544441893-675973e31985?w=800&q=80" alt="Seller Banner">
                <div class="seller-logo">Desigual.</div>
            </div>
            <div class="seller-stats">
                <div class="stat-item"><svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> Seller since 2022</div>
                <div class="stat-item"><svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg> Ships from Malaysia</div>
                <div class="stat-item"><svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg> Over 10k items sold</div>
                <div class="stat-item"><svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg> Verified seller</div>
            </div>
        </div>

    </div>
</div>

<!-- RECOMMENDED -->
<div class="recom-section">
    <div class="recom-header">
        <h2>Recommended for you</h2>
        <p>Sponsored Ads</p>
    </div>
    <div class="recom-grid">
        <?php foreach($complete_look as $item): ?>
        <a href="product.php?id=<?= $item['id'] ?>" class="recom-card" style="text-decoration:none; color:inherit;">
            <div class="recom-img">
                <img src="<?= htmlspecialchars($item['img']) ?>" alt="Rec">
            </div>
            <div class="recom-brand"><?= htmlspecialchars($item['brand']) ?></div>
            <div class="recom-name"><?= htmlspecialchars($item['name']) ?></div>
            <div class="recom-price">Php <?= number_format($item['price'], 2) ?></div>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<div class="floating-actions">
    <button class="float-btn float-btn-z">Z</button>
    <button class="float-btn float-btn-up" onclick="window.scrollTo({top: 0, behavior: 'smooth'})">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><polyline points="18 15 12 9 6 15"></polyline></svg>
    </button>
</div>

<script>
    const variants = <?= json_encode($variants) ?>;
    let selectedColor = "<?= !empty($colors) ? $colors[0] : '' ?>";
    let selectedSize = "<?= !empty($sizes) ? ($sizes[5] ?? $sizes[0]) : '' ?>"; 

    function updateSelectedVariant() {
        const variant = variants.find(v => 
            (v.PVar_Color === selectedColor || !v.PVar_Color) && 
            (v.PVar_Size === selectedSize || !v.PVar_Size)
        );
        if (variant) {
            document.getElementById('selected-pvar-id').value = variant.PVar_Id;
        }
    }

    function selectSize(btn) {
        document.querySelectorAll('.size-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        selectedSize = btn.dataset.size;
        document.getElementById('size-label').textContent = "EU " + selectedSize;
        updateSelectedVariant();
    }

    async function toggleWish(btn) {
        const pvarId = document.getElementById('selected-pvar-id').value;
        if (!pvarId) return;

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
                    btn.innerHTML = '<svg width="20" height="20" fill="currentColor" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>';
                } else {
                    btn.innerHTML = '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>';
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