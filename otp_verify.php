<?php
/**
 * otp_verify.php (project root)
 * ------------------------------------------------------------------
 * Second factor of the login flow. Only reachable mid-login (after
 * process_login.php sets $_SESSION['pending_otp_user_id']) — never
 * a fully authenticated session on its own.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/OtpService.php';

if (isLoggedIn()) {
    redirect(APP_URL . '/dashboard.php');
}

$pendingUserId = $_SESSION['pending_otp_user_id'] ?? null;
if (!$pendingUserId) {
    setFlash('warning', 'Please log in to continue.');
    redirect(APP_URL . '/login.php');
}

$pendingName = $_SESSION['pending_otp_email'] ?? 'there';

// Demo-mode OTP display: read once, then clear so it doesn't linger in
// the session any longer than needed for this single page render.
$demoOtp = null;
if (OTP_DELIVERY_MODE === 'demo' && !empty($_SESSION['demo_otp_code'])) {
    $demoOtp = $_SESSION['demo_otp_code'];
    unset($_SESSION['demo_otp_code']);
}

$otpService = new OtpService(db());
$secondsRemaining = $otpService->secondsRemaining($pendingUserId);

$flashMessages = getFlashMessages();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verify Code | <?= e(APP_NAME) ?></title>
<link href="<?= e(vendorAsset('bootstrap/bootstrap.min.css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css')) ?>" rel="stylesheet">
<link href="<?= e(vendorAsset('bootstrap-icons/bootstrap-icons.css', 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css')) ?>" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
<link href="assets/css/auth-loading.css" rel="stylesheet">
<style>
  body.auth-body { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
  .otp-card { max-width: 440px; width: 100%; padding: 40px 36px; }
  .otp-icon { width: 60px; height: 60px; border-radius: 50%; background: var(--gov-primary); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 26px; margin: 0 auto 18px; }
  .otp-input {
    letter-spacing: 10px; font-size: 28px; font-weight: 700; text-align: center;
    padding: 12px; border-radius: 12px;
  }
  .demo-banner { background: #FBF3DD; border: 1px solid var(--gov-accent); border-radius: 12px; padding: 12px 16px; font-size: 13px; }
  #resendBtn:disabled { opacity: 0.55; cursor: not-allowed; }
</style>
</head>
<body class="auth-body">
  <div class="card auth-card otp-card">
    <div class="otp-icon"><i class="bi bi-shield-lock"></i></div>
    <h5 class="text-center mb-1">Verify Your Identity</h5>
    <p class="text-center text-muted small mb-4">Hi <?= e($pendingName) ?>, enter the 6-digit verification code to finish signing in.</p>

    <?php foreach ($flashMessages as $msg): ?>
      <div class="alert alert-<?= e($msg['type']) ?> py-2 small"><?= e($msg['message']) ?></div>
    <?php endforeach; ?>

    <?php if ($demoOtp !== null): ?>
      <div class="demo-banner mb-3">
        <strong><i class="bi bi-info-circle"></i> Demo Mode</strong> — no SMS/email provider is
        connected yet, so your code is shown here instead of being sent:
        <div class="text-center fs-4 fw-bold mt-1" style="letter-spacing:4px;"><?= e($demoOtp) ?></div>
      </div>
    <?php endif; ?>

    <form action="auth/process_otp.php" method="POST" id="otpForm" data-auth-loading-form>
      <?= csrfField() ?>
      <input type="text" name="otp" id="otpInput" class="form-control otp-input mb-3" maxlength="6"
             inputmode="numeric" pattern="[0-9]{6}" autocomplete="one-time-code" autofocus required>
      <button type="submit" class="btn btn-primary w-100 mb-3">Verify &amp; Continue</button>
    </form>

    <div class="text-center small">
      <span class="text-muted">Code expires in <span id="countdown" class="fw-semibold"></span>.</span>
    </div>
    <form action="auth/resend_otp.php" method="POST" class="text-center mt-2" data-auth-loading-form>
      <?= csrfField() ?>
      <button type="submit" id="resendBtn" class="btn btn-link btn-sm" <?= $secondsRemaining > 0 ? 'disabled' : '' ?>>
        Resend Code
      </button>
    </form>
    <div class="text-center mt-2">
      <a href="<?= e(APP_URL) ?>/auth/cancel_login.php" class="small text-muted" data-auth-loading-link>Cancel and go back to login</a>
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

<script>
(function () {
  let remaining = <?= (int)$secondsRemaining ?>;
  const countdownEl = document.getElementById('countdown');
  const resendBtn = document.getElementById('resendBtn');

  function formatCountdown(seconds) {
    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = seconds % 60;
    return minutes + ':' + String(remainingSeconds).padStart(2, '0');
  }

  countdownEl.textContent = formatCountdown(remaining);

  const timer = setInterval(function () {
    remaining -= 1;
    if (remaining <= 0) {
      remaining = 0;
      countdownEl.textContent = '0:00';
      resendBtn.removeAttribute('disabled');
      clearInterval(timer);
      return;
    }
    countdownEl.textContent = formatCountdown(remaining);
  }, 1000);

  // Auto-advance: numeric only, auto-submit at 6 digits for convenience.
  const input = document.getElementById('otpInput');
  input.addEventListener('input', function () {
    input.value = input.value.replace(/\D/g, '').slice(0, 6);
  });
})();
</script>
<script src="assets/js/auth-loading.js"></script>
</body>
</html>
