<?php
session_start();
$_GET['category'] = 'Kids';
chdir(__DIR__ . '/../customer');
$_SERVER['REQUEST_URI'] = '/customer/products.php';
ob_start();
include 'products.php';
$html = ob_get_clean();

if (strpos($html, 'Wool Shirts') !== false) {
    echo "Wool Shirts found in HTML output!";
} else {
    echo "Wool Shirts NOT FOUND in HTML output.";
    echo "\n\nProducts Array Length: " . substr_count($html, 'product-card');
}
