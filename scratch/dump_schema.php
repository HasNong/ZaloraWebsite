<?php
require_once 'config/db.php';

$res = $conn->query("SHOW TABLES");
$tables = [];
while($row = $res->fetch_array()) {
    $tables[] = $row[0];
}

$schema = [];
foreach($tables as $t) {
    $create_res = $conn->query("SHOW CREATE TABLE `$t`");
    $create_row = $create_res->fetch_assoc();
    $schema[$t] = $create_row['Create Table'];
}

file_put_contents('scratch/schema_dump.json', json_encode($schema, JSON_PRETTY_PRINT));
echo "Dumped " . count($tables) . " tables to scratch/schema_dump.json\n";
?>
