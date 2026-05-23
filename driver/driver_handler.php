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
        
        try {
            // Locate Shipment
            $shipmentRef = $database->getReference('shipment')->orderByChild('Order_Id')->equalTo($order_id)->getSnapshot()->getValue();
            if ($shipmentRef) {
                foreach ($shipmentRef as $s_key => $s_data) {
                    if ($s_data['Driv_Id'] == $driver_id) {
                        $database->getReference('shipment')->getChild($s_key)->update([
                            'Ship_Status' => 'DELIVERED',
                            'Ship_DeliveredAt' => date('Y-m-d H:i:s'),
                            'Ship_ProofImg' => $proof_img_path
                        ]);
                        break;
                    }
                }
            }
            
            // Locate Order
            $orderRef = $database->getReference('orders')->orderByChild('Order_Id')->equalTo($order_id)->getSnapshot()->getValue();
            $cust_id = 0;
            $order_total = 0;
            if ($orderRef) {
                $o_key = key($orderRef);
                $o_node = $database->getReference('orders')->getChild($o_key)->getSnapshot()->exists() ? 'orders' : 'orders';
                $database->getReference($o_node)->getChild($o_key)->update([
                    'Order_Status' => 'DELIVERED',
                    'Order_UpdatedAt' => date('Y-m-d H:i:s')
                ]);
                $cust_id = $orderRef[$o_key]['Cust_Id'] ?? 0;
                $order_total = $orderRef[$o_key]['Order_TotalAmnt'] ?? 0;
            }
            
            // Add delivery fee to driver balance
            $driverRef = $database->getReference('driver')->orderByChild('Driv_Id')->equalTo($driver_id)->getSnapshot()->getValue();
            if ($driverRef) {
                $d_key = key($driverRef);
                $d_node = 'driver';
                $current_balance = $driverRef[$d_key]['Driv_Balance'] ?? 0;
                $database->getReference($d_node)->getChild($d_key)->update([
                    'Driv_Balance' => $current_balance + 15.00
                ]);
            }

            // Award Loyalty Points & Send Notification
            if ($cust_id && $order_total) {
                require_once '../config/functions.php';
                $points = floor($order_total);
                award_points($database, $cust_id, $order_id, $points);
                add_notification($database, $cust_id, 'ORDER_UPDATE', 'Package Delivered!', "Your order #$order_id has been delivered. Enjoy your purchase! You earned $points points.");
            }
            
            $_SESSION['success'] = "Package delivered! $15.00 has been credited to your balance and the customer has been notified.";
        } catch (Exception $e) {
            $_SESSION['error'] = "Error completing delivery: " . $e->getMessage();
        }
    } elseif ($_POST['action'] === 'pickup_return') {
        $rtrn_id = intval($_POST['rtrn_id']);
        
        $rtrnRef = $database->getReference('return_request')->orderByChild('Rtrn_Id')->equalTo($rtrn_id)->getSnapshot()->getValue();
        if ($rtrnRef) {
            $r_key = key($rtrnRef);
            $database->getReference('return_request')->getChild($r_key)->update([
                'Rtrn_Status' => 'PICKED_UP'
            ]);
            $_SESSION['success'] = "Return item #$rtrn_id picked up successfully!";
        } else {
            $_SESSION['error'] = "Error picking up return. Request not found.";
        }
    }
    
    header("Location: dashboard.php");
    exit;
}
?>
