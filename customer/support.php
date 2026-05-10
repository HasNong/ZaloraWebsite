<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$cust_id = $_SESSION['user_id'];
$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_ticket'])) {
    $subject = $_POST['subject'] ?? '';
    $desc = $_POST['desc'] ?? '';
    $category = $_POST['category'] ?? 'OTHER';
    $order_id = !empty($_POST['order_id']) ? intval($_POST['order_id']) : null;

    if ($subject && $desc) {
        $res = $conn->query("SELECT MAX(Tcket_Id) as max_id FROM support_ticket");
        $id = ($res->fetch_assoc()['max_id'] ?? 0) + 1;
        
        $stmt = $conn->prepare("INSERT INTO support_ticket (Tcket_Id, Cust_Id, Order_Id, Tcket_Subject, Tcket_Desc, Tcket_Category, Tcket_Status, Tcket_CreatedAt) VALUES (?, ?, ?, ?, ?, ?, 'OPEN', NOW())");
        $stmt->bind_param("iiisss", $id, $cust_id, $order_id, $subject, $desc, $category);
        if ($stmt->execute()) {
            $msg = "Your support ticket #$id has been submitted successfully!";
        } else {
            $msg = "Error: " . $conn->error;
        }
    }
}

// Fetch open tickets
$tickets = $conn->query("SELECT * FROM support_ticket WHERE Cust_Id = $cust_id ORDER BY Tcket_CreatedAt DESC");
// Fetch orders for dropdown
$orders = $conn->query("SELECT Order_Id, Order_PlacedAt FROM orders WHERE Cust_Id = $cust_id ORDER BY Order_PlacedAt DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Zalora Support Hub</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/global.css">
    <style>
        body { background: #f9f9f9; }
        .support-container { max-width: 800px; margin: 50px auto; padding: 0 20px; }
        .card { background: #fff; padding: 30px; border: 1px solid #eee; margin-bottom: 30px; }
        .section-title { font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 25px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 11px; font-weight: 700; color: #999; margin-bottom: 8px; text-transform: uppercase; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px; border: 1px solid #ddd; font-family: inherit; font-size: 13px; box-sizing: border-box; }
        .btn-submit { background: #000; color: #fff; border: none; padding: 15px 30px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; cursor: pointer; }
        
        .ticket-item { padding: 20px; border: 1px solid #eee; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; }
        .status-badge { padding: 4px 10px; font-size: 10px; font-weight: 700; text-transform: uppercase; border-radius: 100px; }
        .status-OPEN { background: #e0f2fe; color: #0369a1; }
        .status-RESOLVED { background: #dcfce7; color: #15803d; }
        
        .nav-logo { font-size: 24px; font-weight: 700; text-align: center; display: block; margin: 30px 0; text-decoration: none; color: #000; letter-spacing: 0.2em; }
    </style>
</head>
<body>

<a href="../index.php" class="nav-logo">ZALORA</a>

<div class="support-container">
    <h1 style="font-weight: 700; margin-bottom: 10px;">HELP CENTER</h1>
    <p style="color: #666; margin-bottom: 30px;">How can we help you today?</p>

    <?php if ($msg): ?>
        <div style="background: #e6fffa; color: #2c7a7b; padding: 15px; margin-bottom: 20px; font-size: 13px; border-left: 4px solid #38b2ac;"><?= $msg ?></div>
    <?php endif; ?>

    <div class="card">
        <h2 class="section-title">Open a New Ticket</h2>
        <form method="POST">
            <div class="form-group">
                <label>Subject</label>
                <input type="text" name="subject" placeholder="e.g. Missing Item in Order" required>
            </div>
            <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label>Category</label>
                    <select name="category">
                        <option value="ORDER_ISSUE">Order Issue</option>
                        <option value="RETURN">Return Inquiry</option>
                        <option value="PAYMENT">Payment Problem</option>
                        <option value="ACCOUNT">Account Support</option>
                        <option value="OTHER">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Related Order (Optional)</label>
                    <select name="order_id">
                        <option value="">None</option>
                        <?php while($o = $orders->fetch_assoc()): ?>
                            <option value="<?= $o['Order_Id'] ?>">Order #<?= $o['Order_Id'] ?> (<?= date('M d, Y', strtotime($o['Order_PlacedAt'])) ?>)</option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="desc" rows="5" placeholder="Tell us more about your issue..." required></textarea>
            </div>
            <button type="submit" name="submit_ticket" class="btn-submit">Submit Ticket</button>
        </form>
    </div>

    <div class="card">
        <h2 class="section-title">Your Tickets</h2>
        <?php if ($tickets->num_rows > 0): ?>
            <?php while($t = $tickets->fetch_assoc()): ?>
                <div class="ticket-item">
                    <div>
                        <p style="font-weight: 700; font-size: 14px; margin-bottom: 5px;"><?= htmlspecialchars($t['Tcket_Subject']) ?></p>
                        <p style="font-size: 11px; color: #999;">Ticket #<?= $t['Tcket_Id'] ?> • Opened on <?= date('M d, Y', strtotime($t['Tcket_CreatedAt'])) ?></p>
                    </div>
                    <span class="status-badge status-<?= $t['Tcket_Status'] ?>"><?= $t['Tcket_Status'] ?></span>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="color: #999; font-size: 13px;">You have no active support tickets.</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
