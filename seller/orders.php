<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$seller_id = $_SESSION['user_id'];
$status_filter = strtoupper(isset($_GET['status']) ? $_GET['status'] : 'CONFIRMED');

// 1. Get counts for tabs
$counts = ['PENDING' => 0, 'CONFIRMED' => 0, 'SHIPPED' => 0, 'DELIVERED' => 0, 'RETURNED' => 0, 'CANCELED' => 0];
$count_query = "SELECT o.Order_Status, COUNT(*) as cnt 
                FROM ORDERS o 
                JOIN ORDER_ITEM oi ON o.Order_Id = oi.Order_Id
                JOIN PRODUCT_VARIANT pv ON oi.PVar_Id = pv.PVar_Id
                JOIN PRODUCT p ON pv.Prod_Id = p.Prod_Id
                WHERE p.Sell_Id = ? 
                GROUP BY o.Order_Status";
$stmt_count = $conn->prepare($count_query);
$stmt_count->bind_param("i", $seller_id);
$stmt_count->execute();
$count_res = $stmt_count->get_result();
while($row = $count_res->fetch_assoc()) {
    $s = strtoupper($row['Order_Status']);
    if (isset($counts[$s])) $counts[$s] = $row['cnt'];
}

// 2. Fetch Filtered Orders
$query = "SELECT o.Order_Id, o.Order_PlacedAt, o.Order_Status, c.Cust_FirstName, c.Cust_LastName,
                 oi.OdItm_Quantity, oi.OdItm_Subtotal, pv.PVar_Size, pv.PVar_Color,
                 p.Prod_Name, (SELECT PImg_ImgUrl FROM PRODUCT_IMAGE WHERE Prod_Id = p.Prod_Id AND PImg_IsPrimary = 1 LIMIT 1) as img,
                 s.Ship_ProofImg
          FROM ORDERS o
          JOIN CUSTOMER c ON o.Cust_Id = c.Cust_Id
          JOIN ORDER_ITEM oi ON o.Order_Id = oi.Order_Id
          JOIN PRODUCT_VARIANT pv ON oi.PVar_Id = pv.PVar_Id
          JOIN PRODUCT p ON pv.Prod_Id = p.Prod_Id
          LEFT JOIN shipment s ON o.Order_Id = s.Order_Id
          WHERE p.Sell_Id = ? AND UPPER(o.Order_Status) = ?
          ORDER BY o.Order_PlacedAt DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("is", $seller_id, $status_filter);
$stmt->execute();
$orders_res = $stmt->get_result();

// 3. Handle Driver Assignment
if (isset($_POST['assign_driver'])) {
    $oid = $_POST['order_id'];
    $did = intval($_POST['driver_id']);
    
    // Check shipment
    $check_ship = $conn->prepare("SELECT Ship_Id FROM shipment WHERE Order_Id = ?");
    $check_ship->bind_param("s", $oid);
    $check_ship->execute();
    $ship_exists = $check_ship->get_result()->num_rows > 0;
    
    if ($ship_exists) {
        $upd = $conn->prepare("UPDATE shipment SET Driv_Id = ?, Ship_Status = 'OUT_FOR_DELIVERY' WHERE Order_Id = ?");
        $upd->bind_param("is", $did, $oid);
    } else {
        $upd = $conn->prepare("INSERT INTO shipment (Order_Id, Driv_Id, Ship_Status, Ship_Courier) VALUES (?, ?, 'OUT_FOR_DELIVERY', 'ZALORA INTERNAL')");
        $upd->bind_param("si", $oid, $did);
    }
    
    if ($upd->execute()) {
        $conn->query("UPDATE ORDERS SET Order_Status = 'SHIPPED' WHERE Order_Id = '$oid'");
        $success_msg = "Driver assigned successfully!";
    }
}

// 4. Fetch Drivers
$online_drivers = $conn->query("SELECT Driv_Id, Driv_FirstName, Driv_LastName, Driv_VehicleType FROM driver WHERE Driv_IsActive = 1");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Center - Orders</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/seller.css">
    <style>
        /* FAIL-SAFE STYLES */
        :root { --black: #000; --white: #fff; --bg-light: #f9fafb; --border: #f1f1f1; --text-muted: #666; --text-light: #999; }
        body { margin: 0; font-family: 'Inter', sans-serif; background: var(--bg-light); display: flex; }
        .sidebar { width: 240px; background: var(--white); border-right: 1px solid var(--border); position: fixed; height: 100vh; z-index: 100; }
        .main-wrapper { margin-left: 240px; flex-grow: 1; padding: 40px; min-width: 0; }
        .order-card { background: var(--white); border: 1px solid var(--border); display: flex; margin-bottom: 25px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
        .order-img { width: 200px; height: 250px; object-fit: cover; flex-shrink: 0; background: #eee; }
        .order-details { flex-grow: 1; padding: 25px; display: flex; flex-direction: column; }
        .order-header { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .order-name { font-size: 18px; font-weight: 700; margin: 0 0 5px; text-transform: uppercase; }
        .order-id { font-size: 10px; font-weight: 800; color: var(--text-light); margin: 0 0 10px; letter-spacing: 0.1em; }
        .order-meta { font-size: 11px; color: var(--text-muted); }
        .order-footer { margin-top: auto; padding-top: 20px; border-top: 1px solid var(--bg-light); display: flex; justify-content: space-between; align-items: center; }
        .order-info-grid { display: flex; gap: 40px; }
        .info-block label { display: block; font-size: 9px; font-weight: 800; color: var(--text-light); margin-bottom: 5px; }
        .info-block span { font-size: 13px; font-weight: 600; }
        .status-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 8px; }
        .status-dot.orange { background: #f97316; }
        .status-dot.green { background: #22c55e; }
        .tabs { display: flex; gap: 25px; border-bottom: 1px solid var(--border); margin-top: 20px; }
        .tab { text-decoration: none; color: var(--text-light); font-size: 11px; font-weight: 700; padding: 12px 0; text-transform: uppercase; position: relative; }
        .tab.active { color: #000; }
        .tab.active::after { content: ''; position: absolute; bottom: -1px; left: 0; width: 100%; height: 2px; background: #000; }
        .btn-dark { background: #000; color: #fff; border: none; padding: 10px 25px; font-size: 10px; font-weight: 700; cursor: pointer; text-transform: uppercase; }
        .btn-secondary { background: #f4f4f4; border: none; padding: 10px 25px; font-size: 10px; font-weight: 700; cursor: pointer; text-transform: uppercase; margin-right: 10px; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<!-- MAIN WRAPPER -->
<div class="main-wrapper">
    <main class="main-content">
        
        <?php if (isset($success_msg)): ?>
            <div style="background: #f0fdf4; color: #16a34a; padding: 15px; border-left: 5px solid #16a34a; margin-bottom: 20px; font-size: 13px; font-weight: 600;">
                <?= $success_msg ?>
            </div>
        <?php endif; ?>

        <header class="page-header" style="align-items: center;">
            <div>
                <h2 class="page-title">ORDER MANAGEMENT</h2>
                <div class="tabs">
                    <a href="?status=PENDING" class="tab <?= $status_filter == 'PENDING' ? 'active' : '' ?>">Pending (<?= $counts['PENDING'] ?>)</a>
                    <a href="?status=CONFIRMED" class="tab <?= $status_filter == 'CONFIRMED' ? 'active' : '' ?>">Confirmed (<?= $counts['CONFIRMED'] ?>)</a>
                    <a href="?status=SHIPPED" class="tab <?= $status_filter == 'SHIPPED' ? 'active' : '' ?>">Shipped (<?= $counts['SHIPPED'] ?>)</a>
                    <a href="?status=DELIVERED" class="tab <?= $status_filter == 'DELIVERED' ? 'active' : '' ?>">Delivered (<?= $counts['DELIVERED'] ?>)</a>
                    <a href="?status=RETURNED" class="tab <?= $status_filter == 'RETURNED' ? 'active' : '' ?>">Returned (<?= $counts['RETURNED'] ?>)</a>
                    <a href="?status=CANCELED" class="tab <?= $status_filter == 'CANCELED' ? 'active' : '' ?>">Canceled (<?= $counts['CANCELED'] ?>)</a>
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
                                    <span><?= date('M d, Y', strtotime($o['Order_PlacedAt'])) ?></span>
                                </div>
                                <div class="info-block">
                                    <label>STATUS</label>
                                    <span><i class="status-dot <?= $status_color ?>"></i><span class="status-text"><?= $status ?></span></span>
                                    <?php if ($o['Ship_ProofImg']): ?>
                                        <a href="../<?= htmlspecialchars($o['Ship_ProofImg']) ?>" target="_blank" style="display: block; font-size: 9px; color: #000; font-weight: 700; margin-top: 5px; text-decoration: underline;">VIEW PROOF</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="order-actions" style="display: flex; gap: 10px; align-items: center;">
                                <?php if ($status == 'CONFIRMED'): ?>
                                    <form method="POST" style="display: flex; gap: 10px; align-items: center;">
                                        <input type="hidden" name="order_id" value="<?= $o['Order_Id'] ?>">
                                        <select name="driver_id" style="padding: 8px; font-size: 11px; border: 1px solid #ddd;" required>
                                            <option value="">SELECT DRIVER...</option>
                                            <?php 
                                            $online_drivers->data_seek(0);
                                            while($d = $online_drivers->fetch_assoc()): ?>
                                                <option value="<?= $d['Driv_Id'] ?>"><?= $d['Driv_FirstName'] ?> (<?= $d['Driv_VehicleType'] ?>)</option>
                                            <?php endwhile; ?>
                                        </select>
                                        <button type="submit" name="assign_driver" class="btn-dark" style="padding: 10px 15px; font-size: 9px;">CONFIRM DISPATCH</button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn-secondary">VIEW DETAILS</button>
                                    <button class="btn-dark" disabled style="opacity: 0.5; cursor: not-allowed;"><?= $status ?></button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 100px; color: var(--text-light);">
                    <p>No orders found for this category yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

</body>
</html>
