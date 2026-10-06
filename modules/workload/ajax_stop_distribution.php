<?php
/**
 * modules/workload/ajax_stop_distribution.php
 * ------------------------------------------------------------------
 * POST: Chairperson/Admin stops distribution of a proposal entirely —
 * no further candidate will be sought through this proposal chain. The
 * task is not deleted (nothing to delete — no workload_assignments row
 * was ever created for it); the proposal is simply marked terminal.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

if (!canManage()) jsonResponse(false, 'You do not have permission to perform this action.');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method.');
requireCsrf();

$proposalId = (int)($_POST['proposal_id'] ?? 0);
$note = clean((string)($_POST['note'] ?? ''));
if ($proposalId <= 0) jsonResponse(false, 'A proposal is required.');
if (mb_strlen($note) > 500) jsonResponse(false, 'Note is too long (max 500 characters).');

$pdo = db();
$proposal = getProposalWithContext($pdo, $proposalId);
if (!$proposal) jsonResponse(false, 'Proposal not found.');

if (in_array($proposal['state'], ['Pending', 'Approved', 'Rejected', 'Reassigned', 'Stopped'], true)) {
    jsonResponse(false, 'This proposal can no longer be stopped (already ' . $proposal['state'] . ').');
}

try {
    $stmt = $pdo->prepare(
        'UPDATE workload_assignment_proposals SET state = "Stopped", note = :note WHERE proposal_id = :id'
    );
    $stmt->execute([':note' => $note !== '' ? $note : null, ':id' => $proposalId]);

    logActivity(
        (int)currentUserId(), 'Stop Distribution',
        'Stopped distribution for "' . $proposal['task_title'] . '" (proposal #' . $proposalId . ')'
    );

    jsonResponse(true, 'Distribution stopped for this task.');
} catch (Throwable $e) {
    error_log('Stop distribution error: ' . $e->getMessage());
    jsonResponse(false, 'A database error occurred.');
}
