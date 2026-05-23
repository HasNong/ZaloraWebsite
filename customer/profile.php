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
$custRef = $database->getReference('customer')->orderByChild('Cust_Id')->equalTo($customer_id)->getSnapshot()->getValue();
$cust_data = $custRef ? reset($custRef) : null;
$cust_key = $custRef ? key($custRef) : null;
$customer_node = 'customer';

$user = [
    "name"            => ($cust_data['Cust_Firstname'] ?? '') . ' ' . ($cust_data['Cust_Lastname'] ?? ''),
    "email"           => $cust_data['Cust_Email'] ?? '',
];

$msg = $_GET['msg'] ?? '';
$msg_type = $_GET['type'] ?? '';
$active_tab = $_GET['tab'] ?? 'account';

// Fetch Address first so we can use it in both logic and view
$addrRef = $database->getReference('address')->orderByChild('Cust_Id')->equalTo($customer_id)->getSnapshot()->getValue() ?: [];
$address = null;
$addr_key = null;
if ($addrRef) {
    foreach ($addrRef as $k => $a) {
        if (($a['Addrs_IsDefault'] ?? 0) == 1) {
            $address = $a;
            $addr_key = $k;
            break;
        }
    }
}

// Handle save/update address
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_address') {
    $rcp_name = trim($_POST['rcp_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $unit_no = trim($_POST['unit_no'] ?? '');
    $street = trim($_POST['street'] ?? '');
    $province = trim($_POST['province'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $barangay = trim($_POST['barangay'] ?? '');
    $zip_code = trim($_POST['zip_code'] ?? '');

    if ($rcp_name && $phone && $street && $province && $city && $barangay && $zip_code) {
        try {
            if ($address) {
                // Update
                $database->getReference('address')->getChild($addr_key)->update([
                    'Addrs_RcpntName' => $rcp_name,
                    'Addrs_Number' => $phone,
                    'Addrs_UnitNo' => $unit_no,
                    'Addrs_Street' => $street,
                    'Addrs_Province' => $province,
                    'Addrs_City' => $city,
                    'Addrs_Barangay' => $barangay,
                    'Addrs_ZipCode' => $zip_code
                ]);
                $msg = "Address updated successfully!";
                $msg_type = "success";
            } else {
                // Insert
                $newAddr = $database->getReference('address')->push();
                $newAddr->set([
                    'Addrs_id' => $newAddr->getKey(),
                    'Cust_Id' => $customer_id,
                    'Addrs_RcpntName' => $rcp_name,
                    'Addrs_Number' => $phone,
                    'Addrs_UnitNo' => $unit_no,
                    'Addrs_Street' => $street,
                    'Addrs_Province' => $province,
                    'Addrs_City' => $city,
                    'Addrs_Barangay' => $barangay,
                    'Addrs_ZipCode' => $zip_code,
                    'Addrs_IsDefault' => 1,
                    'Addrs_CreatedAt' => date('Y-m-d H:i:s')
                ]);
                $msg = "Address saved successfully!";
                $msg_type = "success";
            }
        } catch (Exception $e) {
            $msg = "Error updating address: " . $e->getMessage();
            $msg_type = "error";
        }

        header("Location: profile.php?msg=" . urlencode($msg) . "&type=" . $msg_type);
        exit;
    }
}

// Handle save/update profile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_profile') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $current_password = $_POST['current_password'] ?? '';

    if ($first_name && $last_name && $email && $current_password && $cust_data) {
        if (password_verify($current_password, $cust_data['Cust_PsswdHash'])) {
            $updateData = [
                'Cust_Firstname' => $first_name,
                'Cust_Lastname' => $last_name,
                'Cust_Email' => $email,
                'Cust_Number' => $phone_number,
                'Cust_UpdatedAt' => date('Y-m-d H:i:s')
            ];
            
            if (!empty($new_password)) {
                $updateData['Cust_PsswdHash'] = password_hash($new_password, PASSWORD_DEFAULT);
            }
            
            if ($cust_key) {
                try {
                    $database->getReference($customer_node)->getChild($cust_key)->update($updateData);
                    $msg = "Profile details updated successfully!";
                    $msg_type = "success";
                } catch (Exception $e) {
                    $msg = "Error updating profile details: " . $e->getMessage();
                    $msg_type = "error";
                }
            }
        } else {
            $msg = "Incorrect current password!";
            $msg_type = "error";
        }
        header("Location: profile.php?msg=" . urlencode($msg) . "&type=" . $msg_type);
        exit;
    }
}

// Handle Wallet Top-up
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'topup_wallet') {
    $amount = floatval($_POST['amount'] ?? 0);
    if ($amount > 0 && $cust_key) {
        $current_balance = floatval($cust_data['Cust_Balance'] ?? 0);
        $new_balance = $current_balance + $amount;
        
        try {
            $database->getReference($customer_node)->getChild($cust_key)->update([
                'Cust_Balance' => $new_balance
            ]);
            $msg = "Successfully topped up $".number_format($amount, 2)."!";
            $msg_type = "success";
        } catch (Exception $e) {
            $msg = "Error topping up wallet: " . $e->getMessage();
            $msg_type = "error";
        }
        header("Location: profile.php?tab=wallet&msg=" . urlencode($msg) . "&type=" . $msg_type);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>ZALORA — My Profile</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&family=Cormorant+Garamond:ital,wght@1,600&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="../assets/css/global.css?v=<?= time() ?>"/>
    <link rel="stylesheet" href="../assets/css/profile.css?v=<?= time() ?>"/>
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
            <li><a href="profile.php" class="<?= $active_tab === 'account' ? 'active' : '' ?>">Account information</a></li>
            <li><a href="profile.php?tab=wallet" class="<?= $active_tab === 'wallet' ? 'active' : '' ?>">My Wallet</a></li>
            <li><a href="#">My Cashback</a></li>
            <li><a href="#">My ZVIP</a></li>
            <li><a href="profile.php?tab=orders" class="<?= $active_tab === 'orders' ? 'active' : '' ?>">Orders & Tracking</a></li>
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
        <?php if ($active_tab === 'wallet'): ?>
            <div class="profile-card" style="background: linear-gradient(135deg, #000, #333); color: #fff; padding: 40px; border-radius: 12px; position: relative; overflow: hidden;">
                <div style="position: absolute; top: -50px; right: -50px; width: 200px; height: 200px; background: rgba(255,255,255,0.05); border-radius: 50%;"></div>
                <div style="position: absolute; bottom: -30px; left: -30px; width: 100px; height: 100px; background: rgba(255,255,255,0.05); border-radius: 50%;"></div>
                
                <h3 style="font-size: 14px; font-weight: 700; letter-spacing: 0.2em; text-transform: uppercase; margin-bottom: 20px; opacity: 0.8;">Zalora Wallet</h3>
                <div style="font-size: 48px; font-weight: 800; font-family: 'Montserrat', sans-serif; margin-bottom: 30px;">
                    $<?= number_format($cust_data['Cust_Balance'] ?? 0, 2) ?>
                </div>
                
                <div style="display: flex; gap: 15px; align-items: center;">
                    <form method="POST" action="profile.php" style="display: flex; gap: 10px; align-items: center;">
                        <input type="hidden" name="action" value="topup_wallet">
                        <span style="font-weight: 700; font-size: 14px;">$</span>
                        <input type="number" name="amount" min="1" step="0.01" required placeholder="0.00" style="padding: 10px; border: none; border-radius: 4px; width: 100px; font-weight: 700;">
                        <button type="submit" style="background: #fff; color: #000; border: none; padding: 12px 30px; font-weight: 700; font-size: 12px; cursor: pointer; text-transform: uppercase; border-radius: 4px; transition: 0.3s; hover: opacity: 0.9;">Top Up</button>
                    </form>
                    <button style="background: transparent; color: #fff; border: 1px solid rgba(255,255,255,0.5); padding: 12px 30px; font-weight: 700; font-size: 12px; cursor: pointer; text-transform: uppercase; border-radius: 4px; transition: 0.3s;">View History</button>
                </div>
            </div>
            
            <div class="profile-card" style="margin-top: 30px;">
                <h3 style="font-size: 14px; font-weight: 700; text-transform: uppercase; margin-bottom: 20px;">Recent Transactions</h3>
                <div style="text-align: center; padding: 40px 0; color: #999; font-size: 14px;">
                    <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin-bottom: 15px; opacity: 0.5;"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                    <p>No recent wallet transactions found.</p>
                </div>
            </div>

        <?php elseif ($active_tab === 'orders'): ?>
            <!-- Orders & Tracking List -->
            <div class="profile-card">
                <div class="card-header">
                    <div class="card-title">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2"/></svg>
                        Orders & Tracking
                    </div>
                </div>

                <?php
                $orders1 = $database->getReference('orders')->orderByChild('Cust_Id')->equalTo($customer_id)->getSnapshot()->getValue() ?: [];
                $orders2 = $database->getReference('orders')->orderByChild('Cust_Id')->equalTo($customer_id)->getSnapshot()->getValue() ?: [];
                $orders = array_merge($orders1, $orders2);

                if ($orders):
                    // Sort by date descending
                    usort($orders, function($a, $b) {
                        return strtotime($b['Order_PlacedAt'] ?? 0) - strtotime($a['Order_PlacedAt'] ?? 0);
                    });
                    
                    $allItemSnapshot1 = $database->getReference('order_item')->getSnapshot()->getValue() ?: [];
                    $allItemSnapshot2 = $database->getReference('order_item')->getSnapshot()->getValue() ?: [];
                    $allItems = array_merge($allItemSnapshot1, $allItemSnapshot2);

                    foreach ($orders as $ord):
                        $ord_id = $ord['Order_Id'];
                        $ord_status = strtoupper($ord['Order_Status'] ?? 'PENDING');
                        
                        $total_qty = 0;
                        foreach ($allItems as $oi) {
                            if (($oi['Order_Id'] ?? '') == $ord_id) {
                                $total_qty += intval($oi['OdItm_Quantity'] ?? 0);
                            }
                        }
                        
                        $status_class = strtolower($ord_status);
                ?>
                    <div class="order-card">
                        <div class="order-meta">
                            <h5>Order #<?= $ord_id ?></h5>
                            <p>Placed on: <?= date('M d, Y', strtotime($ord['Order_PlacedAt'] ?? time())) ?></p>
                            <p>Items: <?= $total_qty ?> | Total: $<?= number_format($ord['Order_TotalAmnt'] ?? 0, 2) ?></p>
                            <span class="order-badge <?= $status_class ?>"><?= $ord_status ?></span>
                        </div>
                        <div>
                            <a href="order_details.php?id=<?= $ord_id ?>" class="btn-track">Track & View</a>
                        </div>
                    </div>
                <?php 
                    endforeach;
                else: 
                ?>
                    <div style="text-align: center; padding: 40px 20px; border: 1px dashed #ddd; border-radius: 12px;">
                        <p style="font-size: 13px; color: #888;">You have not placed any orders yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Personal Details -->
            <div class="profile-card">
                <div class="card-header">
                    <div class="card-title">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        Personal details
                    </div>
                    <a href="#" class="card-action-link" onclick="openProfileModal(); return false;">Edit</a>
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
                        <span class="detail-label">Mobile Number</span>
                        <span class="detail-value"><?= htmlspecialchars($cust_data['Cust_Number'] ?? '-') ?></span>
                    </div>
                    <div class="detail-block">
                        <span class="detail-label">Password</span>
                        <span class="detail-value">**********</span>
                    </div>
                </div>
            </div>

            <!-- Saved Addresses -->
            <div class="profile-card">
                <?php if (!empty($msg)): ?>
                    <div class="alert-toast"><?= htmlspecialchars($msg) ?></div>
                <?php endif; ?>

                <div class="card-header">
                    <div class="card-title">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                        Saved Addresses (<?= $address ? 1 : 0 ?>)
                    </div>
                    <a href="#" class="card-action-link" onclick="openAddressModal(false); return false;">Add</a>
                </div>
                
                <?php if($address): ?>
                    <div class="address-box">
                        <button class="btn-edit-pencil" onclick="openAddressModal(true)">
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
                            <?= htmlspecialchars($address['Addrs_UnitNo'] ? $address['Addrs_UnitNo'].', ' : '') ?>
                            <?= htmlspecialchars($address['Addrs_Street'] ?? '') ?>.<br>
                            <?= htmlspecialchars($address['Addrs_Barangay'] ?? '') ?>. 
                            <?= htmlspecialchars($address['Addrs_City'] ?? '') ?>.<br>
                            <?= htmlspecialchars($address['Addrs_Province'] ?? '') ?>. 
                            <?= htmlspecialchars($address['Addrs_ZipCode'] ?? '') ?><br>
                            Phone: <?= htmlspecialchars($address['Addrs_Number'] ?? '') ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px 20px; border: 1px dashed #ddd; border-radius: 12px;">
                        <p style="font-size: 13px; color: #888; margin-bottom: 15px;">No shipping address saved yet.</p>
                        <button type="button" class="btn-save" style="font-size: 11px;" onclick="openAddressModal(false)">Add Shipping Address</button>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
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

<!-- Address Modal Overlay -->
<div id="address-modal" class="modal-overlay">
    <div class="modal-card">
        <div class="modal-header">
            <h3>Edit Shipping Address</h3>
            <button type="button" class="close-btn" onclick="closeAddressModal()">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="save_address">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="rcp_name">Recipient Name *</label>
                    <input type="text" id="rcp_name" name="rcp_name" class="form-control" required placeholder="e.g. Han Song Malalay">
                </div>
                <div class="form-group">
                    <label for="phone">Phone Number *</label>
                    <input type="text" id="phone" name="phone" class="form-control" required placeholder="e.g. 0917XXXXXXX">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="unit_no">Unit / House No.</label>
                    <input type="text" id="unit_no" name="unit_no" class="form-control" placeholder="e.g. House 45, Phase 2">
                </div>
                <div class="form-group">
                    <label for="street">Street Address *</label>
                    <input type="text" id="street" name="street" class="form-control" required placeholder="e.g. Sunflower St.">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="province">Province *</label>
                    <select id="province" name="province" class="form-control" required onchange="onProvinceChange()">
                        <option value="">Select Province</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="city">City / Municipality *</label>
                    <select id="city" name="city" class="form-control" required onchange="onCityChange()">
                        <option value="">Select City / Municipality</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="barangay">Barangay *</label>
                    <select id="barangay" name="barangay" class="form-control" required>
                        <option value="">Select Barangay</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="zip_code">Zip Code *</label>
                    <input type="text" id="zip_code" name="zip_code" class="form-control" required placeholder="e.g. 1000">
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                <button type="button" class="btn-cancel" onclick="closeAddressModal()">Cancel</button>
                <button type="submit" class="btn-save">Save Address</button>
            </div>
        </form>
    </div>
</div>

<!-- Personal Details Modal Overlay -->
<div id="profile-modal" class="modal-overlay">
    <div class="modal-card">
        <div class="modal-header">
            <h3>Edit Personal Details</h3>
            <button type="button" class="close-btn" onclick="closeProfileModal()">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="save_profile">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">First Name *</label>
                    <input type="text" id="first_name" name="first_name" class="form-control" required value="<?= htmlspecialchars($cust_data['Cust_Firstname'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name *</label>
                    <input type="text" id="last_name" name="last_name" class="form-control" required value="<?= htmlspecialchars($cust_data['Cust_Lastname'] ?? '') ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" class="form-control" required value="<?= htmlspecialchars($cust_data['Cust_Email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="phone_number">Phone Number</label>
                    <input type="text" id="phone_number" name="phone_number" class="form-control" placeholder="e.g. 0917XXXXXXX" value="<?= htmlspecialchars($cust_data['Cust_Number'] ?? '') ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="new_password">New Password (leave blank to keep current)</label>
                    <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Enter new password">
                </div>
                <div class="form-group">
                    <label for="current_password">Current Password * (to confirm changes)</label>
                    <input type="password" id="current_password" name="current_password" class="form-control" required placeholder="Enter current password">
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                <button type="button" class="btn-cancel" onclick="closeProfileModal()">Cancel</button>
                <button type="submit" class="btn-save">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
let savedProvince = "<?= addslashes($address['Addrs_Province'] ?? '') ?>";
let savedCity = "<?= addslashes($address['Addrs_City'] ?? '') ?>";
let savedBarangay = "<?= addslashes($address['Addrs_Barangay'] ?? '') ?>";

function openProfileModal() {
    document.getElementById('profile-modal').classList.add('active');
}

function closeProfileModal() {
    document.getElementById('profile-modal').classList.remove('active');
}

function openAddressModal(isEdit = false) {
    document.getElementById('address-modal').classList.add('active');
    
    if (isEdit) {
        document.getElementById('rcp_name').value = "<?= addslashes($address['Addrs_RcpntName'] ?? '') ?>";
        document.getElementById('phone').value = "<?= addslashes($address['Addrs_Number'] ?? '') ?>";
        document.getElementById('unit_no').value = "<?= addslashes($address['Addrs_UnitNo'] ?? '') ?>";
        document.getElementById('street').value = "<?= addslashes($address['Addrs_Street'] ?? '') ?>";
        document.getElementById('zip_code').value = "<?= addslashes($address['Addrs_ZipCode'] ?? '') ?>";
        savedProvince = "<?= addslashes($address['Addrs_Province'] ?? '') ?>";
        savedCity = "<?= addslashes($address['Addrs_City'] ?? '') ?>";
        savedBarangay = "<?= addslashes($address['Addrs_Barangay'] ?? '') ?>";
    } else {
        document.getElementById('rcp_name').value = "";
        document.getElementById('phone').value = "";
        document.getElementById('unit_no').value = "";
        document.getElementById('street').value = "";
        document.getElementById('zip_code').value = "";
        savedProvince = "";
        savedCity = "";
        savedBarangay = "";
    }
    
    loadProvinces();
}

function closeAddressModal() {
    document.getElementById('address-modal').classList.remove('active');
}

async function loadProvinces() {
    const provinceSelect = document.getElementById('province');
    provinceSelect.innerHTML = '<option value="">Loading Provinces...</option>';
    
    try {
        const response = await fetch('https://psgc.gitlab.io/api/provinces/');
        const provinces = await response.json();
        
        // Sort alphabetically
        provinces.sort((a, b) => a.name.localeCompare(b.name));
        
        provinceSelect.innerHTML = '<option value="">Select Province</option>';
        provinces.forEach(p => {
            const option = document.createElement('option');
            option.value = p.name;
            option.dataset.code = p.code;
            option.innerText = p.name;
            
            if (savedProvince && p.name.toLowerCase() === savedProvince.toLowerCase()) {
                option.selected = true;
            }
            provinceSelect.appendChild(option);
        });
        
        if (provinceSelect.value) {
            onProvinceChange();
        }
    } catch (e) {
        console.error("Error loading provinces", e);
        provinceSelect.innerHTML = '<option value="">Error loading. Try again.</option>';
    }
}

async function onProvinceChange() {
    const provinceSelect = document.getElementById('province');
    const citySelect = document.getElementById('city');
    const barangaySelect = document.getElementById('barangay');
    
    citySelect.innerHTML = '<option value="">Loading Cities...</option>';
    barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
    
    const selectedOption = provinceSelect.options[provinceSelect.selectedIndex];
    const provinceCode = selectedOption.dataset.code;
    
    if (!provinceCode) {
        citySelect.innerHTML = '<option value="">Select City / Municipality</option>';
        return;
    }
    
    try {
        const response = await fetch(`https://psgc.gitlab.io/api/provinces/${provinceCode}/cities-municipalities/`);
        const cities = await response.json();
        
        cities.sort((a, b) => a.name.localeCompare(b.name));
        
        citySelect.innerHTML = '<option value="">Select City / Municipality</option>';
        cities.forEach(c => {
            const option = document.createElement('option');
            option.value = c.name;
            option.dataset.code = c.code;
            option.innerText = c.name;
            
            if (savedCity && c.name.toLowerCase() === savedCity.toLowerCase()) {
                option.selected = true;
            }
            citySelect.appendChild(option);
        });
        
        if (citySelect.value) {
            onCityChange();
        }
    } catch (e) {
        console.error("Error loading cities", e);
        citySelect.innerHTML = '<option value="">Error loading.</option>';
    }
}

async function onCityChange() {
    const citySelect = document.getElementById('city');
    const barangaySelect = document.getElementById('barangay');
    
    barangaySelect.innerHTML = '<option value="">Loading Barangays...</option>';
    
    const selectedOption = citySelect.options[citySelect.selectedIndex];
    const cityCode = selectedOption.dataset.code;
    
    if (!cityCode) {
        barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
        return;
    }
    
    try {
        const response = await fetch(`https://psgc.gitlab.io/api/cities-municipalities/${cityCode}/barangays/`);
        const barangays = await response.json();
        
        barangays.sort((a, b) => a.name.localeCompare(b.name));
        
        barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
        barangays.forEach(b => {
            const option = document.createElement('option');
            option.value = b.name;
            option.innerText = b.name;
            
            if (savedBarangay && b.name.toLowerCase() === savedBarangay.toLowerCase()) {
                option.selected = true;
            }
            barangaySelect.appendChild(option);
        });
    } catch (e) {
        console.error("Error loading barangays", e);
        barangaySelect.innerHTML = '<option value="">Error loading.</option>';
    }
}
</script>

</body>
</html>