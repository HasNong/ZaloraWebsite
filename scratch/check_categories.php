<?php
require_once 'config/db.php';
$res = $conn->query("SELECT * FROM CATEGORY");
$categories = [];
while($row = $res->fetch_assoc()) {
    $categories[] = $row;
}
echo json_encode($categories, JSON_PRETTY_PRINT);
?>
