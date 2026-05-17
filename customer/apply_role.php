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

// 1. Check if they are already the current role
if ($role === 'seller') {
    $role_check = $conn->prepare("SELECT * FROM SELLER WHERE Sell_Email = (SELECT Cust_Email FROM CUSTOMER WHERE Cust_Id = ?)");
    $role_check->bind_param("i", $cust_id);
    $role_check->execute();
    if ($role_check->get_result()->fetch_assoc()) {
        $is_already_role = true;
    }
} else {
    $role_check = $conn->prepare("SELECT * FROM driver WHERE Driv_Email = (SELECT Cust_Email FROM CUSTOMER WHERE Cust_Id = ?)");
    $role_check->bind_param("i", $cust_id);
    $role_check->execute();
    if ($role_check->get_result()->fetch_assoc()) {
        $is_already_role = true;
    }
}

// 2. Check if they are already the OTHER role or have an application for it
if ($role === 'seller') {
    // Check if they are a driver
    $other_db_check = $conn->prepare("SELECT * FROM driver WHERE Driv_Email = (SELECT Cust_Email FROM CUSTOMER WHERE Cust_Id = ?)");
    $other_db_check->bind_param("i", $cust_id);
    $other_db_check->execute();
    if ($other_db_check->get_result()->fetch_assoc()) {
        $is_blocked_by_other_role = true;
    }
} else {
    // Check if they are a seller
    $other_db_check = $conn->prepare("SELECT * FROM SELLER WHERE Sell_Email = (SELECT Cust_Email FROM CUSTOMER WHERE Cust_Id = ?)");
    $other_db_check->bind_param("i", $cust_id);
    $other_db_check->execute();
    if ($other_db_check->get_result()->fetch_assoc()) {
        $is_blocked_by_other_role = true;
    }
}

// Check if they have a pending/approved application for the OTHER role
if (!$is_blocked_by_other_role) {
    $other_app_type = $role === 'seller' ? 'Driver' : 'Seller';
    $other_app_check = $conn->prepare("SELECT * FROM ROLE_APPLICATION WHERE Cust_Id = ? AND App_Type = ? AND App_Status IN ('Pending', 'Approved')");
    $other_app_check->bind_param("is", $cust_id, $other_app_type);
    $other_app_check->execute();
    if ($other_app_check->get_result()->fetch_assoc()) {
        $is_blocked_by_other_role = true;
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
            $check_stmt = $conn->prepare("SELECT * FROM ROLE_APPLICATION WHERE Cust_Id = ? AND App_Type = ? AND App_Status = 'Pending'");
            $check_stmt->bind_param("is", $cust_id, $app_type);
            $check_stmt->execute();
            $existing = $check_stmt->get_result()->fetch_assoc();
            
            if ($existing) {
                $msg = "You already have a pending application for this role.";
                $msg_type = "error";
            } else {
                // Insert application
                $insert_stmt = $conn->prepare("INSERT INTO ROLE_APPLICATION (Cust_Id, App_Type, App_Details, App_Status, Created_At) VALUES (?, ?, ?, 'Pending', NOW())");
                $insert_stmt->bind_param("iss", $cust_id, $app_type, $details_json);
                if ($insert_stmt->execute()) {
                    $msg = "Application submitted successfully! Our admin team will review it shortly.";
                    $msg_type = "success";
                } else {
                    $msg = "Error submitting application. Please try again.";
                    $msg_type = "error";
                }
            }
        }
    }
}

// Fetch existing application status
$app_type_db = $role === 'driver' ? 'Driver' : 'Seller';
$status_stmt = $conn->prepare("SELECT * FROM ROLE_APPLICATION WHERE Cust_Id = ? AND App_Type = ? ORDER BY Created_At DESC LIMIT 1");
$status_stmt->bind_param("is", $cust_id, $app_type_db);
$status_stmt->execute();
$application = $status_stmt->get_result()->fetch_assoc();

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

        /* MAIN CONTENT */
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
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }

        .card-title {
            font-size: 18px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* FORM STYLES */
        .form-group {
            margin-bottom: 20px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .form-group label {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            color: #333;
        }
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 13px;
            outline: none;
            font-family: inherit;
        }
        .form-control:focus {
            border-color: #000;
        }
        textarea.form-control {
            resize: vertical;
            min-height: 120px;
        }
        
        .btn-submit {
            background: #000;
            color: #fff;
            border: 1px solid #000;
            padding: 15px 30px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            cursor: pointer;
            transition: 0.2s;
            width: fit-content;
        }
        .btn-submit:hover {
            background: #fff;
            color: #000;
        }

        /* MESSAGES */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 25px;
            font-weight: 600;
        }
        .alert-success { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
        .alert-error { background: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }
        .alert-info { background: #e3f2fd; color: #1565c0; border: 1px solid #bbdefb; }

        /* STATUS BADGE */
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .status-pending { background: #fff8e1; color: #f57f17; border: 1px solid #ffe082; }
        .status-approved { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
        .status-rejected { background: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }
    </style>
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
            <li><a href="#">Orders & Tracking</a></li>
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
