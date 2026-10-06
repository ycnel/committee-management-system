<?php
/**
 * modules/messages/ajax_unread_count.php
 * ------------------------------------------------------------------
 * GET: just the total unread message count across every committee the
 * user belongs to. Polled frequently (badge on the Message button) so
 * it deliberately avoids the heavier conversation-list query.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    jsonResponse(false, 'GET requests only.');
}

session_write_close();

try {
    jsonResponse(true, '', ['total_unread' => totalUnreadMessagesForUser((int)currentUserId())]);
} catch (Throwable $e) {
    error_log('Message unread-count error: ' . $e->getMessage());
    jsonResponse(false, 'A database error occurred while checking messages.');
}
