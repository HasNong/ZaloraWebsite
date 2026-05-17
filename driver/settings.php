<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'driver') {
    header("Location: ../auth/login.php");
    exit;
}

$driver_id = $_SESSION['user_id'];
$errors = [];
$success_msg = $_SESSION['success_msg'] ?? '';
unset($_SESSION['success_msg']);

// Handle update profile POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $license_no = trim($_POST['license_no'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $vehicle = trim($_POST['vehicle'] ?? '');
    $new_pass = $_POST['new_password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';

    if (empty($first_name) || empty($last_name) || empty($email) || empty($license_no)) {
        $errors[] = "First Name, Last Name, Email, and License Number are required.";
    }

    // Check if email already used by another driver
    if (empty($errors)) {
        $check_stmt = $conn->prepare("SELECT * FROM driver WHERE Driv_Email = ? AND Driv_Id != ?");
        $check_stmt->bind_param("si", $email, $driver_id);
        $check_stmt->execute();
        if ($check_stmt->get_result()->fetch_assoc()) {
            $errors[] = "Email is already in use by another driver.";
        }
    }

    if (empty($errors)) {
        if (!empty($new_pass)) {
            if ($new_pass !== $confirm_pass) {
                $errors[] = "Passwords do not match.";
            } else {
                $hash = password_hash($new_pass, PASSWORD_DEFAULT);
                $upd = $conn->prepare("UPDATE driver SET Driv_FirstName = ?, Driv_LastName = ?, Driv_Email = ?, Driv_LicenseNo = ?, Driv_Phone = ?, Driv_VehicleType = ?, Driv_PsswdHash = ? WHERE Driv_Id = ?");
                $upd->bind_param("sssssssi", $first_name, $last_name, $email, $license_no, $phone, $vehicle, $hash, $driver_id);
            }
        } else {
            $upd = $conn->prepare("UPDATE driver SET Driv_FirstName = ?, Driv_LastName = ?, Driv_Email = ?, Driv_LicenseNo = ?, Driv_Phone = ?, Driv_VehicleType = ? WHERE Driv_Id = ?");
            $upd->bind_param("ssssssi", $first_name, $last_name, $email, $license_no, $phone, $vehicle, $driver_id);
        }

        if (empty($errors)) {
            if ($upd->execute()) {
                $_SESSION['success_msg'] = "Profile credentials updated successfully!";
                header("Location: settings.php");
                exit;
            } else {
                $errors[] = "Database error: " . $conn->error;
            }
        }
    }
}

// Fetch Driver Info
$stmt = $conn->prepare("SELECT * FROM driver WHERE Driv_Id = ?");
$stmt->bind_param("i", $driver_id);
$stmt->execute();
$driver = $stmt->get_result()->fetch_assoc();

if (!$driver) {
    die("Driver profile not found. Please contact support.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Zalora Driver — Settings</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/seller.css">
    <style>
        .settings-container { max-width: 800px; }
        .settings-card { background: #fff; border: 1px solid #eee; padding: 40px; margin-bottom: 30px; }
        .settings-title {
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 2rem;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            color: #111;
        }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .form-group { display: flex; flex-direction: column; gap: 8px; }
        .form-group label { font-size: 10px; font-weight: 800; color: #999; text-transform: uppercase; letter-spacing: 0.1em; }
        .form-group input, .form-group select { width: 100%; padding: 12px 15px; border: 1px solid #eee; font-family: inherit; font-size: 13px; outline: none; border-radius: 4px; }
        .form-group input:focus, .form-group select:focus { border-color: #000; }
        
        .btn-save { background: #000; color: #fff; border: 1px solid #000; padding: 14px 30px; font-weight: 700; text-transform: uppercase; cursor: pointer; transition: 0.2s; font-size: 11px; letter-spacing: 0.1em; width: fit-content; border-radius: 4px; }
        .btn-save:hover { background: #fff; color: #000; }

        .alert { padding: 15px 20px; border-radius: 4px; font-size: 13px; margin-bottom: 30px; font-weight: 600; }
        .alert-success { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
        .alert-error { background: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-wrapper">
    <main class="main-content">
        
        <header class="section-header">
            <h1 class="page-title">PROFILE SETTINGS</h1>
            <p class="page-subtitle" style="font-size: 12px; color: #888; margin-top: 5px;">Update your basic credentials, license, and password security.</p>
        </header>

        <div class="settings-container">
            <?php if (!empty($success_msg)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success_msg) ?></div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error): ?>
                        <p style="margin-bottom: 5px;">• <?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="settings-card">
                <form method="POST">
                    <input type="hidden" name="update_profile" value="1">
                    
                    <h3 class="settings-title">Basic Information</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">First Name *</label>
                            <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($driver['Driv_FirstName']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last Name *</label>
                            <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($driver['Driv_LastName']) ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Email Address *</label>
                            <input type="email" id="email" name="email" value="<?= htmlspecialchars($driver['Driv_Email']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Contact Phone</label>
                            <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($driver['Driv_Phone'] ?? '') ?>" placeholder="e.g. 0917XXXXXXX">
                        </div>
                    </div>

                    <h3 class="settings-title" style="margin-top: 35px;">Vehicle & Licensing</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="license_no">Driver License # *</label>
                            <input type="text" id="license_no" name="license_no" value="<?= htmlspecialchars($driver['Driv_LicenseNo']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="vehicle">Vehicle Type</label>
                            <select id="vehicle" name="vehicle">
                                <option value="Motorcycle" <?= ($driver['Driv_VehicleType'] === 'Motorcycle') ? 'selected' : '' ?>>Motorcycle</option>
                                <option value="Car" <?= ($driver['Driv_VehicleType'] === 'Car') ? 'selected' : '' ?>>Car</option>
                                <option value="Bicycle" <?= ($driver['Driv_VehicleType'] === 'Bicycle') ? 'selected' : '' ?>>Bicycle</option>
                                <option value="Van/Truck" <?= ($driver['Driv_VehicleType'] === 'Van/Truck') ? 'selected' : '' ?>>Van / Truck</option>
                            </select>
                        </div>
                    </div>

                    <h3 class="settings-title" style="margin-top: 35px;">Security & Password (Optional)</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" placeholder="Leave blank to keep current">
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Leave blank to keep current">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-save" style="margin-top: 10px;">SAVE CHANGES</button>
                </form>
            </div>
        </div>

    </main>

    <footer class="seller-footer">
        <div class="footer-logo">ZALORA</div>
        <div class="footer-links">
            <a href="#">HELP & SUPPORT</a>
            <a href="#">PRIVACY POLICY</a>
        </div>
    </footer>
</div>

</body>
</html>
