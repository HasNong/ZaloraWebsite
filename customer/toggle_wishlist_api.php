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
    $pvar_id = isset($_POST['pvar_id']) ? $_POST['pvar_id'] : '';

    if (empty($pvar_id)) {
        echo json_encode(["status" => "error", "message" => "Invalid item"]);
        exit();
    }

    // 1. Get or Create Wishlist for the user
    $wishRef = $database->getReference('wishlist')->orderByChild('Cust_Id')->equalTo($cust_id)->getSnapshot()->getValue();
    $wish_id = null;
    
    if ($wishRef) {
        $wish_id = key($wishRef);
    } else {
        $newWishRef = $database->getReference('wishlist')->push();
        $wish_id = $newWishRef->getKey();
        $newWishRef->set([
            'Wish_Id' => $wish_id,
            'Cust_Id' => $cust_id,
            'Wish_CreatedAt' => date('Y-m-d H:i:s')
        ]);
    }

    // 2. Toggle Wishlist Item
    $itemsRef = $database->getReference('wishlist_item')->orderByChild('Wish_Id')->equalTo($wish_id)->getSnapshot()->getValue();
    $existing_item_key = null;
    
    if ($itemsRef) {
        foreach ($itemsRef as $key => $item) {
            if (($item['PVar_Id'] ?? '') == $pvar_id) {
                $existing_item_key = $key;
                break;
            }
        }
    }

    if ($existing_item_key) {
        // Remove from wishlist
        $database->getReference('wishlist_item')->getChild($existing_item_key)->remove();
        echo json_encode(["status" => "success", "action" => "removed"]);
    } else {
        // Add to wishlist
        $newItemRef = $database->getReference('wishlist_item')->push();
        $newItemRef->set([
            'WItm_Id' => $newItemRef->getKey(),
            'Wish_Id' => $wish_id,
            'PVar_Id' => $pvar_id,
            'WItm_AddedAt' => date('Y-m-d H:i:s')
        ]);
        echo json_encode(["status" => "success", "action" => "added"]);
    }
}
?>
