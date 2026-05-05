<?php
require_once 'config/db.php';
$res = $conn->query("DESCRIBE PRODUCT_IMAGE");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
?>
