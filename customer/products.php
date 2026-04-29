<?php
session_start();

// Static product data
$products = [
    [
        "brand" => "AESTHETIC STUDIO",
        "name" => "Sculptural Wool Blend Dress",
        "price" => 285.00,
        "badge" => "NEW",
        "sold_out" => false,
        "img" => "https://images.unsplash.com/photo-1539008835657-9e8e9680c956?w=600&q=80",
    ],
    [
        "brand" => "MODERN ARCHIVE",
        "name" => "Signature Oversized Trench",
        "price" => 420.00,
        "badge" => "",
        "sold_out" => false,
        "img" => "https://images.unsplash.com/photo-1591047139829-d91aecb6caea?w=600&q=80",
    ],
    [
        "brand" => "LUMINESCE",
        "name" => "Cotton Ribbed Knit Set",
        "price" => 195.00,
        "badge" => "",
        "sold_out" => false,
        "img" => "https://images.unsplash.com/photo-1586790170083-2f9ceadc732d?w=600&q=80",
    ],
    [
        "brand" => "VEIL ACCESSORIES",
        "name" => "Structured Bag in Obsidian",
        "price" => 550.00,
        "badge" => "",
        "sold_out" => false,
        "img" => "https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=600&q=80",
    ],
    [
        "brand" => "SILK & CO",
        "name" => "Wide-Leg Silk Trousers",
        "price" => 310.00,
        "badge" => "",
        "sold_out" => false,
        "img" => "https://images.unsplash.com/photo-1509631179647-0177331693ae?w=600&q=80",
    ],
    [
        "brand" => "RAW DENIM",
        "name" => "Utility Trucker Jacket",
        "price" => 245.00,
        "badge" => "",
        "sold_out" => true,
        "img" => "https://images.unsplash.com/photo-1495105787522-5334e3ffa0ef?w=600&q=80",
    ],
];

$categories = [
    ["label" => "All Clothing", "count" => 1240, "active" => true],
    ["label" => "Dresses",      "count" => 342,  "active" => false],
    ["label" => "Tops",         "count" => 215,  "active" => false],
    ["label" => "Outerwear",    "count" => 128,  "active" => false],
];

$sizes  = ["XS", "S", "M", "L", "XL"];
$colors = [
    ["hex" => "#111111", "name" => "Black"],
    ["hex" => "#f5f5f0", "name" => "White"],
    ["hex" => "#c8a96e", "name" => "Gold"],
    ["hex" => "#5a6e3a", "name" => "Olive"],
    ["hex" => "#6b3fa0", "name" => "Purple"],
    ["hex" => "#e8dcc8", "name" => "Cream"],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>ZALORA — New Arrivals</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;1,300&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="assets/css/global.css"/>
    <link rel="stylesheet" href="../assets/css/products.css"/>
</head>
<body>

<!-- ── NAV ── -->
<nav>
    <a href="../index.php" class="nav-logo">ZALORA</a>
    <ul class="nav-links">
        <li><a href="#" class="active">WOMEN</a></li>
        <li><a href="#">MEN</a></li>
        <li><a href="#">KIDS</a></li>
        <li><a href="#">LUXURY</a></li>
        <li><a href="#">BEAUTY</a></li>
    </ul>
    <div class="nav-actions">
        <a href="#" title="Search">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
        </a>
        <a href="../auth/login.php" title="Account">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        </a>
        <a href="#" title="Wishlist">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
        </a>
        <a href="#" title="Cart">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
        </a>
    </div>
</nav>

<!-- ── PAGE HEADER ── -->
<div class="page-header">
    <div class="page-header-left">
        <h1>New Arrivals</h1>
        <p>Curated selection of the latest international fashion trends.</p>
    </div>
    <div class="sort-wrap">
        <label for="sort">Sort By:</label>
        <select id="sort" name="sort">
            <option>Relevance</option>
            <option>Price: Low to High</option>
            <option>Price: High to Low</option>
            <option>Newest First</option>
        </select>
    </div>
</div>

<!-- ── SHOP LAYOUT ── -->
<div class="shop-layout">

    <!-- SIDEBAR -->
    <aside class="sidebar">

        <!-- Category -->
        <div class="filter-section">
            <p class="filter-title">Category</p>
            <ul class="cat-list">
                <?php foreach ($categories as $cat): ?>
                <li>
                    <a href="#" class="<?= $cat['active'] ? 'active' : '' ?>">
                        <span><?= htmlspecialchars($cat['label']) ?></span>
                        <span class="cat-count">(<?= number_format($cat['count']) ?>)</span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Size -->
        <div class="filter-section">
            <p class="filter-title">Size</p>
            <div class="size-grid">
                <?php foreach ($sizes as $i => $size): ?>
                <button class="size-btn <?= $i === 1 ? 'active' : '' ?>" onclick="toggleSize(this)">
                    <?= htmlspecialchars($size) ?>
                </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Color -->
        <div class="filter-section">
            <p class="filter-title">Color</p>
            <div class="color-swatches">
                <?php foreach ($colors as $color): ?>
                <button
                    class="color-swatch"
                    style="background:<?= htmlspecialchars($color['hex']) ?>; <?= $color['hex'] === '#f5f5f0' ? 'border:2px solid #ddd;' : '' ?>"
                    title="<?= htmlspecialchars($color['name']) ?>"
                    onclick="toggleColor(this)"
                ></button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Price Range -->
        <div class="filter-section">
            <p class="filter-title">Price Range</p>
            <input type="range" min="0" max="500" value="150" id="price-range" oninput="updatePrice(this.value)"/>
            <div class="price-labels">
                <span>$0</span>
                <span id="price-max">$500+</span>
            </div>
        </div>

    </aside>

    <!-- PRODUCTS -->
    <main class="products-area">
        <div class="product-grid">
            <?php foreach ($products as $p): ?>
            <div class="product-card">
                <div class="product-img-wrap">
                    <img src="<?= htmlspecialchars($p['img']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" loading="lazy"/>

                    <?php if ($p['badge']): ?>
                        <span class="product-badge"><?= htmlspecialchars($p['badge']) ?></span>
                    <?php endif; ?>

                    <?php if ($p['sold_out']): ?>
                        <span class="product-badge badge-soldout">SOLD OUT</span>
                    <?php endif; ?>

                    <button class="product-wish" onclick="toggleWish(this)" title="Wishlist">♡</button>
                </div>
                <p class="product-brand"><?= htmlspecialchars($p['brand']) ?></p>
                <p class="product-name"><?= htmlspecialchars($p['name']) ?></p>
                <p class="product-price">$<?= number_format($p['price'], 2) ?></p>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- PAGINATION -->
        <div class="pagination">
            <button class="page-arrow" title="Previous">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"/></svg>
            </button>
            <button class="page-btn active">01</button>
            <button class="page-btn">02</button>
            <button class="page-btn">03</button>
            <span style="font-size:11px;color:var(--grey);padding:0 4px;">...</span>
            <button class="page-btn">12</button>
            <button class="page-arrow" title="Next">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
            </button>
        </div>
    </main>
</div>

<!-- ── FOOTER ── -->
<footer>
    <div class="footer-top">
        <div>
            <div class="footer-brand">ZALORA</div>
            <p class="footer-desc">Elevating the modern wardrobe through intentional design and curated excellence. Experience the future of high-fashion commerce.</p>
        </div>
        <div class="footer-col">
            <h4>Customer Care</h4>
            <ul>
                <li><a href="#">Help & Support</a></li>
                <li><a href="#">Size Guide</a></li>
                <li><a href="#">Returns & Refunds</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Legal</h4>
            <ul>
                <li><a href="#">Terms & Conditions</a></li>
                <li><a href="#">Privacy Policy</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Social</h4>
            <div class="footer-social">
                <a href="#" title="Pinterest">📌</a>
                <a href="#" title="Instagram">📷</a>
                <a href="#" title="TikTok">🎵</a>
            </div>
        </div>
    </div>
    <div class="footer-bottom">© <?= date('Y') ?> Zalora. All Rights Reserved.</div>
</footer>

<script>
    function toggleWish(btn) {
        const liked = btn.classList.toggle('liked');
        btn.textContent = liked ? '♥' : '♡';
    }

    function toggleSize(btn) {
        document.querySelectorAll('.size-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
    }

    function toggleColor(btn) {
        document.querySelectorAll('.color-swatch').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
    }

    function updatePrice(val) {
        document.getElementById('price-max').textContent = val >= 500 ? '$500+' : '$' + val;
    }

    // Pagination
    document.querySelectorAll('.page-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.page-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
        });
    });
</script>

</body>
</html>