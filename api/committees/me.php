<?php
/** Return committees visible to the authenticated user. */

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    jsonResponse(false, 'GET requests only.');
}

$pdo = db();
$isManager = canManage();
$committeeId = (int)($_GET['committee_id'] ?? 0);

if (isCommitteeMember() && $committeeId > 0) {
    $check = $pdo->prepare(
        "SELECT 1 FROM committee_members
         WHERE committee_id = :cid AND user_id = :uid AND status = 'Active' LIMIT 1"
    );
    $check->execute([':cid' => $committeeId, ':uid' => currentUserId()]);
    if (!$check->fetchColumn()) {
        http_response_code(403);
        jsonResponse(false, 'You do not have access to this committee.');
    }
}

$where = [];
$params = [];
if ($committeeId > 0) {
    $where[] = 'c.committee_id = :cid';
    $params[':cid'] = $committeeId;
}
if (isCommitteeMember()) {
    $where[] = "c.committee_id IN
        (SELECT committee_id FROM committee_members WHERE user_id = :uid AND status = 'Active')";
    $params[':uid'] = currentUserId();
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$committeeStmt = $pdo->prepare(
    "SELECT c.committee_id, c.committee_name, c.description, c.status,
            c.jurisdiction_id, j.jurisdiction_name
     FROM committees c
     LEFT JOIN jurisdictions j ON j.jurisdiction_id = c.jurisdiction_id
     $whereSql
     ORDER BY c.committee_name"
);
$committeeStmt->execute($params);
$committees = $committeeStmt->fetchAll();

$visibleCommitteeIds = array_map(static fn(array $committee): int => (int)$committee['committee_id'], $committees);
if (!$visibleCommitteeIds) {
        jsonResponse(true, '', ['committees' => []]);
}
$memberPlaceholders = implode(',', array_fill(0, count($visibleCommitteeIds), '?'));
$membersStmt = $pdo->prepare(
    "SELECT cm.committee_id, cm.user_id, cm.member_role, cm.assigned_date,
            u.full_name, u.email
     FROM committee_members cm
     INNER JOIN users u ON u.id = cm.user_id
     WHERE cm.status = 'Active'
             AND cm.committee_id IN ($memberPlaceholders)
         ORDER BY cm.committee_id, u.full_name"
);
$membersStmt->execute($visibleCommitteeIds);
$membersByCommittee = [];
foreach ($membersStmt->fetchAll() as $member) {
    $membersByCommittee[(int)$member['committee_id']][] = $member;
}

foreach ($committees as &$committee) {
    $committee['members'] = $membersByCommittee[(int)$committee['committee_id']] ?? [];
    $committee['my_role'] = null;
    foreach ($committee['members'] as $member) {
        if ((int)$member['user_id'] === (int)currentUserId()) {
            $committee['my_role'] = $member['member_role'];
            break;
        }
    }
}
unset($committee);

jsonResponse(true, '', ['committees' => $committees]);
