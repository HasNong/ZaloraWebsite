<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Center - Profile</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/seller.css">
    <style>
        .profile-container {
            max-width: 800px;
        }
        .profile-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            padding: 3rem;
            margin-bottom: 2rem;
            display: flex;
            gap: 3rem;
            align-items: flex-start;
        }
        .profile-avatar {
            width: 120px;
            height: 120px;
            background: #f1f5f9;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: var(--text-muted);
            flex-shrink: 0;
        }
        .profile-details {
            flex-grow: 1;
        }
        .profile-name {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .profile-badge {
            display: inline-block;
            background: #dcfce7;
            color: var(--accent-green);
            padding: 4px 10px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 2rem;
            border-radius: 100px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        .info-group label {
            display: block;
            font-size: 10px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 6px;
        }
        .info-group p {
            font-size: 14px;
            color: var(--text-main);
        }
        
        .danger-zone {
            border: 1px solid var(--accent-red);
            padding: 2rem;
            background: #fef2f2;
        }
        .danger-title {
            color: var(--accent-red);
            font-weight: 600;
            font-size: 12px;
            margin-bottom: 1rem;
            text-transform: uppercase;
        }
        .btn-danger {
            background: var(--accent-red);
            color: white;
            border: none;
            padding: 12px 24px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        .btn-danger:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<!-- MAIN WRAPPER -->
<div class="main-wrapper">
    <main class="main-content">
        
        <header class="page-header">
            <div>
                <h2 class="page-title">SELLER PROFILE</h2>
                <p class="page-subtitle">Manage your store's public identity and account settings.</p>
            </div>
            <div class="header-actions">
                <button class="btn-export">EDIT PROFILE</button>
            </div>
        </header>

        <div class="profile-container">
            
            <div class="profile-card">
                <div class="profile-avatar">
                    GF
                </div>
                <div class="profile-details">
                    <h3 class="profile-name">Global Fashion Ltd.</h3>
                    <div class="profile-badge">
                        <svg viewBox="0 0 24 24" width="10" height="10" fill="none" stroke="currentColor" stroke-width="3" style="display:inline;margin-right:2px;vertical-align:middle;"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        VERIFIED SELLER
                    </div>
                    
                    <div class="info-grid">
                        <div class="info-group">
                            <label>BUSINESS EMAIL</label>
                            <p>contact@globalfashion.com</p>
                        </div>
                        <div class="info-group">
                            <label>PHONE NUMBER</label>
                            <p>+1 (555) 123-4567</p>
                        </div>
                        <div class="info-group">
                            <label>SELLER ID</label>
                            <p>#SLR-99824</p>
                        </div>
                        <div class="info-group">
                            <label>JOINED DATE</label>
                            <p>March 15, 2023</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="danger-zone">
                <h4 class="danger-title">Account Access</h4>
                <p style="margin-bottom: 1.5rem; color: var(--text-muted);">Securely log out of your seller session to protect your business data.</p>
                <!-- Links to the same logout script used by customers -->
                <button class="btn-danger" onclick="window.location.href='../auth/logout.php'">SIGN OUT OF SELLER CENTER</button>
            </div>

        </div>

    </main>

    <!-- FOOTER -->
    <footer class="seller-footer">
        <div>
            <div class="footer-logo">ZALORA</div>
            <div class="footer-copy">© 2024 ZALORA ALL RIGHTS RESERVED</div>
        </div>
        <div class="footer-links">
            <a href="#">HELP & SUPPORT</a>
            <a href="#">TERMS & CONDITIONS</a>
            <a href="#">CONTACT US</a>
        </div>
    </footer>
</div>

</body>
</html>
