<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'driver') {
    header("Location: ../auth/login.php");
    exit;
}

$driver_id = $_SESSION['user_id'];

// Fetch Completed Deliveries (DELIVERED)
$shipments = $database->getReference('shipment')->orderByChild('Driv_Id')->equalTo($driver_id)->getSnapshot()->getValue() ?: [];
$history_data = [];

foreach ($shipments as $ship) {
    if (($ship['Ship_Status'] ?? '') === 'DELIVERED') {
        $order_id = $ship['Order_Id'];
        
        $orderRef = $database->getReference('orders')->orderByChild('Order_Id')->equalTo($order_id)->getSnapshot()->getValue();
        if ($orderRef) {
            $order = current($orderRef);
            $addrs_id = $order['Addrs_Id'] ?? $order['Addrs_id'] ?? null;
            
            $addrRef = $database->getReference('address')->orderByChild('Addrs_id')->equalTo($addrs_id)->getSnapshot()->getValue();
            $address = $addrRef ? current($addrRef) : [];
            
            $history_data[] = [
                'Ship_DeliveredAt' => $ship['Ship_DeliveredAt'] ?? '',
                'Order_Id' => $order_id,
                'Addrs_Street' => $address['Addrs_Street'] ?? 'Unknown Street',
                'Addrs_RcpntName' => $address['Addrs_RcpntName'] ?? 'Unknown'
            ];
        }
    }
}

// Sort by delivery date descending
usort($history_data, function($a, $b) {
    return strtotime($b['Ship_DeliveredAt']) - strtotime($a['Ship_DeliveredAt']);
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Zalora Driver — History</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/seller.css">
    <link rel="stylesheet" href="../assets/css/driver.css?v=<?= time() ?>">
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-wrapper">
    <main class="main-content">
        
        <header class="history-header">
            <h1 class="page-title">DELIVERY HISTORY</h1>
            <span class="history-count"><?= count($history_data) ?> COMPLETED</span>
        </header>

        <table class="history-table">
            <thead>
                <tr>
                    <th>Date Completed</th>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Destination</th>
                    <th>Earnings</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($history_data) > 0): ?>
                    <?php foreach($history_data as $row): ?>
                    <tr>
                        <td><?= date('M d, Y • H:i', strtotime($row['Ship_DeliveredAt'])) ?></td>
                        <td><span style="font-weight:700;">#<?= $row['Order_Id'] ?></span></td>
                        <td><?= htmlspecialchars($row['Addrs_RcpntName']) ?></td>
                        <td style="color:#666; font-size:12px;"><?= htmlspecialchars($row['Addrs_Street']) ?></td>
                        <td class="payout-tag">+$15.00</td>
                        <td>
                            <span class="status-check">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                DELIVERED
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center; padding: 100px; color: #999;">
                            <p>No completed deliveries found yet. Start your first journey!</p>
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
