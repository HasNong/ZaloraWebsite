<?php
require_once __DIR__ . '/../config/db.php';
$db = $database->getReference();
$products = array_merge(
    $db->getChild("PRODUCT")->getSnapshot()->getValue() ?: [],
    $db->getChild("product")->getSnapshot()->getValue() ?: []
);
foreach ($products as $k => $p) {
    echo "ID: " . ($p['Prod_Id'] ?? 'N/A') . " | Name: " . ($p['Prod_Name'] ?? 'N/A') . " | Status: " . ($p['Prod_IsActive'] ?? 'N/A') . "\n";
}
