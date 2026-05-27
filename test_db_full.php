<?php
require_once __DIR__ . '/config/db.php';

header('Content-Type: application/json');

try {
    // Get the root of the database
    $root = $database->getReference('/')->getValue();
    
    // Search for the word "newtest" or the email that was registered
    $found_nodes = [];
    
    function search_array($arr, $query, $path = '') {
        $matches = [];
        if (!is_array($arr)) return [];
        foreach ($arr as $key => $val) {
            $current_path = $path . '/' . $key;
            if (is_array($val)) {
                $matches = array_merge($matches, search_array($val, $query, $current_path));
            } else {
                if (stripos(strval($val), $query) !== false) {
                    $matches[] = [
                        'path' => $current_path,
                        'value' => $val
                    ];
                }
            }
        }
        return $matches;
    }
    
    $matches = search_array($root, 'test');
    
    echo json_encode([
        'success' => true,
        'matches' => $matches,
        'root_nodes' => is_array($root) ? array_keys($root) : []
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
