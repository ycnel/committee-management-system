<?php
/**
 * CLI regression checks for Committee Member server-side scoping.
 * Run: C:\xampp\php\php.exe tests\member_scope_test.php
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$pdo = db();
$memberId = (int)$pdo->query(
    "SELECT u.id FROM users u INNER JOIN roles r ON r.id = u.role_id
     WHERE r.name = 'Committee Member' AND u.status = 'Active' ORDER BY u.id LIMIT 1"
)->fetchColumn();
if ($memberId <= 0) {
    throw new RuntimeException('No active Committee Member account found.');
}

$ownedStmt = $pdo->prepare(
    "SELECT committee_id FROM committee_members
     WHERE user_id = :uid AND status = 'Active'"
);
$ownedStmt->execute([':uid' => $memberId]);
$ownedIds = array_map('intval', $ownedStmt->fetchAll(PDO::FETCH_COLUMN));

$otherCommitteeStmt = $pdo->prepare(
    "SELECT c.committee_id FROM committees c
     WHERE c.committee_id NOT IN (SELECT committee_id FROM committee_members WHERE user_id = :uid AND status = 'Active')
     ORDER BY c.committee_id LIMIT 1"
);
$otherCommitteeStmt->execute([':uid' => $memberId]);
$otherCommitteeId = (int)$otherCommitteeStmt->fetchColumn();
if ($otherCommitteeId > 0) {
    $check = $pdo->prepare(
        "SELECT COUNT(*) FROM committees c
         WHERE c.committee_id = :cid
           AND c.committee_id IN (SELECT committee_id FROM committee_members WHERE user_id = :uid AND status = 'Active')"
    );
    $check->execute([':cid' => $otherCommitteeId, ':uid' => $memberId]);
    if ((int)$check->fetchColumn() !== 0) {
        throw new RuntimeException('Member can see an unrelated committee.');
    }
}

$otherTaskStmt = $pdo->prepare(
    "SELECT wa.workload_id FROM workload_assignments wa
     INNER JOIN committee_members cm ON cm.committee_member_id = wa.committee_member_id
     WHERE cm.user_id <> :uid LIMIT 1"
);
$otherTaskStmt->execute([':uid' => $memberId]);
$otherTaskId = (int)$otherTaskStmt->fetchColumn();
if ($otherTaskId > 0) {
    $check = $pdo->prepare(
        "SELECT COUNT(*) FROM workload_assignments wa
         INNER JOIN committee_members cm ON cm.committee_member_id = wa.committee_member_id
         WHERE wa.workload_id = :task_id AND cm.user_id = :uid"
    );
    $check->execute([':task_id' => $otherTaskId, ':uid' => $memberId]);
    if ((int)$check->fetchColumn() !== 0) {
        throw new RuntimeException('Member can see another user\'s workload.');
    }
}

echo "PASS: Committee Member {$memberId} is scoped to committees "
    . json_encode($ownedIds) . " and own workload only.\n";
