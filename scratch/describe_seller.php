<?php
require_once 'config/db.php';
$res = $conn->query("DESCRIBE seller");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
