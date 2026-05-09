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
    $id = intval($_GET['id']);
    $action = $_GET['action'];
    
    if ($action === 'approve') {
        $conn->query("UPDATE coupon SET Is_Approved = 1 WHERE Coup_Id = $id");
    } elseif ($action === 'reject') {
        $conn->query("UPDATE coupon SET Is_Approved = -1 WHERE Coup_Id = $id");
    }
    header("Location: promotions.php");
    exit;
}

// Fetch Pending Requests
$query = "SELECT c.*, s.Cust_Firstname as seller_fname, s.Cust_Lastname as seller_lname 
          FROM coupon c 
          LEFT JOIN customer s ON c.Seller_Id = s.Cust_Id 
          WHERE c.Is_Approved = 0 
          ORDER BY c.Coup_ValidFrom ASC";
$pending = $conn->query($query);

// Fetch Approved Coupons
$query_app = "SELECT c.*, s.Cust_Firstname as seller_fname, s.Cust_Lastname as seller_lname 
              FROM coupon c 
              LEFT JOIN customer s ON c.Seller_Id = s.Cust_Id 
              WHERE c.Is_Approved = 1 
              ORDER BY c.Coup_ValidFrom DESC";
$approved = $conn->query($query_app);
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
            <?php if ($pending->num_rows > 0): ?>
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
                    <?php while($p = $pending->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($p['seller_fname'] . ' ' . $p['seller_lname']) ?></strong>
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
                    <?php endwhile; ?>
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
                    <?php if ($approved->num_rows > 0): ?>
                        <?php while($a = $approved->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($a['seller_fname'] . ' ' . $a['seller_lname']) ?></td>
                            <td><span class="coupon-badge"><?= htmlspecialchars($a['Coup_Code']) ?></span></td>
                            <td><span class="status-active">LIVE</span></td>
                            <td style="text-align: right; font-weight: 600;"><?= $a['Coup_UsedCount'] ?> / <?= $a['Coup_MaxUses'] ?> <span style="font-size: 10px; color: var(--text-light);">REDEEMED</span></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align:center; padding: 40px; color: var(--text-light);">No live vouchers currently active on the platform.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
