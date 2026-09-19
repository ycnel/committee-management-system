<?php
/**
 * Recipient-scoped notification persistence.
 */

require_once __DIR__ . '/../config/database.php';

function createNotification(
    int $recipientUserId,
    string $message,
    ?string $url = null,
    ?int $activityLogId = null
): void {
    if ($recipientUserId <= 0) return;

    try {
        $stmt = db()->prepare(
            'INSERT IGNORE INTO notifications
                (recipient_user_id, activity_log_id, message, url, created_at)
             VALUES (:recipient_user_id, :activity_log_id, :message, :url, NOW())'
        );
        $stmt->execute([
            ':recipient_user_id' => $recipientUserId,
            ':activity_log_id' => $activityLogId,
            ':message' => $message,
            ':url' => $url,
        ]);
    } catch (Throwable $e) {
        error_log('Notification creation failed: ' . $e->getMessage());
    }
}