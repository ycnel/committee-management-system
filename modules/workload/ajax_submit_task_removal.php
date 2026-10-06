<?php
require_once __DIR__ . '/../../includes/task_approval.php';
requireLogin();

if (!isCommitteeMember()) jsonResponse(false, 'Only Committee Members can request removal of their assigned tasks.');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method.');
requireCsrf();

$workloadId = (int)($_POST['workload_id'] ?? 0);
$reason = trim((string)($_POST['reason'] ?? ''));
if ($workloadId <= 0) jsonResponse(false, 'A valid assigned task is required.');
if ($reason === '') jsonResponse(false, 'A reason for requesting task removal is required.');
if (mb_strlen($reason) > 10000) jsonResponse(false, 'The removal reason must not exceed 10,000 characters.');

$pdo = db();
try {
    $pdo->beginTransaction();
    $taskStmt = $pdo->prepare(
        "SELECT wa.workload_id, wa.task_title, wa.status, wa.completion_date,
                cm.user_id, cm.committee_id, c.committee_name,
                COALESCE(wj.jurisdiction_name, cj.jurisdiction_name,
                    (SELECT j2.jurisdiction_name
                     FROM jurisdictions j2
                     WHERE j2.category = c.committee_name
                     ORDER BY j2.jurisdiction_name
                     LIMIT 1)
                ) AS jurisdiction_name
         FROM workload_assignments wa
         INNER JOIN committee_members cm ON cm.committee_member_id = wa.committee_member_id
         INNER JOIN committees c ON c.committee_id = cm.committee_id
         LEFT JOIN jurisdictions wj ON wj.jurisdiction_id = wa.jurisdiction_id
         LEFT JOIN jurisdictions cj ON cj.jurisdiction_id = c.jurisdiction_id
         WHERE wa.workload_id = :id AND cm.user_id = :user_id AND cm.status = 'Active'
         FOR UPDATE"
    );
    $taskStmt->execute([':id' => $workloadId, ':user_id' => currentUserId()]);
    $task = $taskStmt->fetch();
    if (!$task) {
        $pdo->rollBack();
        jsonResponse(false, 'This task is not assigned to your active committee membership.');
    }
    if ($task['completion_date'] !== null || $task['status'] === 'Completed') {
        $pdo->rollBack();
        jsonResponse(false, 'A completed task cannot be requested for removal.');
    }

    foreach (['task_completion_requests', 'task_removal_requests'] as $requestTable) {
        $pending = $pdo->prepare("SELECT 1 FROM {$requestTable} WHERE workload_id = :workload_id AND status = 'Pending' LIMIT 1");
        $pending->execute([':workload_id' => $workloadId]);
        if ($pending->fetchColumn()) {
            $pdo->rollBack();
            jsonResponse(false, 'This task already has a request awaiting review.');
        }
    }

    $insert = $pdo->prepare(
        "INSERT INTO task_removal_requests
            (workload_id, submitted_by, task_title_snapshot, committee_id,
             committee_snapshot, jurisdiction_snapshot, reason, status, created_at)
         VALUES (:workload_id, :submitted_by, :task_title, :committee_id,
                 :committee_name, :jurisdiction_name, :reason, 'Pending', NOW())"
    );
    $insert->execute([
        ':workload_id' => $workloadId,
        ':submitted_by' => currentUserId(),
        ':task_title' => $task['task_title'],
        ':committee_id' => (int)$task['committee_id'],
        ':committee_name' => $task['committee_name'],
        ':jurisdiction_name' => $task['jurisdiction_name'],
        ':reason' => $reason,
    ]);
    $requestId = (int)$pdo->lastInsertId();

    $recipients = taskApprovalReviewRecipients($pdo, (int)$task['committee_id']);
    if (!$recipients) {
        throw new RuntimeException('No eligible Chairperson or Administrator can receive this request.');
    }
    recordTaskApprovalActivity(
        $pdo,
        (int)currentUserId(),
        'Submit Task Removal Request',
        'Submitted task removal request #' . $requestId . ' for "' . $task['task_title'] . '" in committee "' . $task['committee_name'] . '".',
        $recipients,
        'Task removal requested for "' . $task['task_title'] . '" in ' . $task['committee_name'] . '.',
        APP_URL . '/modules/workload/approval_center.php?section=removal&request_id=' . $requestId
    );

    $pdo->commit();
    jsonResponse(true, 'Your task removal request was submitted for review.', ['request_id' => $requestId]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Task removal request submission failed: ' . $e->getMessage());
    jsonResponse(false, 'The task removal request could not be submitted. Please try again or contact an administrator.');
}
