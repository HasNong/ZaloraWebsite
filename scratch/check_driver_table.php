<?php
include 'config/db.php';
$res = $conn->query("DESCRIBE driver");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
