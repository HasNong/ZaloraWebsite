<?php
include 'config/db.php';
$res = $conn->query("SELECT Driv_Email FROM driver LIMIT 5");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
