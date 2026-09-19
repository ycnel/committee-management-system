<?php

require_once __DIR__ . '/../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method.');
requireCsrf();
session_write_close(); // read-only endpoint: release the session lock for concurrent requests

$pdo = db();
$userId = (int)currentUserId();
$markAll = (int)($_POST['mark_all'] ?? 0) === 1;

try {
    if ($markAll) {
        $stmt = $pdo->prepare(
            'UPDATE notifications
             SET read_at = COALESCE(read_at, NOW())
             WHERE recipient_user_id = :user_id AND read_at IS NULL'
        );
        $stmt->execute([':user_id' => $userId]);
    } else {
        $notificationId = (int)($_POST['notification_id'] ?? 0);
        if ($notificationId <= 0) jsonResponse(false, 'Invalid notification id.');

        $stmt = $pdo->prepare(
            'UPDATE notifications
             SET read_at = COALESCE(read_at, NOW())
             WHERE notification_id = :notification_id
               AND recipient_user_id = :user_id
               AND read_at IS NULL'
        );
        $stmt->execute([
            ':notification_id' => $notificationId,
            ':user_id' => $userId,
        ]);
    }

    $unreadStmt = $pdo->prepare(
        'SELECT COUNT(*) FROM notifications
         WHERE recipient_user_id = :user_id AND read_at IS NULL'
    );
    $unreadStmt->execute([':user_id' => $userId]);
    jsonResponse(true, 'Notification marked as read.', ['unread_count' => (int)$unreadStmt->fetchColumn()]);
} catch (PDOException $e) {
    error_log('Notification read error: ' . $e->getMessage());
    jsonResponse(false, 'A database error occurred while updating the notification.');
}
