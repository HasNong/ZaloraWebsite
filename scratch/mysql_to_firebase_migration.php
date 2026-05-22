<?php
// Run this script from the browser or command line to migrate data from MySQL to Firebase Realtime Database
require_once __DIR__ . '/../config/db.php';

echo "Starting migration from MySQL to Firebase Realtime Database...\n";

// Get all tables
$result = $conn->query("SHOW TABLES");
$tables = [];
while ($row = $result->fetch_array()) {
    $tables[] = $row[0];
}

foreach ($tables as $table) {
    echo "Migrating table: $table...\n";
    $tableRef = $database->getReference($table);

    $records = $conn->query("SELECT * FROM `$table`");
    
    if (!$records) {
        echo "Failed to read from $table\n";
        continue;
    }

    $count = 0;
    while ($row = $records->fetch_assoc()) {
        // Try to find a primary key for node ID
        $nodeId = null;
        if (isset($row['id'])) {
            $nodeId = (string)$row['id'];
        } elseif (isset($row[$table . '_id'])) {
            $nodeId = (string)$row[$table . '_id'];
        }
        
        if ($nodeId) {
            $tableRef->getChild($nodeId)->set($row);
        } else {
            $tableRef->push($row);
        }
        $count++;
    }
    
    echo "  -> Migrated $count records for table $table.\n";
}

echo "Migration completed successfully.\n";
?>
