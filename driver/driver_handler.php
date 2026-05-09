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
        $proof_img_path = NULL;

        // Handle File Upload
        if (isset($_FILES['proof_img']) && $_FILES['proof_img']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['proof_img']['name'], PATHINFO_EXTENSION);
            $filename = "proof_" . $order_id . "_" . time() . "." . $ext;
            $target_dir = "../assets/images/proofs/";
            $proof_img_path = "assets/images/proofs/" . $filename;
            
            if (!move_uploaded_file($_FILES['proof_img']['tmp_name'], $target_dir . $filename)) {
                $_SESSION['error'] = "Failed to upload proof image.";
                header("Location: dashboard.php");
                exit;
            }
        } else {
            $_SESSION['error'] = "Proof of delivery image is required.";
            header("Location: dashboard.php");
            exit;
        }
        
        // Use a transaction for safety
        $conn->begin_transaction();
        try {
            // 1. Update Shipment
            $stmt1 = $conn->prepare("UPDATE shipment SET Ship_Status = 'DELIVERED', Ship_DeliveredAt = NOW(), Ship_ProofImg = ? WHERE Order_Id = ? AND Driv_Id = ?");
            $stmt1->bind_param("ssi", $proof_img_path, $order_id, $driver_id);
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
    } elseif ($_POST['action'] === 'pickup_return') {
        $rtrn_id = intval($_POST['rtrn_id']);
        
        $stmt = $conn->prepare("UPDATE return_request SET Rtrn_Status = 'PICKED_UP' WHERE Rtrn_Id = ?");
        $stmt->bind_param("i", $rtrn_id);
        if ($stmt->execute()) {
            $_SESSION['success'] = "Return item #$rtrn_id picked up successfully!";
        } else {
            $_SESSION['error'] = "Error picking up return.";
        }
    }
    
    header("Location: dashboard.php");
    exit;
}
?>
