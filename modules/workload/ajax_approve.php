<?php
/**
 * modules/workload/ajax_approve.php
 * ------------------------------------------------------------------
 * POST: Chairperson/Admin final approval — the one action that turns an
 * Accepted proposal into a real, confirmed workload_assignments row.
 * The Chairperson has final human authority here; nothing upstream (AI
 * recommendation, member acceptance) can confirm an assignment by
 * itself (role-hierarchy revision §7/§9).
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

if (!canManage()) jsonResponse(false, 'You do not have permission to perform this action.');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method.');
requireCsrf();

$proposalId = (int)($_POST['proposal_id'] ?? 0);
if ($proposalId <= 0) jsonResponse(false, 'A proposal is required.');

$pdo = db();
$proposal = getProposalWithContext($pdo, $proposalId);
if (!$proposal) jsonResponse(false, 'Proposal not found.');

$result = approveProposal($pdo, $proposalId, (int)currentUserId());
if (!$result['success']) {
    jsonResponse(false, $result['message']);
}

$activityId = logActivity(
    (int)currentUserId(), 'Approve',
    'Approved proposal #' . $proposalId . ' (' . $proposal['task_title'] . ') — workload #' . $result['workload_id']
);
createNotification(
    (int)$proposal['assignee_user_id'],
    'Your assignment is confirmed: ' . $proposal['task_title'],
    APP_URL . '/modules/workload/task.php?id=' . $result['workload_id'],
    $activityId
);

jsonResponse(true, $result['message'], ['workload_id' => $result['workload_id']]);
