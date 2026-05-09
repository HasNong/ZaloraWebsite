<?php
include 'config/db.php';
echo "--- STATUS VALUES IN DB ---\n";
$res = $conn->query("SELECT DISTINCT Order_Status FROM ORDERS");
while($row = $res->fetch_assoc()) {
    echo "Order_Status: [" . $row['Order_Status'] . "]\n";
}
$res2 = $conn->query("SELECT DISTINCT Coup_IsActive FROM coupon");
while($row = $res2->fetch_assoc()) {
    echo "Coup_IsActive: [" . $row['Coup_IsActive'] . "]\n";
}
?>
