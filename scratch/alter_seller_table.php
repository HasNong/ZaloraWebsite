<?php
require_once 'config/db.php';
$sql = "ALTER TABLE seller ADD COLUMN Sell_PsswdHash VARCHAR(255) AFTER Sell_Email";
if ($conn->query($sql)) {
    echo "Column Sell_PsswdHash added successfully.";
} else {
    echo "Error: " . $conn->error;
}
?>
