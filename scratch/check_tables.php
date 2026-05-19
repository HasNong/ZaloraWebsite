<?php
include 'config/db.php';
$res = $conn->query("SHOW TABLES");
while ($row = $res->fetch_row()) {
    print_r($row);
}
?>
