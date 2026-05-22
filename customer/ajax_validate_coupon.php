<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['code'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$code = trim($_POST['code']);
$subtotal = floatval($_POST['subtotal']);
$cust_id = $_SESSION['user_id'];
$now = date('Y-m-d H:i:s');

// 1. Check general COUPON table
$coupons = $database->getReference('coupon')->orderByChild('Coup_Code')->equalTo($code)->getSnapshot()->getValue();

if ($coupons) {
    foreach ($coupons as $c_key => $coupon) {
        // Validate manual conditions
        if (($coupon['Coup_IsActive'] ?? 0) == 1 && ($coupon['Is_Approved'] ?? 0) == 1 && $now >= ($coupon['Coup_ValidFrom'] ?? '') && $now <= ($coupon['Coup_ValidUntil'] ?? '9999-12-31')) {
            
            // Check min spend
            if ($subtotal < ($coupon['Coup_MinOrderAmt'] ?? 0)) {
                echo json_encode(['success' => false, 'message' => 'Min spend PHP ' . number_format($coupon['Coup_MinOrderAmt'] ?? 0, 2) . ' required']);
                exit;
            }
            
            // Check usage limit
            if (($coupon['Coup_UsedCount'] ?? 0) >= ($coupon['Coup_MaxUses'] ?? 999)) {
                echo json_encode(['success' => false, 'message' => 'Coupon fully redeemed']);
                exit;
            }
            
            $discount = 0;
            if (($coupon['Coup_DiscType'] ?? '') == 'PERCENTAGE') {
                $discount = $subtotal * (($coupon['Coup_DiscValue'] ?? 0) / 100);
            } else {
                $discount = floatval($coupon['Coup_DiscValue'] ?? 0);
            }
            
            echo json_encode([
                'success' => true,
                'discount' => $discount,
                'type' => 'coupon',
                'id' => $coupon['Coup_Id'] ?? $c_key,
                'message' => 'Coupon applied successfully!'
            ]);
            exit;
        }
    }
}

// 2. Check customer-specific VOUCHER table
$vouchers = $database->getReference('voucher')->orderByChild('Vouch_Code')->equalTo($code)->getSnapshot()->getValue();

if ($vouchers) {
    foreach ($vouchers as $v_key => $voucher) {
        if (($voucher['Cust_Id'] ?? '') == $cust_id && ($voucher['Vouch_IsUsed'] ?? 0) == 0 && $now < ($voucher['Vouch_Expiry'] ?? '9999-12-31')) {
            
            $discount = 0;
            if (($voucher['Vouch_DiscType'] ?? '') == 'PERCENTAGE') {
                $discount = $subtotal * (($voucher['Vouch_DiscValue'] ?? 0) / 100);
            } else {
                $discount = floatval($voucher['Vouch_DiscValue'] ?? 0);
            }
            
            echo json_encode([
                'success' => true,
                'discount' => $discount,
                'type' => 'voucher',
                'id' => $voucher['Vouch_Id'] ?? $v_key,
                'message' => 'Voucher applied successfully!'
            ]);
            exit;
        }
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid or expired code']);
