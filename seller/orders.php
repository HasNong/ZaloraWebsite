<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$seller_id = $_SESSION['user_id'];

// Fetch Real Orders for this seller
$query = "SELECT o.Order_Id, o.Order_Date, o.Order_Status, c.Cust_FirstName, c.Cust_LastName,
                 oi.OdItm_Quantity, oi.OdItm_Subtotal, pv.PVar_Size, pv.PVar_Color,
                 p.Prod_Name, (SELECT PImg_ImgUrl FROM PRODUCT_IMAGE WHERE Prod_Id = p.Prod_Id AND PImg_IsPrimary = 1 LIMIT 1) as img
          FROM ORDERS o
          JOIN CUSTOMER c ON o.Cust_Id = c.Cust_Id
          JOIN ORDER_ITEM oi ON o.Order_Id = oi.Order_Id
          JOIN PRODUCT_VARIANT pv ON oi.PVar_Id = pv.PVar_Id
          JOIN PRODUCT p ON pv.Prod_Id = p.Prod_Id
          WHERE p.Sell_Id = ?
          ORDER BY o.Order_Date DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$orders_res = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Center - Orders</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/seller.css">
</head>
<body>

<?php include 'sidebar.php'; ?>

<!-- MAIN WRAPPER -->
<div class="main-wrapper">
    <main class="main-content">
        
        <header class="page-header" style="align-items: center;">
            <div>
                <h2 class="page-title">ORDER MANAGEMENT</h2>
                <div class="tabs">
                    <button class="tab active">Pending (12)</button>
                    <button class="tab">Shipped (84)</button>
                    <button class="tab">Returned</button>
                    <button class="tab">Canceled</button>
                </div>
            </div>
            <div class="search-filter">
                <div class="search-box">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    <input type="text" placeholder="SEARCH ORDER ID...">
                </div>
                <button class="btn-filter">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
                    FILTERS
                </button>
            </div>
        </header>

        <div class="order-list">
            <?php if ($orders_res->num_rows > 0): ?>
                <?php while($o = $orders_res->fetch_assoc()): 
                    $img_path = $o['img'] ?? 'https://via.placeholder.com/100';
                    if (!empty($o['img']) && strpos($o['img'], 'http') === false) {
                        $img_path = '../' . $o['img'];
                    }
                    $status = strtoupper($o['Order_Status'] ?? 'PENDING');
                    $status_color = ($status == 'DELIVERED' || $status == 'SHIPPED') ? 'green' : 'orange';
                ?>
                <div class="order-card">
                    <img src="<?= $img_path ?>" alt="<?= htmlspecialchars($o['Prod_Name']) ?>" class="order-img">
                    <div class="order-details">
                        <div class="order-header">
                            <div>
                                <p class="order-id">ORDER #<?= htmlspecialchars($o['Order_Id']) ?></p>
                                <h3 class="order-name"><?= htmlspecialchars($o['Prod_Name']) ?></h3>
                                <p class="order-meta">SIZE: <?= htmlspecialchars($o['PVar_Size']) ?> | COLOR: <?= htmlspecialchars($o['PVar_Color']) ?></p>
                            </div>
                            <div class="order-price">
                                <p class="order-total">$<?= number_format($o['OdItm_Subtotal'], 2) ?></p>
                                <p class="order-qty">QTY: <?= str_pad($o['OdItm_Quantity'], 2, '0', STR_PAD_LEFT) ?></p>
                            </div>
                        </div>
                        <div class="order-footer">
                            <div class="order-info-grid">
                                <div class="info-block">
                                    <label>CUSTOMER</label>
                                    <span><?= htmlspecialchars($o['Cust_FirstName'] . ' ' . $o['Cust_LastName']) ?></span>
                                </div>
                                <div class="info-block">
                                    <label>ORDER DATE</label>
                                    <span><?= date('M d, Y', strtotime($o['Order_Date'])) ?></span>
                                </div>
                                <div class="info-block">
                                    <label>STATUS</label>
                                    <span><i class="status-dot <?= $status_color ?>"></i><span class="status-text"><?= $status ?></span></span>
                                </div>
                            </div>
                            <div class="order-actions">
                                <button class="btn-secondary">VIEW DETAILS</button>
                                <button class="btn-dark">FULFILL</button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 100px; color: var(--grey);">
                    <p>No orders found yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

</body>
</html>
