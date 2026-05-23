<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}
require_once '../config/db.php';

$msg = "";

// Handle Add Driver
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_driver') {
    $fname   = trim($_POST['firstname'] ?? '');
    $lname   = trim($_POST['lastname'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $pass    = $_POST['password'] ?? '';
    $vehicle = $_POST['vehicle'] ?? 'Motorcycle';
    $license = trim($_POST['license'] ?? '');

    if ($fname && $lname && $email && $pass) {
        $hash   = password_hash($pass, PASSWORD_DEFAULT);
        $newDrv = $database->getReference('driver')->push();
        $newDrv->set([
            'Driv_Id'          => $newDrv->getKey(),
            'Driv_FirstName'   => $fname,
            'Driv_LastName'    => $lname,
            'Driv_Email'       => $email,
            'Driv_PsswdHash'   => $hash,
            'Driv_VehicleType' => $vehicle,
            'Driv_LicenseNo'   => $license,
            'Driv_Status'      => 'OFFLINE',
            'Driv_IsActive'    => 1,
            'Driv_Balance'     => 0,
            'Driv_CreatedAt'   => date('Y-m-d H:i:s')
        ]);
        $msg = "Driver account for '$fname $lname' created successfully!";
    }
}

// Handle Delete Driver (Soft Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_driver') {
    $driv_id = $_POST['driv_id'] ?? null;
    if ($driv_id) {
        $ref = $database->getReference('driver')->orderByChild('Driv_Id')->equalTo($driv_id)->getSnapshot()->getValue();
        if ($ref) {
            $key = key($ref);
            $database->getReference('driver')->getChild($key)->update(['Driv_IsActive' => 0]);
            $msg = "Driver account has been successfully deactivated.";
        }
    }
}

// Query active drivers
$all_drivers_raw = $database->getReference('driver')->getSnapshot()->getValue() ?: [];
$drivers = array_filter($all_drivers_raw, fn($d) => ($d['Driv_IsActive'] ?? 0) == 1);
usort($drivers, fn($a, $b) => strtotime($b['Driv_CreatedAt'] ?? 0) - strtotime($a['Driv_CreatedAt'] ?? 0));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Management - Zalora Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/admin-drivers.css?v=<?= time() ?>">
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <header class="header">
        <h1 class="page-title">Driver Management</h1>
    </header>

    <?php if($msg): ?>
        <p style="background: #000; color: #fff; padding: 12px 20px; font-size: 12px; font-weight: 600; margin-bottom: 20px;"><?= htmlspecialchars($msg) ?></p>
    <?php endif; ?>

    <div class="card" style="margin-bottom: 2rem;">
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

    <div class="card" style="padding: 0;">
        <table>
            <thead>
                <tr>
                    <th>Driver ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Vehicle</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th style="text-align: right; padding-right: 2rem;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($drivers) > 0): ?>
                    <?php foreach($drivers as $d): ?>
                    <tr>
                        <td>#<?= $d['Driv_Id'] ?></td>
                        <td style="font-weight: 600;"><?= htmlspecialchars(($d['Driv_FirstName'] ?? '') . ' ' . ($d['Driv_LastName'] ?? '')) ?></td>
                        <td><?= htmlspecialchars($d['Driv_Email']) ?></td>
                        <td><?= htmlspecialchars($d['Driv_VehicleType']) ?></td>
                        <td><span class="badge" style="background: <?= ($d['Driv_IsActive'] ?? 0) ? '#dcfce7' : '#f1f5f9' ?>; color: <?= ($d['Driv_IsActive'] ?? 0) ? '#166534' : '#64748b' ?>;"><?= ($d['Driv_IsActive'] ?? 0) ? 'ACTIVE' : 'OFFLINE' ?></span></td>
                        <td><?= date('M d, Y', strtotime($d['Driv_CreatedAt'] ?? 'now')) ?></td>
                        <td style="text-align: right; padding-right: 2rem;">
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete/deactivate this driver?');">
                                <input type="hidden" name="action" value="delete_driver">
                                <input type="hidden" name="driv_id" value="<?= $d['Driv_Id'] ?>">
                                <button type="submit" class="btn-delete">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" style="text-align:center; padding: 30px; color:#999; font-style:italic;">No active drivers registered.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
