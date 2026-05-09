<?php
include 'config/db.php';

$email = 'driver@zalora.com';
$pass = password_hash('driver123', PASSWORD_DEFAULT);
$fname = 'CARL';
$lname = 'MALALAY';
$phone = '+65 8888 9999';
$vehicle = 'Motorcycle (Honda CB400)';
$license = 'S1234567A';

$stmt = $conn->prepare("INSERT INTO driver (Driv_FirstName, Driv_LastName, Driv_Email, Driv_PsswdHash, Driv_Phone, Driv_VehicleType, Driv_LicenseNo, Driv_Status) VALUES (?, ?, ?, ?, ?, ?, ?, 'ONLINE')");
$stmt->bind_param("sssssss", $fname, $lname, $email, $pass, $phone, $vehicle, $license);

if ($stmt->execute()) {
    echo "Test Driver Created!\nEmail: $email\nPass: driver123";
} else {
    echo "Error: " . $conn->error;
}
?>
