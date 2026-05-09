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
$total_revenue = $conn->query("SELECT SUM(Order_TotalAmnt) as total FROM ORDERS WHERE Order_Status != 'CANCELLED'")->fetch_assoc()['total'] ?? 0;

// Recent Orders
$recent_orders = $conn->query("SELECT o.*, c.Cust_Firstname, c.Cust_Lastname 
                              FROM ORDERS o 
                              JOIN CUSTOMER c ON o.Cust_Id = c.Cust_Id 
                              ORDER BY o.Order_PlacedAt DESC LIMIT 5");

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

    <div class="stats-grid" style="grid-template-columns: repeat(5, 1fr);">
        <div class="stat-card">
            <div class="stat-label">Total Revenue</div>
            <div class="stat-value" style="color: #10b981;">$<?= number_format($total_revenue, 2) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Orders</div>
            <div class="stat-value"><?= $total_orders ?></div>
        </div>
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
    </div>

    <h2 class="section-title">Recent Orders</h2>
    <div style="background: white; border: 1px solid #eee; overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; font-size: 14px; text-align: left;">
            <thead>
                <tr style="background: #fcfcfc; border-bottom: 1px solid #eee;">
                    <th style="padding: 1rem;">Order ID</th>
                    <th style="padding: 1rem;">Customer</th>
                    <th style="padding: 1rem;">Date</th>
                    <th style="padding: 1rem;">Amount</th>
                    <th style="padding: 1rem;">Status</th>
                    <th style="padding: 1rem;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($recent_orders->num_rows > 0): ?>
                    <?php while($row = $recent_orders->fetch_assoc()): ?>
                    <tr style="border-bottom: 1px solid #fafafa;">
                        <td style="padding: 1rem; font-weight: 600;">#<?= $row['Order_Id'] ?></td>
                        <td style="padding: 1rem;"><?= htmlspecialchars($row['Cust_Firstname'] . ' ' . $row['Cust_Lastname']) ?></td>
                        <td style="padding: 1rem; color: #888;"><?= date('M j, Y', strtotime($row['Order_PlacedAt'])) ?></td>
                        <td style="padding: 1rem; font-weight: 600;">$<?= number_format($row['Order_TotalAmnt'], 2) ?></td>
                        <td style="padding: 1rem;">
                            <span style="padding: 4px 10px; font-size: 11px; font-weight: 700; background: #000; color: #fff; border-radius: 4px;">
                                <?= $row['Order_Status'] ?>
                            </span>
                        </td>
                        <td style="padding: 1rem;">
                            <a href="orders.php?id=<?= $row['Order_Id'] ?>" style="color: #000; text-decoration: underline; font-size: 12px; font-weight: 600;">Manage</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="padding: 2rem; text-align: center; color: #999; font-style: italic;">No orders found yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
