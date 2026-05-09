<?php
session_start();
require_once '../config/db.php';

// Auth check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'seller') {
    header("Location: ../auth/login.php");
    exit;
}

$seller_id = $_SESSION['user_id'];
$prod_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Verify ownership and fetch data
$check = $conn->prepare("SELECT p.*, i.PImg_ImgUrl 
                         FROM PRODUCT p 
                         LEFT JOIN PRODUCT_IMAGE i ON p.Prod_Id = i.Prod_Id AND i.PImg_IsPrimary = 1 
                         WHERE p.Prod_Id = ? AND p.Sell_Id = ?");
$check->bind_param("ii", $prod_id, $seller_id);
$check->execute();
$res = $check->get_result();
if ($res->num_rows === 0) {
    die("Product not found or access denied.");
}
$product = $res->fetch_assoc();

// Fetch ALL Variants
$variants_res = $conn->query("SELECT * FROM PRODUCT_VARIANT WHERE Prod_Id = $prod_id");
$current_variants = [];
while($v = $variants_res->fetch_assoc()) $current_variants[] = $v;

// Fetch Categories and Brands
$categories = $conn->query("SELECT Ctgry_Id, Ctgry_Name FROM CATEGORY WHERE Ctgry_IsActive = 1 ORDER BY Ctgry_Name");
$brands = $conn->query("SELECT Brand_Id, Brand_Name FROM BRAND ORDER BY Brand_Name");

$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $desc = $_POST['desc'] ?? '';
    $price = $_POST['price'] ?? 0;
    $ctgry_id = $_POST['category_id'] ?? 0;
    $brand_id = !empty($_POST['brand_id']) ? intval($_POST['brand_id']) : 1; // Default to ID 1
    $image_base64 = $_POST['prod_image_data'] ?? '';
    
    // Multi-variant handling
    $variants = $_POST['variants'] ?? [];

    if ($name && $price > 0 && $ctgry_id > 0 && !empty($variants)) {
        // UPDATE PRODUCT
        $stmt = $conn->prepare("UPDATE PRODUCT SET Brand_Id = ?, Ctgry_Id = ?, Prod_Name = ?, Prod_Desc = ?, Prod_BasePrice = ?, Prod_UpdatedAt = NOW() WHERE Prod_Id = ? AND Sell_Id = ?");
        $stmt->bind_param("iissdii", $brand_id, $ctgry_id, $name, $desc, $price, $prod_id, $seller_id);
        
        if ($stmt->execute()) {
            // Sync Variants
            foreach ($variants as $v) {
                $v_id    = !empty($v['id']) ? intval($v['id']) : 0;
                $v_size  = $v['size'] ?? 'M';
                $v_color = $v['color'] ?? 'Default';
                $v_stock = intval($v['stock'] ?? 0);

                if ($v_id > 0) {
                    // Update existing
                    $v_stmt = $conn->prepare("UPDATE PRODUCT_VARIANT SET PVar_Size = ?, PVar_Color = ?, PVar_StockQuantity = ? WHERE PVar_Id = ? AND Prod_Id = ?");
                    $v_stmt->bind_param("ssiii", $v_size, $v_color, $v_stock, $v_id, $prod_id);
                    $v_stmt->execute();
                } else {
                    // Insert new
                    $max_var = $conn->query("SELECT MAX(PVar_Id) as max_id FROM PRODUCT_VARIANT");
                    $pvar_id = ($max_var->fetch_assoc()['max_id'] ?? 0) + 1;
                    $sku = "SKU-" . $prod_id . "-" . strtoupper(substr($v_color, 0, 3)) . "-" . strtoupper($v_size);
                    
                    $v_stmt = $conn->prepare("INSERT INTO PRODUCT_VARIANT (PVar_Id, Prod_Id, PVar_Sku, PVar_Size, PVar_Color, PVar_StockQuantity) VALUES (?, ?, ?, ?, ?, ?)");
                    $v_stmt->bind_param("iisssi", $pvar_id, $prod_id, $sku, $v_size, $v_color, $v_stock);
                    $v_stmt->execute();
                }
            }
            if (!empty($image_base64)) {
                $upload_dir = '../assets/uploads/products/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

                if (strpos($image_base64, 'base64,') !== false) {
                    $image_parts = explode("base64,", $image_base64);
                    $image_base64_decoded = base64_decode($image_parts[1]);
                    $image_type_aux = explode("image/", $image_parts[0]);
                    $image_type = trim($image_type_aux[1] ?? 'png', '; ');
                    
                    if ($image_base64_decoded) {
                        $file_name = 'prod_' . $prod_id . '_' . time() . '.' . $image_type;
                        $file_path = $upload_dir . $file_name;
                        $db_save_path = 'assets/uploads/products/' . $file_name;
                        
                        if (file_put_contents($file_path, $image_base64_decoded)) {
                            // Update or Insert image
                            $conn->query("DELETE FROM PRODUCT_IMAGE WHERE Prod_Id = $prod_id");
                            $max_img = $conn->query("SELECT MAX(PImg_Id) as max_id FROM PRODUCT_IMAGE");
                            $pimg_id = ($max_img->fetch_assoc()['max_id'] ?? 0) + 1;
                            
                            $img_stmt = $conn->prepare("INSERT INTO PRODUCT_IMAGE (PImg_Id, Prod_Id, PImg_ImgUrl, PImg_IsPrimary) VALUES (?, ?, ?, 1)");
                            $img_stmt->bind_param("iis", $pimg_id, $prod_id, $db_save_path);
                            $img_stmt->execute();
                        }
                    }
                }
            }

            $_SESSION['success_msg'] = "Product '$name' updated successfully!";
            header("Location: inventory.php");
            exit;
        } else {
            $msg = "Update Error: " . $stmt->error;
        }
    }
}

$current_img = $product['PImg_ImgUrl'] ?? '';
if ($current_img && strpos($current_img, 'http') === false) $current_img = '../' . $current_img;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Center - Edit Product</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/seller.css">
    <style>
        .form-card { background: white; padding: 2.5rem; border: 1px solid #eee; max-width: 900px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; font-size: 11px; font-weight: 700; letter-spacing: 0.1em; color: #999; margin-bottom: 8px; text-transform: uppercase; }
        .form-group input, .form-group select, .form-group textarea { 
            width: 100%; padding: 12px; border: 1px solid #e0e0e0; font-family: 'Inter', sans-serif; font-size: 13px; outline: none; box-sizing: border-box;
        }
        .upload-zone { width: 100%; height: 200px; border: 2px dashed #eee; display: flex; flex-direction: column; align-items: center; justify-content: center; cursor: pointer; position: relative; overflow: hidden; background: #fafafa; }
        .upload-zone.has-image { border-style: solid; border-color: #eee; }
        .upload-zone img { width: 100%; height: 100%; object-fit: contain; }
        .alert { padding: 15px; margin-bottom: 2rem; font-size: 12px; font-weight: 600; border-left: 4px solid #000; background: #f9f9f9; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-wrapper">
    <main class="main-content">
        <header class="page-header">
            <div>
                <h2 class="page-title">EDIT PRODUCT</h2>
                <p class="page-subtitle">Update details for: <?= htmlspecialchars($product['Prod_Name']) ?></p>
            </div>
        </header>

        <?php if($msg): ?>
            <div class="alert"><?= $msg ?></div>
        <?php endif; ?>

        <div class="form-card">
            <form method="POST" id="productForm">
                <input type="hidden" name="prod_image_data" id="prod_image_data">
                <div class="form-row">
                    <div class="dashboard-left">
                        <div class="form-group">
                            <label>Product Name *</label>
                            <input type="text" name="name" value="<?= htmlspecialchars($product['Prod_Name']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="desc" rows="6"><?= htmlspecialchars($product['Prod_Desc']) ?></textarea>
                        </div>
                    </div>
                    <div class="dashboard-right">
                        <div class="form-group">
                            <label>Product Image (Paste to replace)</label>
                            <div class="upload-zone <?= $current_img ? 'has-image' : '' ?>" id="dropZone">
                                <?php if($current_img): ?>
                                    <img src="<?= $current_img ?>" id="previewImg">
                                <?php else: ?>
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                                    <p>DRAG IMAGE HERE OR PASTE</p>
                                <?php endif; ?>
                                <input type="file" id="fileInput" accept="image/*" style="display:none;">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Category *</label>
                        <select name="category_id" required>
                            <?php while($c = $categories->fetch_assoc()): ?>
                                <option value="<?= $c['Ctgry_Id'] ?>" <?= $c['Ctgry_Id'] == $product['Ctgry_Id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['Ctgry_Name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Brand</label>
                        <select name="brand_id">
                            <option value="">No Brand</option>
                            <?php while($b = $brands->fetch_assoc()): ?>
                                <option value="<?= $b['Brand_Id'] ?>" <?= $b['Brand_Id'] == $product['Brand_id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['Brand_Name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div style="margin-bottom: 2rem; border: 1px solid #eee; padding: 1.5rem; background: #fafafa;">
                    <label style="display: block; font-size: 11px; font-weight: 700; letter-spacing: 0.1em; color: #999; margin-bottom: 15px; text-transform: uppercase;">Product Variants (Sizes & Stock)</label>
                    <table style="width: 100%; border-collapse: collapse;" id="variantTable">
                        <thead>
                            <tr style="text-align: left; border-bottom: 1px solid #eee;">
                                <th style="padding: 10px; font-size: 10px; color: #666;">SIZE</th>
                                <th style="padding: 10px; font-size: 10px; color: #666;">COLOR</th>
                                <th style="padding: 10px; font-size: 10px; color: #666;">STOCK</th>
                                <th style="padding: 10px; font-size: 10px; color: #666;">ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($current_variants as $index => $v): ?>
                            <tr class="variant-row">
                                <input type="hidden" name="variants[<?= $index ?>][id]" value="<?= $v['PVar_Id'] ?>">
                                <td style="padding: 10px;">
                                    <select name="variants[<?= $index ?>][size]" style="padding: 8px; width: 100%; border: 1px solid #ddd;">
                                        <?php 
                                            $sizes = ['XS', 'S', 'M', 'L', 'XL', 'One Size'];
                                            foreach($sizes as $s): 
                                        ?>
                                            <option value="<?= $s ?>" <?= $v['PVar_Size'] == $s ? 'selected' : '' ?>><?= $s ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td style="padding: 10px;"><input type="text" name="variants[<?= $index ?>][color]" value="<?= htmlspecialchars($v['PVar_Color']) ?>" style="padding: 8px; width: 100%; border: 1px solid #ddd;" required></td>
                                <td style="padding: 10px;"><input type="number" name="variants[<?= $index ?>][stock]" value="<?= $v['PVar_StockQuantity'] ?>" min="0" style="padding: 8px; width: 100%; border: 1px solid #ddd;" required></td>
                                <td style="padding: 10px;"><button type="button" onclick="removeRow(this)" style="background: none; border: none; color: #ff4d4d; cursor: pointer; font-size: 18px;">&times;</button></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <button type="button" onclick="addVariantRow()" style="margin-top: 15px; background: #fff; border: 1px dashed #ccc; padding: 10px; width: 100%; cursor: pointer; font-size: 10px; font-weight: 700; color: #666;">+ ADD ANOTHER SIZE/COLOR</button>
                </div>

                <div class="form-row" style="grid-template-columns: 1fr;">
                    <div class="form-group">
                        <label>Base Price ($) *</label>
                        <input type="number" step="0.01" name="price" value="<?= $product['Prod_BasePrice'] ?>" required>
                    </div>
                </div>

                <div style="display:flex; align-items:flex-end; padding-top:1.5rem; gap: 10px;">
                         <button type="submit" class="btn-add-product" style="flex: 2; border: none; cursor: pointer;">SAVE CHANGES</button>
                         <a href="inventory.php" style="flex: 1; text-align: center; text-decoration: none; padding: 16px; background: #eee; color: #000; font-size: 11px; font-weight: 700; text-transform: uppercase;">CANCEL</a>
                </div>
            </form>
        </div>
    </main>
</div>

<script>
let variantCount = <?= count($current_variants) ?>;
function addVariantRow() {
    const tbody = document.querySelector('#variantTable tbody');
    const newRow = document.createElement('tr');
    newRow.className = 'variant-row';
    newRow.innerHTML = `
        <td style="padding: 10px;">
            <select name="variants[${variantCount}][size]" style="padding: 8px; width: 100%; border: 1px solid #ddd;">
                <option value="XS">XS</option><option value="S">S</option><option value="M" selected>M</option><option value="L">L</option><option value="XL">XL</option><option value="One Size">One Size</option>
            </select>
        </td>
        <td style="padding: 10px;"><input type="text" name="variants[${variantCount}][color]" placeholder="e.g. Blue" style="padding: 8px; width: 100%; border: 1px solid #ddd;" required></td>
        <td style="padding: 10px;"><input type="number" name="variants[${variantCount}][stock]" value="10" min="0" style="padding: 8px; width: 100%; border: 1px solid #ddd;" required></td>
        <td style="padding: 10px;"><button type="button" onclick="removeRow(this)" style="background: none; border: none; color: #ff4d4d; cursor: pointer; font-size: 18px;">&times;</button></td>
    `;
    tbody.appendChild(newRow);
    variantCount++;
}

function removeRow(btn) {
    const rows = document.querySelectorAll('.variant-row');
    if (rows.length > 1) btn.closest('tr').remove();
    else alert('At least one variant is required.');
}
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');
const base64Input = document.getElementById('prod_image_data');

dropZone.onclick = () => fileInput.click();
fileInput.onchange = (e) => handleFile(e.target.files[0]);
dropZone.ondragover = (e) => { e.preventDefault(); dropZone.style.borderColor = '#000'; };
dropZone.ondrop = (e) => { e.preventDefault(); handleFile(e.dataTransfer.files[0]); };
window.onpaste = (e) => {
    const items = e.clipboardData.items;
    for (let i = 0; i < items.length; i++) {
        if (items[i].type.indexOf("image") !== -1) handleFile(items[i].getAsFile());
    }
};

function handleFile(file) {
    if (!file) return;
    const reader = new FileReader();
    reader.onload = (e) => {
        const base64 = e.target.result;
        base64Input.value = base64;
        dropZone.innerHTML = `<img src="${base64}" style="width:100%; height:100%; object-fit:contain;">`;
        dropZone.classList.add('has-image');
    };
    reader.readAsDataURL(file);
}
</script>
</body>
</html>
