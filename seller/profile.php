<?php
session_start();
require_once '../config/db.php';

// Auth check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seller') {
    header("Location: ../auth/login.php");
    exit;
}

$seller_id = $_SESSION['user_id'];
$errors = [];
$success_msg = $_SESSION['success_msg'] ?? '';
unset($_SESSION['success_msg']);

// Handle update profile POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $business_name = trim($_POST['business_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $new_pass = $_POST['new_password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';

    if (empty($business_name) || empty($email)) {
        $errors[] = "Business Name and Email are required.";
    }

    // Check if email already used by another seller
    if (empty($errors)) {
        $check_stmt = $conn->prepare("SELECT * FROM seller WHERE Sell_Email = ? AND Sell_Id != ?");
        $check_stmt->bind_param("si", $email, $seller_id);
        $check_stmt->execute();
        if ($check_stmt->get_result()->fetch_assoc()) {
            $errors[] = "Business Email is already in use by another seller.";
        }
    }

    if (empty($errors)) {
        if (!empty($new_pass)) {
            if ($new_pass !== $confirm_pass) {
                $errors[] = "Passwords do not match.";
            } else {
                $hash = password_hash($new_pass, PASSWORD_DEFAULT);
                $upd = $conn->prepare("UPDATE seller SET Sell_BusinessName = ?, Sell_Email = ?, Sell_Phone = ?, Sell_PsswdHash = ? WHERE Sell_Id = ?");
                $upd->bind_param("ssssi", $business_name, $email, $phone, $hash, $seller_id);
            }
        } else {
            $upd = $conn->prepare("UPDATE seller SET Sell_BusinessName = ?, Sell_Email = ?, Sell_Phone = ? WHERE Sell_Id = ?");
            $upd->bind_param("sssi", $business_name, $email, $phone, $seller_id);
        }

        if (empty($errors)) {
            if ($upd->execute()) {
                $_SESSION['success_msg'] = "Credentials updated successfully!";
                header("Location: profile.php");
                exit;
            } else {
                $errors[] = "Database error: " . $conn->error;
            }
        }
    }
}

// Fetch current seller data
$stmt = $conn->prepare("SELECT * FROM seller WHERE Sell_Id = ?");
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$seller = $stmt->get_result()->fetch_assoc();

if (!$seller) {
    die("Seller profile not found. Please contact support.");
}

// Get initials for avatar
$initials = "S";
if (!empty($seller['Sell_BusinessName'])) {
    $words = explode(" ", $seller['Sell_BusinessName']);
    $initials = strtoupper($words[0][0]);
    if (count($words) > 1) {
        $initials .= strtoupper($words[1][0]);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Center - Profile</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/seller.css">
    <style>
        .profile-container {
            max-width: 800px;
        }
        .profile-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            padding: 3rem;
            margin-bottom: 2rem;
            display: flex;
            gap: 3rem;
            align-items: flex-start;
        }
        .profile-avatar {
            width: 120px;
            height: 120px;
            background: #f1f5f9;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: var(--text-muted);
            flex-shrink: 0;
            font-weight: 800;
            letter-spacing: -0.02em;
        }
        .profile-details {
            flex-grow: 1;
        }
        .profile-name {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .profile-badge {
            display: inline-block;
            background: #dcfce7;
            color: var(--accent-green);
            padding: 4px 10px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 2rem;
            border-radius: 100px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        .info-group label {
            display: block;
            font-size: 10px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 6px;
        }
        .info-group p {
            font-size: 14px;
            color: var(--text-main);
        }
        
        /* SETTINGS CARD */
        .settings-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            padding: 3rem;
            margin-bottom: 2rem;
        }
        .settings-title {
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 2rem;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .form-group label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--text-muted);
            letter-spacing: 0.05em;
        }
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 13px;
            outline: none;
            font-family: inherit;
        }
        .form-control:focus {
            border-color: #000;
        }
        
        .btn-primary-custom {
            background: #000;
            color: #fff;
            border: 1px solid #000;
            padding: 12px 24px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            cursor: pointer;
            transition: 0.2s;
            width: fit-content;
        }
        .btn-primary-custom:hover {
            background: #fff;
            color: #000;
        }

        .danger-zone {
            border: 1px solid var(--accent-red);
            padding: 2rem;
            background: #fef2f2;
        }
        .danger-title {
            color: var(--accent-red);
            font-weight: 600;
            font-size: 12px;
            margin-bottom: 1rem;
            text-transform: uppercase;
        }
        .btn-danger {
            background: var(--accent-red);
            color: white;
            border: none;
            padding: 12px 24px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        .btn-danger:hover {
            opacity: 0.9;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 4px;
            font-size: 13px;
            margin-bottom: 2rem;
            font-weight: 600;
        }
        .alert-success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .alert-error { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<!-- MAIN WRAPPER -->
<div class="main-wrapper">
    <main class="main-content">
        
        <header class="page-header">
            <div>
                <h2 class="page-title">SELLER PROFILE</h2>
                <p class="page-subtitle">Manage your store's public identity and account settings.</p>
            </div>
        </header>

        <div class="profile-container">

            <?php if (!empty($success_msg)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success_msg) ?></div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error): ?>
                        <p style="margin-bottom: 5px;">• <?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <div class="profile-card">
                <div class="profile-avatar">
                    <?= $initials ?>
                </div>
                <div class="profile-details">
                    <h3 class="profile-name"><?= htmlspecialchars($seller['Sell_BusinessName']) ?></h3>
                    <div class="profile-badge">
                        <svg viewBox="0 0 24 24" width="10" height="10" fill="none" stroke="currentColor" stroke-width="3" style="display:inline;margin-right:2px;vertical-align:middle;"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        VERIFIED SELLER
                    </div>
                    
                    <div class="info-grid">
                        <div class="info-group">
                            <label>BUSINESS EMAIL</label>
                            <p><?= htmlspecialchars($seller['Sell_Email']) ?></p>
                        </div>
                        <div class="info-group">
                            <label>PHONE NUMBER</label>
                            <p><?= htmlspecialchars($seller['Sell_Phone'] ?? 'Not provided') ?></p>
                        </div>
                        <div class="info-group">
                            <label>SELLER ID</label>
                            <p>#SLR-<?= str_pad($seller['Sell_Id'], 5, '0', STR_PAD_LEFT) ?></p>
                        </div>
                        <div class="info-group">
                            <label>JOINED DATE</label>
                            <p><?= date('F d, Y', strtotime($seller['Sell_JoinedAt'])) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- UPDATE PROFILE CREDENTIALS -->
            <div class="settings-card">
                <h4 class="settings-title">Edit Store Credentials</h4>
                <form method="POST">
                    <input type="hidden" name="update_profile" value="1">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="business_name">Business Name *</label>
                            <input type="text" id="business_name" name="business_name" class="form-control" value="<?= htmlspecialchars($seller['Sell_BusinessName']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Business Email *</label>
                            <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($seller['Sell_Email']) ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="text" id="phone" name="phone" class="form-control" value="<?= htmlspecialchars($seller['Sell_Phone'] ?? '') ?>" placeholder="e.g. 0917XXXXXXX">
                        </div>
                        <div style="flex:1;"></div>
                    </div>

                    <h4 class="settings-title" style="margin-top: 3rem; margin-bottom: 2rem;">Security & Password (Optional)</h4>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Leave blank to keep current">
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Leave blank to keep current">
                        </div>
                    </div>

                    <button type="submit" class="btn-primary-custom" style="margin-top: 1rem;">SAVE CHANGES</button>
                </form>
            </div>

            <div class="danger-zone">
                <h4 class="danger-title">Account Access</h4>
                <p style="margin-bottom: 1.5rem; color: var(--text-muted);">Securely log out of your seller session to protect your business data.</p>
                <button class="btn-danger" onclick="window.location.href='../auth/logout.php'">SIGN OUT OF SELLER CENTER</button>
            </div>

        </div>

    </main>

    <!-- FOOTER -->
    <footer class="seller-footer">
        <div>
            <div class="footer-logo">ZALORA</div>
            <div class="footer-copy">© <?= date('Y') ?> ZALORA ALL RIGHTS RESERVED</div>
        </div>
        <div class="footer-links">
            <a href="#">HELP & SUPPORT</a>
            <a href="#">TERMS & CONDITIONS</a>
            <a href="#">CONTACT US</a>
        </div>
    </footer>
</div>

</body>
</html>
