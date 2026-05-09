<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$customer_id = $_SESSION['user_id'];

// Handle Top Up (Dummy Bank)
if (isset($_POST['topup'])) {
    $conn->query("UPDATE CUSTOMER SET Cust_Balance = Cust_Balance + 500.00 WHERE Cust_Id = $customer_id");
    header("Location: profile.php?topup=success");
    exit;
}

// 1. Fetch REAL Customer Data
$stmt = $conn->prepare("SELECT * FROM CUSTOMER WHERE Cust_Id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$cust_data = $stmt->get_result()->fetch_assoc();

// Calculate Membership Tier based on total spending
$spend_query = "SELECT SUM(Order_TotalAmnt) as total_spent FROM ORDERS WHERE Cust_Id = ? AND Order_Status != 'CANCELLED'";
$stmt_s = $conn->prepare($spend_query);
$stmt_s->bind_param("i", $customer_id);
$stmt_s->execute();
$total_spent = $stmt_s->get_result()->fetch_assoc()['total_spent'] ?? 0;

$tier = "BRONZE";
$next_tier = "SILVER";
$points = (int)$total_spent;
$points_to_next = 500 - $points;

if ($points >= 2000) {
    $tier = "PLATINUM";
    $next_tier = "ELITE";
    $points_to_next = 5000 - $points;
} elseif ($points >= 1000) {
    $tier = "GOLD";
    $next_tier = "PLATINUM";
    $points_to_next = 2000 - $points;
} elseif ($points >= 500) {
    $tier = "SILVER";
    $next_tier = "GOLD";
    $points_to_next = 1000 - $points;
}

if ($points_to_next < 0) $points_to_next = 0;

$user = [
    "name"            => ($cust_data['Cust_Firstname'] ?? '') . ' ' . ($cust_data['Cust_Lastname'] ?? ''),
    "email"           => $cust_data['Cust_Email'] ?? '',
    "membership"      => $tier,
    "points_to_next"  => $points_to_next,
    "next_tier"       => $next_tier,
    "wallet"          => $cust_data['Cust_Balance'] ?? 0.00,
];

// Handle Address Update
if (isset($_POST['update_address'])) {
    $name = $_POST['rcpnt_name'];
    $number = $_POST['rcpnt_number'];
    $unit = $_POST['unit_no'];
    $street = $_POST['street'];
    $brgy = $_POST['barangay'];
    $city = $_POST['city'];
    $province = $_POST['province'];
    $zip = $_POST['zip'];
    
    // Check if address exists
    $addr_check = $conn->query("SELECT Addrs_id FROM ADDRESS WHERE Cust_Id = $customer_id LIMIT 1");
    if ($addr_check->num_rows > 0) {
        $addr_id = $addr_check->fetch_assoc()['Addrs_id'];
        $stmt_ua = $conn->prepare("UPDATE ADDRESS SET Addrs_RcpntName = ?, Addrs_Number = ?, Addrs_UnitNo = ?, Addrs_Street = ?, Addrs_Barangay = ?, Addrs_City = ?, Addrs_Province = ?, Addrs_ZipCode = ? WHERE Addrs_id = ?");
        $stmt_ua->bind_param("ssssssssi", $name, $number, $unit, $street, $brgy, $city, $province, $zip, $addr_id);
    } else {
        // Manually get next ID
        $res_id = $conn->query("SELECT MAX(Addrs_id) as max_id FROM ADDRESS");
        $next_id = ($res_id->fetch_assoc()['max_id'] ?? 0) + 1;

        $stmt_ua = $conn->prepare("INSERT INTO ADDRESS (Addrs_id, Cust_Id, Addrs_RcpntName, Addrs_Number, Addrs_UnitNo, Addrs_Street, Addrs_Barangay, Addrs_City, Addrs_Province, Addrs_ZipCode, Addrs_IsDefault, Addrs_CreatedAt) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())");
        $stmt_ua->bind_param("iissssssss", $next_id, $customer_id, $name, $number, $unit, $street, $brgy, $city, $province, $zip);
    }
    $stmt_ua->execute();
    header("Location: profile.php?address=updated");
    exit;
}

// Fetch Address
$addr_query = "SELECT * FROM ADDRESS WHERE Cust_Id = ? AND Addrs_IsDefault = 1 LIMIT 1";
$stmt_ad = $conn->prepare($addr_query);
$stmt_ad->bind_param("i", $customer_id);
$stmt_ad->execute();
$address = $stmt_ad->get_result()->fetch_assoc();

// 2. Fetch REAL Latest Order
$query_latest = "SELECT o.Order_Id, o.Order_Status, o.Order_PlacedAt, oi.OdItm_Quantity, p.Prod_Name,
                 (SELECT PImg_ImgUrl FROM PRODUCT_IMAGE WHERE Prod_Id = p.Prod_Id AND PImg_IsPrimary = 1 LIMIT 1) as img
                 FROM ORDERS o
                 JOIN ORDER_ITEM oi ON o.Order_Id = oi.Order_Id
                 JOIN PRODUCT_VARIANT pv ON oi.PVar_Id = pv.PVar_Id
                 JOIN PRODUCT p ON pv.Prod_Id = p.Prod_Id
                 WHERE o.Cust_Id = ?
                 ORDER BY o.Order_PlacedAt DESC LIMIT 1";
$stmt_l = $conn->prepare($query_latest);
$stmt_l->bind_param("i", $customer_id);
$stmt_l->execute();
$latest_order_data = $stmt_l->get_result()->fetch_assoc();

$latest_order = null;
if ($latest_order_data) {
    $img = $latest_order_data['img'] ?? 'https://via.placeholder.com/300';
    if ($img && strpos($img, 'http') === false) $img = '../' . $img;

    $latest_order = [
        "id"      => $latest_order_data['Order_Id'],
        "name"    => $latest_order_data['Prod_Name'],
        "status"  => $latest_order_data['Order_Status'],
        "arrives" => date('F j, Y', strtotime($latest_order_data['Order_PlacedAt'] . ' + 3 days')),
        "img"     => $img,
    ];
}

// 3. Fetch REAL Recent Orders (last 3)
$query_recent = "SELECT o.Order_Id, o.Order_PlacedAt, o.Order_Status, o.Order_TotalAmnt,
                 (SELECT COUNT(*) FROM ORDER_ITEM WHERE Order_Id = o.Order_Id) as item_count,
                 (SELECT pi.PImg_ImgUrl FROM ORDER_ITEM oi 
                  JOIN PRODUCT_VARIANT pv ON oi.PVar_Id = pv.PVar_Id 
                  JOIN PRODUCT_IMAGE pi ON pv.Prod_Id = pi.Prod_Id 
                  WHERE oi.Order_Id = o.Order_Id AND pi.PImg_IsPrimary = 1 LIMIT 1) as img
                 FROM ORDERS o
                 WHERE o.Cust_Id = ?
                 ORDER BY o.Order_PlacedAt DESC LIMIT 3";
$stmt_r = $conn->prepare($query_recent);
$stmt_r->bind_param("i", $customer_id);
$stmt_r->execute();
$recent_orders_res = $stmt_r->get_result();

$recent_orders = [];
while($row = $recent_orders_res->fetch_assoc()) {
    $img = $row['img'] ?? 'https://via.placeholder.com/200';
    if ($img && strpos($img, 'http') === false) $img = '../' . $img;

    $recent_orders[] = [
        "id"     => $row['Order_Id'],
        "date"   => date('M j, Y', strtotime($row['Order_PlacedAt'])),
        "items"  => $row['item_count'],
        "total"  => $row['Order_TotalAmnt'],
        "status" => strtoupper($row['Order_Status']),
        "img"    => $img,
        "img2"   => null,
    ];
}

// 4. Fetch REAL Picked for You (Random 4 products)
$query_picked = "SELECT p.Prod_Id, p.Prod_Name, p.Prod_BasePrice, 
                  (SELECT PImg_ImgUrl FROM PRODUCT_IMAGE WHERE Prod_Id = p.Prod_Id AND PImg_IsPrimary = 1 LIMIT 1) as img,
                  c.Ctgry_Name as tag
                  FROM PRODUCT p
                  JOIN CATEGORY c ON p.Ctgry_Id = c.Ctgry_Id
                  WHERE p.Prod_IsActive = 1
                  ORDER BY RAND() LIMIT 4";
$picked_res = $conn->query($query_picked);
$picked_for_you = [];
while($row = $picked_res->fetch_assoc()) {
    $img = $row['img'] ?? 'https://via.placeholder.com/400';
    if ($img && strpos($img, 'http') === false) $img = '../' . $img;

    $picked_for_you[] = [
        "id"    => $row['Prod_Id'],
        "tag"   => strtoupper($row['tag']),
        "name"  => $row['Prod_Name'],
        "price" => $row['Prod_BasePrice'],
        "img"   => $img
    ];
}

$nav_links = ["WOMEN", "MEN", "KIDS", "LUXURY", "BEAUTY"];

$sidebar_account = [
    ["icon" => "person",   "label" => "My Details",        "link" => "#account-settings", "active" => true],
    ["icon" => "home",     "label" => "Shipping Address",  "link" => "#shipping-address", "active" => false],
    ["icon" => "bag",      "label" => "Orders & Returns",  "link" => "#order-history", "active" => false],
    ["icon" => "heart",    "label" => "Wishlist",          "link" => "wishlist.php", "active" => false],
    ["icon" => "voucher",  "label" => "Vouchers",          "link" => "#", "active" => false],
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
    <style>
        html { scroll-behavior: smooth; }
    </style>
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

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <p class="sidebar-section-label">Account</p>
        <ul class="sidebar-nav">
            <?php foreach ($sidebar_account as $item): ?>
            <li>
                <a href="<?= $item['link'] ?>" class="<?= $item['active'] ? 'active' : '' ?>">
                    <span class="icon">
                        <?php if ($item['icon'] === 'person'): ?>👤
                        <?php elseif ($item['icon'] === 'home'): ?>🏠
                        <?php elseif ($item['icon'] === 'bag'): ?>🛍
                        <?php elseif ($item['icon'] === 'heart'): ?>❤️
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
            <?php if ($latest_order): ?>
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
            <?php else: ?>
                <div class="latest-order-card" style="display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; padding: 2rem;">
                    <div style="font-size: 30px; margin-bottom: 1rem;">📦</div>
                    <h3 style="font-family:'Montserrat', sans-serif; font-size:14px; font-weight:700;">No Recent Orders</h3>
                    <p style="font-size:11px; color:#666; margin: 0.5rem 0 1.5rem;">Ready to find your first favorite item?</p>
                    <a href="products.php" style="background:#000; color:#fff; text-decoration:none; padding: 10px 25px; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:0.1em;">Start Shopping</a>
                </div>
            <?php endif; ?>

            <div class="wallet-card">
                <div>
                    <p class="wallet-label">Zalora Wallet</p>
                    <p class="wallet-amount">$<?= number_format($user['wallet'], 2) ?></p>
                </div>
                <form method="POST">
                    <button type="submit" name="topup" class="btn-topup">Top Up</button>
                </form>
            </div>
            
            <?php if (isset($_GET['topup']) && $_GET['topup'] === 'success'): ?>
                <script>alert('Successfully topped up $500.00 to your Zalora Wallet!');</script>
            <?php endif; ?>
            <?php if (isset($_GET['order']) && $_GET['order'] === 'success'): ?>
                <script>alert('Thank you! Your order has been placed successfully and is being processed.');</script>
            <?php endif; ?>
            <?php if (isset($_GET['address']) && $_GET['address'] === 'updated'): ?>
                <script>alert('Shipping address updated successfully!');</script>
            <?php endif; ?>
        </div>

        <!-- RECENT ORDERS -->
        <div class="recent-orders-card" id="order-history">
            <div class="card-header">
                <h3>Recent Orders</h3>
                <?php if (count($recent_orders) > 0): ?>
                    <a href="#" class="view-all-link">View All Orders</a>
                <?php endif; ?>
            </div>

            <?php if (count($recent_orders) > 0): ?>
                <?php foreach ($recent_orders as $order): ?>
                <div class="order-row">
                    <div class="order-imgs">
                        <img src="<?= htmlspecialchars($order['img']) ?>" alt="Order item"/>
                    </div>
                    <div class="order-meta">
                        <p class="order-id">Order #<?= htmlspecialchars($order['id']) ?></p>
                        <p class="order-date">Placed on <?= htmlspecialchars($order['date']) ?> • <?= $order['items'] ?> <?= $order['items'] === 1 ? 'Item' : 'Items' ?></p>
                    </div>
                    <div class="order-right">
                        <p class="order-total">$<?= number_format($order['total'], 2) ?></p>
                        <p class="status-badge"><?= htmlspecialchars($order['status']) ?></p>
                        <a href="order_details.php?id=<?= $order['id'] ?>" class="btn-details" style="text-decoration:none; display:inline-block; text-align:center;">Details</a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align:center; padding: 3rem; font-size: 11px; color: #999;">Your order history is empty.</p>
            <?php endif; ?>
        </div>

        <!-- PICKED FOR YOU -->
        <div class="picked-card">
            <div class="card-header">
                <h3>Picked for You</h3>
            </div>
            <div class="picked-grid">
                <?php foreach ($picked_for_you as $item): ?>
                <a href="product.php?id=<?= $item['id'] ?>" class="picked-item" style="text-decoration: none; color: inherit;">
                    <div class="picked-img-wrap">
                        <img src="<?= htmlspecialchars($item['img']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" loading="lazy"/>
                    </div>
                    <p class="picked-tag"><?= htmlspecialchars($item['tag']) ?></p>
                    <p class="picked-name"><?= htmlspecialchars($item['name']) ?></p>
                    <p class="picked-price">$<?= number_format($item['price'], 2) ?></p>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ACCOUNT SETTINGS -->
        <div class="recent-orders-card" id="account-settings" style="margin-top: 2rem;">
            <div class="card-header">
                <h3>Account Settings</h3>
            </div>
            <form method="POST" style="padding: 1.5rem; display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label style="display:block; font-size:10px; font-weight:700; color:#999; text-transform:uppercase; margin-bottom:8px;">First Name</label>
                    <input type="text" name="first_name" value="<?= htmlspecialchars($cust_data['Cust_Firstname'] ?? '') ?>" style="width:100%; padding:12px; border:1px solid #eee; font-family:inherit;">
                </div>
                <div class="form-group">
                    <label style="display:block; font-size:10px; font-weight:700; color:#999; text-transform:uppercase; margin-bottom:8px;">Last Name</label>
                    <input type="text" name="last_name" value="<?= htmlspecialchars($cust_data['Cust_Lastname'] ?? '') ?>" style="width:100%; padding:12px; border:1px solid #eee; font-family:inherit;">
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label style="display:block; font-size:10px; font-weight:700; color:#999; text-transform:uppercase; margin-bottom:8px;">Email Address</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($cust_data['Cust_Email'] ?? '') ?>" style="width:100%; padding:12px; border:1px solid #eee; font-family:inherit;">
                </div>
                <div style="grid-column: span 2; display:flex; justify-content:flex-end;">
                    <button type="submit" name="update_profile" style="background:#000; color:#fff; border:none; padding:12px 30px; font-size:11px; font-weight:700; cursor:pointer; text-transform:uppercase; letter-spacing:0.1em;">Save Changes</button>
                </div>
            </form>
        </div>
        
        <!-- SHIPPING ADDRESS -->
        <div class="recent-orders-card" id="shipping-address" style="margin-top: 2rem;">
            <div class="card-header">
                <h3>Shipping Address</h3>
            </div>
            <form method="POST" style="padding: 1.5rem; display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem;">
                <div class="form-group" style="grid-column: span 2;">
                    <label style="display:block; font-size:10px; font-weight:700; color:#999; text-transform:uppercase; margin-bottom:8px;">Recipient Full Name</label>
                    <input type="text" name="rcpnt_name" value="<?= htmlspecialchars($address['Addrs_RcpntName'] ?? $user['name']) ?>" style="width:100%; padding:12px; border:1px solid #eee; font-family:inherit;" required>
                </div>
                <div class="form-group">
                    <label style="display:block; font-size:10px; font-weight:700; color:#999; text-transform:uppercase; margin-bottom:8px;">Contact Number</label>
                    <input type="text" name="rcpnt_number" value="<?= htmlspecialchars($address['Addrs_Number'] ?? '') ?>" placeholder="e.g. 0912 345 6789" style="width:100%; padding:12px; border:1px solid #eee; font-family:inherit;" required>
                </div>
                <div class="form-group">
                    <label style="display:block; font-size:10px; font-weight:700; color:#999; text-transform:uppercase; margin-bottom:8px;">Unit / Building No.</label>
                    <input type="text" name="unit_no" value="<?= htmlspecialchars($address['Addrs_UnitNo'] ?? '') ?>" placeholder="e.g. Unit 1204, Tower 1" style="width:100%; padding:12px; border:1px solid #eee; font-family:inherit;">
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label style="display:block; font-size:10px; font-weight:700; color:#999; text-transform:uppercase; margin-bottom:8px;">Street Address</label>
                    <input type="text" name="street" value="<?= htmlspecialchars($address['Addrs_Street'] ?? '') ?>" placeholder="e.g. 123 Fashion St." style="width:100%; padding:12px; border:1px solid #eee; font-family:inherit;" required>
                </div>
                <div class="form-group">
                    <label style="display:block; font-size:10px; font-weight:700; color:#999; text-transform:uppercase; margin-bottom:8px;">Barangay</label>
                    <input type="text" name="barangay" value="<?= htmlspecialchars($address['Addrs_Barangay'] ?? '') ?>" style="width:100%; padding:12px; border:1px solid #eee; font-family:inherit;" required>
                </div>
                <div class="form-group">
                    <label style="display:block; font-size:10px; font-weight:700; color:#999; text-transform:uppercase; margin-bottom:8px;">City</label>
                    <input type="text" name="city" value="<?= htmlspecialchars($address['Addrs_City'] ?? '') ?>" style="width:100%; padding:12px; border:1px solid #eee; font-family:inherit;" required>
                </div>
                <div class="form-group">
                    <label style="display:block; font-size:10px; font-weight:700; color:#999; text-transform:uppercase; margin-bottom:8px;">Province</label>
                    <input type="text" name="province" value="<?= htmlspecialchars($address['Addrs_Province'] ?? '') ?>" style="width:100%; padding:12px; border:1px solid #eee; font-family:inherit;" required>
                </div>
                <div class="form-group">
                    <label style="display:block; font-size:10px; font-weight:700; color:#999; text-transform:uppercase; margin-bottom:8px;">Zip Code</label>
                    <input type="text" name="zip" value="<?= htmlspecialchars($address['Addrs_ZipCode'] ?? '') ?>" style="width:100%; padding:12px; border:1px solid #eee; font-family:inherit;" required>
                </div>
                <div style="grid-column: span 3; display:flex; justify-content:flex-end; margin-top: 1rem;">
                    <button type="submit" name="update_address" style="background:#000; color:#fff; border:none; padding:12px 30px; font-size:11px; font-weight:700; cursor:pointer; text-transform:uppercase; letter-spacing:0.1em;">Save Address</button>
                </div>
            </form>
        </div>

    </div><!-- end main-content -->
</div><!-- end page-wrapper -->

<?php
if (isset($_POST['update_profile'])) {
    $fn = $_POST['first_name'];
    $ln = $_POST['last_name'];
    $em = $_POST['email'];
    
    $upd = $conn->prepare("UPDATE CUSTOMER SET Cust_Firstname = ?, Cust_Lastname = ?, Cust_Email = ? WHERE Cust_Id = ?");
    $upd->bind_param("sssi", $fn, $ln, $em, $customer_id);
    if ($upd->execute()) {
        $_SESSION['user_name'] = $fn; // Update session name
        echo "<script>alert('Profile updated successfully!'); window.location.href='profile.php';</script>";
    }
}
?>

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