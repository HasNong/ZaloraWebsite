<?php
require_once 'config/db.php';
$res = $conn->query("SELECT p.Prod_Name, i.PImg_ImgUrl FROM PRODUCT p JOIN PRODUCT_IMAGE i ON p.Prod_Id = i.Prod_Id ORDER BY p.Prod_Id DESC LIMIT 5");
echo "RECENT PRODUCT IMAGES IN DB:\n";
while($row = $res->fetch_assoc()) {
    echo "Product: " . $row['Prod_Name'] . " | Path: " . $row['PImg_ImgUrl'] . "\n";
}

echo "\nFILES IN UPLOADS FOLDER:\n";
$files = scandir('assets/uploads/products');
print_r($files);
?>
