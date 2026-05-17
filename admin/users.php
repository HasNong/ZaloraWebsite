<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}
require_once '../config/db.php';

$msg = "";

// Handle Add Seller
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_seller') {
    $name = $_POST['business_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $pass = $_POST['password'] ?? '';
    
    if ($name && $email && $pass) {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        
        // Manual ID generation
        $max_res = $conn->query("SELECT MAX(Sell_Id) as max_id FROM SELLER");
        $sell_id = ($max_res->fetch_assoc()['max_id'] ?? 0) + 1;
        
        $stmt = $conn->prepare("INSERT INTO SELLER (Sell_Id, Sell_BusinessName, Sell_Email, Sell_PsswdHash, Sell_IsVerified, Sell_JoinedAt, Sell_IsActive) VALUES (?, ?, ?, ?, 1, NOW(), 1)");
        $stmt->bind_param("isss", $sell_id, $name, $email, $hash);
        
        if ($stmt->execute()) {
            $msg = "Seller '$name' created successfully!";
        } else {
            $msg = "Error: " . $conn->error;
        }
    }
}

// Handle Delete Seller (Soft Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_seller') {
    $sell_id = intval($_POST['sell_id'] ?? 0);
    if ($sell_id > 0) {
        $stmt = $conn->prepare("UPDATE SELLER SET Sell_IsActive = 0 WHERE Sell_Id = ?");
        $stmt->bind_param("i", $sell_id);
        if ($stmt->execute()) {
            $msg = "Seller account #$sell_id has been successfully deleted/deactivated.";
        } else {
            $msg = "Error: " . $conn->error;
        }
    }
}

// Handle Delete Customer (Soft Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_customer') {
    $cust_id = intval($_POST['cust_id'] ?? 0);
    if ($cust_id > 0) {
        $stmt = $conn->prepare("UPDATE CUSTOMER SET Cust_IsActive = 0 WHERE Cust_Id = ?");
        $stmt->bind_param("i", $cust_id);
        if ($stmt->execute()) {
            $msg = "Customer account #$cust_id has been successfully deleted/deactivated.";
        } else {
            $msg = "Error: " . $conn->error;
        }
    }
}

// Fetch active sellers and customers
$sellers = $conn->query("SELECT * FROM SELLER WHERE Sell_IsActive = 1 ORDER BY Sell_JoinedAt DESC");
$customers = $conn->query("SELECT * FROM CUSTOMER WHERE Cust_IsActive = 1 ORDER BY Cust_CreatedAt DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Zalora Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .tabs { display: flex; gap: 10px; margin-bottom: 2rem; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .tab-btn { background: none; border: none; font-family: inherit; font-size: 13px; font-weight: 700; color: #888; cursor: pointer; padding: 8px 16px; text-transform: uppercase; letter-spacing: 0.05em; transition: 0.2s; }
        .tab-btn.active { color: #000; border-bottom: 2px solid #000; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }

        .btn-delete { background: #e74c3c; color: white; border: none; padding: 6px 12px; font-size: 10px; font-weight: 700; text-transform: uppercase; cursor: pointer; border-radius: 4px; transition: 0.2s; }
        .btn-delete:hover { background: #c0392b; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <header class="header">
        <h1 class="page-title">User Management</h1>
    </header>

    <?php if($msg): ?>
        <p style="background: #000; color: #fff; padding: 12px 20px; font-size: 12px; font-weight: 600; margin-bottom: 20px;"><?= htmlspecialchars($msg) ?></p>
    <?php endif; ?>

    <div class="tabs">
        <button class="tab-btn active" onclick="switchTab('sellers-tab', this)">Sellers</button>
        <button class="tab-btn" onclick="switchTab('customers-tab', this)">Customers</button>
    </div>

    <!-- SELLERS TAB -->
    <div id="sellers-tab" class="tab-content active">
        <div class="card" style="margin-bottom: 2rem;">
            <h3 style="font-size: 12px; text-transform: uppercase; margin-top: 0; margin-bottom: 1.5rem;">Create New Seller Account</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add_seller">
                <div class="form-row">
                    <div class="form-group">
                        <label>Business Name</label>
                        <input type="text" name="business_name" required placeholder="e.g. Luxury Silks Ltd.">
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" required placeholder="seller@example.com">
                    </div>
                    <div class="form-group">
                        <label>Temporary Password</label>
                        <input type="password" name="password" required placeholder="••••••••">
                    </div>
                </div>
                <button type="submit" class="btn-primary btn-small" style="margin-top: 1.5rem; width: 150px;">Create Account</button>
            </form>
        </div>

        <div class="card" style="padding: 0;">
            <table>
                <thead>
                    <tr>
                        <th>Seller ID</th>
                        <th>Business Name</th>
                        <th>Email</th>
                        <th>Joined Date</th>
                        <th>Status</th>
                        <th style="text-align: right; padding-right: 2rem;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($sellers && $sellers->num_rows > 0): ?>
                        <?php while($s = $sellers->fetch_assoc()): ?>
                        <tr>
                            <td>#<?= $s['Sell_Id'] ?></td>
                            <td style="font-weight: 600;"><?= htmlspecialchars($s['Sell_BusinessName']) ?></td>
                            <td><?= htmlspecialchars($s['Sell_Email']) ?></td>
                            <td><?= date('M d, Y', strtotime($s['Sell_JoinedAt'])) ?></td>
                            <td><span class="badge" style="background: #eafaf1; color: #2b8a73;">Verified</span></td>
                            <td style="text-align: right; padding-right: 2rem;">
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete/deactivate this seller?');">
                                    <input type="hidden" name="action" value="delete_seller">
                                    <input type="hidden" name="sell_id" value="<?= $s['Sell_Id'] ?>">
                                    <button type="submit" class="btn-delete">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center; padding: 30px; color:#999; font-style:italic;">No sellers registered.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- CUSTOMERS TAB -->
    <div id="customers-tab" class="tab-content">
        <div class="card" style="padding: 0;">
            <table>
                <thead>
                    <tr>
                        <th>Customer ID</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Email</th>
                        <th>Balance</th>
                        <th>Joined Date</th>
                        <th style="text-align: right; padding-right: 2rem;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($customers && $customers->num_rows > 0): ?>
                        <?php while($c = $customers->fetch_assoc()): ?>
                        <tr>
                            <td>#<?= $c['Cust_Id'] ?></td>
                            <td style="font-weight: 600;"><?= htmlspecialchars($c['Cust_Firstname']) ?></td>
                            <td style="font-weight: 600;"><?= htmlspecialchars($c['Cust_Lastname']) ?></td>
                            <td><?= htmlspecialchars($c['Cust_Email']) ?></td>
                            <td style="font-weight: 600;">$<?= number_format($c['Cust_Balance'], 2) ?></td>
                            <td><?= date('M d, Y', strtotime($c['Cust_CreatedAt'])) ?></td>
                            <td style="text-align: right; padding-right: 2rem;">
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete/deactivate this customer?');">
                                    <input type="hidden" name="action" value="delete_customer">
                                    <input type="hidden" name="cust_id" value="<?= $c['Cust_Id'] ?>">
                                    <button type="submit" class="btn-delete">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align:center; padding: 30px; color:#999; font-style:italic;">No customers registered.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function switchTab(tabId, btn) {
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    
    document.getElementById(tabId).classList.add('active');
    btn.classList.add('active');
}
</script>

</body>
</html>
