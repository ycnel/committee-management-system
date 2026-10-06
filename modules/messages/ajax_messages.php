<?php
/**
 * modules/messages/ajax_messages.php
 * ------------------------------------------------------------------
 * GET: message history for one committee's group chat. Also used for
 * "real-time" delivery via short polling (?after_id=) and for loading
 * older history when the person scrolls up (?before_id=).
 *
 * Authorization: the caller must have an Active committee_members row
 * for the requested committee_id — enforced server-side on every call,
 * not just hidden in the UI, so a Committee Member from another
 * committee cannot read this one's conversation even by calling the
 * endpoint directly.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    jsonResponse(false, 'GET requests only.');
}

$userId = (int)currentUserId();
$committeeId = (int)($_GET['committee_id'] ?? 0);
if ($committeeId <= 0) jsonResponse(false, 'A committee is required.');

requireCommitteeAccess($committeeId);
session_write_close(); // membership check is done; release the lock while polling

$afterId  = max(0, (int)($_GET['after_id'] ?? 0));
$beforeId = max(0, (int)($_GET['before_id'] ?? 0));
$limit    = (int)($_GET['limit'] ?? 50);
$limit    = max(1, min($limit, 100));

$pdo = db();

try {
    if ($afterId > 0) {
        // Polling for new messages since the last one the client has.
        $stmt = $pdo->prepare(
            'SELECT m.message_id, m.sender_user_id, u.full_name AS sender_name,
                    m.message_body, m.created_at
             FROM committee_messages m
             LEFT JOIN users u ON u.id = m.sender_user_id
             WHERE m.committee_id = :cid AND m.message_id > :after_id
             ORDER BY m.message_id ASC
             LIMIT :limit'
        );
        $stmt->bindValue(':cid', $committeeId, PDO::PARAM_INT);
        $stmt->bindValue(':after_id', $afterId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();
    } elseif ($beforeId > 0) {
        // Scrolling up for older history — does not touch the read cursor.
        $stmt = $pdo->prepare(
            'SELECT m.message_id, m.sender_user_id, u.full_name AS sender_name,
                    m.message_body, m.created_at
             FROM committee_messages m
             LEFT JOIN users u ON u.id = m.sender_user_id
             WHERE m.committee_id = :cid AND m.message_id < :before_id
             ORDER BY m.message_id DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':cid', $committeeId, PDO::PARAM_INT);
        $stmt->bindValue(':before_id', $beforeId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = array_reverse($stmt->fetchAll());
    } else {
        // Initial load: the most recent page of the conversation.
        $stmt = $pdo->prepare(
            'SELECT m.message_id, m.sender_user_id, u.full_name AS sender_name,
                    m.message_body, m.created_at
             FROM committee_messages m
             LEFT JOIN users u ON u.id = m.sender_user_id
             WHERE m.committee_id = :cid
             ORDER BY m.message_id DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':cid', $committeeId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = array_reverse($stmt->fetchAll());
    }

    $messages = array_map(static function (array $row) use ($userId): array {
        return [
            'message_id'  => (int)$row['message_id'],
            'sender_id'   => $row['sender_user_id'] !== null ? (int)$row['sender_user_id'] : null,
            'sender_name' => $row['sender_user_id'] !== null ? $row['sender_name'] : 'Former member',
            'is_mine'     => $row['sender_user_id'] !== null && (int)$row['sender_user_id'] === $userId,
            'body'        => $row['message_body'],
            'created_at'  => $row['created_at'],
            'created_at_human' => formatDateTime($row['created_at'], 'M d, Y h:i A'),
        ];
    }, $rows);

    // Viewing the latest page (initial load or live poll) marks the
    // conversation read up to the newest message that currently exists,
    // not just the ones returned in this particular page.
    if ($beforeId === 0) {
        $maxIdStmt = $pdo->prepare('SELECT MAX(message_id) FROM committee_messages WHERE committee_id = :cid');
        $maxIdStmt->execute([':cid' => $committeeId]);
        $maxId = (int)$maxIdStmt->fetchColumn();
        if ($maxId > 0) {
            markCommitteeRead($userId, $committeeId, $maxId);
        }
    }

    jsonResponse(true, '', [
        'messages'   => $messages,
        'has_more'   => count($rows) === $limit,
    ]);
} catch (Throwable $e) {
    error_log('Message fetch error: ' . $e->getMessage());
    jsonResponse(false, 'A database error occurred while loading messages.');
}
