<?php
session_start();
require_once '../config/db.php';

// Auth check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'seller') {
    header("Location: ../auth/login.php");
    exit;
}

$seller_id = $_SESSION['user_id'];
$seller_name = $_SESSION['user_name'] ?? 'Seller';

// Handle New Coupon Submission
$msg = "";
if (isset($_POST['add_coupon'])) {
    $code = mysqli_real_escape_string($conn, $_POST['code']);
    $type = strtoupper(trim($_POST['type']));
    $val = floatval($_POST['value']);
    $min = floatval($_POST['min_spend']);
    $max = intval($_POST['max_uses']);
    $start = str_replace('T', ' ', $_POST['start_date']) . ':00';
    $end = str_replace('T', ' ', $_POST['end_date']) . ':00';
    $active = 1;
    $approved = 0;
    $used_count = 0;

    // Get next ID manually
    $res_id = $conn->query("SELECT MAX(Coup_Id) as max_id FROM coupon");
    $next_id = ($res_id->fetch_assoc()['max_id'] ?? 0) + 1;

    $query = "INSERT INTO coupon (Coup_Id, Seller_Id, Coup_Code, Coup_DiscType, Coup_DiscValue, Coup_MinOrderAmt, Coup_MaxUses, Coup_UsedCount, Coup_ValidFrom, Coup_ValidUntil, Coup_IsActive, Is_Approved) 
              VALUES ($next_id, $seller_id, '$code', '$type', $val, $min, $max, $used_count, '$start', '$end', $active, $approved)";
    
    if ($conn->query($query)) {
        $msg = "Coupon request submitted! Waiting for Admin approval.";
    } else {
        $msg = "Error: " . $conn->error;
    }
}

// Fetch Seller's Coupons
$query = "SELECT * FROM coupon WHERE Seller_Id = ? ORDER BY Coup_ValidFrom DESC";
$stmt_c = $conn->prepare($query);
$stmt_c->bind_param("i", $seller_id);
$stmt_c->execute();
$coupons = $stmt_c->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Promotions - Zalora Seller Center</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/seller.css">
    <style>
        /* FAIL-SAFE PROMO STYLES */
        :root { --black: #000; --white: #fff; --bg-light: #f9fafb; --border: #f1f1f1; --text-muted: #666; --text-light: #999; }
        body { margin: 0; font-family: 'Inter', sans-serif; background: var(--bg-light); display: flex; }
        .sidebar { width: 240px; background: var(--white); border-right: 1px solid var(--border); position: fixed; height: 100vh; z-index: 100; }
        .main-wrapper { margin-left: 240px; flex-grow: 1; padding: 40px; min-width: 0; }
        .promo-grid { display: grid; grid-template-columns: 1fr 380px; gap: 30px; margin-top: 30px; }
        .content-card { background: var(--white); padding: 30px; border: 1px solid var(--border); margin-bottom: 25px; }
        .card-title { font-size: 13px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 25px; color: var(--black); border-bottom: 2px solid #000; display: inline-block; padding-bottom: 5px; }
        .promo-table { width: 100%; border-collapse: collapse; }
        .promo-table th { text-align: left; font-size: 10px; color: var(--text-light); text-transform: uppercase; padding: 15px 0; border-bottom: 1px solid var(--border); letter-spacing: 0.1em; }
        .promo-table td { padding: 20px 0; border-bottom: 1px solid #fafafa; font-size: 13px; }
        .coupon-pill { font-family: monospace; font-weight: 700; background: #f8f9fa; padding: 6px 12px; border: 1px dashed #ced4da; border-radius: 4px; }
        .status-badge { padding: 6px 12px; border-radius: 20px; font-size: 10px; font-weight: 700; text-transform: uppercase; }
        .status-pending { background: #fffbeb; color: #d97706; }
        .status-approved { background: #f0fdf4; color: #16a34a; }
        .status-rejected { background: #fef2f2; color: #dc2626; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 10px; font-weight: 800; color: var(--text-light); margin-bottom: 8px; text-transform: uppercase; }
        .form-control { width: 100%; padding: 12px; border: 1px solid var(--border); background: #fafafa; font-family: inherit; font-size: 14px; box-sizing: border-box; }
        .btn-primary-full { width: 100%; background: #000; color: #fff; border: none; padding: 15px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; cursor: pointer; }
    </style>
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
                    <?= $msg ?>
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
                            <?php if ($coupons->num_rows > 0): ?>
                                <?php while($c = $coupons->fetch_assoc()): ?>
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
                                        <?php if ($c['Is_Approved'] == 1): ?>
                                            <span class="status-badge status-approved">Live</span>
                                        <?php elseif ($c['Is_Approved'] == 0): ?>
                                            <span class="status-badge status-pending">Pending Review</span>
                                        <?php else: ?>
                                            <span class="status-badge status-rejected">Rejected</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
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
