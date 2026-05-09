<?php
session_start();
require_once '../config/db.php';

// Mock Auth for Driver (We'll implement real auth later)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Temporary mock
    $_SESSION['user_name'] = "CARL MALALAY";
    $_SESSION['role'] = "driver";
}

$driver_id = $_SESSION['user_id'];

// 1. Fetch Real Metrics
$stmt_metrics = $conn->prepare("SELECT Driv_Balance, 
                                (SELECT SUM(15.00) FROM shipment WHERE Driv_Id = ? AND Ship_Status = 'DELIVERED') as total_earnings 
                                FROM driver WHERE Driv_Id = ?");
$stmt_metrics->bind_param("ii", $driver_id, $driver_id);
$stmt_metrics->execute();
$metrics = $stmt_metrics->get_result()->fetch_assoc();

// 2. Fetch Today's Stats
$stmt_today = $conn->prepare("SELECT COUNT(*) as completed_today, SUM(15.00) as today_earnings 
                             FROM shipment 
                             WHERE Driv_Id = ? AND Ship_Status = 'DELIVERED' 
                             AND DATE(Ship_DeliveredAt) = CURDATE()");
$stmt_today->bind_param("i", $driver_id);
$stmt_today->execute();
$today = $stmt_today->get_result()->fetch_assoc();

// 3. Remaining Queue Count
$stmt_rem = $conn->prepare("SELECT COUNT(*) as remaining FROM shipment WHERE Driv_Id = ? AND Ship_Status = 'OUT_FOR_DELIVERY'");
$stmt_rem->bind_param("i", $driver_id);
$stmt_rem->execute();
$remaining = $stmt_rem->get_result()->fetch_assoc()['remaining'];

// 4. Fetch Next Stop (First OUT_FOR_DELIVERY for this driver)
$query_next = "SELECT o.Order_Id, o.Order_TotalAmnt, a.Addrs_Street, a.Addrs_City, a.Addrs_ZipCode, a.Addrs_RcpntName
               FROM shipment s
               JOIN ORDERS o ON s.Order_Id = o.Order_Id
               JOIN ADDRESS a ON o.Addrs_Id = a.Addrs_Id
               WHERE s.Driv_Id = ? AND s.Ship_Status = 'OUT_FOR_DELIVERY'
               LIMIT 1";
$stmt_next = $conn->prepare($query_next);
$stmt_next->bind_param("i", $driver_id);
$stmt_next->execute();
$next_stop = $stmt_next->get_result()->fetch_assoc();

// Mock Data if empty
if (!$next_stop) {
    $next_stop = [
        'Order_Id' => 'ZL-EMPTY',
        'Addrs_Street' => 'Go Online to see assignments',
        'Addrs_City' => 'Waiting...',
        'Addrs_ZipCode' => '0000',
        'Addrs_RcpntName' => 'No Active Orders'
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Zalora Driver — Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/seller.css">
    <style>
        .driver-metrics { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 40px; }
        .d-metric-card { background: #fff; padding: 30px; border: 1px solid #eee; }
        .d-metric-card.dark { background: #000; color: #fff; border: none; }
        .d-metric-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #999; margin-bottom: 25px; display: block; }
        .d-metric-card.dark .d-metric-label { color: #666; }
        .d-metric-value { font-size: 28px; font-weight: 800; margin: 0; }
        .d-metric-sub { font-size: 11px; color: #999; margin-top: 10px; }

        .dashboard-grid { display: grid; grid-template-columns: 1fr 380px; gap: 30px; }
        
        /* Next Stop Card */
        .next-stop-card { background: #fff; border: 1px solid #eee; display: flex; overflow: hidden; margin-top: 20px; }
        .ns-img { width: 300px; height: 320px; object-fit: cover; }
        .ns-content { flex-grow: 1; padding: 40px; position: relative; }
        .badge-next { background: #000; color: #fff; padding: 4px 10px; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; }
        .order-tag { font-size: 11px; font-weight: 700; color: #999; text-align: right; float: right; }
        .ns-address { font-size: 20px; font-weight: 700; margin: 30px 0 15px; }
        .ns-notes { font-size: 13px; color: #666; line-height: 1.6; margin-bottom: 30px; }
        .ns-actions { display: flex; gap: 15px; }
        .btn-nav { flex-grow: 1; background: #000; color: #fff; border: none; padding: 18px; font-weight: 700; text-transform: uppercase; cursor: pointer; }
        .btn-delivered { flex-grow: 1; background: #22c55e; color: #fff; border: none; padding: 18px; font-weight: 700; text-transform: uppercase; cursor: pointer; }
        .btn-call { width: 60px; background: #fff; border: 1px solid #000; display: flex; align-items: center; justify-content: center; cursor: pointer; }

        /* Map Card */
        .map-card { background: #eee; height: 400px; position: relative; border: 1px solid #ddd; overflow: hidden; }
        .map-overlay { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #000; color: #fff; padding: 10px 20px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; cursor: pointer; }

        /* Payouts */
        .payout-list { margin-top: 30px; }
        .payout-row { display: flex; justify-content: space-between; padding: 20px 0; border-bottom: 1px solid #f9f9f9; }
        .payout-info h4 { font-size: 14px; margin-bottom: 4px; }
        .payout-info p { font-size: 10px; color: #999; text-transform: uppercase; font-weight: 700; }
        .payout-amount { font-size: 15px; font-weight: 700; }

        .btn-go-online { width: 100%; background: #000; color: #fff; border: none; padding: 15px; font-weight: 700; text-transform: uppercase; cursor: pointer; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-wrapper">
    <main class="main-content">
        
        <?php if (isset($_SESSION['success'])): ?>
            <div style="background: #f0fdf4; color: #16a34a; padding: 20px; border-left: 5px solid #16a34a; margin-bottom: 30px; font-size: 13px; font-weight: 600;">
                <?= $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <div class="driver-metrics">
            <div class="d-metric-card">
                <span class="d-metric-label">CURRENT BALANCE</span>
                <p class="d-metric-value">$<?= number_format($metrics['Driv_Balance'] ?? 4850.24, 2) ?></p>
                <p class="d-metric-sub">+12.5% FROM LAST WEEK</p>
            </div>
            <div class="d-metric-card dark">
                <span class="d-metric-label">TODAY'S EARNINGS</span>
                <p class="d-metric-value">$<?= number_format($today['today_earnings'] ?? 0, 2) ?></p>
                <p class="d-metric-sub"><?= $today['completed_today'] ?? 0 ?> DELIVERIES COMPLETED</p>
            </div>
            <div class="d-metric-card">
                <span class="d-metric-label">LIFETIME REVENUE</span>
                <p class="d-metric-value">$<?= number_format($metrics['total_earnings'] ?? 0, 2) ?></p>
                <p class="d-metric-sub">TOTAL EARNED TO DATE</p>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="grid-left">
                <header class="section-header" style="display:flex; justify-content:space-between; align-items:baseline;">
                    <h2 class="page-title">ACTIVE QUEUE</h2>
                    <span style="font-size:11px; font-weight:700; color:#999; text-transform:uppercase;"><?= $remaining ?> DELIVERIES REMAINING</span>
                </header>

                <div class="next-stop-card">
                    <img src="https://images.unsplash.com/photo-1449844908441-8829872d2607?auto=format&fit=crop&w=600&q=80" class="ns-img" alt="Property View">
                    <div class="ns-content">
                        <span class="badge-next">NEXT STOP</span>
                        <span class="order-tag">ORDER #<?= $next_stop['Order_Id'] ?></span>
                        
                        <h3 class="ns-address"><?= $next_stop['Addrs_Street'] ?></h3>
                        <p class="ns-notes">Gate code required: <?= $next_stop['Addrs_ZipCode'] ?>. Please leave at front desk if security is present.</p>
                        
                        <div class="ns-actions">
                            <form action="driver_handler.php" method="POST" style="flex-grow: 1; display: flex; gap: 15px;">
                                <input type="hidden" name="action" value="complete_delivery">
                                <input type="hidden" name="order_id" value="<?= $next_stop['Order_Id'] ?>">
                                <button type="button" class="btn-nav" onclick="alert('Starting Navigation...')">NAVIGATE</button>
                                <button type="submit" class="btn-delivered">MARK AS DELIVERED</button>
                            </form>
                            <button class="btn-call">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.88 12.88 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div style="margin-top:40px;">
                    <div class="payout-row" style="color:#ccc; opacity:0.6;">
                        <div class="payout-info">
                            <p style="margin-bottom:5px;">ETA 14:20</p>
                            <h4>128 Oak Avenue, Apt 4C</h4>
                        </div>
                        <div style="display:flex; align-items:center; gap:10px;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>
                            <span style="font-size:11px; font-weight:700;">2 ITEMS</span>
                        </div>
                    </div>
                    <div class="payout-row" style="color:#ccc; opacity:0.6;">
                        <div class="payout-info">
                            <p style="margin-bottom:5px;">ETA 14:45</p>
                            <h4>The Collective Hub, Ground Floor</h4>
                        </div>
                        <div style="display:flex; align-items:center; gap:10px;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>
                            <span style="font-size:11px; font-weight:700;">1 ITEM</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid-right">
                <div class="map-card">
                    <img src="https://api.mapbox.com/styles/v1/mapbox/dark-v10/static/103.8198,1.3521,11,0/380x400?access_token=mock" style="width:100%; height:100%; object-fit:cover; filter: grayscale(1) contrast(1.2);" alt="Map View">
                    <div class="map-overlay">ROUTE OVERVIEW</div>
                </div>

                <div class="content-card" style="margin-top:30px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                        <h3 class="card-title" style="margin:0; border:none; padding:0;">RECENT PAYOUTS</h3>
                        <a href="#" style="font-size:10px; font-weight:800; color:#000; text-decoration:none;">VIEW ALL</a>
                    </div>
                    
                    <div class="payout-list">
                        <div class="payout-row">
                            <div class="payout-info">
                                <h4>Week of Oct 07 - 14</h4>
                                <p>TRANSFERRED TO BANK **** 9012</p>
                            </div>
                            <span class="payout-amount">$1,452.10</span>
                        </div>
                        <div class="payout-row">
                            <div class="payout-info">
                                <h4>Week of Sep 30 - 07</h4>
                                <p>TRANSFERRED TO BANK **** 9012</p>
                            </div>
                            <span class="payout-amount">$1,290.45</span>
                        </div>
                        <div class="payout-row">
                            <div class="payout-info">
                                <h4>Week of Sep 23 - 30</h4>
                                <p>TRANSFERRED TO BANK **** 9012</p>
                            </div>
                            <span class="payout-amount">$1,580.00</span>
                        </div>
                    </div>
                </div>

                <div class="content-card" style="background:#f9f9f9; border:none; margin-top:30px;">
                    <h3 class="card-title" style="border:none; padding:0;">Need Assistance?</h3>
                    <p style="font-size:13px; color:#666; line-height:1.6; margin-bottom:25px;">Connect with a dispatcher immediately for route issues or delivery problems.</p>
                    <button style="width:100%; background:none; border:1px solid #ccc; padding:15px; font-weight:700; text-transform:uppercase; font-size:11px; cursor:pointer;">CONTACT SUPPORT</button>
                </div>
            </div>
        </div>

    </main>

    <footer class="seller-footer">
        <div class="footer-logo">ZALORA</div>
        <div class="footer-links">
            <a href="#">HELP & SUPPORT</a>
            <a href="#">SIZE GUIDE</a>
            <a href="#">RETURNS & REFUNDS</a>
            <a href="#">CONTACT US</a>
            <a href="#">TERMS & CONDITIONS</a>
        </div>
    </footer>
</div>

<script>
function toggleOnline() {
    const btn = document.querySelector('.btn-go-online');
    if (btn.innerText === 'GO ONLINE') {
        btn.innerText = 'GO OFFLINE';
        btn.style.background = '#ef4444';
    } else {
        btn.innerText = 'GO ONLINE';
        btn.style.background = '#000';
    }
}
</script>

</body>
</html>
