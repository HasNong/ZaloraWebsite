<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'driver') {
    header("Location: ../auth/login.php");
    exit;
}

$driver_id = $_SESSION['user_id'];

// Fetch Completed Deliveries (DELIVERED)
$query = "SELECT s.*, o.Order_TotalAmnt, a.Addrs_Street, a.Addrs_City, a.Addrs_RcpntName
          FROM shipment s
          JOIN ORDERS o ON s.Order_Id = o.Order_Id
          JOIN ADDRESS a ON o.Addrs_Id = a.Addrs_id
          WHERE s.Driv_Id = ? AND s.Ship_Status = 'DELIVERED'
          ORDER BY s.Ship_DeliveredAt DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $driver_id);
$stmt->execute();
$history = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Zalora Driver — History</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/seller.css">
    <style>
        .history-header { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 30px; }
        .history-count { font-size: 11px; font-weight: 700; color: var(--text-light); text-transform: uppercase; letter-spacing: 0.1em; }
        
        .history-table { 
            width: 100%; 
            border-collapse: collapse; 
            background: var(--white); 
            border: 1px solid var(--border); 
            border-radius: var(--radius-md);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }
        .history-table th { 
            text-align: left; 
            padding: 20px; 
            font-size: 10px; 
            font-weight: 700; 
            text-transform: uppercase; 
            letter-spacing: 0.1em; 
            color: var(--text-light); 
            border-bottom: 1px solid var(--border); 
        }
        .history-table td { padding: 25px 20px; border-bottom: 1px solid var(--border); font-size: 13px; }
        .status-check { color: var(--accent-green-text); font-weight: 700; font-size: 10px; text-transform: uppercase; display: flex; align-items: center; gap: 5px; }
        .payout-tag { color: var(--accent-green-text); font-weight: 700; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-wrapper">
    <main class="main-content">
        
        <header class="history-header">
            <h1 class="page-title">DELIVERY HISTORY</h1>
            <span class="history-count"><?= $history->num_rows ?> COMPLETED</span>
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
                <?php if ($history->num_rows > 0): ?>
                    <?php while($row = $history->fetch_assoc()): ?>
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
                    <?php endwhile; ?>
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
