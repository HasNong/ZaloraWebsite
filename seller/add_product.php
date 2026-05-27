<?php
session_start();
require_once '../config/db.php';

// Auth check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'seller') {
    header("Location: ../auth/login.php");
    exit;
}

$seller_id = $_SESSION['user_id'];

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
        
        $newProductRef = $database->getReference('product')->push();
        $prod_id = $newProductRef->getKey();

        $productData = [
            'Prod_Id' => $prod_id,
            'Sell_Id' => $seller_id,
            'Brand_Id' => $brand_id,
            'Ctgry_Id' => $ctgry_id,
            'Prod_Name' => $name,
            'Prod_Desc' => $desc,
            'Prod_BasePrice' => (float)$price,
            'Prod_IsActive' => 0,
            'Prod_CreatedAt' => date('Y-m-d H:i:s'),
            'Prod_UpdatedAt' => date('Y-m-d H:i:s')
        ];
        
        $newProductRef->set($productData);

        // Handle Image Upload
        if (!empty($image_base64)) {
            // Standardize base64 string to be a data URL if it isn't already
            if (strpos($image_base64, 'data:image/') === false) {
                $image_base64 = 'data:image/png;base64,' . $image_base64;
            }
            $newImgRef = $database->getReference('product_image')->push();
            $newImgRef->set([
                'PImg_Id' => $newImgRef->getKey(),
                'Prod_Id' => $prod_id,
                'PImg_ImgUrl' => $image_base64,
                'PImg_IsPrimary' => 1
            ]);
        }

        // LOOP THROUGH VARIANTS and save each one
        foreach ($variants as $v) {
            $v_size = $v['size'] ?? 'M';
            $v_color = $v['color'] ?? 'Default';
            $v_stock = intval($v['stock'] ?? 0);

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

        // Handle Product Tags - Store as array in product node
        $tags_raw = $_POST['tags'] ?? '';
        if (!empty($tags_raw)) {
            $tags_array = array_map('trim', explode(',', $tags_raw));
            $tags_array = array_filter($tags_array); // remove empty
            if (!empty($tags_array)) {
                $newProductRef->update(['Tags' => array_values($tags_array)]);
            }
        }

            // Success Redirect
            $_SESSION['success_msg'] = "Product '$name' has been successfully submitted and is pending admin approval!";
            header("Location: inventory.php");
            exit;
        } else {
            $msg = "Please ensure all required fields (*) are filled correctly.";
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Center - Add Product</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/seller.css">
    <style>
        .form-card { 
            background: var(--white); 
            padding: 2.5rem; 
            border: 1px solid var(--border); 
            max-width: 900px; 
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
        }
        .form-card:hover {
            box-shadow: var(--shadow-md);
        }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; font-size: 11px; font-weight: 700; letter-spacing: 0.1em; color: var(--text-light); margin-bottom: 8px; text-transform: uppercase; }
        .form-group input, .form-group select, .form-group textarea { 
            width: 100%; 
            padding: 12px; 
            border: 1px solid var(--border); 
            font-family: inherit; 
            font-size: 13px; 
            outline: none; 
            transition: var(--transition); 
            box-sizing: border-box;
            border-radius: var(--radius-sm);
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: var(--black); }
        
        /* Dropzone */
        .upload-zone {
            width: 100%;
            height: 200px;
            border: 2px dashed var(--border);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            background: var(--background);
            border-radius: var(--radius-sm);
        }
        .upload-zone:hover { border-color: var(--black); background: rgba(0,0,0,0.02); }
        .upload-zone.has-image { border-style: solid; border-color: var(--border); }
        .upload-zone img { width: 100%; height: 100%; object-fit: contain; }
        .upload-zone p { font-size: 11px; font-weight: 700; color: var(--text-light); margin-top: 10px; }
        
        .alert { 
            padding: 15px; 
            margin-bottom: 2rem; 
            font-size: 12px; 
            font-weight: 600; 
            border-radius: var(--radius-sm);
            background: var(--accent-red-bg); 
            color: var(--accent-red-text);
            border: 1px solid rgba(0,0,0,0.02); 
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-wrapper">
    <main class="main-content">
        <header class="page-header">
            <div>
                <h2 class="page-title">ADD NEW PRODUCT</h2>
                <p class="page-subtitle">List a new item in your global catalog.</p>
            </div>
        </header>

        <?php if($msg): ?>
            <div class="alert"><?= $msg ?></div>
        <?php endif; ?>

        <div class="form-card">
            <form method="POST" id="productForm">
                <!-- Unique ID and Name to prevent conflicts -->
                <input type="hidden" name="prod_image_data" id="prod_image_data">
                <div class="form-row">
                    <div class="dashboard-left">
                        <div class="form-group">
                            <label>Product Name *</label>
                            <input type="text" name="name" placeholder="e.g. Silk Minimalist Dress" required>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="desc" rows="6" placeholder="Describe your product..."></textarea>
                        </div>
                    </div>
                    <div class="dashboard-right">
                        <div class="form-group">
                            <label>Product Image (Click, Drop or Paste) *</label>
                            <div class="upload-zone" id="dropZone">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                                <p>DRAG IMAGE HERE OR PASTE</p>
                                <input type="file" id="fileInput" accept="image/*" style="display:none;">
                                <input type="hidden" name="image_base64" id="image_base64">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Category *</label>
                        <select name="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach($categories as $c): ?>
                                <option value="<?= $c['Ctgry_Id'] ?>"><?= htmlspecialchars($c['Ctgry_Name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Brand</label>
                        <select name="brand_id">
                            <option value="">Select Brand</option>
                            <?php foreach($brands as $b): ?>
                                <option value="<?= $b['Brand_Id'] ?>"><?= htmlspecialchars($b['Brand_Name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Product Tags (Comma separated)</label>
                    <input type="text" name="tags" placeholder="e.g. Summer, Linen, Casual, Vintage">
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
                            <tr class="variant-row">
                                <td style="padding: 10px;">
                                    <select name="variants[0][size]" style="padding: 8px; width: 100%; border: 1px solid #ddd;">
                                        <option value="XS">XS</option>
                                        <option value="S">S</option>
                                        <option value="M" selected>M</option>
                                        <option value="L">L</option>
                                        <option value="XL">XL</option>
                                        <option value="One Size">One Size</option>
                                    </select>
                                </td>
                                <td style="padding: 10px;"><input type="text" name="variants[0][color]" placeholder="e.g. Blue" style="padding: 8px; width: 100%; border: 1px solid #ddd;" required></td>
                                <td style="padding: 10px;"><input type="number" name="variants[0][stock]" value="10" min="0" style="padding: 8px; width: 100%; border: 1px solid #ddd;" required></td>
                                <td style="padding: 10px;"><button type="button" onclick="removeRow(this)" style="background: none; border: none; color: #ff4d4d; cursor: pointer; font-size: 18px;">&times;</button></td>
                            </tr>
                        </tbody>
                    </table>
                    <button type="button" onclick="addVariantRow()" style="margin-top: 15px; background: #fff; border: 1px dashed #ccc; padding: 10px; width: 100%; cursor: pointer; font-size: 10px; font-weight: 700; color: #666;">+ ADD ANOTHER SIZE/COLOR</button>
                </div>

                <div class="form-row" style="grid-template-columns: 1fr;">
                    <div class="form-group">
                        <label>Base Price ($) *</label>
                        <input type="number" step="0.01" name="price" placeholder="0.00" required>
                    </div>
                </div>

                <div style="display:flex; justify-content: flex-end; padding-top:1.5rem;">
                     <button type="submit" class="btn-add-product" style="width: 100%; border: none; cursor: pointer;">PUBLISH PRODUCT</button>
                </div>
            </form>
        </div>
    </main>
</div>

<script>
let variantCount = 1;
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

// Click to upload
dropZone.onclick = () => fileInput.click();

fileInput.onchange = (e) => {
    handleFile(e.target.files[0]);
};

// Drag and Drop
dropZone.ondragover = (e) => { e.preventDefault(); dropZone.classList.add('hover'); };
dropZone.ondragleave = () => { dropZone.classList.remove('hover'); };
dropZone.ondrop = (e) => {
    e.preventDefault();
    dropZone.classList.remove('hover');
    handleFile(e.dataTransfer.files[0]);
};

// Paste from clipboard
window.onpaste = (e) => {
    const items = e.clipboardData.items;
    for (let i = 0; i < items.length; i++) {
        if (items[i].type.indexOf("image") !== -1) {
            handleFile(items[i].getAsFile());
        }
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
        dropZone.style.borderColor = 'var(--accent-green)';
    };
    reader.readAsDataURL(file);
}

document.getElementById('productForm').onsubmit = (e) => {
    if (!base64Input.value) {
        alert("Please paste or upload an image first!");
        e.preventDefault();
        return false;
    }
};
</script>

</body>
</html>
