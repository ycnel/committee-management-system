<?php
/**
 * modules/workload/ajax_save.php
 * ------------------------------------------------------------------
 * Handles assignment updates and task proposal creation (id=0). New
 * proposals wait for committee chairperson or Administrator review
 * before a workload assignment is created.
 * is created. This is the "Calculate Workload" write path:
 * assignment fields stay focused on title, description, priority, dates,
 * and the assigned committee member.
 *
 * All values are re-validated here regardless of where they came from
 * (typed manually, or auto-filled by "Generate with AI") — nothing
 * the AI produced is trusted without going through the same checks
 * a manually-typed value would.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/task_approval.php';
requireLogin();

if (!canManage()) jsonResponse(false, 'You do not have permission to perform this action.');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method.');
requireCsrf();

$id                = (int)($_POST['id'] ?? 0);
$committeeMemberId = (int)($_POST['committee_member_id'] ?? 0);
$title             = clean($_POST['task_title'] ?? '');
$description       = clean($_POST['task_description'] ?? '');
$priority          = clean($_POST['priority'] ?? 'Medium');
$dueDate           = clean($_POST['due_date'] ?? '') ?: null;
$aiRecommendationId = (int)($_POST['ai_recommendation_id'] ?? 0) ?: null;

$allowedPriority = ['Low', 'Medium', 'High', 'Urgent'];

$errors = [];
if ($committeeMemberId <= 0) $errors[] = 'Please select who this task is assigned to.';
if ($title === '') $errors[] = 'Task title is required.';
if (!in_array($priority, $allowedPriority, true)) $errors[] = 'Invalid priority value.';
if ($dueDate !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate)) $errors[] = 'Invalid due date.';
if (!empty($errors)) jsonResponse(false, implode(' ', $errors));

$pdo = db();

$committeeId = (int)($_POST['committee_id'] ?? 0);
$jurisdictionId = (int)($_POST['jurisdiction_id'] ?? 0);
$taskTemplateId = (int)($_POST['task_template_id'] ?? 0);

if ($id <= 0) {
    $committeeJurisdictionCheck = $pdo->prepare(
        "SELECT 1
         FROM committees c
         INNER JOIN jurisdictions j
                 ON j.jurisdiction_id = :jid
                AND j.status = 'Active'
         WHERE c.committee_id = :cid
           AND c.status = 'Active'
           AND (c.jurisdiction_id = j.jurisdiction_id OR j.category = c.committee_name)
         LIMIT 1"
    );
    $committeeJurisdictionCheck->execute([
        ':cid' => $committeeId,
        ':jid' => $jurisdictionId,
    ]);
    if (!$committeeJurisdictionCheck->fetchColumn()) {
        jsonResponse(false, 'The selected committee does not belong to the selected jurisdiction.');
    }

    if ($taskTemplateId <= 0) {
        jsonResponse(false, 'Please select a standard task template.');
    }
    $templateCheck = $pdo->prepare(
        'SELECT 1 FROM task_templates
         WHERE id = :id AND is_active = 1
           AND (jurisdiction_id IS NULL OR jurisdiction_id = :jurisdiction_id)
         LIMIT 1'
    );
    $templateCheck->execute([
        ':id' => $taskTemplateId,
        ':jurisdiction_id' => $jurisdictionId,
    ]);
    if (!$templateCheck->fetchColumn()) {
        jsonResponse(false, 'The selected standard task is not available for this jurisdiction.');
    }
}

$memberCheckSql = 'SELECT committee_member_id, user_id, committee_id FROM committee_members WHERE committee_member_id = :id AND status = \'Active\'';
$memberCheckParams = [':id' => $committeeMemberId];
if ($id <= 0) {
    $memberCheckSql .= ' AND committee_id = :committee_id';
    $memberCheckParams[':committee_id'] = $committeeId;
}
$memberCheck = $pdo->prepare($memberCheckSql);
$memberCheck->execute($memberCheckParams);
$member = $memberCheck->fetch();
if (!$member) jsonResponse(false, 'Selected committee member is invalid or inactive.');

$duplicateTaskStmt = $pdo->prepare(
    'SELECT workload_id
     FROM workload_assignments
     WHERE committee_member_id = :committee_member_id
       AND LOWER(TRIM(task_title)) = LOWER(TRIM(:task_title))
       AND ((due_date = :due_date_match) OR (due_date IS NULL AND :due_date_null IS NULL))
       AND workload_id != :workload_id
     LIMIT 1'
);
$duplicateTaskStmt->execute([
    ':committee_member_id' => $committeeMemberId,
    ':task_title' => $title,
    ':due_date_match' => $dueDate,
    ':due_date_null' => $dueDate,
    ':workload_id' => $id,
]);
if ($duplicateTaskStmt->fetch()) {
    jsonResponse(false, 'A task with this title is already assigned to this member for the selected due date.');
}

// CREATE only: also block a duplicate *proposal* (same title/member/due
// date already awaiting response or accepted) — the check above only
// catches confirmed workload_assignments, which a brand-new proposal
// obviously isn't yet.
if ($id <= 0) {
    $duplicateProposalStmt = $pdo->prepare(
        'SELECT proposal_id FROM workload_assignment_proposals
         WHERE committee_member_id = :committee_member_id
           AND LOWER(TRIM(task_title)) = LOWER(TRIM(:task_title))
           AND ((due_date = :due_date_match) OR (due_date IS NULL AND :due_date_null IS NULL))
           AND state IN ("Pending", "Awaiting Response", "Accepted")
         LIMIT 1'
    );
    $duplicateProposalStmt->execute([
        ':committee_member_id' => $committeeMemberId,
        ':task_title' => $title,
        ':due_date_match' => $dueDate,
        ':due_date_null' => $dueDate,
    ]);
    if ($duplicateProposalStmt->fetch()) {
        jsonResponse(false, 'This member already has a pending proposal with this title and due date.');
    }
}

/**
 * Links this saved task back to the AI recommendation log row (if the
 * task was created via "Generate with AI") and records whether the
 * admin's final choice of member matches what the AI recommended.
 * Non-fatal: the task itself always saves even if this bookkeeping
 * step fails for any reason.
 *
 * Thin wrapper kept here for the UPDATE branch below (editing the
 * assignee of an already-approved task directly, bypassing the
 * proposal workflow — an existing Administrator/Chairperson power-user
 * path this revision doesn't change). The proposal-approval path
 * (modules/workload/ajax_approve.php) calls the same shared
 * includes/workload_proposals.php::linkAiRecommendationOutcome()
 * directly instead of duplicating this logic.
 */
function cmas_link_ai_recommendation(PDO $pdo, int $recommendationId, int $workloadId, int $finalCommitteeMemberId): void
{
    linkAiRecommendationOutcome($pdo, $recommendationId, $workloadId, $finalCommitteeMemberId);
}

try {
    if ($id > 0) {
        $before = $pdo->prepare(
            'SELECT cm.user_id
             FROM workload_assignments wa
             INNER JOIN committee_members cm ON cm.committee_member_id = wa.committee_member_id
             WHERE wa.workload_id = :id'
        );
        $before->execute([':id' => $id]);
        $beforeRow = $before->fetch();
        if (!$beforeRow) jsonResponse(false, 'Task not found.');

        $stmt = $pdo->prepare(
            "UPDATE workload_assignments SET committee_member_id = :cmid, task_title = :title, task_description = :desc,
            priority = :priority, due_date = :due
             WHERE workload_id = :id"
        );
        $execParams = [
            ':cmid' => $committeeMemberId, ':title' => $title, ':desc' => $description,
            ':priority' => $priority, ':due' => $dueDate,
            ':id' => $id,
        ];
        $stmt->execute($execParams);

        if ($aiRecommendationId) {
            try {
                cmas_link_ai_recommendation($pdo, $aiRecommendationId, $id, $committeeMemberId);
            } catch (Throwable $e) {
                error_log('AI recommendation outcome tracking failed: ' . $e->getMessage());
            }
        }

        $activityId = logActivity(currentUserId(), 'Update', 'Updated task #' . $id . ' (' . $title . ')');
        createNotification(
            (int)$member['user_id'],
            'Task updated: ' . $title,
            APP_URL . '/modules/workload/task.php?id=' . $id,
            $activityId
        );
        if ((int)$beforeRow['user_id'] !== (int)$member['user_id']) {
            createNotification(
                (int)$beforeRow['user_id'],
                'Task reassigned: ' . $title,
                APP_URL . '/modules/workload/index.php',
                $activityId
            );
        }
        jsonResponse(true, 'Task updated successfully.', ['id' => $id]);
    }

    // ---- CREATE ----
    // New task proposals require review before an assignment is created.
    // Existing member-response proposals retain their established workflow.
    $pdo->beginTransaction();
    $stmt = $pdo->prepare(
        'INSERT INTO workload_assignment_proposals
         (committee_member_id, task_template_id, jurisdiction_id, task_title, task_description, priority, due_date, ai_recommendation_id, proposed_by, proposed_at, state)
         VALUES (:cmid, :template_id, :jurisdiction_id, :title, :desc, :priority, :due, :ai_rec, :proposed_by, NOW(), "Pending")'
    );
    $stmt->execute([
        ':cmid' => $committeeMemberId, ':template_id' => $taskTemplateId,
        ':jurisdiction_id' => $jurisdictionId, ':title' => $title, ':desc' => $description,
        ':priority' => $priority, ':due' => $dueDate,
        ':ai_rec' => $aiRecommendationId, ':proposed_by' => currentUserId(),
    ]);
    $proposalId = (int)$pdo->lastInsertId();

    $activityId = logActivity(
        currentUserId(),
        'Submit Task Proposal',
        'Submitted task proposal #' . $proposalId . ' (' . $title . ') for committee chairperson or Administrator review'
    );
    $reviewerIds = taskApprovalReviewRecipients($pdo, (int)$member['committee_id']);
    if (!$reviewerIds) {
        throw new RuntimeException('No active Administrator or Committee Chairperson is available to review task proposals.');
    }
    $notificationUrl = APP_URL . '/modules/workload/task_requests.php?request_id=' . $proposalId;
    foreach ($reviewerIds as $reviewerId) {
        createNotification(
            $reviewerId,
            'Pending Task Request #' . $proposalId . ': ' . $title . ' was submitted by '
                . (currentUser()['full_name'] ?? 'a user') . ' for review by the committee chairperson or Administrator.',
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
    jsonResponse(true, 'Task proposal submitted and is pending review by the committee chairperson or Administrator.', ['proposal_id' => $proposalId]);

} catch (RuntimeException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    jsonResponse(false, $e->getMessage());
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Workload save error: ' . $e->getMessage());
    if ((int)$e->getCode() === 23000 || str_contains(strtolower($e->getMessage()), 'uq_workload_member_task_due')) {
        jsonResponse(false, 'A task with this title is already assigned to this member for the selected due date.');
    }
    jsonResponse(false, 'A database error occurred while saving the task.');
}
