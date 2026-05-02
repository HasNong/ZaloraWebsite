<?php
session_start();
require_once '../config/db.php';

// Filtering Parameters
$category_name = isset($_GET['category']) ? $_GET['category'] : '';
$max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 1000;
$selected_size = isset($_GET['size']) ? $_GET['size'] : '';
$selected_color = isset($_GET['color']) ? $_GET['color'] : '';
$search_q = isset($_GET['q']) ? trim($_GET['q']) : '';

$query = "SELECT DISTINCT p.Prod_Id, p.Prod_Name, p.Prod_BasePrice, b.Brand_Name, 
          (SELECT PImg_ImgUrl FROM PRODUCT_IMAGE WHERE Prod_Id = p.Prod_Id AND PImg_IsPrimary = 1 LIMIT 1) as Primary_Image
          FROM PRODUCT p
          JOIN BRAND b ON p.Brand_Id = b.Brand_Id
          LEFT JOIN CATEGORY c ON p.Ctgry_Id = c.Ctgry_Id
          LEFT JOIN PRODUCT_VARIANT pv ON p.Prod_Id = pv.Prod_Id
          WHERE p.Prod_IsActive = 1 AND p.Prod_BasePrice <= ?";

$params = [$max_price];
$types = "d";

if (!empty($category_name)) {
    $query .= " AND c.Ctgry_Name = ?";
    $params[] = $category_name;
    $types .= "s";
}
if (!empty($selected_size)) {
    $query .= " AND pv.PVar_Size = ?";
    $params[] = $selected_size;
    $types .= "s";
}
if (!empty($selected_color)) {
    $query .= " AND pv.PVar_Color = ?";
    $params[] = $selected_color;
    $types .= "s";
}
if (!empty($search_q)) {
    $query .= " AND (p.Prod_Name LIKE ? OR p.Prod_Desc LIKE ?)";
    $like_q = "%$search_q%";
    $params[] = $like_q;
    $params[] = $like_q;
    $types .= "ss";
}

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$products = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $products[] = [
            "id"    => $row['Prod_Id'],
            "brand" => $row['Brand_Name'],
            "name"  => $row['Prod_Name'],
            "price" => $row['Prod_BasePrice'],
            "img"   => $row['Primary_Image'] ?? "https://via.placeholder.com/600x800?text=No+Image",
            "badge" => "",
            "sold_out" => false
        ];
    }
}

// Fetch Filter Options from Database
$db_sizes = [];
$s_res = $conn->query("SELECT DISTINCT PVar_Size FROM PRODUCT_VARIANT ORDER BY PVar_Size");
while($srow = $s_res->fetch_assoc()) $db_sizes[] = $srow['PVar_Size'];

$db_colors = [];
$c_res = $conn->query("SELECT DISTINCT PVar_Color, ANY_VALUE(PVar_Sku) FROM PRODUCT_VARIANT GROUP BY PVar_Color ORDER BY PVar_Color");
while($crow = $c_res->fetch_assoc()) {
    // Basic color mapping for dummy UI, usually you'd have a color table
    $hex = "#333"; 
    if(stripos($crow['PVar_Color'], 'white') !== false) $hex = "#fff";
    if(stripos($crow['PVar_Color'], 'black') !== false) $hex = "#000";
    if(stripos($crow['PVar_Color'], 'red') !== false) $hex = "#e74c3c";
    
    $db_colors[] = ["name" => $crow['PVar_Color'], "hex" => $hex];
}

// Fetch Total Product Count (Global)
$total_res = $conn->query("SELECT COUNT(*) as total FROM PRODUCT WHERE Prod_IsActive = 1");
$total_count = $total_res->fetch_assoc()['total'] ?? 0;

// Fetch Categories for Sidebar with accurate counts
$cat_query = "SELECT c.Ctgry_Name, COUNT(p.Prod_Id) as prod_count 
              FROM CATEGORY c 
              LEFT JOIN PRODUCT p ON c.Ctgry_Id = p.Ctgry_Id AND p.Prod_IsActive = 1
              WHERE c.Ctgry_IsActive = 1 
              GROUP BY c.Ctgry_Id, c.Ctgry_Name
              ORDER BY c.Ctgry_Name ASC";
$cat_res = $conn->query($cat_query);

$sidebar_categories = [];
$sidebar_categories[] = ["label" => "All Products", "count" => $total_count, "active" => empty($category_name)];

if ($cat_res) {
    while ($crow = $cat_res->fetch_assoc()) {
        $sidebar_categories[] = [
            "label" => $crow['Ctgry_Name'],
            "count" => $crow['prod_count'],
            "active" => ($category_name === $crow['Ctgry_Name'])
        ];
    }
}



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
    <link rel="stylesheet" href="../assets/css/global.css?v=1.1"/>
    <link rel="stylesheet" href="../assets/css/products.css"/>
</head>
<body>

<!-- ── NAV ── -->
<nav>
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

<!-- ── PAGE HEADER ── -->
<div class="page-header">
    <div class="shop-header">
        <?php if (!empty($search_q)): ?>
            <h1 class="shop-title">Results for "<?= htmlspecialchars($search_q) ?>"</h1>
        <?php else: ?>
            <h1 class="shop-title"><?= !empty($category_name) ? 'Shop ' . htmlspecialchars($category_name) : 'All Products' ?></h1>
        <?php endif; ?>
        <p class="shop-subtitle">Showing <?= count($products) ?> items</p>
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
        <form action="products.php" method="GET" id="filter-form">
            <!-- Hidden Category to preserve state -->
            <input type="hidden" name="category" value="<?= htmlspecialchars($category_name) ?>"/>

            <!-- Category -->
            <div class="filter-section">
                <p class="filter-title">Category</p>
                <ul class="cat-list">
                    <?php foreach ($sidebar_categories as $cat): ?>
                    <li>
                        <a href="products.php<?= ($cat['label'] === 'All Products') ? '' : '?category=' . urlencode($cat['label']) ?>" 
                           class="<?= $cat['active'] ? 'active' : '' ?>">
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
                    <?php foreach ($db_sizes as $size): ?>
                    <button type="button" 
                            class="size-btn <?= $selected_size === $size ? 'active' : '' ?>" 
                            onclick="setFilter('size', '<?= htmlspecialchars($size) ?>')">
                        <?= htmlspecialchars($size) ?>
                    </button>
                    <?php endforeach; ?>
                    <input type="hidden" name="size" id="size-input" value="<?= htmlspecialchars($selected_size) ?>"/>
                </div>
            </div>

            <!-- Color -->
            <div class="filter-section">
                <p class="filter-title">Color</p>
                <div class="color-swatches">
                    <?php foreach ($db_colors as $color): ?>
                    <button
                        type="button"
                        class="color-swatch <?= $selected_color === $color['name'] ? 'active' : '' ?>"
                        style="background:<?= htmlspecialchars($color['hex']) ?>; border: <?= $selected_color === $color['name'] ? '2px solid var(--black)' : '1px solid #ddd' ?>;"
                        title="<?= htmlspecialchars($color['name']) ?>"
                        onclick="setFilter('color', '<?= htmlspecialchars($color['name']) ?>')"
                    ></button>
                    <?php endforeach; ?>
                    <input type="hidden" name="color" id="color-input" value="<?= htmlspecialchars($selected_color) ?>"/>
                </div>
            </div>

            <!-- Price Range -->
            <div class="filter-section">
                <p class="filter-title">Price Range</p>
                <input type="range" name="max_price" min="0" max="1000" step="10" 
                       value="<?= $max_price ?>" id="price-range" 
                       oninput="document.getElementById('price-val').innerText = this.value"/>
                <div class="price-labels">
                    <span>$0</span>
                    <span id="price-max">$<span id="price-val"><?= number_format($max_price) ?></span></span>
                </div>
            </div>

            <button type="submit" class="filter-apply-btn" style="margin-top:15px; width:100%; padding:12px; background:var(--black); color:white; border:none; cursor:pointer; font-size:11px; font-weight:700; letter-spacing:1.5px; transition: all 0.3s ease;">APPLY FILTERS</button>
            
            <?php if(!empty($category_name) || !empty($selected_size) || !empty($selected_color) || $max_price < 1000): ?>
                <a href="products.php" style="display:block; text-align:center; margin-top:10px; font-size:10px; color:#999; text-decoration:none; text-transform:uppercase; letter-spacing:1px;">Clear All Filters</a>
            <?php endif; ?>
        </form>
    </aside>

    <script>
    function setFilter(type, value) {
        document.getElementById(type + '-input').value = value;
        // Optional: auto-submit
        // document.getElementById('filter-form').submit();
        
        // Highlight active button visually immediately
        if(type === 'size') {
            document.querySelectorAll('.size-btn').forEach(b => b.classList.remove('active'));
            event.target.classList.add('active');
        }
    }
    </script>

    <!-- PRODUCTS -->
    <main class="products-area">
        <div class="product-grid">
            <?php foreach ($products as $p): ?>
            <div class="product-card">
                <a href="product.php?id=<?= $p['id'] ?>" class="product-link">
                    <div class="product-img-wrap">
                        <img src="<?= htmlspecialchars($p['img']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" loading="lazy"/>

                        <?php if ($p['badge']): ?>
                            <span class="product-badge"><?= htmlspecialchars($p['badge']) ?></span>
                        <?php endif; ?>

                        <?php if ($p['sold_out']): ?>
                            <span class="product-badge badge-soldout">SOLD OUT</span>
                        <?php endif; ?>
                    </div>
                </a>
                <button class="product-wish" onclick="toggleWish(this)" title="Wishlist">♡</button>
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
    async function toggleWish(btn) {
        const pvarId = 1; // Placeholder for static list

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
                btn.textContent = liked ? '♥' : '♡';
            } else if (result.message === 'Unauthorized') {
                window.location.href = '../auth/login.php';
            }
        } catch (error) {
            console.error('Error toggling wishlist:', error);
        }
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