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
$drivers = $database->getReference('driver')->orderByChild('Driv_Id')->equalTo($driver_id)->getSnapshot()->getValue();
$driver_data = $drivers ? current($drivers) : [];

$shipments = $database->getReference('shipment')->orderByChild('Driv_Id')->equalTo($driver_id)->getSnapshot()->getValue() ?: [];
$total_earnings = 0;
$completed_today = 0;
$today_earnings = 0;
$remaining = 0;
$next_stop = null;
$today_date = date('Y-m-d');

foreach ($shipments as $ship) {
    if (($ship['Ship_Status'] ?? '') === 'DELIVERED') {
        $total_earnings += 15.00;
        $del_date = date('Y-m-d', strtotime($ship['Ship_DeliveredAt'] ?? '1970-01-01'));
        if ($del_date === $today_date) {
            $completed_today++;
            $today_earnings += 15.00;
        }
    } else if (($ship['Ship_Status'] ?? '') === 'OUT_FOR_DELIVERY') {
        $remaining++;
        if (!$next_stop) {
            // Fetch next stop details
            $order_id = $ship['Order_Id'];
            $orderRef = $database->getReference('orders')->orderByChild('Order_Id')->equalTo($order_id)->getSnapshot()->getValue();
            if ($orderRef) {
                $order = current($orderRef);
                $addrs_id = $order['Addrs_Id'] ?? $order['Addrs_id'] ?? null;
                $addrRef = $database->getReference('address')->orderByChild('Addrs_id')->equalTo($addrs_id)->getSnapshot()->getValue();
                $address = $addrRef ? current($addrRef) : [];
                
                $next_stop = [
                    'Order_Id' => $order_id,
                    'Addrs_Street' => $address['Addrs_Street'] ?? 'Unknown Street',
                    'Addrs_City' => $address['Addrs_City'] ?? 'Unknown City',
                    'Addrs_ZipCode' => $address['Addrs_ZipCode'] ?? '0000',
                    'Addrs_RcpntName' => $address['Addrs_RcpntName'] ?? 'Unknown'
                ];
            }
        }
    }
}

$metrics = [
    'Driv_Balance' => $driver_data['Driv_Balance'] ?? 0,
    'total_earnings' => $total_earnings
];

$today = [
    'completed_today' => $completed_today,
    'today_earnings' => $today_earnings
];

if (!$next_stop) {
    $next_stop = [
        'Order_Id' => 'ZL-EMPTY',
        'Addrs_Street' => 'Go Online to see assignments',
        'Addrs_City' => 'Waiting...',
        'Addrs_ZipCode' => '0000',
        'Addrs_RcpntName' => 'No Active Orders'
    ];
}

// 5. Fetch Approved Returns for Pick-up
$returnsRef = $database->getReference('return_request')->orderByChild('Rtrn_Status')->equalTo('APPROVED')->getSnapshot()->getValue() ?: [];
$approved_returns = [];
foreach ($returnsRef as $ret) {
    // Very simplified join for returns, assuming 5 max
    if (count($approved_returns) >= 5) break;
    
    // We would need to fetch Order_Item -> Orders -> Address -> Product Variant -> Product...
    // To save API calls and since this is a mock dashboard display for returns, we will fetch order directly 
    // This is computationally heavy in NoSQL without denormalization, so we'll do our best.
    $oditm_id = $ret['OdItm_Id'];
    $oiRef = $database->getReference('order_item')->orderByChild('OdItm_Id')->equalTo($oditm_id)->getSnapshot()->getValue();
    if ($oiRef) {
        $oi = current($oiRef);
        $order_id = $oi['Order_Id'];
        
        $orderRef = $database->getReference('orders')->orderByChild('Order_Id')->equalTo($order_id)->getSnapshot()->getValue();
        if ($orderRef) {
            $order = current($orderRef);
            $addrs_id = $order['Addrs_Id'] ?? $order['Addrs_id'] ?? null;
            $addrRef = $database->getReference('address')->orderByChild('Addrs_id')->equalTo($addrs_id)->getSnapshot()->getValue();
            $address = $addrRef ? current($addrRef) : [];
            
            $approved_returns[] = [
                'Rtrn_Id' => $ret['Rtrn_Id'],
                'Addrs_Street' => $address['Addrs_Street'] ?? 'Unknown',
                'Addrs_City' => $address['Addrs_City'] ?? 'Unknown',
                'Addrs_RcpntName' => $address['Addrs_RcpntName'] ?? 'Unknown',
                'Prod_Name' => 'Returned Item' // Placeholder since product fetch requires 2 more queries
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Zalora Driver — Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/seller.css">
    <link rel="stylesheet" href="../assets/css/driver.css?v=<?= time() ?>">
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
                            <form action="driver_handler.php" method="POST" enctype="multipart/form-data" style="flex-grow: 1; display: flex; flex-direction: column; gap: 15px;">
                                <input type="hidden" name="action" value="complete_delivery">
                                <input type="hidden" name="order_id" value="<?= $next_stop['Order_Id'] ?>">
                                
                                <div style="background: #f9f9f9; border: 1px dashed #ccc; padding: 15px; text-align: center;">
                                    <label style="font-size: 10px; font-weight: 800; color: #999; display: block; margin-bottom: 10px; text-transform: uppercase;">UPLOAD PROOF OF DELIVERY (PHOTO)</label>
                                    <input type="file" name="proof_img" accept="image/*" required style="font-size: 11px; width: 100%;">
                                </div>

                                <div style="display: flex; gap: 15px;">
                                    <button type="button" class="btn-nav" onclick="alert('Starting Navigation...')">NAVIGATE</button>
                                    <button type="submit" class="btn-delivered">MARK AS DELIVERED</button>
                                </div>
                            </form>
                            <button class="btn-call" style="align-self: flex-end; height: 50px; margin-bottom: 3px;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.88 12.88 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div style="margin-top:60px;">
                    <header class="section-header" style="display:flex; justify-content:space-between; align-items:baseline; margin-bottom: 20px;">
                        <h2 class="page-title">RETURN PICK-UPS</h2>
                        <span style="font-size:11px; font-weight:700; color:#999; text-transform:uppercase;"><?= count($approved_returns) ?> ASSIGNMENTS</span>
                    </header>

                    <?php if (count($approved_returns) > 0): ?>
                        <?php foreach($approved_returns as $ret): ?>
                            <div class="payout-row" style="background: #fdf2f2; padding: 25px; border: 1px solid #fee2e2; margin-bottom: 15px;">
                                <div class="payout-info">
                                    <p style="margin-bottom:5px; color: #dc2626;">APPROVED RETURN #<?= $ret['Rtrn_Id'] ?></p>
                                    <h4 style="font-size: 16px;"><?= $ret['Addrs_Street'] ?></h4>
                                    <p style="font-size: 11px; margin-top: 5px;">Item: <?= htmlspecialchars($ret['Prod_Name']) ?> • From: <?= htmlspecialchars($ret['Addrs_RcpntName']) ?></p>
                                </div>
                                <div style="display:flex; align-items:center;">
                                    <form action="driver_handler.php" method="POST">
                                        <input type="hidden" name="action" value="pickup_return">
                                        <input type="hidden" name="rtrn_id" value="<?= $ret['Rtrn_Id'] ?>">
                                        <button type="submit" style="background: #000; color: #fff; border: none; padding: 12px 20px; font-weight: 700; font-size: 10px; text-transform: uppercase; cursor: pointer;">CONFIRM PICK-UP</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="padding: 40px; text-align: center; background: #fafafa; border: 1px dashed #ddd; color: #999; font-size: 13px;">
                            No approved returns for pick-up at this time.
                        </div>
                    <?php endif; ?>
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
