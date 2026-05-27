<?php
require_once __DIR__ . '/config/db.php';

header('Content-Type: application/json');

try {
    $customers = $database->getReference('customer')->getValue();
    echo json_encode([
        'success' => true,
        'count' => is_array($customers) ? count($customers) : 0,
        'customers' => $customers
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
