<?php
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
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --black: #0a0a0a;
            --white: #fafafa;
            --grey: #888;
            --light-grey: #e8e8e8;
            --accent: #c8a96e;
            --font-display: 'Cormorant Garamond', serif;
            --font-body: 'Montserrat', sans-serif;
        }

        html { scroll-behavior: smooth; }

        body {
            background: var(--white);
            color: var(--black);
            font-family: var(--font-body);
            font-size: 13px;
            letter-spacing: 0.04em;
        }

        /* ── NAV ── */
        nav {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 100;
            background: rgba(250,250,250,0.96);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid var(--light-grey);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2.5rem;
            height: 56px;
        }

        .nav-logo {
            font-family: var(--font-body);
            font-weight: 700;
            font-size: 1.25rem;
            letter-spacing: 0.18em;
            color: var(--black);
            text-decoration: none;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            list-style: none;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--black);
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.12em;
            padding-bottom: 2px;
            border-bottom: 1px solid transparent;
            transition: border-color 0.2s;
        }

        .nav-links a:hover { border-color: var(--black); }

        .nav-actions {
            display: flex;
            gap: 1.2rem;
            align-items: center;
        }

        .nav-actions button {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            color: var(--black);
            padding: 4px;
            transition: opacity 0.2s;
        }

        .nav-actions button:hover { opacity: 0.5; }

        .nav-search {
            display: flex;
            align-items: center;
            gap: 6px;
            border-bottom: 1px solid var(--light-grey);
            padding-bottom: 2px;
        }

        .nav-search input {
            border: none;
            outline: none;
            background: transparent;
            font-family: var(--font-body);
            font-size: 11px;
            letter-spacing: 0.08em;
            width: 120px;
            color: var(--black);
        }

        /* ── HERO ── */
        .hero {
            margin-top: 56px;
            position: relative;
            height: calc(100vh - 56px);
            min-height: 560px;
            overflow: hidden;
        }

        .hero-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center top;
            display: block;
            filter: brightness(0.82);
            transform: scale(1.04);
            animation: heroZoom 8s ease forwards;
        }

        @keyframes heroZoom {
            to { transform: scale(1); }
        }

        .hero-content {
            position: absolute;
            bottom: 10%;
            left: 5%;
            color: var(--white);
            animation: fadeUp 1.2s ease 0.3s both;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(30px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .hero-label {
            font-size: 10px;
            letter-spacing: 0.25em;
            font-weight: 500;
            margin-bottom: 0.8rem;
            opacity: 0.85;
        }

        .hero-title {
            font-family: var(--font-display);
            font-size: clamp(3rem, 7vw, 5.5rem);
            font-weight: 300;
            line-height: 1.0;
            margin-bottom: 2rem;
            max-width: 600px;
        }

        .hero-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn-primary {
            background: var(--white);
            color: var(--black);
            border: none;
            padding: 12px 28px;
            font-family: var(--font-body);
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.18em;
            cursor: pointer;
            transition: background 0.25s, color 0.25s;
            text-transform: uppercase;
        }

        .btn-primary:hover { background: var(--accent); color: var(--white); }

        .btn-outline {
            background: transparent;
            color: var(--white);
            border: 1px solid rgba(255,255,255,0.7);
            padding: 12px 28px;
            font-family: var(--font-body);
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.18em;
            cursor: pointer;
            transition: background 0.25s, color 0.25s, border-color 0.25s;
            text-transform: uppercase;
        }

        .btn-outline:hover { background: var(--white); color: var(--black); border-color: var(--white); }

        /* ── CATEGORIES GRID ── */
        .categories {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3px;
            background: #ccc;
            margin-bottom: 3px;
        }

        .category-tile {
            position: relative;
            overflow: hidden;
            aspect-ratio: 4/3;
            cursor: pointer;
        }

        .category-tile img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.6s ease, filter 0.4s ease;
            filter: brightness(0.75);
        }

        .category-tile:hover img {
            transform: scale(1.06);
            filter: brightness(0.6);
        }

        .category-label {
            position: absolute;
            bottom: 0; left: 0; right: 0;
            padding: 1.2rem 1.4rem;
            color: var(--white);
        }

        .category-label h3 {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.2em;
            margin-bottom: 3px;
        }

        .category-label p {
            font-size: 10px;
            font-weight: 300;
            letter-spacing: 0.12em;
            opacity: 0.85;
        }

        /* ── EDITORIAL ── */
        .editorial {
            padding: 5rem 2.5rem;
        }

        .section-header {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            margin-bottom: 2.5rem;
        }

        .section-label {
            font-size: 10px;
            letter-spacing: 0.22em;
            color: var(--grey);
            font-weight: 500;
            margin-bottom: 0.4rem;
        }

        .section-title {
            font-family: var(--font-display);
            font-size: clamp(1.6rem, 3vw, 2.4rem);
            font-weight: 400;
            letter-spacing: 0.02em;
        }

        .view-all {
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--black);
            text-decoration: none;
            border-bottom: 1px solid var(--black);
            padding-bottom: 2px;
            white-space: nowrap;
            transition: color 0.2s, border-color 0.2s;
        }

        .view-all:hover { color: var(--accent); border-color: var(--accent); }

        .editorial-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
        }

        .look-card {
            cursor: pointer;
        }

        .look-img-wrap {
            position: relative;
            overflow: hidden;
            margin-bottom: 1rem;
        }

        .look-img-wrap img {
            width: 100%;
            aspect-ratio: 3/4;
            object-fit: cover;
            display: block;
            transition: transform 0.5s ease;
        }

        .look-card:hover .look-img-wrap img { transform: scale(1.04); }

        .look-wish {
            position: absolute;
            top: 12px; right: 12px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.1rem;
            color: var(--white);
            text-shadow: 0 1px 4px rgba(0,0,0,0.4);
            transition: transform 0.2s, color 0.2s;
        }

        .look-wish:hover { transform: scale(1.3); color: #e74c3c; }

        .look-number {
            font-size: 9px;
            letter-spacing: 0.2em;
            color: var(--grey);
            margin-bottom: 4px;
        }

        .look-title {
            font-family: var(--font-display);
            font-size: 1.1rem;
            font-weight: 400;
            margin-bottom: 3px;
        }

        .look-pieces {
            font-size: 10px;
            color: var(--grey);
            margin-bottom: 6px;
            letter-spacing: 0.06em;
        }

        .look-price {
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .btn-shop {
            width: 100%;
            background: var(--black);
            color: var(--white);
            border: none;
            padding: 11px;
            font-family: var(--font-body);
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            cursor: pointer;
            transition: background 0.25s;
        }

        .btn-shop:hover { background: var(--accent); }

        /* ── INNER CIRCLE ── */
        .inner-circle {
            display: grid;
            grid-template-columns: 1fr 1fr;
            border-top: 1px solid var(--light-grey);
            border-bottom: 1px solid var(--light-grey);
            margin: 0 2.5rem 5rem;
        }

        .inner-circle-img {
            width: 100%;
            height: 100%;
            min-height: 340px;
            object-fit: cover;
            object-position: center;
            display: block;
            filter: grayscale(30%);
        }

        .inner-circle-content {
            padding: 4rem 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .inner-circle-label {
            font-size: 9px;
            letter-spacing: 0.28em;
            color: var(--grey);
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 1.2rem;
        }

        .inner-circle-title {
            font-family: var(--font-display);
            font-size: clamp(1.8rem, 3vw, 2.8rem);
            font-weight: 300;
            line-height: 1.1;
            margin-bottom: 2rem;
        }

        .subscribe-row {
            display: flex;
            border-bottom: 1px solid var(--black);
            margin-bottom: 1rem;
        }

        .subscribe-row input {
            flex: 1;
            border: none;
            outline: none;
            background: transparent;
            font-family: var(--font-body);
            font-size: 11px;
            letter-spacing: 0.08em;
            padding: 8px 0;
            color: var(--black);
        }

        .subscribe-row input::placeholder { color: var(--grey); }

        .subscribe-btn {
            background: none;
            border: none;
            font-family: var(--font-body);
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            cursor: pointer;
            color: var(--black);
            padding: 8px 0 8px 12px;
            transition: color 0.2s;
        }

        .subscribe-btn:hover { color: var(--accent); }

        .subscribe-note {
            font-size: 9px;
            color: var(--grey);
            line-height: 1.6;
            letter-spacing: 0.04em;
        }

        /* ── FOOTER ── */
        footer {
            background: var(--black);
            color: rgba(255,255,255,0.7);
            padding: 4rem 2.5rem 2rem;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 3rem;
            margin-bottom: 3rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding-bottom: 3rem;
        }

        .footer-brand {
            font-family: var(--font-body);
            font-weight: 700;
            font-size: 1.1rem;
            letter-spacing: 0.18em;
            color: var(--white);
            margin-bottom: 1rem;
        }

        .footer-desc {
            font-size: 11px;
            line-height: 1.8;
            max-width: 220px;
        }

        .footer-col h4 {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--white);
            margin-bottom: 1.2rem;
        }

        .footer-col ul { list-style: none; }

        .footer-col ul li {
            margin-bottom: 0.7rem;
        }

        .footer-col ul li a {
            text-decoration: none;
            color: rgba(255,255,255,0.55);
            font-size: 11px;
            transition: color 0.2s;
        }

        .footer-col ul li a:hover { color: var(--accent); }

        .footer-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 10px;
            color: rgba(255,255,255,0.35);
            flex-wrap: wrap;
            gap: 1rem;
        }

        /* ── RESPONSIVE ── */
        @media (max-width: 768px) {
            .nav-links { display: none; }
            .categories { grid-template-columns: 1fr; }
            .editorial-grid { grid-template-columns: 1fr; }
            .inner-circle { grid-template-columns: 1fr; }
            .inner-circle-img { min-height: 220px; }
            .footer-grid { grid-template-columns: 1fr 1fr; }
        }
    </style>
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
    <a href="auth/login.php" title="Account" style="color:var(--black);display:flex;align-items:center;">
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