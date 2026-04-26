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

        body {
            font-family: var(--font-body);
            background: var(--white);
            color: var(--black);
            font-size: 13px;
            letter-spacing: 0.04em;
        }

        /* ── NAV ── */
        nav {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(250,250,250,0.97);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid var(--light-grey);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2.5rem;
            height: 56px;
        }

        .nav-logo {
            font-weight: 700;
            font-size: 1.15rem;
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
            padding-bottom: 3px;
            border-bottom: 2px solid transparent;
            transition: border-color 0.2s;
        }

        .nav-links a.active,
        .nav-links a:hover { border-color: var(--black); }

        .nav-actions {
            display: flex;
            gap: 1.2rem;
            align-items: center;
        }

        .nav-actions a {
            color: var(--black);
            display: flex;
            align-items: center;
            transition: opacity 0.2s;
        }

        .nav-actions a:hover { opacity: 0.5; }

        /* ── PAGE HEADER ── */
        .page-header {
            padding: 2.5rem 2.5rem 1.5rem;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            border-bottom: 1px solid var(--light-grey);
        }

        .page-header-left h1 {
            font-family: var(--font-body);
            font-size: clamp(1.4rem, 3vw, 1.8rem);
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            margin-bottom: 0.3rem;
        }

        .page-header-left p {
            font-size: 11px;
            color: var(--grey);
            letter-spacing: 0.06em;
        }

        .sort-wrap {
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .sort-wrap label {
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: var(--grey);
        }

        .sort-wrap select {
            border: 1px solid var(--light-grey);
            background: var(--white);
            font-family: var(--font-body);
            font-size: 11px;
            letter-spacing: 0.06em;
            padding: 8px 32px 8px 14px;
            color: var(--black);
            appearance: none;
            cursor: pointer;
            background-image: url("data:image/svg+xml,%3Csvg width='10' height='6' viewBox='0 0 10 6' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%23888' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            outline: none;
            transition: border-color 0.2s;
        }

        .sort-wrap select:focus { border-color: var(--black); }

        /* ── MAIN LAYOUT ── */
        .shop-layout {
            display: grid;
            grid-template-columns: 220px 1fr;
            gap: 0;
            min-height: 80vh;
        }

        /* ── SIDEBAR ── */
        .sidebar {
            padding: 2rem 2rem 2rem 2.5rem;
            border-right: 1px solid var(--light-grey);
        }

        .filter-section { margin-bottom: 2.2rem; }

        .filter-title {
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 0.24em;
            text-transform: uppercase;
            color: var(--grey);
            margin-bottom: 1.1rem;
        }

        /* Category */
        .cat-list { list-style: none; }

        .cat-list li {
            margin-bottom: 0.7rem;
        }

        .cat-list li a {
            text-decoration: none;
            font-size: 12px;
            color: var(--grey);
            display: flex;
            justify-content: space-between;
            transition: color 0.2s;
        }

        .cat-list li a:hover,
        .cat-list li a.active { color: var(--black); font-weight: 600; }

        .cat-count {
            font-size: 10px;
            color: #bbb;
        }

        /* Size */
        .size-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .size-btn {
            border: 1px solid var(--light-grey);
            background: none;
            font-family: var(--font-body);
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.1em;
            padding: 7px 12px;
            cursor: pointer;
            transition: background 0.2s, border-color 0.2s, color 0.2s;
            color: var(--black);
        }

        .size-btn:hover,
        .size-btn.active {
            background: var(--black);
            color: var(--white);
            border-color: var(--black);
        }

        /* Color */
        .color-swatches {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .color-swatch {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            border: 2px solid transparent;
            cursor: pointer;
            transition: border-color 0.2s, transform 0.2s;
            outline: none;
        }

        .color-swatch:hover,
        .color-swatch.active {
            border-color: var(--black);
            transform: scale(1.15);
        }

        /* Price Range */
        .price-labels {
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            color: var(--grey);
            margin-top: 0.6rem;
        }

        input[type="range"] {
            width: 100%;
            accent-color: var(--black);
            cursor: pointer;
        }

        /* ── PRODUCT GRID ── */
        .products-area {
            padding: 2rem 2.5rem;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .product-card { cursor: pointer; }

        .product-img-wrap {
            position: relative;
            overflow: hidden;
            margin-bottom: 0.9rem;
        }

        .product-img-wrap img {
            width: 100%;
            aspect-ratio: 3/4;
            object-fit: cover;
            display: block;
            transition: transform 0.5s ease;
        }

        .product-card:hover .product-img-wrap img { transform: scale(1.04); }

        .product-badge {
            position: absolute;
            top: 12px;
            left: 12px;
            background: var(--black);
            color: var(--white);
            font-size: 8px;
            font-weight: 700;
            letter-spacing: 0.18em;
            padding: 4px 10px;
        }

        .badge-soldout {
            background: var(--grey);
        }

        .product-wish {
            position: absolute;
            top: 10px;
            right: 12px;
            background: none;
            border: none;
            font-size: 1.1rem;
            color: rgba(0,0,0,0.35);
            cursor: pointer;
            transition: color 0.2s, transform 0.2s;
            line-height: 1;
        }

        .product-wish:hover { color: #e74c3c; transform: scale(1.2); }
        .product-wish.liked { color: #e74c3c; }

        .product-brand {
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 0.2em;
            color: var(--grey);
            text-transform: uppercase;
            margin-bottom: 3px;
        }

        .product-name {
            font-size: 12px;
            font-weight: 400;
            margin-bottom: 5px;
            line-height: 1.4;
        }

        .product-price {
            font-size: 13px;
            font-weight: 600;
        }

        /* ── PAGINATION ── */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem 0 2rem;
        }

        .page-btn {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid transparent;
            background: none;
            font-family: var(--font-body);
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.08em;
            cursor: pointer;
            color: var(--grey);
            transition: all 0.2s;
        }

        .page-btn:hover { border-color: var(--light-grey); color: var(--black); }
        .page-btn.active { border-color: var(--black); color: var(--black); }

        .page-arrow {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--light-grey);
            background: none;
            cursor: pointer;
            color: var(--black);
            transition: background 0.2s;
        }

        .page-arrow:hover { background: var(--light-grey); }

        /* ── FOOTER ── */
        footer {
            background: var(--black);
            color: rgba(255,255,255,0.6);
            padding: 3.5rem 2.5rem 2rem;
        }

        .footer-top {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 3rem;
            padding-bottom: 2.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 2rem;
        }

        .footer-brand {
            font-weight: 700;
            font-size: 1.1rem;
            letter-spacing: 0.18em;
            color: var(--white);
            margin-bottom: 1rem;
        }

        .footer-desc {
            font-size: 10px;
            line-height: 1.9;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            max-width: 240px;
        }

        .footer-col h4 {
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            color: var(--white);
            margin-bottom: 1.2rem;
        }

        .footer-col ul { list-style: none; }

        .footer-col ul li {
            margin-bottom: 0.75rem;
        }

        .footer-col ul li a {
            text-decoration: none;
            color: rgba(255,255,255,0.5);
            font-size: 10px;
            letter-spacing: 0.08em;
            transition: color 0.2s;
        }

        .footer-col ul li a:hover { color: var(--accent); }

        .footer-social {
            display: flex;
            gap: 1rem;
            margin-top: 0.5rem;
        }

        .footer-social a {
            color: rgba(255,255,255,0.5);
            font-size: 1rem;
            transition: color 0.2s;
            text-decoration: none;
        }

        .footer-social a:hover { color: var(--accent); }

        .footer-bottom {
            font-size: 10px;
            color: rgba(255,255,255,0.25);
            text-align: center;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        /* ── RESPONSIVE ── */
        @media (max-width: 900px) {
            .shop-layout { grid-template-columns: 1fr; }
            .sidebar { border-right: none; border-bottom: 1px solid var(--light-grey); }
            .product-grid { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 600px) {
            .nav-links { display: none; }
            .product-grid { grid-template-columns: 1fr; }
            .footer-top { grid-template-columns: 1fr 1fr; }
        }
    </style>
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