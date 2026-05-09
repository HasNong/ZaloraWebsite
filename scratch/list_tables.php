<?php
include 'config/db.php';
echo "--- DATABASE TABLES ---\n";
$res = $conn->query("SHOW TABLES");
while($row = $res->fetch_array()) {
    echo $row[0] . "\n";
}
?>
