<?php
include 'config/db.php';
$res = $conn->query("SELECT Sell_Email FROM seller LIMIT 5");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
