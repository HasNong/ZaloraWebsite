<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user = [
    "name"            => $_SESSION['user_name'] ?? "Alexander",
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
    <link rel="stylesheet" href="../assets/css/global.css"/>
    <link rel="stylesheet" href="../assets/css/profile.css"/>
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
        <button class="signout-btn" onclick="window.location.href='../auth/logout.php'">
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