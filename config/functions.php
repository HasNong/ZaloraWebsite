<?php
/**
 * Global Helper Functions for Zalora Ecosystem
 */

function add_notification($database, $cust_id, $type, $title, $message, $channel = 'PUSH') {
    $newNotif = $database->getReference('notification')->push();
    return $newNotif->set([
        'Notif_Id' => $newNotif->getKey(),
        'Cust_Id' => $cust_id,
        'Notif_Type' => $type,
        'Notif_Title' => $title,
        'Notif_Message' => $message,
        'Notif_Channel' => $channel,
        'Notif_SentAt' => date('Y-m-d H:i:s')
    ]);
}

function award_points($database, $cust_id, $order_id, $points) {
    // Get current balance from last transaction
    $loyaltyRef = $database->getReference('loyalty_points')->orderByChild('Cust_Id')->equalTo($cust_id)->getSnapshot()->getValue();
    $current = 0;
    if ($loyaltyRef) {
        // Sort by date descending
        usort($loyaltyRef, function($a, $b) {
            return strtotime($b['Loyal_CreatedAt'] ?? 0) - strtotime($a['Loyal_CreatedAt'] ?? 0);
        });
        $current = $loyaltyRef[0]['Loyal_Balance_after'] ?? 0;
    }
    
    $new_bal = $current + $points;
    
    $newRecord = $database->getReference('loyalty_points')->push();
    return $newRecord->set([
        'Loyal_Id' => $newRecord->getKey(),
        'Cust_Id' => $cust_id,
        'Order_Id' => $order_id,
        'Loyal_TransType' => 'EARNED',
        'Loyal_Points' => $points,
        'Loyal_Balance_after' => $new_bal,
        'Loyal_CreatedAt' => date('Y-m-d H:i:s')
    ]);
}

function deduct_points($database, $cust_id, $points) {
    $loyaltyRef = $database->getReference('loyalty_points')->orderByChild('Cust_Id')->equalTo($cust_id)->getSnapshot()->getValue();
    $current = 0;
    if ($loyaltyRef) {
        usort($loyaltyRef, function($a, $b) {
            return strtotime($b['Loyal_CreatedAt'] ?? 0) - strtotime($a['Loyal_CreatedAt'] ?? 0);
        });
        $current = $loyaltyRef[0]['Loyal_Balance_after'] ?? 0;
    }
    
    if ($current < $points) return false;
    
    $new_bal = $current - $points;
    
    $newRecord = $database->getReference('loyalty_points')->push();
    return $newRecord->set([
        'Loyal_Id' => $newRecord->getKey(),
        'Cust_Id' => $cust_id,
        'Loyal_TransType' => 'REDEEMED',
        'Loyal_Points' => $points,
        'Loyal_Balance_after' => $new_bal,
        'Loyal_CreatedAt' => date('Y-m-d H:i:s')
    ]);
}
?>
