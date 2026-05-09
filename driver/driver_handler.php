<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'driver') {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $driver_id = $_SESSION['user_id'];
    
    if ($_POST['action'] === 'complete_delivery') {
        $order_id = $_POST['order_id'];
        
        // Use a transaction for safety
        $conn->begin_transaction();
        try {
            // 1. Update Shipment
            $stmt1 = $conn->prepare("UPDATE shipment SET Ship_Status = 'DELIVERED', Ship_DeliveredAt = NOW() WHERE Order_Id = ? AND Driv_Id = ?");
            $stmt1->bind_param("si", $order_id, $driver_id);
            $stmt1->execute();
            
            // 2. Update Order
            $stmt2 = $conn->prepare("UPDATE ORDERS SET Order_Status = 'DELIVERED' WHERE Order_Id = ?");
            $stmt2->bind_param("s", $order_id);
            $stmt2->execute();
            
            // 3. Add delivery fee to driver balance ($15.00)
            $stmt3 = $conn->prepare("UPDATE driver SET Driv_Balance = Driv_Balance + 15.00 WHERE Driv_Id = ?");
            $stmt3->bind_param("i", $driver_id);
            $stmt3->execute();
            
            $conn->commit();
            $_SESSION['success'] = "Package delivered! $15.00 has been credited to your balance.";
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error'] = "Error completing delivery: " . $e->getMessage();
        }
    }
    
    header("Location: dashboard.php");
    exit;
}
?>
