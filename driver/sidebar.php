<?php
$current_page = basename($_SERVER['PHP_SELF']);
$driver_id = $_SESSION['user_id'];
$res_status = $conn->query("SELECT Driv_IsActive FROM driver WHERE Driv_Id = $driver_id");
$is_online = $res_status->fetch_assoc()['Driv_IsActive'] ?? 0;
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <h1>DRIVER CENTER</h1>
        <p><?= htmlspecialchars($_SESSION['user_name'] ?? 'DELIVERY PARTNER') ?></p>
        <span style="font-size:9px; font-weight:800; color: <?= $is_online ? '#22c55e' : '#999' ?>;">● <?= $is_online ? 'ONLINE' : 'OFFLINE' ?></span>
    </div>
    
    <ul class="sidebar-nav">
        <li><a href="dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
            DASHBOARD
        </a></li>
        <li><a href="queue.php" class="<?= $current_page == 'queue.php' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
            MY QUEUE
        </a></li>
        <li><a href="history.php" class="<?= $current_page == 'history.php' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
            HISTORY
        </a></li>
        <li><a href="payouts.php" class="<?= $current_page == 'payouts.php' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
            PAYOUTS
        </a></li>
        <li><a href="settings.php" class="<?= $current_page == 'settings.php' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
            SETTINGS
        </a></li>
    </ul>

    <div class="sidebar-footer">
        <button class="btn-go-online" onclick="toggleOnline()" style="background: <?= $is_online ? '#ef4444' : '#000' ?>;">
            <?= $is_online ? 'GO OFFLINE' : 'GO ONLINE' ?>
        </button>
        <a href="../auth/logout.php" style="display: block; text-align: center; margin-top: 20px; font-size: 10px; font-weight: 800; color: #ef4444; text-decoration: none; text-transform: uppercase; letter-spacing: 0.1em;">SIGN OUT</a>
    </div>
</aside>

<script>
function toggleOnline() {
    const formData = new FormData();
    formData.append('action', 'toggle_status');
    
    fetch('status_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        location.reload(); // Quick refresh to update all UI elements
    });
}
</script>
