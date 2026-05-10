<?php
include 'config/db.php';

$tables = ['orders', 'product', 'loyalty_points', 'notification', 'payment', 'support_ticket', 'brand', 'category'];

foreach ($tables as $t) {
    echo "\nTABLE: $t\n";
    $res = $conn->query("DESCRIBE $t");
    if ($res) {
        while($row = $res->fetch_assoc()) {
            print_r($row);
        }
    } else {
        echo "Table $t not found or error: " . $conn->error . "\n";
    }
}
?>
