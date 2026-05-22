<?php
class FirebaseMySQLi extends mysqli {
    public function __construct() {
        // Do not call parent constructor
    }
    public function real_escape_string($string) : string {
        return addslashes($string);
    }
}

$conn = new FirebaseMySQLi();
try {
    $escaped = mysqli_real_escape_string($conn, "hello'world");
    echo "Escaped: " . $escaped . "\n";
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
