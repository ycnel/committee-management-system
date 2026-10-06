<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/task_approval.php';
requireLogin();

if (!isAdmin() && currentRole() !== ROLE_STAFF) {
    jsonResponse(false, 'Only an Administrator or the assigned Committee Chairperson can review task proposals.');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method.');
requireCsrf();

$proposalId = (int)($_POST['proposal_id'] ?? 0);
$decision = clean($_POST['decision'] ?? '');
$adminResponse = clean($_POST['admin_response'] ?? '');
if ($proposalId <= 0) jsonResponse(false, 'A task proposal is required.');
if (!in_array($decision, ['Approved', 'Rejected'], true)) jsonResponse(false, 'Invalid review decision.');
if (mb_strlen($adminResponse) > 5000) jsonResponse(false, 'The response must not exceed 5,000 characters.');

$pdo = db();
try {
    $pdo->beginTransaction();
    $lock = $pdo->prepare(
        'SELECT proposal_id FROM workload_assignment_proposals
         WHERE proposal_id = :id FOR UPDATE'
    );
    $lock->execute([':id' => $proposalId]);
    if (!$lock->fetchColumn()) {
        $pdo->rollBack();
        jsonResponse(false, 'Task proposal not found.');
    }

    $proposal = getProposalWithContext($pdo, $proposalId);
    if (!$proposal) {
        $pdo->rollBack();
        jsonResponse(false, 'Task proposal context is unavailable.');
    }
    if (!taskApprovalChairCanReview($pdo, (int)$proposal['committee_id'])) {
        $pdo->rollBack();
        jsonResponse(false, 'You can only review task proposals for committees you chair.');
    }
    if ($proposal['state'] !== 'Pending') {
        $pdo->rollBack();
        jsonResponse(false, 'This task proposal has already been processed.');
    }

    $workloadId = null;
    if ($decision === 'Approved') {
        $duplicate = $pdo->prepare(
            'SELECT workload_id FROM workload_assignments
             WHERE committee_member_id = :member_id
               AND LOWER(TRIM(task_title)) = LOWER(TRIM(:title))
               AND ((due_date = :due_match) OR (due_date IS NULL AND :due_null IS NULL))
             LIMIT 1'
        );
        $duplicate->execute([
            ':member_id' => (int)$proposal['committee_member_id'],
            ':title' => $proposal['task_title'],
            ':due_match' => $proposal['due_date'],
            ':due_null' => $proposal['due_date'],
        ]);
        if ($duplicate->fetchColumn()) {
            $pdo->rollBack();
            jsonResponse(false, 'A task with this title is already assigned to this member for the selected due date.');
        }

        $memberCheck = $pdo->prepare(
            "SELECT 1 FROM committee_members
             WHERE committee_member_id = :id AND status = 'Active' FOR UPDATE"
        );
        $memberCheck->execute([':id' => (int)$proposal['committee_member_id']]);
        if (!$memberCheck->fetchColumn()) {
            $pdo->rollBack();
            jsonResponse(false, 'The proposed committee member is no longer active; update the proposal before approving.');
        }

        $insert = $pdo->prepare(
            'INSERT INTO workload_assignments
                (committee_member_id, task_template_id, jurisdiction_id, task_title, task_description, priority, assigned_date, due_date, created_at)
             VALUES (:member_id, :template_id, :jurisdiction_id, :title, :description, :priority, CURDATE(), :due_date, NOW())'
        );
        $insert->execute([
            ':member_id' => (int)$proposal['committee_member_id'],
            ':template_id' => $proposal['task_template_id'],
            ':jurisdiction_id' => $proposal['jurisdiction_id'],
            ':title' => $proposal['task_title'],
            ':description' => $proposal['task_description'],
            ':priority' => $proposal['priority'],
            ':due_date' => $proposal['due_date'],
        ]);
        $workloadId = (int)$pdo->lastInsertId();

        $update = $pdo->prepare(
            "UPDATE workload_assignment_proposals
             SET state = 'Approved', approved_by = :admin_id, approved_at = NOW(),
                 resulting_workload_id = :workload_id, admin_response = :response
             WHERE proposal_id = :id AND state = 'Pending'"
        );
        $update->execute([
            ':admin_id' => (int)currentUserId(),
            ':workload_id' => $workloadId,
            ':response' => $adminResponse !== '' ? $adminResponse : null,
            ':id' => $proposalId,
        ]);
        $action = 'Approve Task Proposal';
        $details = 'Approved task proposal #' . $proposalId . ' (' . $proposal['task_title']
            . ') and created assignment #' . $workloadId;
        $notification = 'Your task proposal "' . $proposal['task_title']
            . '" was approved and assigned to ' . $proposal['assignee_name'] . '.';
        $notificationUrl = APP_URL . '/modules/workload/task.php?id=' . $workloadId;
    } else {
        $update = $pdo->prepare(
            "UPDATE workload_assignment_proposals
             SET state = 'Rejected', rejected_by = :admin_id, rejected_at = NOW(),
                 admin_response = :response
             WHERE proposal_id = :id AND state = 'Pending'"
        );
        $update->execute([
            ':admin_id' => (int)currentUserId(),
            ':response' => $adminResponse !== '' ? $adminResponse : null,
            ':id' => $proposalId,
        ]);
        $action = 'Reject Task Proposal';
        $details = 'Rejected task proposal #' . $proposalId . ' (' . $proposal['task_title'] . ')';
        $notification = 'Your task proposal "' . $proposal['task_title'] . '" was rejected.'
            . ($adminResponse !== '' ? ' Reviewer response: ' . $adminResponse : '');
        $notificationUrl = APP_URL . '/modules/workload/task_requests.php?status=all&request_id=' . $proposalId;
    }
    if ($update->rowCount() !== 1) {
        $pdo->rollBack();
        jsonResponse(false, 'This task proposal has already been processed.');
    }

    $activityId = logActivity(currentUserId(), $action, $details);
    $adminRequestUrl = APP_URL . '/modules/workload/task_requests.php?request_id=' . $proposalId;
    $updateReviewNotifications = $pdo->prepare(
        "UPDATE notifications
         SET message = REPLACE(message, 'Pending Task Request', :status_prefix)
         WHERE url = :url"
    );
    $updateReviewNotifications->execute([
        ':status_prefix' => $decision . ' Task Request',
        ':url' => $adminRequestUrl,
    ]);
    $updateReviewNotificationStatus = $pdo->prepare(
        "UPDATE notifications
         SET message = REPLACE(
             REPLACE(message, 'for review by the committee chairperson or Administrator.', :new_status_suffix),
             'for Administrator review.', :legacy_status_suffix
         )
         WHERE url = :url"
    );
    $updateReviewNotificationStatus->execute([
        ':new_status_suffix' => 'was ' . strtolower($decision) . '.',
        ':legacy_status_suffix' => 'was ' . strtolower($decision) . '.',
        ':url' => $adminRequestUrl,
    ]);
    if ($proposal['proposed_by']) {
        createNotification(
            (int)$proposal['proposed_by'],
            $notification,
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
            ':recipient_id' => (int)$proposal['proposed_by'],
            ':activity_id' => $activityId,
            ':url' => $notificationUrl,
        ]);
        if (!$notificationCheck->fetchColumn()) {
            throw new RuntimeException('The requester notification could not be created.');
        }
    }
    if (
        $decision === 'Approved'
        && (int)$proposal['assignee_user_id'] !== (int)$proposal['proposed_by']
    ) {
        $assigneeUrl = APP_URL . '/modules/workload/task.php?id=' . $workloadId;
        createNotification(
            (int)$proposal['assignee_user_id'],
            'A new task was assigned to you: ' . $proposal['task_title'],
            $assigneeUrl,
            $activityId
        );
        $assigneeNotificationCheck = $pdo->prepare(
            'SELECT 1 FROM notifications
             WHERE recipient_user_id = :recipient_id
               AND activity_log_id <=> :activity_id AND url = :url
             LIMIT 1'
        );
        $assigneeNotificationCheck->execute([
            ':recipient_id' => (int)$proposal['assignee_user_id'],
            ':activity_id' => $activityId,
            ':url' => $assigneeUrl,
        ]);
        if (!$assigneeNotificationCheck->fetchColumn()) {
            throw new RuntimeException('The assigned member notification could not be created.');
        }
    }

    $pdo->commit();
    if ($decision === 'Approved' && $proposal['ai_recommendation_id']) {
        linkAiRecommendationOutcome(
            $pdo,
            (int)$proposal['ai_recommendation_id'],
            (int)$workloadId,
            (int)$proposal['committee_member_id']
        );
    }
    jsonResponse(
        true,
        $decision === 'Approved' ? 'Task proposal approved and assignment created.' : 'Task proposal rejected.',
        ['workload_id' => $workloadId]
    );
} catch (RuntimeException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    jsonResponse(false, $e->getMessage());
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Task proposal review error: ' . $e->getMessage());
    jsonResponse(false, 'A database error occurred while processing the task proposal.');
}
