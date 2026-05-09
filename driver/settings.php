<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'driver') {
    header("Location: ../auth/login.php");
    exit;
}

$driver_id = $_SESSION['user_id'];

// Fetch Driver Info
$stmt = $conn->prepare("SELECT * FROM driver WHERE Driv_Id = ?");
$stmt->bind_param("i", $driver_id);
$stmt->execute();
$driver = $stmt->get_result()->fetch_assoc();

if (isset($_POST['update_profile'])) {
    $phone = $_POST['phone'];
    $vehicle = $_POST['vehicle'];
    
    $upd = $conn->prepare("UPDATE driver SET Driv_Phone = ?, Driv_VehicleType = ? WHERE Driv_Id = ?");
    $upd->bind_param("ssi", $phone, $vehicle, $driver_id);
    if ($upd->execute()) {
        $_SESSION['success'] = "Profile updated successfully!";
        header("Location: settings.php");
        exit;
    }
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
        .settings-card { background: #fff; border: 1px solid #eee; padding: 40px; max-width: 600px; }
        .form-group { margin-bottom: 25px; }
        .form-group label { display: block; font-size: 10px; font-weight: 800; color: #999; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 10px; }
        .form-group input { width: 100%; padding: 15px; border: 1px solid #eee; font-family: inherit; font-size: 14px; }
        .form-group input:read-only { background: #fafafa; color: #999; cursor: not-allowed; }
        .btn-save { background: #000; color: #fff; border: none; padding: 18px 40px; font-weight: 700; text-transform: uppercase; cursor: pointer; margin-top: 20px; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-wrapper">
    <main class="main-content">
        
        <header class="section-header">
            <h1 class="page-title">PROFILE SETTINGS</h1>
        </header>

        <?php if (isset($_SESSION['success'])): ?>
            <div style="background: #f0fdf4; color: #16a34a; padding: 20px; border-left: 5px solid #16a34a; margin-bottom: 30px; font-size: 13px; font-weight: 600;">
                <?= $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <div class="settings-card">
            <form method="POST">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" value="<?= htmlspecialchars($driver['Driv_FirstName'] . ' ' . $driver['Driv_LastName']) ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="text" value="<?= htmlspecialchars($driver['Driv_Email']) ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Driver License #</label>
                    <input type="text" value="<?= htmlspecialchars($driver['Driv_LicenseNo']) ?>" readonly>
                </div>
                <hr style="margin: 40px 0; border: none; border-top: 1px solid #eee;">
                <div class="form-group">
                    <label>Contact Phone</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($driver['Driv_Phone']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Vehicle Type</label>
                    <input type="text" name="vehicle" value="<?= htmlspecialchars($driver['Driv_VehicleType']) ?>" required>
                </div>
                
                <button type="submit" name="update_profile" class="btn-save">SAVE CHANGES</button>
            </form>
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
