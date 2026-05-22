<?php
// C:\xampp\htdocs\ZaloraWebsite\scratch\migrate_to_firestore.php

$project_id = 'zaloraapp-75a6d';
$mysql_host = 'localhost';
$mysql_user = 'root';
$mysql_pass = 'hansong123';
$mysql_db   = 'malalay_zalora';

echo "=== Zalora MySQL to Cloud Firestore Migration ===\n";

// 1. Connect to MySQL
$mysql = new mysqli($mysql_host, $mysql_user, $mysql_pass, $mysql_db);
if ($mysql->connect_error) {
    die("MySQL Connection failed: " . $mysql->connect_error . "\n");
}
echo "Connected to MySQL successfully.\n";

// 2. Parse Schema definitions
$schema_file = __DIR__ . '/schema_dump.json';
if (!file_exists($schema_file)) {
    die("Schema file schema_dump.json not found in scratch/.\n");
}

function parseSchema($schema_file) {
    $raw_schema = json_decode(file_get_contents($schema_file), true);
    $schemas = [];
    foreach ($raw_schema as $table => $sql) {
        $schemas[$table] = [
            'columns' => [],
            'pk' => null
        ];
        $lines = explode("\n", $sql);
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (preg_match('/^`(\w+)`\s+(\w+)/', $trimmed, $matches)) {
                $col = $matches[1];
                $type = strtolower($matches[2]);
                $schemas[$table]['columns'][$col] = $type;
            }
            if (preg_match('/PRIMARY KEY\s*\(\s*`(\w+)`\s*\)/i', $trimmed, $matches)) {
                $schemas[$table]['pk'] = $matches[1];
            }
        }
    }
    return $schemas;
}

$tableSchemas = parseSchema($schema_file);

// Helper to format values for Firestore JSON payload
function phpToFirestoreFields($row, $colTypes) {
    $fields = [];
    foreach ($row as $col => $val) {
        if ($val === null) {
            $fields[$col] = ['nullValue' => null];
            continue;
        }
        $type = isset($colTypes[$col]) ? $colTypes[$col] : 'string';
        if ($type === 'int' || $type === 'integer' || $type === 'tinyint') {
            $fields[$col] = ['integerValue' => (string)$val];
        } elseif ($type === 'decimal' || $type === 'float' || $type === 'double') {
            $fields[$col] = ['doubleValue' => (float)$val];
        } elseif ($type === 'boolean') {
            $fields[$col] = ['booleanValue' => (bool)$val];
        } else {
            $fields[$col] = ['stringValue' => (string)$val];
        }
    }
    return $fields;
}

// Helper to commit writes in a batch
function firestoreCommit($projectId, $writes) {
    $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents:commit";
    $payload = json_encode(['writes' => $writes]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($payload)
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception("Firestore Commit failed (HTTP {$httpCode}): " . $response);
    }
    return json_decode($response, true);
}

// 3. Migrate tables
foreach ($tableSchemas as $table => $schema) {
    echo "Migrating table '$table'...\n";
    $pk = $schema['pk'];
    if (!$pk) {
        echo "WARNING: Table '$table' does not have a primary key defined. Skipping.\n";
        continue;
    }

    $res = $mysql->query("SELECT * FROM `$table`");
    if (!$res) {
        echo "WARNING: Failed to query MySQL table '$table'. Skipping.\n";
        continue;
    }

    $writes = [];
    $count = 0;
    
    while ($row = $res->fetch_assoc()) {
        $pkValue = $row[$pk];
        $fields = phpToFirestoreFields($row, $schema['columns']);
        
        $docPath = "projects/{$project_id}/databases/(default)/documents/{$table}/{$pkValue}";
        $writes[] = [
            'update' => [
                'name' => $docPath,
                'fields' => $fields
            ]
        ];

        // Send batch of 400 writes
        if (count($writes) >= 400) {
            try {
                firestoreCommit($project_id, $writes);
                $count += count($writes);
                echo "  Uploaded $count records...\n";
                $writes = [];
            } catch (Exception $e) {
                die("ERROR during migration of '$table': " . $e->getMessage() . "\n");
            }
        }
    }

    // Send remaining writes
    if (count($writes) > 0) {
        try {
            firestoreCommit($project_id, $writes);
            $count += count($writes);
            $writes = [];
        } catch (Exception $e) {
            die("ERROR during migration of '$table': " . $e->getMessage() . "\n");
        }
    }

    echo "Table '$table': Successfully migrated $count records.\n";
}

echo "\nMigration complete!\n";
?>
