<?php
require_once __DIR__ . '/../config/db.php';

$all = [];
foreach (['CUSTOMER', 'customer'] as $node) {
    $data = $database->getReference($node)->getSnapshot()->getValue() ?: [];
    foreach ($data as $key => $c) {
        if (is_array($c)) {
            $all[] = ['node' => $node, 'key' => $key, 'data' => $c];
        }
    }
}

$byEmail = [];
foreach ($all as $row) {
    $email = strtolower(trim($row['data']['Cust_Email'] ?? ''));
    if (!$email) continue;
    $byEmail[$email][] = $row;
}

$dupes = 0;
foreach ($byEmail as $email => $rows) {
    if (count($rows) > 1) {
        $dupes++;
        echo "DUPLICATE EMAIL: $email\n";
        foreach ($rows as $r) {
            echo "  {$r['node']}/{$r['key']} Cust_Id={$r['data']['Cust_Id']}\n";
        }
    }
}

echo "Done. Total: " . count($all) . ", duplicate emails: $dupes\n";
