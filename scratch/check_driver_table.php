<?php
include 'config/db.php';

echo "DRIVER TABLE:\n";
$res = $conn->query("DESCRIBE driver");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
