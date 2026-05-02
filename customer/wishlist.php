<?php
session_start();
require_once '../config/db.php';

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
    $wish_items[] = [
        "witm_id" => $row['WItm_Id'],
        "pvar_id" => $row['PVar_Id'],
        "prod_id" => $row['Prod_Id'],
        "name"    => $row['Prod_Name'],
        "brand"   => $row['Brand_Name'],
        "variant" => ($row['PVar_Color'] ? $row['PVar_Color'] . " • " : "") . "Size " . $row['PVar_Size'],
        "price"   => $row['Prod_BasePrice'],
        "img"     => $row['Prod_Image'] ?? "https://via.placeholder.com/400x500?text=No+Image",
    ];
}

$count = count($wish_items);
$nav_links = ["WOMEN", "MEN", "KIDS", "LUXURY", "BEAUTY"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>ZALORA — My Wishlist</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;1,300&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="../assets/css/global.css?v=1.1"/>
    <style>
        :root {
            --black: #111;
            --white: #fff;
            --grey: #757575;
            --light-grey: #f5f5f5;
            --border: #e0e0e0;
        }
        body { font-family: 'Montserrat', sans-serif; background: var(--white); color: var(--black); margin: 0; padding-top: 56px; }
        
        .page-wrapper { max-width: 1200px; margin: 40px auto; padding: 0 20px; min-height: 60vh; }
        .page-title { font-family: 'Cormorant Garamond', serif; font-size: 2.5rem; font-weight: 300; margin-bottom: 40px; text-align: center; letter-spacing: 2px; }
        
        .wishlist-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 40px 30px; }
        
        .wish-card { position: relative; transition: transform 0.3s ease; }
        .wish-card:hover { transform: translateY(-5px); }
        
        .img-wrap { aspect-ratio: 3/4; overflow: hidden; background: var(--light-grey); position: relative; margin-bottom: 15px; }
        .img-wrap img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.6s ease; }
        .wish-card:hover .img-wrap img { transform: scale(1.05); }
        
        .btn-remove-wish { position: absolute; top: 10px; right: 10px; background: rgba(255,255,255,0.9); border: none; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 18px; color: var(--black); transition: background 0.2s; z-index: 2; }
        .btn-remove-wish:hover { background: var(--black); color: var(--white); }
 
        .wish-info { text-align: center; }
        .wish-brand { font-size: 10px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; margin-bottom: 5px; color: var(--grey); }
        .wish-name { font-size: 14px; font-weight: 400; margin-bottom: 8px; color: var(--black); text-decoration: none; display: block; }
        .wish-variant { font-size: 11px; color: var(--grey); margin-bottom: 10px; }
        .wish-price { font-size: 15px; font-weight: 600; margin-bottom: 15px; }
        
        .btn-move-bag { width: 100%; padding: 12px; background: var(--black); color: var(--white); border: 1px solid var(--black); font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; cursor: pointer; transition: all 0.3s; }
        .btn-move-bag:hover { background: var(--white); color: var(--black); }
 
        .empty-state { text-align: center; padding: 100px 0; }
        .empty-state h2 { font-family: 'Cormorant Garamond', serif; font-weight: 300; font-size: 2rem; margin-bottom: 20px; }
        .btn-shop { display: inline-block; padding: 15px 40px; background: var(--black); color: var(--white); text-decoration: none; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; }
 
        footer { border-top: 1px solid var(--border); padding: 60px 40px; text-align: center; margin-top: 80px; }
        .footer-logo { font-family: 'Cormorant Garamond', serif; font-size: 24px; letter-spacing: 4px; display: block; margin-bottom: 20px; }
        .footer-copy { font-size: 11px; color: var(--grey); }
    </style>
</head>
<body>
 
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
        <a href="cart.php" title="Cart" style="color:var(--black); position:relative; display:flex; align-items:center;">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
            <?php if ($nav_cart_count > 0): ?>
                <span class="cart-badge" style="top:-8px; right:-8px;"><?= $nav_cart_count ?></span>
            <?php endif; ?>
        </a>
    </div>
</nav>

<div class="page-wrapper">
    <h1 class="page-title">My Wishlist (<?= $count ?>)</h1>

    <?php if ($count > 0): ?>
        <div class="wishlist-grid">
            <?php foreach ($wish_items as $item): ?>
                <div class="wish-card" id="wish-<?= $item['witm_id'] ?>">
                    <button class="btn-remove-wish" onclick="removeWish(<?= $item['witm_id'] ?>, <?= $item['pvar_id'] ?>)" title="Remove">×</button>
                    
                    <a href="product.php?id=<?= $item['prod_id'] ?>" class="img-wrap">
                        <img src="<?= htmlspecialchars($item['img']) ?>" alt="<?= htmlspecialchars($item['name']) ?>"/>
                    </a>

                    <div class="wish-info">
                        <p class="wish-brand"><?= htmlspecialchars($item['brand']) ?></p>
                        <a href="product.php?id=<?= $item['prod_id'] ?>" class="wish-name"><?= htmlspecialchars($item['name']) ?></a>
                        <p class="wish-variant"><?= htmlspecialchars($item['variant']) ?></p>
                        <p class="wish-price">$<?= number_format($item['price'], 2) ?></p>
                        
                        <form action="add_to_cart.php" method="POST">
                            <input type="hidden" name="pvar_id" value="<?= $item['pvar_id'] ?>">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" class="btn-move-bag">Add to Bag</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <h2>Your wishlist is empty</h2>
            <p>Save items that you love to keep track of them.</p>
            <br>
            <a href="products.php" class="btn-shop">Start Shopping</a>
        </div>
    <?php endif; ?>
</div>

<footer>
    <span class="footer-logo">ZALORA</span>
    <p class="footer-copy">© <?= date('Y') ?> Zalora. All Rights Reserved.</p>
</footer>

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
                el.style.transform = 'scale(0.9)';
                setTimeout(() => {
                    location.reload(); // Simplest way to update count and layout
                }, 300);
            }
        } catch (error) {
            console.error('Error removing wishlist item:', error);
        }
    }
</script>

</body>
</html>
