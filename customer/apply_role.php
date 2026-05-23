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

// Get desired role application type
$role = isset($_GET['role']) && $_GET['role'] === 'driver' ? 'driver' : 'seller';
$role_title = $role === 'driver' ? 'Driver' : 'Seller';
$other_role_title = $role === 'seller' ? 'Driver' : 'Seller';

$msg = "";
$msg_type = "";

// Mutual exclusivity checks
$is_already_role = false;
$is_blocked_by_other_role = false;

// Fetch current customer email
$cust_email = '';
$all_customers = array_merge(
    $database->getReference('customer')->getSnapshot()->getValue() ?: [],
    $database->getReference('customer')->getSnapshot()->getValue() ?: []
);
foreach ($all_customers as $c) {
    if (($c['Cust_Id'] ?? 0) == $cust_id) {
        $cust_email = $c['Cust_Email'] ?? '';
        break;
    }
}

// Check role status
if ($role === 'seller') {
    $all_sellers = array_merge(
        $database->getReference('seller')->getSnapshot()->getValue() ?: [],
        $database->getReference('seller')->getSnapshot()->getValue() ?: []
    );
    foreach ($all_sellers as $s) {
        if (($s['Sell_Email'] ?? '') === $cust_email && !empty($cust_email)) {
            $is_already_role = true;
            break;
        }
    }
    
    $all_drivers = array_merge(
        $database->getReference('driver')->getSnapshot()->getValue() ?: [],
        $database->getReference('driver')->getSnapshot()->getValue() ?: []
    );
    foreach ($all_drivers as $d) {
        if (($d['Driv_Email'] ?? '') === $cust_email && !empty($cust_email)) {
            $is_blocked_by_other_role = true;
            break;
        }
    }
} else {
    $all_drivers = array_merge(
        $database->getReference('driver')->getSnapshot()->getValue() ?: [],
        $database->getReference('driver')->getSnapshot()->getValue() ?: []
    );
    foreach ($all_drivers as $d) {
        if (($d['Driv_Email'] ?? '') === $cust_email && !empty($cust_email)) {
            $is_already_role = true;
            break;
        }
    }
    
    $all_sellers = array_merge(
        $database->getReference('seller')->getSnapshot()->getValue() ?: [],
        $database->getReference('seller')->getSnapshot()->getValue() ?: []
    );
    foreach ($all_sellers as $s) {
        if (($s['Sell_Email'] ?? '') === $cust_email && !empty($cust_email)) {
            $is_blocked_by_other_role = true;
            break;
        }
    }
}

// Check if they have a pending/approved application for the OTHER role
if (!$is_blocked_by_other_role) {
    $other_app_type = $role === 'seller' ? 'Driver' : 'Seller';
    $all_apps = $database->getReference('role_application')->getSnapshot()->getValue() ?: [];
    foreach ($all_apps as $a) {
        if (($a['Cust_Id'] ?? 0) == $cust_id && ($a['App_Type'] ?? '') === $other_app_type && in_array($a['App_Status'] ?? '', ['Pending', 'Approved'])) {
            $is_blocked_by_other_role = true;
            break;
        }
    }
}

// Handle Application Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_application') {
    if ($is_blocked_by_other_role) {
        $msg = "You are already registered as a $other_role_title (or have an application in progress) and cannot apply for this role.";
        $msg_type = "error";
    } else {
        $details = [];
        if ($role === 'seller') {
            $details['business_name'] = trim($_POST['business_name'] ?? '');
            $details['business_email'] = trim($_POST['business_email'] ?? '');
            $details['business_desc'] = trim($_POST['business_desc'] ?? '');
            
            if (empty($details['business_name']) || empty($details['business_email'])) {
                $msg = "Please fill in all required fields.";
                $msg_type = "error";
            }
        } else {
            $details['license_no'] = trim($_POST['license_no'] ?? '');
            $details['vehicle_type'] = trim($_POST['vehicle_type'] ?? '');
            $details['phone'] = trim($_POST['phone'] ?? '');
            
            if (empty($details['license_no']) || empty($details['vehicle_type']) || empty($details['phone'])) {
                $msg = "Please fill in all required fields.";
                $msg_type = "error";
            }
        }

        if (empty($msg)) {
            $details_json = json_encode($details);
            $app_type = $role === 'driver' ? 'Driver' : 'Seller';
            
            // Check if there is an existing pending application
            $all_apps = $database->getReference('role_application')->getSnapshot()->getValue() ?: [];
            $existing = false;
            foreach ($all_apps as $a) {
                if (($a['Cust_Id'] ?? 0) == $cust_id && ($a['App_Type'] ?? '') === $app_type && ($a['App_Status'] ?? '') === 'Pending') {
                    $existing = true;
                    break;
                }
            }
            
            if ($existing) {
                $msg = "You already have a pending application for this role.";
                $msg_type = "error";
            } else {
                // Insert application
                $newApp = $database->getReference('role_application')->push();
                $newApp->set([
                    'App_Id' => $newApp->getKey(),
                    'Cust_Id' => $cust_id,
                    'App_Type' => $app_type,
                    'App_Details' => $details_json,
                    'App_Status' => 'Pending',
                    'Created_At' => date('Y-m-d H:i:s')
                ]);
                $msg = "Application submitted successfully! Our admin team will review it shortly.";
                $msg_type = "success";
            }
        }
    }
}

// Fetch existing application status
$app_type_db = $role === 'driver' ? 'Driver' : 'Seller';
$all_apps_status = $database->getReference('role_application')->getSnapshot()->getValue() ?: [];
$application = null;
$latest_time = 0;
foreach ($all_apps_status as $a) {
    if (($a['Cust_Id'] ?? 0) == $cust_id && ($a['App_Type'] ?? '') === $app_type_db) {
        $time = strtotime($a['Created_At'] ?? 0);
        if ($time > $latest_time) {
            $latest_time = $time;
            $application = $a;
        }
    }
}

// Get user name for header
$user_name = $_SESSION['user_name'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>ZALORA — Apply for <?= $role_title ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="../assets/css/global.css?v=<?= time() ?>"/>
    <link rel="stylesheet" href="../assets/css/apply-role.css?v=<?= time() ?>"/>
</head>
<body>

<!-- TOP PROMO BAR -->
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

<!-- HEADER -->
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
                <span>Hi <?= htmlspecialchars(explode(' ', $user_name)[0]) ?>,</span>
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
            <li><a href="profile.php">Account information</a></li>
            <li><a href="#">My Wallet</a></li>
            <li><a href="#">My Cashback</a></li>
            <li><a href="#">My ZVIP</a></li>
            <li><a href="profile.php?tab=orders">Orders & Tracking</a></li>
            <li><a href="#">My Reviews</a></li>
            <li><a href="#">My Cards</a></li>
            <li><a href="#">Preferences</a></li>
            <li><a href="wishlist.php">Wishlist</a></li>
            <li><a href="apply_role.php?role=seller" class="<?= $role === 'seller' ? 'active' : '' ?>" style="font-weight:600; color:#c0392b;">Become a Seller</a></li>
            <li><a href="apply_role.php?role=driver" class="<?= $role === 'driver' ? 'active' : '' ?>" style="font-weight:600; color:#2980b9;">Become a Driver</a></li>
            <li><a href="../auth/logout.php">Sign out</a></li>
            <li><a href="#">Request Account Deletion</a></li>
        </ul>
    </aside>

    <!-- CONTENT -->
    <main class="content-area">
        <div class="profile-card">
            
            <div class="card-header">
                <h2 class="card-title">Apply to be a <?= $role_title ?></h2>
                <?php if ($application): ?>
                    <span class="status-badge status-<?= strtolower($application['App_Status']) ?>">
                        <?= htmlspecialchars($application['App_Status']) ?>
                    </span>
                <?php endif; ?>
            </div>

            <?php if (!empty($msg)): ?>
                <div class="alert alert-<?= $msg_type ?>"><?= $msg ?></div>
            <?php endif; ?>

            <?php if ($is_blocked_by_other_role): ?>
                <div class="alert alert-error" style="line-height: 1.6;">
                    <strong>Access Restricted!</strong><br>
                    You are already a registered <?= $other_role_title ?> (or have a pending application). 
                    To ensure high operational focus, users can only apply to be either a Seller or a Driver, not both.
                </div>
            
            <?php elseif ($is_already_role): ?>
                <div class="alert alert-success">
                    <strong>Welcome aboard!</strong> You are already registered as a <?= $role_title ?>. You can log into your portal now using your current customer email and password.
                </div>
            
            <?php elseif ($application && $application['App_Status'] === 'Pending'): ?>
                <?php $details_data = json_decode($application['App_Details'], true); ?>
                <div class="alert alert-info" style="line-height: 1.6;">
                    <strong>Application Under Review!</strong><br>
                    You submitted an application on <?= date("F j, Y, g:i a", strtotime($application['Created_At'])) ?>.<br>
                    Our administrative team is currently validating your details. We will notify you once approved.
                </div>
                
                <div style="background: #fdfdfd; border: 1px dashed #ddd; border-radius: 8px; padding: 20px; margin-top: 20px;">
                    <h4 style="font-size:12px; font-weight:700; text-transform:uppercase; margin-bottom:10px;">Submitted Details:</h4>
                    <?php if ($role === 'seller'): ?>
                        <p style="font-size:13px; margin-bottom:5px;"><strong>Business Name:</strong> <?= htmlspecialchars($details_data['business_name'] ?? '') ?></p>
                        <p style="font-size:13px; margin-bottom:5px;"><strong>Store Email:</strong> <?= htmlspecialchars($details_data['business_email'] ?? '') ?></p>
                        <p style="font-size:13px;"><strong>Description:</strong> <?= htmlspecialchars($details_data['business_desc'] ?? '') ?></p>
                    <?php else: ?>
                        <p style="font-size:13px; margin-bottom:5px;"><strong>License Number:</strong> <?= htmlspecialchars($details_data['license_no'] ?? '') ?></p>
                        <p style="font-size:13px; margin-bottom:5px;"><strong>Vehicle Type:</strong> <?= htmlspecialchars($details_data['vehicle_type'] ?? '') ?></p>
                        <p style="font-size:13px;"><strong>Contact Phone:</strong> <?= htmlspecialchars($details_data['phone'] ?? '') ?></p>
                    <?php endif; ?>
                </div>

            <?php elseif ($application && $application['App_Status'] === 'Approved'): ?>
                <div class="alert alert-success">
                    <strong>Congratulations!</strong> Your application was approved. Please log in through the main Portal using your current customer credentials.
                </div>

            <?php else: ?>
                <!-- Render Application Form -->
                <form method="POST">
                    <input type="hidden" name="action" value="submit_application">
                    
                    <?php if ($role === 'seller'): ?>
                        <div class="form-group">
                            <label for="business_name">Business / Store Name *</label>
                            <input type="text" id="business_name" name="business_name" class="form-control" placeholder="e.g. My Premium Boutique" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="business_email">Business Email *</label>
                            <input type="email" id="business_email" name="business_email" class="form-control" placeholder="e.g. store@boutique.com" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="business_desc">Store Description / Products Offered</label>
                            <textarea id="business_desc" name="business_desc" class="form-control" placeholder="Describe the types of products you sell, your target audience, or brand vision."></textarea>
                        </div>
                    <?php else: ?>
                        <div class="form-group">
                            <label for="vehicle_type">Vehicle Type *</label>
                            <select id="vehicle_type" name="vehicle_type" class="form-control" required style="-webkit-appearance: none; appearance: none; background: #fff;">
                                <option value="">-- Select Vehicle --</option>
                                <option value="Motorcycle">Motorcycle</option>
                                <option value="Car">Car</option>
                                <option value="Bicycle">Bicycle</option>
                                <option value="Van/Truck">Van / Truck</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="license_no">Driver's License Number *</label>
                            <input type="text" id="license_no" name="license_no" class="form-control" placeholder="e.g. N01-XX-XXXXX" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Contact Phone Number *</label>
                            <input type="text" id="phone" name="phone" class="form-control" placeholder="e.g. 0917XXXXXXX" required>
                        </div>
                    <?php endif; ?>

                    <button type="submit" class="btn-submit">Submit Application</button>
                </form>
            <?php endif; ?>

        </div>
    </main>
</div>

</body>
</html>
