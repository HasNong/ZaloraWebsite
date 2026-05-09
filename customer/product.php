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
    $img_url = $img['PImg_ImgUrl'];
    if (!empty($img_url) && strpos($img_url, 'http') === false) {
        $img_url = '../' . $img_url;
    }
    $images[] = $img_url;
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

// 4. Fetch 'Complete the Look' (2 random products from SAME category)
$look_query = "SELECT p.Prod_Id, p.Prod_Name, p.Prod_BasePrice, b.Brand_Name,
               (SELECT PImg_ImgUrl FROM PRODUCT_IMAGE WHERE Prod_Id = p.Prod_Id AND PImg_IsPrimary = 1 LIMIT 1) as img
               FROM PRODUCT p
               JOIN BRAND b ON p.Brand_Id = b.Brand_Id
               WHERE p.Ctgry_Id = ? AND p.Prod_Id != ? AND p.Prod_IsActive = 1
               ORDER BY RAND() LIMIT 2";
$stmt_look = $conn->prepare($look_query);
$stmt_look->bind_param("ii", $product_data['Ctgry_Id'], $prod_id);
$stmt_look->execute();
$look_res = $stmt_look->get_result();

$complete_look = [];
while ($row = $look_res->fetch_assoc()) {
    $img = $row['img'] ?? 'https://via.placeholder.com/400';
    if ($img && strpos($img, 'http') === false) $img = '../' . $img;

    $complete_look[] = [
        "id"    => $row['Prod_Id'],
        "brand" => $row['Brand_Name'],
        "name"  => $row['Prod_Name'],
        "price" => $row['Prod_BasePrice'],
        "img"   => $img
    ];
}

// 5. Fetch Reviews & Average Rating
$reviews_query = "SELECT r.*, c.Cust_FirstName, c.Cust_LastName 
                  FROM review r 
                  JOIN customer c ON r.Cust_Id = c.Cust_Id 
                  WHERE r.Prod_Id = ? AND r.Rview_IsApproved = 1 
                  ORDER BY r.Rview_CreatedAt DESC";
$stmt_rev = $conn->prepare($reviews_query);
$stmt_rev->bind_param("i", $prod_id);
$stmt_rev->execute();
$reviews_res = $stmt_rev->get_result();

$all_reviews = [];
$total_stars = 0;
while ($rev = $reviews_res->fetch_assoc()) {
    $all_reviews[] = $rev;
    $total_stars += $rev['Rview_Rating'];
}
$avg_rating = count($all_reviews) > 0 ? round($total_stars / count($all_reviews), 1) : 0;

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
    <div class="image-panel <?= count($product['images']) === 1 ? 'single-image' : '' ?>">
        <?php if (count($product['images']) === 1): ?>
            <div class="img-main" style="grid-column: 1 / -1; height: 100vh; position: sticky; top: 56px; overflow: hidden;">
                <img src="<?= htmlspecialchars($product['images'][0]) ?>" alt="<?= htmlspecialchars($product['name']) ?>" style="width: 100%; height: 100%; object-fit: cover; object-position: center;"/>
            </div>
        <?php else: ?>
            <div class="img-main"><img src="<?= htmlspecialchars($product['images'][0]) ?>" alt="<?= htmlspecialchars($product['name']) ?>"/></div>
            <div class="img-detail"><img src="<?= htmlspecialchars($product['images'][1] ?? $product['images'][0]) ?>" alt="Detail"/></div>
            <div class="img-full"><img src="<?= htmlspecialchars($product['images'][2] ?? $product['images'][0]) ?>" alt="Lifestyle"/></div>
        <?php endif; ?>
    </div>

    <!-- INFO -->
    <div class="info-panel">
        <p class="product-collection"><?= htmlspecialchars($product['collection']) ?></p>
        <h1 class="product-name"><?= htmlspecialchars($product['name']) ?></h1>
        <div class="product-rating" style="display: flex; align-items: center; gap: 8px; margin: 10px 0;">
            <div style="display: flex; color: #000; font-size: 14px;">
                <?php for($i=1; $i<=5; $i++): ?>
                    <?= $i <= round($avg_rating) ? '★' : '☆' ?>
                <?php endfor; ?>
            </div>
            <span style="font-size: 11px; font-weight: 700; color: #000; border-bottom: 1px solid #000;"><?= count($all_reviews) ?> REVIEWS</span>
            <span style="font-size: 11px; font-weight: 500; color: #999; margin-left: 5px;">Avg. <?= $avg_rating ?></span>
        </div>
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
                <span class="size-guide" onclick="openSizeGuide()">Size Guide</span>
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
        <a href="product.php?id=<?= $item['id'] ?>" class="look-card" style="text-decoration: none; color: inherit;">
            <div class="look-img-wrap">
                <img src="<?= htmlspecialchars($item['img']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" loading="lazy"/>
            </div>
            <p class="look-brand"><?= htmlspecialchars($item['brand']) ?></p>
            <p class="look-name"><?= htmlspecialchars($item['name']) ?></p>
            <p class="look-price">$<?= number_format($item['price'], 2) ?></p>
        </a>
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

<!-- CUSTOMER REVIEWS (FR-19) -->
<section class="customer-reviews" style="max-width: 1200px; margin: 80px auto; padding: 0 40px; border-top: 1px solid #eee; padding-top: 60px;">
    <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 40px;">
        <h2 style="font-size: 20px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em;">Customer Reviews</h2>
        <span style="font-size: 11px; font-weight: 800; color: #999; text-transform: uppercase; letter-spacing: 0.1em;"><?= count($all_reviews) ?> SHARED FEEDBACK</span>
    </div>

    <div class="reviews-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 40px;">
        <?php if (count($all_reviews) > 0): ?>
            <?php foreach ($all_reviews as $rev): ?>
            <div class="review-card" style="border-bottom: 1px solid #f9f9f9; padding-bottom: 30px;">
                <div style="display: flex; color: #000; font-size: 12px; margin-bottom: 10px; gap: 2px;">
                    <?php for($i=1; $i<=5; $i++): ?>
                        <?= $i <= $rev['Rview_Rating'] ? '★' : '☆' ?>
                    <?php endfor; ?>
                </div>
                <h4 style="font-size: 13px; font-weight: 700; margin-bottom: 8px; text-transform: uppercase;"><?= htmlspecialchars($rev['Cust_FirstName'] . ' ' . substr($rev['Cust_LastName'], 0, 1)) ?>.</h4>
                <p style="font-size: 13px; color: #444; line-height: 1.6; margin-bottom: 15px; font-weight: 400;"><?= htmlspecialchars($rev['Rview_Txt']) ?></p>
                <span style="font-size: 10px; color: #999; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;"><?= date('M d, Y', strtotime($rev['Rview_CreatedAt'])) ?></span>
                
                <?php if ($rev['Rview_PicUrl']): 
                    $rev_img = $rev['Rview_PicUrl'];
                    if (strpos($rev_img, 'http') === false && strpos($rev_img, '../') !== 0) {
                        $rev_img = '../' . $rev_img;
                    }
                ?>
                    <div style="margin-top: 15px;">
                        <img src="<?= htmlspecialchars($rev_img) ?>" style="width: 100px; height: 100px; object-fit: cover; border: 1px solid #eee; cursor: pointer;" alt="Customer Photo" onclick="window.open(this.src)">
                    </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 60px; background: #fafafa; color: #999; font-size: 12px; letter-spacing: 0.1em; text-transform: uppercase;">
                No reviews yet. Be the first to share your experience!
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- SIZE GUIDE MODAL -->
<div id="sizeGuideModal" class="modal-overlay">
    <div class="modal-content" style="max-width: 600px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h2 style="font-size: 18px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em;">Size Guide</h2>
            <span style="cursor: pointer; font-size: 24px;" onclick="closeSizeGuide()">&times;</span>
        </div>
        <p style="font-size: 13px; color: #666; margin-bottom: 20px;">All measurements are in centimeters (cm). Use this guide to find your perfect fit.</p>
        <table style="width: 100%; border-collapse: collapse; font-size: 12px; text-align: left;">
            <thead>
                <tr style="border-bottom: 1px solid #000; text-transform: uppercase; font-weight: 700;">
                    <th style="padding: 10px 0;">Size</th>
                    <th>Chest</th>
                    <th>Waist</th>
                    <th>Hips</th>
                </tr>
            </thead>
            <tbody>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 15px 0; font-weight: 700;">XS</td>
                    <td>82-86</td>
                    <td>64-68</td>
                    <td>90-94</td>
                </tr>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 15px 0; font-weight: 700;">S</td>
                    <td>86-90</td>
                    <td>68-72</td>
                    <td>94-98</td>
                </tr>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 15px 0; font-weight: 700;">M</td>
                    <td>90-94</td>
                    <td>72-76</td>
                    <td>98-102</td>
                </tr>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 15px 0; font-weight: 700;">L</td>
                    <td>94-100</td>
                    <td>76-82</td>
                    <td>102-108</td>
                </tr>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 15px 0; font-weight: 700;">XL</td>
                    <td>100-106</td>
                    <td>82-88</td>
                    <td>108-114</td>
                </tr>
            </tbody>
        </table>
        <div style="margin-top: 30px; background: #fafafa; padding: 20px; font-size: 11px; line-height: 1.6; color: #666;">
            <strong>HOW TO MEASURE:</strong><br>
            <strong>CHEST:</strong> Measure around the fullest part of your chest, keeping the tape horizontal.<br>
            <strong>WAIST:</strong> Measure around the narrowest part (typically where your body bends side to side), keeping the tape horizontal.<br>
            <strong>HIPS:</strong> Measure around the fullest part of your hips, keeping the tape horizontal.
        </div>
    </div>
</div>

<style>
.modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: none; justify-content: center; align-items: center; z-index: 1000; }
.modal-content { background: #fff; padding: 40px; position: relative; max-height: 90vh; overflow-y: auto; }
</style>

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

    function openSizeGuide() {
        document.getElementById('sizeGuideModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeSizeGuide() {
        document.getElementById('sizeGuideModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    window.onclick = function(event) {
        if (event.target == document.getElementById('sizeGuideModal')) {
            closeSizeGuide();
        }
    }
</script>
</body>
</html>