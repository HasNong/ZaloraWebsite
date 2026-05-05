<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}
require_once '../config/db.php';

$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_seller') {
    $name = $_POST['business_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $pass = $_POST['password'] ?? '';
    
    if ($name && $email && $pass) {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        
        // Manual ID generation
        $max_res = $conn->query("SELECT MAX(Sell_Id) as max_id FROM SELLER");
        $sell_id = ($max_res->fetch_assoc()['max_id'] ?? 0) + 1;
        
        $stmt = $conn->prepare("INSERT INTO SELLER (Sell_Id, Sell_BusinessName, Sell_Email, Sell_PsswdHash, Sell_IsVerified, Sell_JoinedAt) VALUES (?, ?, ?, ?, 1, NOW())");
        $stmt->bind_param("isss", $sell_id, $name, $email, $hash);
        
        if ($stmt->execute()) {
            $msg = "Seller '$name' created successfully!";
        } else {
            $msg = "Error: " . $conn->error;
        }
    }
}

$sellers = $conn->query("SELECT * FROM SELLER ORDER BY Sell_JoinedAt DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Management - Zalora Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <header class="header">
        <h1 class="page-title">Seller Management</h1>
    </header>

    <?php if($msg): ?>
        <p style="background: #000; color: #fff; padding: 10px; font-size: 12px; font-weight: 600;"><?= $msg ?></p>
    <?php endif; ?>

    <div class="card">
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

    <table>
        <thead>
            <tr>
                <th>Seller ID</th>
                <th>Business Name</th>
                <th>Email</th>
                <th>Joined Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php while($s = $sellers->fetch_assoc()): ?>
            <tr>
                <td>#<?= $s['Sell_Id'] ?></td>
                <td style="font-weight: 600;"><?= htmlspecialchars($s['Sell_BusinessName']) ?></td>
                <td><?= htmlspecialchars($s['Sell_Email']) ?></td>
                <td><?= date('M d, Y', strtotime($s['Sell_JoinedAt'])) ?></td>
                <td><span class="badge">Verified</span></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

</body>
</html>
