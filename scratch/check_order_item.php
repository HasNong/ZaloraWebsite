<?php
include 'config/db.php';

echo "ORDER_ITEM TABLE:\n";
$res = $conn->query("DESCRIBE order_item");
while($row = $res->fetch_assoc()) {
    echo "Field: " . $row['Field'] . "\n";
}
?>
