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
    $citm_id = isset($_POST['citm_id']) ? $_POST['citm_id'] : '';

    if (empty($citm_id)) {
        echo json_encode(["status" => "error", "message" => "Invalid item ID"]);
        exit();
    }

    $cartItemRef = $database->getReference('cart_item')->getChild($citm_id);

    if ($action === 'update_qty') {
        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
        if ($quantity < 1) $quantity = 1;

        try {
            $cartItemRef->update(['CItm_Quantity' => $quantity]);
            echo json_encode(["status" => "success"]);
        } catch (\Exception $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
    } 
    elseif ($action === 'remove') {
        try {
            $cartItemRef->remove();
            echo json_encode(["status" => "success"]);
        } catch (\Exception $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
    }
}
?>
