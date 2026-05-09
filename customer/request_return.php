<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$cust_id = $_SESSION['user_id'];

// Verify order exists and belongs to customer and is DELIVERED
$check = $conn->prepare("SELECT o.Order_Status, s.Ship_DeliveredAt 
                         FROM ORDERS o 
                         LEFT JOIN shipment s ON o.Order_Id = s.Order_Id
                         WHERE o.Order_Id = ? AND o.Cust_Id = ?");
$check->bind_param("ii", $order_id, $cust_id);
$check->execute();
$order = $check->get_result()->fetch_assoc();

if (!$order || strtoupper($order['Order_Status']) !== 'DELIVERED') {
    die("Invalid request or order not eligible for return.");
}

// Check for existing return
$ret_check = $conn->query("SELECT Rtrn_Id FROM return_request rr JOIN ORDER_ITEM oi ON rr.OdItm_Id = oi.OdItm_Id WHERE oi.Order_Id = $order_id");
if ($ret_check->num_rows > 0) {
    header("Location: order_details.php?id=$order_id");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_return'])) {
    $reason = mysqli_real_escape_string($conn, $_POST['reason']);
    $evidence_url = NULL;

    // Handle File Upload
    if (isset($_FILES['evidence']) && $_FILES['evidence']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['evidence']['name'], PATHINFO_EXTENSION);
        $filename = "return_" . $order_id . "_" . time() . "." . $ext;
        $target_dir = "../assets/images/returns/";
        
        if (move_uploaded_file($_FILES['evidence']['tmp_name'], $target_dir . $filename)) {
            $evidence_url = "assets/images/returns/" . $filename;
        }
    }

    // Get next ID
    $res_rid = $conn->query("SELECT MAX(Rtrn_Id) as max_id FROM return_request");
    $next_rid = ($res_rid->fetch_assoc()['max_id'] ?? 0) + 1;

    $stmt = $conn->prepare("INSERT INTO return_request (Rtrn_Id, OdItm_Id, Cust_Id, Rtrn_Reason, Rtrn_PicEvidence, Rtrn_Type, Rtrn_Status, Rtrn_CreatedAt) VALUES (?, (SELECT OdItm_Id FROM ORDER_ITEM WHERE Order_Id = ? LIMIT 1), ?, ?, ?, 'RETURN', 'PENDING', NOW())");
    $stmt->bind_param("iiiss", $next_rid, $order_id, $cust_id, $reason, $evidence_url);
    
    if ($stmt->execute()) {
        header("Location: order_details.php?id=$order_id&return=submitted");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <title>ZALORA — Request Return #<?= $order_id ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="../assets/css/global.css"/>
    <style>
        body { background: #f9f9f9; font-family: 'Montserrat', sans-serif; }
        .container { max-width: 600px; margin: 60px auto; padding: 0 20px; }
        .card { background: #fff; border: 1px solid #eee; padding: 40px; }
        .title { font-size: 20px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 30px; text-align: center; }
        .form-group { margin-bottom: 25px; }
        .form-group label { display: block; font-size: 10px; font-weight: 800; color: #999; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 10px; }
        .form-group textarea, .form-group input { width: 100%; padding: 15px; border: 1px solid #eee; font-family: inherit; font-size: 14px; box-sizing: border-box; }
        .btn-submit { width: 100%; background: #000; color: #fff; border: none; padding: 20px; font-weight: 700; text-transform: uppercase; cursor: pointer; letter-spacing: 0.1em; }
        .nav-logo { font-size: 24px; font-weight: 700; text-align: center; display: block; margin: 30px 0; text-decoration: none; color: #000; letter-spacing: 0.2em; }
        .policy-note { font-size: 12px; color: #666; line-height: 1.6; margin-top: 30px; padding: 20px; background: #fafafa; border: 1px dashed #eee; }
    </style>
</head>
<body>

<a href="../index.php" class="nav-logo">ZALORA</a>

<div class="container">
    <div class="card">
        <h1 class="title">Initiate Return</h1>
        <p style="font-size: 13px; color: #666; text-align: center; margin-bottom: 40px;">Order #<?= $order_id ?></p>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Reason for Return</label>
                <textarea name="reason" rows="5" placeholder="Please describe the issue (e.g., Wrong size, Damaged on arrival, Not as described)" required></textarea>
            </div>

            <div class="form-group">
                <label>Photo Evidence (Optional)</label>
                <input type="file" name="evidence" accept="image/*">
                <p style="font-size: 11px; color: #999; margin-top: 8px;">Photos help us process your request faster.</p>
            </div>

            <button type="submit" name="submit_return" class="btn-submit">Submit Request</button>
        </form>

        <div class="policy-note">
            <strong>Return Policy:</strong> Returns are accepted within 30 days of delivery. Items must be in original condition with tags attached. Once submitted, our team will review your request within 24-48 hours.
        </div>
    </div>
</div>

</body>
</html>
