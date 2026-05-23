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
        $otherDrivers = $database->getReference('driver')->orderByChild('Driv_Email')->equalTo($email)->getSnapshot()->getValue();
        if ($otherDrivers) {
            foreach ($otherDrivers as $o_drv) {
                if ($o_drv['Driv_Id'] != $driver_id) {
                    $errors[] = "Email is already in use by another driver.";
                    break;
                }
            }
        }
    }

    if (empty($errors)) {
        $driverRef = $database->getReference('driver')->orderByChild('Driv_Id')->equalTo($driver_id)->getSnapshot()->getValue();
        if ($driverRef) {
            $key = key($driverRef);
            $node = 'driver';
            
            $updateData = [
                'Driv_FirstName' => $first_name,
                'Driv_LastName' => $last_name,
                'Driv_Email' => $email,
                'Driv_LicenseNo' => $license_no,
                'Driv_Phone' => $phone,
                'Driv_VehicleType' => $vehicle
            ];

            if (!empty($new_pass)) {
                if ($new_pass !== $confirm_pass) {
                    $errors[] = "Passwords do not match.";
                } else {
                    $updateData['Driv_PsswdHash'] = password_hash($new_pass, PASSWORD_DEFAULT);
                }
            }
            
            if (empty($errors)) {
                try {
                    $database->getReference($node)->getChild($key)->update($updateData);
                    $_SESSION['success_msg'] = "Profile credentials updated successfully!";
                    header("Location: settings.php");
                    exit;
                } catch (Exception $e) {
                    $errors[] = "Database error: " . $e->getMessage();
                }
            }
        } else {
            $errors[] = "Driver record not found to update.";
        }
    }
}

// Fetch Driver Info
$drivers = $database->getReference('driver')->orderByChild('Driv_Id')->equalTo($driver_id)->getSnapshot()->getValue();
$driver = $drivers ? current($drivers) : null;

if (!$driver) {
    die("Driver profile not found. Please contact support.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Zalora Driver — Settings</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/seller.css">
    <link rel="stylesheet" href="../assets/css/driver.css?v=<?= time() ?>">
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
