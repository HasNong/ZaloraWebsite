<aside class="sidebar">
    <div class="sidebar-logo">ZALORA ADMIN</div>
    <ul class="nav-list">
        <li><a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">Dashboard</a></li>
        <li><a href="users.php" class="<?= basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : '' ?>">Seller Management</a></li>
        <li><a href="drivers.php" class="<?= basename($_SERVER['PHP_SELF']) == 'drivers.php' ? 'active' : '' ?>">Driver Management</a></li>
        <li><a href="catalog.php" class="<?= basename($_SERVER['PHP_SELF']) == 'catalog.php' ? 'active' : '' ?>">Catalog Management</a></li>
        <li><a href="orders.php" class="<?= basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : '' ?>">Order Management</a></li>
        <li><a href="promotions.php" class="<?= basename($_SERVER['PHP_SELF']) == 'promotions.php' ? 'active' : '' ?>">Promotions</a></li>
        <li><a href="support.php" class="<?= basename($_SERVER['PHP_SELF']) == 'support.php' ? 'active' : '' ?>">Support Tickets</a></li>
        <li><a href="returns.php" class="<?= basename($_SERVER['PHP_SELF']) == 'returns.php' ? 'active' : '' ?>">Return Management</a></li>
        <li><a href="reports.php" class="<?= basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : '' ?>">Reports</a></li>
        <li><a href="logout.php" style="color: #e74c3c; margin-top: 2rem; display: block;">Logout</a></li>
    </ul>
</aside>
