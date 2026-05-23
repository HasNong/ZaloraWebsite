<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seller') {
    header("Location: ../auth/login.php");
    exit;
}

$seller_id = $_SESSION['user_id'];
$errors = [];
$success_msg = $_SESSION['success_msg'] ?? '';
unset($_SESSION['success_msg']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $business_name = trim($_POST['business_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $new_pass = $_POST['new_password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';

    if (empty($business_name) || empty($email)) {
        $errors[] = "Business Name and Email are required.";
    }

    if (empty($errors)) {
        $all_sellers = fb_merge_nodes($database, 'seller');
        foreach ($all_sellers as $s) {
            if (($s['Sell_Email'] ?? '') === $email && (string) ($s['Sell_Id'] ?? '') !== (string) $seller_id) {
                $errors[] = "Business Email is already in use by another seller.";
                break;
            }
        }
    }

    if (empty($errors)) {
        if (!empty($new_pass)) {
            if ($new_pass !== $confirm_pass) {
                $errors[] = "Passwords do not match.";
            } else {
                $updates = [
                    'Sell_BusinessName' => $business_name,
                    'Sell_Email' => $email,
                    'Sell_Phone' => $phone,
                    'Sell_PsswdHash' => password_hash($new_pass, PASSWORD_DEFAULT),
                ];
            }
        } else {
            $updates = [
                'Sell_BusinessName' => $business_name,
                'Sell_Email' => $email,
                'Sell_Phone' => $phone,
            ];
        }

        if (empty($errors) && isset($updates)) {
            if (fb_update_record($database, 'seller', 'Sell_Id', $seller_id, $updates)) {
                $_SESSION['user_name'] = $business_name;
                $_SESSION['user_email'] = $email;
                $_SESSION['success_msg'] = "Credentials updated successfully!";
                header("Location: profile.php");
                exit;
            }
            $errors[] = "Seller profile not found. Please contact support.";
        }
    }
}

$found = fb_find_record($database, 'seller', 'Sell_Id', $seller_id);
$seller = $found['data'] ?? null;

if (!$seller) {
    die("Seller profile not found. Please contact support.");
}

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
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/seller.css">
    <link rel="stylesheet" href="../assets/css/seller-profile.css?v=<?= time() ?>">
</head>
<body>

<?php include 'sidebar.php'; ?>

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
                            <p><?= date('F d, Y', strtotime($seller['Sell_JoinedAt'] ?? 'now')) ?></p>
                        </div>
                    </div>
                </div>
            </div>

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
