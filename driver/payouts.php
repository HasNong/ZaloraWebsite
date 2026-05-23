<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'driver') {
    header("Location: ../auth/login.php");
    exit;
}

$driver_id = $_SESSION['user_id'];

// Fetch Driver Financials
$drivers = $database->getReference('driver')->orderByChild('Driv_Id')->equalTo($driver_id)->getSnapshot()->getValue();
$driver = $drivers ? current($drivers) : [];

// Fetch Completed Earnings
$shipments = $database->getReference('shipment')->orderByChild('Driv_Id')->equalTo($driver_id)->getSnapshot()->getValue() ?: [];
$total_life = 0;
foreach ($shipments as $ship) {
    if (($ship['Ship_Status'] ?? '') === 'DELIVERED') {
        $total_life += 15.00;
    }
}
$earnings = ['total_life' => $total_life];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Zalora Driver — Payouts</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/seller.css">
    <link rel="stylesheet" href="../assets/css/driver.css?v=<?= time() ?>">
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-wrapper">
    <main class="main-content">
        
        <header class="section-header">
            <h1 class="page-title">PAYOUTS & EARNINGS</h1>
        </header>

        <div class="payout-grid">
            <div class="balance-card">
                <span class="balance-label">AVAILABLE FOR WITHDRAWAL</span>
                <p class="balance-value">$<?= number_format($driver['Driv_Balance'] ?? 0, 2) ?></p>
                <button class="balance-btn" onclick="alert('Transfer request sent to Admin!')">WITHDRAW TO BANK</button>
            </div>
            <div class="stat-card">
                <span class="stat-label">LIFETIME EARNINGS</span>
                <p class="stat-value">$<?= number_format($earnings['total_life'] ?? 0, 2) ?></p>
                <p style="font-size:12px; color:#999; margin-top:10px;">Based on completed deliveries at $15.00 each.</p>
            </div>
        </div>

        <div class="payout-history">
            <h3 style="font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:0.1em; margin-bottom:30px;">RECENT TRANSFERS</h3>
            <div style="text-align:center; padding: 60px; color:#999; background:#fafafa; border: 1px dashed #eee;">
                <p>No recent bank transfers found. Keep delivering to reach your first milestone!</p>
            </div>
        </div>

    </main>

    <footer class="seller-footer">
        <div class="footer-logo">ZALORA</div>
        <div class="footer-links">
            <a href="#">HELP & SUPPORT</a>
            <a href="#">BANKING SECURITY</a>
        </div>
    </footer>
</div>

</body>
</html>
