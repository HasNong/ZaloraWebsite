<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit();
}

$cust_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pvar_id = isset($_POST['pvar_id']) ? intval($_POST['pvar_id']) : 0;

    if ($pvar_id <= 0) {
        echo json_encode(["status" => "error", "message" => "Invalid item"]);
        exit();
    }

    // 1. Get or Create Wishlist for the user
    $wish_query = "SELECT Wish_Id FROM WISHLIST WHERE Cust_Id = ?";
    $stmt = $conn->prepare($wish_query);
    $stmt->bind_param("i", $cust_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $wish = $result->fetch_assoc();
        $wish_id = $wish['Wish_Id'];
    } else {
        // Generate manual Wish_Id (bypassing missing auto_increment)
        $max_wish_query = "SELECT MAX(Wish_Id) as max_id FROM WISHLIST";
        $max_res = $conn->query($max_wish_query);
        $max_row = $max_res->fetch_assoc();
        $wish_id = ($max_row['max_id'] ?? 0) + 1;

        $create_wish = "INSERT INTO WISHLIST (Wish_Id, Cust_Id, Wish_CreatedAt) VALUES (?, ?, NOW())";
        $stmt_create = $conn->prepare($create_wish);
        $stmt_create->bind_param("ii", $wish_id, $cust_id);
        $stmt_create->execute();
    }

    // 2. Toggle Wishlist Item
    $check_item = "SELECT WItm_Id FROM WISHLIST_ITEM WHERE Wish_Id = ? AND PVar_Id = ?";
    $stmt_check = $conn->prepare($check_item);
    $stmt_check->bind_param("ii", $wish_id, $pvar_id);
    $stmt_check->execute();
    $item_result = $stmt_check->get_result();

    if ($item_result->num_rows > 0) {
        // Remove from wishlist
        $item = $item_result->fetch_assoc();
        $delete_item = "DELETE FROM WISHLIST_ITEM WHERE WItm_Id = ?";
        $stmt_del = $conn->prepare($delete_item);
        $stmt_del->bind_param("i", $item['WItm_Id']);
        $stmt_del->execute();
        echo json_encode(["status" => "success", "action" => "removed"]);
    } else {
        // Generate manual WItm_Id
        $max_itm_query = "SELECT MAX(WItm_Id) as max_id FROM WISHLIST_ITEM";
        $max_itm_res = $conn->query($max_itm_query);
        $max_itm_row = $max_itm_res->fetch_assoc();
        $witm_id = ($max_itm_row['max_id'] ?? 0) + 1;

        // Add to wishlist
        $insert_item = "INSERT INTO WISHLIST_ITEM (WItm_Id, Wish_Id, PVar_Id, WItm_AddedAt) VALUES (?, ?, ?, NOW())";
        $stmt_ins = $conn->prepare($insert_item);
        $stmt_ins->bind_param("iii", $witm_id, $wish_id, $pvar_id);
        $stmt_ins->execute();
        echo json_encode(["status" => "success", "action" => "added"]);
    }
}
?>
