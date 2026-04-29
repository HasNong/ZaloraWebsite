<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $_SESSION['error'] = "Email and password are required.";
        header("Location: login.php?tab=login");
        exit;
    }

    $stmt = $conn->prepare("SELECT Cust_Id, Cust_Firstname, Cust_Lastname, Cust_PsswdHash FROM CUSTOMER WHERE Cust_Email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['Cust_PsswdHash'])) {
            // Login successful
            $_SESSION['user_id'] = $row['Cust_Id'];
            $_SESSION['user_name'] = trim($row['Cust_Firstname'] . ' ' . $row['Cust_Lastname']);
            $_SESSION['user_email'] = $email;
            
            header("Location: ../index.php");
            exit;
        } else {
            $_SESSION['error'] = "Invalid email or password.";
            header("Location: login.php?tab=login");
            exit;
        }
    } else {
        $_SESSION['error'] = "Invalid email or password.";
        header("Location: login.php?tab=login");
        exit;
    }
} else {
    header("Location: login.php?tab=login");
}
?>
