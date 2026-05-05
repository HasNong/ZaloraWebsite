<?php
require_once 'config/db.php';

echo "--- RECENT PRODUCTS & IMAGE LINKS ---\n";
$query = "SELECT p.Prod_Name, i.PImg_ImgUrl 
          FROM PRODUCT p 
          JOIN PRODUCT_IMAGE i ON p.Prod_Id = i.Prod_Id 
          ORDER BY p.Prod_Id DESC LIMIT 5";

$res = $conn->query($query);

if ($res->num_rows > 0) {
    while($row = $res->fetch_assoc()) {
        echo "Product: " . str_pad($row['Prod_Name'], 25) . " | DB Link: " . $row['PImg_ImgUrl'] . "\n";
    }
} else {
    echo "No products found with images yet.\n";
}

echo "\n--- PHYSICAL FILES IN UPLOADS FOLDER ---\n";
$dir = 'assets/uploads/products/';
if (is_dir($dir)) {
    $files = array_diff(scandir($dir), array('.', '..'));
    foreach($files as $file) {
        echo "File: " . $file . "\n";
    }
} else {
    echo "Uploads directory does not exist yet.\n";
}
?>
