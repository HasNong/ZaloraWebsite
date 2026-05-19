<?php
include 'config/db.php';
$pw_hash = password_hash("@Qwerty1234", PASSWORD_DEFAULT);
$stmt = $conn->prepare("UPDATE driver SET Driv_PsswdHash = ?, Driv_IsActive = 1 WHERE Driv_Email = 'driver@zalora.com'");
$stmt->bind_param("s", $pw_hash);
if ($stmt->execute()) {
    echo "Driver password updated successfully to @Qwerty1234\n";
} else {
    echo "Failed to update driver password\n";
}
?>
