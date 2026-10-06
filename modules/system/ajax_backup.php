<?php
/**
 * modules/system/ajax_backup.php
 * ------------------------------------------------------------------
 * GET: streams a full .sql backup of the database. Super Admin only.
 * A simple <a>/window.location download (not a fetch() call) so the
 * browser's native file-save flow handles the response — see
 * assets/js/system-backup.js.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../../includes/auth.php';
requireSuperAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit('GET requests only.');
}

// CSRF: a plain GET/download link can't attach a header, so this is
// checked via a short-lived token query param instead of requireCsrf().
$token = (string)($_GET['csrf_token'] ?? '');
if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    http_response_code(403);
    exit('Invalid or expired security token. Please refresh the page and try again.');
}

set_time_limit(300); // large databases can take a while to dump
session_write_close(); // release the session lock before the (potentially slow) dump

try {
    logActivity((int)currentUserId(), 'Backup', 'Downloaded a full database backup.');
    streamDatabaseBackup(db()); // writes the file and exit()s
} catch (Throwable $e) {
    error_log('Database backup error: ' . $e->getMessage());
    http_response_code(500);
    exit('Backup failed: ' . $e->getMessage());
}
