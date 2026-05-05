<?php
require_once 'config/db.php';

$res = $conn->query("SELECT Prod_Id FROM PRODUCT WHERE Prod_Id NOT IN (SELECT Prod_Id FROM PRODUCT_VARIANT)");
while($row = $res->fetch_assoc()) {
    $pid = $row['Prod_Id'];
    $max_res = $conn->query("SELECT MAX(PVar_Id) as max_id FROM PRODUCT_VARIANT");
    $pvar_id = ($max_res->fetch_assoc()['max_id'] ?? 0) + 1;
    
    $sku = "SKU-" . $pid . "-STD";
    $conn->query("INSERT INTO PRODUCT_VARIANT (PVar_Id, Prod_Id, PVar_Sku, PVar_Size, PVar_Color, PVar_StockQuantity) VALUES ($pvar_id, $pid, '$sku', 'Standard', 'Default', 100)");
    echo "Fixed Product ID: $pid\n";
}
?>
