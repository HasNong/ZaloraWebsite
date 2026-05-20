<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$cust_id = $_SESSION['user_id'];

// 1. Fetch Order Header + Address + Driver Info + Proof
$order_query = "SELECT o.*, a.Addrs_RcpntName, a.Addrs_Street, a.Addrs_City, a.Addrs_ZipCode,
                       d.Driv_FirstName, d.Driv_LastName, d.Driv_VehicleType, d.Driv_Phone,
                       s.Ship_ProofImg, s.Ship_DeliveredAt
                FROM ORDERS o
                JOIN ADDRESS a ON o.Addrs_Id = a.Addrs_id
                LEFT JOIN shipment s ON o.Order_Id = s.Order_Id
                LEFT JOIN driver d ON s.Driv_Id = d.Driv_Id
                WHERE o.Order_Id = ? AND o.Cust_Id = ?";
$stmt = $conn->prepare($order_query);
$stmt->bind_param("ii", $order_id, $cust_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    die("Order not found or access denied.");
}

// 2. Fetch Order Items
$items_query = "SELECT oi.*, p.Prod_Name, p.Prod_Id AS ProductId, pv.PVar_Size, pv.PVar_Color,
                (SELECT PImg_ImgUrl FROM PRODUCT_IMAGE WHERE Prod_Id = p.Prod_Id AND PImg_IsPrimary = 1 LIMIT 1) as img
                FROM ORDER_ITEM oi
                JOIN PRODUCT_VARIANT pv ON oi.PVar_Id = pv.PVar_Id
                JOIN PRODUCT p ON pv.Prod_Id = p.Prod_Id
                WHERE oi.Order_Id = ?";
$stmt_i = $conn->prepare($items_query);
$stmt_i->bind_param("i", $order_id);
$stmt_i->execute();
$items = $stmt_i->get_result();

// 3. Handle Review Submission
if (isset($_POST['submit_review'])) {
    $oditm_id = intval($_POST['oditm_id']);
    $prod_id = intval($_POST['prod_id']);
    $rating = intval($_POST['rating']);
    $comment = mysqli_real_escape_string($conn, $_POST['comment']);
    $pic_url = mysqli_real_escape_string($conn, $_POST['pic_url']);
    
    // Get next ID
    $res_rid = $conn->query("SELECT MAX(Rview_Id) as max_id FROM review");
    $next_rid = ($res_rid->fetch_assoc()['max_id'] ?? 0) + 1;

    // Insert review with IsApproved = 0 (Requires Admin Review)
    $ins_rev = $conn->prepare("INSERT INTO review (Rview_Id, Prod_Id, Cust_Id, OdItm_Id, Rview_Rating, Rview_Txt, Rview_PicUrl, Rview_IsApproved, Rview_CreatedAt) VALUES (?, ?, ?, ?, ?, ?, ?, 0, NOW())");
    $ins_rev->bind_param("iiiiiss", $next_rid, $prod_id, $cust_id, $oditm_id, $rating, $comment, $pic_url);
    $ins_rev->execute();
    header("Location: order_details.php?id=$order_id&review=success");
    exit;
}

// 6. Check for existing Return Request
$res_ret = $conn->query("SELECT Rtrn_Status FROM return_request rr JOIN ORDER_ITEM oi ON rr.OdItm_Id = oi.OdItm_Id WHERE oi.Order_Id = $order_id LIMIT 1");
$return_data = $res_ret->fetch_assoc();

$nav_links = ["WOMEN", "MEN", "KIDS", "LUXURY", "BEAUTY"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <title>ZALORA — Order #<?= $order_id ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="../assets/css/global.css"/>
    <style>
        :root {
            --black: #000000;
            --white: #ffffff;
            --background: #fdfdfd;
            --border: rgba(0,0,0,0.06);
            --border-light: rgba(0,0,0,0.03);
            --text-dark: #111111;
            --text-light: #777777;
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 18px;
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.03);
            --shadow-md: 0 8px 24px rgba(0, 0, 0, 0.06);
            --transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            --accent-green-bg: #e6fffa;
            --accent-green-text: #2e7d32;
            --accent-red-bg: #fdf2f2;
            --accent-red-text: #c53030;
        }
        
        body { background: #fafafa; font-family: 'Outfit', sans-serif; color: var(--text-dark); margin: 0; padding: 0; }
        .detail-container { max-width: 800px; margin: 40px auto; padding: 0 20px; }
        .back-link { display: inline-block; margin-bottom: 20px; color: var(--text-light); text-decoration: none; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; transition: var(--transition); }
        .back-link:hover { color: var(--black); }
        
        .order-header { 
            background: var(--white); 
            padding: 30px; 
            border: 1px solid var(--border); 
            border-radius: var(--radius-md);
            margin-bottom: 20px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            box-shadow: var(--shadow-sm);
        }
        .order-id { font-size: 20px; font-weight: 800; margin: 0 0 5px 0; letter-spacing: -0.02em; }
        .order-date { font-size: 12px; color: var(--text-light); margin: 0; }
        .status-badge { 
            background: var(--black); 
            color: var(--white); 
            padding: 8px 16px; 
            font-size: 10px; 
            font-weight: 700; 
            text-transform: uppercase; 
            letter-spacing: 0.1em; 
            border-radius: var(--radius-sm);
        }
        
        .card { 
            background: var(--white); 
            border: 1px solid var(--border); 
            padding: 35px; 
            margin-bottom: 20px; 
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
        }
        .card-title { 
            font-size: 11px; 
            font-weight: 700; 
            text-transform: uppercase; 
            letter-spacing: 0.1em; 
            margin: 0 0 25px 0; 
            border-bottom: 1px solid var(--border); 
            padding-bottom: 12px; 
            color: var(--text-light);
        }
        
        .item-row { display: grid; grid-template-columns: 80px 1fr 120px; gap: 20px; padding: 20px 0; border-bottom: 1px solid var(--border-light); }
        .item-img { width: 80px; height: 100px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--border); }
        .item-info h4 { font-size: 14px; font-weight: 700; margin: 0 0 5px 0; }
        .item-variant { font-size: 11px; color: var(--text-light); margin: 3px 0; }
        .item-price { text-align: right; font-weight: 700; font-size: 14px; }
        
        .summary-row { display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 10px; color: var(--text-light); }
        .total-row { display: flex; justify-content: space-between; font-weight: 800; font-size: 18px; margin-top: 15px; padding-top: 15px; border-top: 1px solid var(--border); color: var(--black); }
        
        .shipping-info p { font-size: 13px; color: #444; line-height: 1.6; margin: 0 0 5px 0; }
        .nav-logo { font-size: 24px; font-weight: 800; text-align: center; display: block; margin: 30px 0; text-decoration: none; color: var(--black); letter-spacing: 0.25em; }
        
        .btn-review { background: #f4f4f4; border: none; padding: 6px 12px; font-size: 10px; font-weight: 700; text-transform: uppercase; cursor: pointer; margin-top: 10px; display: inline-block; border-radius: var(--radius-sm); transition: var(--transition); }
        .btn-review:hover { background: var(--black); color: var(--white); }
        
        /* Stepper Tracking timeline styles */
        .tracking-wrapper {
            background: var(--white);
            border: 1px solid var(--border);
            padding: 30px;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            margin-bottom: 20px;
        }
        .stepper-container {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin-top: 25px;
            margin-bottom: 15px;
        }
        .stepper-line {
            position: absolute;
            top: 15px;
            left: 45px;
            right: 45px;
            height: 3px;
            background: #eee;
            z-index: 1;
            border-radius: 2px;
        }
        .stepper-line-progress {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            background: var(--black);
            transition: var(--transition);
            border-radius: 2px;
        }
        .step-node {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 2;
            width: 90px;
        }
        .step-dot {
            width: 30px;
            height: 30px;
            background: #f9f9f9;
            border: 3px solid #eee;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 800;
            color: var(--text-light);
            transition: var(--transition);
        }
        .step-label {
            margin-top: 10px;
            font-size: 10px;
            font-weight: 700;
            color: var(--text-light);
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            transition: var(--transition);
        }
        .step-subtext {
            font-size: 9px;
            color: var(--text-light);
            margin-top: 2px;
            text-align: center;
        }
        .step-node.active .step-dot {
            background: var(--white);
            border-color: var(--black);
            color: var(--black);
            box-shadow: 0 0 0 4px rgba(0, 0, 0, 0.05);
        }
        .step-node.active .step-label {
            color: var(--black);
        }
        .step-node.completed .step-dot {
            background: var(--black);
            border-color: var(--black);
            color: var(--white);
        }
        .step-node.completed .step-label {
            color: var(--black);
        }

        /* Modal Styles */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); transition: var(--transition); }
        .modal-content { background: var(--white); width: 400px; margin: 100px auto; padding: 35px; position: relative; border-radius: var(--radius-md); box-shadow: var(--shadow-md); border: 1px solid var(--border); }
        .modal-close { position: absolute; top: 15px; right: 20px; cursor: pointer; font-size: 24px; color: var(--text-light); transition: var(--transition); }
        .modal-close:hover { color: var(--black); }
        .review-form label { display: block; font-size: 10px; font-weight: 700; text-transform: uppercase; margin-bottom: 8px; color: var(--text-light); letter-spacing: 0.05em; }
        .review-form select, .review-form textarea, .review-form input { width: 100%; padding: 12px; margin-bottom: 20px; border: 1px solid var(--border); font-family: inherit; border-radius: var(--radius-sm); outline: none; box-sizing: border-box; transition: var(--transition); }
        .review-form select:focus, .review-form textarea:focus, .review-form input:focus { border-color: var(--black); }
        .btn-submit-review { width: 100%; background: var(--black); color: var(--white); border: none; padding: 15px; font-weight: 700; text-transform: uppercase; cursor: pointer; border-radius: var(--radius-sm); transition: var(--transition); }
        .btn-submit-review:hover { opacity: 0.9; }
    </style>
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
        <?php while($item = $items->fetch_assoc()): 
            $img = $item['img'] ?? 'https://via.placeholder.com/100';
            if ($img && strpos($img, 'http') === false) $img = '../' . $img;
            
            // Check if reviewed
            $rev_check = $conn->query("SELECT Rview_Id FROM review WHERE OdItm_Id = " . $item['OdItm_Id']);
            $is_reviewed = $rev_check->num_rows > 0;
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
                        <button class="btn-review" onclick="openReviewModal(<?= $item['OdItm_Id'] ?>, <?= $item['ProductId'] ?>, '<?= addslashes($item['Prod_Name']) ?>')">Write Review</button>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <div class="item-price">$<?= number_format($item['OdItm_Subtotal'], 2) ?></div>
        </div>
        <?php endwhile; ?>

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

    <?php if ($order['Ship_ProofImg']): ?>
        <div class="card">
            <h2 class="card-title">Proof of Delivery</h2>
            <div style="text-align: center;">
                <img src="../<?= htmlspecialchars($order['Ship_ProofImg']) ?>" style="max-width: 100%; border: 1px solid #eee;" alt="Delivery Proof">
                <p style="font-size: 11px; color: #999; margin-top: 10px; text-transform: uppercase; font-weight: 700;">Captured by driver upon handover</p>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($order['Driv_FirstName']): ?>
        <div class="card" style="border-left: 5px solid #000;">
            <h2 class="card-title">Delivery Information</h2>
            <div style="display: flex; align-items: center; gap: 20px;">
                <div style="width: 50px; height: 50px; background: #eee; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                </div>
                <div>
                    <p style="font-size: 14px; font-weight: 700; margin: 0;"><?= htmlspecialchars($order['Driv_FirstName'] . ' ' . $order['Driv_LastName']) ?></p>
                    <p style="font-size: 11px; color: #666; text-transform: uppercase; margin-top: 4px;"><?= htmlspecialchars($order['Driv_VehicleType']) ?></p>
                </div>
                <div style="margin-left: auto;">
                    <a href="tel:<?= $order['Driv_Phone'] ?>" style="display: inline-block; background: #f4f4f4; padding: 10px; border-radius: 50%;">
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
