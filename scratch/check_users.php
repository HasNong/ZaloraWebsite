<?php
include 'config/db.php';
$res = $conn->query("SELECT Cust_Email FROM CUSTOMER LIMIT 5");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
