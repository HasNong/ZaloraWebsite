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
    $app_id = $_POST['app_id'] ?? 0;
    $action = $_POST['action'];

    if ($app_id) {
        // Fetch the application
        // Fetch the application manually since we don't have an index on App_Id
        $all_apps_for_search = $database->getReference('role_application')->getSnapshot()->getValue() ?: [];
        $appRef = [];
        foreach ($all_apps_for_search as $key => $app_data) {
            if (isset($app_data['App_Id']) && intval($app_data['App_Id']) === intval($app_id)) {
                $appRef[$key] = $app_data;
                break;
            }
        }
        if ($appRef) {
            $app_key = key($appRef);
            $app = current($appRef);
            $cust_id = $app['Cust_Id'];
            $app_type = $app['App_Type'];
            $details = json_decode($app['App_Details'], true);

            // Fetch Customer Info (try both int and string since Firebase equalTo is type-strict)
            $custRef = $database->getReference('customer')->orderByChild('Cust_Id')->equalTo(intval($cust_id))->getSnapshot()->getValue();
            if (!$custRef) {
                $custRef = $database->getReference('customer')->orderByChild('Cust_Id')->equalTo(strval($cust_id))->getSnapshot()->getValue();
            }
            if ($custRef) {
                $cust = current($custRef);
                if ($action === 'approve') {
                    try {
                        if ($app_type === 'Seller') {
                            $business_name = $details['business_name'] ?? ($cust['Cust_Firstname'] . ' ' . $cust['Cust_Lastname'] . "'s Store");
                            $store_email = $details['business_email'] ?? $cust['Cust_Email'];
                            $pass_hash = $cust['Cust_PsswdHash'];

                            // Check if seller already exists with this email
                            $all_sellers = array_merge(
                                $database->getReference('seller')->getSnapshot()->getValue() ?: [],
                                $database->getReference('seller')->getSnapshot()->getValue() ?: []
                            );
                            foreach ($all_sellers as $s) {
                                if (($s['Sell_Email'] ?? '') === $store_email) {
                                    throw new Exception("A seller with email '$store_email' already exists.");
                                }
                            }

                            // Insert into SELLER
                            $newSell = $database->getReference('seller')->push();
                            $newSell->set([
                                'Sell_Id'           => $newSell->getKey(),
                                'Sell_BusinessName' => $business_name,
                                'Sell_Email'        => $store_email,
                                'Sell_PsswdHash'    => $pass_hash,
                                'Sell_IsVerified'   => 1,
                                'Sell_IsActive'     => 1,
                                'Sell_JoinedAt'     => date('Y-m-d H:i:s')
                            ]);

                        } else if ($app_type === 'Driver') {
                            $license_no = $details['license_no'] ?? '';
                            $vehicle_type = $details['vehicle_type'] ?? '';
                            $phone = $details['phone'] ?? '';
                            $email = $cust['Cust_Email'];
                            $first_name = $cust['Cust_Firstname'];
                            $last_name = $cust['Cust_Lastname'];
                            $pass_hash = $cust['Cust_PsswdHash'];

                            // Check if driver already exists with this email
                            $all_drivers = array_merge(
                                $database->getReference('driver')->getSnapshot()->getValue() ?: [],
                                $database->getReference('driver')->getSnapshot()->getValue() ?: []
                            );
                            foreach ($all_drivers as $d) {
                                if (($d['Driv_Email'] ?? '') === $email) {
                                    throw new Exception("A driver with email '$email' already exists.");
                                }
                            }

                            // Insert into driver
                            $newDrv = $database->getReference('driver')->push();
                            $newDrv->set([
                                'Driv_Id'          => $newDrv->getKey(),
                                'Driv_FirstName'   => $first_name,
                                'Driv_LastName'    => $last_name,
                                'Driv_Email'       => $email,
                                'Driv_PsswdHash'   => $pass_hash,
                                'Driv_Phone'       => $phone,
                                'Driv_VehicleType' => $vehicle_type,
                                'Driv_LicenseNo'   => $license_no,
                                'Driv_Status'      => 'OFFLINE',
                                'Driv_IsActive'    => 1,
                                'Driv_Balance'     => 0,
                                'Driv_CreatedAt'   => date('Y-m-d H:i:s')
                            ]);
                        }

                        // Update Application Status to Approved
                        $database->getReference('role_application')->getChild($app_key)->update(['App_Status' => 'Approved']);

                        $msg = "Application #$app_id approved successfully! The user is now active as a $app_type.";
                        $msg_type = "success";

                    } catch (Exception $e) {
                        $msg = "Error approving application: " . $e->getMessage();
                        $msg_type = "error";
                    }

                } else if ($action === 'reject') {
                    // Update Application Status to Rejected
                    $database->getReference('role_application')->getChild($app_key)->update(['App_Status' => 'Rejected']);
                    $msg = "Application #$app_id has been rejected.";
                    $msg_type = "success";
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

// Fetch Applications
$all_apps = $database->getReference('role_application')->getSnapshot()->getValue() ?: [];
$all_customers = array_merge(
    $database->getReference('customer')->getSnapshot()->getValue() ?: [],
    $database->getReference('customer')->getSnapshot()->getValue() ?: []
);

$cust_map = [];
foreach ($all_customers as $c) {
    if (isset($c['Cust_Id'])) $cust_map[$c['Cust_Id']] = $c;
}

$pending_apps = [];
$past_apps = [];
foreach ($all_apps as $a) {
    $cust = $cust_map[$a['Cust_Id'] ?? ''] ?? [];
    $a['Cust_Firstname'] = $cust['Cust_Firstname'] ?? 'Unknown';
    $a['Cust_Lastname'] = $cust['Cust_Lastname'] ?? '';
    $a['Cust_Email'] = $cust['Cust_Email'] ?? '';

    if (($a['App_Status'] ?? '') === 'Pending') {
        $pending_apps[] = $a;
    } else {
        $past_apps[] = $a;
    }
}
usort($pending_apps, fn($a, $b) => strtotime($b['Created_At'] ?? 0) - strtotime($a['Created_At'] ?? 0));
usort($past_apps, fn($a, $b) => strtotime($b['Created_At'] ?? 0) - strtotime($a['Created_At'] ?? 0));
$past_apps = array_slice($past_apps, 0, 20);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Role Applications - Zalora Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/admin-applications.css?v=<?= time() ?>">
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
        
        <?php if (count($pending_apps) > 0): ?>
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
                    <?php foreach($pending_apps as $a): 
                        $details_data = is_array($a['App_Details']) ? $a['App_Details'] : json_decode($a['App_Details'], true);
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
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="font-size: 13px; color: #666; font-style: italic;">No pending applications at this time.</p>
        <?php endif; ?>
    </div>

    <!-- PAST APPLICATIONS HISTORY -->
    <div class="card">
        <h3 style="font-size: 13px; text-transform: uppercase; margin-top: 0; margin-bottom: 1.5rem; letter-spacing: 0.05em;">Past Applications History (Last 20)</h3>
        
        <?php if (count($past_apps) > 0): ?>
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
                    <?php foreach($past_apps as $a): 
                        $details_data = is_array($a['App_Details']) ? $a['App_Details'] : json_decode($a['App_Details'], true);
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
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="font-size: 13px; color: #666; font-style: italic;">No past history found.</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
