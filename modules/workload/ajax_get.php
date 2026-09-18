<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) jsonResponse(false, 'Invalid task id.');

$pdo = db();
$stmt = $pdo->prepare(
    'SELECT wa.*, cm.committee_id, cm.user_id AS assigned_user_id
     FROM workload_assignments wa
     INNER JOIN committee_members cm ON cm.committee_member_id = wa.committee_member_id
     WHERE wa.workload_id = :id'
);
$stmt->execute([':id' => $id]);
$task = $stmt->fetch();

if (!$task) jsonResponse(false, 'Task not found.');

// Committee Member may only view details of tasks assigned to them.
if (isCommitteeMember() && (int)$task['assigned_user_id'] !== currentUserId()) {
    jsonResponse(false, 'You do not have permission to view this task.');
}

jsonResponse(true, '', ['task' => $task]);
