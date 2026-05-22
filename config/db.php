<?php
require __DIR__ . '/../vendor/autoload.php';

use Kreait\Firebase\Factory;

// Initialize Firebase
$factory = (new Factory)
    ->withServiceAccount(__DIR__ . '/../zaloramalalay-6eb75-firebase-adminsdk-fbsvc-4c7fa7c3f5.json')
    ->withDatabaseUri('https://zaloramalalay-6eb75-default-rtdb.asia-southeast1.firebasedatabase.app/');

$database = $factory->createDatabase();

// Legacy MySQL connection (keep for now until all modules are migrated)
$conn = new mysqli("localhost", "root", "hansong123", "malalay_zalora");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
