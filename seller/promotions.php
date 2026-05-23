<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'seller') {
    header("Location: ../auth/login.php");
    exit;
}

$seller_id = $_SESSION['user_id'];
$msg = "";

if (isset($_POST['add_coupon'])) {
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $type = strtoupper(trim($_POST['type'] ?? 'PERCENTAGE'));
    $val = floatval($_POST['value'] ?? 0);
    $min = floatval($_POST['min_spend'] ?? 0);
    $max = intval($_POST['max_uses'] ?? 100);
    $start = str_replace('T', ' ', $_POST['start_date'] ?? '') . ':00';
    $end = str_replace('T', ' ', $_POST['end_date'] ?? '') . ':00';

    if ($code) {
        try {
            $next_id = fb_next_id($database, 'coupon', 'Coup_Id');
            $couponRef = $database->getReference('coupon')->push();
            $couponRef->set([
                'Coup_Id' => $next_id,
                'Seller_Id' => $seller_id,
                'Coup_Code' => $code,
                'Coup_DiscType' => $type,
                'Coup_DiscValue' => $val,
                'Coup_MinOrderAmt' => $min,
                'Coup_MaxUses' => $max,
                'Coup_UsedCount' => 0,
                'Coup_ValidFrom' => $start,
                'Coup_ValidUntil' => $end,
                'Coup_IsActive' => 1,
                'Is_Approved' => 0,
            ]);
            $msg = "Coupon request submitted! Waiting for Admin approval.";
        } catch (Exception $e) {
            $msg = "Error: " . $e->getMessage();
        }
    }
}

$all_coupons = $database->getReference('coupon')->getSnapshot()->getValue() ?: [];
$coupons = [];
foreach ($all_coupons as $c) {
    if ((string) ($c['Seller_Id'] ?? '') === (string) $seller_id) {
        $coupons[] = $c;
    }
}
usort($coupons, fn($a, $b) => strtotime($b['Coup_ValidFrom'] ?? 0) - strtotime($a['Coup_ValidFrom'] ?? 0));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Promotions - Zalora Seller Center</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/seller.css">
    <link rel="stylesheet" href="../assets/css/seller-promotions.css?v=<?= time() ?>">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-wrapper">
        <main class="main-content">
            <header class="page-header">
                <div>
                    <h2 class="page-title">PROMOTIONS & CAMPAIGNS</h2>
                    <p class="page-subtitle">Submit your discount requests for Admin verification and marketplace activation.</p>
                </div>
            </header>

            <?php if ($msg): ?>
                <div style="background: var(--black); color: var(--white); padding: 18px; margin-bottom: 30px; font-size: 11px; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase;">
                    <?= htmlspecialchars($msg) ?>
                </div>
            <?php endif; ?>

            <div class="promo-grid">
                <div class="content-card">
                    <div class="card-header">
                        <h3 class="card-title">My Campaigns</h3>
                    </div>
                    <table class="promo-table">
                        <thead>
                            <tr>
                                <th>Campaign Code</th>
                                <th>Benefit</th>
                                <th>Usage</th>
                                <th>Approval</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($coupons) > 0): ?>
                                <?php foreach ($coupons as $c): ?>
                                <tr>
                                    <td><span class="coupon-pill"><?= htmlspecialchars($c['Coup_Code']) ?></span></td>
                                    <td>
                                        <span style="font-weight: 700; font-size: 14px;">
                                            <?= $c['Coup_DiscType'] === 'PERCENTAGE' ? $c['Coup_DiscValue'].'%' : '$'.number_format($c['Coup_DiscValue'], 2) ?>
                                        </span>
                                        <div style="font-size: 10px; color: var(--text-light); margin-top: 4px;">MIN. SPEND $<?= number_format($c['Coup_MinOrderAmt'], 2) ?></div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 600;"><?= $c['Coup_UsedCount'] ?> / <?= $c['Coup_MaxUses'] ?></div>
                                        <div style="font-size: 10px; color: var(--text-light); margin-top: 4px;">REDEMPTIONS</div>
                                    </td>
                                    <td>
                                        <?php if (($c['Is_Approved'] ?? 0) == 1): ?>
                                            <span class="status-badge status-approved">Live</span>
                                        <?php elseif (($c['Is_Approved'] ?? 0) == 0): ?>
                                            <span class="status-badge status-pending">Pending Review</span>
                                        <?php else: ?>
                                            <span class="status-badge status-rejected">Rejected</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4" style="text-align:center; padding: 60px; color: var(--text-light);">No active campaigns. Design your first voucher to boost sales!</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="content-card">
                    <div class="card-header">
                        <h3 class="card-title">Create New Voucher</h3>
                    </div>
                    <form method="POST">
                        <div class="form-group">
                            <label>Campaign Code</label>
                            <input type="text" name="code" class="form-control" placeholder="E.G. FLASH25" required>
                        </div>
                        <div class="form-group" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div>
                                <label>Type</label>
                                <select name="type" class="form-control">
                                    <option value="PERCENTAGE">% Percent</option>
                                    <option value="FIXED">$ Fixed</option>
                                </select>
                            </div>
                            <div>
                                <label>Value</label>
                                <input type="number" step="0.01" name="value" class="form-control" placeholder="0.00" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Min. Spend ($)</label>
                            <input type="number" step="0.01" name="min_spend" class="form-control" value="0.00">
                        </div>
                        <div class="form-group">
                            <label>Total Slots (Usage)</label>
                            <input type="number" name="max_uses" class="form-control" value="100">
                        </div>
                        <div class="form-group">
                            <label>Campaign Start</label>
                            <input type="datetime-local" name="start_date" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Campaign End</label>
                            <input type="datetime-local" name="end_date" class="form-control" required>
                        </div>
                        <button type="submit" name="add_coupon" class="btn-primary-full">Submit for Approval</button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
