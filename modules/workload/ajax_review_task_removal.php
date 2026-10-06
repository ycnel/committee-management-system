<?php
require_once __DIR__ . '/../../includes/task_approval.php';
requireLogin();

if (!canManage()) jsonResponse(false, 'Only an Administrator or the responsible Committee Chairperson can review task removal requests.');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method.');
requireCsrf();

$requestId = (int)($_POST['request_id'] ?? 0);
$decision = clean($_POST['decision'] ?? '');
$remarks = trim((string)($_POST['reviewer_remarks'] ?? ''));
if ($requestId <= 0) jsonResponse(false, 'A valid task removal request is required.');
if (!in_array($decision, ['Approved', 'Rejected'], true)) jsonResponse(false, 'Invalid review decision.');
if ($decision === 'Rejected' && $remarks === '') jsonResponse(false, 'Reviewer remarks are required when rejecting a task removal request.');
if (mb_strlen($remarks) > 10000) jsonResponse(false, 'Reviewer remarks must not exceed 10,000 characters.');

$pdo = db();
try {
    $pdo->beginTransaction();
    $requestStmt = $pdo->prepare(
        "SELECT tr.*, COALESCE(tr.committee_id, cm.committee_id) AS review_committee_id,
                wa.workload_id AS current_workload_id
         FROM task_removal_requests tr
         LEFT JOIN workload_assignments wa ON wa.workload_id = tr.workload_id
         LEFT JOIN committee_members cm ON cm.committee_member_id = wa.committee_member_id
         WHERE tr.id = :id
         FOR UPDATE"
    );
    $requestStmt->execute([':id' => $requestId]);
    $request = $requestStmt->fetch();
    if (!$request) {
        $pdo->rollBack();
        jsonResponse(false, 'Task removal request not found.');
    }
    if ($request['status'] !== 'Pending') {
        $pdo->rollBack();
        jsonResponse(false, 'This task removal request has already been reviewed.');
    }
    $committeeId = (int)$request['review_committee_id'];
    if (!taskApprovalChairCanReview($pdo, $committeeId)) {
        $pdo->rollBack();
        jsonResponse(false, 'You are not the Chairperson of the committee this task belongs to.');
    }

    if ($decision === 'Approved') {
        $workloadId = (int)$request['current_workload_id'];
        if ($workloadId <= 0) {
            $pdo->rollBack();
            jsonResponse(false, 'The assigned task no longer exists and cannot be removed.');
        }
        $taskLock = $pdo->prepare(
            'SELECT status, completion_date FROM workload_assignments WHERE workload_id = :id FOR UPDATE'
        );
        $taskLock->execute([':id' => $workloadId]);
        $task = $taskLock->fetch();
        if (!$task || $task['completion_date'] !== null || $task['status'] === 'Completed') {
            $pdo->rollBack();
            jsonResponse(false, 'A completed task cannot be removed through this request.');
        }
        $pendingCompletion = $pdo->prepare(
            "SELECT 1 FROM task_completion_requests
             WHERE workload_id = :workload_id AND status = 'Pending'
             LIMIT 1"
        );
        $pendingCompletion->execute([':workload_id' => $workloadId]);
        if ($pendingCompletion->fetchColumn()) {
            $pdo->rollBack();
            jsonResponse(false, 'The task has a pending completion request and cannot be removed yet.');
        }
    }

    $update = $pdo->prepare(
        'UPDATE task_removal_requests
         SET status = :status, reviewed_by = :reviewer_id, reviewed_at = NOW(),
             reviewer_remarks = :remarks
         WHERE id = :id AND status = \'Pending\''
    );
    $update->execute([
        ':status' => $decision,
        ':reviewer_id' => currentUserId(),
        ':remarks' => $remarks !== '' ? $remarks : null,
        ':id' => $requestId,
    ]);
    if ($update->rowCount() !== 1) {
        throw new RuntimeException('The task removal request changed while it was being reviewed.');
    }
    if ($decision === 'Approved') {
        $delete = $pdo->prepare('DELETE FROM workload_assignments WHERE workload_id = :id');
        $delete->execute([':id' => (int)$request['current_workload_id']]);
        if ($delete->rowCount() !== 1) {
            throw new RuntimeException('The assigned task could not be removed.');
        }
    }

    $message = $decision === 'Approved'
        ? 'Your task removal request for "' . $request['task_title_snapshot'] . '" was approved.'
        : 'Your task removal request for "' . $request['task_title_snapshot'] . '" was rejected. Reviewer remarks: ' . $remarks;
    recordTaskApprovalActivity(
        $pdo,
        (int)currentUserId(),
        'Review Task Removal Request',
        $decision . ' task removal request #' . $requestId . ' for "' . $request['task_title_snapshot'] . '".'
            . ($remarks !== '' ? ' Reviewer remarks: ' . $remarks : ''),
        $request['submitted_by'] ? [(int)$request['submitted_by']] : [],
        mb_substr($message, 0, 500),
        APP_URL . '/modules/workload/approval_center.php?section=removal&request_id=' . $requestId
    );
    $pdo->commit();
    jsonResponse(true, $decision === 'Approved'
        ? 'The task was removed and the member was notified.'
        : 'Task removal request rejected.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Task removal review failed: ' . $e->getMessage());
    jsonResponse(false, 'The task removal request could not be reviewed. Please try again or contact an administrator.');
}
