<?php
/** Return workload items visible to the authenticated user. */

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    jsonResponse(false, 'GET requests only.');
}

$pdo = db();
$requestedUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
$requestedCommitteeId = (int)($_GET['committee_id'] ?? 0);

if (isCommitteeMember() && $requestedUserId !== null && $requestedUserId !== currentUserId()) {
    http_response_code(403);
    jsonResponse(false, 'You can only view your own workload.');
}

$where = [];
$params = [];
if (isCommitteeMember()) {
    $where[] = 'cm.user_id = :uid';
    $params[':uid'] = currentUserId();
} elseif ($requestedUserId !== null && $requestedUserId > 0) {
    $where[] = 'cm.user_id = :requested_uid';
    $params[':requested_uid'] = $requestedUserId;
}
if ($requestedCommitteeId > 0) {
    $where[] = 'cm.committee_id = :cid';
    $params[':cid'] = $requestedCommitteeId;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare(
    "SELECT wa.workload_id, wa.task_title, wa.task_description, wa.priority,
            wa.assigned_date, wa.due_date,
            wa.completion_date, wa.status, cm.committee_id,
            c.committee_name, cm.user_id AS assigned_user_id
     FROM workload_assignments wa
     INNER JOIN committee_members cm ON cm.committee_member_id = wa.committee_member_id
     INNER JOIN committees c ON c.committee_id = cm.committee_id
     $whereSql
     ORDER BY (wa.due_date IS NULL), wa.due_date ASC, wa.created_at DESC"
);
$stmt->execute($params);

jsonResponse(true, '', ['workload' => $stmt->fetchAll()]);
