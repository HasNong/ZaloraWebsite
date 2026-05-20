<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'driver') {
    header("Location: ../auth/login.php");
    exit;
}

$driver_id = $_SESSION['user_id'];

// Fetch Driver Financials
$stmt = $conn->prepare("SELECT Driv_Balance FROM driver WHERE Driv_Id = ?");
$stmt->bind_param("i", $driver_id);
$stmt->execute();
$driver = $stmt->get_result()->fetch_assoc();

// Fetch Completed Earnings
$query_earn = "SELECT SUM(15.00) as total_life FROM shipment WHERE Driv_Id = ? AND Ship_Status = 'DELIVERED'";
$stmt_earn = $conn->prepare($query_earn);
$stmt_earn->bind_param("i", $driver_id);
$stmt_earn->execute();
$earnings = $stmt_earn->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Zalora Driver — Payouts</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/seller.css">
    <style>
        .payout-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 40px; }
        .balance-card { 
            background: var(--black); 
            color: var(--white); 
            padding: 40px; 
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
        }
        .balance-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }
        .balance-label { font-size: 11px; font-weight: 700; color: #888; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 30px; display: block; }
        .balance-value { font-size: 42px; font-weight: 800; margin: 0; }
        .balance-btn { 
            margin-top: 30px; 
            background: var(--white); 
            color: var(--black); 
            border: none; 
            padding: 15px 30px; 
            font-weight: 700; 
            font-size: 11px; 
            cursor: pointer; 
            text-transform: uppercase; 
            border-radius: var(--radius-sm);
            transition: var(--transition);
        }
        .balance-btn:hover {
            opacity: 0.9;
        }
        
        .stat-card { 
            background: var(--white); 
            border: 1px solid var(--border); 
            padding: 40px; 
            display: flex; 
            flex-direction: column; 
            justify-content: center; 
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
        }
        .stat-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }
        .stat-label { font-size: 11px; font-weight: 700; color: var(--text-light); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 10px; }
        .stat-value { font-size: 28px; font-weight: 800; }
        
        .payout-history { 
            background: var(--white); 
            border: 1px solid var(--border); 
            padding: 40px; 
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
        }
    </style>
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
