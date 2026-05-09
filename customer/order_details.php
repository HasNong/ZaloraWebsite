<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$cust_id = $_SESSION['user_id'];

// 1. Fetch Order Header + Address + Driver Info
$order_query = "SELECT o.*, a.Addrs_RcpntName, a.Addrs_Street, a.Addrs_City, a.Addrs_ZipCode,
                       d.Driv_FirstName, d.Driv_LastName, d.Driv_VehicleType, d.Driv_Phone
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
$items_query = "SELECT oi.*, p.Prod_Name, pv.PVar_Size, pv.PVar_Color,
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

    $ins_rev = $conn->prepare("INSERT INTO review (Rview_Id, Prod_Id, Cust_Id, OdItm_Id, Rview_Rating, Rview_Txt, Rview_PicUrl, Rview_IsApproved, Rview_CreatedAt) VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())");
    $ins_rev->bind_param("iiiiiss", $next_rid, $prod_id, $cust_id, $oditm_id, $rating, $comment, $pic_url);
    $ins_rev->execute();
    header("Location: order_details.php?id=$order_id&review=success");
    exit;
}

$nav_links = ["WOMEN", "MEN", "KIDS", "LUXURY", "BEAUTY"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <title>ZALORA — Order #<?= $order_id ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="../assets/css/global.css"/>
    <style>
        body { background: #f9f9f9; font-family: 'Montserrat', sans-serif; }
        .detail-container { max-width: 800px; margin: 40px auto; padding: 0 20px; }
        .back-link { display: inline-block; margin-bottom: 20px; color: #666; text-decoration: none; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.1em; }
        .back-link:hover { color: #000; }
        .order-header { background: #fff; padding: 30px; border: 1px solid #eee; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .order-id { font-size: 20px; font-weight: 700; margin-bottom: 5px; }
        .order-date { font-size: 12px; color: #999; }
        .status-badge { background: #000; color: #fff; padding: 8px 15px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; }
        
        .card { background: #fff; border: 1px solid #eee; padding: 30px; margin-bottom: 20px; }
        .card-title { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        
        .item-row { display: grid; grid-template-columns: 80px 1fr 120px; gap: 20px; padding: 15px 0; border-bottom: 1px solid #fafafa; }
        .item-img { width: 80px; height: 100px; object-fit: cover; }
        .item-info h4 { font-size: 14px; margin-bottom: 5px; }
        .item-variant { font-size: 11px; color: #888; }
        .item-price { text-align: right; font-weight: 600; font-size: 14px; }
        
        .summary-row { display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 10px; }
        .total-row { display: flex; justify-content: space-between; font-weight: 700; font-size: 18px; margin-top: 15px; padding-top: 15px; border-top: 2px solid #eee; }
        
        .shipping-info p { font-size: 13px; color: #666; line-height: 1.6; margin-bottom: 5px; }
        .nav-logo { font-size: 24px; font-weight: 700; text-align: center; display: block; margin: 30px 0; text-decoration: none; color: #000; letter-spacing: 0.2em; }
        
        .btn-review { background: #eee; border: none; padding: 5px 12px; font-size: 10px; font-weight: 700; text-transform: uppercase; cursor: pointer; margin-top: 10px; display: inline-block; }
        .btn-review:hover { background: #000; color: #fff; }
        
        /* Modal Styles */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); }
        .modal-content { background: #fff; width: 400px; margin: 100px auto; padding: 30px; position: relative; }
        .modal-close { position: absolute; top: 15px; right: 20px; cursor: pointer; font-size: 24px; }
        .review-form label { display: block; font-size: 10px; font-weight: 700; text-transform: uppercase; margin-bottom: 8px; color: #999; }
        .review-form select, .review-form textarea, .review-form input { width: 100%; padding: 12px; margin-bottom: 20px; border: 1px solid #eee; font-family: inherit; }
        .btn-submit-review { width: 100%; background: #000; color: #fff; border: none; padding: 15px; font-weight: 700; text-transform: uppercase; cursor: pointer; }
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
                
                <?php if ($order['Order_Status'] === 'DELIVERED'): ?>
                    <?php if ($is_reviewed): ?>
                        <span style="font-size: 10px; color: #27ae60; font-weight: 700; text-transform: uppercase;">✓ Reviewed</span>
                    <?php else: ?>
                        <button class="btn-review" onclick="openReviewModal(<?= $item['OdItm_Id'] ?>, <?= $item['Prod_Id'] ?>, '<?= addslashes($item['Prod_Name']) ?>')">Write Review</button>
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
