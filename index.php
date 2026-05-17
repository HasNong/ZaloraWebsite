<?php
session_start();
require_once 'config/db.php';
include 'customer/nav_counts.php';

// Fetch Featured Products for "Steals You Can't Miss"
$prod_query = "SELECT p.*, pi.PImg_ImgUrl, b.Brand_Name 
               FROM product p
               LEFT JOIN product_image pi ON p.Prod_Id = pi.Prod_Id AND pi.PImg_IsPrimary = 1
               LEFT JOIN brand b ON p.Brand_Id = b.Brand_Id
               WHERE p.Prod_IsActive = 1
               LIMIT 8";
$products = $conn->query($prod_query);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZALORA | Asia's Leading Fashion Destination</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&family=Cormorant+Garamond:ital,wght@1,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./assets/css/index.css?v=<?= time() ?>">
    <link rel="stylesheet" href="./assets/css/global.css?v=<?= time() ?>">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Montserrat', sans-serif; background: #fff; color: #000; overflow-x: hidden; }
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
        .brand-icons-section { max-width: 1200px; margin: 40px auto; display: flex; justify-content: space-between; padding: 0 20px; }
        .brand-icon-item { text-align: center; text-decoration: none; color: #000; width: 100px; }
        .brand-circle { width: 70px; height: 70px; border-radius: 50%; margin: 0 auto 10px; display: flex; align-items: center; justify-content: center; transition: 0.3s; }
        .brand-promo-text { font-size: 10px; font-weight: 700; text-transform: uppercase; }
        .main-hero { max-width: 1400px; margin: 0 auto 60px; padding: 0 20px; }
        .hero-card { display: flex; background: #8b9d77; min-height: 500px; }
        .hero-image-side, .hero-text-side { flex: 1; }
        .hero-image-side img { width: 100%; height: 100%; object-fit: cover; }
        .hero-text-side { padding: 80px; color: #fff; display: flex; flex-direction: column; justify-content: center; }
        .hero-brand { font-family: 'Cormorant Garamond', serif; font-size: 80px; font-style: italic; line-height: 1; margin: 10px 0 20px; }
        .btn-hero-shop { border: 2px solid #fff; padding: 12px 30px; color: #fff; text-decoration: none; font-weight: 700; width: fit-content; }
        .brand-deals-grid { max-width: 1200px; margin: 0 auto 60px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; padding: 0 20px; }
        .deal-card { position: relative; aspect-ratio: 3/4; overflow: hidden; }
        .deal-card img { width: 100%; height: 100%; object-fit: cover; }
        .deal-overlay { position: absolute; bottom: 0; left: 0; right: 0; padding: 20px; background: linear-gradient(transparent, rgba(0,0,0,0.8)); color: #fff; }
        .product-carousel-wrap { max-width: 1400px; margin: 0 auto 80px; padding: 0 20px; }
        .carousel-scroll { display: flex; gap: 20px; overflow-x: auto; padding-bottom: 20px; }
        .product-card-premium { flex: 0 0 250px; }
        .prod-img-box { aspect-ratio: 3/4; margin-bottom: 10px; }
        .prod-img-box img { width: 100%; height: 100%; object-fit: cover; }
        .btn-add-bag-slim { width: 100%; padding: 10px; border: 1px solid #000; background: #fff; font-weight: 700; cursor: pointer; text-transform: uppercase; font-size: 11px; }
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
        <a href="index.php" class="logo">ZALORA</a>
        
        <div class="search-bar-wrap">
            <form action="customer/products.php" method="GET">
                <input type="text" name="q" class="search-input" placeholder="Got You Scoring More">
                <button type="submit" class="search-icon-btn">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                </button>
            </form>
        </div>

        <div class="header-actions">
            <a href="<?= isset($_SESSION['user_id']) ? 'customer/profile.php' : 'auth/login.php' ?>" class="header-action-item">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <span><?= isset($_SESSION['user_id']) ? 'Hi ' . htmlspecialchars($nav_user_name) : 'Login / Register' ?></span>
            </a>
            <a href="customer/wishlist.php" class="header-action-item" style="position:relative;">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                <?php if ($nav_wish_count > 0): ?><span class="badge-count"><?= $nav_wish_count ?></span><?php endif; ?>
            </a>
            <a href="customer/cart.php" class="header-action-item" style="position:relative;">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                <?php if ($nav_cart_count > 0): ?><span class="badge-count"><?= $nav_cart_count ?></span><?php endif; ?>
            </a>
        </div>
    </div>

    <nav class="nav-bar">
        <div class="nav-container">
            <a href="customer/products.php?gender=women" class="nav-item">WOMEN</a>
            <a href="customer/products.php?gender=men" class="nav-item">MEN</a>
            <a href="customer/products.php?category=kids" class="nav-item">KIDS</a>
            <a href="customer/products.php?premium=1" class="nav-item">LUXURY</a>
            <a href="customer/products.php?category=beauty" class="nav-item">BEAUTY</a>
            <a href="customer/products.php?category=sports" class="nav-item">SPORTS</a>
        </div>
    </nav>
</header>

<!-- --- BRAND ICONS --- -->
<section class="brand-icons-section">
    <?php
    $brands_list = [
        ['name' => 'Payday Party', 'logo' => 'https://brandeps.com/logo-download/Z/Zalora-logo-vector-01.svg', 'color' => '#7a1b28', 'promo' => 'Up to 60% OFF'],
        ['name' => 'Nike', 'logo' => 'https://www.vectorlogo.zone/logos/nike/nike-ar21.svg', 'color' => '#333', 'promo' => 'Up to 40% OFF'],
        ['name' => 'Adidas', 'logo' => 'https://www.vectorlogo.zone/logos/adidas/adidas-ar21.svg', 'color' => '#000', 'promo' => 'Up to 50% OFF'],
        ['name' => 'Veja', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/1/1a/Veja_logo.svg', 'color' => '#7a1b28', 'promo' => 'Up to 30% OFF'],
        ['name' => 'Birkenstock', 'logo' => 'https://www.vectorlogo.zone/logos/birkenstock/birkenstock-ar21.svg', 'color' => '#3c2415', 'promo' => 'Shop Now'],
        ['name' => 'Jordan', 'logo' => 'https://www.vectorlogo.zone/logos/jordan/jordan-ar21.svg', 'color' => '#7a1b28', 'promo' => 'Up to 50% OFF'],
        ['name' => 'Coach', 'logo' => 'https://www.vectorlogo.zone/logos/coach/coach-ar21.svg', 'color' => '#4a1018', 'promo' => 'Up to 80% OFF'],
    ];
    foreach($brands_list as $b):
    ?>
    <a href="customer/products.php" class="brand-icon-item">
        <div class="brand-circle" style="background: <?= $b['color'] ?>;">
            <span style="color:#fff; font-size:10px; font-weight:800; text-align:center;"><?= strtoupper(substr($b['name'], 0, 2)) ?></span>
        </div>
        <div class="brand-promo-text"><?= $b['promo'] ?></div>
    </a>
    <?php endforeach; ?>
</section>

<!-- --- MAIN HERO --- -->
<section class="main-hero">
    <div class="hero-card">
        <div class="hero-image-side">
            <img src="https://images.unsplash.com/photo-1539109132304-3915502ad33d?w=1200&q=80" alt="H&M Payday Party">
        </div>
        <div class="hero-text-side">
            <p class="hero-tag">PAYDAY PARTY</p>
            <h1 class="hero-brand">H&M</h1>
            <p class="hero-offer">UP TO 50% OFF</p>
            <a href="customer/products.php" class="btn-hero-shop">SHOP NOW ></a>
            <p style="margin-top: 40px; font-size: 10px; opacity: 0.8;">T&Cs apply. DTI Fair Trade Permit No. FTEB-201672 Series of 2026</p>
        </div>
    </div>
</section>

<div class="section-header-wrap">
    <h2 class="section-title-main">Got You Splurging</h2>
</div>

<!-- --- TOP BRAND DEALS --- -->
<section class="brand-deals-grid">
    <div class="deal-card">
        <img src="https://images.unsplash.com/photo-1581044777550-4cfa60707c03?w=600&q=80" alt="Desigual">
        <div class="deal-overlay">
            <p class="deal-offer">UP TO 60% OFF</p>
        </div>
    </div>
    <div class="deal-card">
        <img src="https://images.unsplash.com/photo-1512436991641-6745cdb1723f?w=600&q=80" alt="Adidas">
        <div class="deal-overlay">
            <p class="deal-offer">UP TO 70% OFF</p>
        </div>
    </div>
    <div class="deal-card">
        <img src="https://images.unsplash.com/photo-1445205170230-053b830c6039?w=600&q=80" alt="Puma">
        <div class="deal-overlay">
            <p class="deal-offer">UP TO 50% OFF</p>
        </div>
    </div>
</section>

<!-- --- STEALS YOU CAN'T MISS --- -->
<section class="product-carousel-wrap">
    <div class="section-header-wrap">
        <h2 class="section-title-main">Steals You Can't Miss</h2>
    </div>
    <div class="carousel-scroll">
        <?php if ($products->num_rows > 0): ?>
            <?php while($p = $products->fetch_assoc()): ?>
            <div class="product-card-premium">
                <div class="prod-img-box">
                    <img src="<?= $p['PImg_ImgUrl'] ? $p['PImg_ImgUrl'] : 'https://via.placeholder.com/300x400' ?>" alt="<?= htmlspecialchars($p['Prod_Name']) ?>">
                    <button class="wishlist-btn-overlay">
                        <svg width="20" height="20" fill="none" stroke="#000" stroke-width="1.5" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                    </button>
                </div>
                <div class="prod-info-box">
                    <p class="prod-brand-name"><?= htmlspecialchars($p['Brand_Name'] ?? 'ZALORA') ?></p>
                    <p class="prod-title-text"><?= htmlspecialchars($p['Prod_Name']) ?></p>
                    <div class="price-row">
                        <span class="current-price">$<?= number_format($p['Prod_BasePrice'], 2) ?></span>
                        <span class="old-price">$<?= number_format($p['Prod_BasePrice'] * 1.5, 2) ?></span>
                        <span class="discount-tag">-33%</span>
                    </div>
                    <form action="customer/add_to_cart.php" method="POST">
                        <input type="hidden" name="prod_id" value="<?= $p['Prod_Id'] ?>">
                        <button type="submit" class="btn-add-bag-slim">Add to Bag</button>
                    </form>
                </div>
            </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
</section>

<!-- --- STYLE INSPO --- -->
<div class="section-header-wrap">
    <h2 class="section-title-main">Trending Now, Your Style Inspo</h2>
</div>
<section class="brand-deals-grid">
    <div class="deal-card">
        <img src="https://images.unsplash.com/photo-1532332248682-206cc786359f?w=600&q=80" alt="Football">
        <div class="deal-overlay">
            <p style="font-size:16px; font-weight:800;">FOOTBALL SEASON 2026</p>
            <p style="font-size:10px;">Match day, style your way</p>
        </div>
    </div>
    <div class="deal-card">
        <img src="https://images.unsplash.com/photo-1544441893-675973e31985?w=600&q=80" alt="Polish">
        <div class="deal-overlay">
            <p style="font-size:16px; font-weight:800;">POLISH MEETS POWER</p>
            <p style="font-size:10px;">Sharp looks and bolder energy</p>
        </div>
    </div>
    <div class="deal-card">
        <img src="https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=600&q=80" alt="Shades">
        <div class="deal-overlay">
            <p style="font-size:16px; font-weight:800;">SUN'S OUT, SHADES ON</p>
            <p style="font-size:10px;">Sharp silhouettes for brighter scenes</p>
        </div>
    </div>
</section>

<!-- --- MOST-COVETED LABELS --- -->
<div class="section-header-wrap">
    <h2 class="section-title-main">Most-Coveted Labels</h2>
</div>
<section class="brand-deals-grid" style="margin-bottom: 100px;">
    <div class="deal-card" style="background: #000; display: flex;">
        <img src="https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=600&q=80" alt="Nike" style="width: 50%;">
        <div style="width: 50%; padding: 20px; display: flex; flex-direction: column; justify-content: center; color: #fff;">
             <img src="https://www.vectorlogo.zone/logos/nike/nike-ar21.svg" style="width: 40px; filter: brightness(0) invert(1); margin-bottom: 15px;">
             <p style="font-size: 14px; font-weight: 800; text-transform: uppercase;">ZOOM VOMERO 5 & P6000</p>
             <p style="font-size: 10px; margin-top: 5px;">Y2K archive back to the streets.</p>
        </div>
    </div>
    <div class="deal-card" style="background: #000; display: flex;">
        <img src="https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=600&q=80" alt="Label" style="width: 50%;">
        <div style="width: 50%; padding: 20px; display: flex; flex-direction: column; justify-content: center; color: #fff;">
             <p style="font-family: 'Cormorant Garamond', serif; font-style: italic; font-size: 24px; margin-bottom: 10px;">Ray-Ban</p>
             <p style="font-size: 12px; font-weight: 700;">Unfiltered Confidence</p>
             <p style="font-size: 10px;">With Jennie.</p>
        </div>
    </div>
    <div class="deal-card">
        <img src="https://images.unsplash.com/photo-1596462502278-27bfdc403348?w=600&q=80" alt="Beauty">
    </div>
</section>

<!-- --- FLOATING ACTIONS --- -->
<div class="floating-actions">
    <button class="float-btn float-btn-z">Z</button>
    <button class="float-btn float-btn-up" onclick="window.scrollTo({top: 0, behavior: 'smooth'})">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><polyline points="18 15 12 9 6 15"></polyline></svg>
    </button>
</div>

<footer class="zalora-footer-premium">
    <div class="footer-main-grid">
        <div class="footer-col">
            <h4 style="font-size:13px; margin-bottom:20px;">CUSTOMER SERVICE</h4>
            <ul style="list-style:none; padding:0; font-size:11px; color:#666; line-height:2.5;">
                <li>FAQ</li>
                <li>Returns & Refunds</li>
                <li>Size Guide</li>
                <li>Shipping</li>
            </ul>
        </div>
        <div class="footer-col">
            <h4 style="font-size:13px; margin-bottom:20px;">ABOUT ZALORA</h4>
            <ul style="list-style:none; padding:0; font-size:11px; color:#666; line-height:2.5;">
                <li>Our Story</li>
                <li>ZALORA VIP</li>
                <li>Sustainability</li>
                <li>Press</li>
            </ul>
        </div>
        <div class="footer-col">
            <h4 style="font-size:13px; margin-bottom:20px;">FOR SELLERS</h4>
            <ul style="list-style:none; padding:0; font-size:11px; color:#666; line-height:2.5;">
                <li>Sell with Us</li>
                <li>Seller Center</li>
            </ul>
        </div>
        <div class="footer-col">
            <h4 style="font-size:13px; margin-bottom:20px;">CONNECT WITH US</h4>
            <ul style="list-style:none; padding:0; font-size:11px; color:#666; line-height:2.5;">
                <li>Facebook</li>
                <li>Instagram</li>
                <li>YouTube</li>
            </ul>
        </div>
    </div>
</footer>

</body>
</html>