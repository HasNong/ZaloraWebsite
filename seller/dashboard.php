<?php
session_start();
require_once '../config/db.php';

// Auth check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'seller') {
    header("Location: ../auth/login.php");
    exit;
}

// Real dynamic data filtered by seller
$stmt_count = $conn->prepare("SELECT COUNT(*) as count FROM product WHERE Sell_Id = ?");
$stmt_count->bind_param("i", $seller_id);
$stmt_count->execute();
$total_products = $stmt_count->get_result()->fetch_assoc()['count'];

// Top Selling Products (Calculated from order_item via variants)
$query_top = "SELECT p.*, 
              (SELECT PImg_ImgUrl FROM PRODUCT_IMAGE WHERE Prod_Id = p.Prod_Id AND PImg_IsPrimary = 1 LIMIT 1) as img,
              (SELECT SUM(PVar_StockQuantity) FROM product_variant WHERE Prod_Id = p.Prod_Id) as total_stock,
              IFNULL((SELECT SUM(oi.OdItm_Quantity) FROM order_item oi JOIN product_variant pv ON oi.PVar_Id = pv.PVar_Id WHERE pv.Prod_Id = p.Prod_Id), 0) as total_sales,
              IFNULL((SELECT SUM(oi.OdItm_Subtotal) FROM order_item oi JOIN product_variant pv ON oi.PVar_Id = pv.PVar_Id WHERE pv.Prod_Id = p.Prod_Id), 0) as total_revenue
              FROM product p 
              WHERE p.Sell_Id = ? 
              ORDER BY total_sales DESC LIMIT 5";
$stmt_top = $conn->prepare($query_top);
$stmt_top->bind_param("i", $seller_id);
$stmt_top->execute();
$recent_products = $stmt_top->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Center - Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/seller.css">
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-wrapper">
    <main class="main-content">
        
        <header class="page-header">
            <div>
                <h2 class="page-title">DASHBOARD OVERVIEW</h2>
                <p class="page-subtitle">Welcome back, <?= htmlspecialchars($seller_name) ?>. Here's your performance for the last 24 hours.</p>
            </div>
            <div class="header-actions">
                <button class="btn-export">EXPORT DATA</button>
                <button class="btn-icon">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                </button>
            </div>
        </header>

        <!-- METRICS -->
        <div class="metrics-grid">
            <div class="metric-card">
                <p class="metric-title">TOTAL REVENUE</p>
                <p class="metric-value">$128,430.00</p>
                <p class="metric-sub trend-up">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg>
                    +12.5% vs Last Week
                </p>
            </div>
            <div class="metric-card">
                <p class="metric-title">ACTIVE ORDERS</p>
                <p class="metric-value">1,248</p>
                <p class="metric-sub">84 Pending Fulfillment</p>
            </div>
            <div class="metric-card">
                <p class="metric-title">CONVERSION RATE</p>
                <p class="metric-value">4.82%</p>
                <p class="metric-sub trend-down">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"></polyline><polyline points="17 18 23 18 23 12"></polyline></svg>
                    -0.4% vs Yesterday
                </p>
            </div>
            <div class="metric-card">
                <p class="metric-title">TOTAL VISITORS</p>
                <p class="metric-value">42.5K</p>
                <p class="metric-sub">Peak: 2:00 PM EST</p>
            </div>
        </div>

        <div class="dashboard-grid">
            <!-- LEFT COLUMN -->
            <div class="dashboard-left">
                <div class="content-card">
                    <div class="card-header">
                        <h3 class="card-title">SALES PERFORMANCE</h3>
                        <div class="tabs" style="display:flex; border:1px solid #eee;">
                            <button style="border:none; padding:8px 15px; background:#000; color:#fff; font-size:10px; font-weight:700;">WEEKLY</button>
                            <button style="border:none; padding:8px 15px; background:#fff; color:#666; font-size:10px; font-weight:700;">MONTHLY</button>
                        </div>
                    </div>
                    <!-- Chart Placeholder -->
                    <div style="height: 250px; display: flex; align-items: flex-end; gap: 20px; padding: 20px 0;">
                        <div style="flex:1; height: 30%; background: #f4f4f4;"></div>
                        <div style="flex:1; height: 50%; background: #f4f4f4;"></div>
                        <div style="flex:1; height: 40%; background: #f4f4f4;"></div>
                        <div style="flex:1; height: 60%; background: #f4f4f4;"></div>
                        <div style="flex:1; height: 100%; background: #000; position:relative;">
                             <span style="position:absolute; top:-25px; left:50%; transform:translateX(-50%); font-size:10px; font-weight:700;">THU</span>
                        </div>
                        <div style="flex:1; height: 55%; background: #f4f4f4;"></div>
                        <div style="flex:1; height: 35%; background: #f4f4f4;"></div>
                        <div style="flex:1; height: 30%; background: #f4f4f4;"></div>
                    </div>
                    <div style="display: flex; gap: 40px; border-top: 1px solid #eee; padding-top: 15px;">
                        <div>
                            <p style="font-size:10px; color:#999; margin:0;">AVG ORDER VALUE</p>
                            <p style="font-size:16px; font-weight:700; margin:4px 0;">$102.50</p>
                        </div>
                        <div>
                            <p style="font-size:10px; color:#999; margin:0;">TOTAL ITEMS SOLD</p>
                            <p style="font-size:16px; font-weight:700; margin:4px 0;">1,842</p>
                        </div>
                        <div style="margin-left:auto;">
                            <a href="#" style="font-size:10px; font-weight:700; color:#000; text-decoration:none; border-bottom:1px solid #000;">VIEW FULL ANALYTICS</a>
                        </div>
                    </div>
                </div>

                <div class="content-card">
                    <div class="card-header">
                        <h3 class="card-title">TOP SELLING PRODUCTS</h3>
                    </div>
                    <table class="inventory-table">
                        <thead>
                            <tr>
                                <th>PRODUCT</th>
                                <th>STATUS</th>
                                <th>SALES</th>
                                <th>REVENUE</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($p = $recent_products->fetch_assoc()): 
                                $stock = $p['total_stock'] ?? 0;
                                $status_tag = $stock <= 0 ? 'OUT OF STOCK' : ($stock < 10 ? 'LOW STOCK' : 'IN STOCK');
                                $status_class = $stock <= 0 ? 'out' : ($stock < 10 ? 'low' : 'active');
                                
                                $img_path = $p['img'] ?? 'https://via.placeholder.com/50';
                                if (!empty($p['img']) && strpos($p['img'], 'http') === false) {
                                    $img_path = '../' . $p['img'];
                                }
                            ?>
                            <tr>
                                <td>
                                    <div class="prod-details">
                                        <img src="<?= $img_path ?>" class="prod-thumb">
                                        <div class="prod-info">
                                            <h4><?= htmlspecialchars($p['Prod_Name']) ?></h4>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="status-tag <?= $status_class ?>"><?= $status_tag ?></span></td>
                                <td style="font-weight:600;"><?= number_format($p['total_sales']) ?></td>
                                <td style="font-weight:700;">$<?= number_format($p['total_revenue'], 2) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- RIGHT COLUMN -->
            <div class="dashboard-right">
                <div class="content-card">
                    <h3 class="card-title" style="margin-bottom: 1.5rem;">RECENT ACTIVITY</h3>
                    <div style="display:flex; flex-direction:column; gap:20px;">
                        <div style="display:flex; gap:15px;">
                            <div style="width:40px; height:40px; background:#f4f4f4; display:flex; align-items:center; justify-content:center;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"></path><path d="M3 6h18"></path><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
                            </div>
                            <div>
                                <p style="font-size:12px; margin:0;">Order #92843 placed for "Minimal Silk Slip Dress"</p>
                                <p style="font-size:10px; color:#999; margin:4px 0;">2 MINUTES AGO</p>
                            </div>
                        </div>
                        <div style="display:flex; gap:15px;">
                            <div style="width:40px; height:40px; background:#000; color:#fff; display:flex; align-items:center; justify-content:center;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m7.5 4.27 9 5.15"></path><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"></path><path d="m3.27 6.96 8.73 5.05 8.73-5.05"></path><path d="M12 22.08V12"></path></svg>
                            </div>
                            <div>
                                <p style="font-size:12px; margin:0;">Inventory Alert: "Oversized Blazer" is low on stock (2 units left)</p>
                                <p style="font-size:10px; color:#999; margin:4px 0;">15 MINUTES AGO</p>
                            </div>
                        </div>
                    </div>
                    <button style="width:100%; margin-top:2rem; padding:10px; background:none; border:1px solid #eee; font-size:10px; font-weight:700; cursor:pointer;">VIEW ALL NOTIFICATIONS</button>
                </div>

                <div class="content-card" style="background:#000; color:#fff;">
                    <h3 class="card-title" style="color:#fff; margin-bottom:1rem;">QUICK ACTIONS</h3>
                    <p style="font-size:11px; color:#aaa; margin-bottom:2rem;">Frequent tasks at your fingertips.</p>
                    <div style="display:flex; flex-direction:column; gap:10px;">
                        <button style="background:none; border:1px solid #333; color:#fff; padding:12px; text-align:left; font-size:11px; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:10px;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 12v10H4V12"></path><path d="M2 7h20v5H2z"></path><path d="M12 22V7"></path><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"></path><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"></path></svg>
                            RUN A PROMOTION
                        </button>
                        <button style="background:none; border:1px solid #333; color:#fff; padding:12px; text-align:left; font-size:11px; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:10px;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><path d="M12 17h.01"></path></svg>
                            HELP CENTER
                        </button>
                    </div>
                    <div style="margin-top:3rem; border-top:1px solid #333; padding-top:2rem;">
                         <p style="font-size:10px; color:#aaa; margin-bottom:8px;">STORE HEALTH</p>
                         <div style="display:flex; align-items:baseline; gap:10px;">
                             <span style="font-size:32px; font-weight:800;">98%</span>
                             <span style="font-size:11px; font-weight:700; color:var(--accent-green);">EXCELLENT</span>
                         </div>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <!-- FOOTER -->
    <footer class="seller-footer">
        <div>
            <div class="footer-logo">ZALORA</div>
            <div class="footer-copy">© 2024 ZALORA ALL RIGHTS RESERVED</div>
        </div>
        <div class="footer-links">
            <a href="#">HELP & SUPPORT</a>
            <a href="#">TERMS & CONDITIONS</a>
            <a href="#">CONTACT US</a>
        </div>
    </footer>
</div>

</body>
</html>
