<?php
require_once 'config/db.php';
$sizes = $conn->query("SELECT DISTINCT PVar_Size FROM product_variant ORDER BY PVar_Size");
$colors = $conn->query("SELECT DISTINCT PVar_Color FROM product_variant ORDER BY PVar_Color");

$data = ['sizes' => [], 'colors' => []];
while($row = $sizes->fetch_assoc()) $data['sizes'][] = $row['PVar_Size'];
while($row = $colors->fetch_assoc()) $data['colors'][] = $row['PVar_Color'];

echo json_encode($data, JSON_PRETTY_PRINT);
?>
