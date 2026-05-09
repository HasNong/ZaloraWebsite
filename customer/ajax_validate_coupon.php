<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['code'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$code = mysqli_real_escape_string($conn, $_POST['code']);
$subtotal = floatval($_POST['subtotal']);
$cust_id = $_SESSION['user_id'];

// 1. Check general COUPON table
$stmt = $conn->prepare("SELECT * FROM coupon WHERE Coup_Code = ? AND Coup_IsActive = 1 AND Is_Approved = 1 AND NOW() BETWEEN Coup_ValidFrom AND Coup_ValidUntil LIMIT 1");
$stmt->bind_param("s", $code);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    $coupon = $res->fetch_assoc();
    
    // Check min spend
    if ($subtotal < $coupon['Coup_MinOrderAmt']) {
        echo json_encode(['success' => false, 'message' => 'Min spend PHP ' . number_format($coupon['Coup_MinOrderAmt'], 2) . ' required']);
        exit;
    }
    
    // Check usage limit
    if ($coupon['Coup_UsedCount'] >= $coupon['Coup_MaxUses']) {
        echo json_encode(['success' => false, 'message' => 'Coupon fully redeemed']);
        exit;
    }
    
    $discount = 0;
    if ($coupon['Coup_DiscType'] == 'PERCENTAGE') {
        $discount = $subtotal * ($coupon['Coup_DiscValue'] / 100);
    } else {
        $discount = $coupon['Coup_DiscValue'];
    }
    
    echo json_encode([
        'success' => true,
        'discount' => $discount,
        'type' => 'coupon',
        'id' => $coupon['Coup_Id'],
        'message' => 'Coupon applied successfully!'
    ]);
    exit;
}

// 2. Check customer-specific VOUCHER table
$stmt_v = $conn->prepare("SELECT * FROM voucher WHERE Vouch_Code = ? AND Cust_Id = ? AND Vouch_IsUsed = 0 AND NOW() < Vouch_Expiry LIMIT 1");
$stmt_v->bind_param("si", $code, $cust_id);
$stmt_v->execute();
$res_v = $stmt_v->get_result();

if ($res_v->num_rows > 0) {
    $voucher = $res_v->fetch_assoc();
    
    $discount = 0;
    if ($voucher['Vouch_DiscType'] == 'PERCENTAGE') {
        $discount = $subtotal * ($voucher['Vouch_DiscValue'] / 100);
    } else {
        $discount = $voucher['Vouch_DiscValue'];
    }
    
    echo json_encode([
        'success' => true,
        'discount' => $discount,
        'type' => 'voucher',
        'id' => $voucher['Vouch_Id'],
        'message' => 'Voucher applied successfully!'
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid or expired code']);
