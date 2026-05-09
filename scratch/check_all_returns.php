<?php
include 'config/db.php';
$res = $conn->query("SELECT Rtrn_Id, Rtrn_Status FROM return_request");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
