<?php
// nav_counts.php - Helper to fetch cart and wishlist counts for the navbar
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../config/db.php';
    $uid = $_SESSION['user_id'];

    // 1. Cart Count
    $c_q = "SELECT SUM(ci.CItm_Quantity) as total FROM CART c 
            JOIN CART_ITEM ci ON c.Cart_Id = ci.Cart_Id 
            WHERE c.Cust_Id = ?";
    $c_stmt = $conn->prepare($c_q);
    $c_stmt->bind_param("i", $uid);
    $c_stmt->execute();
    $c_res = $c_stmt->get_result()->fetch_assoc();
    $nav_cart_count = $c_res['total'] ?? 0;

    // 2. Wishlist Count
    $w_q = "SELECT COUNT(wi.WItm_Id) as total FROM WISHLIST w 
            JOIN WISHLIST_ITEM wi ON w.Wish_Id = wi.Wish_Id 
            WHERE w.Cust_Id = ?";
    $w_stmt = $conn->prepare($w_q);
    $w_stmt->bind_param("i", $uid);
    $w_stmt->execute();
    $w_res = $w_stmt->get_result()->fetch_assoc();
    $nav_wish_count = $w_res['total'] ?? 0;

    // 3. User Name
    $u_q = "SELECT Cust_Firstname FROM CUSTOMER WHERE Cust_Id = ?";
    $u_stmt = $conn->prepare($u_q);
    $u_stmt->bind_param("i", $uid);
    $u_stmt->execute();
    $u_res = $u_stmt->get_result()->fetch_assoc();
    $nav_user_name = $u_res['Cust_Firstname'] ?? 'User';
} else {
    $nav_cart_count = 0;
    $nav_wish_count = 0;
    $nav_user_name = '';
}

// Global Nav Links
$nav_links = ["WOMEN", "MEN", "KIDS", "LUXURY", "BEAUTY"];
?>
