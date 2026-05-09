<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$cust_id = $_SESSION['user_id'];

// 1. Fetch Cart Totals
$cart_query = "SELECT SUM(p.Prod_BasePrice * ci.CItm_Quantity) as subtotal
               FROM CART c
               JOIN CART_ITEM ci ON c.Cart_Id = ci.Cart_Id
               JOIN PRODUCT_VARIANT pv ON ci.PVar_Id = pv.PVar_Id
               JOIN PRODUCT p ON pv.Prod_Id = p.Prod_Id
               WHERE c.Cust_Id = ?";
$stmt = $conn->prepare($cart_query);
$stmt->bind_param("i", $cust_id);
$stmt->execute();
$cart_res = $stmt->get_result()->fetch_assoc();

$subtotal = $cart_res['subtotal'] ?? 0;
if ($subtotal == 0) {
    header("Location: cart.php");
    exit();
}

$tax = round($subtotal * 0.08, 2);
$total = $subtotal + $tax;

// 2. Fetch Customer Balance & Address
$cust_query = "SELECT c.Cust_Balance, c.Cust_Firstname, c.Cust_Lastname, a.* 
               FROM CUSTOMER c 
               LEFT JOIN ADDRESS a ON c.Cust_Id = a.Cust_Id AND a.Addrs_IsDefault = 1
               WHERE c.Cust_Id = ?";
$stmt_c = $conn->prepare($cust_query);
$stmt_c->bind_param("i", $cust_id);
$stmt_c->execute();
$cust = $stmt_c->get_result()->fetch_assoc();

// 3. Handle Order Placement
$error = "";
if (isset($_POST['place_order'])) {
    if ($cust['Cust_Balance'] < $total) {
        $error = "Insufficient balance in your Zalora Wallet. Please top up!";
    } else {
        // START TRANSACTION
        $conn->begin_transaction();
        try {
            // A. Get or Create Address
            $addr_query = "SELECT Addrs_id FROM ADDRESS WHERE Cust_Id = ? AND Addrs_IsDefault = 1 LIMIT 1";
            $stmt_a = $conn->prepare($addr_query);
            $stmt_a->bind_param("i", $cust_id);
            $stmt_a->execute();
            $addr_res = $stmt_a->get_result()->fetch_assoc();
            
            if ($addr_res) {
                $addr_id = $addr_res['Addrs_id'];
            } else {
                // Get next ID manually
                $res_id = $conn->query("SELECT MAX(Addrs_id) as max_id FROM ADDRESS");
                $next_id = ($res_id->fetch_assoc()['max_id'] ?? 0) + 1;

                // Create default address
                $stmt_new_addr = $conn->prepare("INSERT INTO ADDRESS (Addrs_id, Cust_Id, Addrs_RcpntName, Addrs_Street, Addrs_City, Addrs_Province, Addrs_ZipCode, Addrs_IsDefault, Addrs_CreatedAt) VALUES (?, ?, ?, 'Not Set', 'Not Set', 'Not Set', '0000', 1, NOW())");
                $full_name = $cust['Cust_Firstname'] . ' ' . $cust['Cust_Lastname'];
                $stmt_new_addr->bind_param("iis", $next_id, $cust_id, $full_name);
                $stmt_new_addr->execute();
                $addr_id = $next_id;
            }

            // B. Calculate Discount
            $discount = 0;
            $applied_coupon_id = null;
            $applied_voucher_id = null;

            if (isset($_POST['coupon_code']) && !empty($_POST['coupon_code'])) {
                $code = mysqli_real_escape_string($conn, $_POST['coupon_code']);
                // Check Coupon
                $cp_res = $conn->query("SELECT * FROM coupon WHERE Coup_Code = '$code' AND Coup_IsActive = 1 AND Is_Approved = 1 AND NOW() BETWEEN Coup_ValidFrom AND Coup_ValidUntil LIMIT 1");
                if ($cp_res->num_rows > 0) {
                    $cp = $cp_res->fetch_assoc();
                    if ($subtotal >= $cp['Coup_MinOrderAmt'] && $cp['Coup_UsedCount'] < $cp['Coup_MaxUses']) {
                        $applied_coupon_id = $cp['Coup_Id'];
                        $discount = ($cp['Coup_DiscType'] == 'PERCENTAGE') ? ($subtotal * ($cp['Coup_DiscValue'] / 100)) : $cp['Coup_DiscValue'];
                    }
                }
                // Check Voucher if no coupon applied
                if (!$applied_coupon_id) {
                    $vc_res = $conn->query("SELECT * FROM voucher WHERE Vouch_Code = '$code' AND Cust_Id = $cust_id AND Vouch_IsUsed = 0 AND NOW() < Vouch_Expiry LIMIT 1");
                    if ($vc_res->num_rows > 0) {
                        $vc = $vc_res->fetch_assoc();
                        $applied_voucher_id = $vc['Vouch_Id'];
                        $discount = ($vc['Vouch_DiscType'] == 'PERCENTAGE') ? ($subtotal * ($vc['Vouch_DiscValue'] / 100)) : $vc['Vouch_DiscValue'];
                    }
                }
            }

            $final_total = max(0, $total - $discount);

            // C. Deduct Balance
            $conn->query("UPDATE CUSTOMER SET Cust_Balance = Cust_Balance - $final_total WHERE Cust_Id = $cust_id");

            // D. Create Order
            $res_oid = $conn->query("SELECT MAX(Order_Id) as max_id FROM ORDERS");
            $order_id = ($res_oid->fetch_assoc()['max_id'] ?? 0) + 1;

            $ord_stmt = $conn->prepare("INSERT INTO ORDERS (Order_Id, Cust_Id, Addrs_Id, Order_Status, Order_TotalAmnt, Order_ShipFee, Order_PlacedAt, Order_UpdatedAt) VALUES (?, ?, ?, 'PENDING', ?, 0, NOW(), NOW())");
            $ord_stmt->bind_param("iiid", $order_id, $cust_id, $addr_id, $final_total);
            $ord_stmt->execute();

            // E. Record Coupon Usage
            if ($applied_coupon_id) {
                $res_ocid = $conn->query("SELECT MAX(OCoup_Id) as max_id FROM ORDER_COUPON");
                $next_oc_id = ($res_ocid->fetch_assoc()['max_id'] ?? 0) + 1;
                $conn->query("INSERT INTO ORDER_COUPON (OCoup_Id, Order_Id, Coup_Id, OCoup_DiscApplied, OCoup_AppliedAt) VALUES ($next_oc_id, $order_id, $applied_coupon_id, $discount, NOW())");
                $conn->query("UPDATE coupon SET Coup_UsedCount = Coup_UsedCount + 1 WHERE Coup_Id = $applied_coupon_id");
            }
            if ($applied_voucher_id) {
                $conn->query("UPDATE voucher SET Vouch_IsUsed = 1, Vouch_UsedAt = NOW() WHERE Vouch_Id = $applied_voucher_id");
            }

            // F. Move Cart Items to Order Items
            $cart_items_query = "SELECT ci.PVar_Id, ci.CItm_Quantity, p.Prod_BasePrice 
                                 FROM CART_ITEM ci 
                                 JOIN CART c ON ci.Cart_Id = c.Cart_Id 
                                 JOIN PRODUCT_VARIANT pv ON ci.PVar_Id = pv.PVar_Id
                                 JOIN PRODUCT p ON pv.Prod_Id = p.Prod_Id
                                 WHERE c.Cust_Id = $cust_id";
            $cart_items = $conn->query($cart_items_query);
            
            $res_itemid = $conn->query("SELECT MAX(OdItm_Id) as max_id FROM ORDER_ITEM");
            $next_item_id = ($res_itemid->fetch_assoc()['max_id'] ?? 0) + 1;

            while($item = $cart_items->fetch_assoc()) {
                $sub = $item['CItm_Quantity'] * $item['Prod_BasePrice'];
                $ins_item = $conn->prepare("INSERT INTO ORDER_ITEM (OdItm_Id, Order_Id, PVar_Id, OdItm_Quantity, OdItm_UnitPrice, OdItm_Subtotal) VALUES (?, ?, ?, ?, ?, ?)");
                $ins_item->bind_param("iiiidd", $next_item_id, $order_id, $item['PVar_Id'], $item['CItm_Quantity'], $item['Prod_BasePrice'], $sub);
                $ins_item->execute();
                $next_item_id++;
            }

            // G. Clear Cart
            $conn->query("DELETE ci FROM CART_ITEM ci JOIN CART c ON ci.Cart_Id = c.Cart_Id WHERE c.Cust_Id = $cust_id");

            $conn->commit();
            header("Location: profile.php?order=success");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
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
                <span style="color: green; font-weight: 700;">FREE</span>
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
