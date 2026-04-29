<?php
session_start();
// Static product data
$editorial_looks = [
    [
        "id" => 1,
        "title" => "SOFT UTILITY",
        "subtitle" => "3 Piece Collection",
        "price" => "From $249.00",
        "img" => "https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=400&q=80",
    ],
    [
        "id" => 2,
        "title" => "URBAN ARMATURE",
        "subtitle" => "2 Piece Collection",
        "price" => "From $310.00",
        "img" => "https://images.unsplash.com/photo-1529139574466-a303027c1d8b?w=400&q=80",
    ],
    [
        "id" => 3,
        "title" => "SILK MINIMALISM",
        "subtitle" => "1 Piece Collection",
        "price" => "From $185.00",
        "img" => "https://images.unsplash.com/photo-1496747611176-843222e1e57c?w=400&q=80",
    ],
];

$categories = [
    [
        "name" => "FOOTWEAR",
        "sub" => "Sculpted Silhouettes",
        "img" => "https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=600&q=80",
        "span" => "col-span-1",
    ],
    [
        "name" => "APPARELS",
        "sub" => "Essential Tailoring",
        "img" => "https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=600&q=80",
        "span" => "col-span-1",
    ],
    [
        "name" => "ACCESSORIES",
        "sub" => "Bold Accents",
        "img" => "https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=600&q=80",
        "span" => "col-span-1",
    ],
    [
        "name" => "BEAUTY",
        "sub" => "Conscious Radiance",
        "img" => "https://images.unsplash.com/photo-1596462502278-27bfdc403348?w=600&q=80",
        "span" => "col-span-1",
    ],
];

$nav_links = ["WOMEN", "MEN", "KIDS", "BEAUTY", "LUXURY"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>ZALORA — New Collection 2024</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="assets/css/global.css"/>
    <link rel="stylesheet" href="assets/css/index.css"/>
   
</head>
<body>

<!-- ── NAVIGATION ── -->
<nav>
    <a href="#" class="nav-logo">ZALORA</a>
    <ul class="nav-links">
        <?php foreach ($nav_links as $link): ?>
            <li><a href="#"><?= htmlspecialchars($link) ?></a></li>
        <?php endforeach; ?>
    </ul>
    <div class="nav-actions">
    <div class="nav-search">
        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
        <input type="text" placeholder="Search" />
    </div>
    <a href="<?= isset($_SESSION['user_id']) ? 'customer/profile.php' : 'auth/login.php' ?>" title="Account" style="color:var(--black);display:flex;align-items:center;">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
    </a>
    <a href="auth/login.php" title="Wishlist" style="color:var(--black);display:flex;align-items:center;">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
    </a>
    <a href="auth/login.php" title="Cart" style="color:var(--black);display:flex;align-items:center;">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
    </a>
</div>
</nav>

<!-- ── HERO ── -->
<section class="hero">
    <img
        class="hero-img"
        src="https://images.unsplash.com/photo-1469334031218-e382a71b716b?w=1600&q=85"
        alt="The Architectural Minimalist"
    />
    <div class="hero-content">
        <p class="hero-label">NEW COLLECTION 2024</p>
        <h1 class="hero-title">The Architectural<br>Minimalist</h1>
        <div class="hero-actions">
            <button class="btn-primary">Shop Women</button>
            <button class="btn-outline">Discover Editorial</button>
        </div>
    </div>
</section>

<!-- ── CATEGORY TILES ── -->
<section class="categories">
    <?php foreach ($categories as $cat): ?>
    <div class="category-tile">
        <img src="<?= htmlspecialchars($cat['img']) ?>" alt="<?= htmlspecialchars($cat['name']) ?>" loading="lazy" />
        <div class="category-label">
            <h3><?= htmlspecialchars($cat['name']) ?></h3>
            <p><?= htmlspecialchars($cat['sub']) ?></p>
        </div>
    </div>
    <?php endforeach; ?>
</section>

<!-- ── EDITORIAL / SHOP THE LOOK ── -->
<section class="editorial">
    <div class="section-header">
        <div>
            <p class="section-label">Curated for You</p>
            <h2 class="section-title">Shop the Editorial</h2>
        </div>
        <a href="#" class="view-all">View All Looks</a>
    </div>

    <div class="editorial-grid">
        <?php foreach ($editorial_looks as $i => $look): ?>
        <div class="look-card">
            <div class="look-img-wrap">
                <img src="<?= htmlspecialchars($look['img']) ?>" alt="<?= htmlspecialchars($look['title']) ?>" loading="lazy" />
                <button class="look-wish" title="Wishlist">♡</button>
            </div>
            <p class="look-number">Look <?= str_pad($i + 1, 2, '0', STR_PAD_LEFT) ?></p>
            <h3 class="look-title"><?= htmlspecialchars($look['title']) ?></h3>
            <p class="look-pieces"><?= htmlspecialchars($look['subtitle']) ?></p>
            <p class="look-price"><?= htmlspecialchars($look['price']) ?></p>
            <button class="btn-shop">Shop Full Look</button>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- ── INNER CIRCLE ── -->
<div class="inner-circle">
    <img
        class="inner-circle-img"
        src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=800&q=80"
        alt="Zalora Privé"
    />
    <div class="inner-circle-content">
        <p class="inner-circle-label">The Zalora Privé</p>
        <h2 class="inner-circle-title">Join the Inner Circle<br>for Exclusive<br>Previews.</h2>
        <div class="subscribe-row">
            <input type="email" placeholder="Email Address" />
            <button class="subscribe-btn">Subscribe</button>
        </div>
        <p class="subscribe-note">By subscribing, you agree to our Terms of Use and Privacy Policy. You may unsubscribe at any time.</p>
    </div>
</div>

<!-- ── FOOTER ── -->
<footer>
    <div class="footer-grid">
        <div>
            <div class="footer-brand">ZALORA</div>
            <p class="footer-desc">Asia's leading online fashion destination. New arrivals daily, free returns, and exclusive brand partnerships.</p>
        </div>
        <div class="footer-col">
            <h4>Help</h4>
            <ul>
                <li><a href="#">Customer Service</a></li>
                <li><a href="#">Returns</a></li>
                <li><a href="#">Terms of Use</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Company</h4>
            <ul>
                <li><a href="#">About Us</a></li>
                <li><a href="#">Press</a></li>
                <li><a href="#">Careers</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Connect</h4>
            <ul>
                <li><a href="#">Instagram</a></li>
                <li><a href="#">Facebook</a></li>
                <li><a href="#">TikTok</a></li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        <span>© <?= date('Y') ?> ZALORA. All rights reserved.</span>
        <span>💳 Visa &nbsp; Mastercard &nbsp; GCash</span>
    </div>
</footer>

<script>
    // Wishlist heart toggle
    document.querySelectorAll('.look-wish').forEach(btn => {
        btn.addEventListener('click', () => {
            btn.textContent = btn.textContent === '♡' ? '♥' : '♡';
            btn.style.color = btn.textContent === '♥' ? '#e74c3c' : 'white';
        });
    });
</script>

</body>
</html>