<?php
session_start();
require_once '../config/db.php';

// Role Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Handle Status Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $rtrn_id = $_POST['rtrn_id'];
    $action = $_POST['action'];
    $status = ($action === 'approve') ? 'APPROVED' : 'REJECTED';

    $rtrnRef = $database->getReference('return_request')->orderByChild('Rtrn_Id')->equalTo(intval($rtrn_id))->getSnapshot()->getValue();
    if ($rtrnRef) {
        $key = key($rtrnRef);
        $database->getReference('return_request')->getChild($key)->update(['Rtrn_Status' => $status]);
        $_SESSION['success'] = "Return request $status successfully.";
    }
    header("Location: returns.php");
    exit;
}

// Fetch Returns with in-memory joins
$returnsRaw = $database->getReference('return_request')->getSnapshot()->getValue() ?: [];
$all_customers = array_merge(
    $database->getReference('customer')->getSnapshot()->getValue() ?: [],
    $database->getReference('customer')->getSnapshot()->getValue() ?: []
);
$all_order_items = array_merge(
    $database->getReference('order_item')->getSnapshot()->getValue() ?: [],
    $database->getReference('order_item')->getSnapshot()->getValue() ?: []
);
$all_pvariants = $database->getReference('product_variant')->getSnapshot()->getValue() ?: [];
$all_products  = $database->getReference('product')->getSnapshot()->getValue() ?: [];

// Build lookup maps
$cust_map = [];
foreach ($all_customers as $c) { if (isset($c['Cust_Id'])) $cust_map[$c['Cust_Id']] = $c; }
$oi_map = [];
foreach ($all_order_items as $oi) { if (isset($oi['OdItm_Id'])) $oi_map[$oi['OdItm_Id']] = $oi; }
$pvar_map = [];
foreach ($all_pvariants as $pv) { if (isset($pv['PVar_Id'])) $pvar_map[$pv['PVar_Id']] = $pv; }
$prod_map = [];
foreach ($all_products as $p) { if (isset($p['Prod_Id'])) $prod_map[$p['Prod_Id']] = $p; }

$returns = [];
foreach ($returnsRaw as $rr) {
    $cust = $cust_map[$rr['Cust_Id'] ?? ''] ?? [];
    $oi   = $oi_map[$rr['OdItm_Id'] ?? ''] ?? [];
    $pvar = $pvar_map[$oi['PVar_Id'] ?? ''] ?? [];
    $prod = $prod_map[$pvar['Prod_Id'] ?? ''] ?? [];
    $returns[] = array_merge($rr, [
        'Cust_FirstName'  => $cust['Cust_Firstname'] ?? 'Unknown',
        'Cust_LastName'   => $cust['Cust_Lastname'] ?? '',
        'Prod_Name'       => $prod['Prod_Name'] ?? 'Unknown Product',
        'OdItm_Quantity'  => $oi['OdItm_Quantity'] ?? 0,
        'OdItm_Subtotal'  => $oi['OdItm_Subtotal'] ?? 0
    ]);
}
usort($returns, fn($a, $b) => strtotime($b['Rtrn_CreatedAt'] ?? 0) - strtotime($a['Rtrn_CreatedAt'] ?? 0));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Return Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/admin-returns.css?v=<?= time() ?>">
</head>
<body class="admin-body">
    <div class="admin-layout">
        <?php include 'sidebar.php'; ?>
        
        <main class="main-content">
            <div class="returns-container">
                <h1 class="page-title">Return Management</h1>
                
                <?php if (isset($_SESSION['success'])): ?>
                    <div style="background: #e6fffa; color: #2c7a7b; padding: 15px; margin-bottom: 20px; font-size: 12px; font-weight: 600;">
                        <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>

                <table class="return-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Product</th>
                            <th>Reason</th>
                            <th>Evidence</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($returns as $r): ?>
                        <tr>
                            <td>#<?= $r['Rtrn_Id'] ?></td>
                            <td><?= htmlspecialchars($r['Cust_FirstName'] . ' ' . $r['Cust_LastName']) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($r['Prod_Name']) ?></strong><br>
                                <span style="font-size: 10px; color: #999;">Qty: <?= $r['OdItm_Quantity'] ?></span>
                            </td>
                            <td style="max-width: 200px;"><?= htmlspecialchars($r['Rtrn_Reason']) ?></td>
                            <td>
                                <?php if (!empty($r['Rtrn_PicEvidence'])): ?>
                                    <img src="../<?= htmlspecialchars($r['Rtrn_PicEvidence']) ?>" class="evidence-img" onclick="window.open(this.src)">
                                <?php else: ?>
                                    <span style="color: #999; font-style: italic;">No Photo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?= strtolower($r['Rtrn_Status']) ?>"><?= $r['Rtrn_Status'] ?></span>
                            </td>
                            <td>
                                <?php if ($r['Rtrn_Status'] === 'PENDING'): ?>
                                    <form method="POST" style="display: flex; gap: 5px;">
                                        <input type="hidden" name="rtrn_id" value="<?= $r['Rtrn_Id'] ?>">
                                        <button type="submit" name="action" value="approve" class="btn-action btn-approve">Approve</button>
                                        <button type="submit" name="action" value="reject" class="btn-action btn-reject">Reject</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
