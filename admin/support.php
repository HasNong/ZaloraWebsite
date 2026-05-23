<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}
require_once '../config/db.php';

if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'resolve') {
    $ticket_id = intval($_GET['id']);
    $tickets = $database->getReference('support_ticket')->orderByChild('Tcket_Id')->equalTo($ticket_id)->getSnapshot()->getValue();
    if ($tickets) {
        $key = key($tickets);
        $database->getReference('support_ticket')->getChild($key)->update([
            'Tcket_Status' => 'RESOLVED',
            'Tcket_ResolvedAt' => date('Y-m-d H:i:s'),
        ]);
    }
    header("Location: support.php");
    exit;
}

$all_tickets = $database->getReference('support_ticket')->getSnapshot()->getValue() ?: [];
$all_customers = fb_merge_nodes($database, 'customer');
$customers_by_id = [];
foreach ($all_customers as $c) {
    if (isset($c['Cust_Id'])) {
        $customers_by_id[$c['Cust_Id']] = $c;
    }
}

$tickets = [];
foreach ($all_tickets as $t) {
    if (!is_array($t)) {
        continue;
    }
    $cust = $customers_by_id[$t['Cust_Id'] ?? ''] ?? [];
    $t['customer_name'] = trim(($cust['Cust_Firstname'] ?? '') . ' ' . ($cust['Cust_Lastname'] ?? '')) ?: 'Unknown';
    $tickets[] = $t;
}
usort($tickets, fn($a, $b) => strtotime($b['Tcket_CreatedAt'] ?? 0) - strtotime($a['Tcket_CreatedAt'] ?? 0));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Tickets - Zalora Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="main-content">
    <header class="header">
        <h1 class="page-title">Support Tickets</h1>
    </header>
    <div class="card">
        <?php if (count($tickets) > 0): ?>
        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr>
                    <th>Ticket</th>
                    <th>Customer</th>
                    <th>Subject</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tickets as $t): ?>
                <tr>
                    <td>#<?= htmlspecialchars($t['Tcket_Id'] ?? '') ?></td>
                    <td><?= htmlspecialchars($t['customer_name']) ?></td>
                    <td><?= htmlspecialchars($t['Tcket_Subject'] ?? '') ?></td>
                    <td><?= htmlspecialchars($t['Tcket_Category'] ?? '') ?></td>
                    <td><?= htmlspecialchars($t['Tcket_Status'] ?? 'OPEN') ?></td>
                    <td style="text-align:right;">
                        <?php if (($t['Tcket_Status'] ?? '') !== 'RESOLVED'): ?>
                        <a href="support.php?action=resolve&id=<?= urlencode($t['Tcket_Id'] ?? '') ?>">Mark Resolved</a>
                        <?php else: ?>
                        <span style="color:#999;">Resolved</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p>No support tickets yet.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
