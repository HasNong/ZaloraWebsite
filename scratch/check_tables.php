<?php
include 'config/db.php';

echo "ORDERS TABLE:\n";
$res = $conn->query("DESCRIBE orders");
while($row = $res->fetch_assoc()) {
    echo "Field: " . $row['Field'] . "\n";
}

echo "\nSHIPMENT TABLE:\n";
$res2 = $conn->query("DESCRIBE shipment");
while($row = $res2->fetch_assoc()) {
    echo "Field: " . $row['Field'] . "\n";
}
?>
