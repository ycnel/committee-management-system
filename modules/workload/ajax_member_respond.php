<?php
/**
 * modules/workload/ajax_member_respond.php
 * ------------------------------------------------------------------
 * POST: the assigned Committee Member accepts or declines a proposal.
 * Only the actual candidate can respond — not the Chairperson/Admin who
 * proposed it, and not any other member (server-side enforced, matches
 * role-hierarchy revision §8: "Committee Members should NOT approve
 * their own final assignment" — nor can anyone respond on their behalf).
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    jsonResponse(false, 'POST requests only.');
}
requireCsrf();

$proposalId = (int)($_POST['proposal_id'] ?? 0);
$action = clean((string)($_POST['action'] ?? ''));
$note = clean((string)($_POST['note'] ?? ''));

if ($proposalId <= 0) jsonResponse(false, 'A proposal is required.');
if (!in_array($action, ['accept', 'decline'], true)) jsonResponse(false, 'Invalid action.');
if ($action === 'decline' && mb_strlen($note) > 500) jsonResponse(false, 'Decline reason is too long (max 500 characters).');

$pdo = db();
$proposal = getProposalWithContext($pdo, $proposalId);
if (!$proposal) jsonResponse(false, 'Proposal not found.');

if ((int)$proposal['assignee_user_id'] !== (int)currentUserId()) {
    http_response_code(403);
    jsonResponse(false, 'Only the assigned member can accept or decline this proposal.');
}
if ($proposal['state'] !== 'Awaiting Response') {
    jsonResponse(false, 'This proposal has already been responded to.');
}

$newState = $action === 'accept' ? 'Accepted' : 'Declined';

try {
    $stmt = $pdo->prepare(
        'UPDATE workload_assignment_proposals
         SET state = :state, responded_at = NOW(), note = :note
         WHERE proposal_id = :id'
    );
    $stmt->execute([
        ':state' => $newState,
        ':note' => $note !== '' ? $note : null,
        ':id' => $proposalId,
    ]);

    $activityId = logActivity(
        (int)currentUserId(), $action === 'accept' ? 'Accept' : 'Decline',
        'Proposal #' . $proposalId . ' (' . $proposal['task_title'] . ') ' . $newState . ' by member'
    );

    if ($proposal['proposed_by']) {
        createNotification(
            (int)$proposal['proposed_by'],
            $proposal['assignee_name'] . ' ' . strtolower($newState) . ' the task "' . $proposal['task_title'] . '"'
                . ($action === 'accept' ? ' — ready for your final approval.' : '.'),
            APP_URL . '/modules/workload/proposal.php?id=' . $proposalId,
            $activityId
        );
    }

    jsonResponse(true, $action === 'accept'
        ? 'Accepted. The Chairperson/Administrator still needs to give final approval before this is confirmed.'
        : 'Declined.');
} catch (Throwable $e) {
    error_log('Proposal response error: ' . $e->getMessage());
    jsonResponse(false, 'A database error occurred while recording your response.');
}
