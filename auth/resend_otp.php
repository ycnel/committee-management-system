<?php
/**
 * auth/resend_otp.php
 * ------------------------------------------------------------------
 * Issues a new OTP for the pending login. Resend is only honored if
 * the previous OTP has actually expired — enforced here server-side,
 * not just by a disabled button in the browser.
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

$pdo = db();
$otpService = new OtpService($pdo);

$userStmt = $pdo->prepare('SELECT email, full_name FROM users WHERE id = :id LIMIT 1');
$userStmt->execute([':id' => (int)$pendingUserId]);
$user = $userStmt->fetch();
if (!$user) {
    setFlash('danger', 'Your account could not be found. Please log in again.');
    redirect(APP_URL . '/login.php');
}

if (!$otpService->canResend((int)$pendingUserId)) {
    $remaining = $otpService->secondsRemaining((int)$pendingUserId);
    setFlash('warning', "Please wait {$remaining} more second(s) before requesting a new code.");
    redirect(APP_URL . '/otp_verify.php');
}

$issued = $otpService->generate((int)$pendingUserId);
if (OTP_DELIVERY_MODE === 'email') {
    try {
        $otpService->sendEmail($user['email'], $user['full_name'], $issued['otp']);
    } catch (Throwable $e) {
        error_log('OTP resend email error: ' . $e->getMessage());
        setFlash('danger', 'We could not send the verification email. Please try again later.');
        redirect(APP_URL . '/otp_verify.php');
    }
}
logActivity((int)$pendingUserId, 'OTP Generated', 'A new OTP was issued (resend).');

if (OTP_DELIVERY_MODE === 'demo') {
    $_SESSION['demo_otp_code'] = $issued['otp'];
}

setFlash('success', 'A new verification code has been issued.');
redirect(APP_URL . '/otp_verify.php');
