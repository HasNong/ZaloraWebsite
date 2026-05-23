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

    // Helper function to query Firebase
    function findUserByEmail($db, $table, $emailField, $email) {
        $snapshot = $db->getReference($table)->orderByChild($emailField)->equalTo($email)->getSnapshot();
        if ($snapshot->hasChildren()) {
            $users = $snapshot->getValue();
            $key = key($users);
            $user = reset($users);
            $user['_firebase_key'] = $key; // Inject the key into the user array
            return $user;
        }
        return null;
    }

    // ── 2. CUSTOMER CHECK ──
    $customer = findUserByEmail($database, 'customer', 'Cust_Email', $email);
    if ($customer && (isset($customer['Cust_IsActive']) ? $customer['Cust_IsActive'] == 1 : true)) {
        if (password_verify($password, $customer['Cust_PsswdHash'])) {
            $_SESSION['user_id'] = $customer['Cust_Id'] ?? $customer['id'] ?? $customer['_firebase_key'];
            $_SESSION['user_name'] = trim(($customer['Cust_Firstname'] ?? '') . ' ' . ($customer['Cust_Lastname'] ?? ''));
            $_SESSION['user_email'] = $email;
            $_SESSION['role'] = 'customer';
            
            header("Location: ../index.php");
            exit;
        }
    }

    // ── 3. SELLER CHECK ──
    $seller = findUserByEmail($database, 'seller', 'Sell_Email', $email);
    if ($seller && (isset($seller['Sell_IsActive']) ? $seller['Sell_IsActive'] == 1 : true)) {
        if (password_verify($password, $seller['Sell_PsswdHash'])) {
            $_SESSION['user_id'] = $seller['Sell_Id'] ?? $seller['id'] ?? $seller['_firebase_key'];
            $_SESSION['user_name'] = $seller['Sell_BusinessName'] ?? '';
            $_SESSION['user_email'] = $email;
            $_SESSION['role'] = 'seller';
            
            header("Location: ../seller/dashboard.php");
            exit;
        }
    }

    // ── 4. DRIVER CHECK ──
    $driver = findUserByEmail($database, 'driver', 'Driv_Email', $email);
    if ($driver && (isset($driver['Driv_IsActive']) ? $driver['Driv_IsActive'] == 1 : true)) {
        if (password_verify($password, $driver['Driv_PsswdHash'])) {
            $_SESSION['user_id'] = $driver['Driv_Id'] ?? $driver['id'] ?? $driver['_firebase_key'];
            $_SESSION['user_name'] = trim(($driver['Driv_FirstName'] ?? '') . ' ' . ($driver['Driv_LastName'] ?? ''));
            $_SESSION['user_email'] = $email;
            $_SESSION['role'] = 'driver';
            
            header("Location: ../driver/dashboard.php");
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
