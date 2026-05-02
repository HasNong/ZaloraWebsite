<?php
require_once 'config/db.php';
$res = $conn->query("SELECT Prod_Id, Prod_Name, Ctgry_Id FROM PRODUCT");
while($row = $res->fetch_assoc()) {
    echo "ID: {$row['Prod_Id']} | Name: {$row['Prod_Name']} | Current Category ID: {$row['Ctgry_Id']}\n";
}
?>
