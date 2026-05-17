<?php
session_start();
require_once '../config/db.php';
include 'nav_counts.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$customer_id = $_SESSION['user_id'];

// 1. Fetch REAL Customer Data
$stmt = $conn->prepare("SELECT * FROM CUSTOMER WHERE Cust_Id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$cust_data = $stmt->get_result()->fetch_assoc();

$user = [
    "name"            => ($cust_data['Cust_Firstname'] ?? '') . ' ' . ($cust_data['Cust_Lastname'] ?? ''),
    "email"           => $cust_data['Cust_Email'] ?? '',
];

// Fetch Address
$addr_query = "SELECT * FROM ADDRESS WHERE Cust_Id = ? AND Addrs_IsDefault = 1 LIMIT 1";
$stmt_ad = $conn->prepare($addr_query);
$stmt_ad->bind_param("i", $customer_id);
$stmt_ad->execute();
$address = $stmt_ad->get_result()->fetch_assoc();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>ZALORA — My Profile</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&family=Cormorant+Garamond:ital,wght@1,600&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="../assets/css/global.css?v=<?= time() ?>"/>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Montserrat', sans-serif; background: #fff; color: #000; overflow-x: hidden; display: flex; flex-direction: column; min-height: 100vh; }
        
        /* HEADER STYLES */
        .top-promo-bar { background: #fff; border-bottom: 1px solid #eee; padding: 10px 0; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; }
        .promo-container { max-width: 1400px; margin: 0 auto; display: flex; justify-content: space-around; }
        .promo-item { color: #000; text-decoration: none; display: flex; align-items: center; gap: 8px; }
        header { background: #fff; position: sticky; top: 0; z-index: 1000; border-bottom: 1px solid #eee; }
        .main-header { max-width: 1400px; margin: 0 auto; padding: 15px 20px; display: flex; align-items: center; justify-content: space-between; }
        .logo { font-size: 24px; font-weight: 400; letter-spacing: 0.3em; text-decoration: none; color: #000; }
        .search-bar-wrap { flex: 1; max-width: 500px; margin: 0 40px; position: relative; }
        .search-input { width: 100%; padding: 12px 25px; border: 1px solid #ddd; border-radius: 100px; font-size: 13px; background: #f5f5f5; outline: none; }
        .search-icon-btn { position: absolute; right: 5px; top: 50%; transform: translateY(-50%); background: #000; color: #fff; border: none; width: 34px; height: 34px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; }
        .header-actions { display: flex; gap: 20px; }
        .header-action-item { color: #000; text-decoration: none; font-size: 13px; display: flex; align-items: center; gap: 5px; }
        .nav-bar { border-bottom: 1px solid #eee; }
        .nav-container { max-width: 1400px; margin: 0 auto; display: flex; justify-content: center; gap: 40px; padding: 15px 0; }
        .nav-item { font-size: 11px; font-weight: 700; text-transform: uppercase; text-decoration: none; color: #000; letter-spacing: 0.1em; }
        .badge-count { position: absolute; top: -8px; right: -12px; background: #000; color: #fff; font-size: 9px; padding: 2px 6px; border-radius: 10px; }

        /* LAYOUT */
        .page-wrapper {
            max-width: 1200px;
            margin: 40px auto;
            width: 100%;
            display: flex;
            gap: 40px;
            padding: 0 20px;
            flex: 1;
        }

        /* SIDEBAR */
        .sidebar {
            width: 280px;
            background: #f8f8f8;
            border-radius: 12px;
            padding: 25px 0;
            flex-shrink: 0;
            height: fit-content;
        }
        .sidebar h4 {
            font-size: 12px;
            font-weight: 800;
            margin-bottom: 15px;
            padding: 0 25px;
            text-transform: uppercase;
        }
        .sidebar-menu {
            list-style: none;
        }
        .sidebar-menu li a {
            display: block;
            padding: 15px 25px;
            color: #333;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s;
        }
        .sidebar-menu li a:hover {
            background: #eee;
        }
        .sidebar-menu li a.active {
            background: #444;
            color: #fff;
        }

        /* MAIN CONTENT CARDS */
        .content-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 30px;
        }
        
        .profile-card {
            border: 1px solid #eaeaea;
            border-radius: 16px;
            padding: 30px 40px;
            background: #fff;
            box-shadow: 0 4px 20px rgba(0,0,0,0.02);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .card-title {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 16px;
            font-weight: 600;
        }

        .card-action-link {
            font-size: 13px;
            color: #000;
            text-decoration: underline;
            cursor: pointer;
        }

        /* PERSONAL DETAILS */
        .details-grid {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }
        .detail-block {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .detail-label {
            font-size: 13px;
            font-weight: 600;
            color: #000;
        }
        .detail-value {
            font-size: 13px;
            color: #666;
        }

        /* SAVED ADDRESSES */
        .address-box {
            border: 1px solid #eaeaea;
            border-radius: 12px;
            padding: 25px;
            position: relative;
        }
        .address-name {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 12px;
        }
        .btn-edit-pencil {
            position: absolute;
            top: 25px;
            right: 25px;
            background: none;
            border: none;
            cursor: pointer;
            color: #666;
        }
        .tag-row {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .addr-tag {
            background: #eafaf1;
            color: #2b8a73; /* Teal color from screenshot */
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .address-text {
            font-size: 13px;
            color: #666;
            line-height: 1.8;
        }

        /* FOOTER LISTS */
        .simple-footer {
            max-width: 1000px;
            width: 100%;
            margin: 60px auto 40px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }
        .footer-block h4 {
            font-size: 12px;
            font-weight: 800;
            margin-bottom: 20px;
        }
        .footer-list {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 15px;
            list-style: none;
        }
        .footer-list li a {
            color: #666;
            text-decoration: none;
            font-size: 11px;
        }

        /* FLOATING Z */
        .floating-actions {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 2000;
        }
        .float-btn-z {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #333;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            border: none;
            font-weight: 800;
            font-size: 20px;
        }
    </style>
</head>
<body>

<!-- --- TOP PROMO BAR --- -->
<div class="top-promo-bar">
    <div class="promo-container">
        <a href="#" class="promo-item">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
            30 Days Free Returns | T&C Apply >
        </a>
        <a href="#" class="promo-item">
            <span style="background: #000; color:#fff; padding: 2px 5px; margin-right:5px; border-radius:2px;">VIP</span>
            Become a ZALORA VIP today! >
        </a>
        <a href="#" class="promo-item">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg>
            Save more on the app! 25% OFF + P150 OFF >
        </a>
    </div>
</div>

<!-- --- HEADER --- -->
<header>
    <div class="main-header">
        <a href="../index.php" class="logo">ZALORA</a>
        
        <div class="search-bar-wrap">
            <form action="products.php" method="GET">
                <input type="text" name="q" class="search-input" placeholder="Got You Scoring More">
                <button type="submit" class="search-icon-btn">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                </button>
            </form>
        </div>

        <div class="header-actions">
            <a href="profile.php" class="header-action-item">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <span>Hi <?= htmlspecialchars(explode(' ', $user['name'])[0]) ?>,</span>
            </a>
            <a href="wishlist.php" class="header-action-item" style="position:relative;">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                <?php if ($nav_wish_count > 0): ?><span class="badge-count"><?= $nav_wish_count ?></span><?php endif; ?>
            </a>
            <a href="cart.php" class="header-action-item" style="position:relative;">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                <?php if ($nav_cart_count > 0): ?><span class="badge-count"><?= $nav_cart_count ?></span><?php endif; ?>
            </a>
        </div>
    </div>

    <nav class="nav-bar">
        <div class="nav-container">
            <a href="products.php?gender=women" class="nav-item">WOMEN</a>
            <a href="products.php?gender=men" class="nav-item">MEN</a>
            <a href="products.php?category=kids" class="nav-item">KIDS</a>
            <a href="products.php?premium=1" class="nav-item">LUXURY</a>
            <a href="products.php?category=beauty" class="nav-item">BEAUTY</a>
            <a href="products.php?category=sports" class="nav-item">SPORTS</a>
        </div>
    </nav>
</header>

<div class="page-wrapper">
    <!-- SIDEBAR -->
    <aside class="sidebar">
        <h4>MY ACCOUNT</h4>
        <ul class="sidebar-menu">
            <li><a href="#" class="active">Account information</a></li>
            <li><a href="#">My Wallet</a></li>
            <li><a href="#">My Cashback</a></li>
            <li><a href="#">My ZVIP</a></li>
            <li><a href="#">Orders & Tracking</a></li>
            <li><a href="#">My Reviews</a></li>
            <li><a href="#">My Cards</a></li>
            <li><a href="#">Preferences</a></li>
            <li><a href="wishlist.php">Wishlist</a></li>
            <li><a href="apply_role.php?role=seller" style="font-weight:600; color:#c0392b;">Become a Seller</a></li>
            <li><a href="apply_role.php?role=driver" style="font-weight:600; color:#2980b9;">Become a Driver</a></li>
            <li><a href="../auth/logout.php">Sign out</a></li>
            <li><a href="#">Request Account Deletion</a></li>
        </ul>
    </aside>

    <!-- CONTENT -->
    <main class="content-area">
        <!-- Personal Details -->
        <div class="profile-card">
            <div class="card-header">
                <div class="card-title">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    Personal details
                </div>
                <a href="#" class="card-action-link">Edit</a>
            </div>
            <div class="details-grid">
                <div class="detail-block">
                    <span class="detail-label">Email Address</span>
                    <span class="detail-value"><?= htmlspecialchars($user['email']) ?></span>
                </div>
                <div class="detail-block">
                    <span class="detail-label">Name</span>
                    <span class="detail-value"><?= htmlspecialchars($user['name']) ?></span>
                </div>
                <div class="detail-block">
                    <span class="detail-label">Birthday</span>
                    <span class="detail-value">-</span>
                </div>
                <div class="detail-block">
                    <span class="detail-label">Password</span>
                    <span class="detail-value">**********</span>
                </div>
            </div>
        </div>

        <!-- Saved Addresses -->
        <div class="profile-card">
            <div class="card-header">
                <div class="card-title">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                    Saved Addresses (1)
                </div>
                <a href="#" class="card-action-link">Add</a>
            </div>
            
            <div class="address-box">
                <button class="btn-edit-pencil">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                </button>
                <div class="address-name">
                    <?= htmlspecialchars($address['Addrs_RcpntName'] ?? $user['name']) ?>
                </div>
                <div class="tag-row">
                    <span class="addr-tag">Shipping Address</span>
                    <span class="addr-tag" style="background:#e8f4fd; color:#2c82c9;">Billing Address</span>
                </div>
                <div class="address-text">
                    <?php if($address): ?>
                        <?= htmlspecialchars($address['Addrs_UnitNo'] ? $address['Addrs_UnitNo'].', ' : '') ?>
                        <?= htmlspecialchars($address['Addrs_Street'] ?? '') ?>.<br>
                        <?= htmlspecialchars($address['Addrs_Barangay'] ?? '') ?>. 
                        <?= htmlspecialchars($address['Addrs_City'] ?? '') ?>.<br>
                        <?= htmlspecialchars($address['Addrs_Province'] ?? '') ?>. 
                        <?= htmlspecialchars($address['Addrs_ZipCode'] ?? '') ?><br>
                        Phone: <?= htmlspecialchars($address['Addrs_Number'] ?? '') ?>
                    <?php else: ?>
                        Boop. Beep. Reformista. Limay. Bataan. 2103<br>
                        Phone: 9928274186
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </main>
</div>

<!-- FOOTER -->
<div class="simple-footer">
    <div class="footer-block">
        <h4>TOP BRANDS</h4>
        <ul class="footer-list">
            <li><a href="#">ALDO</a></li>
            <li><a href="#">Converse</a></li>
            <li><a href="#">PUMA</a></li>
            <li><a href="#">Birkenstock</a></li>
            <li><a href="#">Crocs</a></li>
            <li><a href="#">Casio</a></li>
            <li><a href="#">Lacoste</a></li>
            <li><a href="#">New Balance</a></li>
            <li><a href="#">GAP</a></li>
            <li><a href="#">NIKE</a></li>
            <li><a href="#">Ray-Ban</a></li>
            <li><a href="#">CLN</a></li>
            <li><a href="#">Superdry</a></li>
            <li><a href="#">ADIDAS</a></li>
            <li><a href="#">Mango</a></li>
        </ul>
    </div>
    <div class="footer-block">
        <h4>TOP SEARCHES</h4>
        <ul class="footer-list">
            <li><a href="#">Bags</a></li>
            <li><a href="#">Shoes</a></li>
            <li><a href="#">Casual Dresses</a></li>
            <li><a href="#">Clothes</a></li>
            <li><a href="#">Discount Prices</a></li>
            <li><a href="#">Corporate Attire</a></li>
            <li><a href="#">Sports</a></li>
            <li><a href="#">Accessories</a></li>
            <li><a href="#">Sneakers</a></li>
            <li><a href="#">New Products</a></li>
            <li><a href="#">Maxi Dress</a></li>
            <li><a href="#">Long Sleeve</a></li>
            <li><a href="#">Beauty</a></li>
            <li><a href="#">Jacket</a></li>
            <li><a href="#">Culottes</a></li>
        </ul>
    </div>
</div>

<div class="floating-actions">
    <button class="float-btn-z">Z</button>
</div>

</body>
</html>