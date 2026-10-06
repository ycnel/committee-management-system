<?php
/**
 * includes/workload_proposals.php
 * ------------------------------------------------------------------
 * Shared helpers for workload proposal workflows. New proposals use a
 * Pending Admin-review state before assignment creation; legacy
 * Awaiting Response / Accepted proposals continue through the member
 * acceptance and Chairperson final-approval workflow.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../config/database.php';

/** Non-terminal states — the proposal is still "in flight". */
const PROPOSAL_OPEN_STATES = ['Pending', 'Awaiting Response', 'Accepted'];

/** Terminal states — nothing further can happen to this specific row. */
const PROPOSAL_TERMINAL_STATES = ['Approved', 'Rejected', 'Declined', 'Reassigned', 'Stopped'];

/** The user_id behind a committee_member_id, or null if not found. */
function committeeMemberUserId(PDO $pdo, int $committeeMemberId): ?int
{
    $stmt = $pdo->prepare('SELECT user_id FROM committee_members WHERE committee_member_id = :id');
    $stmt->execute([':id' => $committeeMemberId]);
    $userId = $stmt->fetchColumn();
    return $userId !== false ? (int)$userId : null;
}

/** The committee_id a committee_member_id belongs to, or null. */
function committeeMemberCommitteeId(PDO $pdo, int $committeeMemberId): ?int
{
    $stmt = $pdo->prepare('SELECT committee_id FROM committee_members WHERE committee_member_id = :id');
    $stmt->execute([':id' => $committeeMemberId]);
    $committeeId = $stmt->fetchColumn();
    return $committeeId !== false ? (int)$committeeId : null;
}

/**
 * Fetch one proposal with its committee/member/jurisdiction context
 * joined in — the shape modules/workload/proposal.php and the ajax
 * action endpoints all need. Returns null if it doesn't exist.
 */
function getProposalWithContext(PDO $pdo, int $proposalId): ?array
{
    $stmt = $pdo->prepare(
        "SELECT p.*, cm.user_id AS assignee_user_id, cm.committee_id,
                u.full_name AS assignee_name, c.committee_name,
                pb.full_name AS proposed_by_name, ab.full_name AS approved_by_name
         FROM workload_assignment_proposals p
         INNER JOIN committee_members cm ON cm.committee_member_id = p.committee_member_id
         INNER JOIN users u ON u.id = cm.user_id
         INNER JOIN committees c ON c.committee_id = cm.committee_id
         LEFT JOIN users pb ON pb.id = p.proposed_by
         LEFT JOIN users ab ON ab.id = p.approved_by
         WHERE p.proposal_id = :id"
    );
    $stmt->execute([':id' => $proposalId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Links a saved (now-approved) task back to the AI recommendation log
 * row it came from (if any) and records whether the final choice of
 * member matches what the AI recommended. Non-fatal by design: the
 * caller already committed the real assignment; this is bookkeeping.
 *
 * Shared by modules/workload/ajax_save.php (editing an already-approved
 * task's assignee directly — Administrator/Chairperson power-user path)
 * and modules/workload/ajax_approve.php (the normal proposal-approval
 * path) so the two never drift out of sync with each other.
 */
function linkAiRecommendationOutcome(PDO $pdo, int $recommendationId, int $workloadId, int $finalCommitteeMemberId): void
{
    try {
        $stmt = $pdo->prepare(
            "UPDATE ai_recommendations
             SET workload_id = :wid,
                 final_member_id = :final,
                 admin_followed_ai = (ai_recommended_member_id IS NOT NULL AND ai_recommended_member_id = :final2)
             WHERE recommendation_id = :rid"
        );
        $stmt->execute([
            ':wid' => $workloadId,
            ':final' => $finalCommitteeMemberId,
            ':final2' => $finalCommitteeMemberId,
            ':rid' => $recommendationId,
        ]);
    } catch (Throwable $e) {
        error_log('AI recommendation outcome tracking failed: ' . $e->getMessage());
    }
}

/**
 * Legacy final approval: turn an Accepted proposal into a real, confirmed
 * workload_assignments row — the one and only place that table gets a
 * new row written to it as a result of this workflow. Re-runs the same
 * duplicate-task guard modules/workload/ajax_save.php uses, so a
 * proposal can still be rejected at approval time if something else
 * created a conflicting task in the meantime.
 *
 * Returns ['success' => bool, 'message' => string, 'workload_id' => ?int].
 */
function approveProposal(PDO $pdo, int $proposalId, int $approverUserId): array
{
    $proposal = getProposalWithContext($pdo, $proposalId);
    if (!$proposal) return ['success' => false, 'message' => 'Proposal not found.', 'workload_id' => null];
    if ($proposal['state'] !== 'Accepted') {
        return ['success' => false, 'message' => 'Only a proposal the member has accepted can be given final approval.', 'workload_id' => null];
    }

    $dupStmt = $pdo->prepare(
        'SELECT workload_id FROM workload_assignments
         WHERE committee_member_id = :cmid AND LOWER(TRIM(task_title)) = LOWER(TRIM(:title))
           AND ((due_date = :due_match) OR (due_date IS NULL AND :due_null IS NULL))
         LIMIT 1'
    );
    $dupStmt->execute([
        ':cmid' => $proposal['committee_member_id'], ':title' => $proposal['task_title'],
        ':due_match' => $proposal['due_date'], ':due_null' => $proposal['due_date'],
    ]);
    if ($dupStmt->fetch()) {
        return ['success' => false, 'message' => 'A task with this title is already assigned to this member for the selected due date.', 'workload_id' => null];
    }

    try {
        $pdo->beginTransaction();

        $insert = $pdo->prepare(
            'INSERT INTO workload_assignments
                (committee_member_id, task_template_id, jurisdiction_id, task_title, task_description, priority, assigned_date, due_date, created_at)
             VALUES (:cmid, :template_id, :jurisdiction_id, :title, :desc, :priority, CURDATE(), :due, NOW())'
        );
        $insert->execute([
            ':cmid' => $proposal['committee_member_id'],
            ':template_id' => $proposal['task_template_id'],
            ':jurisdiction_id' => $proposal['jurisdiction_id'],
            ':title' => $proposal['task_title'],
            ':desc' => $proposal['task_description'], ':priority' => $proposal['priority'],
            ':due' => $proposal['due_date'],
        ]);
        $workloadId = (int)$pdo->lastInsertId();

        $update = $pdo->prepare(
            'UPDATE workload_assignment_proposals
             SET state = "Approved", approved_by = :approver, approved_at = NOW(), resulting_workload_id = :wid
             WHERE proposal_id = :id'
        );
        $update->execute([':approver' => $approverUserId, ':wid' => $workloadId, ':id' => $proposalId]);

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Proposal approval error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'A database error occurred while finalizing the assignment.', 'workload_id' => null];
    }

    if ($proposal['ai_recommendation_id']) {
        linkAiRecommendationOutcome($pdo, (int)$proposal['ai_recommendation_id'], $workloadId, (int)$proposal['committee_member_id']);
    }

    return ['success' => true, 'message' => 'Assignment approved and confirmed.', 'workload_id' => $workloadId];
}
