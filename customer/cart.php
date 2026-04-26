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
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --black: #0a0a0a;
            --white: #fafafa;
            --grey: #888;
            --light-grey: #e8e8e8;
            --accent: #c8a96e;
            --danger: #c0392b;
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
            position: relative;
            transition: opacity 0.2s;
            text-decoration: none;
        }

        .nav-actions a:hover { opacity: 0.5; }

        .cart-badge {
            position: absolute;
            top: -6px;
            right: -8px;
            background: var(--black);
            color: var(--white);
            font-size: 8px;
            font-weight: 700;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* ── PAGE WRAPPER ── */
        .page-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2.5rem 2.5rem 4rem;
        }

        /* ── PAGE TITLE ── */
        .page-title {
            font-family: var(--font-body);
            font-size: clamp(1.4rem, 3vw, 1.8rem);
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            margin-bottom: 2rem;
        }

        /* ── CART LAYOUT ── */
        .cart-layout {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 3rem;
            align-items: start;
        }

        /* ── CART ITEMS ── */
        .cart-item {
            display: grid;
            grid-template-columns: 100px 1fr auto;
            gap: 1.5rem;
            align-items: start;
            padding: 1.8rem 0;
            border-bottom: 1px solid var(--light-grey);
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .cart-item:first-child { border-top: 1px solid var(--light-grey); }

        .item-img {
            width: 100px;
            aspect-ratio: 3/4;
            object-fit: cover;
            display: block;
            background: #f0f0f0;
        }

        .item-details { padding-top: 4px; }

        .item-name {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .item-variant {
            font-size: 11px;
            color: var(--grey);
            margin-bottom: 1.2rem;
        }

        /* Qty stepper */
        .qty-stepper {
            display: inline-flex;
            align-items: center;
            border: 1px solid var(--light-grey);
            margin-bottom: 1.2rem;
        }

        .qty-btn {
            width: 34px;
            height: 34px;
            background: none;
            border: none;
            font-size: 1rem;
            cursor: pointer;
            color: var(--black);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }

        .qty-btn:hover { background: var(--light-grey); }

        .qty-val {
            width: 38px;
            text-align: center;
            font-family: var(--font-body);
            font-size: 12px;
            font-weight: 600;
            border: none;
            border-left: 1px solid var(--light-grey);
            border-right: 1px solid var(--light-grey);
            outline: none;
            background: transparent;
            padding: 0 4px;
            height: 34px;
        }

        .item-actions {
            display: flex;
            gap: 1.2rem;
        }

        .item-action-btn {
            background: none;
            border: none;
            font-family: var(--font-body);
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            cursor: pointer;
            padding: 0;
            text-decoration: underline;
            text-underline-offset: 3px;
            transition: color 0.2s;
        }

        .btn-fav { color: var(--black); }
        .btn-fav:hover { color: var(--accent); }

        .btn-remove { color: var(--danger); }
        .btn-remove:hover { opacity: 0.7; }

        .item-price {
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
            padding-top: 4px;
        }

        /* ── ORDER SUMMARY ── */
        .order-summary {
            border: 1px solid var(--light-grey);
            padding: 2rem;
            position: sticky;
            top: 76px;
        }

        .summary-title {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            margin-bottom: 1.8rem;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.9rem;
            font-size: 11px;
        }

        .summary-row .label { color: var(--grey); letter-spacing: 0.1em; text-transform: uppercase; font-size: 10px; }
        .summary-row .value { font-weight: 600; }
        .summary-row .free { color: #27ae60; font-weight: 700; font-size: 10px; }

        .summary-divider {
            border: none;
            border-top: 1px solid var(--light-grey);
            margin: 1.2rem 0;
        }

        .summary-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.8rem;
        }

        .summary-total .label {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        .summary-total .value {
            font-size: 16px;
            font-weight: 700;
        }

        .btn-checkout {
            width: 100%;
            background: var(--black);
            color: var(--white);
            border: none;
            padding: 16px;
            font-family: var(--font-body);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            cursor: pointer;
            margin-bottom: 1.5rem;
            transition: background 0.25s;
        }

        .btn-checkout:hover { background: #333; }

        /* Promo */
        .promo-row {
            display: flex;
            border-bottom: 1px solid var(--light-grey);
            margin-bottom: 1.5rem;
        }

        .promo-input {
            flex: 1;
            border: none;
            outline: none;
            background: transparent;
            font-family: var(--font-body);
            font-size: 11px;
            letter-spacing: 0.1em;
            padding: 8px 0;
            color: var(--grey);
            text-transform: uppercase;
        }

        .promo-apply {
            background: none;
            border: none;
            font-family: var(--font-body);
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            cursor: pointer;
            color: var(--black);
            transition: color 0.2s;
        }

        .promo-apply:hover { color: var(--accent); }

        /* Perks */
        .perk {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 0.75rem;
        }

        .perk svg { flex-shrink: 0; color: var(--grey); }

        .perk span {
            font-size: 10px;
            color: var(--grey);
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        /* ── YOU MAY ALSO LIKE ── */
        .recommendations {
            margin-top: 4rem;
        }

        .rec-title {
            font-family: var(--font-body);
            font-size: clamp(1.1rem, 2vw, 1.4rem);
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            margin-bottom: 1.8rem;
        }

        .rec-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.2rem;
        }

        .rec-card { cursor: pointer; }

        .rec-img-wrap {
            overflow: hidden;
            margin-bottom: 0.8rem;
        }

        .rec-img-wrap img {
            width: 100%;
            aspect-ratio: 1/1;
            object-fit: cover;
            display: block;
            transition: transform 0.5s ease;
        }

        .rec-card:hover .rec-img-wrap img { transform: scale(1.05); }

        .rec-name {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .rec-price {
            font-size: 11px;
            font-weight: 600;
            color: var(--grey);
        }

        /* ── FOOTER ── */
        footer {
            background: var(--black);
            color: rgba(255,255,255,0.5);
            padding: 2rem 2.5rem;
            margin-top: 4rem;
        }

        .footer-inner {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .footer-logo {
            font-weight: 700;
            font-size: 1rem;
            letter-spacing: 0.18em;
            color: var(--white);
        }

        .footer-links {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
        }

        .footer-links a {
            text-decoration: none;
            color: rgba(255,255,255,0.5);
            font-size: 10px;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            transition: color 0.2s;
        }

        .footer-links a:hover { color: var(--accent); }

        .footer-copy {
            width: 100%;
            font-size: 9px;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            margin-top: 1rem;
            color: rgba(255,255,255,0.25);
        }

        /* ── RESPONSIVE ── */
        @media (max-width: 900px) {
            .cart-layout { grid-template-columns: 1fr; }
            .order-summary { position: static; }
            .rec-grid { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 600px) {
            .nav-links { display: none; }
            .cart-item { grid-template-columns: 80px 1fr; }
            .item-price { grid-column: 2; }
        }
    </style>
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