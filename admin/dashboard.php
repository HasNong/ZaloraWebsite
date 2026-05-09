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

// Daily Sales (Last 7 Days)
$sales_query = "SELECT DATE(Order_PlacedAt) as date, SUM(Order_TotalAmnt) as total 
                FROM ORDERS 
                WHERE Order_PlacedAt >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                AND Order_Status != 'CANCELLED'
                GROUP BY DATE(Order_PlacedAt)
                ORDER BY date ASC";
$sales_res = $conn->query($sales_query);
$sales_data = [];
$sales_labels = [];
while($s = $sales_res->fetch_assoc()) {
    $sales_labels[] = date('M d', strtotime($s['date']));
    $sales_data[] = $s['total'];
}

// Category Distribution
$cat_query = "SELECT c.Ctgry_Name, COUNT(p.Prod_Id) as count 
              FROM CATEGORY c 
              JOIN PRODUCT p ON c.Ctgry_Id = p.Ctgry_Id 
              GROUP BY c.Ctgry_Id";
$cat_res = $conn->query($cat_query);
$cat_data = [];
$cat_labels = [];
while($c = $cat_res->fetch_assoc()) {
    $cat_labels[] = $c['Ctgry_Name'];
    $cat_data[] = $c['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Zalora</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

    <div class="charts-grid" style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px; margin-bottom: 40px;">
        <div class="stat-card">
            <h3 class="section-title" style="margin-top: 0; margin-bottom: 20px;">Revenue Trend (Last 7 Days)</h3>
            <canvas id="salesChart" height="120"></canvas>
        </div>
        <div class="stat-card">
            <h3 class="section-title" style="margin-top: 0; margin-bottom: 20px;">Catalog by Category</h3>
            <canvas id="catChart"></canvas>
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

<script>
    // Sales Chart
    const ctxSales = document.getElementById('salesChart').getContext('2d');
    new Chart(ctxSales, {
        type: 'line',
        data: {
            labels: <?= json_encode($sales_labels) ?>,
            datasets: [{
                label: 'Daily Revenue ($)',
                data: <?= json_encode($sales_data) ?>,
                borderColor: '#000',
                backgroundColor: 'rgba(0,0,0,0.05)',
                borderWidth: 3,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: '#f5f5f5' } },
                x: { grid: { display: false } }
            }
        }
    });

    // Category Chart
    const ctxCat = document.getElementById('catChart').getContext('2d');
    new Chart(ctxCat, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($cat_labels) ?>,
            datasets: [{
                data: <?= json_encode($cat_data) ?>,
                backgroundColor: ['#000', '#333', '#666', '#999', '#ccc'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } }
            },
            cutout: '70%'
        }
    });
</script>
</body>
</html>
