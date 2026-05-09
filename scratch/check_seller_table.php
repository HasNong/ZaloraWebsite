<?php
include 'config/db.php';

echo "SELLER TABLE:\n";
$res = $conn->query("DESCRIBE seller");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
