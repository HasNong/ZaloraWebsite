<?php
include 'config/db.php';

echo "Checking return_request table...\n";
$res = $conn->query("DESCRIBE return_request");
if ($res) {
    while($row = $res->fetch_assoc()) {
        echo "Field: " . $row['Field'] . " | Type: " . $row['Type'] . "\n";
    }
} else {
    echo "Table 'return_request' does not exist!\n";
    
    // Check if it's capitalized
    $res2 = $conn->query("DESCRIBE RETURN_REQUEST");
    if ($res2) {
        echo "Table exists as 'RETURN_REQUEST'!\n";
    }
}
?>
