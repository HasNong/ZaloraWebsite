<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}
require_once '../config/db.php';

$msg = "";
$msg_type = "";

// Handle Approve/Reject Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $app_id = intval($_POST['app_id'] ?? 0);
    $action = $_POST['action'];

    if ($app_id > 0) {
        // Fetch the application
        $app_stmt = $conn->prepare("SELECT * FROM ROLE_APPLICATION WHERE App_Id = ?");
        $app_stmt->bind_param("i", $app_id);
        $app_stmt->execute();
        $app = $app_stmt->get_result()->fetch_assoc();

        if ($app) {
            $cust_id = $app['Cust_Id'];
            $app_type = $app['App_Type'];
            $details = json_decode($app['App_Details'], true);

            // Fetch Customer Info
            $cust_stmt = $conn->prepare("SELECT * FROM CUSTOMER WHERE Cust_Id = ?");
            $cust_stmt->bind_param("i", $cust_id);
            $cust_stmt->execute();
            $cust = $cust_stmt->get_result()->fetch_assoc();

            if ($cust) {
                if ($action === 'approve') {
                    $conn->begin_transaction();
                    try {
                        if ($app_type === 'Seller') {
                            // Find Max Seller ID (Manual Generation)
                            $max_res = $conn->query("SELECT MAX(Sell_Id) as max_id FROM SELLER");
                            $sell_id = ($max_res->fetch_assoc()['max_id'] ?? 0) + 1;
                            
                            $business_name = $details['business_name'] ?? ($cust['Cust_Firstname'] . ' ' . $cust['Cust_Lastname'] . "'s Store");
                            $store_email = $details['business_email'] ?? $cust['Cust_Email'];
                            $pass_hash = $cust['Cust_PsswdHash'];

                            // Check if seller already exists with this email
                            $email_check = $conn->prepare("SELECT * FROM SELLER WHERE Sell_Email = ?");
                            $email_check->bind_param("s", $store_email);
                            $email_check->execute();
                            if ($email_check->get_result()->fetch_assoc()) {
                                throw new Exception("A seller with email '$store_email' already exists.");
                            }

                            // Insert into SELLER
                            $ins_stmt = $conn->prepare("INSERT INTO SELLER (Sell_Id, Sell_BusinessName, Sell_Email, Sell_PsswdHash, Sell_IsVerified, Sell_JoinedAt, Sell_IsActive) VALUES (?, ?, ?, ?, 1, NOW(), 1)");
                            $ins_stmt->bind_param("isss", $sell_id, $business_name, $store_email, $pass_hash);
                            if (!$ins_stmt->execute()) {
                                throw new Exception("Failed to insert Seller record: " . $conn->error);
                            }

                        } else if ($app_type === 'Driver') {
                            $license_no = $details['license_no'] ?? '';
                            $vehicle_type = $details['vehicle_type'] ?? '';
                            $phone = $details['phone'] ?? '';
                            $email = $cust['Cust_Email'];
                            $first_name = $cust['Cust_Firstname'];
                            $last_name = $cust['Cust_Lastname'];
                            $pass_hash = $cust['Cust_PsswdHash'];

                            // Check if driver already exists with this email
                            $email_check = $conn->prepare("SELECT * FROM driver WHERE Driv_Email = ?");
                            $email_check->bind_param("s", $email);
                            $email_check->execute();
                            if ($email_check->get_result()->fetch_assoc()) {
                                throw new Exception("A driver with email '$email' already exists.");
                            }

                            // Insert into driver
                            $ins_stmt = $conn->prepare("INSERT INTO driver (Driv_FirstName, Driv_LastName, Driv_Email, Driv_PsswdHash, Driv_Phone, Driv_VehicleType, Driv_LicenseNo, Driv_Status, Driv_IsActive, Driv_CreatedAt) VALUES (?, ?, ?, ?, ?, ?, ?, 'OFFLINE', 1, NOW())");
                            $ins_stmt->bind_param("sssssss", $first_name, $last_name, $email, $pass_hash, $phone, $vehicle_type, $license_no);
                            if (!$ins_stmt->execute()) {
                                throw new Exception("Failed to insert Driver record: " . $conn->error);
                            }
                        }

                        // Update Application Status to Approved
                        $upd_stmt = $conn->prepare("UPDATE ROLE_APPLICATION SET App_Status = 'Approved' WHERE App_Id = ?");
                        $upd_stmt->bind_param("i", $app_id);
                        $upd_stmt->execute();

                        $conn->commit();
                        $msg = "Application #$app_id approved successfully! The user is now active as a $app_type.";
                        $msg_type = "success";

                    } catch (Exception $e) {
                        $conn->rollback();
                        $msg = "Error approving application: " . $e->getMessage();
                        $msg_type = "error";
                    }

                } else if ($action === 'reject') {
                    // Update Application Status to Rejected
                    $upd_stmt = $conn->prepare("UPDATE ROLE_APPLICATION SET App_Status = 'Rejected' WHERE App_Id = ?");
                    $upd_stmt->bind_param("i", $app_id);
                    if ($upd_stmt->execute()) {
                        $msg = "Application #$app_id has been rejected.";
                        $msg_type = "success";
                    } else {
                        $msg = "Error rejecting application: " . $conn->error;
                        $msg_type = "error";
                    }
                }
            } else {
                $msg = "Customer associated with this application not found.";
                $msg_type = "error";
            }
        } else {
            $msg = "Application not found.";
            $msg_type = "error";
        }
    }
}

// Fetch Pending Applications
$pending_apps = $conn->query("SELECT a.*, c.Cust_Firstname, c.Cust_Lastname, c.Cust_Email FROM ROLE_APPLICATION a JOIN CUSTOMER c ON a.Cust_Id = c.Cust_Id WHERE a.App_Status = 'Pending' ORDER BY a.Created_At DESC");

// Fetch Past Applications (Approved/Rejected)
$past_apps = $conn->query("SELECT a.*, c.Cust_Firstname, c.Cust_Lastname, c.Cust_Email FROM ROLE_APPLICATION a JOIN CUSTOMER c ON a.Cust_Id = c.Cust_Id WHERE a.App_Status != 'Pending' ORDER BY a.Created_At DESC LIMIT 20");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Role Applications - Zalora Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .app-details-box {
            font-size: 11px;
            background: #fdfdfd;
            border: 1px dashed #ccc;
            border-radius: 6px;
            padding: 8px 12px;
            margin-top: 4px;
            line-height: 1.5;
        }
        .btn-action {
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            transition: 0.2s;
        }
        .btn-approve {
            background: #2ecc71;
            color: #fff;
        }
        .btn-approve:hover { background: #27ae60; }
        .btn-reject {
            background: #e74c3c;
            color: #fff;
        }
        .btn-reject:hover { background: #c0391b; }
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-pending { background: #f1c40f; color: #fff; }
        .badge-approved { background: #2ecc71; color: #fff; }
        .badge-rejected { background: #e74c3c; color: #fff; }

        .alert-box {
            padding: 12px 20px;
            border-radius: 6px;
            font-size: 13px;
            margin-bottom: 25px;
            font-weight: 600;
        }
        .alert-success { background: #eafaf1; color: #2b8a73; border: 1px solid #d1f2e1; }
        .alert-error { background: #fdf2f2; color: #9b1c1c; border: 1px solid #fde8e8; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <header class="header">
        <h1 class="page-title">Role Applications Management</h1>
    </header>

    <?php if(!empty($msg)): ?>
        <div class="alert-box alert-<?= $msg_type === 'success' ? 'success' : 'error' ?>">
            <?= htmlspecialchars($msg) ?>
        </div>
    <?php endif; ?>

    <!-- PENDING APPLICATIONS -->
    <div class="card" style="margin-bottom: 2rem;">
        <h3 style="font-size: 13px; text-transform: uppercase; margin-top: 0; margin-bottom: 1.5rem; letter-spacing: 0.05em;">Pending Applications</h3>
        
        <?php if ($pending_apps && $pending_apps->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Applicant</th>
                        <th>Role Type</th>
                        <th>Application Details</th>
                        <th>Submitted Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($a = $pending_apps->fetch_assoc()): 
                        $details_data = json_decode($a['App_Details'], true);
                    ?>
                    <tr>
                        <td>#<?= $a['App_Id'] ?></td>
                        <td>
                            <strong style="font-size:13px;"><?= htmlspecialchars($a['Cust_Firstname'] . ' ' . $a['Cust_Lastname']) ?></strong><br>
                            <span style="font-size:11px; color:#666;"><?= htmlspecialchars($a['Cust_Email']) ?></span>
                        </td>
                        <td>
                            <span style="font-weight: 700; color: <?= $a['App_Type'] === 'Driver' ? '#2980b9' : '#c0392b' ?>;">
                                <?= htmlspecialchars($a['App_Type']) ?>
                            </span>
                        </td>
                        <td>
                            <div class="app-details-box">
                                <?php if ($a['App_Type'] === 'Seller'): ?>
                                    <strong>Business:</strong> <?= htmlspecialchars($details_data['business_name'] ?? '') ?><br>
                                    <strong>Email:</strong> <?= htmlspecialchars($details_data['business_email'] ?? '') ?><br>
                                    <strong>Desc:</strong> <?= htmlspecialchars($details_data['business_desc'] ?? '') ?>
                                <?php else: ?>
                                    <strong>Vehicle:</strong> <?= htmlspecialchars($details_data['vehicle_type'] ?? '') ?><br>
                                    <strong>License:</strong> <?= htmlspecialchars($details_data['license_no'] ?? '') ?><br>
                                    <strong>Phone:</strong> <?= htmlspecialchars($details_data['phone'] ?? '') ?>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><?= date('M d, Y, g:i a', strtotime($a['Created_At'])) ?></td>
                        <td>
                            <div style="display:flex; gap:8px;">
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Approve this application?');">
                                    <input type="hidden" name="app_id" value="<?= $a['App_Id'] ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="btn-action btn-approve">Approve</button>
                                </form>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Reject this application?');">
                                    <input type="hidden" name="app_id" value="<?= $a['App_Id'] ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="btn-action btn-reject">Reject</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="font-size: 13px; color: #666; font-style: italic;">No pending applications at this time.</p>
        <?php endif; ?>
    </div>

    <!-- PAST APPLICATIONS HISTORY -->
    <div class="card">
        <h3 style="font-size: 13px; text-transform: uppercase; margin-top: 0; margin-bottom: 1.5rem; letter-spacing: 0.05em;">Past Applications History (Last 20)</h3>
        
        <?php if ($past_apps && $past_apps->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Applicant</th>
                        <th>Role Type</th>
                        <th>Application Details</th>
                        <th>Submitted Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($a = $past_apps->fetch_assoc()): 
                        $details_data = json_decode($a['App_Details'], true);
                    ?>
                    <tr>
                        <td>#<?= $a['App_Id'] ?></td>
                        <td>
                            <strong><?= htmlspecialchars($a['Cust_Firstname'] . ' ' . $a['Cust_Lastname']) ?></strong><br>
                            <span style="font-size:11px; color:#666;"><?= htmlspecialchars($a['Cust_Email']) ?></span>
                        </td>
                        <td><?= htmlspecialchars($a['App_Type']) ?></td>
                        <td>
                            <div class="app-details-box">
                                <?php if ($a['App_Type'] === 'Seller'): ?>
                                    <strong>Business:</strong> <?= htmlspecialchars($details_data['business_name'] ?? '') ?><br>
                                    <strong>Email:</strong> <?= htmlspecialchars($details_data['business_email'] ?? '') ?>
                                <?php else: ?>
                                    <strong>Vehicle:</strong> <?= htmlspecialchars($details_data['vehicle_type'] ?? '') ?><br>
                                    <strong>License:</strong> <?= htmlspecialchars($details_data['license_no'] ?? '') ?>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><?= date('M d, Y', strtotime($a['Created_At'])) ?></td>
                        <td>
                            <span class="badge badge-<?= strtolower($a['App_Status']) ?>">
                                <?= htmlspecialchars($a['App_Status']) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="font-size: 13px; color: #666; font-style: italic;">No past history found.</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
