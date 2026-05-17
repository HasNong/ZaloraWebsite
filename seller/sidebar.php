<aside class="sidebar">
    <div class="sidebar-header">
        <h1>SELLER CENTER</h1>
        <p><?= htmlspecialchars($_SESSION['user_name'] ?? 'GLOBAL FASHION LTD.') ?></p>
    </div>
    
    <ul class="sidebar-nav">
        <li><a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
            DASHBOARD
        </a></li>
        <li><a href="inventory.php" class="<?= basename($_SERVER['PHP_SELF']) == 'inventory.php' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
            INVENTORY
        </a></li>
        <li><a href="orders.php" class="<?= basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
            ORDERS
        </a></li>
        <li><a href="promotions.php" class="<?= basename($_SERVER['PHP_SELF']) == 'promotions.php' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
            PROMOTIONS
        </a></li>
        <li><a href="profile.php" class="<?= basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            PROFILE
        </a></li>
    </ul>

    <div class="sidebar-footer">
        <a href="../auth/logout.php" class="btn-add-product" style="background: #e74c3c; border: 1px solid #e74c3c; color: #fff; text-align: center; font-weight: 700; border-radius: 4px;">LOG OUT</a>
    </div>
</aside>
