<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}
require_once '../config/db.php';

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle Status Update
if (isset($_POST['update_status'])) {
    $new_status = $_POST['order_status'];
    $stmt = $conn->prepare("UPDATE ORDERS SET Order_Status = ? WHERE Order_Id = ?");
    $stmt->bind_param("si", $new_status, $order_id);
    if ($stmt->execute()) {
        $success = "Order status updated successfully!";
    }
}

// Handle Driver Assignment
if (isset($_POST['assign_driver']) && $order_id > 0) {
    $driv_id = intval($_POST['driver_id']);
    // Check if shipment entry exists
    $check_ship = $conn->prepare("SELECT Ship_Id FROM shipment WHERE Order_Id = ?");
    $check_ship->bind_param("i", $order_id);
    $check_ship->execute();
    $ship_res = $check_ship->get_result();
    
    if ($ship_res->num_rows > 0) {
        $upd_ship = $conn->prepare("UPDATE shipment SET Driv_Id = ?, Ship_Status = 'OUT_FOR_DELIVERY' WHERE Order_Id = ?");
        $upd_ship->bind_param("ii", $driv_id, $order_id);
    } else {
        $upd_ship = $conn->prepare("INSERT INTO shipment (Order_Id, Driv_Id, Ship_Status, Ship_Courier) VALUES (?, ?, 'OUT_FOR_DELIVERY', 'ZALORA INTERNAL')");
        $upd_ship->bind_param("ii", $order_id, $driv_id);
    }
    
    if ($upd_ship->execute()) {
        $conn->query("UPDATE ORDERS SET Order_Status = 'SHIPPED' WHERE Order_Id = $order_id");
        $success = "Driver assigned! Order is now OUT FOR DELIVERY.";
    }
}

// Fetch Orders
if ($order_id > 0) {
    // Detail View
    $query = "SELECT o.*, c.Cust_Firstname, c.Cust_Lastname, c.Cust_Email, a.Addrs_Street, a.Addrs_City 
              FROM ORDERS o 
              JOIN CUSTOMER c ON o.Cust_Id = c.Cust_Id 
              JOIN ADDRESS a ON o.Addrs_Id = a.Addrs_id 
              WHERE o.Order_Id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();

    $items_query = "SELECT oi.*, p.Prod_Name, pv.PVar_Size, pv.PVar_Color 
                    FROM ORDER_ITEM oi 
                    JOIN PRODUCT_VARIANT pv ON oi.PVar_Id = pv.PVar_Id 
                    JOIN PRODUCT p ON pv.Prod_Id = p.Prod_Id 
                    WHERE oi.Order_Id = ?";
    $stmt_i = $conn->prepare($items_query);
    $stmt_i->bind_param("i", $order_id);
    $stmt_i->execute();
    $items = $stmt_i->get_result();
} else {
    // List View
    $orders = $conn->query("SELECT o.*, c.Cust_Firstname, c.Cust_Lastname 
                           FROM ORDERS o 
                           JOIN CUSTOMER c ON o.Cust_Id = c.Cust_Id 
                           ORDER BY o.Order_PlacedAt DESC");
}

$statuses = ['PENDING', 'CONFIRMED', 'PACKED', 'SHIPPED', 'DELIVERED', 'CANCELLED', 'RETURNED'];

// Fetch Online Drivers
$online_drivers = $conn->query("SELECT Driv_Id, Driv_FirstName, Driv_LastName, Driv_VehicleType FROM driver WHERE Driv_IsActive = 1");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management - Zalora Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .status-select { padding: 10px; border: 1px solid #ddd; font-weight: 600; }
        .btn-update { background: #000; color: #fff; border: none; padding: 10px 20px; font-weight: 700; cursor: pointer; }
        .order-table { width: 100%; border-collapse: collapse; background: white; border: 1px solid #eee; }
        .order-table th, .order-table td { padding: 1rem; border-bottom: 1px solid #eee; text-align: left; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; background: #eee; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-shipped { background: #dcfce7; color: #166534; }
    </style>
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="main-content">
    <header class="header">
        <h1 class="page-title"><?= $order_id > 0 ? "Order Detail #$order_id" : "Order Management" ?></h1>
        <?php if ($order_id > 0): ?>
            <a href="orders.php" style="color: #666; text-decoration: none; font-size: 14px;">← Back to List</a>
        <?php endif; ?>
    </header>

    <?php if (isset($success)): ?>
        <div style="background: #dcfce7; color: #166534; padding: 1rem; margin-bottom: 1rem; border-radius: 4px;"><?= $success ?></div>
    <?php endif; ?>

    <?php if ($order_id > 0 && $order): ?>
        <!-- DETAIL VIEW -->
        <div style="display: grid; grid-template-columns: 1fr 350px; gap: 2rem;">
            <div>
                <div class="card" style="margin-bottom: 2rem;">
                    <h2 style="font-size: 14px; text-transform: uppercase; margin-bottom: 1rem;">Order Items</h2>
                    <table class="order-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Variant</th>
                                <th>Qty</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($item = $items->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['Prod_Name']) ?></td>
                                <td style="font-size: 12px; color: #666;">
                                    <?= $item['PVar_Color'] ? $item['PVar_Color'] . ' • ' : '' ?>Size <?= $item['PVar_Size'] ?>
                                </td>
                                <td><?= $item['OdItm_Quantity'] ?></td>
                                <td style="font-weight: 600;">$<?= number_format($item['OdItm_Subtotal'], 2) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <div class="card">
                    <h2 style="font-size: 14px; text-transform: uppercase; margin-bottom: 1rem;">Shipping Details</h2>
                    <p><strong>Customer:</strong> <?= htmlspecialchars($order['Cust_Firstname'] . ' ' . $order['Cust_Lastname']) ?></p>
                    <p><strong>Address:</strong> <?= htmlspecialchars($order['Addrs_Street'] . ', ' . $order['Addrs_City']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($order['Cust_Email']) ?></p>
                </div>
            </div>

            <div>
                <div class="card">
                    <h2 style="font-size: 14px; text-transform: uppercase; margin-bottom: 1rem;">Order Status</h2>
                    <form method="POST">
                        <select name="order_status" class="status-select" style="width: 100%; margin-bottom: 1rem;">
                            <?php foreach ($statuses as $s): ?>
                                <option value="<?= $s ?>" <?= $order['Order_Status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" name="update_status" class="btn-update" style="width: 100%;">Update Status</button>
                    </form>
                    <hr style="margin: 2rem 0; border: none; border-top: 1px solid #eee;">
                    <div style="font-size: 14px;">
                        <p style="margin-bottom: 10px;"><strong>Order Date:</strong> <?= date('F j, Y', strtotime($order['Order_PlacedAt'])) ?></p>
                        <p style="font-size: 18px;"><strong>Total Amount:</strong> $<?= number_format($order['Order_TotalAmnt'], 2) ?></p>
                    </div>
                </div> <!-- End Order Status Card -->

                <div class="card" style="margin-top: 2rem;">
                    <h2 style="font-size: 14px; text-transform: uppercase; margin-bottom: 1rem;">Assign Driver</h2>
                    <form method="POST">
                        <select name="driver_id" class="status-select" style="width: 100%; margin-bottom: 1rem;" required>
                            <option value="">Select a Driver...</option>
                            <?php 
                            $online_drivers->data_seek(0); // Reset pointer
                            while($d = $online_drivers->fetch_assoc()): ?>
                                <option value="<?= $d['Driv_Id'] ?>"><?= htmlspecialchars($d['Driv_FirstName'] . ' ' . $d['Driv_LastName']) ?> (<?= $d['Driv_VehicleType'] ?>)</option>
                            <?php endwhile; ?>
                        </select>
                        <button type="submit" name="assign_driver" class="btn-update" style="width: 100%; background: #22c55e;">Confirm Assignment</button>
                    </form>
                    <p style="font-size: 11px; color: #999; margin-top: 10px; text-align: center;">Drivers must be ONLINE to receive new orders.</p>
                </div>
            </div> <!-- End Right Column -->
        </div> <!-- End Grid Wrapper -->

    <?php else: ?>
        <!-- LIST VIEW -->
        <div class="card">
            <table class="order-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $orders->fetch_assoc()): ?>
                    <tr>
                        <td>#<?= $row['Order_Id'] ?></td>
                        <td><?= htmlspecialchars($row['Cust_Firstname'] . ' ' . $row['Cust_Lastname']) ?></td>
                        <td style="color: #888;"><?= date('M j, Y', strtotime($row['Order_PlacedAt'])) ?></td>
                        <td style="font-weight: 600;">$<?= number_format($row['Order_TotalAmnt'], 2) ?></td>
                        <td><span class="badge"><?= $row['Order_Status'] ?></span></td>
                        <td><a href="orders.php?id=<?= $row['Order_Id'] ?>" style="color: #000; font-weight: 600; text-decoration: underline;">Manage</a></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
