<?php
/**
 * auth/cancel_login.php
 * ------------------------------------------------------------------
 * Clears any pending (unverified) login state and returns to the
 * login page. Used by the "Cancel and go back to login" link on
 * otp_verify.php.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../includes/auth.php';

unset($_SESSION['pending_otp_user_id'], $_SESSION['pending_otp_email'], $_SESSION['demo_otp_code']);
redirect(APP_URL . '/login.php');
