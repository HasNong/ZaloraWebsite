<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}
require_once '../config/db.php';

// Stats
$total_customers = $conn->query("SELECT COUNT(*) as count FROM CUSTOMER")->fetch_assoc()['count'];
$total_sellers = $conn->query("SELECT COUNT(*) as count FROM SELLER")->fetch_assoc()['count'];
$total_products = $conn->query("SELECT COUNT(*) as count FROM PRODUCT")->fetch_assoc()['count'];
$total_orders = $conn->query("SELECT COUNT(*) as count FROM ORDERS")->fetch_assoc()['count'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Zalora</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <header class="header">
        <h1 class="page-title">Dashboard Overview</h1>
        <div style="font-size: 12px; color: #888;">Logged in as: <strong><?= htmlspecialchars($_SESSION['admin_email']) ?></strong></div>
    </header>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total Customers</div>
            <div class="stat-value"><?= $total_customers ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Sellers</div>
            <div class="stat-value"><?= $total_sellers ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Live Products</div>
            <div class="stat-value"><?= $total_products ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Orders</div>
            <div class="stat-value"><?= $total_orders ?></div>
        </div>
    </div>

    <h2 class="section-title">System Status</h2>
    <div style="background: white; padding: 2rem; border: 1px solid #eee;">
        <p style="font-size: 14px; color: #555;">All systems operational. Seller Center synchronization active.</p>
    </div>
</div>

</body>
</html>
