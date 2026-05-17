<?php
include 'config/db.php';

$q = "CREATE TABLE IF NOT EXISTS ROLE_APPLICATION (
    App_Id INT AUTO_INCREMENT PRIMARY KEY,
    Cust_Id INT NOT NULL,
    App_Type ENUM('Seller', 'Driver') NOT NULL,
    App_Status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    App_Details TEXT NOT NULL,
    Created_At DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (Cust_Id) REFERENCES CUSTOMER(Cust_Id) ON DELETE CASCADE
)";

if ($conn->query($q)) {
    echo "ROLE_APPLICATION table created successfully.\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}
?>
