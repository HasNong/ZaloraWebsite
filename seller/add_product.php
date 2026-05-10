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
$categories = $conn->query("SELECT Ctgry_Id, Ctgry_Name FROM CATEGORY WHERE Ctgry_IsActive = 1 ORDER BY Ctgry_Name");
// Fetch Brands for dropdown
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
        // Get next Prod_Id
        $max_res = $conn->query("SELECT MAX(Prod_Id) as max_id FROM PRODUCT");
        $prod_id = ($max_res->fetch_assoc()['max_id'] ?? 0) + 1;

        // INSERT into PRODUCT (Price is 'd' for double/decimal) - Prod_IsActive set to 0 for admin approval
        $stmt = $conn->prepare("INSERT INTO PRODUCT (Prod_Id, Sell_Id, Brand_Id, Ctgry_Id, Prod_Name, Prod_Desc, Prod_BasePrice, Prod_IsActive, Prod_CreatedAt, Prod_UpdatedAt) VALUES (?, ?, ?, ?, ?, ?, ?, 0, NOW(), NOW())");
        $stmt->bind_param("iiiissd", $prod_id, $seller_id, $brand_id, $ctgry_id, $name, $desc, $price);
        
        if ($stmt->execute()) {
            // Handle Image Upload
            if (!empty($image_base64)) {
                $upload_dir = '../assets/uploads/products/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

                // Handle both "data:image/png;base64,..." and raw base64
                if (strpos($image_base64, 'base64,') !== false) {
                    $image_parts = explode("base64,", $image_base64);
                    $image_base64_decoded = base64_decode($image_parts[1]);
                    $image_type_aux = explode("image/", $image_parts[0]);
                    $image_type = trim($image_type_aux[1] ?? 'png', '; ');
                } else {
                    $image_base64_decoded = base64_decode($image_base64);
                    $image_type = 'png';
                }
                
                if ($image_base64_decoded) {
                    $file_name = 'prod_' . $prod_id . '_' . time() . '.' . $image_type;
                    $file_path = $upload_dir . $file_name;
                    $db_save_path = 'assets/uploads/products/' . $file_name;
                    
                    if (file_put_contents($file_path, $image_base64_decoded)) {
                        $max_img = $conn->query("SELECT MAX(PImg_Id) as max_id FROM PRODUCT_IMAGE");
                        $pimg_id = ($max_img->fetch_assoc()['max_id'] ?? 0) + 1;
                        
                        $img_stmt = $conn->prepare("INSERT INTO PRODUCT_IMAGE (PImg_Id, Prod_Id, PImg_ImgUrl, PImg_IsPrimary) VALUES (?, ?, ?, 1)");
                        $img_stmt->bind_param("iis", $pimg_id, $prod_id, $db_save_path);
                        $img_stmt->execute();
                    }
                }
            }

            // LOOP THROUGH VARIANTS and save each one
            foreach ($variants as $v) {
                $v_size = $v['size'] ?? 'M';
                $v_color = $v['color'] ?? 'Default';
                $v_stock = intval($v['stock'] ?? 0);

                $max_var = $conn->query("SELECT MAX(PVar_Id) as max_id FROM product_variant");
                $pvar_id = ($max_var->fetch_assoc()['max_id'] ?? 0) + 1;
                
                $sku = "SKU-" . $prod_id . "-" . strtoupper(substr($v_color, 0, 3)) . "-" . strtoupper($v_size);
                $var_stmt = $conn->prepare("INSERT INTO product_variant (PVar_Id, Prod_Id, PVar_Sku, PVar_Size, PVar_Color, PVar_StockQuantity) VALUES (?, ?, ?, ?, ?, ?)");
                $var_stmt->bind_param("iisssi", $pvar_id, $prod_id, $sku, $v_size, $v_color, $v_stock);
                $var_stmt->execute();
            }

            // Handle Product Tags
            $tags_raw = $_POST['tags'] ?? '';
            if (!empty($tags_raw)) {
                $tags_array = explode(',', $tags_raw);
                foreach ($tags_array as $t_name) {
                    $t_name = trim($t_name);
                    if (empty($t_name)) continue;
                    
                    // Check if tag exists
                    $t_stmt = $conn->prepare("SELECT Tag_Id FROM product_tag WHERE Tag_Name = ?");
                    $t_stmt->bind_param("s", $t_name);
                    $t_stmt->execute();
                    $t_res = $t_stmt->get_result();
                    
                    if ($t_res->num_rows > 0) {
                        $tag_id = $t_res->fetch_assoc()['Tag_Id'];
                    } else {
                        $max_t = $conn->query("SELECT MAX(Tag_Id) as max_id FROM product_tag");
                        $tag_id = ($max_t->fetch_assoc()['max_id'] ?? 0) + 1;
                        $ins_t = $conn->prepare("INSERT INTO product_tag (Tag_Id, Tag_Name) VALUES (?, ?)");
                        $ins_t->bind_param("is", $tag_id, $t_name);
                        $ins_t->execute();
                    }
                    
                    // Map tag to product
                    $conn->query("INSERT INTO product_tag_map (Prod_Id, Tag_Id) VALUES ($prod_id, $tag_id)");
                }
            }

            // Success Redirect
            $_SESSION['success_msg'] = "Product '$name' has been successfully submitted and is pending admin approval!";
            header("Location: inventory.php");
            exit;
        } else {
            $msg = "Database Error: " . $stmt->error;
        }
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/seller.css">
    <style>
        .form-card { background: white; padding: 2.5rem; border: 1px solid #eee; max-width: 900px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; font-size: 11px; font-weight: 700; letter-spacing: 0.1em; color: #999; margin-bottom: 8px; text-transform: uppercase; }
        .form-group input, .form-group select, .form-group textarea { 
            width: 100%; padding: 12px; border: 1px solid #e0e0e0; font-family: 'Inter', sans-serif; font-size: 13px; outline: none; transition: all 0.2s; box-sizing: border-box;
        }
        .form-group input:focus { border-color: #000; }
        
        /* Dropzone */
        .upload-zone {
            width: 100%;
            height: 200px;
            border: 2px dashed #eee;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
            overflow: hidden;
            background: #fafafa;
        }
        .upload-zone:hover { border-color: #000; background: #f4f4f4; }
        .upload-zone.has-image { border-style: solid; border-color: #eee; }
        .upload-zone img { width: 100%; height: 100%; object-fit: contain; }
        .upload-zone p { font-size: 11px; font-weight: 600; color: #999; margin-top: 10px; }
        
        .alert { padding: 15px; margin-bottom: 2rem; font-size: 12px; font-weight: 600; border-left: 4px solid #000; background: #f9f9f9; }
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
                            <?php while($c = $categories->fetch_assoc()): ?>
                                <option value="<?= $c['Ctgry_Id'] ?>"><?= htmlspecialchars($c['Ctgry_Name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Brand</label>
                        <select name="brand_id">
                            <option value="">Select Brand</option>
                            <?php while($b = $brands->fetch_assoc()): ?>
                                <option value="<?= $b['Brand_Id'] ?>"><?= htmlspecialchars($b['Brand_Name']) ?></option>
                            <?php endwhile; ?>
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
