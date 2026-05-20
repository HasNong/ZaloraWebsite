<?php
/**
 * Global Helper Functions for Zalora Ecosystem
 */

function add_notification($conn, $cust_id, $type, $title, $message, $channel = 'PUSH') {
    $res = $conn->query("SELECT MAX(Notif_Id) as max_id FROM notification");
    $id = ($res->fetch_assoc()['max_id'] ?? 0) + 1;
    $stmt = $conn->prepare("INSERT INTO notification (Notif_Id, Cust_Id, Notif_Type, Notif_Title, Notif_Message, Notif_Channel, Notif_SentAt) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("iissss", $id, $cust_id, $type, $title, $message, $channel);
    return $stmt->execute();
}

function award_points($conn, $cust_id, $order_id, $points) {
    // Get current balance from last transaction
    $stmt_bal = $conn->prepare("SELECT Loyal_Balance_after FROM loyalty_points WHERE Cust_Id = ? ORDER BY Loyal_CreatedAt DESC LIMIT 1");
    $stmt_bal->bind_param("i", $cust_id);
    $stmt_bal->execute();
    $res_bal = $stmt_bal->get_result()->fetch_assoc();
    $current = $res_bal['Loyal_Balance_after'] ?? 0;
    
    $new_bal = $current + $points;
    
    $res_id = $conn->query("SELECT MAX(Loyal_Id) as max_id FROM loyalty_points");
    $id = ($res_id->fetch_assoc()['max_id'] ?? 0) + 1;
    
    $stmt = $conn->prepare("INSERT INTO loyalty_points (Loyal_Id, Cust_Id, Order_Id, Loyal_TransType, Loyal_Points, Loyal_Balance_after, Loyal_CreatedAt) VALUES (?, ?, ?, 'EARNED', ?, ?, NOW())");
    $stmt->bind_param("iiidd", $id, $cust_id, $order_id, $points, $new_bal);
    return $stmt->execute();
}

function deduct_points($conn, $cust_id, $points) {
    $stmt_bal = $conn->prepare("SELECT Loyal_Balance_after FROM loyalty_points WHERE Cust_Id = ? ORDER BY Loyal_CreatedAt DESC LIMIT 1");
    $stmt_bal->bind_param("i", $cust_id);
    $stmt_bal->execute();
    $res_bal = $stmt_bal->get_result()->fetch_assoc();
    $current = $res_bal['Loyal_Balance_after'] ?? 0;
    
    if ($current < $points) return false;
    
    $new_bal = $current - $points;
    
    $res_id = $conn->query("SELECT MAX(Loyal_Id) as max_id FROM loyalty_points");
    $id = ($res_id->fetch_assoc()['max_id'] ?? 0) + 1;
    
    $stmt = $conn->prepare("INSERT INTO loyalty_points (Loyal_Id, Cust_Id, Loyal_TransType, Loyal_Points, Loyal_Balance_after, Loyal_CreatedAt) VALUES (?, ?, 'REDEEMED', ?, ?, NOW())");
    $stmt->bind_param("iidd", $id, $cust_id, $points, $new_bal);
    return $stmt->execute();
}
?>
