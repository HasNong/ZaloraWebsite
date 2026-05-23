<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'driver') {
    exit('Unauthorized');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $driver_id = $_SESSION['user_id'];
    
    if ($_POST['action'] === 'toggle_status') {
        $drivers = $database->getReference('driver')->orderByChild('Driv_Id')->equalTo($driver_id)->getSnapshot()->getValue();
        if ($drivers) {
            $key = key($drivers);
            $driver_data = current($drivers);
            $current_status = $driver_data['Driv_IsActive'] ?? 0;
            $new_status = 1 - $current_status;
            
            $node = 'driver';
            $database->getReference($node)->getChild($key)->update(['Driv_IsActive' => $new_status]);
            
            echo json_encode(['status' => $new_status]);
        } else {
            echo json_encode(['status' => 0, 'error' => 'Driver not found']);
        }
        exit;
    }
}
?>
