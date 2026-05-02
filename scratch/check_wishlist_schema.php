<?php
require_once 'config/db.php';
$res = $conn->query("DESCRIBE wishlist");
while($row = $res->fetch_assoc()) print_r($row);
$res = $conn->query("DESCRIBE wishlist_item");
while($row = $res->fetch_assoc()) print_r($row);
?>
