<?php
require_once 'config/db.php';
$res = $conn->query("SELECT Ctgry_Id, COUNT(*) as count FROM PRODUCT GROUP BY Ctgry_Id");
$dist = [];
while($row = $res->fetch_assoc()) {
    $dist[] = $row;
}
echo json_encode($dist, JSON_PRETTY_PRINT);
?>
