<?php
require_once __DIR__ . '/config/db.php';
header('Content-Type: application/json');

try {
    $wishlists = $database->getReference('wishlist')->getValue();
    $wishlistItems = $database->getReference('wishlist_item')->getValue();
    $customers = $database->getReference('customer')->getValue();

    echo json_encode([
        'wishlist' => $wishlists,
        'wishlist_item' => $wishlistItems,
        'customers_keys' => is_array($customers) ? array_keys($customers) : []
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
