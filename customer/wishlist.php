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
$query = "SELECT wi.WItm_Id, pv.PVar_Id, pv.PVar_Size, pv.PVar_Color, p.Prod_Id, p.Prod_Name, p.Prod_BasePrice, b.Brand_Name,
          (SELECT PImg_ImgUrl FROM PRODUCT_IMAGE WHERE Prod_Id = p.Prod_Id AND PImg_IsPrimary = 1 LIMIT 1) as Prod_Image
          FROM WISHLIST w
          JOIN WISHLIST_ITEM wi ON w.Wish_Id = wi.Wish_Id
          JOIN PRODUCT_VARIANT pv ON wi.PVar_Id = pv.PVar_Id
          JOIN PRODUCT p ON pv.Prod_Id = p.Prod_Id
          JOIN BRAND b ON p.Brand_Id = b.Brand_Id
          WHERE w.Cust_Id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $cust_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $img = $row['Prod_Image'] ?? "https://via.placeholder.com/400x500?text=No+Image";
    if (!empty($row['Prod_Image']) && strpos($row['Prod_Image'], 'http') === false) {
        $img = '../' . $row['Prod_Image'];
    }
    $wish_items[] = [
        "witm_id" => $row['WItm_Id'],
        "pvar_id" => $row['PVar_Id'],
        "prod_id" => $row['Prod_Id'],
        "name"    => $row['Prod_Name'],
        "brand"   => $row['Brand_Name'],
        "size"    => $row['PVar_Size'],
        "color"   => $row['PVar_Color'],
        "price"   => $row['Prod_BasePrice'],
        "img"     => $img,
    ];
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
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Montserrat', sans-serif; background: #fff; color: #000; }

        /* HEADER STYLES */
        .top-promo-bar { background: #fff; border-bottom: 1px solid #eee; padding: 10px 0; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; }
        .promo-container-top { max-width: 1400px; margin: 0 auto; display: flex; justify-content: space-around; }
        .promo-item-top { color: #000; text-decoration: none; display: flex; align-items: center; gap: 8px; }
        header { background: #fff; position: sticky; top: 0; z-index: 1000; border-bottom: 1px solid #eee; }
        .main-header { max-width: 1400px; margin: 0 auto; padding: 15px 20px; display: flex; align-items: center; justify-content: space-between; }
        .logo { font-size: 24px; font-weight: 400; letter-spacing: 0.3em; text-decoration: none; color: #000; }
        .search-bar-wrap { flex: 1; max-width: 500px; margin: 0 40px; position: relative; }
        .search-input { width: 100%; padding: 12px 25px; border: 1px solid #ddd; border-radius: 100px; font-size: 13px; background: #f5f5f5; outline: none; }
        .search-icon-btn { position: absolute; right: 5px; top: 50%; transform: translateY(-50%); background: #000; color: #fff; border: none; width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; }
        .header-actions { display: flex; gap: 20px; }
        .header-action-item { color: #000; text-decoration: none; font-size: 13px; display: flex; align-items: center; gap: 5px; position: relative; }
        .badge-count { position: absolute; top: -8px; right: -12px; background: #000; color: #fff; font-size: 9px; padding: 2px 6px; border-radius: 10px; }
        
        .nav-bar { border-bottom: 1px solid #eee; }
        .nav-container { max-width: 1400px; margin: 0 auto; display: flex; justify-content: center; gap: 40px; padding: 15px 0; }
        .nav-item { font-size: 11px; font-weight: 700; text-transform: uppercase; text-decoration: none; color: #000; letter-spacing: 0.1em; padding: 4px 8px; border-radius: 4px; border: 2px solid transparent; }
        .nav-item.active { border-color: #000; }

        /* DASHBOARD LAYOUT */
        .dashboard-container { max-width: 1300px; margin: 40px auto; padding: 0 20px; display: flex; gap: 40px; }
        
        /* SIDEBAR */
        .sidebar { width: 250px; flex-shrink: 0; background: #f8f8f8; padding: 20px 0; border-radius: 8px; height: fit-content; }
        .sidebar h3 { font-size: 12px; margin: 10px 20px 20px; text-transform: uppercase; font-weight: 800; letter-spacing: 0.05em; }
        .sidebar-link { display: block; padding: 15px 20px; font-size: 12px; color: #444; text-decoration: none; }
        .sidebar-link:hover { background: #eee; }
        .sidebar-link.active { background: #444; color: #fff; font-weight: 600; }

        /* MAIN CONTENT */
        .content-area { flex: 1; }
        .page-title { display: flex; align-items: center; gap: 10px; font-size: 18px; font-weight: 400; margin-bottom: 25px; color: #333; }
        
        .wishlist-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
        
        /* PRODUCT CARD */
        .wish-card { position: relative; display: flex; flex-direction: column; background: #fff; border-radius: 8px; padding-bottom: 15px; }
        .img-container { background: #f0f0f0; border-radius: 8px; aspect-ratio: 3/4; overflow: hidden; position: relative; margin-bottom: 12px; display: flex; align-items: center; justify-content: center; }
        .img-container img { width: 100%; height: 100%; object-fit: cover; }
        
        .btn-remove { position: absolute; top: 10px; right: 10px; background: transparent; border: none; font-size: 18px; cursor: pointer; color: #666; transition: 0.2s; z-index: 2; display: flex; align-items: center; justify-content: center; width: 24px; height: 24px; }
        .btn-remove:hover { color: #000; }

        .prod-brand { font-size: 11px; font-weight: 800; text-transform: uppercase; margin-bottom: 4px; color: #111; }
        .prod-name { font-size: 11px; color: #666; margin-bottom: 10px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        
        .price-row { display: flex; align-items: baseline; gap: 8px; margin-bottom: 4px; }
        .current-price { font-size: 14px; font-weight: 700; color: #c0392b; }
        .old-price { font-size: 10px; color: #999; text-decoration: line-through; }
        .discount-tag { font-size: 10px; color: #c0392b; font-weight: 700; }
        
        .size-select { width: 100%; padding: 8px 10px; font-size: 11px; border: 1px solid #eee; border-radius: 4px; outline: none; margin-bottom: 10px; background: #fff; color: #444; appearance: none; }
        
        .btn-add-bag { width: 100%; padding: 12px; background: #333; color: #fff; border: none; border-radius: 6px; font-size: 11px; font-weight: 700; cursor: pointer; transition: background 0.3s; }
        .btn-add-bag:hover { background: #000; }
        
        .empty-state { text-align: center; padding: 100px 0; }
        .empty-state p { margin-bottom: 20px; font-size: 14px; color: #666; }
        .btn-shop { display: inline-block; padding: 12px 30px; background: #000; color: #fff; text-decoration: none; font-size: 12px; font-weight: 700; text-transform: uppercase; border-radius: 4px; }
    </style>
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
                            <button class="btn-remove" onclick="removeWish(<?= $item['witm_id'] ?>, <?= $item['pvar_id'] ?>)" title="Remove">
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
