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
    $name  = trim($_POST['business_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if ($name && $email && $pass) {
        $hash    = password_hash($pass, PASSWORD_DEFAULT);
        $newSell = $database->getReference('seller')->push();
        $sell_id = $newSell->getKey();
        $newSell->set([
            'Sell_Id'           => $sell_id,
            'Sell_BusinessName' => $name,
            'Sell_Email'        => $email,
            'Sell_PsswdHash'    => $hash,
            'Sell_IsVerified'   => 1,
            'Sell_IsActive'     => 1,
            'Sell_JoinedAt'     => date('Y-m-d H:i:s')
        ]);
        $msg = "Seller '$name' created successfully!";
    }
}

// Handle Delete Seller (Soft Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_seller') {
    $sell_id = $_POST['sell_id'] ?? null;
    if ($sell_id) {
        $ref = $database->getReference('seller')->orderByChild('Sell_Id')->equalTo($sell_id)->getSnapshot()->getValue();
        if ($ref) {
            $key = key($ref);
            $database->getReference('seller')->getChild($key)->update(['Sell_IsActive' => 0]);
            $msg = "Seller account has been successfully deactivated.";
        }
    }
}

// Handle Delete Customer (Soft Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_customer') {
    $cust_id = $_POST['cust_id'] ?? null;
    if ($cust_id) {
        $ref = $database->getReference('customer')->orderByChild('Cust_Id')->equalTo($cust_id)->getSnapshot()->getValue();
        if ($ref) {
            $key = key($ref);
            $database->getReference('customer')->getChild($key)->update(['Cust_IsActive' => 0]);
            $msg = "Customer account has been successfully deactivated.";
        }
    }
}

// Fetch active sellers and customers
$all_sellers_raw = array_merge(
    $database->getReference('seller')->getSnapshot()->getValue() ?: [],
    $database->getReference('seller')->getSnapshot()->getValue() ?: []
);
$sellers = array_filter($all_sellers_raw, fn($s) => ($s['Sell_IsActive'] ?? 1) == 1);
usort($sellers, fn($a, $b) => strtotime($b['Sell_JoinedAt'] ?? 0) - strtotime($a['Sell_JoinedAt'] ?? 0));

$all_customers_raw = array_merge(
    $database->getReference('customer')->getSnapshot()->getValue() ?: [],
    $database->getReference('customer')->getSnapshot()->getValue() ?: []
);
$customers = array_filter($all_customers_raw, fn($c) => ($c['Cust_IsActive'] ?? 1) == 1);
usort($customers, fn($a, $b) => strtotime($b['Cust_CreatedAt'] ?? 0) - strtotime($a['Cust_CreatedAt'] ?? 0));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Zalora Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/admin-users.css?v=<?= time() ?>">
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
                    <?php if (count($sellers) > 0): ?>
                        <?php foreach($sellers as $s): ?>
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
                        <?php endforeach; ?>
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
                    <?php if (count($customers) > 0): ?>
                        <?php foreach($customers as $c): ?>
                        <tr>
                            <td>#<?= $c['Cust_Id'] ?></td>
                            <td style="font-weight: 600;"><?= htmlspecialchars($c['Cust_Firstname']) ?></td>
                            <td style="font-weight: 600;"><?= htmlspecialchars($c['Cust_Lastname']) ?></td>
                            <td><?= htmlspecialchars($c['Cust_Email']) ?></td>
                            <td style="font-weight: 600;">$<?= number_format($c['Cust_Balance'] ?? 0, 2) ?></td>
                            <td><?= date('M d, Y', strtotime($c['Cust_CreatedAt'])) ?></td>
                            <td style="text-align: right; padding-right: 2rem;">
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete/deactivate this customer?');">
                                    <input type="hidden" name="action" value="delete_customer">
                                    <input type="hidden" name="cust_id" value="<?= $c['Cust_Id'] ?>">
                                    <button type="submit" class="btn-delete">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
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
