<?php
/**
 * modules/workload/ajax_reassign.php
 * ------------------------------------------------------------------
 * POST: Chairperson/Admin reassigns a proposal to a different candidate.
 * The old row is retained in the proposal chain and the new candidate
 * selection is sent to the committee chairperson / Administrator review queue.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/task_approval.php';
requireLogin();

if (!canManage()) jsonResponse(false, 'You do not have permission to perform this action.');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method.');
requireCsrf();

$proposalId = (int)($_POST['proposal_id'] ?? 0);
$newCommitteeMemberId = (int)($_POST['committee_member_id'] ?? 0);
if ($proposalId <= 0) jsonResponse(false, 'A proposal is required.');
if ($newCommitteeMemberId <= 0) jsonResponse(false, 'Please select who to reassign this task to.');

$pdo = db();
$proposal = getProposalWithContext($pdo, $proposalId);
if (!$proposal) jsonResponse(false, 'Proposal not found.');

if (in_array($proposal['state'], ['Pending', 'Approved', 'Rejected', 'Reassigned', 'Stopped'], true)) {
    jsonResponse(false, 'This proposal can no longer be reassigned (already ' . $proposal['state'] . ').');
}
if ($newCommitteeMemberId === (int)$proposal['committee_member_id']) {
    jsonResponse(false, 'Choose a different member to reassign to.');
}

$memberCheck = $pdo->prepare(
    'SELECT committee_member_id, user_id, committee_id FROM committee_members WHERE committee_member_id = :id AND status = "Active"'
);
$memberCheck->execute([':id' => $newCommitteeMemberId]);
$newMember = $memberCheck->fetch();
if (!$newMember) jsonResponse(false, 'Selected committee member is invalid or inactive.');
if ((int)$newMember['committee_id'] !== (int)$proposal['committee_id']) {
    jsonResponse(false, 'The new candidate must belong to the same committee.');
}

try {
    $pdo->beginTransaction();

    // Leave a Declined row as Declined (accurate history); otherwise mark
    // the previous candidate's proposal as superseded.
    if ($proposal['state'] !== 'Declined') {
        $close = $pdo->prepare('UPDATE workload_assignment_proposals SET state = "Reassigned" WHERE proposal_id = :id');
        $close->execute([':id' => $proposalId]);
    }

    $insert = $pdo->prepare(
        'INSERT INTO workload_assignment_proposals
         (committee_member_id, task_title, task_description, priority, due_date, ai_recommendation_id, proposed_by, proposed_at, state, previous_proposal_id)
         VALUES (:cmid, :title, :desc, :priority, :due, NULL, :proposed_by, NOW(), "Pending", :previous)'
    );
    $insert->execute([
        ':cmid' => $newCommitteeMemberId, ':title' => $proposal['task_title'], ':desc' => $proposal['task_description'],
        ':priority' => $proposal['priority'], ':due' => $proposal['due_date'],
        ':proposed_by' => currentUserId(), ':previous' => $proposalId,
    ]);
    $newProposalId = (int)$pdo->lastInsertId();

    $activityId = logActivity(
        (int)currentUserId(), 'Reassign',
        'Reassigned "' . $proposal['task_title'] . '" from proposal #' . $proposalId . ' to ' . $newMember['user_id'] . ' (new proposal #' . $newProposalId . ') for committee chairperson or Administrator review'
    );
    $reviewerIds = taskApprovalReviewRecipients($pdo, (int)$proposal['committee_id']);
    if (!$reviewerIds) {
        throw new RuntimeException('No active Administrator or Committee Chairperson is available to review task proposals.');
    }

    $notificationUrl = APP_URL . '/modules/workload/task_requests.php?request_id=' . $newProposalId;
    foreach ($reviewerIds as $reviewerId) {
        createNotification(
            $reviewerId,
            'Pending Task Request #' . $newProposalId . ': ' . $proposal['task_title']
                . ' was submitted by ' . (currentUser()['full_name'] ?? 'a user') . ' for review by the committee chairperson or Administrator.',
            $notificationUrl,
            $activityId
        );
        $notificationCheck = $pdo->prepare(
            'SELECT 1 FROM notifications
             WHERE recipient_user_id = :recipient_id
               AND activity_log_id <=> :activity_id AND url = :url
             LIMIT 1'
        );
        $notificationCheck->execute([
            ':recipient_id' => $reviewerId,
            ':activity_id' => $activityId,
            ':url' => $notificationUrl,
        ]);
        if (!$notificationCheck->fetchColumn()) {
            throw new RuntimeException('A task reviewer notification could not be created for this proposal.');
        }
    }
    $pdo->commit();
} catch (RuntimeException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    jsonResponse(false, $e->getMessage());
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Proposal reassign error: ' . $e->getMessage());
    jsonResponse(false, 'A database error occurred while reassigning this task.');
}

jsonResponse(true, 'Reassignment submitted for committee chairperson or Administrator review.', ['proposal_id' => $newProposalId]);
