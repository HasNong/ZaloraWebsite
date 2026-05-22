<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$cust_id = $_SESSION['user_id'];

// 1. Fetch Cart Totals
$carts = $database->getReference('cart')->orderByChild('Cust_Id')->equalTo($cust_id)->getSnapshot()->getValue();
$subtotal = 0;
$cart_id = null;

if ($carts) {
    $cart_id = reset($carts)['Cart_Id'] ?? key($carts);
    $items = $database->getReference('cart_item')->orderByChild('Cart_Id')->equalTo($cart_id)->getSnapshot()->getValue();
    
    if ($items) {
        $allVariants = $database->getReference('product_variant')->getSnapshot()->getValue() ?: [];
        $allProducts = $database->getReference('product')->getSnapshot()->getValue() ?: [];
        
        foreach ($items as $ci) {
            $pvar_id = $ci['PVar_Id'] ?? '';
            $qty = intval($ci['CItm_Quantity'] ?? 0);
            
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
                    $subtotal += floatval($product['Prod_BasePrice'] ?? 0) * $qty;
                }
            }
        }
    }
}

if ($subtotal == 0) {
    header("Location: cart.php");
    exit();
}

$tax = round($subtotal * 0.08, 2);
$shipping_fee = ($subtotal > 100) ? 0 : 5.00;
$total = $subtotal + $tax + $shipping_fee;

// 2. Fetch Customer Balance & Address
$custSnapshot = $database->getReference('CUSTOMER')->orderByChild('Cust_Id')->equalTo($cust_id)->getSnapshot()->getValue();
$cust = $custSnapshot ? reset($custSnapshot) : null;
$custKey = $custSnapshot ? key($custSnapshot) : null;

$addrSnapshot = $database->getReference('ADDRESS')->orderByChild('Cust_Id')->equalTo($cust_id)->getSnapshot()->getValue();
$defaultAddr = null;
if ($addrSnapshot) {
    foreach ($addrSnapshot as $a) {
        if (($a['Addrs_IsDefault'] ?? 0) == 1) {
            $defaultAddr = $a;
            break;
        }
    }
}

if ($cust && $defaultAddr) {
    $cust = array_merge($cust, $defaultAddr);
}

// 3. Handle Order Placement
$error = "";
if (isset($_POST['place_order'])) {
    if (floatval($cust['Cust_Balance'] ?? 0) < $total) {
        $error = "Insufficient balance in your Zalora Wallet. Please top up!";
    } else {
        try {
            // A. Get or Create Address
            if ($defaultAddr) {
                $addr_id = $defaultAddr['Addrs_id'] ?? '';
            } else {
                $newAddrRef = $database->getReference('ADDRESS')->push();
                $addr_id = $newAddrRef->getKey();
                $full_name = ($cust['Cust_Firstname'] ?? '') . ' ' . ($cust['Cust_Lastname'] ?? '');
                $newAddrRef->set([
                    'Addrs_id' => $addr_id,
                    'Cust_Id' => $cust_id,
                    'Addrs_RcpntName' => $full_name,
                    'Addrs_Street' => 'Not Set',
                    'Addrs_City' => 'Not Set',
                    'Addrs_Province' => 'Not Set',
                    'Addrs_ZipCode' => '0000',
                    'Addrs_IsDefault' => 1,
                    'Addrs_CreatedAt' => date('Y-m-d H:i:s')
                ]);
            }

            // B. Calculate Fees & Discount
            $shipping_fee = ($subtotal > 100) ? 0 : 5.00;
            $discount = 0;
            $applied_coupon_id = null;
            $applied_coupon_key = null;
            $applied_voucher_id = null;
            $applied_voucher_key = null;

            if (isset($_POST['coupon_code']) && !empty($_POST['coupon_code'])) {
                $code = trim($_POST['coupon_code']);
                $now = date('Y-m-d H:i:s');
                
                $coupons = $database->getReference('coupon')->orderByChild('Coup_Code')->equalTo($code)->getSnapshot()->getValue();
                if ($coupons) {
                    foreach ($coupons as $c_key => $cp) {
                        if (($cp['Coup_IsActive'] ?? 0) == 1 && ($cp['Is_Approved'] ?? 0) == 1 && $now >= ($cp['Coup_ValidFrom'] ?? '') && $now <= ($cp['Coup_ValidUntil'] ?? '9999-12-31')) {
                            if ($subtotal >= ($cp['Coup_MinOrderAmt'] ?? 0) && ($cp['Coup_UsedCount'] ?? 0) < ($cp['Coup_MaxUses'] ?? 999)) {
                                $applied_coupon_key = $c_key;
                                $applied_coupon_id = $cp['Coup_Id'] ?? $c_key;
                                $discount = (($cp['Coup_DiscType'] ?? '') == 'PERCENTAGE') ? ($subtotal * (($cp['Coup_DiscValue'] ?? 0) / 100)) : floatval($cp['Coup_DiscValue'] ?? 0);
                            }
                        }
                    }
                }
                
                if (!$applied_coupon_id) {
                    $vouchers = $database->getReference('voucher')->orderByChild('Vouch_Code')->equalTo($code)->getSnapshot()->getValue();
                    if ($vouchers) {
                        foreach ($vouchers as $v_key => $vc) {
                            if (($vc['Cust_Id'] ?? '') == $cust_id && ($vc['Vouch_IsUsed'] ?? 0) == 0 && $now < ($vc['Vouch_Expiry'] ?? '9999-12-31')) {
                                $applied_voucher_key = $v_key;
                                $applied_voucher_id = $vc['Vouch_Id'] ?? $v_key;
                                $discount = (($vc['Vouch_DiscType'] ?? '') == 'PERCENTAGE') ? ($subtotal * (($vc['Vouch_DiscValue'] ?? 0) / 100)) : floatval($vc['Vouch_DiscValue'] ?? 0);
                            }
                        }
                    }
                }
            }

            $final_total = max(0, ($subtotal + $tax + $shipping_fee) - $discount);

            // C. Deduct Balance
            if ($custKey) {
                $newBalance = floatval($cust['Cust_Balance'] ?? 0) - $final_total;
                $database->getReference('CUSTOMER')->getChild($custKey)->update(['Cust_Balance' => $newBalance]);
            }

            // D. Create Order
            $orderRef = $database->getReference('ORDERS')->push();
            $order_id = $orderRef->getKey();
            $orderRef->set([
                'Order_Id' => $order_id,
                'Cust_Id' => $cust_id,
                'Addrs_Id' => $addr_id,
                'Order_Status' => 'PENDING',
                'Order_TotalAmnt' => $final_total,
                'Order_ShipFee' => $shipping_fee,
                'Order_PlacedAt' => date('Y-m-d H:i:s'),
                'Order_UpdatedAt' => date('Y-m-d H:i:s')
            ]);

            // D2. Create Payment Record
            $paymentRef = $database->getReference('payment')->push();
            $paymentRef->set([
                'Pymnt_Id' => $paymentRef->getKey(),
                'Order_Id' => $order_id,
                'Pymnt_Method' => 'BANK_TRANSFER',
                'Pymnt_Status' => 'PAID',
                'Pymnt_Amount' => $final_total,
                'Pymnt_CreatedAt' => date('Y-m-d H:i:s')
            ]);

            // E. Record Coupon Usage
            if ($applied_coupon_key) {
                $oCoupRef = $database->getReference('ORDER_COUPON')->push();
                $oCoupRef->set([
                    'OCoup_Id' => $oCoupRef->getKey(),
                    'Order_Id' => $order_id,
                    'Coup_Id' => $applied_coupon_id,
                    'OCoup_DiscApplied' => $discount,
                    'OCoup_AppliedAt' => date('Y-m-d H:i:s')
                ]);
                $currUses = $database->getReference('coupon')->getChild($applied_coupon_key)->getValue()['Coup_UsedCount'] ?? 0;
                $database->getReference('coupon')->getChild($applied_coupon_key)->update(['Coup_UsedCount' => $currUses + 1]);
            }
            if ($applied_voucher_key) {
                $database->getReference('voucher')->getChild($applied_voucher_key)->update([
                    'Vouch_IsUsed' => 1,
                    'Vouch_UsedAt' => date('Y-m-d H:i:s')
                ]);
            }

            // F. Move Cart Items to Order Items
            $items = $database->getReference('cart_item')->orderByChild('Cart_Id')->equalTo($cart_id)->getSnapshot()->getValue();
            if ($items) {
                $allVariants = $database->getReference('product_variant')->getSnapshot()->getValue() ?: [];
                $allProducts = $database->getReference('product')->getSnapshot()->getValue() ?: [];
                
                foreach ($items as $ci_key => $item) {
                    $pvar_id = $item['PVar_Id'] ?? '';
                    $qty = intval($item['CItm_Quantity'] ?? 0);
                    $basePrice = 0;
                    
                    // lookup base price
                    foreach ($allVariants as $v) {
                        if (($v['PVar_Id'] ?? '') == $pvar_id) {
                            $prod_id = $v['Prod_Id'] ?? '';
                            foreach ($allProducts as $p) {
                                if (($p['Prod_Id'] ?? '') == $prod_id) {
                                    $basePrice = floatval($p['Prod_BasePrice'] ?? 0);
                                    break 2;
                                }
                            }
                        }
                    }
                    
                    $sub = $qty * $basePrice;
                    $odItmRef = $database->getReference('ORDER_ITEM')->push();
                    $odItmRef->set([
                        'OdItm_Id' => $odItmRef->getKey(),
                        'Order_Id' => $order_id,
                        'PVar_Id' => $pvar_id,
                        'OdItm_Quantity' => $qty,
                        'OdItm_UnitPrice' => $basePrice,
                        'OdItm_Subtotal' => $sub
                    ]);
                    
                    // G. Clear Cart Item
                    $database->getReference('cart_item')->getChild($ci_key)->remove();
                }
            }

            header("Location: profile.php?tab=orders&order=success");
            exit();
        } catch (Exception $e) {
            $error = "Something went wrong: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <title>ZALORA — Checkout</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="../assets/css/global.css"/>
    <style>
        body { background: #f9f9f9; font-family: 'Montserrat', sans-serif; }
        .checkout-container { max-width: 900px; margin: 50px auto; display: grid; grid-template-columns: 1.5fr 1fr; gap: 30px; padding: 0 20px; }
        .card { background: #fff; padding: 30px; border: 1px solid #eee; }
        .section-title { font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 25px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .wallet-box { background: #000; color: #fff; padding: 20px; margin-bottom: 20px; }
        .wallet-label { font-size: 10px; text-transform: uppercase; opacity: 0.7; }
        .wallet-balance { font-size: 24px; font-weight: 700; margin-top: 5px; }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 13px; }
        .total-row { display: flex; justify-content: space-between; margin-top: 20px; padding-top: 20px; border-top: 2px solid #eee; font-weight: 700; font-size: 18px; }
        .btn-order { width: 100%; background: #000; color: #fff; border: none; padding: 18px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; cursor: pointer; margin-top: 20px; }
        .btn-order:hover { background: #333; }
        .error-msg { background: #fff0f0; color: #d00; padding: 15px; font-size: 12px; margin-bottom: 20px; border-left: 4px solid #d00; }
        .nav-logo { font-size: 24px; font-weight: 700; text-align: center; display: block; margin: 30px 0; text-decoration: none; color: #000; letter-spacing: 0.2em; }
        
        .promo-input-group { display: flex; gap: 10px; margin-top: 15px; padding-top: 15px; border-top: 1px dashed #eee; }
        .promo-input { flex: 1; padding: 10px; border: 1px solid #ddd; font-family: inherit; font-size: 12px; }
        .btn-apply { background: #eee; border: none; padding: 0 15px; font-weight: 700; font-size: 10px; text-transform: uppercase; cursor: pointer; }
        .promo-msg { font-size: 10px; margin-top: 5px; display: none; }
    </style>
</head>
<body>

<a href="../index.php" class="nav-logo">ZALORA</a>

<div class="checkout-container">
    <div class="left-col">
        <?php if ($error): ?>
            <div class="error-msg"><?= $error ?></div>
        <?php endif; ?>

        <div class="card">
            <h2 class="section-title">Shipping Address</h2>
            <div style="font-size: 13px; color: #666; line-height: 1.6;">
                <?php if (isset($cust['Addrs_id'])): ?>
                    <p><strong><?= htmlspecialchars($cust['Addrs_RcpntName']) ?></strong></p>
                    <p><?= $cust['Addrs_UnitNo'] ? htmlspecialchars($cust['Addrs_UnitNo']) . ', ' : '' ?><?= htmlspecialchars($cust['Addrs_Street']) ?></p>
                    <p>Brgy. <?= htmlspecialchars($cust['Addrs_Barangay']) ?>, <?= htmlspecialchars($cust['Addrs_City']) ?></p>
                    <p><?= htmlspecialchars($cust['Addrs_Province']) ?> <?= htmlspecialchars($cust['Addrs_ZipCode']) ?></p>
                    <p>Contact: <?= htmlspecialchars($cust['Addrs_Number']) ?></p>
                <?php else: ?>
                    <p style="color: #d00; font-weight: 600;">No shipping address found.</p>
                    <p><a href="profile.php#shipping-address" style="color: #000; font-weight: 700;">Click here to set up your address</a></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="card" style="margin-top: 20px;">
            <h2 class="section-title">Payment Method</h2>
            <div class="wallet-box">
                <p class="wallet-label">Pay with Zalora Wallet</p>
                <p class="wallet-balance">$<?= number_format($cust['Cust_Balance'], 2) ?></p>
            </div>
            <p style="font-size: 11px; color: #888;">Your balance will be deducted upon order confirmation.</p>
        </div>
    </div>

    <div class="right-col">
        <div class="card">
            <h2 class="section-title">Order Summary</h2>
            <div class="summary-row">
                <span>Subtotal</span>
                <span>$<?= number_format($subtotal, 2) ?></span>
            </div>
            <div class="summary-row">
                <span>Estimated Tax (8%)</span>
                <span>$<?= number_format($tax, 2) ?></span>
            </div>
            <div class="summary-row">
                <span>Shipping</span>
                <span style="color: <?= $shipping_fee == 0 ? 'green' : '#000' ?>; font-weight: 700;"><?= $shipping_fee == 0 ? 'FREE' : '$' . number_format($shipping_fee, 2) ?></span>
            </div>
            <div class="summary-row" id="discount-row" style="display: none; color: #d00; font-weight: 600;">
                <span>Promo Discount</span>
                <span>-$<span id="discount-amt">0.00</span></span>
            </div>
            
            <div class="promo-input-group">
                <input type="text" id="promo-code" class="promo-input" placeholder="Enter promo code">
                <button type="button" class="btn-apply" onclick="applyPromo()">Apply</button>
            </div>
            <div id="promo-status" class="promo-msg"></div>

            <div class="total-row">
                <span>Total</span>
                <span>$<span id="display-total"><?= number_format($total, 2) ?></span></span>
            </div>

            <form method="POST" id="checkout-form">
                <input type="hidden" name="coupon_code" id="hidden-promo-code">
                <button type="submit" name="place_order" class="btn-order">Confirm & Place Order</button>
            </form>
            
            <a href="cart.php" style="display:block; text-align:center; font-size:11px; color:#999; margin-top:15px; text-decoration:none;">Back to Bag</a>
        </div>
    </div>
</div>

<script>
const originalTotal = <?= $total ?>;
const subtotal = <?= $subtotal ?>;
const shippingFee = <?= $shipping_fee ?>;

function applyPromo() {
    const code = document.getElementById('promo-code').value.trim();
    const status = document.getElementById('promo-status');
    
    if (!code) return;

    fetch('ajax_validate_coupon.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `code=${encodeURIComponent(code)}&subtotal=${subtotal}`
    })
    .then(res => res.json())
    .then(data => {
        status.style.display = 'block';
        if (data.success) {
            status.style.color = 'green';
            status.innerText = data.message;
            
            // Update UI
            document.getElementById('discount-row').style.display = 'flex';
            document.getElementById('discount-amt').innerText = data.discount.toFixed(2);
            document.getElementById('display-total').innerText = (originalTotal - data.discount).toFixed(2);
            document.getElementById('hidden-promo-code').value = code;
        } else {
            status.style.color = '#d00';
            status.innerText = data.message;
            
            // Reset UI
            document.getElementById('discount-row').style.display = 'none';
            document.getElementById('display-total').innerText = originalTotal.toFixed(2);
            document.getElementById('hidden-promo-code').value = '';
        }
    });
}
</script>

</body>
</html>
