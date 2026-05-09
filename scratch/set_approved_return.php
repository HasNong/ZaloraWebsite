<?php
include 'config/db.php';
if ($conn->query("UPDATE return_request SET Rtrn_Status = 'APPROVED' WHERE Rtrn_Id = 1")) {
    echo "Return #1 set to APPROVED";
} else {
    echo "Error: " . $conn->error;
}
?>
