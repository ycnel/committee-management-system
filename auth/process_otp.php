<?php
/**
 * auth/process_otp.php
 * ------------------------------------------------------------------
 * Verifies the OTP submitted from otp_verify.php. Only on success is
 * the real authenticated session created — this is the single place
 * in the whole app where $_SESSION['user_id'] gets set on login.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/OtpService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/login.php');
}
requireCsrf();

$pendingUserId = $_SESSION['pending_otp_user_id'] ?? null;
if (!$pendingUserId) {
    setFlash('warning', 'Please log in to continue.');
    redirect(APP_URL . '/login.php');
}

$otp = clean($_POST['otp'] ?? '');
if ($otp === '' || !preg_match('/^\d{6}$/', $otp)) {
    setFlash('danger', 'Please enter the 6-digit code.');
    redirect(APP_URL . '/otp_verify.php');
}

$pdo = db();
$otpService = new OtpService($pdo);
$result = $otpService->verify((int)$pendingUserId, $otp);

if (!$result['success']) {
    logActivity((int)$pendingUserId, 'OTP Verification Failed', $result['message']);
    setFlash('danger', $result['message']);
    redirect(APP_URL . '/otp_verify.php');
}

// ---- OTP verified: fetch the user and create the real session ----
$stmt = $pdo->prepare(
    'SELECT u.id, u.full_name, u.email, u.role_id, r.name AS role_name
     FROM users u INNER JOIN roles r ON r.id = u.role_id
     WHERE u.id = :id'
);
$stmt->execute([':id' => $pendingUserId]);
$user = $stmt->fetch();

if (!$user) {
    setFlash('danger', 'Your account could not be found. Please try logging in again.');
    redirect(APP_URL . '/login.php');
}

unset($_SESSION['pending_otp_user_id'], $_SESSION['pending_otp_email'], $_SESSION['demo_otp_code']);
session_regenerate_id(true);

$sessionToken = bin2hex(random_bytes(32));
$sessionStmt = $pdo->prepare(
    'UPDATE users SET current_session_token = :token WHERE id = :id'
);
$sessionStmt->execute([':token' => $sessionToken, ':id' => $user['id']]);

$_SESSION['user_id']       = (int)$user['id'];
$_SESSION['session_token']  = $sessionToken;
$_SESSION['full_name']     = $user['full_name'];
$_SESSION['email']         = $user['email'];
$_SESSION['role_id']       = (int)$user['role_id'];
$_SESSION['role_name']     = $user['role_name'];
$_SESSION['last_activity'] = time();
$_SESSION['login_started_at'] = time();

logActivity((int)$user['id'], 'Login', 'User logged in successfully (OTP verified).');
setFlash('success', 'Welcome back, ' . $user['full_name'] . '!');
redirect(APP_URL . '/dashboard.php');
