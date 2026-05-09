<?php
session_start();
require_once '../config/db.php';

// Auth check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Fetch some quick stats
$total_sales = $conn->query("SELECT SUM(Order_TotalAmnt) as total FROM ORDERS WHERE Order_Status != 'CANCELLED'")->fetch_assoc()['total'];
$total_orders = $conn->query("SELECT COUNT(*) as count FROM ORDERS")->fetch_assoc()['count'];
$total_customers = $conn->query("SELECT COUNT(*) as count FROM customer")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Reports & Analytics</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .reports-container { padding: 40px; }
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 40px; }
        .stat-card { background: #fff; padding: 30px; border: 1px solid #eee; }
        .stat-label { font-size: 10px; font-weight: 800; color: #999; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 10px; display: block; }
        .stat-value { font-size: 24px; font-weight: 700; }
        
        .export-section { background: #fff; border: 1px solid #eee; padding: 40px; }
        .export-row { display: flex; justify-content: space-between; align-items: center; padding: 20px 0; border-bottom: 1px solid #f9f9f9; }
        .export-row:last-child { border-bottom: none; }
        .export-info h3 { font-size: 15px; margin-bottom: 5px; }
        .export-info p { font-size: 12px; color: #666; }
        .btn-export { background: #000; color: #fff; border: none; padding: 12px 25px; font-size: 10px; font-weight: 700; text-transform: uppercase; cursor: pointer; text-decoration: none; }
    </style>
</head>
<body class="admin-body">
    <div class="admin-layout">
        <?php include 'sidebar.php'; ?>
        
        <main class="main-content">
            <div class="reports-container">
                <h1 class="page-title">Reports & Analytics</h1>

                <div class="stats-grid">
                    <div class="stat-card">
                        <span class="stat-label">Total Revenue</span>
                        <div class="stat-value">$<?= number_format($total_sales, 2) ?></div>
                    </div>
                    <div class="stat-card">
                        <span class="stat-label">Total Orders</span>
                        <div class="stat-value"><?= number_format($total_orders) ?></div>
                    </div>
                    <div class="stat-card">
                        <span class="stat-label">Verified Customers</span>
                        <div class="stat-value"><?= number_format($total_customers) ?></div>
                    </div>
                </div>

                <div class="export-section">
                    <h2 style="font-size: 18px; font-weight: 700; margin-bottom: 30px; text-transform: uppercase; letter-spacing: 0.05em;">Generate CSV Reports</h2>
                    
                    <div class="export-row">
                        <div class="export-info">
                            <h3>Sales & Revenue Report</h3>
                            <p>Complete transaction history including customer details and payment status.</p>
                        </div>
                        <a href="export_api.php?type=sales" class="btn-export">Download CSV</a>
                    </div>

                    <div class="export-row">
                        <div class="export-info">
                            <h3>Global Inventory Report</h3>
                            <p>Current stock levels, pricing, and seller distribution across all categories.</p>
                        </div>
                        <a href="export_api.php?type=inventory" class="btn-export">Download CSV</a>
                    </div>

                    <div class="export-row">
                        <div class="export-info">
                            <h3>Logistics & Driver Performance</h3>
                            <p>Delivery success rates, driver balances, and fulfillment efficiency metrics.</p>
                        </div>
                        <a href="export_api.php?type=drivers" class="btn-export">Download CSV</a>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
