<?php
session_start();
require_once '../config/db.php';

// Auth check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Handle Approval / Rejection
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $action = $_GET['action'];

    $coupRef = $database->getReference('coupon')->orderByChild('Coup_Id')->equalTo(intval($id))->getSnapshot()->getValue();
    if ($coupRef) {
        $key = key($coupRef);
        $status = ($action === 'approve') ? 1 : -1;
        $database->getReference('coupon')->getChild($key)->update(['Is_Approved' => $status]);
    }
    header("Location: promotions.php");
    exit;
}

// Fetch Sellers for mapping
$all_sellers = fb_merge_nodes($database, 'seller');
$seller_map = [];
foreach ($all_sellers as $s) {
    if (isset($s['Sell_Id'])) {
        $seller_map[$s['Sell_Id']] = $s;
    }
}

// Fetch Coupons
$all_coupons = $database->getReference('coupon')->getSnapshot()->getValue() ?: [];
$pending = [];
$approved = [];

foreach ($all_coupons as $c) {
    if (!is_array($c)) {
        continue;
    }
    $seller_id = $c['Seller_Id'] ?? null;
    $seller = $seller_map[$seller_id] ?? [];
    $c['seller_name'] = $seller['Sell_BusinessName'] ?? 'Unknown Seller';

    if (($c['Is_Approved'] ?? 0) == 0) {
        $pending[] = $c;
    } elseif (($c['Is_Approved'] ?? 0) == 1) {
        $approved[] = $c;
    }
}
usort($pending, fn($a, $b) => strtotime($a['Coup_ValidFrom'] ?? 0) - strtotime($b['Coup_ValidFrom'] ?? 0));
usort($approved, fn($a, $b) => strtotime($b['Coup_ValidFrom'] ?? 0) - strtotime($a['Coup_ValidFrom'] ?? 0));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Promotions Approval - Zalora Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <meta charset="UTF-8">
    <title>Promotion Approval - Zalora Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <header class="header">
            <h1 class="page-title">PROMOTION APPROVAL CENTER</h1>
        </header>

        <!-- PENDING REQUESTS -->
        <div class="card">
            <div class="section-title">Pending Seller Requests</div>
            <?php if (count($pending) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Seller Identity</th>
                        <th>Voucher Code</th>
                        <th>Benefit Details</th>
                        <th>Validity Period</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($pending as $p): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($p['seller_name']) ?></strong>
                            <span class="seller-tag">SELLER ID: <?= $p['Seller_Id'] ?></span>
                        </td>
                        <td><span class="coupon-badge"><?= htmlspecialchars($p['Coup_Code']) ?></span></td>
                        <td>
                            <div style="font-weight: 700; font-size: 14px;">
                                <?= $p['Coup_DiscType'] === 'PERCENTAGE' ? $p['Coup_DiscValue'].'%' : '$'.number_format($p['Coup_DiscValue'], 2) ?>
                            </div>
                            <div style="font-size: 10px; color: var(--text-light); margin-top: 4px;">MIN. SPEND $<?= number_format($p['Coup_MinOrderAmt'], 2) ?></div>
                        </td>
                        <td>
                            <div style="font-size: 11px;">START: <?= date('M j, Y H:i', strtotime($p['Coup_ValidFrom'])) ?></div>
                            <div style="font-size: 11px; color: var(--text-light); margin-top: 4px;">END: <?= date('M j, Y H:i', strtotime($p['Coup_ValidUntil'])) ?></div>
                        </td>
                        <td style="text-align: right;">
                            <a href="promotions.php?action=approve&id=<?= $p['Coup_Id'] ?>" class="btn-action btn-approve">Approve</a>
                            <a href="promotions.php?action=reject&id=<?= $p['Coup_Id'] ?>" class="btn-action btn-reject">Reject</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <div style="text-align: center; padding: 60px; color: var(--text-light); font-size: 13px;">
                    All seller promotion requests have been processed.
                </div>
            <?php endif; ?>
        </div>

        <!-- LIVE COUPONS -->
        <div class="card">
            <div class="section-title">Active Marketplace Vouchers</div>
            <table>
                <thead>
                    <tr>
                        <th>Source Seller</th>
                        <th>Voucher Code</th>
                        <th>Status</th>
                        <th style="text-align: right;">Performance Stats</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($approved) > 0): ?>
                        <?php foreach ($approved as $a): ?>
                        <tr>
                            <td><?= htmlspecialchars($a['seller_name']) ?></td>
                            <td><span class="coupon-badge"><?= htmlspecialchars($a['Coup_Code']) ?></span></td>
                            <td><span class="status-active">LIVE</span></td>
                            <td style="text-align: right; font-weight: 600;"><?= $a['Coup_UsedCount'] ?> / <?= $a['Coup_MaxUses'] ?> <span style="font-size: 10px; color: var(--text-light);">REDEEMED</span></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align:center; padding: 40px; color: var(--text-light);">No live vouchers currently active on the platform.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
