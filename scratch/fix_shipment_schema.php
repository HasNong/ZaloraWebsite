<?php
include 'config/db.php';

echo "Adding AUTO_INCREMENT to Ship_Id...\n";

// Only modify the attribute
if ($conn->query("ALTER TABLE shipment MODIFY Ship_Id INT AUTO_INCREMENT")) {
    echo "Shipment table upgraded successfully!\n";
} else {
    echo "Error: " . $conn->error . "\n";
}
?>
