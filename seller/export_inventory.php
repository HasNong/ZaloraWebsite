<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seller') {
    die("Unauthorized");
}

$seller_id = $_SESSION['user_id'];
$filter = $_GET['filter'] ?? 'all';
$search = strtolower($_GET['search'] ?? '');

$productsRef = $database->getReference('product')->orderByChild('Sell_Id')->equalTo($seller_id)->getSnapshot()->getValue() ?: [];
$variantsRef = $database->getReference('product_variant')->getSnapshot()->getValue() ?: [];
$categoriesRef = $database->getReference('category')->getSnapshot()->getValue() ?: [];

$filename = "zalora_inventory_" . $filter . "_" . date('Ymd') . ".csv";

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Product ID', 'Name', 'Category', 'Base Price', 'Total Stock', 'Status']);

foreach ($productsRef as $p) {
    if (!is_array($p) || ($p['Prod_IsActive'] ?? 1) == 2) {
        continue;
    }

    $cat_name = 'General';
    foreach ($categoriesRef as $c) {
        if ((string) ($c['Ctgry_Id'] ?? '') === (string) ($p['Ctgry_Id'] ?? '')) {
            $cat_name = $c['Ctgry_Name'] ?? 'General';
            break;
        }
    }

    if ($search) {
        $pname = strtolower($p['Prod_Name'] ?? '');
        $cname = strtolower($cat_name);
        if (strpos($pname, $search) === false && strpos($cname, $search) === false) {
            continue;
        }
    }

    $stock = 0;
    foreach ($variantsRef as $v) {
        if ((string) ($v['Prod_Id'] ?? '') === (string) ($p['Prod_Id'] ?? '')) {
            $stock += (int) ($v['PVar_StockQuantity'] ?? 0);
        }
    }

    if ($filter === 'in_stock' && $stock <= 10) {
        continue;
    }
    if ($filter === 'low_stock' && ($stock < 1 || $stock > 10)) {
        continue;
    }

    $status = "ACTIVE";
    if ($stock <= 0) {
        $status = "OUT OF STOCK";
    } elseif ($stock < 10) {
        $status = "LOW STOCK";
    }
    if (($p['Prod_IsActive'] ?? 1) == 0) {
        $status .= " (PENDING)";
    }

    fputcsv($output, [
        $p['Prod_Id'] ?? '',
        $p['Prod_Name'] ?? '',
        $cat_name,
        $p['Prod_BasePrice'] ?? 0,
        $stock,
        $status,
    ]);
}

fclose($output);
exit;
