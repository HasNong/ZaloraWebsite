<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $citm_id = isset($_POST['citm_id']) ? intval($_POST['citm_id']) : 0;

    if ($citm_id <= 0) {
        echo json_encode(["status" => "error", "message" => "Invalid item ID"]);
        exit();
    }

    if ($action === 'update_qty') {
        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
        if ($quantity < 1) $quantity = 1;

        $update_query = "UPDATE CART_ITEM SET CItm_Quantity = ? WHERE CItm_Id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("ii", $quantity, $citm_id);
        
        if ($stmt->execute()) {
            echo json_encode(["status" => "success"]);
        } else {
            echo json_encode(["status" => "error", "message" => $conn->error]);
        }
    } 
    elseif ($action === 'remove') {
        $delete_query = "DELETE FROM CART_ITEM WHERE CItm_Id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("i", $citm_id);
        
        if ($stmt->execute()) {
            echo json_encode(["status" => "success"]);
        } else {
            echo json_encode(["status" => "error", "message" => $conn->error]);
        }
    }
}
?>
