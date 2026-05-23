<?php
require_once __DIR__ . '/../config/db.php';

$pairs = [
    ['ADDRESS', 'address'],
    ['CUSTOMER', 'customer'],
    ['SELLER', 'seller'],
    ['ORDERS', 'orders'],
    ['ORDER_ITEM', 'order_item'],
    ['ORDER_COUPON', 'order_coupon'],
    ['CART', 'cart'],
    ['CART_ITEM', 'cart_item'],
    ['PRODUCT', 'product'],
    ['PRODUCT_IMAGE', 'product_image'],
    ['PRODUCT_VARIANT', 'product_variant'],
    ['BRAND', 'brand'],
    ['CATEGORY', 'category'],
    ['WISHLIST', 'wishlist'],
    ['WISHLIST_ITEM', 'wishlist_item'],
    ['DRIVER', 'driver'],
];

$root = $database->getReference('/')->getSnapshot()->getValue() ?: [];
echo "Root keys: " . implode(', ', array_keys($root)) . "\n\n";

foreach ($pairs as [$upper, $lower]) {
    $u = isset($root[$upper]) ? count($root[$upper]) : 0;
    $l = isset($root[$lower]) ? count($root[$lower]) : 0;
    if ($u || $l) {
        echo str_pad("$upper / $lower", 28) . " upper=$u  lower=$l\n";
    }
}
