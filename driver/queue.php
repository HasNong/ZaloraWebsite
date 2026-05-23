<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'driver') {
    header("Location: ../auth/login.php");
    exit;
}

$driver_id = $_SESSION['user_id'];

// Fetch All Active Deliveries (OUT_FOR_DELIVERY)
$shipments = $database->getReference('shipment')->orderByChild('Driv_Id')->equalTo($driver_id)->getSnapshot()->getValue() ?: [];
$queue_data = [];

foreach ($shipments as $ship) {
    if (($ship['Ship_Status'] ?? '') === 'OUT_FOR_DELIVERY') {
        $order_id = $ship['Order_Id'];
        
        $orderRef = $database->getReference('orders')->orderByChild('Order_Id')->equalTo($order_id)->getSnapshot()->getValue();
        if ($orderRef) {
            $order = current($orderRef);
            $addrs_id = $order['Addrs_Id'] ?? $order['Addrs_id'] ?? null;
            
            $addrRef = $database->getReference('address')->orderByChild('Addrs_id')->equalTo($addrs_id)->getSnapshot()->getValue();
            $address = $addrRef ? current($addrRef) : [];
            
            $queue_data[] = [
                'Order_Id' => $order_id,
                'Order_TotalAmnt' => $order['Order_TotalAmnt'] ?? 0,
                'Addrs_Street' => $address['Addrs_Street'] ?? 'Unknown Street',
                'Addrs_City' => $address['Addrs_City'] ?? 'Unknown City',
                'Addrs_ZipCode' => $address['Addrs_ZipCode'] ?? '0000',
                'Addrs_RcpntName' => $address['Addrs_RcpntName'] ?? 'Unknown'
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Zalora Driver — My Queue</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/seller.css">
    <link rel="stylesheet" href="../assets/css/driver.css?v=<?= time() ?>">
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-wrapper">
    <main class="main-content">
        
        <header class="queue-header">
            <h1 class="page-title">MY DELIVERY QUEUE</h1>
            <span class="queue-count"><?= count($queue_data) ?> ASSIGNMENTS</span>
        </header>

        <table class="queue-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Recipient</th>
                    <th>Destination</th>
                    <th>Amount</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($queue_data) > 0): ?>
                    <?php foreach($queue_data as $row): ?>
                    <tr>
                        <td><span class="order-pill">#<?= $row['Order_Id'] ?></span></td>
                        <td><?= htmlspecialchars($row['Addrs_RcpntName']) ?></td>
                        <td>
                            <span class="addr-main"><?= htmlspecialchars($row['Addrs_Street']) ?></span>
                            <span class="addr-sub"><?= htmlspecialchars($row['Addrs_City']) ?>, <?= htmlspecialchars($row['Addrs_ZipCode']) ?></span>
                        </td>
                        <td style="font-weight:700;">$<?= number_format($row['Order_TotalAmnt'], 2) ?></td>
                        <td>
                            <a href="dashboard.php" class="btn-action">VIEW ON MAP</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align:center; padding: 100px; color: #999;">
                            <p>Your queue is currently empty. Go online to receive new orders.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

    </main>

    <footer class="seller-footer">
        <div class="footer-logo">ZALORA</div>
        <div class="footer-links">
            <a href="#">HELP & SUPPORT</a>
            <a href="#">CONTACT US</a>
            <a href="#">TERMS & CONDITIONS</a>
        </div>
    </footer>
</div>

</body>
</html>
