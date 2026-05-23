<?php
session_start();
require_once '../config/db.php';

// Auth check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'seller') {
    header("Location: ../auth/login.php");
    exit;
}

$seller_id = $_SESSION['user_id'];
$prod_id = $_GET['id'] ?? '';

// Verify ownership and fetch data
$product = $database->getReference("product/$prod_id")->getSnapshot()->getValue();

if (!$product || ($product['Sell_Id'] ?? '') != $seller_id) {
    die("Product not found or access denied.");
}

// Fetch image
$imagesRef = $database->getReference('product_image')->orderByChild('Prod_Id')->equalTo($prod_id)->getSnapshot()->getValue() ?: [];
$primary_img = '';
foreach ($imagesRef as $img) {
    if (($img['PImg_IsPrimary'] ?? 0) == 1) {
        $primary_img = $img['PImg_ImgUrl'] ?? '';
        break;
    }
}
$product['PImg_ImgUrl'] = $primary_img;

// Fetch ALL Variants
$variantsRef = $database->getReference('product_variant')->orderByChild('Prod_Id')->equalTo($prod_id)->getSnapshot()->getValue() ?: [];
$current_variants = array_values($variantsRef);

// Fetch Categories for dropdown
$categoriesRef = $database->getReference('category')->getSnapshot()->getValue() ?: [];
$categories = [];
foreach ($categoriesRef as $c) {
    if (($c['Ctgry_IsActive'] ?? 0) == 1) {
        $categories[] = $c;
    }
}
usort($categories, function($a, $b) {
    return strcmp($a['Ctgry_Name'] ?? '', $b['Ctgry_Name'] ?? '');
});

// Fetch Brands for dropdown
$brandsRef = $database->getReference('brand')->getSnapshot()->getValue() ?: [];
$brands = array_values($brandsRef);
usort($brands, function($a, $b) {
    return strcmp($a['Brand_Name'] ?? '', $b['Brand_Name'] ?? '');
});

$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $desc = $_POST['desc'] ?? '';
    $price = $_POST['price'] ?? 0;
    $ctgry_id = $_POST['category_id'] ?? 0;
    $brand_id = !empty($_POST['brand_id']) ? $_POST['brand_id'] : 1; // Default
    $image_base64 = $_POST['prod_image_data'] ?? '';
    
    // Multi-variant handling
    $variants = $_POST['variants'] ?? [];

    if ($name && $price > 0 && $ctgry_id && !empty($variants)) {
        // UPDATE PRODUCT
        $updateData = [
            'Brand_Id' => $brand_id,
            'Ctgry_Id' => $ctgry_id,
            'Prod_Name' => $name,
            'Prod_Desc' => $desc,
            'Prod_BasePrice' => (float)$price,
            'Prod_UpdatedAt' => date('Y-m-d H:i:s')
        ];
        $database->getReference("product/$prod_id")->update($updateData);

        // Sync Variants
        foreach ($variants as $v) {
            $v_id    = $v['id'] ?? '';
            $v_size  = $v['size'] ?? 'M';
            $v_color = $v['color'] ?? 'Default';
            $v_stock = intval($v['stock'] ?? 0);

            if (!empty($v_id)) {
                // Update existing
                $database->getReference("product_variant/$v_id")->update([
                    'PVar_Size' => $v_size,
                    'PVar_Color' => $v_color,
                    'PVar_StockQuantity' => $v_stock
                ]);
            } else {
                // Insert new
                $sku = "SKU-" . substr(md5($prod_id), 0, 6) . "-" . strtoupper(substr($v_color, 0, 3)) . "-" . strtoupper($v_size);
                
                $newVarRef = $database->getReference('product_variant')->push();
                $newVarRef->set([
                    'PVar_Id' => $newVarRef->getKey(),
                    'Prod_Id' => $prod_id,
                    'PVar_Sku' => $sku,
                    'PVar_Size' => $v_size,
                    'PVar_Color' => $v_color,
                    'PVar_StockQuantity' => $v_stock
                ]);
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
                    $file_name = 'prod_' . substr(md5($prod_id), 0, 8) . '_' . time() . '.' . $image_type;
                    $file_path = $upload_dir . $file_name;
                    $db_save_path = 'assets/uploads/products/' . $file_name;
                    
                    if (file_put_contents($file_path, $image_base64_decoded)) {
                        // Update or Insert image
                        $oldImages = $database->getReference('product_image')->orderByChild('Prod_Id')->equalTo($prod_id)->getSnapshot()->getValue() ?: [];
                        foreach ($oldImages as $imgId => $img) {
                            $database->getReference("product_image/$imgId")->remove();
                        }
                        
                        $newImgRef = $database->getReference('product_image')->push();
                        $newImgRef->set([
                            'PImg_Id' => $newImgRef->getKey(),
                            'Prod_Id' => $prod_id,
                            'PImg_ImgUrl' => $db_save_path,
                            'PImg_IsPrimary' => 1
                        ]);
                    }
                }
            }
        }

        $_SESSION['success_msg'] = "Product '$name' updated successfully!";
        header("Location: inventory.php");
        exit;
    } else {
        $msg = "Please ensure all required fields (*) are filled correctly.";
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
    <link rel="stylesheet" href="../assets/css/seller-product-form.css?v=<?= time() ?>">
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
                            <?php foreach($categories as $c): ?>
                                <option value="<?= $c['Ctgry_Id'] ?>" <?= ($c['Ctgry_Id'] ?? '') == ($product['Ctgry_Id'] ?? '') ? 'selected' : '' ?>><?= htmlspecialchars($c['Ctgry_Name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Brand</label>
                        <select name="brand_id">
                            <option value="">No Brand</option>
                            <?php foreach($brands as $b): ?>
                                <option value="<?= $b['Brand_Id'] ?>" <?= ($b['Brand_Id'] ?? '') == ($product['Brand_Id'] ?? '') ? 'selected' : '' ?>><?= htmlspecialchars($b['Brand_Name']) ?></option>
                            <?php endforeach; ?>
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
