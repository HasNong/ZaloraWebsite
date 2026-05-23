<?php
require_once __DIR__ . '/../config/db.php';
$val = $database->getReference('/')->getSnapshot()->getValue();
if ($val) {
    foreach(array_keys($val) as $k) {
        echo $k . "\n";
    }
} else {
    echo "No data\n";
}
