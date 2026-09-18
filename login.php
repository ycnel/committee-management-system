<?php
/**
 * login.php (project root)
 * ------------------------------------------------------------------
 * Public login page. Renders the login form and, on success,
 * hands off credential checking to auth/process_login.php.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/includes/auth.php';


// Already logged in? Go straight to the dashboard.
if (isLoggedIn()) {
    redirect(APP_URL . '/dashboard.php');
}

$timeout = isset($_GET['timeout']);
$sessionReplaced = isset($_GET['session_replaced']);
$flashMessages = getFlashMessages();
if ($sessionReplaced) {
    $flashMessages[] = [
        'type' => 'warning',
        'message' => 'Your session has expired. Please sign in again to continue ',
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta property="og:title" content="<?= e($pageTitle) ?>">
<meta property="og:description" content="Committee Management System">
<meta property="og:image" content="<?= e(APP_URL) ?>/assets/img/manila-city.jpg">
<meta property="og:url" content="<?= e(APP_URL) ?>">
<meta property="og:type" content="website">
<title>Login | <?= e(APP_NAME) ?></title>

<link href="<?= e(vendorAsset('bootstrap/bootstrap.min.css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css')) ?>" rel="stylesheet">
<link href="<?= e(vendorAsset('bootstrap-icons/bootstrap-icons.css', 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css')) ?>" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
<link rel="icon" type="image/png" href="<?= e(APP_URL) ?>/assets/img/city_manila_seal.png">
<link href="assets/css/auth-loading.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Google+Sans:ital,opsz,wght@0,17..18,400..700;1,17..18,400..700&display=swap" rel="stylesheet">
<style>
    :root {
        --primary-blue: #0B2E59;
        --primary-blue-dark: #08213F;
        --primary-blue-light: #1F4E85;
        --primary-yellow: #D4AF37;
        --primary-yellow-light: #E5C767;
        --primary-white: #FFFFFF;
        --primary-gray: #F7F8FA;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', Roboto, Arial, sans-serif;
    }

    html, body {
        height: 100%;
        overflow: hidden;
    }

    .login-wrapper {
        display: flex;
        height: 100vh;
        width: 100%;
        background: var(--primary-white);
    }

    /* LEFT SIDE - Brand Section */
    .brand-side {
        flex: 1;
        
        background-image: linear-gradient(rgba(6, 26, 53, 0.65), rgba(6, 26, 53, 100)), url('assets/img/manila-city.jpg');
        background-size: cover;
        background-position: center;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        padding: 3rem;
        position: relative;
        overflow: hidden;
        min-height: 100vh;
    }

    .brand-content {
        position: relative;
        z-index: 2;
        text-align: center;
        max-width: 480px;
        color: var(--primary-white);
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .brand-mark {
        width: 280px;
        height: 280px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        margin-bottom: 1.5rem;
        position: relative;
        overflow: hidden;
        transform-style: preserve-3d;
        transition: transform 300ms ease, box-shadow 300ms ease;
    }

    .brand-mark::before {
        content: "";
        position: absolute;
        inset: 10%;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(255,255,255,0.22) 0%, rgba(255,255,255,0.06) 28%, rgba(255,255,255,0) 70%);
    }

    .brand-mark::after {
        content: "";
        position: absolute;
        top: -20%;
        left: -35%;
        width: 24%;
        height: 140%;
        border-radius: 20px;
        background: rgba(255, 255, 255, 0.4);
        filter: blur(8px);
        opacity: 0;
        transform: skewX(20deg) translateX(-120px);
        z-index: 3;
        pointer-events: none;
    }

    .brand-mark:hover {
        transform: scale(1.06);
        box-shadow: 0 28px 42px rgba(0, 0, 0, 0.38);
    }

    .brand-mark:hover::after {
        opacity: 1;
        animation: sealLightSweep 1.2s ease-out forwards;
    }

    @keyframes sealLightSweep {
        from { transform: skewX(20deg) translateX(-120px); }
        to { transform: skewX(20deg) translateX(560px); }
    }

    @media (prefers-reduced-motion: reduce) {
        .brand-mark,
        .brand-mark::after {
            transition: none;
            animation: none;
        }
    }

    @keyframes brandShine {
        0% {
            transform: scale(0.98);
            opacity: 0.8;
        }
        100% {
            transform: scale(1.04);
            opacity: 1;
        }
    }

    .brand-mark img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        filter: none;
        position: relative;
        z-index: 2;
    }

    /* Logo Image Styling */
    .brand-logo {
        width: 120px;
        height: 120px;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(1px);
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 2rem;
        border: 1px solid rgba(251, 191, 36, 0.4);
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        overflow: hidden;

    }

    .brand-logo:hover {
        transform: scale(1.05) rotate(-3deg);
        box-shadow: 0 25px 70px rgba(0, 0, 0, 0.4);
    }

    .brand-logo img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        border-radius: 50%;
    }

    .brand-title {
        font-family: 'Bebas Neue', sans-serif;
        font-size: 4.5rem;
        font-weight: 500;
        line-height: 1.1;
        margin-bottom: 0rem;
        align-items: left;
        position: relative;
        text-align: center;
        letter-spacing: 0px;
        color: white;
        z-index: 1;
        text-shadow: 0 2px 12px rgba(0, 0, 0, 0.2);
    }

    .brand-title .highlight {
        color: var(--primary-yellow);
        position: relative;
    }

    .brand-title .highlight::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--primary-yellow);
        border-radius: 2px;
        opacity: 0.5;
    }

    .brand-description {
        font-family: 'Inter', sans-serif;
        font-size: 1rem;
        font-weight: 200;
        line-height: 1.5;
        margin-bottom: 0;
        margin-top: -0.5rem;
        color: white;
        text-shadow: 0 2px 10px rgba(0, 0, 0, 0.25);
        letter-spacing: 0.02em;
    }

    .brand-features {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        text-align: left;
        margin-top: 2rem;
    }

    .feature-item {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        padding: 1rem;
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .feature-item:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: translateY(-2px);
    }

    .feature-item i {
        color: var(--primary-yellow);
        font-size: 1.25rem;
    }

    .feature-item span {
        font-size: 0.85rem;
        font-weight: 500;
        color: var(--primary-white);
    }

    /* RIGHT SIDE - Login Section */
    .login-side {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem;
        background: var(--primary-white);
        position: relative;
        min-height: 100vh;
    }

    .login-container {
        width: 100%;
        max-width: 375px;
        padding: 0.5rem;
        position: relative;
        z-index: 1;
        animation: slideInRight 0.6s ease-out;
    }

    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(30px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    .login-header {
        margin-bottom: 0rem;
        text-align: center;
    }

    .login-header-logo {
        width: 88px;
        height: 88px;
        display: block;
        object-fit: contain;
        margin: 0 auto 1rem;
    }

    .login-greeting {
        font-family: 'Manrope', sans-serif;
        font-size: 2.1rem;
        font-weight: 800;
        color: var(--primary-blue-dark);
        margin-bottom: 0rem;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 0.5rem;
    }

    .login-greeting i {
        color: var(--primary-yellow);
        font-size: 1.5rem;
    }

    .login-subtitle {
        color: #6B7280;
        font-size: 0.85rem;
    }

    /* Custom Alerts */
    .alert-custom {
        border: none;
        border-radius: 10px;
        padding: 0.75rem 1rem;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-weight: 500;
        font-size: 0.85rem;
        border-left: 4px solid;
    }

    .alert-custom i {
        font-size:  0.85rem;
    }

    .alert-custom.alert-warning {
        background: #FFFBEB;
        color: #92400E;
        border-left-color: var(--primary-yellow);
    }

    .alert-custom.alert-danger {
        background: #ffe0e0;
        color: #991B1B;
        font-size: 0.80rem;
        border: 1px solid transparent;
        
    }

    .alert-custom.alert-success {
        background: #F0FDF4;
        color: #065F46;
        border-left-color: #10B981;
    }

    /* Form Fields */
    .form-group {
        margin-bottom: 10px;
    }

    .form-label {
        font-weight: 600;
        color: #374151;
        font-size: 0.85rem;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .form-label i {
        color: var(--primary-blue);
        font-size: 0.9rem;
    }

    .input-group-modern {
        position: relative;
    }

    .input-group-modern .input-icon {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #9CA3AF;
        z-index: 10;
        font-size: 1rem;
        transition: color 0.3s ease;
        pointer-events: none;
    }

    .input-group-modern .form-control {
        width: 100%;
        padding: 0.75rem 1rem 0.75rem 2.75rem;
        border-radius: 8px;
        border: 1px solid #E5E7EB;
        background: white;
        height: 3rem;
        font-size: 0.80rem;
        transition: all 0.3s ease;
        color: #1F2937;
        text-align: left;
    }

    .input-group-modern .form-control.password-input {
        padding-right: 3rem;
    }

    .input-group-modern .password-toggle {
        position: absolute;
        right: 1rem;
        top: 50%;
        transform: translateY(-50%);
        background: transparent;
        border: none;
        color: #9CA3AF;
        font-size: 1rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10;
        padding: 0;
        transition: color 0.3s ease;
    }

    .input-group-modern .password-toggle:hover {
        color: var(--primary-blue);
    }

    .input-group-modern .form-control:focus {
        border-color: var(--primary-blue);
        background: #FFFFFF;
        box-shadow: 0 1 1 1px rgba(26, 86, 219, 0.1);
        outline: none;
    }

    .input-group-modern .form-control:focus ~ .input-icon {
        color: var(--primary-blue);
       
    }

    .input-group-modern .form-control::placeholder {
        color: #9CA3AF;
        font-weight: 400;
       
    }

    /* Form Options */
    .form-options {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin: 1.5rem 0;
        flex-wrap: wrap;
        gap: 0.75rem;
    }

    .checkbox-custom {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        cursor: pointer;
        margin: 0;
    }

    .checkbox-custom input[type="checkbox"] {
        width: 18px;
        height: 18px;
        border-radius: 6px;
        border: 1px solid #D1D5DB;
        accent-color: var(--primary-blue);
        cursor: pointer;
        margin: 0;
        transition: all 0.2s ease;
        flex-shrink: 0;
    }

    .checkbox-custom input[type="checkbox"]:checked {
        border-color: var(--primary-blue);
        background-color: var(--primary-blue);
    }

    .checkbox-custom .check-label {
        font-size: 0.875rem;
        color: #4B5563;
        font-weight: 500;
        cursor: pointer;
        user-select: none;
    }

    /* Login Button */
    .btn-login {
        background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-blue-dark) 100%);
        border: none;
        border-radius: 8px;
        padding: 0.85rem;
        font-weight: 500;
        font-size: 0.85rem;
        color: white;
        height: 3rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        width: 100%;
        cursor: pointer;
        margin-top: 25px;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(26, 86, 219, 0.3);
    }

    .btn-login::after {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: left 0.6s ease;
    }

    .btn-login:hover::after {
        left: 100%;
    }

    .btn-login:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 30px rgba(26, 86, 219, 0.4);
        color: white;
    }

    .btn-login:active {
        transform: translateY(0);
    }

    .btn-login i {
        font-size: 1.1rem;
        transition: transform 0.3s ease;
    }

    .btn-login:hover i {
        transform: translateX(4px);
    }

    /* Footer */
    .login-footer {
        text-align: center;
        margin-top: 1.5rem;
        padding-top: 1.5rem;
        border-top: 1px solid #F3F4F6;
        color: #9CA3AF;
        font-size: 0.75rem;
    }

    .login-footer a {
        color: var(--primary-blue);
        text-decoration: none;
        font-weight: 500;
    }

    .login-footer a:hover {
        text-decoration: underline;
    }

    .login-footer i {
        color: var(--primary-yellow);
    }

    /* Divider */
    .divider {
        display: flex;
        align-items: center;
        margin: 1.5rem 0;
        gap: 1rem;
        color: #9CA3AF;
        font-size: 0.8rem;
        font-weight: 500;
    }

    .divider::before,
    .divider::after {
        content: '';
        flex: 1;
        height: 1px;
        background: #E5E7EB;
    }

    /* Responsive */
    @media (max-width: 992px) {
        .login-wrapper {
            flex-direction: column;
            height: auto;
            overflow-y: auto;
        }

        .brand-side {
            min-height: 50vh;
            padding: 2rem;
        }

        .brand-title {
            font-size: 1.25rem;
        }

        .login-side {
            min-height: 50vh;
            padding: 2rem 1.5rem;
        }

        .login-container {
            max-width: 100%;
            padding: 0;
        }

        .login-greeting {
            font-size: 1.5rem;
        }
    }

    @media (max-width: 576px) {
        .brand-title {
            font-size: 1.4rem;
        }

        .login-container {
            padding: 0;
        }
    }
</style>
</head>
<body>
<div class="login-wrapper">
    <!-- LEFT SIDE - Brand Information -->
    <div class="brand-side">
        

        <div class="brand-content">
            <div class="brand-mark" aria-label="Manila seal logo">
              <img src="assets/img/Ph_seal_ncr_manila.svg" alt="Manila seal"/> 
            </div>

            
            <h1 class="brand-title">
                Lungsod ng Maynila
            </h1>
            <p class="brand-description">Committee Management and Assigment System</p>

        </div>
    </div>

    <!-- RIGHT SIDE - Login Form -->
    <div class="login-side">
        <div class="login-container">
            <!-- Header -->
            <div class="login-header">
               <!-- <img class="login-header-logo" src="assets/img/Ph_seal_ncr_manila.svg" alt="Manila City seal"> -->
                <h2 class="login-greeting">
                    Welcome back!
                </h2>
                <p class="login-subtitle">Sign in to continue to your account.</p>
            </div>

            <!-- Alerts -->
            <?php if ($timeout): ?>
                <div class="alert-custom alert-warning">
                    <i class="bi bi-clock-history"></i>
                    <span>Your session expired. Please log in again.</span>
                </div>
            <?php endif; ?>

            <?php foreach ($flashMessages as $msg): ?>
                <div class="alert-custom alert-<?= e($msg['type']) ?>">
                    <i class="bi <?= $msg['type'] === 'success' ? 'bi-check-circle' : ($msg['type'] === 'warning' ? 'bi-exclamation-triangle' : 'bi-x-circle') ?>"></i>
                    <span><?= e($msg['message']) ?></span>
                </div>
            <?php endforeach; ?>

            <!-- Admin Login Info (for demonstration purposes) 
            <div class="alert-custom" style="background:#EFF6FF;color:#1E3A8A;border-left-color:var(--primary-blue);">
                <i class="bi bi-info-circle"></i>
                <span>First time here? Seeded admin login: <strong>admin@cmas.local</strong> / <strong>Admin@123</strong></span>
            </div>

            -->
            <!-- Login Form -->
            <form action="auth/process_login.php" method="POST" id="loginForm" novalidate data-auth-loading-form data-login-form>
                <?= csrfField() ?>

                <!-- Email -->
                <div class="form-group">
                    <label class="form-label" style="font-weight: 500;">
                        Email 
                    </label>
                    <div class="input-group-modern">
                        <input 
                            type="email" 
                            name="email" 
                            class="form-control" 
                            required 
                            autofocus 
                            autocomplete="email"
                            placeholder="Enter your email"
                        >
                        <i class="bi bi-envelope input-icon"></i>
                    </div>
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label class="form-label" style="font-weight: 500;">
                        Password
                    </label>
                    <div class="input-group-modern">
                        <input 
                            type="password" 
                            name="password" 
                            class="form-control password-input" 
                            required 
                            autocomplete="current-password"
                            placeholder="Enter your password"
                        >
                        <i class="bi bi-lock input-icon"></i>
                        <button type="button" class="password-toggle" aria-label="Show password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- Options -->
                

                <!-- Submit Button -->
                <button type="submit" class="btn-login">
                    Sign In
                </button>
            </form>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const passwordToggle = document.querySelector('.password-toggle');
                    const passwordInput = document.querySelector('input[name="password"]');

                    if (passwordToggle && passwordInput) {
                        passwordToggle.addEventListener('click', function () {
                            const isPassword = passwordInput.type === 'password';
                            passwordInput.type = isPassword ? 'text' : 'password';
                            passwordToggle.innerHTML = isPassword
                                ? '<i class="bi bi-eye-slash"></i>'
                                : '<i class="bi bi-eye"></i>';
                            passwordToggle.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
                        });
                    }
                });
            </script>

            <!-- Divider -->
           

            <!-- Footer -->
            <div class="login-footer">
                 <!--
                <i class="bi bi-shield-lock"></i>

               &nbsp;Your information is protected <br> -->
                &copy; <?= date('Y') ?> <?= e(APP_NAME) ?>
            </div>
        </div>
    </div>
</div>

<div class="auth-loading" id="authLoading" role="status" aria-live="polite" aria-hidden="true">
    <div class="auth-loading-content">
        <img src="assets/img/Ph_seal_ncr_manila.svg" alt="Manila seal" class="auth-loading-seal">
        <div class="leap-frog" aria-label="Loading">
            <div class="leap-frog__dot"></div>
            <div class="leap-frog__dot"></div>
            <div class="leap-frog__dot"></div>
        </div>
    </div>
</div>

<script src="<?= e(vendorAsset('bootstrap/bootstrap.bundle.min.js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js')) ?>"></script>
<script src="assets/js/auth-loading.js"></script>
</body>
</html>