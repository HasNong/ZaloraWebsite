<?php
include 'config/db.php';
echo "--- COUPON TABLE COLUMNS ---\n";
$res = $conn->query("DESCRIBE coupon");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
?>
