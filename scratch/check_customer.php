<?php
include 'config/db.php';
$res = $conn->query("SELECT * FROM customer WHERE Cust_Email = 'malalayhansong13@gmail.com'");
print_r($res->fetch_assoc());
?>
