<?php
/**
 * modules/availability/ajax_save.php
 * ------------------------------------------------------------------
 * POST: record a new availability status for a committee member.
 * Always inserts a new history row (see includes/availability.php) —
 * never overwrites one.
 *
 * Authorization: either the member themselves (self-service — "I'm
 * going to be unavailable next week"), or a canManage() role acting on
 * their behalf (role-hierarchy revision §11 implies the Chairperson
 * needs to be able to set this too, e.g. marking someone "Emergency"
 * when they can't do it themselves).
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    jsonResponse(false, 'POST requests only.');
}
requireCsrf();

$committeeMemberId = (int)($_POST['committee_member_id'] ?? 0);
$status = clean((string)($_POST['status'] ?? ''));
$reason = clean((string)($_POST['reason'] ?? ''));
$startDate = clean((string)($_POST['start_date'] ?? ''));
$endDate = clean((string)($_POST['end_date'] ?? ''));

if ($committeeMemberId <= 0) jsonResponse(false, 'A committee member is required.');
if (!in_array($status, ['Available', 'Unavailable', 'Idle', 'Emergency'], true)) {
    jsonResponse(false, 'Invalid availability status.');
}
if (mb_strlen($reason) > 500) jsonResponse(false, 'Reason is too long (max 500 characters).');

$startDate = $startDate !== '' ? $startDate : null;
$endDate = $endDate !== '' ? $endDate : null;
foreach ([$startDate, $endDate] as $d) {
    if ($d !== null && !DateTime::createFromFormat('Y-m-d', $d)) {
        jsonResponse(false, 'Dates must be in YYYY-MM-DD format.');
    }
}
if ($startDate !== null && $endDate !== null && $endDate < $startDate) {
    jsonResponse(false, 'End date cannot be before start date.');
}

$pdo = db();
$memberCheck = $pdo->prepare(
    'SELECT cm.user_id, cm.committee_id, u.full_name
     FROM committee_members cm JOIN users u ON u.id = cm.user_id
     WHERE cm.committee_member_id = :id'
);
$memberCheck->execute([':id' => $committeeMemberId]);
$member = $memberCheck->fetch();
if (!$member) jsonResponse(false, 'Committee member not found.');

$isSelf = (int)$member['user_id'] === (int)currentUserId();
if (!$isSelf && !canManage()) {
    http_response_code(403);
    jsonResponse(false, 'You can only update your own availability.');
}

try {
    setAvailability($pdo, $committeeMemberId, $status, $reason !== '' ? $reason : null, $startDate, $endDate, (int)currentUserId());

    $who = $isSelf ? 'self' : 'by ' . (currentUser()['full_name'] ?? 'a manager');
    logActivity(
        (int)currentUserId(), 'Availability',
        $member['full_name'] . ' set to "' . $status . '" (' . $who . ')' . ($reason !== '' ? ': ' . $reason : '')
    );

    // If someone other than the member set this, let them know.
    if (!$isSelf) {
        createNotification(
            (int)$member['user_id'],
            'Your availability was updated to "' . availabilityStatusMeta($status)['label'] . '" by ' . (currentUser()['full_name'] ?? 'a Chairperson/Administrator') . '.',
            APP_URL . '/dashboard.php',
            null
        );
    }

    jsonResponse(true, 'Availability updated.', ['status' => $status]);
} catch (Throwable $e) {
    error_log('Availability save error: ' . $e->getMessage());
    jsonResponse(false, 'A database error occurred while updating availability.');
}