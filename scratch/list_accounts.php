<?php
require_once 'config/db.php';

echo "\n--- SELLERS ---\n";
$res = $conn->query("SELECT Sell_Id, Sell_FirstName, Sell_LastName, Sell_Email FROM SELLER");
while($row = $res->fetch_assoc()) echo "ID: " . $row['Sell_Id'] . " | Name: " . $row['Sell_FirstName'] . " " . $row['Sell_LastName'] . " | Email: " . $row['Sell_Email'] . "\n";

echo "\n--- CUSTOMERS ---\n";
$res = $conn->query("SELECT Cust_Id, Cust_FirstName, Cust_LastName, Cust_Email FROM CUSTOMER");
while($row = $res->fetch_assoc()) echo "ID: " . $row['Cust_Id'] . " | Name: " . $row['Cust_FirstName'] . " " . $row['Cust_LastName'] . " | Email: " . $row['Cust_Email'] . "\n";
?>
