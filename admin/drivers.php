<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}
require_once '../config/db.php';

$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_driver') {
    $fname = $_POST['firstname'] ?? '';
    $lname = $_POST['lastname'] ?? '';
    $email = $_POST['email'] ?? '';
    $pass = $_POST['password'] ?? '';
    $vehicle = $_POST['vehicle'] ?? 'Motorcycle';
    $license = $_POST['license'] ?? '';
    
    if ($fname && $lname && $email && $pass) {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        
        // Manual ID generation
        $max_res = $conn->query("SELECT MAX(Driv_Id) as max_id FROM driver");
        $driv_id = ($max_res->fetch_assoc()['max_id'] ?? 0) + 1;
        
        $stmt = $conn->prepare("INSERT INTO driver (Driv_Id, Driv_FirstName, Driv_LastName, Driv_Email, Driv_PsswdHash, Driv_VehicleType, Driv_LicenseNo, Driv_Status, Driv_IsActive, Driv_CreatedAt) VALUES (?, ?, ?, ?, ?, ?, ?, 'OFFLINE', 1, NOW())");
        $stmt->bind_param("issssss", $driv_id, $fname, $lname, $email, $hash, $vehicle, $license);
        
        if ($stmt->execute()) {
            $msg = "Driver account for '$fname $lname' created successfully!";
        } else {
            $msg = "Error: " . $conn->error;
        }
    }
}

$drivers = $conn->query("SELECT * FROM driver ORDER BY Driv_CreatedAt DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Management - Zalora Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .form-row { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; }
        .form-group { margin-bottom: 1rem; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <header class="header">
        <h1 class="page-title">Driver Management</h1>
    </header>

    <?php if($msg): ?>
        <p style="background: #000; color: #fff; padding: 10px; font-size: 12px; font-weight: 600;"><?= $msg ?></p>
    <?php endif; ?>

    <div class="card">
        <h3 style="font-size: 12px; text-transform: uppercase; margin-top: 0; margin-bottom: 1.5rem;">Onboard New Driver</h3>
        <form method="POST">
            <input type="hidden" name="action" value="add_driver">
            <div class="form-row">
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="firstname" required placeholder="First Name">
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="lastname" required placeholder="Last Name">
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required placeholder="driver@zalora.com">
                </div>
                <div class="form-group">
                    <label>Temporary Password</label>
                    <input type="password" name="password" required placeholder="••••••••">
                </div>
                <div class="form-group">
                    <label>Vehicle Type</label>
                    <select name="vehicle" required style="width:100%; padding: 8px; border: 1px solid #ddd; font-family: inherit;">
                        <option value="Motorcycle">Motorcycle</option>
                        <option value="Van">Van</option>
                        <option value="Truck">Truck</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>License Number</label>
                    <input type="text" name="license" placeholder="ABC-123-XYZ">
                </div>
            </div>
            <button type="submit" class="btn-primary btn-small" style="margin-top: 1rem; width: 150px;">Create Account</button>
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th>Driver ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Vehicle</th>
                <th>Status</th>
                <th>Joined</th>
            </tr>
        </thead>
        <tbody>
            <?php while($d = $drivers->fetch_assoc()): ?>
            <tr>
                <td>#<?= $d['Driv_Id'] ?></td>
                <td style="font-weight: 600;"><?= htmlspecialchars(($d['Driv_FirstName'] ?? '') . ' ' . ($d['Driv_LastName'] ?? '')) ?></td>
                <td><?= htmlspecialchars($d['Driv_Email']) ?></td>
                <td><?= htmlspecialchars($d['Driv_VehicleType']) ?></td>
                <td><span class="badge" style="background: <?= $d['Driv_Status'] === 'ONLINE' ? '#dcfce7' : '#f1f5f9' ?>; color: <?= $d['Driv_Status'] === 'ONLINE' ? '#166534' : '#64748b' ?>;"><?= $d['Driv_Status'] ?></span></td>
                <td><?= date('M d, Y', strtotime($d['Driv_CreatedAt'])) ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

</body>
</html>
