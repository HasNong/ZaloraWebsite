<?php
/**
 * Normalize Firebase node paths to lowercase canonical names in production PHP files.
 */
$dirs = [
    __DIR__ . '/../admin',
    __DIR__ . '/../customer',
    __DIR__ . '/../seller',
    __DIR__ . '/../driver',
    __DIR__ . '/../auth',
];
$files = [__DIR__ . '/../index.php'];

foreach ($dirs as $dir) {
    foreach (glob($dir . '/*.php') as $f) {
        $files[] = $f;
    }
}

$nodeMap = [
    'CUSTOMER' => 'customer',
    'CART' => 'cart',
    'CART_ITEM' => 'cart_item',
    'ORDERS' => 'orders',
    'ORDER_ITEM' => 'order_item',
    'ORDER_COUPON' => 'order_coupon',
    'ADDRESS' => 'address',
    'WISHLIST' => 'wishlist',
    'WISHLIST_ITEM' => 'wishlist_item',
    'DRIVER' => 'driver',
    'SELLER' => 'seller',
    'PRODUCT' => 'product',
    'BRAND' => 'brand',
    'PRODUCT_IMAGE' => 'product_image',
    'PRODUCT_VARIANT' => 'product_variant',
    'ROLE_APPLICATION' => 'role_application',
    'CATEGORY' => 'category',
];

$changed = 0;
foreach ($files as $file) {
    $content = file_get_contents($file);
    $original = $content;

    foreach ($nodeMap as $upper => $lower) {
        $content = str_replace("getReference('$upper')", "getReference('$lower')", $content);
        $content = str_replace('getReference("' . $upper . '")', 'getReference("' . $lower . '")', $content);
        $content = str_replace("getChild(\"$upper\")", "getChild(\"$lower\")", $content);
        $content = str_replace("getChild('$upper')", "getChild('$lower')", $content);
    }

    // Remove uppercase fallback query lines
    $content = preg_replace(
        '/\s*if\s*\(\s*!\$\w+\s*\)\s*\$\w+\s*=\s*\$database->getReference\(\'[a-z_]+\'\)->[^;]+;\s*\n/',
        "\n",
        $content
    );

    // index.php getChild merges -> lowercase only
    $content = preg_replace(
        '/\$allProducts\s*=\s*array_merge\([^;]+getChild\("product"\)[^;]+;\s*/s',
        '$allProducts = $db->getChild("product")->getSnapshot()->getValue() ?: [];' . "\n",
        $content
    );
    $content = preg_replace(
        '/\$allBrands\s*=\s*array_merge\([^;]+getChild\("brand"\)[^;]+;\s*/s',
        '$allBrands = $db->getChild("brand")->getSnapshot()->getValue() ?: [];' . "\n",
        $content
    );
    $content = preg_replace(
        '/\$allImages\s*=\s*array_merge\([^;]+getChild\("product_image"\)[^;]+;\s*/s',
        '$allImages = $db->getChild("product_image")->getSnapshot()->getValue() ?: [];' . "\n",
        $content
    );

    // Node detection ternaries: 'ORDERS' : 'orders' -> 'orders'
    $content = preg_replace(
        "/getReference\('orders'\)->getChild\(\$[^)]+\)->getSnapshot\(\)->exists\(\)\s*\?\s*'orders'\s*:\s*'orders'/",
        "'orders'",
        $content
    );
    $content = str_replace("? 'ORDERS' : 'orders'", "? 'orders' : 'orders'", $content);
    $content = str_replace("? 'orders' : 'ORDERS'", "'orders'", $content);
    $content = str_replace("? 'CUSTOMER' : 'customer'", "'customer'", $content);
    $content = str_replace("? 'customer' : 'CUSTOMER'", "'customer'", $content);
    $content = str_replace("? 'DRIVER' : 'driver'", "'driver'", $content);
    $content = str_replace("? 'driver' : 'DRIVER'", "'driver'", $content);
    $content = str_replace("? 'ADDRESS' : 'address'", "'address'", $content);

    if ($content !== $original) {
        file_put_contents($file, $content);
        echo "Updated: " . basename(dirname($file)) . '/' . basename($file) . "\n";
        $changed++;
    }
}

echo "\nTotal files updated: $changed\n";
