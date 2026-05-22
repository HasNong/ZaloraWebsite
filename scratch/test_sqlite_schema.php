<?php
$raw_schema = json_decode(file_get_contents('scratch/schema_dump.json'), true);
$sqlite = new PDO('sqlite::memory:');
$sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function mysqlToSqlite($mysql_create) {
    // 1. Remove ENGINE and trailing settings
    $sqlite_create = preg_replace('/\) ENGINE=.*$/s', ')', $mysql_create);
    
    // 2. Split lines
    $lines = explode("\n", $sqlite_create);
    $cleaned_lines = [];
    
    foreach ($lines as $line) {
        $trimmed = trim($line);
        
        // Skip foreign key constraints and index keys which SQLite handles differently or doesn't require in-memory
        if (preg_match('/^(KEY\b|CONSTRAINT\b|CONSTRAINT_FOREIGN\b|FOREIGN KEY\b)/i', $trimmed)) {
            continue;
        }
        
        // Convert UNIQUE KEY `name` (`col`) to UNIQUE (`col`)
        if (preg_match('/^UNIQUE KEY\s+`?\w+`?\s*\(([^)]+)\)/i', $trimmed, $matches)) {
            $cleaned_lines[] = "  UNIQUE (" . $matches[1] . ")";
            continue;
        }
        
        // Convert enum(...) to text
        if (preg_match('/enum\([^)]+\)/i', $line)) {
            $line = preg_replace('/enum\([^)]+\)/i', 'text', $line);
        }
        
        $cleaned_lines[] = $line;
    }
    
    // Reassemble and cleanup commas
    $cleaned_sql = implode("\n", $cleaned_lines);
    
    // Remove trailing commas before the closing parenthesis
    $cleaned_sql = preg_replace('/,\s*\)/s', "\n)", $cleaned_sql);
    
    // Replace MySQL specific keywords
    $cleaned_sql = str_replace('AUTO_INCREMENT', '', $cleaned_sql);
    
    return $cleaned_sql;
}

echo "--- TESTING SCHEMA CONVERSION ---\n";
foreach ($raw_schema as $table => $sql) {
    try {
        $sqlite_sql = mysqlToSqlite($sql);
        $sqlite->exec($sqlite_sql);
        echo "Table '$table': SUCCESS\n";
    } catch (Exception $e) {
        echo "Table '$table': FAILED -> " . $e->getMessage() . "\n";
        echo "SQL:\n" . mysqlToSqlite($sql) . "\n\n";
    }
}
?>
