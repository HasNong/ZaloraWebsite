<?php
session_start();
require_once '../config/db.php';

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

    $all_orders = fb_merge_nodes($database, 'orders');
    $all_customers = fb_merge_nodes($database, 'customer');
    $all_payments = $database->getReference('payment')->getSnapshot()->getValue() ?: [];

    $customers_by_id = [];
    foreach ($all_customers as $c) {
        if (isset($c['Cust_Id'])) {
            $customers_by_id[$c['Cust_Id']] = $c;
        }
    }

    $payments_by_order = [];
    foreach ($all_payments as $p) {
        if (is_array($p) && isset($p['Order_Id'])) {
            $payments_by_order[$p['Order_Id']] = $p;
        }
    }

    usort($all_orders, fn($a, $b) => strtotime($b['Order_PlacedAt'] ?? 0) - strtotime($a['Order_PlacedAt'] ?? 0));

    foreach ($all_orders as $o) {
        if (!is_array($o)) {
            continue;
        }
        $cust = $customers_by_id[$o['Cust_Id'] ?? ''] ?? [];
        $cust_name = trim(($cust['Cust_Firstname'] ?? '') . ' ' . ($cust['Cust_Lastname'] ?? ''));
        $payment = $payments_by_order[$o['Order_Id'] ?? ''] ?? [];

        fputcsv($output, [
            $o['Order_Id'] ?? '',
            $cust_name ?: 'Unknown',
            $o['Order_PlacedAt'] ?? '',
            $o['Order_TotalAmnt'] ?? 0,
            $o['Order_Status'] ?? '',
            $payment['Pymnt_Method'] ?? 'N/A',
        ]);
    }
} elseif ($type === 'inventory') {
    fputcsv($output, ['Prod ID', 'Product Name', 'Brand', 'Seller', 'Stock Total', 'Base Price']);

    $all_products = fb_merge_nodes($database, 'product');
    $all_brands = fb_merge_nodes($database, 'brand');
    $all_sellers = fb_merge_nodes($database, 'seller');
    $all_variants = fb_merge_nodes($database, 'product_variant');

    $brands_by_id = [];
    foreach ($all_brands as $b) {
        if (isset($b['Brand_Id'])) {
            $brands_by_id[$b['Brand_Id']] = $b['Brand_Name'] ?? '';
        }
    }
    $sellers_by_id = [];
    foreach ($all_sellers as $s) {
        if (isset($s['Sell_Id'])) {
            $sellers_by_id[$s['Sell_Id']] = $s['Sell_BusinessName'] ?? '';
        }
    }

    foreach ($all_products as $p) {
        if (!is_array($p)) {
            continue;
        }
        $prod_id = $p['Prod_Id'] ?? '';
        $stock = 0;
        foreach ($all_variants as $v) {
            if ((string) ($v['Prod_Id'] ?? '') === (string) $prod_id) {
                $stock += (int) ($v['PVar_StockQuantity'] ?? 0);
            }
        }

        fputcsv($output, [
            $prod_id,
            $p['Prod_Name'] ?? '',
            $brands_by_id[$p['Brand_Id'] ?? $p['Brand_id'] ?? ''] ?? 'Unknown',
            $sellers_by_id[$p['Sell_Id'] ?? ''] ?? 'Unknown',
            $stock,
            $p['Prod_BasePrice'] ?? 0,
        ]);
    }
} elseif ($type === 'drivers') {
    fputcsv($output, ['Driver ID', 'Name', 'Balance', 'Total Deliveries']);

    $all_drivers = fb_merge_nodes($database, 'driver');
    $all_shipments = $database->getReference('shipment')->getSnapshot()->getValue() ?: [];

    $deliveries_by_driver = [];
    foreach ($all_shipments as $s) {
        if (!is_array($s) || ($s['Ship_Status'] ?? '') !== 'DELIVERED') {
            continue;
        }
        $driv_id = $s['Driv_Id'] ?? '';
        $deliveries_by_driver[$driv_id] = ($deliveries_by_driver[$driv_id] ?? 0) + 1;
    }

    foreach ($all_drivers as $d) {
        if (!is_array($d)) {
            continue;
        }
        $driv_id = $d['Driv_Id'] ?? '';
        fputcsv($output, [
            $driv_id,
            trim(($d['Driv_FirstName'] ?? '') . ' ' . ($d['Driv_LastName'] ?? '')),
            $d['Driv_Balance'] ?? 0,
            $deliveries_by_driver[$driv_id] ?? 0,
        ]);
    }
}

fclose($output);
exit;
