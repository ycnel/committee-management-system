<?php
/**
 * modules/system/ajax_restore.php
 * ------------------------------------------------------------------
 * POST (multipart): restore the database from an uploaded .sql file.
 * Super Admin only, CSRF-checked, and — because this is genuinely
 * destructive — the client (assets/js/system-backup.js) refuses to
 * even submit the form until the person has typed "RESTORE" into a
 * confirmation field, on top of the server-side checks here.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../../includes/auth.php';
requireSuperAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    jsonResponse(false, 'POST requests only.');
}
requireCsrf();

if (empty($_FILES['backup_file']) || ($_FILES['backup_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    jsonResponse(false, 'No valid .sql file was uploaded.');
}

$file = $_FILES['backup_file'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if ($ext !== 'sql') {
    jsonResponse(false, 'Only .sql files are accepted.');
}
if ($file['size'] > MAX_UPLOAD_SIZE * 10) { // backups run larger than the general upload cap
    jsonResponse(false, 'File is too large (max ' . ((MAX_UPLOAD_SIZE * 10) / 1024 / 1024) . ' MB).');
}
if (!is_uploaded_file($file['tmp_name'])) {
    jsonResponse(false, 'Upload failed validation.');
}

try {
    $result = restoreDatabaseFromSql(db(), $file['tmp_name']);

    if ($result['error'] !== null) {
        logActivity((int)currentUserId(), 'Restore',
            "Restore FAILED after {$result['statements']} statement(s): {$result['error']}");
        jsonResponse(false, 'Restore failed and was rolled back: ' . $result['error'], [
            'statements' => $result['statements'],
        ]);
    }

    logActivity((int)currentUserId(), 'Restore',
        "Restored database from uploaded file ({$result['statements']} statements executed).");
    jsonResponse(true, "Restore complete — {$result['statements']} statement(s) executed.", [
        'statements' => $result['statements'],
    ]);
} catch (Throwable $e) {
    error_log('Database restore error: ' . $e->getMessage());
    jsonResponse(false, 'A server error occurred during restore: ' . $e->getMessage());
}
