<?php
session_start();
require_once '../config/db.php';

$prod_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($prod_id <= 0) {
    header("Location: products.php");
    exit();
}

// Fetch Product Details
$query = "SELECT p.*, b.Brand_Name, c.Ctgry_Name 
          FROM PRODUCT p 
          JOIN BRAND b ON p.Brand_Id = b.Brand_Id 
          JOIN CATEGORY c ON p.Ctgry_Id = c.Ctgry_Id 
          WHERE p.Prod_Id = ? AND p.Prod_IsActive = 1";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $prod_id);
$stmt->execute();
$product_res = $stmt->get_result();

if ($product_res->num_rows === 0) {
    die("Product not found.");
}

$product_data = $product_res->fetch_assoc();

// Fetch Variants
$variant_query = "SELECT * FROM PRODUCT_VARIANT WHERE Prod_Id = ?";
$v_stmt = $conn->prepare($variant_query);
$v_stmt->bind_param("i", $prod_id);
$v_stmt->execute();
$variants_res = $v_stmt->get_result();
$variants = [];
$sizes = [];
$colors = [];

while ($v = $variants_res->fetch_assoc()) {
    $variants[] = $v;
    if ($v['PVar_Size'] && !in_array($v['PVar_Size'], $sizes)) $sizes[] = $v['PVar_Size'];
    if ($v['PVar_Color'] && !in_array($v['PVar_Color'], $colors)) $colors[] = $v['PVar_Color'];
}

// Fetch Images
$img_query = "SELECT PImg_ImgUrl FROM PRODUCT_IMAGE WHERE Prod_Id = ? ORDER BY PImg_IsPrimary DESC";
$i_stmt = $conn->prepare($img_query);
$i_stmt->bind_param("i", $prod_id);
$i_stmt->execute();
$img_res = $i_stmt->get_result();
$images = [];
while ($img = $img_res->fetch_assoc()) {
    $images[] = $img['PImg_ImgUrl'];
}
if (empty($images)) $images[] = "https://via.placeholder.com/800x1000?text=No+Image";
 
// Check if product is in wishlist
$is_wishlisted = false;
if (isset($_SESSION['user_id'])) {
    $cust_id = $_SESSION['user_id'];
    $wish_check = "SELECT 1 FROM wishlist_item wi 
                   JOIN wishlist w ON wi.Wish_Id = w.Wish_Id 
                   JOIN product_variant pv ON wi.PVar_Id = pv.PVar_Id 
                   WHERE w.Cust_Id = ? AND pv.Prod_Id = ? LIMIT 1";
    $w_stmt = $conn->prepare($wish_check);
    $w_stmt->bind_param("ii", $cust_id, $prod_id);
    $w_stmt->execute();
    if ($w_stmt->get_result()->num_rows > 0) {
        $is_wishlisted = true;
    }
}

$product = [
    "collection"  => strtoupper($product_data['Ctgry_Name']) . " COLLECTION",
    "brand"       => $product_data['Brand_Name'],
    "name"        => $product_data['Prod_Name'],
    "price"       => $product_data['Prod_BasePrice'],
    "description" => $product_data['Prod_Desc'],
    "colors"      => $colors,
    "sizes"       => $sizes,
    "images"      => $images,
];

$complete_look = [
    ["brand" => "ARCHIVE",      "name" => "Wide-Leg Wool Trouser",  "price" => 145.00, "img" => "https://images.unsplash.com/photo-1473966968600-fa801b869a1a?w=400&q=80"],
    ["brand" => "ZALORA LUXURY","name" => "Pointed Leather Boot",   "price" => 210.00, "img" => "https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=400&q=80"],
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
    <link rel="stylesheet" href="../assets/css/global.css"/>
    <link rel="stylesheet" href="../assets/css/product.css"/>
</head>
<body>

<!-- NAV --><nav>
    <?php include 'nav_counts.php'; ?>
    <a href="../index.php" class="nav-logo">ZALORA</a>
    <ul class="nav-links">
        <?php foreach ($nav_links as $link): ?>
            <li><a href="products.php?category=<?= urlencode($link) ?>"><?= htmlspecialchars($link) ?></a></li>
        <?php endforeach; ?>
    </ul>
    <div class="nav-actions">
        <form action="products.php" method="GET" class="nav-search">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
            <input type="text" name="q" placeholder="Search" />
        </form>
        <a href="profile.php" title="Account" style="color:var(--black);display:flex;align-items:center;text-decoration:none;gap:8px;">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <?php if (!empty($nav_user_name)): ?>
                <span style="font-size:11px; font-weight:700; letter-spacing:0.05em;">Hi <?= htmlspecialchars($nav_user_name) ?>,</span>
            <?php endif; ?>
        </a>
        <a href="wishlist.php" title="Wishlist" style="color:var(--black);display:flex;align-items:center;position:relative;">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
            <?php if ($nav_wish_count > 0): ?>
                <span class="cart-badge" style="top:-8px; right:-8px;"><?= $nav_wish_count ?></span>
            <?php endif; ?>
        </a>
        <a href="cart.php" title="Cart" style="color:var(--black);display:flex;align-items:center;position:relative;">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
            <?php if ($nav_cart_count > 0): ?>
                <span class="cart-badge" style="top:-8px; right:-8px;"><?= $nav_cart_count ?></span>
            <?php endif; ?>
        </a>
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

        <!-- Form for Add to Bag -->
        <form action="add_to_cart.php" method="POST">
            <!-- Hidden inputs for product info -->
            <input type="hidden" name="pvar_id" id="selected-pvar-id" value="<?= !empty($variants) ? $variants[0]['PVar_Id'] : '' ?>">
            <input type="hidden" name="quantity" value="1">

            <!-- Color -->
            <?php if (!empty($product['colors'])): ?>
            <p class="option-label">Color: <span class="color-name" id="color-label"><?= htmlspecialchars($product['colors'][0]) ?></span></p>
            <div class="color-swatches">
                <?php foreach ($product['colors'] as $i => $color): ?>
                <button
                    type="button"
                    class="color-swatch <?= $i === 0 ? 'active' : '' ?>"
                    style="background:<?= htmlspecialchars($color) ?>;"
                    title="<?= htmlspecialchars($color) ?>"
                    data-name="<?= htmlspecialchars($color) ?>"
                    onclick="selectColor(this)"
                ></button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Size -->
            <?php if (!empty($product['sizes'])): ?>
            <div class="size-header">
                <p class="option-label" style="margin-bottom:0;">Select Size</p>
                <span class="size-guide">Size Guide</span>
            </div>
            <div class="size-grid">
                <?php foreach ($product['sizes'] as $i => $size): ?>
                <button type="button" class="size-btn <?= $i === 0 ? 'active' : '' ?>" data-size="<?= htmlspecialchars($size) ?>" onclick="selectSize(this)"><?= htmlspecialchars($size) ?></button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- CTAs -->
            <button type="submit" class="btn-add">Add to Bag</button>
            <button type="button" class="btn-wish <?= $is_wishlisted ? 'liked' : '' ?>" onclick="toggleWish(this)">
                <?= $is_wishlisted ? '♥' : '♡' ?> <span><?= $is_wishlisted ? 'Added to Wishlist' : 'Add to Wishlist' ?></span>
            </button>
        </form>

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
    const variants = <?= json_encode($variants) ?>;
    let selectedColor = "<?= !empty($product['colors']) ? $product['colors'][0] : '' ?>";
    let selectedSize = "<?= !empty($product['sizes']) ? $product['sizes'][0] : '' ?>";

    function updateSelectedVariant() {
        const variant = variants.find(v => 
            (v.PVar_Color === selectedColor || !v.PVar_Color) && 
            (v.PVar_Size === selectedSize || !v.PVar_Size)
        );
        if (variant) {
            document.getElementById('selected-pvar-id').value = variant.PVar_Id;
        }
    }

    function selectColor(btn) {
        document.querySelectorAll('.color-swatch').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        selectedColor = btn.dataset.name;
        document.getElementById('color-label').textContent = selectedColor;
        updateSelectedVariant();
    }

    function selectSize(btn) {
        document.querySelectorAll('.size-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        selectedSize = btn.dataset.size;
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
                btn.querySelector('span').textContent = liked ? 'Added to Wishlist' : 'Add to Wishlist';
                btn.childNodes[0].textContent = liked ? '♥ ' : '♡ ';
            } else if (result.message === 'Unauthorized') {
                window.location.href = '../auth/login.php';
            }
        } catch (error) {
            console.error('Error toggling wishlist:', error);
        }
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