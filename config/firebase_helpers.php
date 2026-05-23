<?php
/**
 * Shared Firebase Realtime Database helpers for Zalora.
 */

function fb_canonical_node(string $node): string {
    static $map = [
        'CUSTOMER' => 'customer',
        'CART' => 'cart',
        'CART_ITEM' => 'cart_item',
        'ORDERS' => 'orders',
        'ORDER_ITEM' => 'order_item',
        'ORDER_COUPON' => 'order_coupon',
        'ADDRESS' => 'address',
        'WISHLIST' => 'wishlist',
        'WISHLIST_ITEM' => 'wishlist_item',
        'DRIVER' => 'driver',
        'SELLER' => 'seller',
        'PRODUCT' => 'product',
        'BRAND' => 'brand',
        'PRODUCT_IMAGE' => 'product_image',
        'PRODUCT_VARIANT' => 'product_variant',
        'ROLE_APPLICATION' => 'role_application',
        'CATEGORY' => 'category',
    ];

    $upper = strtoupper($node);
    return $map[$upper] ?? strtolower($node);
}

function fb_ref($database, string $node) {
    return $database->getReference(fb_canonical_node($node));
}

function fb_merge_nodes($database, ...$nodeNames) {
    $merged = [];
    $seen = [];
    foreach ($nodeNames as $name) {
        $canonical = fb_canonical_node($name);
        if (isset($seen[$canonical])) {
            continue;
        }
        $seen[$canonical] = true;
        $data = $database->getReference($canonical)->getSnapshot()->getValue();
        if (is_array($data)) {
            $merged = array_merge($merged, $data);
        }
    }
    return $merged;
}

function fb_next_id($database, $node, $idField) {
    $all = fb_ref($database, $node)->getSnapshot()->getValue() ?: [];
    $max = 0;
    foreach ($all as $row) {
        if (!is_array($row)) {
            continue;
        }
        $max = max($max, intval($row[$idField] ?? 0));
    }
    return $max + 1;
}

function fb_find_record($database, $nodes, $field, $value) {
    $tried = [];
    foreach ((array) $nodes as $node) {
        $canonical = fb_canonical_node($node);
        if (isset($tried[$canonical])) {
            continue;
        }
        $tried[$canonical] = true;
        $snapshot = $database->getReference($canonical)->orderByChild($field)->equalTo($value)->getSnapshot();
        if ($snapshot->hasChildren()) {
            $data = $snapshot->getValue();
            $key = key($data);
            return [
                'key' => $key,
                'node' => $canonical,
                'data' => reset($data),
            ];
        }
    }
    return null;
}

function fb_update_record($database, $nodes, $field, $value, array $updates) {
    $found = fb_find_record($database, $nodes, $field, $value);
    if (!$found) {
        return false;
    }
    $database->getReference($found['node'])->getChild($found['key'])->update($updates);
    return true;
}

function fb_filter_by_child($database, $nodes, $field, $value) {
    $results = [];
    $tried = [];
    foreach ((array) $nodes as $node) {
        $canonical = fb_canonical_node($node);
        if (isset($tried[$canonical])) {
            continue;
        }
        $tried[$canonical] = true;
        $data = $database->getReference($canonical)->orderByChild($field)->equalTo($value)->getSnapshot()->getValue();
        if (is_array($data)) {
            foreach ($data as $key => $row) {
                if (is_array($row)) {
                    $row['_firebase_key'] = $key;
                    $results[] = $row;
                }
            }
        }
    }
    return $results;
}
