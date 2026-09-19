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
// Reads HostForge environment variables first; falls back to local XAMPP values.
function cmas_env(array $keys, string $default = ''): string
{
    foreach ($keys as $k) {
        $v = getenv($k);
        if ($v === false || $v === '') { $v = $_ENV[$k] ?? $_SERVER[$k] ?? false; }
        if ($v !== false && $v !== '') { return (string)$v; }
    }
    return $default;
}

// Fallback: parse DATABASE_URL (mysql://user:pass@host:port/dbname)
$cmasDbUrl = [];
$cmasRawUrl = cmas_env(['DATABASE_URL']);
if ($cmasRawUrl !== '') {
    $parsed = parse_url($cmasRawUrl);
    if (is_array($parsed)) {
        $cmasDbUrl = [
            'host' => $parsed['host'] ?? '',
            'port' => isset($parsed['port']) ? (string)$parsed['port'] : '',
            'name' => isset($parsed['path']) ? ltrim($parsed['path'], '/') : '',
            'user' => isset($parsed['user']) ? urldecode($parsed['user']) : '',
            'pass' => isset($parsed['pass']) ? urldecode($parsed['pass']) : '',
        ];
    }
}

$cmasDbHost = cmas_env(['DB_HOST'], $cmasDbUrl['host'] ?? '') ?: 'localhost';
// "localhost" makes PDO use a unix socket, which does not exist on the host.
// Force TCP instead.
if (strtolower($cmasDbHost) === 'localhost') {
    $cmasDbHost = '127.0.0.1';
}

define('DB_HOST', $cmasDbHost);
define('DB_PORT', cmas_env(['DB_PORT'], ($cmasDbUrl['port'] ?? '') ?: '3306'));
define('DB_NAME', cmas_env(['DB_DATABASE', 'DB_NAME'], ($cmasDbUrl['name'] ?? '') ?: 'committee_management_db'));
define('DB_USER', cmas_env(['DB_USERNAME', 'DB_USER'], ($cmasDbUrl['user'] ?? '') ?: 'root'));
define('DB_PASS', cmas_env(['DB_PASSWORD', 'DB_PASS'], $cmasDbUrl['pass'] ?? ''));
define('DB_CHARSET', 'utf8mb4');

// ---- Application settings -----------------------------------------
define('APP_NAME', 'Committee Management and Assignment System');
define('APP_SHORT_NAME', 'CMAS');

// Set CMAS_APP_URL in production (for example, https://example.com).
// When unset, derive the URL from the current request so local XAMPP and
// hosted deployments both keep their own scheme and hostname.
$configuredAppUrl = trim((string)getenv('CMAS_APP_URL'));
$forwardedProto = strtolower(trim(explode(',', (string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0] ?? ''));
$scheme = $forwardedProto === 'https' || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    ? 'https'
    : 'http';
$requestHost = trim((string)($_SERVER['HTTP_HOST'] ?? ''));
$isLocalHost = $requestHost === 'localhost'
    || str_starts_with($requestHost, 'localhost:')
    || $requestHost === '127.0.0.1'
    || str_starts_with($requestHost, '127.0.0.1:');
$configuredHost = strtolower((string)(parse_url($configuredAppUrl, PHP_URL_HOST) ?? ''));
$configuredIsLocal = $configuredHost === 'localhost'
    || $configuredHost === '127.0.0.1'
    || $configuredHost === '::1';

if ($configuredAppUrl === '' || ($configuredIsLocal && !$isLocalHost)) {
    $configuredAppUrl = $requestHost !== '' && !$isLocalHost
        ? $scheme . '://' . $requestHost
        : 'http://localhost/committee-management-system';
}
define('APP_URL', rtrim($configuredAppUrl, '/'));

define('APP_TIMEZONE', 'Asia/Manila');

// ---- Upload settings -------------------------------------------------
define('UPLOAD_DIR', __DIR__ . '/../assets/uploads/');
define('UPLOAD_URL', APP_URL . '/assets/uploads/');
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10 MB
define('ALLOWED_UPLOAD_EXT', ['pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg']);

// ---- Session settings -----------------------------------------------
define('SESSION_NAME', 'cmas_session');
define('SESSION_LIFETIME', 60 * 60 * 8); // 8-hour maximum cookie lifetime
define('IDLE_TIMEOUT', 60 * 30); // Total inactivity timeout
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
