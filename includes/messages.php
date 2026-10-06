<?php
/**
 * includes/messages.php
 * ------------------------------------------------------------------
 * Shared helpers for the committee group-chat ("Messages") feature.
 * Access is always derived from the existing committee_members table
 * (status = 'Active') — a user only ever sees/sends messages for
 * committees they actually belong to. Mirrors the scoping pattern
 * already used by api/committees/me.php and tests/member_scope_test.php.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../config/database.php';

/** Longest a single message body is allowed to be (matches the DB column). */
const MESSAGE_MAX_LENGTH = 2000;

/** Active committee_ids the given user currently belongs to. */
function userActiveCommitteeIds(int $userId): array
{
    $stmt = db()->prepare(
        "SELECT committee_id FROM committee_members
         WHERE user_id = :uid AND status = 'Active'
         ORDER BY committee_id"
    );
    $stmt->execute([':uid' => $userId]);
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

/** True only if the user has an Active membership row for that committee. */
function isActiveCommitteeMember(int $userId, int $committeeId): bool
{
    if ($userId <= 0 || $committeeId <= 0) return false;
    $stmt = db()->prepare(
        "SELECT 1 FROM committee_members
         WHERE user_id = :uid AND committee_id = :cid AND status = 'Active' LIMIT 1"
    );
    $stmt->execute([':uid' => $userId, ':cid' => $committeeId]);
    return (bool)$stmt->fetchColumn();
}

/**
 * Enforce that the current request's user belongs to $committeeId.
 * Ends the request with a 403 JSON response otherwise (this feature is
 * AJAX-only, so a JSON response is always appropriate here).
 */
function requireCommitteeAccess(int $committeeId): void
{
    if (!isActiveCommitteeMember((int)currentUserId(), $committeeId)) {
        http_response_code(403);
        jsonResponse(false, 'You do not have access to this committee\'s conversation.');
    }
}

/** This user's saved "read up to" message id for one committee (0 = none read yet). */
function lastReadMessageId(int $userId, int $committeeId): int
{
    $stmt = db()->prepare(
        'SELECT last_read_message_id FROM committee_message_reads
         WHERE user_id = :uid AND committee_id = :cid'
    );
    $stmt->execute([':uid' => $userId, ':cid' => $committeeId]);
    $value = $stmt->fetchColumn();
    return $value !== false ? (int)$value : 0;
}

/**
 * Advance (never rewind) this user's read cursor for a committee. Safe to
 * call repeatedly, e.g. every time the thread is polled while open.
 */
function markCommitteeRead(int $userId, int $committeeId, int $uptoMessageId): void
{
    if ($userId <= 0 || $committeeId <= 0 || $uptoMessageId <= 0) return;
    $stmt = db()->prepare(
        'INSERT INTO committee_message_reads (committee_id, user_id, last_read_message_id)
         VALUES (:cid, :uid, :mid)
         ON DUPLICATE KEY UPDATE
            last_read_message_id = GREATEST(last_read_message_id, VALUES(last_read_message_id))'
    );
    $stmt->execute([':cid' => $committeeId, ':uid' => $userId, ':mid' => $uptoMessageId]);
}

/**
 * Conversation list for the message panel: one row per committee the user
 * belongs to, with the latest message preview and an unread count.
 */
function committeeConversationsForUser(int $userId): array
{
    $committeeIds = userActiveCommitteeIds($userId);
    if (!$committeeIds) return [];

    $placeholders = implode(',', array_fill(0, count($committeeIds), '?'));
    $pdo = db();

    $committeeStmt = $pdo->prepare(
        "SELECT committee_id, committee_name FROM committees
         WHERE committee_id IN ($placeholders)
         ORDER BY committee_name"
    );
    $committeeStmt->execute($committeeIds);
    $committees = $committeeStmt->fetchAll();

    // Latest message per committee (one query, not N).
    $lastMsgStmt = $pdo->prepare(
        "SELECT m.committee_id, m.message_id, m.message_body, m.created_at,
                m.sender_user_id, u.full_name AS sender_name
         FROM committee_messages m
         INNER JOIN (
             SELECT committee_id, MAX(message_id) AS max_id
             FROM committee_messages
             WHERE committee_id IN ($placeholders)
             GROUP BY committee_id
         ) latest ON latest.committee_id = m.committee_id AND latest.max_id = m.message_id
         LEFT JOIN users u ON u.id = m.sender_user_id"
    );
    $lastMsgStmt->execute($committeeIds);
    $lastByCommittee = [];
    foreach ($lastMsgStmt->fetchAll() as $row) {
        $lastByCommittee[(int)$row['committee_id']] = $row;
    }

    // Unread counts per committee in one query.
    $readsStmt = $pdo->prepare(
        'SELECT committee_id, last_read_message_id FROM committee_message_reads
         WHERE user_id = :uid'
    );
    $readsStmt->execute([':uid' => $userId]);
    $readCursor = [];
    foreach ($readsStmt->fetchAll() as $row) {
        $readCursor[(int)$row['committee_id']] = (int)$row['last_read_message_id'];
    }

    $unreadStmt = $pdo->prepare(
        'SELECT COUNT(*) FROM committee_messages
         WHERE committee_id = :cid AND message_id > :since'
    );

    $conversations = [];
    foreach ($committees as $committee) {
        $cid = (int)$committee['committee_id'];
        $since = $readCursor[$cid] ?? 0;
        $unreadStmt->execute([':cid' => $cid, ':since' => $since]);
        $unread = (int)$unreadStmt->fetchColumn();

        $last = $lastByCommittee[$cid] ?? null;

        $conversations[] = [
            'committee_id'   => $cid,
            'committee_name' => $committee['committee_name'],
            'unread_count'   => $unread,
            'last_message'   => $last ? [
                'message_id' => (int)$last['message_id'],
                'sender_name' => $last['sender_user_id'] !== null
                    ? $last['sender_name']
                    : 'Former member',
                'is_mine'     => $last['sender_user_id'] !== null && (int)$last['sender_user_id'] === $userId,
                'body'        => $last['message_body'],
                'created_at'  => $last['created_at'],
            ] : null,
        ];
    }

    // Most recently active conversation first.
    usort($conversations, static function (array $a, array $b): int {
        $aId = $a['last_message']['message_id'] ?? 0;
        $bId = $b['last_message']['message_id'] ?? 0;
        return $bId <=> $aId;
    });

    return $conversations;
}

/** Total unread messages across every committee the user belongs to (for the header badge). */
function totalUnreadMessagesForUser(int $userId): int
{
    $committeeIds = userActiveCommitteeIds($userId);
    if (!$committeeIds) return 0;

    $pdo = db();

    $readsStmt = $pdo->prepare(
        'SELECT committee_id, last_read_message_id FROM committee_message_reads WHERE user_id = ?'
    );
    $readsStmt->execute([$userId]);
    $readCursor = [];
    foreach ($readsStmt->fetchAll() as $row) {
        $readCursor[(int)$row['committee_id']] = (int)$row['last_read_message_id'];
    }

    $unreadStmt = $pdo->prepare(
        'SELECT COUNT(*) FROM committee_messages WHERE committee_id = :cid AND message_id > :since'
    );

    $total = 0;
    foreach ($committeeIds as $cid) {
        $unreadStmt->execute([':cid' => $cid, ':since' => $readCursor[$cid] ?? 0]);
        $total += (int)$unreadStmt->fetchColumn();
    }

    return $total;
}
