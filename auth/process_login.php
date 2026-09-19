<?php
/**
 * auth/process_login.php
 * ------------------------------------------------------------------
 * Handles the login form POST from login.php.
 *
 * v1.1: no longer creates an authenticated session directly. Instead:
 *   1. Check the account isn't currently locked (3 failed attempts ->
 *      5-minute lock, tracked server-side in `login_security`).
 *   2. Verify credentials with password_verify().
 *   3. On success, issue an OTP and redirect to otp_verify.php — the
 *      session is only created after OTP verification succeeds
 *      (see auth/process_otp.php).
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/LoginSecurity.php';
require_once __DIR__ . '/../includes/OtpService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/login.php');
}

requireCsrf();

$email    = clean($_POST['email'] ?? '');
$password = (string)($_POST['password'] ?? '');

if ($email === '' || $password === '') {
    setFlash('danger', 'Please enter both email and password.');
    redirect(APP_URL . '/login.php');
}

$pdo = db();
$security = new LoginSecurity($pdo);

// ---- Account lockout check (server-side, before touching the password) ----
$lockRemaining = $security->lockSecondsRemaining($email);
if ($lockRemaining > 0) {
    $mins = ceil($lockRemaining / 60);
    setFlash('danger', "This account is temporarily locked due to repeated failed login attempts. Please try again in about {$mins} minute(s).");
    redirect(APP_URL . '/login.php');
}

try {
    $stmt = $pdo->prepare(
        'SELECT u.id, u.full_name, u.email, u.password, u.status, u.role_id, r.name AS role_name
         FROM users u
         INNER JOIN roles r ON r.id = u.role_id
         WHERE u.email = :email
         LIMIT 1'
    );
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        $security->recordFailure($email);
        logActivity(null, 'Login Failed', 'Email: ' . $email);

        // Check the actual lock state directly rather than the attempts
        // counter — recordFailure() resets that counter to 0 as part of
        // locking, so re-reading it here would wrongly look like a
        // fresh, unlocked account on the very attempt that triggers the lock.
        if ($security->isLocked($email)) {
            logActivity(null, 'Account Locked', 'Email: ' . $email . ' locked for ' . (LoginSecurity::LOCK_SECONDS / 60) . ' minutes after repeated failed attempts.');
            setFlash('danger', 'This account has been locked for 5 minutes due to 3 failed login attempts.');
        } else {
            $remaining = $security->attemptsRemaining($email);
            setFlash('danger', "Invalid email or password. {$remaining} attempt(s) remaining before this account is temporarily locked.");
        }
        redirect(APP_URL . '/login.php');
    }

    if (strcasecmp($user['status'], 'Active') !== 0) {
        setFlash('danger', 'Your account is currently inactive. Please contact the administrator.');
        redirect(APP_URL . '/login.php');
    }

    // Credentials valid — reset the failure counter and move to OTP.
    $security->recordSuccess($email);

    $otpService = new OtpService($pdo);
    $issued = $otpService->generate((int)$user['id']);

    if (OTP_DELIVERY_MODE === 'email') {
        $otpService->sendEmail($user['email'], $user['full_name'], $issued['otp']);
    }

    // Partial "pending" auth state only — NOT a real authenticated
    // session. requireLogin() elsewhere in the app does not honor
    // these keys, so no protected page is reachable until OTP passes.
    session_regenerate_id(true);
    $_SESSION['pending_otp_user_id'] = (int)$user['id'];
    $_SESSION['pending_otp_email']   = $user['full_name'];

    logActivity((int)$user['id'], 'OTP Generated', 'OTP issued for login verification.');

    // Demo delivery only: hand the plaintext code to the next page via a
    // one-time session value. Email mode never stores or displays the code.
    if (OTP_DELIVERY_MODE === 'demo') {
        $_SESSION['demo_otp_code'] = $issued['otp'];
    }

    redirect(APP_URL . '/otp_verify.php');

} catch (Throwable $e) {
    error_log('Login error: ' . $e->getMessage());

    // TEMPORARY: while APP_DEBUG is true, show the real error so we can
    // find out why the OTP email fails. Set APP_DEBUG to false in
    // config/config.php afterwards to restore the generic message.
    if (APP_DEBUG) {
        setFlash('danger', 'DEBUG: ' . $e->getMessage());
    } else {
        setFlash('danger', OTP_DELIVERY_MODE === 'email'
            ? 'We could not send the verification email. Please try again later.'
            : 'A system error occurred. Please try again later.');
    }
    redirect(APP_URL . '/login.php');
}
