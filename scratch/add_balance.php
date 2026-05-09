<?php
require_once 'config/db.php';
$sql = "ALTER TABLE CUSTOMER ADD COLUMN Cust_Balance DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER Cust_Number";
if ($conn->query($sql)) {
    echo "Column Cust_Balance added successfully!";
} else {
    echo "Error: " . $conn->error;
}
?>
