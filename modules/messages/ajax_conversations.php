<?php
/**
 * modules/messages/ajax_conversations.php
 * ------------------------------------------------------------------
 * GET: the list of committee group chats visible to the logged-in
 * user (their own Active committee memberships only), each with a
 * last-message preview and unread count. Populates the Messenger-style
 * conversation list in the floating Message panel.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    jsonResponse(false, 'GET requests only.');
}

session_write_close(); // read-only endpoint: don't hold the session lock while polling

$userId = (int)currentUserId();

try {
    $conversations = committeeConversationsForUser($userId);
    jsonResponse(true, '', [
        'conversations' => $conversations,
        'total_unread'  => array_sum(array_column($conversations, 'unread_count')),
    ]);
} catch (Throwable $e) {
    error_log('Message conversations error: ' . $e->getMessage());
    jsonResponse(false, 'A database error occurred while loading your conversations.');
}
