<?php
session_start();
require_once '../config/db.php';

// Auth check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'seller') {
    header("Location: ../auth/login.php");
    exit;
}

$seller_id = $_SESSION['user_id'];
$prod_id = $_GET['id'] ?? '';

if (!empty($prod_id)) {
    // 1. Verify ownership
    $productRef = $database->getReference("product/$prod_id")->getSnapshot()->getValue();
    
    if ($productRef && ($productRef['Sell_Id'] ?? '') == $seller_id) {
        
        // 2. Optional: Delete image files from disk
        $imagesRef = $database->getReference('product_image')->orderByChild('Prod_Id')->equalTo($prod_id)->getSnapshot()->getValue() ?: [];
        foreach ($imagesRef as $imgId => $img) {
            if (!empty($img['PImg_ImgUrl'])) {
                $full_path = '../' . $img['PImg_ImgUrl'];
                if (file_exists($full_path) && is_file($full_path)) {
                    unlink($full_path);
                }
            }
        }

        // 3. Perform soft delete to avoid breaking order history constraints
        $database->getReference("product/$prod_id")->update(['Prod_IsActive' => 2]);

        $_SESSION['success_msg'] = "Product has been successfully removed.";
    }
}

header("Location: inventory.php");
exit;
?>
