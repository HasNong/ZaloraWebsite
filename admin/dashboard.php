<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}
require_once '../config/db.php';

// Stats — count active records
$all_customers = $database->getReference('customer')->getSnapshot()->getValue() ?: [];
$total_customers = count(array_filter($all_customers, fn($c) => ($c['Cust_IsActive'] ?? 1) == 1));

$all_sellers = $database->getReference('seller')->getSnapshot()->getValue() ?: [];
$total_sellers = count(array_filter($all_sellers, fn($s) => ($s['Sell_IsActive'] ?? 1) == 1));

$all_products = $database->getReference('product')->getSnapshot()->getValue() ?: [];
$total_products = count(array_filter($all_products, fn($p) => ($p['Prod_IsDeleted'] ?? 0) == 0));

$all_orders = $database->getReference('orders')->getSnapshot()->getValue() ?: [];
$total_orders = count($all_orders);

$total_revenue = array_sum(array_map(
    fn($o) => ($o['Order_Status'] ?? '') !== 'CANCELLED' ? floatval($o['Order_TotalAmnt'] ?? 0) : 0,
    $all_orders
));

// Recent Orders (last 5, sorted by date)
usort($all_orders, fn($a, $b) => strtotime($b['Order_PlacedAt'] ?? 0) - strtotime($a['Order_PlacedAt'] ?? 0));
$recent_orders_raw = array_slice($all_orders, 0, 5);
$recent_orders = [];
foreach ($recent_orders_raw as $o) {
    $cust_id = $o['Cust_Id'] ?? null;
    $cust = null;
    if ($cust_id) {
        foreach ($all_customers as $c) {
            if (($c['Cust_Id'] ?? null) == $cust_id) { $cust = $c; break; }
        }
    }
    $recent_orders[] = array_merge($o, [
        'Cust_Firstname' => $cust['Cust_Firstname'] ?? 'Unknown',
        'Cust_Lastname'  => $cust['Cust_Lastname'] ?? ''
    ]);
}

// Daily Sales (Last 7 Days)
$sales_map = [];
$seven_days_ago = strtotime('-7 days');
foreach ($all_orders as $o) {
    if (($o['Order_Status'] ?? '') === 'CANCELLED') continue;
    $ts = strtotime($o['Order_PlacedAt'] ?? 0);
    if ($ts >= $seven_days_ago) {
        $day = date('M d', $ts);
        $sales_map[$day] = ($sales_map[$day] ?? 0) + floatval($o['Order_TotalAmnt'] ?? 0);
    }
}
ksort($sales_map);
$sales_labels = array_keys($sales_map);
$sales_data   = array_values($sales_map);

// Category Distribution
$all_categories = $database->getReference('category')->getSnapshot()->getValue() ?: [];
$cat_map = [];
foreach ($all_products as $p) {
    $ctgry_id = $p['Ctgry_Id'] ?? null;
    if (!$ctgry_id) continue;
    $cat_name = 'Unknown';
    foreach ($all_categories as $cat) {
        if (($cat['Ctgry_Id'] ?? null) == $ctgry_id) { $cat_name = $cat['Ctgry_Name']; break; }
    }
    $cat_map[$cat_name] = ($cat_map[$cat_name] ?? 0) + 1;
}
$cat_labels = array_keys($cat_map);
$cat_data   = array_values($cat_map);
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
        <div style="font-size: 12px; color: #888;">Logged in as: <strong><?= htmlspecialchars($_SESSION['user_email'] ?? 'admin@zalora.com') ?></strong></div>
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
                <?php if (count($recent_orders) > 0): ?>
                    <?php foreach($recent_orders as $row): ?>
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
                    <?php endforeach; ?>
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
