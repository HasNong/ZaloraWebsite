<?php
require_once 'config/db.php';
$res = $conn->query("DESCRIBE PRODUCT");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . " (" . $row['Type'] . ") - Null: " . $row['Null'] . "\n";
}
echo "\n--- BRANDS ---\n";
$res = $conn->query("SELECT * FROM BRAND LIMIT 1");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
