<?php
include 'config/db.php';
$res = $conn->query("SHOW COLUMNS FROM CUSTOMER");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
