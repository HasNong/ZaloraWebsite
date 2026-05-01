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
    $pvar_id = isset($_POST['pvar_id']) ? intval($_POST['pvar_id']) : 0;
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

    if ($pvar_id <= 0) {
        die("Invalid product variant.");
    }

    // 1. Get or Create Cart for the user
    $cart_query = "SELECT Cart_Id FROM CART WHERE Cust_Id = ?";
    $stmt = $conn->prepare($cart_query);
    $stmt->bind_param("i", $cust_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $cart = $result->fetch_assoc();
        $cart_id = $cart['Cart_Id'];
    } else {
        // Generate manual Cart_Id
        $max_cart_query = "SELECT MAX(Cart_Id) as max_id FROM CART";
        $max_res = $conn->query($max_cart_query);
        $max_row = $max_res->fetch_assoc();
        $cart_id = ($max_row['max_id'] ?? 0) + 1;

        // Create a new cart
        $create_cart = "INSERT INTO CART (Cart_Id, Cust_Id, Cart_CreatedAt, Cart_UpdatedAt) VALUES (?, ?, NOW(), NOW())";
        $stmt_create = $conn->prepare($create_cart);
        $stmt_create->bind_param("ii", $cart_id, $cust_id);
        $stmt_create->execute();
    }

    // 2. Check if item already exists in CART_ITEM
    $item_query = "SELECT CItm_Id, CItm_Quantity FROM CART_ITEM WHERE Cart_Id = ? AND PVar_Id = ?";
    $stmt_item = $conn->prepare($item_query);
    $stmt_item->bind_param("ii", $cart_id, $pvar_id);
    $stmt_item->execute();
    $item_result = $stmt_item->get_result();

    if ($item_result->num_rows > 0) {
        // Update quantity
        $item = $item_result->fetch_assoc();
        $new_qty = $item['CItm_Quantity'] + $quantity;
        $update_item = "UPDATE CART_ITEM SET CItm_Quantity = ? WHERE CItm_Id = ?";
        $stmt_update = $conn->prepare($update_item);
        $stmt_update->bind_param("ii", $new_qty, $item['CItm_Id']);
        $stmt_update->execute();
    } else {
        // Generate manual CItm_Id
        $max_itm_query = "SELECT MAX(CItm_Id) as max_id FROM CART_ITEM";
        $max_itm_res = $conn->query($max_itm_query);
        $max_itm_row = $max_itm_res->fetch_assoc();
        $citm_id = ($max_itm_row['max_id'] ?? 0) + 1;

        // Insert new item
        $insert_item = "INSERT INTO CART_ITEM (CItm_Id, Cart_Id, PVar_Id, CItm_Quantity, CItm_AddedAt) VALUES (?, ?, ?, ?, NOW())";
        $stmt_insert = $conn->prepare($insert_item);
        $stmt_insert->bind_param("iiii", $citm_id, $cart_id, $pvar_id, $quantity);
        $stmt_insert->execute();
    }

    // Redirect to cart page
    header("Location: cart.php");
    exit();
}
?>
