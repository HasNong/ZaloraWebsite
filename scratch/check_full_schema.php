<?php
require_once 'config/db.php';

function describe($table) {
    global $conn;
    echo "--- $table ---\n";
    $res = $conn->query("DESCRIBE $table");
    while($row = $res->fetch_assoc()) {
        echo "{$row['Field']} ({$row['Type']})\n";
    }
    echo "\n";
}

describe('CUSTOMER');
describe('ORDERS');
describe('ORDER_ITEM');
?>
