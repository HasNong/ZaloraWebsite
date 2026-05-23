<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}
require_once '../config/db.php';

$order_id = $_GET['id'] ?? null;

$all_customers_raw = $database->getReference('customer')->getSnapshot()->getValue() ?: [];
$customers_by_id = [];
foreach ($all_customers_raw as $c) {
    if (isset($c['Cust_Id'])) {
        $customers_by_id[$c['Cust_Id']] = $c;
    }
}

$order = null;
$items = [];
$orders_list = [];
$shipment = null;

if ($order_id) {
    $orderRef = $database->getReference('orders')->orderByChild('Order_Id')->equalTo($order_id)->getSnapshot()->getValue();
    if ($orderRef) {
        $order_data = current($orderRef);
        $cust = $customers_by_id[$order_data['Cust_Id'] ?? ''] ?? [];
        $addrs_id = $order_data['Addrs_Id'] ?? $order_data['Addrs_id'] ?? null;
        $addrRef = $database->getReference('address')->orderByChild('Addrs_id')->equalTo($addrs_id)->getSnapshot()->getValue();
        $addr = $addrRef ? current($addrRef) : [];

        $order = array_merge($order_data, [
            'Cust_Firstname' => $cust['Cust_Firstname'] ?? 'Unknown',
            'Cust_Lastname'  => $cust['Cust_Lastname']  ?? '',
            'Cust_Email'     => $cust['Cust_Email']     ?? '',
            'Addrs_Street'   => $addr['Addrs_Street']   ?? '',
            'Addrs_City'     => $addr['Addrs_City']     ?? '',
        ]);

        $shipRef = $database->getReference('shipment')->orderByChild('Order_Id')->equalTo($order_id)->getSnapshot()->getValue();
        $shipment = $shipRef ? current($shipRef) : null;

        $itemsRef = $database->getReference('order_item')->orderByChild('Order_Id')->equalTo($order_id)->getSnapshot()->getValue();
        foreach (($itemsRef ?: []) as $oi) {
            $pvar_id = $oi['PVar_Id'] ?? null;
            $pvarRef = $database->getReference('product_variant')->orderByChild('PVar_Id')->equalTo($pvar_id)->getSnapshot()->getValue();
            $pvar = $pvarRef ? current($pvarRef) : [];
            $prod_id = $pvar['Prod_Id'] ?? null;
            $prodRef = $database->getReference('product')->orderByChild('Prod_Id')->equalTo($prod_id)->getSnapshot()->getValue();
            $prod = $prodRef ? current($prodRef) : [];
            $items[] = array_merge($oi, [
                'Prod_Name'  => $prod['Prod_Name']  ?? 'Unknown Product',
                'PVar_Size'  => $pvar['PVar_Size']  ?? '-',
                'PVar_Color' => $pvar['PVar_Color'] ?? '',
                'Sell_Id'    => $prod['Sell_Id']    ?? '',
            ]);
        }
    }
} else {
    $all_orders_raw = $database->getReference('orders')->getSnapshot()->getValue() ?: [];
    usort($all_orders_raw, fn($a, $b) => strtotime($b['Order_PlacedAt'] ?? 0) - strtotime($a['Order_PlacedAt'] ?? 0));
    foreach ($all_orders_raw as $o) {
        if (!is_array($o)) {
            continue;
        }
        $cust = $customers_by_id[$o['Cust_Id'] ?? ''] ?? [];
        $orders_list[] = array_merge($o, [
            'Cust_Firstname' => $cust['Cust_Firstname'] ?? 'Unknown',
            'Cust_Lastname'  => $cust['Cust_Lastname'] ?? '',
        ]);
    }
}

$sellers_by_id = [];
foreach ($database->getReference('seller')->getSnapshot()->getValue() ?: [] as $s) {
    if (is_array($s) && isset($s['Sell_Id'])) {
        $sellers_by_id[$s['Sell_Id']] = $s['Sell_BusinessName'] ?? 'Unknown Seller';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Overview - Zalora Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/admin-orders.css?v=<?= time() ?>">
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="main-content">
    <header class="header">
        <h1 class="page-title"><?= $order_id ? "Order Overview #$order_id" : "Orders Overview" ?></h1>
        <?php if ($order_id): ?>
            <a href="orders.php" style="color: #666; text-decoration: none; font-size: 14px;">← Back to List</a>
        <?php endif; ?>
    </header>

    <div class="notice">
        Sellers manage order fulfillment in <strong>Seller Center → Orders</strong> (confirm, pack, assign drivers).
        This page is read-only for platform oversight.
    </div>

    <?php if ($order_id && $order): ?>
        <div style="display: grid; grid-template-columns: 1fr 350px; gap: 2rem;">
            <div>
                <div class="card" style="margin-bottom: 2rem;">
                    <h2 style="font-size: 14px; text-transform: uppercase; margin-bottom: 1rem;">Order Items</h2>
                    <table class="order-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Seller</th>
                                <th>Variant</th>
                                <th>Qty</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['Prod_Name']) ?></td>
                                <td style="font-size: 12px;"><?= htmlspecialchars($sellers_by_id[$item['Sell_Id'] ?? ''] ?? '—') ?></td>
                                <td style="font-size: 12px; color: #666;">
                                    <?= $item['PVar_Color'] ? htmlspecialchars($item['PVar_Color']) . ' • ' : '' ?>Size <?= htmlspecialchars($item['PVar_Size']) ?>
                                </td>
                                <td><?= (int) ($item['OdItm_Quantity'] ?? 0) ?></td>
                                <td style="font-weight: 600;">$<?= number_format($item['OdItm_Subtotal'] ?? 0, 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card">
                    <h2 style="font-size: 14px; text-transform: uppercase; margin-bottom: 1rem;">Shipping Details</h2>
                    <p><strong>Customer:</strong> <?= htmlspecialchars(trim(($order['Cust_Firstname'] ?? '') . ' ' . ($order['Cust_Lastname'] ?? ''))) ?></p>
                    <p><strong>Address:</strong> <?= htmlspecialchars(trim(($order['Addrs_Street'] ?? '') . ', ' . ($order['Addrs_City'] ?? ''))) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($order['Cust_Email'] ?? '') ?></p>
                </div>
            </div>
            <div>
                <div class="card">
                    <h2 style="font-size: 14px; text-transform: uppercase; margin-bottom: 1rem;">Status</h2>
                    <p style="font-size: 18px; font-weight: 700; margin-bottom: 1rem;"><?= htmlspecialchars($order['Order_Status'] ?? 'PENDING') ?></p>
                    <p style="font-size: 13px; color: #666; margin-bottom: 8px;"><strong>Placed:</strong> <?= date('F j, Y g:i A', strtotime($order['Order_PlacedAt'] ?? 'now')) ?></p>
                    <p style="font-size: 16px;"><strong>Total:</strong> $<?= number_format($order['Order_TotalAmnt'] ?? 0, 2) ?></p>
                    <?php if ($shipment): ?>
                        <hr style="margin: 1.5rem 0; border: none; border-top: 1px solid #eee;">
                        <p style="font-size: 12px; color: #666;"><strong>Shipment:</strong> <?= htmlspecialchars($shipment['Ship_Status'] ?? '') ?></p>
                        <?php if (!empty($shipment['Ship_ProofImg'])): ?>
                            <p style="font-size: 12px;"><a href="../<?= htmlspecialchars($shipment['Ship_ProofImg']) ?>" target="_blank">View proof of delivery</a></p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php elseif ($order_id): ?>
        <div class="card"><p>Order not found.</p></div>
    <?php else: ?>
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
                    <?php foreach ($orders_list as $row): ?>
                    <tr>
                        <td>#<?= htmlspecialchars($row['Order_Id'] ?? '') ?></td>
                        <td><?= htmlspecialchars(trim(($row['Cust_Firstname'] ?? '') . ' ' . ($row['Cust_Lastname'] ?? ''))) ?></td>
                        <td style="color: #888;"><?= date('M j, Y', strtotime($row['Order_PlacedAt'] ?? 'now')) ?></td>
                        <td style="font-weight: 600;">$<?= number_format($row['Order_TotalAmnt'] ?? 0, 2) ?></td>
                        <td><span class="badge"><?= htmlspecialchars($row['Order_Status'] ?? '') ?></span></td>
                        <td><a href="orders.php?id=<?= urlencode($row['Order_Id'] ?? '') ?>" style="color: #000; font-weight: 600; text-decoration: underline;">View</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
