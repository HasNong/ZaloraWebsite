<?php
session_start();

$product = [
    "collection"  => "ARCHIVAL COLLECTION",
    "name"        => "WOOL STRUCTURED BLAZER",
    "price"       => 289.00,
    "description" => "A cornerstone of the Archival Collection. This blazer features a structured silhouette crafted from ethically sourced virgin wool. Designed for versatility, it transitions seamlessly from formal environments to elevated casual wear.",
    "colors" => [
        ["name" => "Midnight Black", "hex" => "#1a1a1a"],
        ["name" => "Slate Grey",     "hex" => "#9e9e9e"],
        ["name" => "Ash",            "hex" => "#d0d0d0"],
    ],
    "sizes" => ["XS", "S", "M", "L"],
    "images" => [
        "https://images.unsplash.com/photo-1507679799987-c73779587ccf?w=800&q=80",
        "https://images.unsplash.com/photo-1593030761757-71fae45fa0e7?w=800&q=80",
        "https://images.unsplash.com/photo-1548718135-8e4cdc28d8a3?w=800&q=80",
    ],
];

$complete_look = [
    ["brand" => "ARCHIVE",      "name" => "Wide-Leg Wool Trouser",  "price" => 145.00, "img" => "https://images.unsplash.com/photo-1473966968600-fa801b869a1a?w=400&q=80"],
    ["brand" => "ZALORA LUXURY","name" => "Pointed Leather Boot",   "price" => 210.00, "img" => "https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=400&q=80"],
    ["brand" => "THE STUDIO",   "name" => "Structured Totem Bag",   "price" => 180.00, "img" => "https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=400&q=80"],
    ["brand" => "FINE JEWELRY", "name" => "24K Sculpted Hoops",     "price" => 85.00,  "img" => "https://images.unsplash.com/photo-1599643477877-530eb83abc8e?w=400&q=80"],
];

$nav_links = ["WOMEN", "MEN", "KIDS", "LUXURY", "BEAUTY"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>ZALORA — <?= htmlspecialchars($product['name']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;1,300&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --black: #0a0a0a; --white: #fafafa; --grey: #888;
            --light-grey: #e8e8e8; --accent: #c8a96e;
            --font-display: 'Cormorant Garamond', serif;
            --font-body: 'Montserrat', sans-serif;
        }
        body { font-family: var(--font-body); background: var(--white); color: var(--black); font-size: 13px; letter-spacing: 0.04em; }

        /* NAV */
        nav { position: sticky; top: 0; z-index: 100; background: rgba(250,250,250,0.97); backdrop-filter: blur(8px); border-bottom: 1px solid var(--light-grey); display: flex; align-items: center; justify-content: space-between; padding: 0 2.5rem; height: 56px; }
        .nav-logo { font-weight: 700; font-size: 1.15rem; letter-spacing: 0.18em; color: var(--black); text-decoration: none; }
        .nav-links { display: flex; gap: 2rem; list-style: none; }
        .nav-links a { text-decoration: none; color: var(--black); font-size: 11px; font-weight: 600; letter-spacing: 0.12em; padding-bottom: 3px; border-bottom: 2px solid transparent; transition: border-color 0.2s; }
        .nav-links a.active, .nav-links a:hover { border-color: var(--black); }
        .nav-actions { display: flex; gap: 1.2rem; align-items: center; }
        .nav-actions a { color: var(--black); display: flex; align-items: center; position: relative; text-decoration: none; transition: opacity 0.2s; }
        .nav-actions a:hover { opacity: 0.5; }
        .cart-badge { position: absolute; top: -6px; right: -8px; background: var(--black); color: var(--white); font-size: 8px; font-weight: 700; width: 16px; height: 16px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }

        /* PRODUCT SECTION */
        .product-section { display: grid; grid-template-columns: 1fr 360px; }

        /* IMAGE PANEL */
        .image-panel { display: grid; grid-template-columns: 1fr 1fr; gap: 3px; background: #ddd; }
        .img-main { grid-column: 1; grid-row: 1; }
        .img-detail { grid-column: 2; grid-row: 1; }
        .img-full { grid-column: 1 / -1; grid-row: 2; }
        .image-panel img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform 0.5s ease; cursor: zoom-in; }
        .img-main, .img-detail { aspect-ratio: 3/4; }
        .img-full { aspect-ratio: 16/9; }
        .image-panel img:hover { transform: scale(1.03); }

        /* INFO PANEL */
        .info-panel { padding: 2.5rem 2rem; border-left: 1px solid var(--light-grey); position: sticky; top: 56px; height: calc(100vh - 56px); overflow-y: auto; }
        .product-collection { font-size: 9px; font-weight: 700; letter-spacing: 0.28em; color: var(--grey); text-transform: uppercase; margin-bottom: 0.6rem; }
        .product-name { font-family: var(--font-display); font-size: clamp(1.5rem, 2.5vw, 2rem); font-weight: 400; margin-bottom: 0.5rem; line-height: 1.15; }
        .product-price { font-size: 15px; font-weight: 600; margin-bottom: 2rem; }

        /* Color */
        .option-label { font-size: 9px; font-weight: 700; letter-spacing: 0.2em; text-transform: uppercase; color: var(--grey); margin-bottom: 0.7rem; }
        .color-name { font-size: 11px; font-weight: 500; color: var(--black); margin-left: 6px; }
        .color-swatches { display: flex; gap: 8px; margin-bottom: 1.6rem; }
        .color-swatch { width: 28px; height: 28px; border-radius: 50%; border: 2px solid transparent; cursor: pointer; outline: 2px solid transparent; outline-offset: 2px; transition: outline-color 0.2s, transform 0.2s; }
        .color-swatch.active, .color-swatch:hover { outline-color: var(--black); transform: scale(1.1); }

        /* Size */
        .size-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.7rem; }
        .size-guide { font-size: 9px; font-weight: 600; letter-spacing: 0.14em; text-transform: uppercase; color: var(--grey); text-decoration: underline; cursor: pointer; }
        .size-guide:hover { color: var(--black); }
        .size-grid { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 2rem; }
        .size-btn { border: 1px solid var(--light-grey); background: none; font-family: var(--font-body); font-size: 10px; font-weight: 600; letter-spacing: 0.1em; padding: 10px 16px; cursor: pointer; color: var(--black); transition: background 0.2s, border-color 0.2s, color 0.2s; }
        .size-btn:hover, .size-btn.active { background: var(--black); color: var(--white); border-color: var(--black); }

        /* CTAs */
        .btn-add { width: 100%; background: var(--black); color: var(--white); border: none; padding: 16px; font-family: var(--font-body); font-size: 11px; font-weight: 700; letter-spacing: 0.22em; text-transform: uppercase; cursor: pointer; margin-bottom: 0.9rem; transition: background 0.25s; }
        .btn-add:hover { background: #333; }
        .btn-wish { width: 100%; background: none; border: none; font-family: var(--font-body); font-size: 11px; font-weight: 600; letter-spacing: 0.14em; cursor: pointer; color: var(--black); display: flex; align-items: center; justify-content: center; gap: 7px; padding: 10px; transition: color 0.2s; margin-bottom: 2rem; }
        .btn-wish:hover { color: #e74c3c; }
        .btn-wish.liked { color: #e74c3c; }

        /* Accordion */
        .accordion { border-top: 1px solid var(--light-grey); }
        .accordion-item { border-bottom: 1px solid var(--light-grey); }
        .accordion-header { display: flex; justify-content: space-between; align-items: center; padding: 1rem 0; cursor: pointer; font-size: 11px; font-weight: 600; letter-spacing: 0.12em; user-select: none; }
        .accordion-icon { font-size: 1.2rem; font-weight: 300; color: var(--grey); transition: transform 0.25s; }
        .accordion-body { max-height: 0; overflow: hidden; transition: max-height 0.35s ease, padding 0.2s; }
        .accordion-body.open { max-height: 300px; padding-bottom: 1rem; }
        .accordion-body p { font-size: 11px; color: var(--grey); line-height: 1.8; }

        /* COMPLETE THE LOOK */
        .complete-look { padding: 4rem 2.5rem; border-top: 1px solid var(--light-grey); }
        .section-label { font-size: 10px; font-weight: 600; letter-spacing: 0.28em; text-transform: uppercase; text-align: center; color: var(--grey); margin-bottom: 2rem; }
        .look-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.2rem; }
        .look-card { cursor: pointer; }
        .look-img-wrap { overflow: hidden; margin-bottom: 0.8rem; }
        .look-img-wrap img { width: 100%; aspect-ratio: 3/4; object-fit: cover; display: block; transition: transform 0.5s ease; }
        .look-card:hover .look-img-wrap img { transform: scale(1.05); }
        .look-brand { font-size: 9px; font-weight: 700; letter-spacing: 0.2em; color: var(--grey); text-transform: uppercase; margin-bottom: 3px; }
        .look-name { font-size: 11px; font-weight: 500; margin-bottom: 4px; }
        .look-price { font-size: 11px; font-weight: 600; color: var(--grey); }

        /* PHILOSOPHY */
        .philosophy { display: grid; grid-template-columns: 1fr 1fr; border-top: 1px solid var(--light-grey); }
        .philosophy-text { padding: 4rem 3rem; display: flex; flex-direction: column; justify-content: center; }
        .philosophy-label { font-size: 9px; font-weight: 600; letter-spacing: 0.24em; color: var(--grey); text-transform: uppercase; margin-bottom: 1rem; }
        .philosophy-title { font-family: var(--font-body); font-size: clamp(1rem, 2vw, 1.3rem); font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: 1.2rem; }
        .philosophy-body { font-size: 11px; color: var(--grey); line-height: 1.9; max-width: 420px; }
        .philosophy-img { width: 100%; height: 100%; min-height: 360px; object-fit: cover; display: block; }

        /* FOOTER */
        footer { background: var(--black); color: rgba(255,255,255,0.5); padding: 2rem 2.5rem; }
        .footer-inner { max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; }
        .footer-logo { font-weight: 700; font-size: 1rem; letter-spacing: 0.18em; color: var(--white); }
        .footer-links { display: flex; gap: 2rem; flex-wrap: wrap; }
        .footer-links a { text-decoration: none; color: rgba(255,255,255,0.5); font-size: 10px; letter-spacing: 0.12em; text-transform: uppercase; transition: color 0.2s; }
        .footer-links a:hover { color: var(--accent); }
        .footer-copy { width: 100%; font-size: 9px; letter-spacing: 0.1em; text-transform: uppercase; margin-top: 1rem; color: rgba(255,255,255,0.25); }

        /* RESPONSIVE */
        @media (max-width: 900px) {
            .product-section { grid-template-columns: 1fr; }
            .image-panel { grid-template-columns: 1fr; }
            .img-main, .img-detail { grid-column: 1; grid-row: auto; }
            .img-full { grid-column: 1; }
            .info-panel { position: static; height: auto; }
            .look-grid { grid-template-columns: repeat(2, 1fr); }
            .philosophy { grid-template-columns: 1fr; }
        }
        @media (max-width: 600px) { .nav-links { display: none; } }
    </style>
</head>
<body>

<!-- NAV -->
<nav>
    <a href="index.php" class="nav-logo">ZALORA</a>
    <ul class="nav-links">
        <?php foreach ($nav_links as $i => $link): ?>
            <li><a href="#" class="<?= $i === 0 ? 'active' : '' ?>"><?= htmlspecialchars($link) ?></a></li>
        <?php endforeach; ?>
    </ul>
    <div class="nav-actions">
        <a href="#" title="Search"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg></a>
        <a href="auth/login.php" title="Account"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></a>
        <a href="#" title="Wishlist"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg></a>
        <a href="cart.php" title="Cart"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg><span class="cart-badge">3</span></a>
    </div>
</nav>

<!-- PRODUCT SECTION -->
<div class="product-section">

    <!-- IMAGES -->
    <div class="image-panel">
        <div class="img-main"><img src="<?= htmlspecialchars($product['images'][0]) ?>" alt="<?= htmlspecialchars($product['name']) ?>"/></div>
        <div class="img-detail"><img src="<?= htmlspecialchars($product['images'][1]) ?>" alt="Detail"/></div>
        <div class="img-full"><img src="<?= htmlspecialchars($product['images'][2]) ?>" alt="Lifestyle"/></div>
    </div>

    <!-- INFO -->
    <div class="info-panel">
        <p class="product-collection"><?= htmlspecialchars($product['collection']) ?></p>
        <h1 class="product-name"><?= htmlspecialchars($product['name']) ?></h1>
        <p class="product-price">$<?= number_format($product['price'], 2) ?></p>

        <!-- Color -->
        <p class="option-label">Color: <span class="color-name" id="color-label"><?= htmlspecialchars($product['colors'][0]['name']) ?></span></p>
        <div class="color-swatches">
            <?php foreach ($product['colors'] as $i => $color): ?>
            <button
                class="color-swatch <?= $i === 0 ? 'active' : '' ?>"
                style="background:<?= htmlspecialchars($color['hex']) ?>;<?= $color['hex']==='#d0d0d0'?'border:1px solid #ccc;':'' ?>"
                title="<?= htmlspecialchars($color['name']) ?>"
                data-name="<?= htmlspecialchars($color['name']) ?>"
                onclick="selectColor(this)"
            ></button>
            <?php endforeach; ?>
        </div>

        <!-- Size -->
        <div class="size-header">
            <p class="option-label" style="margin-bottom:0;">Select Size</p>
            <span class="size-guide">Size Guide</span>
        </div>
        <div class="size-grid">
            <?php foreach ($product['sizes'] as $i => $size): ?>
            <button class="size-btn <?= $i === 1 ? 'active' : '' ?>" onclick="selectSize(this)"><?= htmlspecialchars($size) ?></button>
            <?php endforeach; ?>
        </div>

        <!-- CTAs -->
        <button class="btn-add" onclick="addToBag(this)">Add to Bag</button>
        <button class="btn-wish" onclick="toggleWish(this)">♡ <span>Add to Wishlist</span></button>

        <!-- Accordion -->
        <div class="accordion">
            <div class="accordion-item">
                <div class="accordion-header" onclick="toggleAccordion(this)">
                    <span>Description</span><span class="accordion-icon">−</span>
                </div>
                <div class="accordion-body open">
                    <p><?= htmlspecialchars($product['description']) ?></p>
                </div>
            </div>
            <div class="accordion-item">
                <div class="accordion-header" onclick="toggleAccordion(this)">
                    <span>Shipping & Returns</span><span class="accordion-icon">+</span>
                </div>
                <div class="accordion-body">
                    <p>Free standard shipping on all orders. Express delivery available at checkout. Returns accepted within 30 days of delivery. Items must be unworn and in original packaging.</p>
                </div>
            </div>
            <div class="accordion-item">
                <div class="accordion-header" onclick="toggleAccordion(this)">
                    <span>Care & Composition</span><span class="accordion-icon">+</span>
                </div>
                <div class="accordion-body">
                    <p>90% Virgin Wool, 10% Cashmere. Dry clean only. Do not tumble dry. Iron on low heat. Store on a padded hanger.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- COMPLETE THE LOOK -->
<section class="complete-look">
    <p class="section-label">Complete the Look</p>
    <div class="look-grid">
        <?php foreach ($complete_look as $item): ?>
        <div class="look-card">
            <div class="look-img-wrap">
                <img src="<?= htmlspecialchars($item['img']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" loading="lazy"/>
            </div>
            <p class="look-brand"><?= htmlspecialchars($item['brand']) ?></p>
            <p class="look-name"><?= htmlspecialchars($item['name']) ?></p>
            <p class="look-price">$<?= number_format($item['price'], 2) ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- PHILOSOPHY -->
<div class="philosophy">
    <div class="philosophy-text">
        <p class="philosophy-label">The Archival Philosophy</p>
        <h2 class="philosophy-title">Meticulously Crafted for the Modern Wardrobe</h2>
        <p class="philosophy-body">Our wool is sourced from heritage mills in Northern Italy, ensuring every fiber meets our rigorous standards for durability and comfort. Each piece is constructed with an internal floating canvas, a hallmark of traditional bespoke tailoring that allows the blazer to mold to your body over time.</p>
    </div>
    <img class="philosophy-img" src="https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=800&q=80" alt="Archival Philosophy" loading="lazy"/>
</div>

<!-- FOOTER -->
<footer>
    <div class="footer-inner">
        <span class="footer-logo">ZALORA</span>
        <div class="footer-links">
            <a href="#">Help & Support</a>
            <a href="#">Size Guide</a>
            <a href="#">Returns & Refunds</a>
            <a href="#">Contact Us</a>
            <a href="#">Terms & Conditions</a>
        </div>
        <p class="footer-copy">© <?= date('Y') ?> Zalora. All Rights Reserved.</p>
    </div>
</footer>

<script>
    function selectColor(btn) {
        document.querySelectorAll('.color-swatch').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById('color-label').textContent = btn.dataset.name;
    }

    function selectSize(btn) {
        document.querySelectorAll('.size-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
    }

    function toggleWish(btn) {
        const liked = btn.classList.toggle('liked');
        btn.querySelector('span').textContent = liked ? 'Added to Wishlist' : 'Add to Wishlist';
        btn.childNodes[0].textContent = liked ? '♥ ' : '♡ ';
    }

    function addToBag(btn) {
        const orig = btn.textContent;
        btn.textContent = '✓ Added to Bag';
        btn.style.background = '#27ae60';
        setTimeout(() => { btn.textContent = orig; btn.style.background = ''; }, 2000);
        const badge = document.querySelector('.cart-badge');
        badge.textContent = parseInt(badge.textContent) + 1;
    }

    function toggleAccordion(header) {
        const body = header.nextElementSibling;
        const icon = header.querySelector('.accordion-icon');
        const isOpen = body.classList.contains('open');
        body.classList.toggle('open', !isOpen);
        icon.textContent = isOpen ? '+' : '−';
    }
</script>
</body>
</html>