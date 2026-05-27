<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$order_id = isset($_GET['id']) ? $_GET['id'] : '';
$cust_id = $_SESSION['user_id'];

// 1. Fetch Order Header + Address + Driver Info + Proof
$orderSnapshot = $database->getReference('orders')->orderByChild('Order_Id')->equalTo($order_id)->getSnapshot()->getValue();
$order = $orderSnapshot ? reset($orderSnapshot) : null;

if (!$order || ($order['Cust_Id'] ?? '') != $cust_id) {
    die("Order not found or access denied.");
}

// Fetch Address
$addr_id = $order['Addrs_Id'] ?? '';
$addrSnapshot = $database->getReference('address')->orderByChild('Addrs_id')->equalTo($addr_id)->getSnapshot()->getValue();
$addr = $addrSnapshot ? reset($addrSnapshot) : [];
if ($addr) {
    $order = array_merge($order, $addr);
}

// Fetch Shipment & Driver Info
$shipSnapshot = $database->getReference('shipment')->orderByChild('Order_Id')->equalTo($order_id)->getSnapshot()->getValue();
$shipment = $shipSnapshot ? reset($shipSnapshot) : [];
if ($shipment) {
    $order = array_merge($order, $shipment);
    
    $driv_id = $shipment['Driv_Id'] ?? '';
    if (!empty($driv_id)) {
        $drivSnapshot = $database->getReference('driver')->orderByChild('Driv_Id')->equalTo($driv_id)->getSnapshot()->getValue();
        $driver = $drivSnapshot ? reset($drivSnapshot) : [];
        if ($driver) {
            $order = array_merge($order, $driver);
        }
    }
}

// 2. Fetch Order Items
$items = [];
$oiSnapshot = $database->getReference('order_item')->orderByChild('Order_Id')->equalTo($order_id)->getSnapshot()->getValue();
if ($oiSnapshot) {
    $allVariants = $database->getReference('product_variant')->getSnapshot()->getValue() ?: [];
    $allProducts = $database->getReference('product')->getSnapshot()->getValue() ?: [];
    $allImages = $database->getReference('product_image')->getSnapshot()->getValue() ?: [];

    foreach ($oiSnapshot as $oi_key => $oi) {
        $pvar_id = $oi['PVar_Id'] ?? '';
        $variant = null;
        foreach ($allVariants as $v) {
            if (($v['PVar_Id'] ?? '') == $pvar_id) { $variant = $v; break; }
        }
        
        if ($variant) {
            $prod_id = $variant['Prod_Id'] ?? '';
            $product = null;
            foreach ($allProducts as $p) {
                if (($p['Prod_Id'] ?? '') == $prod_id) { $product = $p; break; }
            }
            
            if ($product) {
                $img = '';
                foreach ($allImages as $pi) {
                    if (($pi['Prod_Id'] ?? '') == $prod_id && ($pi['PImg_IsPrimary'] ?? 0) == 1) {
                        $img = $pi['PImg_ImgUrl'] ?? '';
                        break;
                    }
                }
                
                $items[] = [
                    'OdItm_Id' => $oi['OdItm_Id'] ?? $oi_key,
                    'Prod_Name' => $product['Prod_Name'] ?? '',
                    'ProductId' => $product['Prod_Id'] ?? $prod_id,
                    'PVar_Size' => $variant['PVar_Size'] ?? '',
                    'PVar_Color' => $variant['PVar_Color'] ?? '',
                    'OdItm_Quantity' => $oi['OdItm_Quantity'] ?? 0,
                    'OdItm_Subtotal' => $oi['OdItm_Subtotal'] ?? 0,
                    'img' => $img
                ];
            }
        }
    }
}

// 3. Handle Review Submission
if (isset($_POST['submit_review'])) {
    $oditm_id = $_POST['oditm_id'];
    $prod_id = $_POST['prod_id'];
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment']);
    $pic_url = trim($_POST['pic_url']);
    
    $newReviewRef = $database->getReference('review')->push();
    $newReviewRef->set([
        'Rview_Id' => $newReviewRef->getKey(),
        'Prod_Id' => $prod_id,
        'Cust_Id' => $cust_id,
        'OdItm_Id' => $oditm_id,
        'Rview_Rating' => $rating,
        'Rview_Txt' => $comment,
        'Rview_PicUrl' => $pic_url,
        'Rview_IsApproved' => 0,
        'Rview_CreatedAt' => date('Y-m-d H:i:s')
    ]);
    header("Location: order_details.php?id=$order_id&review=success");
    exit;
}

// 6. Check for existing Return Request
$return_data = null;
$returnRequests = $database->getReference('return_request')->getSnapshot()->getValue() ?: [];
foreach ($returnRequests as $rr) {
    $rr_oditm_id = $rr['OdItm_Id'] ?? '';
    foreach ($items as $itm) {
        if ($itm['OdItm_Id'] == $rr_oditm_id) {
            $return_data = $rr;
            break 2;
        }
    }
}

$nav_links = ["WOMEN", "MEN", "KIDS", "LUXURY", "BEAUTY"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <title>ZALORA — Order #<?= $order_id ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="../assets/css/global.css"/>
    <link rel="stylesheet" href="../assets/css/order-details.css?v=<?= time() ?>">
</head>
<body>

<a href="../index.php" class="nav-logo">ZALORA</a>

<div class="detail-container">
    <a href="profile.php" class="back-link">← Back to Profile</a>

    <?php if (isset($_GET['review']) && $_GET['review'] === 'success'): ?>
        <div style="background: #e6fffa; border-left: 4px solid #38b2ac; padding: 15px; margin-bottom: 20px; font-size: 12px;">
            Thank you! Your review has been submitted successfully.
        </div>
    <?php endif; ?>

    <div class="order-header">
        <div>
            <h1 class="order-id">Order #<?= $order_id ?></h1>
            <p class="order-date">Placed on <?= date('F j, Y', strtotime($order['Order_PlacedAt'])) ?></p>
        </div>
        <div class="status-badge"><?= $order['Order_Status'] ?></div>
    </div>

    <?php
    $status = strtoupper($order['Order_Status']);
    $is_cancelled = ($status === 'CANCELLED');
    $is_returned = ($status === 'RETURNED');

    $step1_active = !$is_cancelled && !$is_returned;
    $step1_completed = in_array($status, ['CONFIRMED', 'PACKED', 'SHIPPED', 'DELIVERED']);

    $step2_active = in_array($status, ['CONFIRMED', 'PACKED', 'SHIPPED', 'DELIVERED']);
    $step2_completed = in_array($status, ['SHIPPED', 'DELIVERED']);

    $step3_active = in_array($status, ['SHIPPED', 'DELIVERED']);
    $step3_completed = ($status === 'DELIVERED');

    $step4_active = ($status === 'DELIVERED');
    $step4_completed = ($status === 'DELIVERED');

    if ($status === 'DELIVERED') {
        $progress_pct = 100;
    } elseif ($status === 'SHIPPED') {
        $progress_pct = 66;
    } elseif (in_array($status, ['CONFIRMED', 'PACKED'])) {
        $progress_pct = 33;
    } else {
        $progress_pct = 0;
    }
    ?>

    <!-- ORDER TRACKING TIMELINE -->
    <?php if ($is_cancelled): ?>
        <div class="tracking-wrapper" style="border-left: 5px solid var(--accent-red-text); background: var(--accent-red-bg);">
            <h3 style="font-size: 13px; font-weight: 700; color: var(--accent-red-text); margin: 0 0 5px 0; text-transform: uppercase;">Order Cancelled</h3>
            <p style="font-size: 12px; color: var(--accent-red-text); margin: 0; opacity: 0.8;">This order has been cancelled and will not be processed further. If you have any concerns, please contact support.</p>
        </div>
    <?php elseif ($is_returned): ?>
        <div class="tracking-wrapper" style="border-left: 5px solid var(--accent-red-text); background: var(--accent-red-bg);">
            <h3 style="font-size: 13px; font-weight: 700; color: var(--accent-red-text); margin: 0 0 5px 0; text-transform: uppercase;">Order Returned</h3>
            <p style="font-size: 12px; color: var(--accent-red-text); margin: 0; opacity: 0.8;">The items in this order have been returned. Refund or exchange details have been sent to your email.</p>
        </div>
    <?php else: ?>
        <div class="tracking-wrapper">
            <div class="tracking-title">Track Shipment</div>
            <div class="stepper-container">
                <div class="stepper-line">
                    <div class="stepper-line-progress" style="width: <?= $progress_pct ?>%;"></div>
                </div>
                
                <!-- Step 1 -->
                <div class="step-node <?= $step1_completed ? 'completed' : ($step1_active ? 'active' : '') ?>">
                    <div class="step-dot">1</div>
                    <div class="step-label">Placed</div>
                    <div class="step-subtext"><?= date('M d', strtotime($order['Order_PlacedAt'])) ?></div>
                </div>
                
                <!-- Step 2 -->
                <div class="step-node <?= $step2_completed ? 'completed' : ($step2_active ? 'active' : '') ?>">
                    <div class="step-dot">2</div>
                    <div class="step-label">Confirmed</div>
                    <div class="step-subtext"><?= in_array($status, ['CONFIRMED', 'PACKED', 'SHIPPED', 'DELIVERED']) ? 'Processed' : 'Pending' ?></div>
                </div>
                
                <!-- Step 3 -->
                <div class="step-node <?= $step3_completed ? 'completed' : ($step3_active ? 'active' : '') ?>">
                    <div class="step-dot">3</div>
                    <div class="step-label">Shipped</div>
                    <div class="step-subtext"><?= in_array($status, ['SHIPPED', 'DELIVERED']) ? 'In Transit' : 'Awaiting' ?></div>
                </div>
                
                <!-- Step 4 -->
                <div class="step-node <?= $step4_completed ? 'completed' : ($step4_active ? 'active' : '') ?>">
                    <div class="step-dot">4</div>
                    <div class="step-label">Delivered</div>
                    <div class="step-subtext"><?= ($status === 'DELIVERED' && isset($order['Ship_DeliveredAt']) && $order['Ship_DeliveredAt']) ? date('M d', strtotime($order['Ship_DeliveredAt'])) : 'Expected' ?></div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2 class="card-title">Order Items</h2>
        <?php foreach($items as $item): 
            $img = $item['img'] ?? 'https://via.placeholder.com/100';
            if ($img && strpos($img, 'http') === false && strpos($img, 'data:') === false) $img = '../' . $img;
            
            // Check if reviewed
            $rev_check = $database->getReference('review')->orderByChild('OdItm_Id')->equalTo($item['OdItm_Id'])->getSnapshot()->getValue();
            $is_reviewed = !empty($rev_check);
        ?>
        <div class="item-row">
            <img src="<?= htmlspecialchars($img) ?>" class="item-img" alt="<?= htmlspecialchars($item['Prod_Name']) ?>"/>
            <div class="item-info">
                <h4><?= htmlspecialchars($item['Prod_Name']) ?></h4>
                <p class="item-variant"><?= $item['PVar_Color'] ? $item['PVar_Color'] . ' • ' : '' ?>Size <?= $item['PVar_Size'] ?></p>
                <p class="item-variant">Quantity: <?= $item['OdItm_Quantity'] ?></p>
                
                <?php if (strtoupper($order['Order_Status']) === 'DELIVERED'): ?>
                    <?php if ($is_reviewed): ?>
                        <span style="font-size: 10px; color: #27ae60; font-weight: 700; text-transform: uppercase;">✓ Reviewed</span>
                    <?php else: ?>
                        <button class="btn-review" onclick="openReviewModal('<?= $item['OdItm_Id'] ?>', '<?= $item['ProductId'] ?>', '<?= addslashes($item['Prod_Name']) ?>')">Write Review</button>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <div class="item-price">$<?= number_format($item['OdItm_Subtotal'], 2) ?></div>
        </div>
        <?php endforeach; ?>

        <div style="margin-top: 30px;">
            <div class="summary-row">
                <span>Subtotal</span>
                <span>$<?= number_format($order['Order_TotalAmnt'] / 1.08, 2) ?></span>
            </div>
            <div class="summary-row">
                <span>Tax (8%)</span>
                <span>$<?= number_format($order['Order_TotalAmnt'] - ($order['Order_TotalAmnt'] / 1.08), 2) ?></span>
            </div>
            <div class="summary-row">
                <span>Shipping</span>
                <span style="color: green; font-weight: 700;">FREE</span>
            </div>
            <div class="total-row">
                <span>Order Total</span>
                <span>$<?= number_format($order['Order_TotalAmnt'], 2) ?></span>
            </div>
        </div>

        <?php if ($order['Order_Status'] === 'DELIVERED'): ?>
            <div style="margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h3 style="font-size: 11px; font-weight: 700; text-transform: uppercase; margin-bottom: 5px; letter-spacing: 0.1em;">Need a Return?</h3>
                    <p style="font-size: 11px; color: #999;">Eligible for return until <?= date('M d, Y', strtotime(($order['Ship_DeliveredAt'] ?? date('Y-m-d')) . ' + 30 days')) ?></p>
                </div>
                <?php if ($return_data): ?>
                    <span style="background: #eee; padding: 12px 25px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em;">Return: <?= $return_data['Rtrn_Status'] ?></span>
                <?php else: ?>
                    <a href="request_return.php?id=<?= $order_id ?>" style="background: #000; color: #fff; text-decoration: none; padding: 12px 25px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em;">Initiate Return</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2 class="card-title">Shipping Address</h2>
        <div class="shipping-info">
            <p><strong><?= htmlspecialchars($order['Addrs_RcpntName']) ?></strong></p>
            <p><?= htmlspecialchars($order['Addrs_Street']) ?></p>
            <p><?= htmlspecialchars($order['Addrs_City']) ?>, <?= htmlspecialchars($order['Addrs_ZipCode']) ?></p>
            <p>Singapore</p>
        </div>
    </div>

    <?php if (!empty($order['Ship_ProofImg'])): 
        $proof_img = $order['Ship_ProofImg'];
        if (strpos($proof_img, 'http') === false && strpos($proof_img, 'data:') === false) {
            $proof_img = '../' . $proof_img;
        }
    ?>
        <div class="card">
            <h2 class="card-title">Proof of Delivery</h2>
            <div style="text-align: center;">
                <img src="<?= htmlspecialchars($proof_img) ?>" style="max-width: 100%; border: 1px solid #eee;" alt="Delivery Proof">
                <p style="font-size: 11px; color: #999; margin-top: 10px; text-transform: uppercase; font-weight: 700;">Captured by driver upon handover</p>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($order['Driv_FirstName'])): ?>
        <div class="card" style="border-left: 5px solid #000;">
            <h2 class="card-title">Delivery Information</h2>
            <div style="display: flex; align-items: center; gap: 20px;">
                <div style="width: 50px; height: 50px; background: #eee; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                </div>
                <div>
                    <p style="font-size: 14px; font-weight: 700; margin: 0;"><?= htmlspecialchars($order['Driv_FirstName'] . ' ' . $order['Driv_LastName']) ?></p>
                    <p style="font-size: 11px; color: #666; text-transform: uppercase; margin-top: 4px;"><?= htmlspecialchars($order['Driv_VehicleType'] ?? '') ?></p>
                </div>
                <div style="margin-left: auto;">
                    <a href="tel:<?= htmlspecialchars($order['Driv_Phone'] ?? '') ?>" style="display: inline-block; background: #f4f4f4; padding: 10px; border-radius: 50%;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.88 12.88 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Review Modal -->
<div id="reviewModal" class="modal">
    <div class="modal-content">
        <span class="modal-close" onclick="closeReviewModal()">&times;</span>
        <h2 id="modal-title" style="font-size: 14px; font-weight: 700; text-transform: uppercase; margin-bottom: 25px;">Rate Product</h2>
        <form method="POST" class="review-form">
            <input type="hidden" name="oditm_id" id="modal-oditm-id">
            <input type="hidden" name="prod_id" id="modal-prod-id">
            
            <label>Rating</label>
            <select name="rating" required>
                <option value="5">5 Stars - Excellent</option>
                <option value="4">4 Stars - Very Good</option>
                <option value="3">3 Stars - Average</option>
                <option value="2">2 Stars - Poor</option>
                <option value="1">1 Star - Terrible</option>
            </select>
            
            <label>Your Thoughts</label>
            <textarea name="comment" rows="4" placeholder="How was the fit? What about the material?" required></textarea>
            
            <label>Photo URL (Optional)</label>
            <input type="text" name="pic_url" placeholder="https://example.com/photo.jpg">
            
            <button type="submit" name="submit_review" class="btn-submit-review">Submit Review</button>
        </form>
    </div>
</div>

<script>
function openReviewModal(oditmId, prodId, prodName) {
    document.getElementById('modal-oditm-id').value = oditmId;
    document.getElementById('modal-prod-id').value = prodId;
    document.getElementById('modal-title').innerText = 'Review: ' + prodName;
    document.getElementById('reviewModal').style.display = 'block';
}

function closeReviewModal() {
    document.getElementById('reviewModal').style.display = 'none';
}

// Close if clicked outside
window.onclick = function(event) {
    let modal = document.getElementById('reviewModal');
    if (event.target == modal) {
        closeReviewModal();
    }
}
</script>

</body>
</html>
