<?php
require_once __DIR__ . '/../config/db.php';

$rules = [
    'rules' => [
        '.read' => true,
        '.write' => true,
        'customer' => ['.indexOn' => ['Cust_Id', 'Cust_Email']],
        'seller' => ['.indexOn' => ['Sell_Id', 'Sell_Email']],
        'driver' => ['.indexOn' => ['Driv_Id', 'Driv_Email', 'Driv_IsActive']],
        'cart' => ['.indexOn' => ['Cust_Id']],
        'cart_item' => ['.indexOn' => ['Cart_Id', 'PVar_Id']],
        'product' => ['.indexOn' => ['Prod_Id', 'Sell_Id', 'Brand_Id', 'Ctgry_Id']],
        'product_image' => ['.indexOn' => ['Prod_Id']],
        'product_variant' => ['.indexOn' => ['Prod_Id', 'PVar_Id']],
        'orders' => ['.indexOn' => ['Cust_Id', 'Order_Id']],
        'order_item' => ['.indexOn' => ['Order_Id', 'PVar_Id', 'OdItm_Id']],
        'order_coupon' => ['.indexOn' => ['Order_Id', 'Coup_Id']],
        'address' => ['.indexOn' => ['Cust_Id', 'Addrs_id']],
        'coupon' => ['.indexOn' => ['Coup_Code', 'Coup_Id', 'Seller_Id']],
        'support_ticket' => ['.indexOn' => ['Tcket_Id', 'Cust_Id', 'Tcket_Status']],
        'voucher' => ['.indexOn' => ['Vouch_Code', 'Cust_Id']],
        'shipment' => ['.indexOn' => ['Order_Id', 'Driv_Id', 'Ship_Status']],
        'return_request' => ['.indexOn' => ['OdItm_Id', 'Rtrn_Id', 'Rtrn_Status', 'Cust_Id']],
        'review' => ['.indexOn' => ['OdItm_Id', 'Prod_Id']],
        'wishlist' => ['.indexOn' => ['Cust_Id', 'Wish_Id']],
        'wishlist_item' => ['.indexOn' => ['Wish_Id', 'PVar_Id']],
        'loyalty_points' => ['.indexOn' => ['Cust_Id']],
        'notification' => ['.indexOn' => ['Cust_Id']],
        'brand' => ['.indexOn' => ['Brand_Id']],
        'category' => ['.indexOn' => ['Ctgry_Id']],
        'role_application' => ['.indexOn' => ['Cust_Id', 'App_Status']],
        'payment' => ['.indexOn' => ['Order_Id']],
    ],
];

try {
    $ruleSet = \Kreait\Firebase\Database\RuleSet::fromArray($rules);
    $database->updateRules($ruleSet);
    echo "Rules updated successfully!\n";
} catch (Exception $e) {
    echo "Error updating rules: " . $e->getMessage() . "\n";
}
