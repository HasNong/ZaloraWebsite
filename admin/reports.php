<?php
session_start();
require_once '../config/db.php';

// Auth check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Fetch some quick stats
$all_orders_raw = array_merge(
    $database->getReference('orders')->getSnapshot()->getValue() ?: [],
    $database->getReference('orders')->getSnapshot()->getValue() ?: []
);

$total_sales = array_sum(array_map(
    fn($o) => ($o['Order_Status'] ?? '') !== 'CANCELLED' ? floatval($o['Order_TotalAmnt'] ?? 0) : 0,
    $all_orders_raw
));
$total_orders = count($all_orders_raw);

$all_customers_raw = array_merge(
    $database->getReference('customer')->getSnapshot()->getValue() ?: [],
    $database->getReference('customer')->getSnapshot()->getValue() ?: []
);
$total_customers = count($all_customers_raw);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Reports & Analytics</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/admin-reports.css?v=<?= time() ?>">
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
