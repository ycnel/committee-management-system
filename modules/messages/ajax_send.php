<?php
/**
 * modules/messages/ajax_send.php
 * ------------------------------------------------------------------
 * POST: send a message to a committee's group chat. The sender is
 * always the logged-in user and the committee is always one they
 * actively belong to — both are re-derived from the session/DB, never
 * trusted from the request body.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    jsonResponse(false, 'POST requests only.');
}
requireCsrf();

$userId = (int)currentUserId();
$committeeId = (int)($_POST['committee_id'] ?? 0);
if ($committeeId <= 0) jsonResponse(false, 'A committee is required.');

requireCommitteeAccess($committeeId);

$body = clean((string)($_POST['message'] ?? ''));
if ($body === '') {
    jsonResponse(false, 'Message cannot be empty.');
}
if (mb_strlen($body) > MESSAGE_MAX_LENGTH) {
    jsonResponse(false, 'Message is too long (max ' . MESSAGE_MAX_LENGTH . ' characters).');
}

$pdo = db();

try {
    $insert = $pdo->prepare(
        'INSERT INTO committee_messages (committee_id, sender_user_id, message_body, created_at)
         VALUES (:cid, :uid, :body, NOW())'
    );
    $insert->execute([':cid' => $committeeId, ':uid' => $userId, ':body' => $body]);
    $messageId = (int)$pdo->lastInsertId();

    // Sending a message counts as having read up to it, so the sender's
    // own message never shows up as "unread" in their own badge/list.
    markCommitteeRead($userId, $committeeId, $messageId);

    // Read the row's actual created_at back from the DB (rather than using
    // PHP's clock) so the timestamp shown to the sender right now is
    // identical to what every later poll/fetch of this same message will
    // show, even if the DB server and web server clocks/timezones differ.
    $createdAtStmt = $pdo->prepare('SELECT created_at FROM committee_messages WHERE message_id = :id');
    $createdAtStmt->execute([':id' => $messageId]);
    $createdAt = (string)$createdAtStmt->fetchColumn();

    $user = currentUser();

    logActivity($userId, 'Message', 'Sent a message in committee #' . $committeeId);

    jsonResponse(true, 'Message sent.', [
        'message' => [
            'message_id'  => $messageId,
            'sender_id'   => $userId,
            'sender_name' => $user['full_name'] ?? '',
            'is_mine'     => true,
            'body'        => $body,
            'created_at'  => $createdAt,
            'created_at_human' => formatDateTime($createdAt, 'M d, Y h:i A'),
        ],
    ]);
} catch (PDOException $e) {
    error_log('Message send error: ' . $e->getMessage());
    jsonResponse(false, 'A database error occurred while sending your message.');
}
