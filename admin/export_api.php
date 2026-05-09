<?php
session_start();
require_once '../config/db.php';

// Auth check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Unauthorized");
}

$type = $_GET['type'] ?? 'sales';
$filename = "zalora_" . $type . "_report_" . date('Ymd') . ".csv";

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

if ($type === 'sales') {
    fputcsv($output, ['Order ID', 'Customer', 'Date', 'Amount', 'Status', 'Payment Method']);
    $res = $conn->query("SELECT o.Order_Id, c.Cust_FirstName, c.Cust_LastName, o.Order_Date, o.Order_TotalAmnt, o.Order_Status, o.Order_PayMethod 
                         FROM ORDERS o 
                         JOIN customer c ON o.Cust_Id = c.Cust_Id 
                         ORDER BY o.Order_Date DESC");
    while($row = $res->fetch_assoc()) {
        fputcsv($output, [$row['Order_Id'], $row['Cust_FirstName'].' '.$row['Cust_LastName'], $row['Order_Date'], $row['Order_TotalAmnt'], $row['Order_Status'], $row['Order_PayMethod']]);
    }
} elseif ($type === 'inventory') {
    fputcsv($output, ['Prod ID', 'Product Name', 'Brand', 'Seller', 'Stock Total', 'Base Price']);
    $res = $conn->query("SELECT p.Prod_Id, p.Prod_Name, b.Brand_Name, s.Sell_BusinessName, 
                         (SELECT SUM(PVar_StockQuantity) FROM PRODUCT_VARIANT WHERE Prod_Id = p.Prod_Id) as total_stock,
                         p.Prod_BasePrice
                         FROM PRODUCT p
                         JOIN BRAND b ON p.Brand_Id = b.Brand_Id
                         JOIN seller s ON p.Sell_Id = s.Sell_Id");
    while($row = $res->fetch_assoc()) {
        fputcsv($output, [$row['Prod_Id'], $row['Prod_Name'], $row['Brand_Name'], $row['Sell_BusinessName'], $row['total_stock'], $row['Prod_BasePrice']]);
    }
} elseif ($type === 'drivers') {
    fputcsv($output, ['Driver ID', 'Name', 'Balance', 'Total Deliveries']);
    $res = $conn->query("SELECT d.Driv_Id, d.Driv_FirstName, d.Driv_LastName, d.Driv_Balance,
                         (SELECT COUNT(*) FROM shipment WHERE Driv_Id = d.Driv_Id AND Ship_Status = 'DELIVERED') as completed
                         FROM driver d");
    while($row = $res->fetch_assoc()) {
        fputcsv($output, [$row['Driv_Id'], $row['Driv_FirstName'].' '.$row['Driv_LastName'], $row['Driv_Balance'], $row['completed']]);
    }
}

fclose($output);
exit;
