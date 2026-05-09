<?php
include 'config/db.php';

echo "RETURN_REQUEST TABLE:\n";
$res = $conn->query("DESCRIBE return_request");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
