<?php
require_once 'config/db.php';
$res = $conn->query("DESCRIBE PRODUCT");
$columns = [];
while($row = $res->fetch_assoc()) {
    $columns[] = $row;
}
echo json_encode($columns, JSON_PRETTY_PRINT);
?>
