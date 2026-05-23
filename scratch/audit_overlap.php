<?php
require_once __DIR__ . '/../config/db.php';

function ids_in_node($data, $idField) {
    $ids = [];
    foreach ($data ?: [] as $key => $row) {
        if (!is_array($row)) continue;
        $ids[] = ($row[$idField] ?? $key) . " (key=$key)";
    }
    return $ids;
}

$checks = [
    ['CUSTOMER', 'customer', 'Cust_Id'],
    ['ADDRESS', 'address', 'Addrs_id'],
    ['WISHLIST', 'wishlist', 'Wish_Id'],
    ['WISHLIST_ITEM', 'wishlist_item', 'WItm_Id'],
];

foreach ($checks as [$u, $l, $field]) {
    $upper = $database->getReference($u)->getSnapshot()->getValue() ?: [];
    $lower = $database->getReference($l)->getSnapshot()->getValue() ?: [];
    echo "=== $u vs $l ($field) ===\n";
    echo "Upper IDs: " . implode(', ', ids_in_node($upper, $field)) . "\n";
    echo "Lower IDs: " . implode(', ', ids_in_node($lower, $field)) . "\n";

    $upperBiz = [];
    foreach ($upper as $k => $r) {
        if (is_array($r)) $upperBiz[(string)($r[$field] ?? $k)] = $k;
    }
    $lowerBiz = [];
    foreach ($lower as $k => $r) {
        if (is_array($r)) $lowerBiz[(string)($r[$field] ?? $k)] = $k;
    }
    $overlap = array_intersect_key($upperBiz, $lowerBiz);
    echo "Overlapping business IDs: " . (empty($overlap) ? 'none' : implode(', ', array_keys($overlap))) . "\n\n";
}
