<?php
require_once __DIR__ . '/../config/db.php';
$snapshot = $database->getReference('/')->getSnapshot()->getValue();
if ($snapshot) {
    echo implode(", ", array_keys($snapshot));
} else {
    echo "No data";
}
