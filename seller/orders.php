<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$seller_id = $_SESSION['user_id'];
$status_filter = strtoupper($_GET['status'] ?? 'PENDING');

function seller_owns_order($order_id, $variantToProductMap, $orderItemsRef) {
    foreach ($orderItemsRef as $oi) {
        if (!is_array($oi)) continue;
        if (($oi['Order_Id'] ?? '') == $order_id && isset($variantToProductMap[$oi['PVar_Id'] ?? ''])) {
            return true;
        }
    }
    return false;
}

function update_order_status($database, $order_id, $status) {
    $found = fb_find_record($database, 'orders', 'Order_Id', $order_id);
    if ($found) {
        $database->getReference('orders')->getChild($found['key'])->update([
            'Order_Status' => $status,
            'Order_UpdatedAt' => date('Y-m-d H:i:s'),
        ]);
        return true;
    }
    return false;
}

// Load catalog + orders data (needed for ownership checks)
$productsRef = $database->getReference('product')->orderByChild('Sell_Id')->equalTo($seller_id)->getSnapshot()->getValue() ?: [];
$variantsRef = $database->getReference('product_variant')->getSnapshot()->getValue() ?: [];
$orderItemsRef = $database->getReference('order_item')->getSnapshot()->getValue() ?: [];

// Build a Prod_Id-keyed lookup of the seller's products
// ($productsRef is keyed by Firebase push-keys, NOT by Prod_Id)
$sellerProductIds = [];
foreach ($productsRef as $pKey => $p) {
    if (is_array($p) && isset($p['Prod_Id'])) {
        $sellerProductIds[$p['Prod_Id']] = $p;
    }
}

$variantToProductMap = [];
foreach ($variantsRef as $vid => $v) {
    if (!is_array($v)) continue;
    $prod_id = $v['Prod_Id'] ?? '';
    if (isset($sellerProductIds[$prod_id])) {
        $variantToProductMap[$v['PVar_Id'] ?? $vid] = $v;
    }
}

// Handle status updates (confirm, cancel, pack)
if (isset($_POST['update_status'])) {
    $oid = $_POST['order_id'] ?? '';
    $new_status = strtoupper($_POST['new_status'] ?? '');
    $allowed = ['CONFIRMED', 'PACKED', 'CANCELLED'];

    if ($oid && in_array($new_status, $allowed, true) && seller_owns_order($oid, $variantToProductMap, $orderItemsRef)) {
        if (update_order_status($database, $oid, $new_status)) {
            $_SESSION['success_msg'] = "Order #$oid updated to $new_status.";
            header('Location: orders.php?status=' . urlencode($new_status === 'CANCELLED' ? 'CANCELLED' : $new_status));
            exit;
        }
    }
    $_SESSION['error_msg'] = 'Unable to update order status.';
    header('Location: orders.php?status=' . urlencode($status_filter));
    exit;
}

// Handle driver assignment (seller dispatches to driver)
if (isset($_POST['assign_driver'])) {
    $oid = $_POST['order_id'] ?? '';
    $did = $_POST['driver_id'] ?? '';

    if ($oid && $did && seller_owns_order($oid, $variantToProductMap, $orderItemsRef)) {
        $shipmentsRef = $database->getReference('shipment')->getSnapshot()->getValue() ?: [];
        $existing_ship_id = null;
        foreach ($shipmentsRef as $shipId => $ship) {
            if (($ship['Order_Id'] ?? '') == $oid) {
                $existing_ship_id = $shipId;
                break;
            }
        }

        if ($existing_ship_id) {
            $database->getReference('shipment')->getChild($existing_ship_id)->update([
                'Driv_Id' => $did,
                'Ship_Status' => 'OUT_FOR_DELIVERY',
            ]);
        } else {
            $newShipRef = $database->getReference('shipment')->push();
            $newShipRef->set([
                'Ship_Id' => $newShipRef->getKey(),
                'Order_Id' => $oid,
                'Driv_Id' => $did,
                'Ship_Status' => 'OUT_FOR_DELIVERY',
                'Ship_Courier' => 'ZALORA INTERNAL',
                'Ship_ShippedAt' => date('Y-m-d H:i:s'),
            ]);
        }

        update_order_status($database, $oid, 'SHIPPED');
        $_SESSION['success_msg'] = 'Driver assigned — order is now out for delivery.';
        header('Location: orders.php?status=SHIPPED');
        exit;
    }
    $_SESSION['error_msg'] = 'Unable to assign driver to this order.';
    header('Location: orders.php?status=' . urlencode($status_filter));
    exit;
}

$success_msg = $_SESSION['success_msg'] ?? '';
$error_msg = $_SESSION['error_msg'] ?? '';
unset($_SESSION['success_msg'], $_SESSION['error_msg']);

// Fetch remaining data from Firebase
$ordersRef = $database->getReference('orders')->getSnapshot()->getValue() ?: [];
$customersRef = $database->getReference('customer')->getSnapshot()->getValue() ?: [];
$imagesRef = $database->getReference('product_image')->getSnapshot()->getValue() ?: [];
$shipmentsRef = $database->getReference('shipment')->getSnapshot()->getValue() ?: [];
$driversRef = $database->getReference('driver')->getSnapshot()->getValue() ?: [];

$customers_by_id = [];
foreach ($customersRef as $c) {
    if (is_array($c) && isset($c['Cust_Id'])) {
        $customers_by_id[$c['Cust_Id']] = $c;
    }
}

// Map order items
$orderItemMap = [];
$seller_order_ids = [];
foreach ($orderItemsRef as $oi_id => $oi) {
    $pvar_id = $oi['PVar_Id'] ?? '';
    if (isset($variantToProductMap[$pvar_id])) {
        $oid = $oi['Order_Id'] ?? '';
        $seller_order_ids[$oid] = true;
        $orderItemMap[$oid][] = $oi;
    }
}

$counts = ['PENDING' => 0, 'CONFIRMED' => 0, 'PACKED' => 0, 'SHIPPED' => 0, 'DELIVERED' => 0, 'RETURNED' => 0, 'CANCELLED' => 0];
$filtered_orders = [];

foreach (array_keys($seller_order_ids) as $oid) {
    $o = $ordersRef[$oid] ?? null;
    if (!$o) {
        foreach ($ordersRef as $ord) {
            if (is_array($ord) && (string) ($ord['Order_Id'] ?? '') === (string) $oid) {
                $o = $ord;
                break;
            }
        }
    }
    if (!$o) {
        continue;
    }

    $s = strtoupper($o['Order_Status'] ?? 'PENDING');
    if ($s === 'CANCELED') {
        $s = 'CANCELLED';
    }
    if (isset($counts[$s])) {
        $counts[$s]++;
    }

    if ($s === $status_filter || ($status_filter === 'CANCELLED' && $s === 'CANCELED')) {
        $cust = $customers_by_id[$o['Cust_Id'] ?? ''] ?? [];
        $cust_first = $cust['Cust_FirstName'] ?? 'Unknown';
        $cust_last = $cust['Cust_LastName'] ?? '';

        $ship_img = '';
        foreach ($shipmentsRef as $shipId => $ship) {
            if (($ship['Order_Id'] ?? '') == $oid) {
                $ship_img = $ship['Ship_ProofImg'] ?? '';
                break;
            }
        }

        foreach ($orderItemMap[$oid] as $oi) {
            $var = $variantToProductMap[$oi['PVar_Id'] ?? ''] ?? [];
            $prod = $sellerProductIds[$var['Prod_Id'] ?? ''] ?? [];
            
            $img = 'https://via.placeholder.com/100';
            foreach ($imagesRef as $imgId => $pi) {
                if (($pi['Prod_Id'] ?? '') == ($prod['Prod_Id'] ?? '') && ($pi['PImg_IsPrimary'] ?? 0) == 1) {
                    $img = $pi['PImg_ImgUrl'] ?? '';
                    break;
                }
            }
            
            $filtered_orders[] = [
                'Order_Id' => $oid,
                'Order_PlacedAt' => $o['Order_PlacedAt'] ?? '',
                'Order_Status' => $s,
                'Cust_FirstName' => $cust_first,
                'Cust_LastName' => $cust_last,
                'OdItm_Quantity' => $oi['OdItm_Quantity'] ?? 0,
                'OdItm_Subtotal' => $oi['OdItm_Subtotal'] ?? 0,
                'PVar_Size' => $var['PVar_Size'] ?? '',
                'PVar_Color' => $var['PVar_Color'] ?? '',
                'Prod_Name' => $prod['Prod_Name'] ?? '',
                'img' => $img,
                'Ship_ProofImg' => $ship_img
            ];
        }
    }
}

usort($filtered_orders, function($a, $b) {
    return strtotime($b['Order_PlacedAt'] ?? '2000-01-01') <=> strtotime($a['Order_PlacedAt'] ?? '2000-01-01');
});

$online_drivers = [];
foreach ($driversRef as $did => $d) {
    if (!is_array($d)) continue;
    if (($d['Driv_IsActive'] ?? 0) == 1) {
        $d['Driv_Id'] = $d['Driv_Id'] ?? $did;
        $online_drivers[] = $d;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Center - Orders</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/seller.css">
    <link rel="stylesheet" href="../assets/css/seller-orders.css?v=<?= time() ?>">
</head>
<body>

<?php include 'sidebar.php'; ?>

<!-- MAIN WRAPPER -->
<div class="main-wrapper">
    <main class="main-content">
        
        <?php if ($success_msg): ?>
            <div style="background: #f0fdf4; color: #16a34a; padding: 15px; border-left: 5px solid #16a34a; margin-bottom: 20px; font-size: 13px; font-weight: 600;">
                <?= htmlspecialchars($success_msg) ?>
            </div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div style="background: #fef2f2; color: #b91c1c; padding: 15px; border-left: 5px solid #b91c1c; margin-bottom: 20px; font-size: 13px; font-weight: 600;">
                <?= htmlspecialchars($error_msg) ?>
            </div>
        <?php endif; ?>

        <header class="page-header" style="align-items: center;">
            <div>
                <h2 class="page-title">ORDER FULFILLMENT</h2>
                <p class="page-subtitle" style="font-size: 12px; color: var(--text-muted); margin-top: 6px;">Confirm orders, pack items, and assign drivers for your products.</p>
                <div class="tabs">
                    <a href="?status=PENDING" class="tab <?= $status_filter == 'PENDING' ? 'active' : '' ?>">Pending (<?= $counts['PENDING'] ?>)</a>
                    <a href="?status=CONFIRMED" class="tab <?= $status_filter == 'CONFIRMED' ? 'active' : '' ?>">Confirmed (<?= $counts['CONFIRMED'] ?>)</a>
                    <a href="?status=PACKED" class="tab <?= $status_filter == 'PACKED' ? 'active' : '' ?>">Packed (<?= $counts['PACKED'] ?>)</a>
                    <a href="?status=SHIPPED" class="tab <?= $status_filter == 'SHIPPED' ? 'active' : '' ?>">Shipped (<?= $counts['SHIPPED'] ?>)</a>
                    <a href="?status=DELIVERED" class="tab <?= $status_filter == 'DELIVERED' ? 'active' : '' ?>">Delivered (<?= $counts['DELIVERED'] ?>)</a>
                    <a href="?status=RETURNED" class="tab <?= $status_filter == 'RETURNED' ? 'active' : '' ?>">Returned (<?= $counts['RETURNED'] ?>)</a>
                    <a href="?status=CANCELLED" class="tab <?= $status_filter == 'CANCELLED' ? 'active' : '' ?>">Cancelled (<?= $counts['CANCELLED'] ?>)</a>
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
            <?php if (count($filtered_orders) > 0): ?>
                <?php foreach($filtered_orders as $o): 
                    $img_path = $o['img'] ?? 'https://via.placeholder.com/100';
                    if (!empty($o['img']) && strpos($o['img'], 'http') === false) {
                        $img_path = '../' . $o['img'];
                    }
                    $status = strtoupper($o['Order_Status'] ?? 'PENDING');
                    $status_color = ($status == 'DELIVERED' || $status == 'SHIPPED') ? 'green' : 'orange';
                ?>
                <div class="order-card">
                    <img src="<?= htmlspecialchars($img_path) ?>" alt="<?= htmlspecialchars($o['Prod_Name']) ?>" class="order-img">
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
                                    <?php if (!empty($o['Ship_ProofImg'])): ?>
                                        <a href="../<?= htmlspecialchars($o['Ship_ProofImg']) ?>" target="_blank" style="display: block; font-size: 9px; color: #000; font-weight: 700; margin-top: 5px; text-decoration: underline;">VIEW PROOF</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="order-actions" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                                <?php if ($status === 'PENDING'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="order_id" value="<?= htmlspecialchars($o['Order_Id']) ?>">
                                        <input type="hidden" name="new_status" value="CONFIRMED">
                                        <button type="submit" name="update_status" class="btn-dark" style="padding: 10px 15px; font-size: 9px;">CONFIRM ORDER</button>
                                    </form>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Cancel this order?');">
                                        <input type="hidden" name="order_id" value="<?= htmlspecialchars($o['Order_Id']) ?>">
                                        <input type="hidden" name="new_status" value="CANCELLED">
                                        <button type="submit" name="update_status" class="btn-secondary" style="padding: 10px 15px; font-size: 9px;">CANCEL</button>
                                    </form>
                                <?php elseif ($status === 'CONFIRMED' || $status === 'PACKED'): ?>
                                    <?php if ($status === 'CONFIRMED'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="order_id" value="<?= htmlspecialchars($o['Order_Id']) ?>">
                                        <input type="hidden" name="new_status" value="PACKED">
                                        <button type="submit" name="update_status" class="btn-secondary" style="padding: 10px 15px; font-size: 9px;">MARK PACKED</button>
                                    </form>
                                    <?php endif; ?>
                                    <form method="POST" style="display: flex; gap: 10px; align-items: center;">
                                        <input type="hidden" name="order_id" value="<?= htmlspecialchars($o['Order_Id']) ?>">
                                        <select name="driver_id" style="padding: 8px; font-size: 11px; border: 1px solid #ddd;" required>
                                            <option value="">SELECT DRIVER...</option>
                                            <?php foreach ($online_drivers as $d): ?>
                                                <option value="<?= htmlspecialchars($d['Driv_Id']) ?>">
                                                    <?= htmlspecialchars(trim(($d['Driv_FirstName'] ?? '') . ' ' . ($d['Driv_LastName'] ?? ''))) ?>
                                                    (<?= htmlspecialchars($d['Driv_VehicleType'] ?? 'Vehicle') ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" name="assign_driver" class="btn-dark" style="padding: 10px 15px; font-size: 9px;">ASSIGN DRIVER</button>
                                    </form>
                                <?php else: ?>
                                    <span style="font-size: 10px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;"><?= htmlspecialchars($status) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
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
