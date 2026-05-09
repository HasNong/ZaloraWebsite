<?php
include 'config/db.php';
echo "--- SHIPMENT SCHEMA ---\n";
$res = $conn->query("DESCRIBE shipment");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
?>
