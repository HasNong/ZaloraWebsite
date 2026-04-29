<?php
$conn = new mysqli("localhost", "root", "hansong123", "malalay_zalora");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
