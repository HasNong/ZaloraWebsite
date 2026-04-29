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
    <link rel="stylesheet" href="assets/css/global.css"/>
    <link rel="stylesheet" href="../assets/css/product.css"/>
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