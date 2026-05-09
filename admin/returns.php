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
    $rtrn_id = intval($_POST['rtrn_id']);
    $action = $_POST['action'];
    $status = ($action === 'approve') ? 'APPROVED' : 'REJECTED';
    
    $stmt = $conn->prepare("UPDATE return_request SET Rtrn_Status = ? WHERE Rtrn_Id = ?");
    $stmt->bind_param("si", $status, $rtrn_id);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Return request $status successfully.";
    }
    header("Location: returns.php");
    exit;
}

// Fetch Returns
$query = "SELECT rr.*, c.Cust_FirstName, c.Cust_LastName, p.Prod_Name, p.Prod_Id,
                 oi.OdItm_Quantity, oi.OdItm_Subtotal
          FROM return_request rr
          JOIN customer c ON rr.Cust_Id = c.Cust_Id
          JOIN ORDER_ITEM oi ON rr.OdItm_Id = oi.OdItm_Id
          JOIN PRODUCT_VARIANT pv ON oi.PVar_Id = pv.PVar_Id
          JOIN PRODUCT p ON pv.Prod_Id = p.Prod_Id
          ORDER BY rr.Rtrn_CreatedAt DESC";
$returns = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Return Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .returns-container { padding: 40px; }
        .page-title { font-size: 24px; font-weight: 700; text-transform: uppercase; margin-bottom: 30px; letter-spacing: 0.05em; }
        .return-table { width: 100%; border-collapse: collapse; background: #fff; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .return-table th { background: #000; color: #fff; text-transform: uppercase; font-size: 10px; letter-spacing: 0.1em; padding: 20px; text-align: left; }
        .return-table td { padding: 20px; border-bottom: 1px solid #eee; font-size: 13px; vertical-align: top; }
        .status-badge { padding: 5px 10px; font-size: 10px; font-weight: 700; border-radius: 3px; text-transform: uppercase; }
        .status-pending { background: #fff4e5; color: #b7791f; }
        .status-approved { background: #e6fffa; color: #2c7a7b; }
        .status-rejected { background: #fff5f5; color: #c53030; }
        .btn-action { padding: 8px 15px; font-size: 10px; font-weight: 700; border: none; cursor: pointer; text-transform: uppercase; }
        .btn-approve { background: #000; color: #fff; }
        .btn-reject { background: #fff; color: #000; border: 1px solid #000; }
        .evidence-img { width: 80px; height: 80px; object-fit: cover; border: 1px solid #eee; cursor: pointer; }
    </style>
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
                        <?php while($r = $returns->fetch_assoc()): ?>
                        <tr>
                            <td>#<?= $r['Rtrn_Id'] ?></td>
                            <td><?= htmlspecialchars($r['Cust_FirstName'] . ' ' . $r['Cust_LastName']) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($r['Prod_Name']) ?></strong><br>
                                <span style="font-size: 10px; color: #999;">Qty: <?= $r['OdItm_Quantity'] ?></span>
                            </td>
                            <td style="max-width: 200px;"><?= htmlspecialchars($r['Rtrn_Reason']) ?></td>
                            <td>
                                <?php if ($r['Rtrn_PicEvidence']): ?>
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
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
