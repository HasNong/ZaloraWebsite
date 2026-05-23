<?php
require 'config/db.php';
$db = $database->getReference();
$products = array_merge(
    $db->getChild("PRODUCT")->getSnapshot()->getValue() ?: [],
    $db->getChild("product")->getSnapshot()->getValue() ?: []
);
foreach ($products as $p) {
    if ($p['Prod_IsActive'] == 1) {
        echo $p['Prod_Id'] . " | " . $p['Prod_Name'] . " | CatId: " . $p['Ctgry_Id'] . " | Status: " . $p['Prod_IsActive'] . "\n";
    }
}
