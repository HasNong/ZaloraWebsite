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

    // Check if email exists
    $stmt = $conn->prepare("SELECT Cust_Id FROM CUSTOMER WHERE Cust_Email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $_SESSION['error'] = "Email is already registered.";
        $stmt->close();
        header("Location: login.php?tab=register");
        exit;
    }
    $stmt->close();

    // Firstname and lastname are already set from POST

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $created_at = date('Y-m-d H:i:s');
    $updated_at = $created_at;

    // Manually generate the next ID since the database doesn't auto-increment it
    $res = $conn->query("SELECT MAX(Cust_Id) AS max_id FROM CUSTOMER");
    $row = $res->fetch_assoc();
    $new_id = ($row['max_id'] ? $row['max_id'] : 0) + 1;

    $stmt = $conn->prepare("INSERT INTO CUSTOMER (Cust_Id, Cust_Firstname, Cust_Lastname, Cust_Email, Cust_PsswdHash, Cust_CreatedAt, Cust_UpdatedAt) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssss", $new_id, $firstname, $lastname, $email, $hashed_password, $created_at, $updated_at);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Registration successful. Please log in.";
        header("Location: login.php?tab=login");
    } else {
        $_SESSION['error'] = "Something went wrong. Please try again.";
        header("Location: login.php?tab=register");
    }
    $stmt->close();
} else {
    header("Location: login.php?tab=register");
}
?>
