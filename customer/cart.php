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
$cart_items = [];

// Fetch cart items from DB
$query = "SELECT ci.CItm_Id, ci.CItm_Quantity, pv.PVar_Size, pv.PVar_Color, p.Prod_Name, p.Prod_BasePrice, 
          (SELECT PImg_ImgUrl FROM PRODUCT_IMAGE WHERE Prod_Id = p.Prod_Id LIMIT 1) as Prod_Image
          FROM CART c
          JOIN CART_ITEM ci ON c.Cart_Id = ci.Cart_Id
          JOIN PRODUCT_VARIANT pv ON ci.PVar_Id = pv.PVar_Id
          JOIN PRODUCT p ON pv.Prod_Id = p.Prod_Id
          WHERE c.Cust_Id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $cust_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $img = $row['Prod_Image'] ?? "https://via.placeholder.com/400x500?text=No+Image";
    if (!empty($row['Prod_Image']) && strpos($row['Prod_Image'], 'http') === false) {
        $img = '../' . $row['Prod_Image'];
    }
    $cart_items[] = [
        "id"      => $row['CItm_Id'],
        "name"    => $row['Prod_Name'],
        "variant" => ($row['PVar_Color'] ? $row['PVar_Color'] . " • " : "") . "Size " . $row['PVar_Size'],
        "price"   => $row['Prod_BasePrice'],
        "qty"     => $row['CItm_Quantity'],
        "img"     => $img,
    ];
}

$count = count($cart_items);

// Updated recommendations for the empty state
$you_may_like = [
    ["brand" => "NIKE", "img" => "https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=400&q=80"],
    ["brand" => "NIKE", "img" => "https://images.unsplash.com/photo-1605348532760-6753d2c43329?w=400&q=80"],
    ["brand" => "NIKE", "img" => "https://images.unsplash.com/photo-1606107557195-0e29a4b5b4aa?w=400&q=80"],
    ["brand" => "Blackbough Swim", "img" => "https://images.unsplash.com/photo-1571650392338-766e409b5522?w=400&q=80"],
    ["brand" => "NIKE", "img" => "https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=400&q=80"],
    ["brand" => "New Balance", "img" => "https://images.unsplash.com/photo-1539185441755-769473a23570?w=400&q=80"],
];

$subtotal = array_sum(array_map(fn($i) => $i['price'] * $i['qty'], $cart_items));
$tax = round($subtotal * 0.08, 2);
$total = $subtotal + $tax;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>ZALORA — Shopping Bag</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="../assets/css/global.css"/>
    <link rel="stylesheet" href="../assets/css/cart.css"/>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Montserrat', sans-serif; background: #fff; color: #000; }
        
        /* SIMPLIFIED CHECKOUT HEADER */
        .checkout-header { display: flex; justify-content: space-between; align-items: center; padding: 20px 40px; border-bottom: 1px solid #eaeaea; background: #fff; }
        .checkout-logo { font-size: 20px; font-weight: 400; letter-spacing: 0.4em; text-decoration: none; color: #000; margin-left: 20%; }
        .header-actions { display: flex; gap: 20px; align-items: center; }
        .header-icon { color: #000; position: relative; text-decoration: none; display: flex; align-items: center; }
        .badge-count { position: absolute; top: -6px; right: -8px; background: #c0392b; color: #fff; font-size: 9px; padding: 2px 5px; border-radius: 10px; font-weight: 700; }

        /* EMPTY STATE STYLES */
        .empty-bag-wrapper { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 80px 20px 60px; text-align: center; }
        
        .empty-icon-circle { width: 140px; height: 140px; background: #f6f6f6; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 25px; position: relative; }
        .empty-icon-circle svg { color: #555; }
        .sparkle-1 { position: absolute; top: 15px; left: 15px; color: #ccc; }
        .sparkle-2 { position: absolute; bottom: 25px; right: 10px; color: #aaa; }
        .sparkle-3 { position: absolute; bottom: 15px; right: 25px; color: #ccc; }
        
        .empty-title { font-size: 15px; font-weight: 800; margin-bottom: 6px; color: #111; }
        .empty-subtitle { font-size: 11px; color: #666; margin-bottom: 25px; }
        .btn-go-shopping { display: inline-block; padding: 10px 24px; border: 1px solid #000; border-radius: 4px; color: #000; text-decoration: none; font-size: 11px; font-weight: 600; transition: 0.2s; }
        .btn-go-shopping:hover { background: #000; color: #fff; }

        /* RECOMMENDATIONS SECTION */
        .rec-section { max-width: 1200px; margin: 0 auto 100px; background: #fbfbfb; border-radius: 12px; padding: 25px; }
        .rec-title { font-size: 13px; font-weight: 800; color: #333; margin-bottom: 20px; text-align: left; }
        
        .rec-row { display: flex; gap: 15px; overflow-x: auto; padding-bottom: 10px; }
        .rec-row::-webkit-scrollbar { height: 4px; }
        .rec-row::-webkit-scrollbar-thumb { background: #ddd; border-radius: 4px; }
        
        .rec-item { min-width: 180px; flex: 1; background: #fff; border-radius: 8px; overflow: hidden; position: relative; padding-bottom: 15px; display: flex; flex-direction: column; box-shadow: 0 2px 5px rgba(0,0,0,0.02); }
        .rec-img-container { background: #f0f0f0; aspect-ratio: 1; margin-bottom: 10px; overflow: hidden; display: flex; align-items: center; justify-content: center; }
        .rec-img-container img { width: 100%; height: 100%; object-fit: contain; mix-blend-mode: multiply; }
        .rec-brand { font-size: 10px; font-weight: 800; padding: 0 12px; color: #111; text-transform: uppercase; }
        
        .btn-heart { position: absolute; bottom: 12px; right: 12px; background: none; border: none; cursor: pointer; color: #999; display: flex; align-items: center; justify-content: center; }
        .btn-heart:hover { color: #000; }
        
        .chat-widget { position: fixed; bottom: 30px; right: 30px; width: 50px; height: 50px; background: #222; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 800; font-size: 20px; cursor: pointer; box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
    </style>
</head>
<body>

<!-- CHECKOUT SIMPLIFIED HEADER -->
<header class="checkout-header">
    <div style="width:100px;"></div> <!-- spacer -->
    <a href="../index.php" class="checkout-logo">ZALORA</a>
    <div class="header-actions">
        <a href="wishlist.php" class="header-icon">
            <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
            <?php if ($nav_wish_count > 0): ?><span class="badge-count"><?= $nav_wish_count ?></span><?php endif; ?>
        </a>
        <a href="cart.php" class="header-icon">
            <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
            <?php if ($nav_cart_count > 0): ?><span class="badge-count"><?= $nav_cart_count ?></span><?php endif; ?>
        </a>
    </div>
</header>

<?php if ($count === 0): ?>
    <!-- EMPTY STATE -->
    <div class="empty-bag-wrapper">
        <div class="empty-icon-circle">
            <svg class="sparkle-1" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L14.5 9.5L22 12L14.5 14.5L12 22L9.5 14.5L2 12L9.5 9.5L12 2Z"/></svg>
            <svg class="sparkle-2" width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L14.5 9.5L22 12L14.5 14.5L12 22L9.5 14.5L2 12L9.5 9.5L12 2Z"/></svg>
            <svg class="sparkle-3" width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L14.5 9.5L22 12L14.5 14.5L12 22L9.5 14.5L2 12L9.5 9.5L12 2Z"/></svg>
            
            <svg width="50" height="50" fill="none" stroke="#555" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                <rect x="3" y="6" width="18" height="15" rx="2" ry="2"/>
                <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                <path d="M8 11s1.5 2 4 2 4-2 4-2"/>
            </svg>
        </div>
        
        <h2 class="empty-title">Your Bag is empty.</h2>
        <p class="empty-subtitle">Start filling it up with your favourites.</p>
        <a href="products.php" class="btn-go-shopping">Let's go Shopping!</a>
    </div>

    <!-- RECOMMENDATIONS (Empty State) -->
    <div class="rec-section">
        <h3 class="rec-title">Just For You: Best Sellers & Crowd Favorites</h3>
        <div class="rec-row">
            <?php foreach ($you_may_like as $rec): ?>
                <div class="rec-item">
                    <div class="rec-img-container">
                        <img src="<?= htmlspecialchars($rec['img']) ?>" alt="<?= htmlspecialchars($rec['brand']) ?>"/>
                    </div>
                    <span class="rec-brand"><?= htmlspecialchars($rec['brand']) ?></span>
                    <button class="btn-heart">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php else: ?>
    <!-- POPULATED CART LAYOUT (Kept from original) -->
    <div class="page-wrapper" style="max-width:1200px; margin:40px auto; padding:0 20px;">
        <h1 class="page-title" style="font-family:'Cormorant Garamond',serif; font-size:2rem; font-weight:300; margin-bottom:40px;">Shopping Bag (<?= $count ?>)</h1>
        
        <div class="cart-layout" style="display:grid; grid-template-columns: 2fr 1fr; gap:40px;">
            <div class="cart-items" id="cart-items">
                <?php foreach ($cart_items as $item): ?>
                <div class="cart-item" id="item-<?= $item['id'] ?>" style="display:flex; gap:20px; padding:20px 0; border-bottom:1px solid #eee;">
                    <img class="item-img" src="<?= htmlspecialchars($item['img']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" style="width:120px; aspect-ratio:3/4; object-fit:cover;"/>
                    <div class="item-details" style="flex:1;">
                        <p class="item-name" style="font-size:14px; margin-bottom:5px;"><?= htmlspecialchars($item['name']) ?></p>
                        <p class="item-variant" style="font-size:11px; color:#666; margin-bottom:15px;"><?= htmlspecialchars($item['variant']) ?></p>
                        <div class="item-actions">
                            <button class="item-action-btn btn-remove" onclick="removeItem(<?= $item['id'] ?>)" style="font-size:11px; background:none; border:none; text-decoration:underline; cursor:pointer;">Remove</button>
                        </div>
                    </div>
                    <p class="item-price" id="price-<?= $item['id'] ?>" data-unit-price="<?= $item['price'] ?>" style="font-weight:600;">$<?= number_format($item['price'] * $item['qty'], 2) ?></p>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="order-summary" style="background:#f9f9f9; padding:30px; height:fit-content;">
                <p class="summary-title" style="font-size:14px; font-weight:700; margin-bottom:20px;">Order Summary</p>
                <div class="summary-row" style="display:flex; justify-content:space-between; margin-bottom:10px; font-size:13px;">
                    <span>Subtotal</span>
                    <span id="summary-subtotal">$<?= number_format($subtotal, 2) ?></span>
                </div>
                <div class="summary-row" style="display:flex; justify-content:space-between; margin-bottom:10px; font-size:13px;">
                    <span>Estimated Tax</span>
                    <span id="summary-tax">$<?= number_format($tax, 2) ?></span>
                </div>
                <hr style="border:none; border-top:1px solid #ddd; margin:15px 0;"/>
                <div class="summary-total" style="display:flex; justify-content:space-between; font-weight:700; margin-bottom:20px;">
                    <span>Total</span>
                    <span id="summary-total">$<?= number_format($total, 2) ?></span>
                </div>
                <a href="checkout.php" style="display:block; width:100%; padding:15px; background:#000; color:#fff; text-align:center; text-decoration:none; font-size:12px; font-weight:700; text-transform:uppercase;">Proceed to Checkout</a>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="chat-widget">Z</div>

<script>
    const TAX_RATE = 0.08;
    async function removeItem(citmId) {
        if (!confirm('Remove this item from your bag?')) return;
        const formData = new FormData();
        formData.append('action', 'remove');
        formData.append('citm_id', citmId);
        try {
            const response = await fetch('update_cart_api.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.status === 'success') {
                location.reload();
            }
        } catch (error) {
            console.error('Error removing item:', error);
        }
    }
</script>

</body>
</html>