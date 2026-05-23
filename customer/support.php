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
    $subject = trim($_POST['subject'] ?? '');
    $desc = trim($_POST['desc'] ?? '');
    $category = $_POST['category'] ?? 'OTHER';
    $order_id = !empty($_POST['order_id']) ? $_POST['order_id'] : null;

    if ($subject && $desc) {
        try {
            $ticket_id = fb_next_id($database, 'support_ticket', 'Tcket_Id');
            $ticketRef = $database->getReference('support_ticket')->push();
            $ticketRef->set([
                'Tcket_Id' => $ticket_id,
                'Cust_Id' => $cust_id,
                'Order_Id' => $order_id,
                'Tcket_Subject' => $subject,
                'Tcket_Desc' => $desc,
                'Tcket_Category' => $category,
                'Tcket_Status' => 'OPEN',
                'Tcket_CreatedAt' => date('Y-m-d H:i:s'),
            ]);
            $msg = "Your support ticket #$ticket_id has been submitted successfully!";
        } catch (Exception $e) {
            $msg = "Error: " . $e->getMessage();
        }
    }
}

$tickets = fb_filter_by_child($database, 'support_ticket', 'Cust_Id', $cust_id);
usort($tickets, fn($a, $b) => strtotime($b['Tcket_CreatedAt'] ?? 0) - strtotime($a['Tcket_CreatedAt'] ?? 0));

$orders = array_merge(
    fb_filter_by_child($database, 'orders', 'Cust_Id', $cust_id)
);
usort($orders, fn($a, $b) => strtotime($b['Order_PlacedAt'] ?? 0) - strtotime($a['Order_PlacedAt'] ?? 0));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Zalora Support Hub</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/support.css?v=<?= time() ?>">
</head>
<body>

<a href="../index.php" class="nav-logo">ZALORA</a>

<div class="support-container">
    <h1 style="font-weight: 700; margin-bottom: 10px;">HELP CENTER</h1>
    <p style="color: #666; margin-bottom: 30px;">How can we help you today?</p>

    <?php if ($msg): ?>
        <div style="background: #e6fffa; color: #2c7a7b; padding: 15px; margin-bottom: 20px; font-size: 13px; border-left: 4px solid #38b2ac;"><?= htmlspecialchars($msg) ?></div>
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
                        <?php foreach ($orders as $o): ?>
                            <option value="<?= htmlspecialchars($o['Order_Id'] ?? '') ?>">
                                Order #<?= htmlspecialchars($o['Order_Id'] ?? '') ?>
                                (<?= date('M d, Y', strtotime($o['Order_PlacedAt'] ?? 'now')) ?>)
                            </option>
                        <?php endforeach; ?>
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
        <?php if (count($tickets) > 0): ?>
            <?php foreach ($tickets as $t): ?>
                <div class="ticket-item">
                    <div>
                        <p style="font-weight: 700; font-size: 14px; margin-bottom: 5px;"><?= htmlspecialchars($t['Tcket_Subject'] ?? '') ?></p>
                        <p style="font-size: 11px; color: #999;">
                            Ticket #<?= htmlspecialchars($t['Tcket_Id'] ?? '') ?>
                            • Opened on <?= date('M d, Y', strtotime($t['Tcket_CreatedAt'] ?? 'now')) ?>
                        </p>
                    </div>
                    <span class="status-badge status-<?= htmlspecialchars($t['Tcket_Status'] ?? 'OPEN') ?>">
                        <?= htmlspecialchars($t['Tcket_Status'] ?? 'OPEN') ?>
                    </span>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="color: #999; font-size: 13px;">You have no active support tickets.</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
