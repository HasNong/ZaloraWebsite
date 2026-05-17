<?php
include 'config/db.php';

// Try finding a valid customer ID
$res = $conn->query("SELECT Cust_Id FROM CUSTOMER LIMIT 1");
if ($res && $res->num_rows > 0) {
    $cust_id = $res->fetch_assoc()['Cust_Id'];
    echo "Found Customer ID: $cust_id\n";
    
    // Clear any previous test role applications
    $conn->query("DELETE FROM ROLE_APPLICATION WHERE Cust_Id = $cust_id");
    
    // Insert test Driver application
    $details = json_encode([
        'license_no' => 'N01-XX-XXXXX',
        'vehicle_type' => 'Motorcycle',
        'phone' => '09170000000'
    ]);
    
    $stmt = $conn->prepare("INSERT INTO ROLE_APPLICATION (Cust_Id, App_Type, App_Details, App_Status, Created_At) VALUES (?, 'Driver', ?, 'Pending', NOW())");
    $stmt->bind_param("is", $cust_id, $details);
    if ($stmt->execute()) {
        $app_id = $stmt->insert_id;
        echo "Test Driver application created with App_Id: $app_id\n";
        
        // Simulating approval process
        $app_stmt = $conn->prepare("SELECT * FROM ROLE_APPLICATION WHERE App_Id = ?");
        $app_stmt->bind_param("i", $app_id);
        $app_stmt->execute();
        $app = $app_stmt->get_result()->fetch_assoc();
        
        if ($app) {
            $details_data = json_decode($app['App_Details'], true);
            $cust_stmt = $conn->prepare("SELECT * FROM CUSTOMER WHERE Cust_Id = ?");
            $cust_stmt->bind_param("i", $cust_id);
            $cust_stmt->execute();
            $cust = $cust_stmt->get_result()->fetch_assoc();
            
            if ($cust) {
                $license_no = $details_data['license_no'];
                $vehicle_type = $details_data['vehicle_type'];
                $phone = $details_data['phone'];
                $email = $cust['Cust_Email'];
                $first_name = $cust['Cust_Firstname'];
                $last_name = $cust['Cust_Lastname'];
                $pass_hash = $cust['Cust_PsswdHash'];
                
                // Temporary deletion to prevent key conflicts if test run again
                $conn->query("DELETE FROM driver WHERE Driv_Email = '$email'");
                
                $ins_stmt = $conn->prepare("INSERT INTO driver (Driv_FirstName, Driv_LastName, Driv_Email, Driv_PsswdHash, Driv_Phone, Driv_VehicleType, Driv_LicenseNo, Driv_Status, Driv_IsActive, Driv_CreatedAt) VALUES (?, ?, ?, ?, ?, ?, ?, 'OFFLINE', 1, NOW())");
                $ins_stmt->bind_param("sssssss", $first_name, $last_name, $email, $pass_hash, $phone, $vehicle_type, $license_no);
                
                if ($ins_stmt->execute()) {
                    echo "Successfully inserted test Driver record into driver table.\n";
                    $conn->query("UPDATE ROLE_APPLICATION SET App_Status = 'Approved' WHERE App_Id = $app_id");
                    echo "Test Driver application approved.\n";
                } else {
                    echo "Error inserting into driver: " . $conn->error . "\n";
                }
            }
        }
    } else {
        echo "Error creating test application: " . $conn->error . "\n";
    }
} else {
    echo "No customer found to perform verify test.\n";
}
?>
