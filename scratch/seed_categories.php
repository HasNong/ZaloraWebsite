<?php
require_once 'config/db.php';

$cats = ["WOMEN", "MEN", "KIDS", "LUXURY", "BEAUTY"];

foreach ($cats as $name) {
    $stmt = $conn->prepare("SELECT Ctgry_Id FROM CATEGORY WHERE Ctgry_Name = ?");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows === 0) {
        // Get max ID manually since user mentioned current schema doesn't use AUTO_INCREMENT on some tables
        $max_res = $conn->query("SELECT MAX(Ctgry_Id) as max_id FROM CATEGORY");
        $max_id = ($max_res->fetch_assoc()['max_id'] ?? 0) + 1;
        
        $ins = $conn->prepare("INSERT INTO CATEGORY (Ctgry_Id, Ctgry_Name, Ctgry_IsActive, Ctgry_DisplayOrd) VALUES (?, ?, 1, 0)");
        $ins->bind_param("is", $max_id, $name);
        $ins->execute();
        echo "Inserted Category: $name with ID $max_id\n";
    } else {
        echo "Category already exists: $name\n";
    }
}
?>
