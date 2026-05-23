<?php
require 'config/db.php';
$prod_id = '-OtIBFv98vV8hY6RpMfP';
$status = 1;

$db = $database->getReference();
$prodRef = $db->getChild("product")->orderByChild("Prod_Id")->equalTo($prod_id)->getSnapshot();
if ($prodRef->hasChildren()) {
    $key = array_key_first($prodRef->getValue());
    echo "Found in product: $key\n";
    $db->getChild("product/$key")->update(['Prod_IsActive' => $status]);
} else {
    $prodRef = $db->getChild("PRODUCT")->orderByChild("Prod_Id")->equalTo($prod_id)->getSnapshot();
    if ($prodRef->hasChildren()) {
        $key = array_key_first($prodRef->getValue());
        echo "Found in PRODUCT: $key\n";
        $db->getChild("PRODUCT/$key")->update(['Prod_IsActive' => $status]);
    } else {
        echo "Not found.\n";
    }
}
