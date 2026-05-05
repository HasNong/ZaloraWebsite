<?php
require_once 'config/db.php';
$res = $conn->query("SELECT p.Prod_Id, p.Prod_Name, p.Sell_Id FROM PRODUCT p ORDER BY p.Prod_Id DESC LIMIT 5");
echo "LAST 5 PRODUCTS IN TABLE:\n";
while($row = $res->fetch_assoc()) {
    echo "ID: " . $row['Prod_Id'] . " | Name: " . $row['Prod_Name'] . " | Seller: " . $row['Sell_Id'] . "\n";
}
?>
