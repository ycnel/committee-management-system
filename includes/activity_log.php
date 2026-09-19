<?php
/**
 * includes/activity_log.php
 * ------------------------------------------------------------------
 * Central helper to write to the `activity_logs` table. Used across
 * the whole app for Login, Logout, Insert, Update, Delete, Download,
 * Print, Export, and other user actions.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Record an activity log entry.
 *
 * @param int|null $userId  The acting user's id (null for anonymous public actions)
 * @param string   $action  Short action label, e.g. 'Login', 'Insert Hearing', 'Delete Issue'
 * @param string   $details Optional free-text details
 */
/**
 * Real client IP — behind a reverse proxy REMOTE_ADDR is the proxy's
 * loopback; X-Forwarded-For carries the client chain (leftmost = original).
 */
function clientIp(): ?string
{
    $xff = (string)($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '');
    if ($xff !== '') {
        foreach (explode(',', $xff) as $candidate) {
            $ip = trim($candidate);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? null;
}

function logActivity(?int $userId, string $action, string $details = ''): ?int
{
    try {
        $stmt = db()->prepare(
            'INSERT INTO activity_logs
                (user_id, action, details, ip_address, user_agent, created_at)
             VALUES (:user_id, :action, :details, :ip_address, :user_agent, NOW())'
        );
        $stmt->execute([
            ':user_id' => $userId,
            ':action'  => $action,
            ':details' => $details,
            ':ip_address' => clientIp(),
            ':user_agent' => isset($_SERVER['HTTP_USER_AGENT'])
                ? substr((string)$_SERVER['HTTP_USER_AGENT'], 0, 500)
                : null,
        ]);
        return (int)db()->lastInsertId();
    } catch (Throwable $e) {
        // Logging must never break the main request flow.
        error_log('Activity log failed: ' . $e->getMessage());
        return null;
    }
}
