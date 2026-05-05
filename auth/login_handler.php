<?php
session_start();
require_once '../config/db.php';

// Hardcoded Admin Credentials
$admin_email = "admin@zalora.com";
$admin_pass = "admin123";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $_SESSION['error'] = "Email and password are required.";
        header("Location: login.php?tab=login");
        exit;
    }

    // ── 1. ADMIN CHECK ──
    if ($email === $admin_email && $password === $admin_pass) {
        $_SESSION['user_id'] = 0; // Admin ID
        $_SESSION['user_name'] = "System Administrator";
        $_SESSION['user_email'] = $email;
        $_SESSION['role'] = 'admin';
        $_SESSION['admin_logged_in'] = true;
        
        header("Location: ../admin/dashboard.php");
        exit;
    }

    // ── 2. CUSTOMER CHECK ──
    $stmt = $conn->prepare("SELECT Cust_Id, Cust_Firstname, Cust_Lastname, Cust_PsswdHash FROM CUSTOMER WHERE Cust_Email = ? AND Cust_IsActive = 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['Cust_PsswdHash'])) {
            $_SESSION['user_id'] = $row['Cust_Id'];
            $_SESSION['user_name'] = trim($row['Cust_Firstname'] . ' ' . $row['Cust_Lastname']);
            $_SESSION['user_email'] = $email;
            $_SESSION['role'] = 'customer';
            
            header("Location: ../index.php");
            exit;
        }
    }

    // ── 3. SELLER CHECK ──
    $stmt = $conn->prepare("SELECT Sell_Id, Sell_BusinessName, Sell_PsswdHash FROM SELLER WHERE Sell_Email = ? AND Sell_IsActive = 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['Sell_PsswdHash'])) {
            $_SESSION['user_id'] = $row['Sell_Id'];
            $_SESSION['user_name'] = $row['Sell_BusinessName'];
            $_SESSION['user_email'] = $email;
            $_SESSION['role'] = 'seller';
            
            header("Location: ../seller/dashboard.php");
            exit;
        }
    }

    // If none of the above
    $_SESSION['error'] = "Invalid email or password.";
    header("Location: login.php?tab=login");
    exit;

} else {
    header("Location: login.php?tab=login");
}
?>
