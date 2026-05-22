<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$cust_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Note: variants could have string IDs in Firebase depending on how they were generated
    $pvar_id = isset($_POST['pvar_id']) ? $_POST['pvar_id'] : '';
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

    if (empty($pvar_id)) {
        die("Invalid product variant.");
    }

    $cartsRef = $database->getReference('cart');
    $cartItemsRef = $database->getReference('cart_item');

    // 1. Get or Create Cart for the user
    $carts = $cartsRef->orderByChild('Cust_Id')->equalTo($cust_id)->getSnapshot()->getValue();
    
    if ($carts) {
        $cart_id = reset($carts)['Cart_Id'] ?? key($carts);
    } else {
        // Create a new cart
        $newCartRef = $cartsRef->push();
        $cart_id = $newCartRef->getKey();
        $newCartRef->set([
            'Cart_Id' => $cart_id,
            'Cust_Id' => $cust_id,
            'Cart_CreatedAt' => date('Y-m-d H:i:s'),
            'Cart_UpdatedAt' => date('Y-m-d H:i:s')
        ]);
    }

    // 2. Check if item already exists in CART_ITEM
    $cartItems = $cartItemsRef->orderByChild('Cart_Id')->equalTo($cart_id)->getSnapshot()->getValue();
    $existingItemKey = null;
    $existingItemQty = 0;
    
    if ($cartItems) {
        foreach ($cartItems as $key => $item) {
            if (($item['PVar_Id'] ?? '') == $pvar_id) {
                $existingItemKey = $key;
                $existingItemQty = $item['CItm_Quantity'] ?? 0;
                break;
            }
        }
    }

    if ($existingItemKey) {
        // Update quantity
        $new_qty = $existingItemQty + $quantity;
        $cartItemsRef->getChild($existingItemKey)->update([
            'CItm_Quantity' => $new_qty
        ]);
    } else {
        // Insert new item
        $newItemRef = $cartItemsRef->push();
        $newItemRef->set([
            'CItm_Id' => $newItemRef->getKey(),
            'Cart_Id' => $cart_id,
            'PVar_Id' => $pvar_id,
            'CItm_Quantity' => $quantity,
            'CItm_AddedAt' => date('Y-m-d H:i:s')
        ]);
    }

    // Redirect to cart page
    header("Location: cart.php");
    exit();
}
?>
