<?php
require_once 'config/db.php';
$res = $conn->query("SHOW CREATE TABLE PRODUCT");
echo $res->fetch_array()[1];
?>
