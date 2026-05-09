<?php
session_start();
require_once '../config/db.php';

// Auth check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'seller') {
    header("Location: ../auth/login.php");
    exit;
}

$seller_id = $_SESSION['user_id'];
$prod_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($prod_id > 0) {
    // 1. Verify ownership
    $check = $conn->prepare("SELECT Prod_Id FROM PRODUCT WHERE Prod_Id = ? AND Sell_Id = ?");
    $check->bind_param("ii", $prod_id, $seller_id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        
        // 2. Optional: Delete image files from disk
        $img_res = $conn->query("SELECT PImg_ImgUrl FROM PRODUCT_IMAGE WHERE Prod_Id = $prod_id");
        while ($img = $img_res->fetch_assoc()) {
            $full_path = '../' . $img['PImg_ImgUrl'];
            if (file_exists($full_path) && is_file($full_path)) {
                unlink($full_path);
            }
        }

        // 3. Delete from DB (Dependencies first or Cascade)
        // Assuming no cascade for safety, let's delete variants and images first
        $conn->query("DELETE FROM PRODUCT_VARIANT WHERE Prod_Id = $prod_id");
        $conn->query("DELETE FROM PRODUCT_IMAGE WHERE Prod_Id = $prod_id");
        $conn->query("DELETE FROM PRODUCT WHERE Prod_Id = $prod_id");

        $_SESSION['success_msg'] = "Product has been successfully removed.";
    }
}

header("Location: inventory.php");
exit;
?>
