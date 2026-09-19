<?php
/**
 * auth/process_otp.php
 * ------------------------------------------------------------------
 * Verifies the OTP submitted from otp_verify.php. Only on success is
 * the real authenticated session created (via createAuthSession() in
 * includes/auth.php, also used by the OTP_BYPASS path in
 * process_login.php).
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

createAuthSession($user);

logActivity((int)$user['id'], 'Login', 'User logged in successfully (OTP verified).');
setFlash('success', 'Welcome back, ' . $user['full_name'] . '!');
redirect(APP_URL . '/dashboard.php');
