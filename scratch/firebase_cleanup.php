<?php
/**
 * Consolidate duplicate uppercase Firebase nodes into lowercase canonical nodes.
 *
 * Usage:
 *   php scratch/firebase_cleanup.php --dry-run
 *   php scratch/firebase_cleanup.php --execute
 */
require_once __DIR__ . '/../config/db.php';

$dryRun = !in_array('--execute', $argv ?? [], true);
$mode = $dryRun ? 'DRY RUN' : 'EXECUTE';

echo "=== Firebase Data Cleanup [$mode] ===\n\n";

$mergePairs = [
    'CUSTOMER' => 'customer',
    'ADDRESS' => 'address',
    'WISHLIST' => 'wishlist',
    'WISHLIST_ITEM' => 'wishlist_item',
];

$stats = ['moved' => 0, 'merged' => 0, 'removed_nodes' => []];

foreach ($mergePairs as $source => $target) {
    $sourceData = $database->getReference($source)->getSnapshot()->getValue() ?: [];
    $count = is_array($sourceData) ? count($sourceData) : 0;

    if ($count === 0) {
        echo "[$source -> $target] Nothing to merge.\n";
        continue;
    }

    echo "[$source -> $target] Processing $count record(s)...\n";

    foreach ($sourceData as $key => $record) {
        if (!is_array($record)) {
            continue;
        }

        $targetRef = $database->getReference($target)->getChild($key);
        $existing = $targetRef->getSnapshot()->getValue();

        if (is_array($existing)) {
            $merged = array_merge($record, $existing);
            echo "  merge key=$key (exists in $target)\n";
            if (!$dryRun) {
                $targetRef->set($merged);
            }
            $stats['merged']++;
        } else {
            echo "  move key=$key\n";
            if (!$dryRun) {
                $targetRef->set($record);
            }
            $stats['moved']++;
        }
    }

    if (!$dryRun) {
        $database->getReference($source)->remove();
        $stats['removed_nodes'][] = $source;
        echo "  removed node: $source\n";
    } else {
        echo "  would remove node: $source\n";
    }
}

echo "\n--- Orphan check: wishlist_item ---\n";
$wishlists = $database->getReference('wishlist')->getSnapshot()->getValue() ?: [];
$validWishIds = [];
foreach ($wishlists as $key => $w) {
    if (is_array($w)) {
        $validWishIds[(string) ($w['Wish_Id'] ?? $key)] = true;
    }
}

$items = $database->getReference('wishlist_item')->getSnapshot()->getValue() ?: [];
$orphans = 0;
foreach ($items as $key => $item) {
    if (!is_array($item)) {
        continue;
    }
    $wishId = (string) ($item['Wish_Id'] ?? '');
    if ($wishId && !isset($validWishIds[$wishId])) {
        echo "  orphan wishlist_item key=$key Wish_Id=$wishId\n";
        if (!$dryRun) {
            $database->getReference('wishlist_item')->getChild($key)->remove();
        }
        $orphans++;
    }
}
echo $orphans ? "Removed $orphans orphan wishlist item(s).\n" : "No orphan wishlist items.\n";

echo "\n--- Summary ---\n";
echo "Moved: {$stats['moved']}\n";
echo "Merged: {$stats['merged']}\n";
if (!empty($stats['removed_nodes'])) {
    echo "Removed nodes: " . implode(', ', $stats['removed_nodes']) . "\n";
}
if ($dryRun) {
    echo "\nRe-run with --execute to apply changes.\n";
} else {
    echo "\nCleanup complete.\n";
}
