<?php
require_once 'config/db.php';

function describe($table) {
    global $conn;
    echo "--- $table ---\n";
    $res = $conn->query("DESCRIBE $table");
    if (!$res) { echo "Table not found\n\n"; return; }
    while($row = $res->fetch_assoc()) {
        echo "{$row['Field']} ({$row['Type']}) - Extra: {$row['Extra']}\n";
    }
    echo "\n";
}

describe('CART');
describe('CART_ITEM');
?>
