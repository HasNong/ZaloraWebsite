<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'driver') {
    header("Location: ../auth/login.php");
    exit;
}

$driver_id = $_SESSION['user_id'];

// Fetch All Active Deliveries (OUT_FOR_DELIVERY)
$query = "SELECT s.*, o.Order_TotalAmnt, a.Addrs_Street, a.Addrs_City, a.Addrs_ZipCode, a.Addrs_RcpntName
          FROM shipment s
          JOIN ORDERS o ON s.Order_Id = o.Order_Id
          JOIN ADDRESS a ON o.Addrs_Id = a.Addrs_id
          WHERE s.Driv_Id = ? AND s.Ship_Status = 'OUT_FOR_DELIVERY'
          ORDER BY s.Ship_Id ASC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $driver_id);
$stmt->execute();
$queue = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Zalora Driver — My Queue</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/seller.css">
    <style>
        .queue-header { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 30px; }
        .queue-count { font-size: 11px; font-weight: 800; color: #999; text-transform: uppercase; letter-spacing: 0.1em; }
        
        .queue-table { width: 100%; border-collapse: collapse; background: #fff; border: 1px solid #eee; }
        .queue-table th { text-align: left; padding: 20px; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; color: #999; border-bottom: 1px solid #eee; }
        .queue-table td { padding: 25px 20px; border-bottom: 1px solid #f9f9f9; font-size: 13px; }
        .order-pill { background: #f4f4f4; padding: 4px 8px; font-size: 10px; font-weight: 700; border-radius: 4px; }
        .addr-main { font-weight: 700; display: block; margin-bottom: 5px; }
        .addr-sub { font-size: 11px; color: #999; }
        .btn-action { background: #000; color: #fff; border: none; padding: 10px 15px; font-size: 10px; font-weight: 700; cursor: pointer; text-transform: uppercase; text-decoration: none; display: inline-block; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-wrapper">
    <main class="main-content">
        
        <header class="queue-header">
            <h1 class="page-title">MY DELIVERY QUEUE</h1>
            <span class="queue-count"><?= $queue->num_rows ?> ASSIGNMENTS</span>
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
                <?php if ($queue->num_rows > 0): ?>
                    <?php while($row = $queue->fetch_assoc()): ?>
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
                    <?php endwhile; ?>
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
