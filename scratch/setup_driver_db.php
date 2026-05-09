<?php
include 'config/db.php';

// 1. Create Driver Table
$q1 = "CREATE TABLE IF NOT EXISTS driver (
    Driv_Id INT AUTO_INCREMENT PRIMARY KEY,
    Driv_FirstName VARCHAR(50),
    Driv_LastName VARCHAR(50),
    Driv_Email VARCHAR(100) UNIQUE,
    Driv_PsswdHash VARCHAR(255),
    Driv_Phone VARCHAR(20),
    Driv_VehicleType VARCHAR(50),
    Driv_LicenseNo VARCHAR(50),
    Driv_Balance DECIMAL(10,2) DEFAULT 0.00,
    Driv_Status ENUM('ONLINE', 'OFFLINE', 'BUSY') DEFAULT 'OFFLINE',
    Driv_IsActive TINYINT(1) DEFAULT 1,
    Driv_CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($q1)) echo "Driver table ready.\n";

// 2. Check if Driv_Id already exists in shipment
$check = $conn->query("SHOW COLUMNS FROM shipment LIKE 'Driv_Id'");
if ($check->num_rows == 0) {
    if ($conn->query("ALTER TABLE shipment ADD Driv_Id INT")) {
        echo "Shipment table updated with Driv_Id.\n";
        // Add foreign key
        $conn->query("ALTER TABLE shipment ADD CONSTRAINT fk_shipment_driver FOREIGN KEY (Driv_Id) REFERENCES driver(Driv_Id)");
    }
} else {
    echo "Shipment table already has Driv_Id.\n";
}
?>
