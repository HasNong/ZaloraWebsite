<?php
session_start();
require_once '../config/db.php';

// Auth check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seller') {
    die("Unauthorized");
}

$seller_id = $_SESSION['user_id'];
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

$filename = "zalora_inventory_" . $filter . "_" . date('Ymd') . ".csv";

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Product ID', 'Name', 'Category', 'Base Price', 'Total Stock', 'Status']);

$where_clause = "WHERE p.Sell_Id = ? AND p.Prod_IsActive != 2";
$params = [$seller_id];
$types = "i";

if ($search) {
    $where_clause .= " AND (p.Prod_Name LIKE ? OR c.Ctgry_Name LIKE ?)";
    $sp = "%$search%";
    $params[] = $sp; $params[] = $sp;
    $types .= "ss";
}

if ($filter === 'in_stock') {
    $where_clause .= " AND (SELECT SUM(PVar_StockQuantity) FROM product_variant WHERE Prod_Id = p.Prod_Id) > 10";
} elseif ($filter === 'low_stock') {
    $where_clause .= " AND (SELECT SUM(PVar_StockQuantity) FROM product_variant WHERE Prod_Id = p.Prod_Id) BETWEEN 1 AND 10";
}

$query = "SELECT p.*, c.Ctgry_Name, 
          (SELECT SUM(PVar_StockQuantity) FROM product_variant WHERE Prod_Id = p.Prod_Id) as total_stock
          FROM product p
          LEFT JOIN category c ON p.Ctgry_Id = c.Ctgry_Id
          $where_clause
          ORDER BY p.Prod_CreatedAt DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

while($row = $res->fetch_assoc()) {
    $status = "ACTIVE";
    if ($row['total_stock'] <= 0) $status = "OUT OF STOCK";
    elseif ($row['total_stock'] < 10) $status = "LOW STOCK";
    if ($row['Prod_IsActive'] == 0) $status .= " (PENDING)";

    fputcsv($output, [
        $row['Prod_Id'],
        $row['Prod_Name'],
        $row['Ctgry_Name'],
        $row['Prod_BasePrice'],
        $row['total_stock'] ?? 0,
        $status
    ]);
}

fclose($output);
exit;
