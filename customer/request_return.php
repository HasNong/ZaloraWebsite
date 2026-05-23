<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$order_id = $_GET['id'] ?? null;
$cust_id = $_SESSION['user_id'];

if (!$order_id) {
    die("Invalid request.");
}

// Verify order exists and belongs to customer and is DELIVERED
$ordersRef = $database->getReference('orders')->orderByChild('Order_Id')->equalTo($order_id)->getSnapshot()->getValue();
$order = $ordersRef ? current($ordersRef) : null;

if (!$order || ($order['Cust_Id'] ?? 0) != $cust_id || strtoupper($order['Order_Status'] ?? '') !== 'DELIVERED') {
    die("Invalid request or order not eligible for return.");
}

// Fetch Order Items for this order to attach return to the first item (legacy logic emulation)
$orderItemsRef = $database->getReference('order_item')->orderByChild('Order_Id')->equalTo($order_id)->getSnapshot()->getValue();
$first_item_id = null;
if ($orderItemsRef) {
    $first_item = current($orderItemsRef);
    $first_item_id = $first_item['OdItm_Id'] ?? null;
}

// Check for existing return
if ($first_item_id) {
    $returnsRef = $database->getReference('return_request')->orderByChild('OdItm_Id')->equalTo($first_item_id)->getSnapshot()->getValue();
    if ($returnsRef) {
        header("Location: order_details.php?id=$order_id");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_return'])) {
    $reason = trim($_POST['reason'] ?? '');
    $evidence_url = NULL;

    // Handle File Upload
    if (isset($_FILES['evidence']) && $_FILES['evidence']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['evidence']['name'], PATHINFO_EXTENSION);
        $filename = "return_" . substr(md5($order_id), 0, 8) . "_" . time() . "." . $ext;
        $target_dir = "../assets/images/returns/";
        
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

        if (move_uploaded_file($_FILES['evidence']['tmp_name'], $target_dir . $filename)) {
            $evidence_url = "assets/images/returns/" . $filename;
        }
    }

    $newRet = $database->getReference('return_request')->push();
    $newRet->set([
        'Rtrn_Id' => $newRet->getKey(),
        'OdItm_Id' => $first_item_id,
        'Cust_Id' => $cust_id,
        'Rtrn_Reason' => $reason,
        'Rtrn_PicEvidence' => $evidence_url,
        'Rtrn_Type' => 'RETURN',
        'Rtrn_Status' => 'PENDING',
        'Rtrn_CreatedAt' => date('Y-m-d H:i:s')
    ]);
    
    header("Location: order_details.php?id=$order_id&return=submitted");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <title>ZALORA — Request Return #<?= $order_id ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="../assets/css/global.css"/>
    <link rel="stylesheet" href="../assets/css/request-return.css?v=<?= time() ?>">
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
