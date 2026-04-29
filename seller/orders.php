<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Center - Orders</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/seller.css">
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="sidebar-header">
        <h1>SELLER CENTER</h1>
        <p>GLOBAL FASHION LTD.</p>
    </div>
    
    <ul class="sidebar-nav">
        <li><a href="dashboard.php">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
            DASHBOARD
        </a></li>
        <li><a href="#">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
            INVENTORY
        </a></li>
        <li><a href="orders.php" class="active">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
            ORDERS
        </a></li>
        <li><a href="#">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
            ANALYTICS
        </a></li>
        <li><a href="profile.php">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            PROFILE
        </a></li>
        <li><a href="#">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
            SETTINGS
        </a></li>
    </ul>

    <div class="sidebar-footer">
        <a href="#" class="btn-add-product">ADD NEW PRODUCT</a>
    </div>
</aside>

<!-- MAIN WRAPPER -->
<div class="main-wrapper">
    <main class="main-content">
        
        <header class="page-header" style="align-items: center;">
            <div>
                <h2 class="page-title">ORDER MANAGEMENT</h2>
                <div class="tabs">
                    <button class="tab active">Pending (12)</button>
                    <button class="tab">Shipped (84)</button>
                    <button class="tab">Returned</button>
                    <button class="tab">Canceled</button>
                </div>
            </div>
            <div class="search-filter">
                <div class="search-box">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    <input type="text" placeholder="SEARCH ORDER ID...">
                </div>
                <button class="btn-filter">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
                    FILTERS
                </button>
            </div>
        </header>

        <div class="order-list">
            
            <!-- ORDER CARD 1 -->
            <div class="order-card">
                <img src="https://images.unsplash.com/photo-1529139574466-a303027c1d8b?w=400&q=80" alt="Coat" class="order-img">
                <div class="order-details">
                    <div class="order-header">
                        <div>
                            <p class="order-id">ORDER #ZA-98231-LX</p>
                            <h3 class="order-name">Oversized Merino Wool Blend Coat</h3>
                            <p class="order-meta">SIZE: L | COLOR: ANTHRACITE</p>
                        </div>
                        <div class="order-price">
                            <p class="order-total">$349.00</p>
                            <p class="order-qty">QTY: 01</p>
                        </div>
                    </div>
                    <div class="order-footer">
                        <div class="order-info-grid">
                            <div class="info-block">
                                <label>CUSTOMER</label>
                                <span>Julianne Moore</span>
                            </div>
                            <div class="info-block">
                                <label>ORDER DATE</label>
                                <span>OCT 24, 2023</span>
                            </div>
                            <div class="info-block">
                                <label>STATUS</label>
                                <span><i class="status-dot orange"></i><span class="status-text">PENDING PICKUP</span></span>
                            </div>
                        </div>
                        <div class="order-actions">
                            <button class="btn-secondary">VIEW DETAILS</button>
                            <button class="btn-dark">FULFILL</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ORDER CARD 2 -->
            <div class="order-card">
                <img src="https://images.unsplash.com/photo-1614252339474-13837ebdbb5a?w=400&q=80" alt="Shoes" class="order-img">
                <div class="order-details">
                    <div class="order-header">
                        <div>
                            <p class="order-id">ORDER #ZA-98240-LX</p>
                            <h3 class="order-name">Sculptural Leather Slingback Heels</h3>
                            <p class="order-meta">SIZE: 38 | COLOR: NOIR</p>
                        </div>
                        <div class="order-price">
                            <p class="order-total">$520.00</p>
                            <p class="order-qty">QTY: 01</p>
                        </div>
                    </div>
                    <div class="order-footer">
                        <div class="order-info-grid">
                            <div class="info-block">
                                <label>CUSTOMER</label>
                                <span>Alexander McQueen</span>
                            </div>
                            <div class="info-block">
                                <label>ORDER DATE</label>
                                <span>OCT 24, 2023</span>
                            </div>
                            <div class="info-block">
                                <label>STATUS</label>
                                <span><i class="status-dot red"></i><span class="status-text">PAYMENT FAILED</span></span>
                            </div>
                        </div>
                        <div class="order-actions">
                            <button class="btn-secondary">CONTACT CUSTOMER</button>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="section-divider">
            <span>RECENTLY SHIPPED</span>
        </div>

        <div class="order-list">
            
            <!-- ORDER CARD 3 -->
            <div class="order-card">
                <img src="https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=400&q=80" alt="Shirt" class="order-img">
                <div class="order-details">
                    <div class="order-header">
                        <div>
                            <p class="order-id">ORDER #ZA-97102-LX</p>
                            <h3 class="order-name">Minimalist Poplin Shirt</h3>
                            <p class="order-meta">SIZE: M | COLOR: OPTIC WHITE</p>
                        </div>
                        <div class="order-price">
                            <p class="order-total">$120.00</p>
                        </div>
                    </div>
                    <div class="order-footer">
                        <div class="order-info-grid">
                            <div class="info-block">
                                <label>CUSTOMER</label>
                                <span>Satoshi Kon</span>
                            </div>
                            <div class="info-block">
                                <label>SHIPPED DATE</label>
                                <span>OCT 22, 2023</span>
                            </div>
                            <div class="info-block">
                                <label>STATUS</label>
                                <span><i class="status-dot green"></i><span class="status-text">IN TRANSIT</span></span>
                            </div>
                        </div>
                        <div class="order-actions">
                            <button class="btn-secondary">TRACK PACKAGE</button>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- PAGINATION -->
        <div class="pagination">
            <span class="page-info">SHOWING 1-10 OF 112 ORDERS</span>
            <div class="page-controls">
                <button class="page-btn">&lsaquo;</button>
                <button class="page-btn active">1</button>
                <button class="page-btn">2</button>
                <button class="page-btn">3</button>
                <button class="page-btn">&rsaquo;</button>
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
            <a href="#">SIZE GUIDE</a>
            <a href="#">RETURNS & REFUNDS</a>
            <a href="#">CONTACT US</a>
            <a href="#">TERMS & CONDITIONS</a>
        </div>
    </footer>
</div>

</body>
</html>
