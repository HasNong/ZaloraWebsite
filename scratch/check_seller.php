<?php
include 'config/db.php';
echo "--- SELLER SCHEMA ---\n";
$res = $conn->query("DESCRIBE seller");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
?>
