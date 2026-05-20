<?php
include 'config/db.php';
$res = $conn->query("SELECT * FROM ORDERS LIMIT 5");
echo "--- ORDERS ---\n";
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
$res2 = $conn->query("SELECT * FROM shipment LIMIT 5");
echo "--- SHIPMENTS ---\n";
while ($row = $res2->fetch_assoc()) {
    print_r($row);
}
?>
