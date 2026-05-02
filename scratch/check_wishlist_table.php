<?php
require_once 'config/db.php';
$res = $conn->query("SELECT * FROM wishlist");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
