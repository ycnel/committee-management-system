<?php
/**
 * config/config.php
 * ------------------------------------------------------------------
 * Global application configuration for the standalone Committee
 * Management and Assignment System (CMAS).
 * Edit the DB_* constants to match your MySQL server. The database
 * itself (committee_management_db) and its tables are created by
 * database/schema.sql — run that file once before using the app.
 * ------------------------------------------------------------------
 */

// ---- Database credentials ----------------------------------------
define('DB_HOST', 'localhost');
define('DB_NAME', 'committee_management_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ---- Application settings -----------------------------------------
define('APP_NAME', 'Committee Management and Assignment System');
define('APP_SHORT_NAME', 'CMAS');

// *** IMPORTANT — READ THIS IF FORMS/BUTTONS AREN'T WORKING ***
// APP_URL MUST exactly match the URL of the project folder in your browser's
// address bar. Every single AJAX/fetch call in the app (Add/Edit/Delete,
// search, filters, everything) is built from this constant on the client
// side — if it's wrong, ALL of those calls silently fail with 404s that
// look like "network error" in the UI.
//   - Placed the project at C:\xampp\htdocs\committee-management-system\
//     -> keep 'http://localhost/committee-management-system'
//   - Placed it at C:\xampp\htdocs\cmas\ -> change to 'http://localhost/cmas'
//   - Placed it directly in htdocs\ (no subfolder) -> change to 'http://localhost'
// After changing this, do a hard refresh (Ctrl+F5) so the browser doesn't
// use a cached copy of the old value.
define('APP_URL', 'http://localhost/committee-management-system');

define('APP_TIMEZONE', 'Asia/Manila');

// ---- Upload settings -------------------------------------------------
define('UPLOAD_DIR', __DIR__ . '/../assets/uploads/');
define('UPLOAD_URL', APP_URL . '/assets/uploads/');
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10 MB
define('ALLOWED_UPLOAD_EXT', ['pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg']);

// ---- Session settings -----------------------------------------------
define('SESSION_NAME', 'cmas_session');
define('SESSION_LIFETIME', 60 * 60 * 8); // 8-hour maximum cookie lifetime
define('IDLE_TIMEOUT', 60 * 15); // Total inactivity timeout
define('IDLE_WARNING_SECONDS', 10); // Show the expiry modal this many seconds before logout

ini_set('session.gc_maxlifetime', (string)SESSION_LIFETIME);
ini_set('session.cookie_lifetime', (string)SESSION_LIFETIME);

// ---- OTP delivery -----------------------------------------------------
// Real email delivery through the configured Brevo SMTP account.
define('OTP_DELIVERY_MODE', 'email');
define('OTP_SMTP_HOST', getenv('CMAS_OTP_SMTP_HOST') ?: 'smtp-relay.brevo.com');
define('OTP_SMTP_PORT', (int)(getenv('CMAS_OTP_SMTP_PORT') ?: 587));
define('OTP_SMTP_USERNAME', getenv('CMAS_OTP_SMTP_USERNAME') ?: '');
define('OTP_SMTP_PASSWORD', getenv('CMAS_OTP_SMTP_PASSWORD') ?: '');
define('OTP_SMTP_ENCRYPTION', getenv('CMAS_OTP_SMTP_ENCRYPTION') ?: 'tls');
define('OTP_FROM_EMAIL', getenv('CMAS_OTP_FROM_EMAIL') ?: '');
define('OTP_FROM_NAME', getenv('CMAS_OTP_FROM_NAME') ?: APP_NAME);

// ---- Pagination -------------------------------------------------------
define('DEFAULT_PAGE_SIZE', 10);

// ---- Roles (must match `roles` table `name` values exactly) -----------
// NOTE: "Legislative Staff" was renamed to "Committee Chairperson".
// The PHP constant name (ROLE_STAFF) is kept as-is on purpose -- it is
// referenced throughout the codebase, and only the display/DB value
// changes. If you already have an existing database, run
// database/migration_role_rename.sql once to update the stored role name.
define('ROLE_ADMIN', 'Administrator');
define('ROLE_STAFF', 'Committee Chairperson');
define('ROLE_COMMITTEE', 'Committee Member');

date_default_timezone_set(APP_TIMEZONE);

// ---- Error display (turn off in production) ----------------------------
define('APP_DEBUG', true);
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Central error/exception log file
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
