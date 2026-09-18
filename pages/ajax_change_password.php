<?php
/**
 * pages/ajax_change_password.php
 * ------------------------------------------------------------------
 * Handles the "Change Password" form on pages/profile.php.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method.');
requireCsrf();

$currentPassword = (string)($_POST['current_password'] ?? '');
$newPassword     = (string)($_POST['new_password'] ?? '');
$confirmPassword = (string)($_POST['confirm_password'] ?? '');

if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
    jsonResponse(false, 'All fields are required.');
}
$policyErrors = validatePasswordPolicy($newPassword);
if (!empty($policyErrors)) {
    jsonResponse(false, implode(' ', $policyErrors));
}
if ($newPassword !== $confirmPassword) {
    jsonResponse(false, 'New password and confirmation do not match.');
}

$pdo = db();

try {
    $stmt = $pdo->prepare('SELECT password FROM users WHERE id = :id');
    $stmt->execute([':id' => currentUserId()]);
    $row = $stmt->fetch();

    if (!$row || !password_verify($currentPassword, $row['password'])) {
        jsonResponse(false, 'Your current password is incorrect.');
    }

    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $upd = $pdo->prepare('UPDATE users SET password = :pw WHERE id = :id');
    $upd->execute([':pw' => $newHash, ':id' => currentUserId()]);

    logActivity(currentUserId(), 'Update', 'Changed own password.');
    jsonResponse(true, 'Password updated successfully. Please use it next time you log in.');

} catch (PDOException $e) {
    error_log('Change password error: ' . $e->getMessage());
    jsonResponse(false, 'A database error occurred while updating your password.');
}
