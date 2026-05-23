<?php
session_start();
require_once '../config/db.php';
include 'nav_counts.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$cust_id = $_SESSION['user_id'];
$wish_items = [];

// Fetch Wishlist Items from DB
$wishRef = $database->getReference('wishlist')->orderByChild('Cust_Id')->equalTo($cust_id)->getSnapshot()->getValue();

if ($wishRef) {
    $wish_id = key($wishRef);
    
    // Fetch Items
    $wishItemsData = $database->getReference('wishlist_item')->orderByChild('Wish_Id')->equalTo($wish_id)->getSnapshot()->getValue();
    
    if ($wishItemsData) {
        $allVariants = $database->getReference('product_variant')->getSnapshot()->getValue() ?: [];
        $allProducts = $database->getReference('product')->getSnapshot()->getValue() ?: [];
        $allBrands = $database->getReference('brand')->getSnapshot()->getValue() ?: [];
        $allImages = $database->getReference('product_image')->getSnapshot()->getValue() ?: [];
        
        foreach ($wishItemsData as $wkey => $wi) {
            $pvar_id = $wi['PVar_Id'] ?? '';
            $variant = null;
            foreach ($allVariants as $v) {
                if (($v['PVar_Id'] ?? '') == $pvar_id) { $variant = $v; break; }
            }
            
            if ($variant) {
                $prod_id = $variant['Prod_Id'] ?? '';
                $product = null;
                foreach ($allProducts as $p) {
                    if (($p['Prod_Id'] ?? '') == $prod_id) { $product = $p; break; }
                }
                
                if ($product) {
                    $brand_id = $product['Brand_Id'] ?? '';
                    $brand_name = '';
                    foreach ($allBrands as $b) {
                        if (($b['Brand_Id'] ?? '') == $brand_id) { $brand_name = $b['Brand_Name'] ?? ''; break; }
                    }
                    
                    $img = '';
                    foreach ($allImages as $pi) {
                        if (($pi['Prod_Id'] ?? '') == $prod_id && ($pi['PImg_IsPrimary'] ?? 0) == 1) {
                            $img = $pi['PImg_ImgUrl'] ?? '';
                            break;
                        }
                    }
                    
                    if (empty($img)) {
                        $img = "https://via.placeholder.com/400x500?text=No+Image";
                    } elseif (strpos($img, 'http') === false) {
                        $img = '../' . $img;
                    }
                    
                    $wish_items[] = [
                        "witm_id" => $wi['WItm_Id'] ?? $wkey,
                        "pvar_id" => $pvar_id,
                        "prod_id" => $prod_id,
                        "name"    => $product['Prod_Name'] ?? '',
                        "brand"   => $brand_name,
                        "size"    => $variant['PVar_Size'] ?? '',
                        "color"   => $variant['PVar_Color'] ?? '',
                        "price"   => $product['Prod_BasePrice'] ?? 0,
                        "img"     => $img,
                    ];
                }
            }
        }
    }
}

$count = count($wish_items);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>ZALORA — My Wishlist</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="../assets/css/global.css?v=<?= time() ?>"/>
    <link rel="stylesheet" href="../assets/css/wishlist.css?v=<?= time() ?>"/>
</head>
<body>

<!-- --- TOP PROMO BAR --- -->
<div class="top-promo-bar">
    <div class="promo-container-top">
        <a href="#" class="promo-item-top">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
            30 Days Free Returns | T&C Apply >
        </a>
        <a href="#" class="promo-item-top">
            <span style="background: #000; color:#fff; padding: 2px 5px; margin-right:5px; border-radius:2px;">VIP</span>
            Become a ZALORA VIP today! >
        </a>
        <a href="#" class="promo-item-top">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg>
            Save more on the app! 25% OFF + P150 OFF >
        </a>
    </div>
</div>

<!-- HEADER -->
<header>
    <div class="main-header">
        <a href="../index.php" class="logo">ZALORA</a>
        <div class="search-bar-wrap">
            <form action="products.php" method="GET">
                <input type="text" name="q" class="search-input" placeholder="Got You Scoring More">
                <button type="submit" class="search-icon-btn"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg></button>
            </form>
        </div>
        <div class="header-actions">
            <a href="profile.php" class="header-action-item">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <span><?= isset($_SESSION['user_id']) ? 'Hi ' . htmlspecialchars(explode(' ', $_SESSION['user_name'] ?? 'User')[0]) . ',' : 'Login' ?></span>
            </a>
            <a href="wishlist.php" class="header-action-item">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                <?php if ($nav_wish_count > 0): ?><span class="badge-count"><?= $nav_wish_count ?></span><?php endif; ?>
            </a>
            <a href="cart.php" class="header-action-item">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                <?php if ($nav_cart_count > 0): ?><span class="badge-count"><?= $nav_cart_count ?></span><?php endif; ?>
            </a>
        </div>
    </div>
    <nav class="nav-bar">
        <div class="nav-container">
            <a href="products.php?category=Women" class="nav-item">WOMEN</a>
            <a href="products.php?category=Men" class="nav-item">MEN</a>
            <a href="products.php?category=Kids" class="nav-item">KIDS</a>
            <a href="products.php?category=Luxury" class="nav-item">LUXURY</a>
            <a href="products.php?category=Beauty" class="nav-item">BEAUTY</a>
            <a href="products.php?category=Sports" class="nav-item">SPORTS</a>
        </div>
    </nav>
</header>

<div class="dashboard-container">
    
    <!-- SIDEBAR -->
    <aside class="sidebar">
        <h3>MY ACCOUNT</h3>
        <a href="profile.php" class="sidebar-link">Account information</a>
        <a href="#" class="sidebar-link">My Wallet</a>
        <a href="#" class="sidebar-link">My Cashback</a>
        <a href="#" class="sidebar-link">My ZVIP</a>
        <a href="profile.php?tab=orders" class="sidebar-link">Orders & Tracking</a>
        <a href="#" class="sidebar-link">My Reviews</a>
        <a href="#" class="sidebar-link">My Cards</a>
        <a href="#" class="sidebar-link">Preferences</a>
        <a href="wishlist.php" class="sidebar-link active">Wishlist</a>
        <a href="apply_role.php?role=seller" class="sidebar-link" style="font-weight:600; color:#c0392b;">Become a Seller</a>
        <a href="apply_role.php?role=driver" class="sidebar-link" style="font-weight:600; color:#2980b9;">Become a Driver</a>
        <a href="../auth/logout.php" class="sidebar-link">Sign out</a>
        <a href="#" class="sidebar-link">Request Account Deletion</a>
    </aside>

    <!-- CONTENT -->
    <main class="content-area">
        <h1 class="page-title">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
            My Wishlist (<?= $count ?>)
        </h1>

        <?php if ($count > 0): ?>
            <div class="wishlist-grid">
                <?php foreach ($wish_items as $item): 
                    $discount_pct = 10;
                    $orig_price = $item['price'];
                    $disc_price = $orig_price * (1 - ($discount_pct / 100));
                ?>
                    <div class="wish-card" id="wish-<?= $item['witm_id'] ?>">
                        <div class="img-container">
                            <button class="btn-remove" onclick="removeWish('<?= $item['witm_id'] ?>', '<?= $item['pvar_id'] ?>')" title="Remove">
                                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                            </button>
                            <a href="product.php?id=<?= $item['prod_id'] ?>">
                                <img src="<?= htmlspecialchars($item['img']) ?>" alt="<?= htmlspecialchars($item['name']) ?>"/>
                            </a>
                        </div>

                        <div class="prod-brand"><?= htmlspecialchars($item['brand']) ?></div>
                        <div class="prod-name"><?= htmlspecialchars($item['name']) ?></div>
                        
                        <div class="price-row">
                            <span class="current-price">Php <?= number_format($disc_price, 2) ?></span>
                        </div>
                        <div class="price-row" style="margin-top:-6px; margin-bottom:10px;">
                            <span class="old-price">Php <?= number_format($orig_price, 2) ?></span>
                            <span class="discount-tag">-<?= $discount_pct ?>%</span>
                        </div>
                        
                        <form action="add_to_cart.php" method="POST" style="margin-top:auto;">
                            <input type="hidden" name="pvar_id" value="<?= $item['pvar_id'] ?>">
                            <input type="hidden" name="quantity" value="1">
                            
                            <select class="size-select" disabled>
                                <option>Size: <?= htmlspecialchars($item['size']) ?></option>
                            </select>
                            
                            <button type="submit" class="btn-add-bag">Add to Bag</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <p>Your wishlist is empty. Save items that you love to keep track of them.</p>
                <a href="products.php" class="btn-shop">Start Shopping</a>
            </div>
        <?php endif; ?>
    </main>

</div>

<script>
    async function removeWish(witmId, pvarId) {
        if (!confirm('Remove this item from your wishlist?')) return;

        const formData = new FormData();
        formData.append('pvar_id', pvarId);

        try {
            const response = await fetch('toggle_wishlist_api.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.status === 'success') {
                const el = document.getElementById(`wish-${witmId}`);
                el.style.opacity = '0';
                setTimeout(() => {
                    location.reload();
                }, 200);
            }
        } catch (error) {
            console.error('Error removing wishlist item:', error);
        }
    }
</script>

</body>
</html>
