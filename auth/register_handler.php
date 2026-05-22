<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $firstname = trim($_POST['firstname'] ?? '');
    $lastname = trim($_POST['lastname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Basic validation
    if (empty($firstname) || empty($lastname) || empty($email) || empty($password)) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: login.php?tab=register");
        exit;
    }

    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match.";
        header("Location: login.php?tab=register");
        exit;
    }

    $customersRef = $database->getReference('CUSTOMER');

    // Check if email exists
    $existing = $customersRef->orderByChild('Cust_Email')->equalTo($email)->getSnapshot();
    if ($existing->hasChildren()) {
        $_SESSION['error'] = "Email is already registered.";
        header("Location: login.php?tab=register");
        exit;
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $created_at = date('Y-m-d H:i:s');
    
    $newCustomer = [
        'Cust_Firstname' => $firstname,
        'Cust_Lastname' => $lastname,
        'Cust_Email' => $email,
        'Cust_PsswdHash' => $hashed_password,
        'Cust_CreatedAt' => $created_at,
        'Cust_UpdatedAt' => $created_at,
        'Cust_IsActive' => 1
    ];
    
    try {
        $customersRef->push($newCustomer);
        $_SESSION['success'] = "Registration successful. Please log in.";
        header("Location: login.php?tab=login");
    } catch (\Exception $e) {
        $_SESSION['error'] = "Something went wrong. Please try again.";
        header("Location: login.php?tab=register");
    }
} else {
    header("Location: login.php?tab=register");
}
?>
