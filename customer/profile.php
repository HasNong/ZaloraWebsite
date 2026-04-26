<?php
session_start();

$user = [
    "name"            => "Alexander",
    "membership"      => "PLATINUM",
    "points_to_next"  => 1250,
    "next_tier"       => "DIAMOND",
    "wallet"          => 428.50,
];

$latest_order = [
    "id"      => "ZL-98231",
    "name"    => "Tailored Wool Blend Overcoat",
    "status"  => "In Transit",
    "arrives" => "Arriving Thursday, Oct 24",
    "img"     => "https://images.unsplash.com/photo-1551028719-00167b16eac5?w=300&q=80",
];

$recent_orders = [
    [
        "id"     => "ZL-87721",
        "date"   => "Oct 12, 2023",
        "items"  => 2,
        "total"  => 285.00,
        "status" => "DELIVERED",
        "img"    => "https://images.unsplash.com/photo-1591047139829-d91aecb6caea?w=200&q=80",
        "img2"   => "https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=200&q=80",
    ],
    [
        "id"     => "ZL-86409",
        "date"   => "Sep 28, 2023",
        "items"  => 1,
        "total"  => 45.00,
        "status" => "DELIVERED",
        "img"    => "https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=200&q=80",
        "img2"   => null,
    ],
];

$picked_for_you = [
    ["tag" => "NEW ARRIVAL",  "name" => "Structured Leather Tote",     "price" => 320.00, "img" => "https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=400&q=80"],
    ["tag" => "BESTSELLER",   "name" => "Midnight Silk Pumps",          "price" => 185.00, "img" => "https://images.unsplash.com/photo-1543163521-1bf539c55dd2?w=400&q=80"],
    ["tag" => "TRENDING",     "name" => "Raw Indigo Straight Fit",      "price" => 120.00, "img" => "https://images.unsplash.com/photo-1542272604-787c3835535d?w=400&q=80"],
    ["tag" => "ESSENTIAL",    "name" => "Premium Heavyweight Hoodie",   "price" => 95.00,  "img" => "https://images.unsplash.com/photo-1556821840-3a63f15732ce?w=400&q=80"],
];

$nav_links = ["WOMEN", "MEN", "KIDS", "LUXURY", "BEAUTY"];

$sidebar_account = [
    ["icon" => "person",   "label" => "My Details",        "active" => true],
    ["icon" => "bag",      "label" => "Orders & Returns",  "active" => false],
    ["icon" => "card",     "label" => "Payment Methods",   "active" => false],
    ["icon" => "voucher",  "label" => "Vouchers",          "active" => false],
];

$sidebar_prefs = [
    ["icon" => "bell",     "label" => "Notifications",     "active" => false],
    ["icon" => "gear",     "label" => "Settings",          "active" => false],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>ZALORA — My Profile</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;1,300&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --black: #0a0a0a; --white: #fafafa; --grey: #888;
            --light-grey: #e8e8e8; --accent: #c8a96e;
            --green: #27ae60; --red: #e74c3c;
            --font-display: 'Cormorant Garamond', serif;
            --font-body: 'Montserrat', sans-serif;
        }
        body { font-family: var(--font-body); background: #f4f4f2; color: var(--black); font-size: 13px; letter-spacing: 0.04em; }

        /* ── NAV ── */
        nav {
            position: sticky; top: 0; z-index: 100;
            background: rgba(250,250,250,0.97); backdrop-filter: blur(8px);
            border-bottom: 1px solid var(--light-grey);
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 2.5rem; height: 56px;
        }
        .nav-logo { font-weight: 700; font-size: 1.15rem; letter-spacing: 0.18em; color: var(--black); text-decoration: none; }
        .nav-links { display: flex; gap: 2rem; list-style: none; }
        .nav-links a { text-decoration: none; color: var(--black); font-size: 11px; font-weight: 600; letter-spacing: 0.12em; padding-bottom: 3px; border-bottom: 2px solid transparent; transition: border-color 0.2s; }
        .nav-links a:hover { border-color: var(--black); }
        .nav-actions { display: flex; gap: 1rem; align-items: center; }
        .nav-actions a { color: var(--black); display: flex; align-items: center; position: relative; text-decoration: none; transition: opacity 0.2s; }
        .nav-actions a:hover { opacity: 0.5; }
        .nav-divider { width: 1px; height: 20px; background: var(--light-grey); margin: 0 0.4rem; }
        .nav-profile { display: flex; align-items: center; gap: 6px; font-size: 10px; font-weight: 700; letter-spacing: 0.14em; text-decoration: none; color: var(--black); }
        .nav-profile:hover { opacity: 0.6; }
        .cart-badge { position: absolute; top: -6px; right: -8px; background: var(--black); color: var(--white); font-size: 8px; font-weight: 700; width: 16px; height: 16px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }

        /* ── PAGE LAYOUT ── */
        .page-wrapper { max-width: 1100px; margin: 0 auto; padding: 2.5rem 2rem 5rem; display: grid; grid-template-columns: 200px 1fr; gap: 2.5rem; align-items: start; }

        /* ── SIDEBAR ── */
        .sidebar { background: var(--white); padding: 1.8rem 1.5rem; }
        .sidebar-section-label { font-size: 9px; font-weight: 700; letter-spacing: 0.24em; text-transform: uppercase; color: var(--grey); margin-bottom: 1rem; }
        .sidebar-nav { list-style: none; margin-bottom: 2rem; }
        .sidebar-nav li { margin-bottom: 0.2rem; }
        .sidebar-nav a {
            display: flex; align-items: center; gap: 10px;
            text-decoration: none; font-size: 11px; font-weight: 600;
            letter-spacing: 0.1em; text-transform: uppercase;
            color: var(--grey); padding: 9px 10px;
            transition: color 0.2s, background 0.2s;
            border-radius: 2px;
        }
        .sidebar-nav a:hover { color: var(--black); background: #f0f0ee; }
        .sidebar-nav a.active { color: var(--black); }
        .sidebar-nav .icon { font-size: 14px; width: 18px; text-align: center; }
        .sidebar-divider { border: none; border-top: 1px solid var(--light-grey); margin: 1rem 0 1.5rem; }
        .signout-btn {
            display: flex; align-items: center; gap: 10px;
            background: none; border: none; cursor: pointer;
            font-family: var(--font-body); font-size: 11px; font-weight: 700;
            letter-spacing: 0.1em; text-transform: uppercase;
            color: var(--red); padding: 9px 10px;
            transition: opacity 0.2s; width: 100%;
        }
        .signout-btn:hover { opacity: 0.7; }

        /* ── MAIN CONTENT ── */
        .main-content { display: flex; flex-direction: column; gap: 1.5rem; }

        /* ── GREETING ROW ── */
        .greeting-row { display: grid; grid-template-columns: 1fr auto; gap: 1.5rem; align-items: start; }
        .greeting-card { background: var(--white); padding: 2rem 2rem 1.8rem; }
        .greeting-card h2 { font-size: clamp(1.1rem, 2vw, 1.4rem); font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; margin-bottom: 0.4rem; }
        .greeting-card p { font-size: 11px; color: var(--grey); }

        /* Membership card */
        .membership-card {
            background: var(--black); color: var(--white);
            padding: 1.8rem 2rem; min-width: 200px; text-align: center;
        }
        .mem-label { font-size: 9px; font-weight: 600; letter-spacing: 0.28em; text-transform: uppercase; color: rgba(255,255,255,0.5); margin-bottom: 0.5rem; }
        .mem-tier { font-size: 1.5rem; font-weight: 700; letter-spacing: 0.2em; color: var(--accent); margin-bottom: 0.8rem; }
        .mem-points { font-size: 10px; color: rgba(255,255,255,0.5); letter-spacing: 0.1em; }
        .mem-points span { color: var(--white); font-weight: 600; }

        /* ── LATEST ORDER + WALLET ROW ── */
        .order-wallet-row { display: grid; grid-template-columns: 1fr 220px; gap: 1.5rem; }

        /* Latest order */
        .latest-order-card { background: var(--white); padding: 1.8rem; }
        .card-tag { font-size: 9px; font-weight: 700; letter-spacing: 0.22em; text-transform: uppercase; color: var(--grey); margin-bottom: 1rem; }
        .latest-order-inner { display: flex; gap: 1.5rem; align-items: flex-start; }
        .latest-order-img { width: 90px; height: 110px; object-fit: cover; flex-shrink: 0; background: #eee; }
        .latest-order-info h3 { font-size: 13px; font-weight: 600; letter-spacing: 0.06em; margin-bottom: 0.5rem; }
        .order-status { font-size: 11px; color: var(--grey); margin-bottom: 1.2rem; }
        .order-status strong { color: var(--black); }
        .btn-track {
            background: var(--black); color: var(--white); border: none;
            font-family: var(--font-body); font-size: 10px; font-weight: 700;
            letter-spacing: 0.18em; text-transform: uppercase;
            padding: 11px 20px; cursor: pointer; transition: background 0.2s;
        }
        .btn-track:hover { background: #333; }

        /* Wallet card */
        .wallet-card { background: var(--white); padding: 1.8rem; display: flex; flex-direction: column; justify-content: space-between; }
        .wallet-label { font-size: 9px; font-weight: 700; letter-spacing: 0.22em; text-transform: uppercase; color: var(--grey); margin-bottom: 0.8rem; }
        .wallet-amount { font-size: 1.6rem; font-weight: 700; margin-bottom: 1.5rem; }
        .btn-topup {
            width: 100%; background: none; border: 1px solid var(--black);
            font-family: var(--font-body); font-size: 10px; font-weight: 700;
            letter-spacing: 0.18em; text-transform: uppercase;
            padding: 12px; cursor: pointer; transition: background 0.2s, color 0.2s;
            color: var(--black);
        }
        .btn-topup:hover { background: var(--black); color: var(--white); }

        /* ── RECENT ORDERS ── */
        .recent-orders-card { background: var(--white); padding: 1.8rem; }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .card-header h3 { font-size: 11px; font-weight: 700; letter-spacing: 0.2em; text-transform: uppercase; }
        .view-all-link { font-size: 10px; font-weight: 600; letter-spacing: 0.14em; text-transform: uppercase; color: var(--black); text-decoration: underline; text-underline-offset: 3px; transition: color 0.2s; }
        .view-all-link:hover { color: var(--grey); }

        .order-row {
            display: flex; align-items: center; gap: 1.2rem;
            padding: 1.2rem 0; border-bottom: 1px solid var(--light-grey);
        }
        .order-row:last-child { border-bottom: none; padding-bottom: 0; }
        .order-imgs { display: flex; gap: 4px; flex-shrink: 0; }
        .order-imgs img { width: 52px; height: 64px; object-fit: cover; background: #eee; }
        .order-meta { flex: 1; }
        .order-id { font-size: 10px; font-weight: 700; letter-spacing: 0.16em; text-transform: uppercase; color: var(--grey); margin-bottom: 4px; }
        .order-date { font-size: 11px; color: var(--grey); }
        .order-right { text-align: right; display: flex; flex-direction: column; align-items: flex-end; gap: 8px; }
        .order-total { font-size: 14px; font-weight: 700; }
        .status-badge { font-size: 9px; font-weight: 700; letter-spacing: 0.16em; text-transform: uppercase; color: var(--green); }
        .btn-details {
            background: none; border: 1px solid var(--light-grey);
            font-family: var(--font-body); font-size: 9px; font-weight: 700;
            letter-spacing: 0.14em; text-transform: uppercase;
            padding: 7px 14px; cursor: pointer; color: var(--black);
            transition: border-color 0.2s, background 0.2s;
        }
        .btn-details:hover { border-color: var(--black); background: #f5f5f5; }

        /* ── PICKED FOR YOU ── */
        .picked-card { background: var(--white); padding: 1.8rem; }
        .picked-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-top: 1.2rem; }
        .picked-item { cursor: pointer; }
        .picked-img-wrap { overflow: hidden; margin-bottom: 0.7rem; }
        .picked-img-wrap img { width: 100%; aspect-ratio: 1/1; object-fit: cover; display: block; transition: transform 0.4s ease; }
        .picked-item:hover .picked-img-wrap img { transform: scale(1.05); }
        .picked-tag { font-size: 8px; font-weight: 700; letter-spacing: 0.2em; text-transform: uppercase; color: var(--grey); margin-bottom: 4px; }
        .picked-name { font-size: 11px; font-weight: 600; margin-bottom: 4px; line-height: 1.35; }
        .picked-price { font-size: 11px; color: var(--grey); font-weight: 500; }

        /* ── FOOTER ── */
        footer { background: var(--white); border-top: 1px solid var(--light-grey); padding: 1.8rem 2.5rem; }
        .footer-inner { max-width: 1100px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; }
        .footer-logo { font-weight: 700; font-size: 1rem; letter-spacing: 0.18em; color: var(--black); }
        .footer-links { display: flex; gap: 1.8rem; flex-wrap: wrap; }
        .footer-links a { text-decoration: none; color: var(--grey); font-size: 10px; letter-spacing: 0.1em; text-transform: uppercase; transition: color 0.2s; }
        .footer-links a:hover { color: var(--black); }
        .footer-copy { font-size: 9px; color: var(--grey); letter-spacing: 0.08em; text-transform: uppercase; white-space: nowrap; }

        /* ── RESPONSIVE ── */
        @media (max-width: 900px) {
            .page-wrapper { grid-template-columns: 1fr; }
            .greeting-row { grid-template-columns: 1fr; }
            .order-wallet-row { grid-template-columns: 1fr; }
            .picked-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 600px) {
            .nav-links { display: none; }
            .picked-grid { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>

<!-- ── NAV ── -->
<nav>
    <a href="index.php" class="nav-logo">ZALORA</a>
    <ul class="nav-links">
        <?php foreach ($nav_links as $link): ?>
            <li><a href="#"><?= htmlspecialchars($link) ?></a></li>
        <?php endforeach; ?>
    </ul>
    <div class="nav-actions">
        <a href="#" title="Search">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
        </a>
        <a href="#" title="Wishlist">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
        </a>
        <a href="cart.php" title="Cart">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
            <span class="cart-badge">3</span>
        </a>
        <div class="nav-divider"></div>
        <a href="profile.php" class="nav-profile">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            MY PROFILE
        </a>
    </div>
</nav>

<!-- ── PAGE ── -->
<div class="page-wrapper">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <p class="sidebar-section-label">Account</p>
        <ul class="sidebar-nav">
            <?php foreach ($sidebar_account as $item): ?>
            <li>
                <a href="#" class="<?= $item['active'] ? 'active' : '' ?>">
                    <span class="icon">
                        <?php if ($item['icon'] === 'person'): ?>👤
                        <?php elseif ($item['icon'] === 'bag'): ?>🛍
                        <?php elseif ($item['icon'] === 'card'): ?>💳
                        <?php else: ?>🎟
                        <?php endif; ?>
                    </span>
                    <?= htmlspecialchars($item['label']) ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>

        <p class="sidebar-section-label">Preferences</p>
        <ul class="sidebar-nav">
            <?php foreach ($sidebar_prefs as $item): ?>
            <li>
                <a href="#">
                    <span class="icon"><?= $item['icon'] === 'bell' ? '🔔' : '⚙️' ?></span>
                    <?= htmlspecialchars($item['label']) ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>

        <hr class="sidebar-divider"/>
        <button class="signout-btn" onclick="window.location.href='auth/login.php'">
            <span>↩</span> Sign Out
        </button>
    </aside>

    <!-- MAIN -->
    <div class="main-content">

        <!-- GREETING ROW -->
        <div class="greeting-row">
            <div class="greeting-card">
                <h2>Hello, <?= htmlspecialchars($user['name']) ?></h2>
                <p>Manage your profile, track orders and check your membership status.</p>
            </div>
            <div class="membership-card">
                <p class="mem-label">Membership Status</p>
                <p class="mem-tier"><?= htmlspecialchars($user['membership']) ?></p>
                <p class="mem-points"><span><?= number_format($user['points_to_next']) ?></span> POINTS TO <?= htmlspecialchars($user['next_tier']) ?></p>
            </div>
        </div>

        <!-- LATEST ORDER + WALLET -->
        <div class="order-wallet-row">
            <div class="latest-order-card">
                <p class="card-tag">Latest Order • #<?= htmlspecialchars($latest_order['id']) ?></p>
                <div class="latest-order-inner">
                    <img class="latest-order-img" src="<?= htmlspecialchars($latest_order['img']) ?>" alt="<?= htmlspecialchars($latest_order['name']) ?>"/>
                    <div class="latest-order-info">
                        <h3><?= htmlspecialchars($latest_order['name']) ?></h3>
                        <p class="order-status">Status: <strong><?= htmlspecialchars($latest_order['status']) ?></strong> — <?= htmlspecialchars($latest_order['arrives']) ?></p>
                        <button class="btn-track">Track Package</button>
                    </div>
                </div>
            </div>

            <div class="wallet-card">
                <div>
                    <p class="wallet-label">Zalora Wallet</p>
                    <p class="wallet-amount">$<?= number_format($user['wallet'], 2) ?></p>
                </div>
                <button class="btn-topup">Top Up</button>
            </div>
        </div>

        <!-- RECENT ORDERS -->
        <div class="recent-orders-card">
            <div class="card-header">
                <h3>Recent Orders</h3>
                <a href="#" class="view-all-link">View All Orders</a>
            </div>

            <?php foreach ($recent_orders as $order): ?>
            <div class="order-row">
                <div class="order-imgs">
                    <img src="<?= htmlspecialchars($order['img']) ?>" alt="Order item"/>
                    <?php if ($order['img2']): ?>
                        <img src="<?= htmlspecialchars($order['img2']) ?>" alt="Order item 2"/>
                    <?php endif; ?>
                </div>
                <div class="order-meta">
                    <p class="order-id">Order #<?= htmlspecialchars($order['id']) ?></p>
                    <p class="order-date">Placed on <?= htmlspecialchars($order['date']) ?> • <?= $order['items'] ?> <?= $order['items'] === 1 ? 'Item' : 'Items' ?></p>
                </div>
                <div class="order-right">
                    <p class="order-total">$<?= number_format($order['total'], 2) ?></p>
                    <p class="status-badge"><?= htmlspecialchars($order['status']) ?></p>
                    <button class="btn-details">Details</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- PICKED FOR YOU -->
        <div class="picked-card">
            <div class="card-header">
                <h3>Picked for You</h3>
            </div>
            <div class="picked-grid">
                <?php foreach ($picked_for_you as $item): ?>
                <div class="picked-item">
                    <div class="picked-img-wrap">
                        <img src="<?= htmlspecialchars($item['img']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" loading="lazy"/>
                    </div>
                    <p class="picked-tag"><?= htmlspecialchars($item['tag']) ?></p>
                    <p class="picked-name"><?= htmlspecialchars($item['name']) ?></p>
                    <p class="picked-price">$<?= number_format($item['price'], 2) ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div><!-- end main-content -->
</div><!-- end page-wrapper -->

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
        <span class="footer-copy">© <?= date('Y') ?> Zalora. All Rights Reserved.</span>
    </div>
</footer>

</body>
</html>