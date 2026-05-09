<?php
require_once 'config/db.php';
$res = $conn->query("SHOW TABLES LIKE 'ADDRESS'");
if ($res->num_rows > 0) {
    echo "ADDRESS_EXISTS";
    $conn->query("INSERT IGNORE INTO ADDRESS (Addrs_Id, Cust_Id, Addrs_Name, Addrs_Line1, Addrs_City, Addrs_Postcode, Addrs_IsDefault) VALUES (1, 1, 'Main', '123 Fashion St', 'Singapore', '123456', 1)");
} else {
    echo "ADDRESS_NOT_FOUND";
}
?>
