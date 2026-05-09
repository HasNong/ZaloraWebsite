<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'driver') {
    exit('Unauthorized');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $driver_id = $_SESSION['user_id'];
    
    if ($_POST['action'] === 'toggle_status') {
        // Toggle the Driv_IsActive bit
        $conn->query("UPDATE driver SET Driv_IsActive = 1 - Driv_IsActive WHERE Driv_Id = $driver_id");
        
        // Return current status
        $res = $conn->query("SELECT Driv_IsActive FROM driver WHERE Driv_Id = $driver_id");
        $status = $res->fetch_assoc();
        echo json_encode(['status' => $status['Driv_IsActive']]);
        exit;
    }
}
?>
