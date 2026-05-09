<?php
include 'config/db.php';

$tables = ['shipment', 'review', 'return_request'];

foreach ($tables as $t) {
    echo "\nTABLE: $t\n";
    $res = $conn->query("DESCRIBE $t");
    while($row = $res->fetch_assoc()) {
        print_r($row);
    }
}
?>
