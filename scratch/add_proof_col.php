<?php
include 'config/db.php';

echo "Adding Ship_ProofImg column to shipment table...\n";

if ($conn->query("ALTER TABLE shipment ADD COLUMN Ship_ProofImg VARCHAR(255) DEFAULT NULL")) {
    echo "Column added successfully!\n";
} else {
    echo "Error (it might already exist): " . $conn->error . "\n";
}
?>
