<?php
require_once 'config/db.php';
$res = $conn->query("SELECT w.Cust_Id, pv.Prod_Id, wi.PVar_Id 
                     FROM wishlist_item wi 
                     JOIN wishlist w ON wi.Wish_Id = w.Wish_Id 
                     JOIN product_variant pv ON wi.PVar_Id = pv.PVar_Id");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
