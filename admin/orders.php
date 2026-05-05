<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management - Zalora Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="main-content">
    <header class="header">
        <h1 class="page-title">Order Management</h1>
    </header>
    <div class="card">
        <p>View all orders and handle escalations.</p>
    </div>
</div>
</body>
</html>
