<?php
// nav_counts.php - Helper to fetch cart and wishlist counts for the navbar
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../config/db.php';
    $uid = $_SESSION['user_id'];
    
    // 1. Cart Count
    $carts = $database->getReference('cart')->orderByChild('Cust_Id')->equalTo($uid)->getSnapshot()->getValue();
    $nav_cart_count = 0;
    if ($carts) {
        $cart_id = reset($carts)['Cart_Id'] ?? key($carts);
        $items = $database->getReference('cart_item')->orderByChild('Cart_Id')->equalTo($cart_id)->getSnapshot()->getValue();
        if ($items) {
            foreach($items as $i) {
                $nav_cart_count += $i['CItm_Quantity'] ?? 1;
            }
        }
    }

    // 2. Wishlist Count
    $wishRef = $database->getReference('wishlist')->orderByChild('Cust_Id')->equalTo($uid)->getSnapshot()->getValue();
    $nav_wish_count = 0;
    if ($wishRef) {
        $wish_id = reset($wishRef)['Wish_Id'] ?? key($wishRef);
        $wishItemsData = $database->getReference('wishlist_item')->orderByChild('Wish_Id')->equalTo($wish_id)->getSnapshot()->getValue();
        $nav_wish_count = $wishItemsData ? count($wishItemsData) : 0;
    }

    // 3. User Name
    $full_name = $_SESSION['user_name'] ?? 'User';
    $name_parts = explode(' ', trim($full_name));
    $nav_user_name = $name_parts[0] ?: 'User';
} else {
    $nav_cart_count = 0;
    $nav_wish_count = 0;
    $nav_user_name = '';
}

// Global Nav Links
$nav_links = ["ALL PRODUCTS","WOMEN", "MEN", "KIDS", "LUXURY"];
?>
