<?php
require_once __DIR__ . '/../config/db.php';
$cust_upper = $database->getReference('CUSTOMER')->getSnapshot()->getValue() ?: [];
$cust_lower = $database->getReference('customer')->getSnapshot()->getValue() ?: [];
$seller = $database->getReference('seller')->getSnapshot()->getValue() ?: [];
echo "CUSTOMER count: " . count($cust_upper) . "\n";
echo "customer count: " . count($cust_lower) . "\n";
echo "seller count: " . count($seller) . "\n";
