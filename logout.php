<?php
/**
 * logout.php (project root)
 * ------------------------------------------------------------------
 * Destroys the session and logs the Logout activity.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    $sessionDuration = isset($_SESSION['login_started_at'])
        ? max(0, time() - (int)$_SESSION['login_started_at'])
        : null;
    $logoutDetails = 'User logged out.';
    if ($sessionDuration !== null) {
        $logoutDetails .= ' Session duration: ' . $sessionDuration . ' seconds.';
    }
    logActivity(currentUserId(), 'Logout', $logoutDetails);

    $stmt = db()->prepare(
        'UPDATE users SET current_session_token = NULL
         WHERE id = :id AND current_session_token = :token'
    );
    $stmt->execute([
        ':id' => currentUserId(),
        ':token' => $_SESSION['session_token'] ?? '',
    ]);
}

destroySession();

$loginUrl = APP_URL . '/login.php';
if (isset($_GET['timeout'])) {
    $loginUrl .= '?timeout=1';
}
redirect($loginUrl);
