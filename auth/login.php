<?php
session_start();
require_once '../config/db.php';
include '../customer/nav_counts.php';

// Redirect if already logged in based on role
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') { header('Location: ../admin/dashboard.php'); exit; }
    if ($_SESSION['role'] === 'seller') { header('Location: ../seller/dashboard.php'); exit; }
    if ($_SESSION['role'] === 'customer') { header('Location: ../customer/profile.php'); exit; }
    if ($_SESSION['role'] === 'driver') { header('Location: ../driver/dashboard.php'); exit; }
} else if (isset($_SESSION['user_id'])) { 
    header('Location: ../customer/profile.php'); exit; 
}

$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error']);
unset($_SESSION['success']);

$tab = isset($_GET['tab']) && $_GET['tab'] === 'register' ? 'register' : 'login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZALORA | Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&family=Cormorant+Garamond:ital,wght@1,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/global.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../assets/css/login.css?v=<?= time() ?>">
</head>
<body>

<!-- --- TOP PROMO BAR --- -->
<div class="top-promo-bar">
    <div class="promo-container">
        <a href="#" class="promo-item">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
            30 Days Free Returns | T&C Apply >
        </a>
        <a href="#" class="promo-item">
            <span style="background: #000; color:#fff; padding: 2px 5px; margin-right:5px; border-radius:2px;">VIP</span>
            Become a ZALORA VIP today! >
        </a>
        <a href="#" class="promo-item">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg>
            Save more on the app! 25% OFF + P150 OFF >
        </a>
    </div>
</div>

<!-- --- HEADER --- -->
<header>
    <div class="main-header">
        <a href="../index.php" class="logo">ZALORA</a>
        
        <div class="search-bar-wrap">
            <form action="../customer/products.php" method="GET">
                <input type="text" name="q" class="search-input" placeholder="Got You Scoring More">
                <button type="submit" class="search-icon-btn">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                </button>
            </form>
        </div>

        <div class="header-actions">
            <a href="login.php" class="header-action-item">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <span>Login / Register</span>
            </a>
            <a href="../customer/wishlist.php" class="header-action-item" style="position:relative;">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
            </a>
            <a href="../customer/cart.php" class="header-action-item" style="position:relative;">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
            </a>
        </div>
    </div>

    <nav class="nav-bar">
        <div class="nav-container">
            <a href="../customer/products.php?gender=women" class="nav-item">WOMEN</a>
            <a href="../customer/products.php?gender=men" class="nav-item">MEN</a>
            <a href="../customer/products.php?category=kids" class="nav-item">KIDS</a>
            <a href="../customer/products.php?premium=1" class="nav-item">LUXURY</a>
            <a href="../customer/products.php?category=beauty" class="nav-item">BEAUTY</a>
            <a href="../customer/products.php?category=sports" class="nav-item">SPORTS</a>
        </div>
    </nav>
</header>

<main class="main-content">
    <div class="auth-container">
        
        <div class="tabs-header">
            <a href="login.php?tab=login" class="tab-link <?= $tab === 'login' ? 'active' : '' ?>">Login</a>
            <a href="login.php?tab=register" class="tab-link <?= $tab === 'register' ? 'active' : '' ?>">Sign up</a>
        </div>

        <div class="auth-card">
            <?php if ($error): ?>
                <div class="msg-banner msg-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="msg-banner msg-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <div class="social-login">
                <button class="social-btn">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="#1877F2"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.469h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.469h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                </button>
                <button class="social-btn">
                    <svg width="20" height="20" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                </button>
            </div>

            <div class="divider">
                <span>Or continue with:</span>
            </div>

            <?php if ($tab === 'login'): ?>
            <!-- LOGIN FORM -->
            <form method="POST" action="login_handler.php" id="authForm">
                <input type="hidden" name="action" value="login"/>
                
                <div class="form-group">
                    <input type="email" name="email" id="email" placeholder="Email Address *" required oninput="checkForm()">
                </div>
                
                <div class="form-group">
                    <input type="password" name="password" id="password" placeholder="Password *" required oninput="checkForm()">
                    <button type="button" class="pw-toggle" onclick="togglePw('password')">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
                
                <div class="form-options">
                    <label class="remember-me">
                        <input type="checkbox" name="remember" checked> Keep me signed in
                    </label>
                    <a href="forgot.php" class="forgot-link">Forgot Password?</a>
                </div>

                <button type="submit" class="btn-submit" id="submitBtn">Login</button>
            </form>
            <?php else: ?>
            <!-- REGISTER FORM (Simplified to match visual style) -->
            <form method="POST" action="register_handler.php" id="authForm">
                <input type="hidden" name="action" value="register"/>
                
                <div style="display:flex; gap:10px; margin-bottom:20px;">
                    <div class="form-group" style="margin-bottom:0; flex:1;">
                        <input type="text" name="firstname" placeholder="First Name *" required oninput="checkForm()">
                    </div>
                    <div class="form-group" style="margin-bottom:0; flex:1;">
                        <input type="text" name="lastname" placeholder="Last Name *" required oninput="checkForm()">
                    </div>
                </div>

                <div class="form-group">
                    <input type="email" name="email" id="email" placeholder="Email Address *" required oninput="checkForm()">
                </div>
                
                <div class="form-group">
                    <input type="password" name="password" id="password" placeholder="Password *" required oninput="checkForm()">
                    <button type="button" class="pw-toggle" onclick="togglePw('password')">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>

                <div class="form-group">
                    <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password *" required oninput="checkForm()">
                </div>
                
                <button type="submit" class="btn-submit" id="submitBtn">Sign up</button>
            </form>
            <?php endif; ?>

            <p class="terms-text">
                By creating your account or signing in, you agree to our<br>
                <a href="#">Terms and Conditions</a> & <a href="#">Privacy Policy</a>
            </p>
        </div>
    </div>

    <div class="simple-footer">
        <div class="footer-block">
            <h4>TOP BRANDS</h4>
            <ul class="footer-list">
                <li><a href="#">ALDO</a></li>
                <li><a href="#">Converse</a></li>
                <li><a href="#">PUMA</a></li>
                <li><a href="#">Birkenstock</a></li>
                <li><a href="#">Crocs</a></li>
                <li><a href="#">Casio</a></li>
                <li><a href="#">Lacoste</a></li>
                <li><a href="#">New Balance</a></li>
                <li><a href="#">GAP</a></li>
                <li><a href="#">NIKE</a></li>
                <li><a href="#">Ray-Ban</a></li>
                <li><a href="#">CLN</a></li>
                <li><a href="#">Superdry</a></li>
                <li><a href="#">ADIDAS</a></li>
                <li><a href="#">Mango</a></li>
            </ul>
        </div>
        <div class="footer-block">
            <h4>TOP SEARCHES</h4>
            <ul class="footer-list">
                <li><a href="#">Bags</a></li>
                <li><a href="#">Shoes</a></li>
                <li><a href="#">Casual Dresses</a></li>
                <li><a href="#">Clothes</a></li>
                <li><a href="#">Discount Prices</a></li>
                <li><a href="#">Corporate Attire</a></li>
                <li><a href="#">Sports</a></li>
                <li><a href="#">Accessories</a></li>
                <li><a href="#">Sneakers</a></li>
                <li><a href="#">New Products</a></li>
                <li><a href="#">Maxi Dress</a></li>
                <li><a href="#">Long Sleeve</a></li>
                <li><a href="#">Beauty</a></li>
                <li><a href="#">Jacket</a></li>
                <li><a href="#">Culottes</a></li>
            </ul>
        </div>
    </div>
</main>

<div class="floating-actions">
    <button class="float-btn-z">Z</button>
</div>

<script>
    function togglePw(id) {
        const input = document.getElementById(id);
        if (input.type === 'password') {
            input.type = 'text';
        } else {
            input.type = 'password';
        }
    }

    function checkForm() {
        const form = document.getElementById('authForm');
        const btn = document.getElementById('submitBtn');
        let allFilled = true;
        const inputs = form.querySelectorAll('input[required]');
        inputs.forEach(input => {
            if (!input.value.trim()) {
                allFilled = false;
            }
        });
        
        if (allFilled) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    }
</script>
</body>
</html>