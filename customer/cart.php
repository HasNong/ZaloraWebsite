<?php
session_start();

// Static cart items
$cart_items = [
    [
        "name"    => "TAILORED WOOL COAT",
        "variant" => "Charcoal Grey • Size 40",
        "price"   => 450.00,
        "qty"     => 1,
        "img"     => "https://images.unsplash.com/photo-1544022613-e87ca75a784a?w=400&q=80",
    ],
    [
        "name"    => "SILK DRAPE MIDI DRESS",
        "variant" => "Ivory • Size 38",
        "price"   => 280.00,
        "qty"     => 1,
        "img"     => "https://images.unsplash.com/photo-1515372039744-b8f02a3ae446?w=400&q=80",
    ],
    [
        "name"    => "POLISHED LEATHER BOOTS",
        "variant" => "Black • Size 42",
        "price"   => 320.00,
        "qty"     => 1,
        "img"     => "https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=400&q=80",
    ],
];

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
    <link rel="stylesheet" href="assets/css/global.css"/>
    <link rel="stylesheet" href="../assets/css/cart.css"/>
</head>
<body>

<!-- ── NAV ── -->
<nav>
    <a href="../index.php" class="nav-logo">ZALORA</a>
    <ul class="nav-links">
        <?php foreach ($nav_links as $link): ?>
            <li><a href="#"><?= htmlspecialchars($link) ?></a></li>
        <?php endforeach; ?>
    </ul>
    <div class="nav-actions">
        <a href="#" title="Search">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
        </a>
        <a href="login.php" title="Account">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        </a>
        <a href="#" title="Wishlist">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
        </a>
        <a href="cart.php" title="Cart" style="color:var(--black);">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
            <span class="cart-badge"><?= $count ?></span>
        </a>
    </div>
</nav>

<!-- ── PAGE ── -->
<div class="page-wrapper">

    <h1 class="page-title">Shopping Bag (<?= $count ?>)</h1>

    <div class="cart-layout">

        <!-- CART ITEMS -->
        <div class="cart-items" id="cart-items">
            <?php foreach ($cart_items as $index => $item): ?>
            <div class="cart-item" id="item-<?= $index ?>">
                <img class="item-img" src="<?= htmlspecialchars($item['img']) ?>" alt="<?= htmlspecialchars($item['name']) ?>"/>

                <div class="item-details">
                    <p class="item-name"><?= htmlspecialchars($item['name']) ?></p>
                    <p class="item-variant"><?= htmlspecialchars($item['variant']) ?></p>

                    <div class="qty-stepper">
                        <button class="qty-btn" onclick="changeQty(<?= $index ?>, -1)">−</button>
                        <input class="qty-val" id="qty-<?= $index ?>" type="number" value="<?= $item['qty'] ?>" min="1" max="99" readonly/>
                        <button class="qty-btn" onclick="changeQty(<?= $index ?>, 1)">+</button>
                    </div>

                    <div class="item-actions">
                        <button class="item-action-btn btn-fav">Move to Favorites</button>
                        <button class="item-action-btn btn-remove" onclick="removeItem(<?= $index ?>)">Remove</button>
                    </div>
                </div>

                <p class="item-price" id="price-<?= $index ?>">$<?= number_format($item['price'], 2) ?></p>
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

            <button class="btn-checkout">Proceed to Checkout</button>

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
    // Item prices (for JS recalculation)
    const prices = <?= json_encode(array_column($cart_items, 'price')) ?>;
    const qtys   = <?= json_encode(array_column($cart_items, 'qty')) ?>;
    const TAX_RATE = 0.08;

    function changeQty(index, delta) {
        const input = document.getElementById(`qty-${index}`);
        let val = parseInt(input.value) + delta;
        if (val < 1) val = 1;
        if (val > 99) val = 99;
        input.value = val;
        qtys[index] = val;

        // Update individual price display
        const linePrice = prices[index] * val;
        document.getElementById(`price-${index}`).textContent = '$' + linePrice.toFixed(2);

        recalcSummary();
    }

    function removeItem(index) {
        const el = document.getElementById(`item-${index}`);
        el.style.transition = 'opacity 0.3s, transform 0.3s';
        el.style.opacity = '0';
        el.style.transform = 'translateX(-20px)';
        setTimeout(() => {
            el.remove();
            prices[index] = 0;
            qtys[index]   = 0;
            recalcSummary();
            updateBagCount();
        }, 300);
    }

    function recalcSummary() {
        const subtotal = prices.reduce((sum, p, i) => sum + p * (qtys[i] || 0), 0);
        const tax      = subtotal * TAX_RATE;
        const total    = subtotal + tax;
        document.getElementById('summary-subtotal').textContent = '$' + subtotal.toFixed(2);
        document.getElementById('summary-tax').textContent      = '$' + tax.toFixed(2);
        document.getElementById('summary-total').textContent    = '$' + total.toFixed(2);
    }

    function updateBagCount() {
        const remaining = document.querySelectorAll('.cart-item').length;
        document.querySelector('.page-title').textContent = `Shopping Bag (${remaining})`;
        document.querySelector('.cart-badge').textContent = remaining;
    }
</script>

</body>
</html>