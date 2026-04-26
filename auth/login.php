<?php
session_start();

// Redirect if already logged in
// if (isset($_SESSION['user'])) { header('Location: ../index.php'); exit; }

$error = '';
$tab = isset($_GET['tab']) && $_GET['tab'] === 'register' ? 'register' : 'login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>ZALORA — <?= $tab === 'login' ? 'Sign In' : 'Register' ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;1,300&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --black: #0a0a0a;
            --white: #fafafa;
            --grey: #888;
            --light-grey: #e0e0e0;
            --font-display: 'Cormorant Garamond', serif;
            --font-body: 'Montserrat', sans-serif;
        }

        body {
            font-family: var(--font-body);
            background: var(--white);
            color: var(--black);
            height: 100vh;
            display: grid;
            grid-template-columns: 60% 40%;
            overflow: hidden;
        }

        /* ── LEFT PANEL ── */
        .left {
            position: relative;
            overflow: hidden;
        }

        .left img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center top;
            display: block;
            filter: grayscale(100%) brightness(0.75);
        }

        .left-logo {
            position: absolute;
            top: 2rem;
            left: 2rem;
            font-family: var(--font-body);
            font-weight: 700;
            font-size: 1.2rem;
            letter-spacing: 0.18em;
            color: var(--white);
            text-decoration: none;
        }

        .left-quote {
            position: absolute;
            bottom: 2.5rem;
            left: 2rem;
            right: 2rem;
            font-family: var(--font-display);
            font-style: italic;
            font-size: 1.05rem;
            color: rgba(255,255,255,0.75);
            letter-spacing: 0.04em;
            line-height: 1.5;
        }

        /* ── RIGHT PANEL ── */
        .right {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 3rem 3.5rem 2rem;
            overflow-y: auto;
            border-left: 1px solid var(--light-grey);
        }

        /* ── TABS ── */
        .tabs {
            display: flex;
            gap: 2.5rem;
            border-bottom: 1px solid var(--light-grey);
            margin-bottom: 2.5rem;
        }

        .tab-btn {
            background: none;
            border: none;
            font-family: var(--font-body);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            cursor: pointer;
            padding-bottom: 1rem;
            color: var(--grey);
            border-bottom: 2px solid transparent;
            margin-bottom: -1px;
            transition: color 0.2s, border-color 0.2s;
        }

        .tab-btn.active {
            color: var(--black);
            border-bottom-color: var(--black);
        }

        /* ── FORM AREA ── */
        .form-area { flex: 1; }

        .form-title {
            font-family: var(--font-body);
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.14em;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
        }

        .form-subtitle {
            font-size: 12px;
            color: var(--grey);
            line-height: 1.6;
            margin-bottom: 2.2rem;
        }

        .error-msg {
            background: #fdecea;
            color: #c0392b;
            font-size: 11px;
            padding: 10px 14px;
            border-left: 3px solid #c0392b;
            margin-bottom: 1.5rem;
            letter-spacing: 0.04em;
        }

        /* ── FIELDS ── */
        .field {
            margin-bottom: 1.6rem;
        }

        .field-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .field label {
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            color: var(--grey);
        }

        .forgot-link {
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: var(--black);
            text-decoration: none;
            border-bottom: 1px solid var(--black);
            padding-bottom: 1px;
            transition: color 0.2s;
        }

        .forgot-link:hover { color: var(--grey); border-color: var(--grey); }

        .input-wrap {
            position: relative;
            border-bottom: 1px solid var(--light-grey);
            transition: border-color 0.2s;
        }

        .input-wrap:focus-within { border-color: var(--black); }

        .input-wrap input {
            width: 100%;
            border: none;
            outline: none;
            background: transparent;
            font-family: var(--font-body);
            font-size: 12px;
            letter-spacing: 0.06em;
            padding: 10px 36px 10px 0;
            color: var(--black);
        }

        .input-wrap input::placeholder { color: #bbb; }

        .toggle-pw {
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--grey);
            padding: 4px;
            line-height: 1;
            transition: color 0.2s;
        }

        .toggle-pw:hover { color: var(--black); }

        /* ── REMEMBER ── */
        .remember {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 2rem;
            cursor: pointer;
        }

        .remember input[type="checkbox"] {
            width: 16px;
            height: 16px;
            border: 1px solid var(--black);
            appearance: none;
            cursor: pointer;
            position: relative;
            flex-shrink: 0;
        }

        .remember input[type="checkbox"]:checked::after {
            content: '';
            position: absolute;
            inset: 2px;
            background: var(--black);
        }

        .remember span {
            font-size: 9px;
            font-weight: 600;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--grey);
        }

        /* ── BUTTONS ── */
        .btn-signin {
            width: 100%;
            background: var(--black);
            color: var(--white);
            border: none;
            padding: 16px;
            font-family: var(--font-body);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.24em;
            text-transform: uppercase;
            cursor: pointer;
            margin-bottom: 1.8rem;
            transition: background 0.25s;
        }

        .btn-signin:hover { background: #333; }

        /* ── DIVIDER ── */
        .divider {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--light-grey);
        }

        .divider span {
            font-size: 9px;
            letter-spacing: 0.18em;
            color: var(--grey);
            white-space: nowrap;
            text-transform: uppercase;
        }

        /* ── SOCIAL BUTTONS ── */
        .social-btns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .btn-social {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            border: 1px solid var(--light-grey);
            background: var(--white);
            padding: 13px;
            font-family: var(--font-body);
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.14em;
            cursor: pointer;
            transition: border-color 0.2s, background 0.2s;
        }

        .btn-social:hover { border-color: var(--black); background: #f5f5f5; }

        .btn-social svg { flex-shrink: 0; }

        /* ── FOOTER TEXT ── */
        .auth-footer {
            font-size: 10px;
            color: var(--grey);
            line-height: 1.7;
            text-align: center;
        }

        .auth-footer a {
            color: var(--black);
            font-weight: 600;
            text-decoration: underline;
        }

        .support-row {
            display: flex;
            justify-content: flex-end;
            gap: 1.5rem;
            align-items: center;
            margin-top: 1.5rem;
        }

        .support-row a {
            font-size: 10px;
            font-weight: 500;
            letter-spacing: 0.1em;
            color: var(--grey);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: color 0.2s;
        }

        .support-row a:hover { color: var(--black); }

        /* ── PANEL VISIBILITY ── */
        .panel { display: none; }
        .panel.active { display: block; }

        /* ── RESPONSIVE ── */
        @media (max-width: 768px) {
            body { grid-template-columns: 1fr; }
            .left { display: none; }
            .right { padding: 2rem 1.8rem; }
        }
    </style>
</head>
<body>

<!-- ── LEFT PANEL ── -->
<div class="left">
    <img
        src="https://images.unsplash.com/photo-1487222477894-8943e31ef7b2?w=1200&q=85"
        alt="Fashion"
    />
    <a href="../index.php" class="left-logo">ZALORA</a>
    <p class="left-quote">"Elegance is the only beauty<br><em>that never fades."</em></p>
</div>

<!-- ── RIGHT PANEL ── -->
<div class="right">
    <div>
        <!-- TABS -->
        <div class="tabs">
            <button class="tab-btn <?= $tab === 'login' ? 'active' : '' ?>" onclick="switchTab('login')">Login</button>
            <button class="tab-btn <?= $tab === 'register' ? 'active' : '' ?>" onclick="switchTab('register')">Register</button>
        </div>

        <?php if ($error): ?>
            <div class="error-msg"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- LOGIN PANEL -->
        <div class="panel <?= $tab === 'login' ? 'active' : '' ?>" id="panel-login">
            <div class="form-area">
                <h2 class="form-title">Welcome Back</h2>
                <p class="form-subtitle">Enter your credentials to access your curated wardrobe.</p>

                <form method="POST" action="login_handler.php">
                    <input type="hidden" name="action" value="login"/>

                    <div class="field">
                        <div class="field-top">
                            <label for="login-email">Email Address</label>
                        </div>
                        <div class="input-wrap">
                            <input type="email" id="login-email" name="email" placeholder="alex@studio.com" required/>
                        </div>
                    </div>

                    <div class="field">
                        <div class="field-top">
                            <label for="login-pw">Password</label>
                            <a href="forgot.php" class="forgot-link">Forgot?</a>
                        </div>
                        <div class="input-wrap">
                            <input type="password" id="login-pw" name="password" placeholder="••••••••" required/>
                            <button type="button" class="toggle-pw" onclick="togglePw('login-pw', this)" title="Show/hide password">
                                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                    </div>

                    <label class="remember">
                        <input type="checkbox" name="remember"/>
                        <span>Remember this device</span>
                    </label>

                    <button type="submit" class="btn-signin">Sign In</button>
                </form>

                <div class="divider"><span>Or continue with</span></div>

                <div class="social-btns">
                    <button class="btn-social">
                        <!-- Google icon -->
                        <svg width="16" height="16" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                        Google
                    </button>
                    <button class="btn-social">
                        <!-- Apple icon -->
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/></svg>
                        Apple
                    </button>
                </div>
            </div>
        </div>

        <!-- REGISTER PANEL -->
        <div class="panel <?= $tab === 'register' ? 'active' : '' ?>" id="panel-register">
            <div class="form-area">
                <h2 class="form-title">Create Account</h2>
                <p class="form-subtitle">Join Zalora and discover your curated wardrobe.</p>

                <form method="POST" action="register_handler.php">
                    <input type="hidden" name="action" value="register"/>

                    <div class="field">
                        <div class="field-top"><label for="reg-name">Full Name</label></div>
                        <div class="input-wrap">
                            <input type="text" id="reg-name" name="name" placeholder="Alex Studio" required/>
                        </div>
                    </div>

                    <div class="field">
                        <div class="field-top"><label for="reg-email">Email Address</label></div>
                        <div class="input-wrap">
                            <input type="email" id="reg-email" name="email" placeholder="alex@studio.com" required/>
                        </div>
                    </div>

                    <div class="field">
                        <div class="field-top"><label for="reg-pw">Password</label></div>
                        <div class="input-wrap">
                            <input type="password" id="reg-pw" name="password" placeholder="••••••••" required/>
                            <button type="button" class="toggle-pw" onclick="togglePw('reg-pw', this)" title="Show/hide password">
                                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                    </div>

                    <div class="field">
                        <div class="field-top"><label for="reg-confirm">Confirm Password</label></div>
                        <div class="input-wrap">
                            <input type="password" id="reg-confirm" name="confirm_password" placeholder="••••••••" required/>
                            <button type="button" class="toggle-pw" onclick="togglePw('reg-confirm', this)" title="Show/hide password">
                                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-signin">Create Account</button>
                </form>

                <div class="divider"><span>Or continue with</span></div>

                <div class="social-btns">
                    <button class="btn-social">
                        <svg width="16" height="16" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                        Google
                    </button>
                    <button class="btn-social">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/></svg>
                        Apple
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- BOTTOM -->
    <div>
        <p class="auth-footer">
            By signing in, you agree to our <a href="#">Terms</a> and <a href="#">Privacy Policy</a>.
        </p>
        <div class="support-row">
            <a href="#">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                Support
            </a>
            <a href="#">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                EN / USD
            </a>
        </div>
    </div>
</div>

<script>
    function switchTab(tab) {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
        document.querySelector(`#panel-${tab}`).classList.add('active');
        event.target.classList.add('active');
        history.replaceState(null, '', `?tab=${tab}`);
    }

    function togglePw(inputId, btn) {
        const input = document.getElementById(inputId);
        const isHidden = input.type === 'password';
        input.type = isHidden ? 'text' : 'password';
        btn.style.opacity = isHidden ? '1' : '0.4';
    }
</script>
</body>
</html>