<?php
session_start();
require_once __DIR__ . '/../config/db.php';
$_SESSION['role'] = 'seller';
$_SESSION['user_id'] = 1;
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'name' => 'Test Product',
    'desc' => 'Test Desc',
    'price' => '99.99',
    'category_id' => 1,
    'brand_id' => 1,
    'prod_image_data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=',
    'variants' => [
        ['size' => 'M', 'color' => 'Red', 'stock' => 10]
    ]
];

chdir(__DIR__ . '/../seller');
include 'add_product.php';
