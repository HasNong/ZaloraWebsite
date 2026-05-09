<?php
include 'config/db.php';

echo "PRODUCT TABLE:\n";
$res = $conn->query("DESCRIBE PRODUCT");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
