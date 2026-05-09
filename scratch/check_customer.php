<?php
include 'config/db.php';
echo "--- CUSTOMER SCHEMA ---\n";
$res = $conn->query("DESCRIBE customer");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
?>
