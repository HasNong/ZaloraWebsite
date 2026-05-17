<?php
include 'config/db.php';
$res = $conn->query("SHOW COLUMNS FROM SELLER");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
