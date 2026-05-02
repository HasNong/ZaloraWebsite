<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Center - Dashboard</title>
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
        <li><a href="dashboard.php" class="active">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
            DASHBOARD
        </a></li>
        <li><a href="inventory.php">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
            INVENTORY
        </a></li>
        <li><a href="orders.php">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
            ORDERS
        </a></li>
        <li><a href="profile.php">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            PROFILE
        </a></li>
    </ul>

    <div class="sidebar-footer">
        <a href="add_product.php" class="btn-add-product">ADD NEW PRODUCT</a>
    </div>
</aside>

<!-- MAIN WRAPPER -->
<div class="main-wrapper">
    <main class="main-content">
        
        <!-- HEADER -->
        <header class="page-header">
            <div>
                <h2 class="page-title">DASHBOARD OVERVIEW</h2>
                <p class="page-subtitle">Welcome back, Global Fashion Team. Here's your performance for the last 24 hours.</p>
            </div>
            <div class="header-actions">
                <button class="btn-export">EXPORT DATA</button>
                <button class="btn-icon">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                </button>
            </div>
        </header>

        <!-- METRICS -->
        <div class="metrics-grid">
            <div class="metric-card">
                <p class="metric-title">TOTAL REVENUE</p>
                <p class="metric-value">$128,430.00</p>
                <p class="metric-sub trend-up">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg>
                    +12.5% vs Last Week
                </p>
            </div>
            <div class="metric-card">
                <p class="metric-title">ACTIVE ORDERS</p>
                <p class="metric-value">1,248</p>
                <p class="metric-sub">84 Pending Fulfillment</p>
            </div>
            <div class="metric-card">
                <p class="metric-title">CONVERSION RATE</p>
                <p class="metric-value">4.82%</p>
                <p class="metric-sub trend-down">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"></polyline><polyline points="17 18 23 18 23 12"></polyline></svg>
                    -0.4% vs Yesterday
                </p>
            </div>
            <div class="metric-card">
                <p class="metric-title">TOTAL VISITORS</p>
                <p class="metric-value">42.5K</p>
                <p class="metric-sub">Peak: 2:00 PM EST</p>
            </div>
        </div>

        <!-- CHARTS & FEED -->
        <div class="dashboard-grid">
            
            <!-- CHART CARD -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title">SALES PERFORMANCE</span>
                    <div class="toggle-group">
                        <button class="toggle-btn active">WEEKLY</button>
                        <button class="toggle-btn">MONTHLY</button>
                    </div>
                </div>
                
                <div class="bar-chart-container">
                    <div class="bar-wrapper"><div class="bar" style="height: 30%"></div></div>
                    <div class="bar-wrapper"><div class="bar" style="height: 45%"></div></div>
                    <div class="bar-wrapper"><div class="bar" style="height: 60%"></div></div>
                    <div class="bar-wrapper">
                        <div class="bar-label">THU</div>
                        <div class="bar active" style="height: 100%"></div>
                    </div>
                    <div class="bar-wrapper"><div class="bar" style="height: 65%"></div></div>
                    <div class="bar-wrapper"><div class="bar" style="height: 40%"></div></div>
                    <div class="bar-wrapper"><div class="bar" style="height: 35%"></div></div>
                </div>

                <div class="chart-footer">
                    <div style="display:flex;gap:3rem;">
                        <div class="chart-stat">
                            <span class="chart-stat-label">AVG ORDER VALUE</span>
                            <span class="chart-stat-val">$102.50</span>
                        </div>
                        <div class="chart-stat">
                            <span class="chart-stat-label">TOTAL ITEMS SOLD</span>
                            <span class="chart-stat-val">1,842</span>
                        </div>
                    </div>
                    <a href="#" class="link-muted">VIEW FULL ANALYTICS</a>
                </div>
            </div>

            <!-- ACTIVITY FEED -->
            <div class="card" style="padding-bottom: 0;">
                <div class="card-header" style="margin-bottom: 1rem;">
                    <span class="card-title">RECENT ACTIVITY</span>
                </div>
                
                <div class="activity-feed">
                    <div class="activity-item">
                        <div class="activity-icon img">
                            <img src="https://images.unsplash.com/photo-1496747611176-843222e1e57c?w=100&q=80" alt="Silk Dress">
                        </div>
                        <div class="activity-content">
                            <p><strong>Order #92843</strong> placed for "Minimal Silk Slip Dress"</p>
                            <span class="activity-time">2 MINUTES AGO</span>
                        </div>
                    </div>
                    
                    <div class="activity-item">
                        <div class="activity-icon dark">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>
                        </div>
                        <div class="activity-content">
                            <p><strong>Inventory Alert:</strong> "Oversized Blazer" is low on stock (2 units left)</p>
                            <span class="activity-time">15 MINUTES AGO</span>
                        </div>
                    </div>
                    
                    <div class="activity-item">
                        <div class="activity-icon img">
                            <img src="https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=100&q=80" alt="Coat">
                        </div>
                        <div class="activity-content">
                            <p><strong>Product Live:</strong> "Winter Collection '24" Wool Coat is now public</p>
                            <span class="activity-time">1 HOUR AGO</span>
                        </div>
                    </div>

                    <div class="activity-item">
                        <div class="activity-icon">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="var(--text-main)" stroke="var(--text-main)" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                        </div>
                        <div class="activity-content">
                            <p><strong>New Review:</strong> 5-stars received on "Tailored Trousers"</p>
                            <span class="activity-time">3 HOURS AGO</span>
                        </div>
                    </div>
                </div>
                
                <a href="#" class="view-all-btn">VIEW ALL NOTIFICATIONS</a>
            </div>

        </div>

        <!-- BOTTOM GRID -->
        <div class="bottom-grid">
            
            <!-- TOP SELLING TABLE -->
            <div>
                <h2 class="page-title" style="margin-bottom: 1.5rem;">TOP SELLING PRODUCTS</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>PRODUCT</th>
                            <th>STATUS</th>
                            <th>SALES</th>
                            <th>REVENUE</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <div class="product-cell">
                                    <img src="https://images.unsplash.com/photo-1551028719-00167b16eac5?w=100&q=80" alt="Jacket" class="product-img">
                                    <span class="product-name">Cropped Vegan Leather Jacket</span>
                                </div>
                            </td>
                            <td><span class="badge in-stock">IN STOCK</span></td>
                            <td class="sales-val">482</td>
                            <td class="revenue-val">$43,380</td>
                        </tr>
                        <tr>
                            <td>
                                <div class="product-cell">
                                    <img src="https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=100&q=80" alt="Sneakers" class="product-img">
                                    <span class="product-name">Classic White Sneakers</span>
                                </div>
                            </td>
                            <td><span class="badge low-stock">LOW STOCK</span></td>
                            <td class="sales-val">312</td>
                            <td class="revenue-val">$24,960</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- QUICK ACTIONS -->
            <div class="card dark-card">
                <span class="card-title" style="display:block;margin-bottom:8px;">QUICK ACTIONS</span>
                <p>Frequent tasks at your fingertips.</p>
                
                <button class="action-btn">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    RUN A PROMOTION
                </button>
                <button class="action-btn">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                    HELP CENTER
                </button>
                <button class="action-btn">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                    LIVE CHAT SUPPORT
                </button>

                <div class="store-health">
                    <div class="health-label">STORE HEALTH</div>
                    <div class="health-val">98% <span class="health-status">EXCELLENT</span></div>
                </div>
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
