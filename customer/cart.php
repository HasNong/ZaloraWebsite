<?php
session_start();
require_once '../config/db.php';

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

$you_may_like = [
    [
        "name"  => "STRUCTURED TOTE",
        "price" => 195.00,
        "img"   => "https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=400&q=80",
    ],
    [
        "name"  => "CASHMERE WRAP",
        "price" => 120.00,
        "img"   => "https://images.unsplash.com/photo-1520903920243-00d872a2d1c9?w=400&q=80",
    ],
    [
        "name"  => "HORIZON WATCH",
        "price" => 350.00,
        "img"   => "https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=400&q=80",
    ],
    [
        "name"  => "ONYX AVIATORS",
        "price" => 180.00,
        "img"   => "https://images.unsplash.com/photo-1511499767150-a48a237f0083?w=400&q=80",
    ],
];

$subtotal = array_sum(array_map(fn($i) => $i['price'] * $i['qty'], $cart_items));
$shipping = 0;
$tax      = round($subtotal * 0.08, 2);
$total    = $subtotal + $shipping + $tax;
$count    = count($cart_items);
$nav_links = ["WOMEN", "MEN", "KIDS", "LUXURY", "BEAUTY"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>ZALORA — Shopping Bag</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;1,300&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="../assets/css/global.css"/>
    <link rel="stylesheet" href="../assets/css/cart.css"/>
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
        <a href="cart.php" title="Cart" style="color:var(--black); position:relative; display:flex; align-items:center;">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
            <?php if ($nav_cart_count > 0): ?>
                <span class="cart-badge" style="top:-8px; right:-8px;"><?= $nav_cart_count ?></span>
            <?php endif; ?>
        </a>
    </div>
</nav>

<!-- ── PAGE ── -->
<div class="page-wrapper">

    <h1 class="page-title">Shopping Bag (<?= $count ?>)</h1>

    <div class="cart-layout">

        <!-- CART ITEMS -->
        <div class="cart-items" id="cart-items">
            <?php foreach ($cart_items as $item): ?>
            <div class="cart-item" id="item-<?= $item['id'] ?>">
                <img class="item-img" src="<?= htmlspecialchars($item['img']) ?>" alt="<?= htmlspecialchars($item['name']) ?>"/>

                <div class="item-details">
                    <p class="item-name"><?= htmlspecialchars($item['name']) ?></p>
                    <p class="item-variant"><?= htmlspecialchars($item['variant']) ?></p>

                    <div class="qty-stepper">
                        <button class="qty-btn" onclick="changeQty(<?= $item['id'] ?>, -1)">−</button>
                        <input class="qty-val" id="qty-<?= $item['id'] ?>" type="number" value="<?= $item['qty'] ?>" min="1" max="99" readonly/>
                        <button class="qty-btn" onclick="changeQty(<?= $item['id'] ?>, 1)">+</button>
                    </div>

                    <div class="item-actions">
                        <button class="item-action-btn btn-fav">Move to Favorites</button>
                        <button class="item-action-btn btn-remove" onclick="removeItem(<?= $item['id'] ?>)">Remove</button>
                    </div>
                </div>

                <p class="item-price" id="price-<?= $item['id'] ?>" data-unit-price="<?= $item['price'] ?>">$<?= number_format($item['price'] * $item['qty'], 2) ?></p>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- ORDER SUMMARY -->
        <div class="order-summary">
            <p class="summary-title">Order Summary</p>

            <div class="summary-row">
                <span class="label">Subtotal</span>
                <span class="value" id="summary-subtotal">$<?= number_format($subtotal, 2) ?></span>
            </div>
            <div class="summary-row">
                <span class="label">Shipping</span>
                <span class="free">FREE</span>
            </div>
            <div class="summary-row">
                <span class="label">Estimated Tax</span>
                <span class="value" id="summary-tax">$<?= number_format($tax, 2) ?></span>
            </div>

            <hr class="summary-divider"/>

            <div class="summary-total">
                <span class="label">Total</span>
                <span class="value" id="summary-total">$<?= number_format($total, 2) ?></span>
            </div>

            <a href="checkout.php" class="btn-checkout" style="text-decoration:none; display:block; text-align:center;">Proceed to Checkout</a>

            <div class="promo-row">
                <input class="promo-input" type="text" placeholder="Promo Code"/>
                <button class="promo-apply">Apply</button>
            </div>

            <div class="perk">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 5v3h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                <span>Free Shipping on All Orders</span>
            </div>
            <div class="perk">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                <span>30-Day Easy Returns Policy</span>
            </div>
            <div class="perk">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                <span>Secure Checkout Guarantee</span>
            </div>
        </div>
    </div>

    <!-- YOU MAY ALSO LIKE -->
    <div class="recommendations">
        <h2 class="rec-title">You May Also Like</h2>
        <div class="rec-grid">
            <?php foreach ($you_may_like as $rec): ?>
            <div class="rec-card">
                <div class="rec-img-wrap">
                    <img src="<?= htmlspecialchars($rec['img']) ?>" alt="<?= htmlspecialchars($rec['name']) ?>" loading="lazy"/>
                </div>
                <p class="rec-name"><?= htmlspecialchars($rec['name']) ?></p>
                <p class="rec-price">$<?= number_format($rec['price'], 2) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<!-- ── FOOTER ── -->
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
    const TAX_RATE = 0.08;

    async function changeQty(citmId, delta) {
        const input = document.getElementById(`qty-${citmId}`);
        let val = parseInt(input.value) + delta;
        if (val < 1) val = 1;
        if (val > 99) val = 99;

        // Send update to DB
        const formData = new FormData();
        formData.append('action', 'update_qty');
        formData.append('citm_id', citmId);
        formData.append('quantity', val);

        try {
            const response = await fetch('update_cart_api.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            
            if (result.status === 'success') {
                input.value = val;
                // Update line price
                const priceEl = document.getElementById(`price-${citmId}`);
                const unitPrice = parseFloat(priceEl.dataset.unitPrice);
                priceEl.textContent = '$' + (unitPrice * val).toFixed(2);
                recalcSummary();
            }
        } catch (error) {
            console.error('Error updating quantity:', error);
        }
    }

    async function removeItem(citmId) {
        if (!confirm('Remove this item from your bag?')) return;

        const formData = new FormData();
        formData.append('action', 'remove');
        formData.append('citm_id', citmId);

        try {
            const response = await fetch('update_cart_api.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.status === 'success') {
                const el = document.getElementById(`item-${citmId}`);
                el.style.transition = 'opacity 0.3s, transform 0.3s';
                el.style.opacity = '0';
                el.style.transform = 'translateX(-20px)';
                setTimeout(() => {
                    el.remove();
                    recalcSummary();
                    updateBagCount();
                }, 300);
            }
        } catch (error) {
            console.error('Error removing item:', error);
        }
    }

    function recalcSummary() {
        let subtotal = 0;
        document.querySelectorAll('.item-price').forEach(el => {
            const price = parseFloat(el.textContent.replace('$', ''));
            subtotal += price;
        });

        const tax = subtotal * TAX_RATE;
        const total = subtotal + tax;

        document.getElementById('summary-subtotal').textContent = '$' + subtotal.toFixed(2);
        document.getElementById('summary-tax').textContent = '$' + tax.toFixed(2);
        document.getElementById('summary-total').textContent = '$' + total.toFixed(2);
    }

    function updateBagCount() {
        const remaining = document.querySelectorAll('.cart-item').length;
        document.querySelector('.page-title').textContent = `Shopping Bag (${remaining})`;
        document.querySelector('.cart-badge').textContent = remaining;
        
        if (remaining === 0) {
            document.getElementById('cart-items').innerHTML = '<p style="padding: 2rem 0; color: var(--text-muted); font-style: italic;">Your bag is empty.</p>';
        }
    }
</script>

</body>
</html>