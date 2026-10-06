<?php
/**
 * modules/availability/ajax_history.php
 * ------------------------------------------------------------------
 * GET: current status + full history for one committee member.
 * Readable by: the member themselves, canManage() roles, or any of the
 * read/oversight roles that can already see this committee (Super
 * Admin, Pro Tempore, Vice Mayor — role-hierarchy revision Phase 1).
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    jsonResponse(false, 'GET requests only.');
}

$committeeMemberId = (int)($_GET['committee_member_id'] ?? 0);
if ($committeeMemberId <= 0) jsonResponse(false, 'A committee member is required.');

session_write_close();

$pdo = db();
$memberCheck = $pdo->prepare('SELECT user_id FROM committee_members WHERE committee_member_id = :id');
$memberCheck->execute([':id' => $committeeMemberId]);
$ownerUserId = $memberCheck->fetchColumn();
if ($ownerUserId === false) jsonResponse(false, 'Committee member not found.');

$isSelf = (int)$ownerUserId === (int)currentUserId();
if (!$isSelf && !canManage() && !isLegislativeOversight() && !isSuperAdmin()) {
    http_response_code(403);
    jsonResponse(false, 'You do not have permission to view this.');
}

try {
    $current = currentAvailability($pdo, $committeeMemberId);
    $history = array_map(static function (array $row): array {
        return [
            'availability_id' => (int)$row['availability_id'],
            'status' => $row['status'],
            'status_label' => availabilityStatusMeta($row['status'])['label'],
            'reason' => $row['reason'],
            'start_date' => $row['start_date'],
            'end_date' => $row['end_date'],
            'updated_by_name' => $row['updated_by_name'],
            'created_at' => $row['created_at'],
            'created_at_human' => timeAgo($row['created_at']),
        ];
    }, availabilityHistory($pdo, $committeeMemberId));

    jsonResponse(true, '', [
        'current' => array_merge($current, ['status_label' => availabilityStatusMeta($current['status'])['label']]),
        'history' => $history,
    ]);
} catch (Throwable $e) {
    error_log('Availability history error: ' . $e->getMessage());
    jsonResponse(false, 'A database error occurred while loading availability.');
}