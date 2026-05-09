<?php
include 'config/db.php';

echo "Creating RETURN_REQUEST table...\n";

$sql = "CREATE TABLE IF NOT EXISTS return_request (
    Return_Id INT PRIMARY KEY AUTO_INCREMENT,
    Order_Id INT NOT NULL,
    Cust_Id INT NOT NULL,
    Return_Reason TEXT NOT NULL,
    Return_Status ENUM('PENDING', 'APPROVED', 'REJECTED', 'PICKED_UP', 'COMPLETED') DEFAULT 'PENDING',
    Return_EvidenceUrl VARCHAR(255),
    Return_CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (Order_Id) REFERENCES orders(Order_Id),
    FOREIGN KEY (Cust_Id) REFERENCES customer(Cust_Id)
)";

if ($conn->query($sql)) {
    echo "Table created successfully!\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}
?>
