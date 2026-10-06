<?php
require_once __DIR__ . '/../../includes/task_approval.php';
requireLogin();

if (!isCommitteeMember()) jsonResponse(false, 'Only Committee Members can submit completion requests.');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method.');
requireCsrf();

$workloadId = (int)($_POST['workload_id'] ?? 0);
$completionNotes = trim((string)($_POST['completion_notes'] ?? ''));
if ($workloadId <= 0) jsonResponse(false, 'A valid assigned task is required.');
if ($completionNotes === '') jsonResponse(false, 'Completion remarks are required.');
if (mb_strlen($completionNotes) > 10000) jsonResponse(false, 'Completion remarks must not exceed 10,000 characters.');

$pdo = db();
$storedProof = null;
try {
    $pdo->beginTransaction();
    $taskStmt = $pdo->prepare(
        "SELECT wa.workload_id, wa.task_title, wa.status, wa.completion_date,
                wa.assigned_date, wa.committee_member_id, cm.user_id, cm.committee_id,
                c.committee_name,
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
        jsonResponse(false, 'This task has already been completed and approved.');
    }

    $pending = $pdo->prepare(
        "SELECT 1 FROM task_completion_requests
         WHERE workload_id = :workload_id AND status = 'Pending'
         LIMIT 1"
    );
    $pending->execute([':workload_id' => $workloadId]);
    if ($pending->fetchColumn()) {
        $pdo->rollBack();
        jsonResponse(false, 'A completion request for this task is already awaiting review.');
    }
    $pendingRemoval = $pdo->prepare(
        "SELECT 1 FROM task_removal_requests
         WHERE workload_id = :workload_id AND status = 'Pending'
         LIMIT 1"
    );
    $pendingRemoval->execute([':workload_id' => $workloadId]);
    if ($pendingRemoval->fetchColumn()) {
        $pdo->rollBack();
        jsonResponse(false, 'This task has a pending removal request and cannot be submitted for completion yet.');
    }

    $storedProof = storeTaskCompletionProof($_FILES['proof_file'] ?? []);
    $insert = $pdo->prepare(
        "INSERT INTO task_completion_requests
            (workload_id, submitted_by, task_title_snapshot, committee_id,
             committee_snapshot, jurisdiction_snapshot, completion_notes, status, created_at)
         VALUES (:workload_id, :submitted_by, :task_title, :committee_id,
                 :committee_name, :jurisdiction_name, :completion_notes, 'Pending', NOW())"
    );
    $insert->execute([
        ':workload_id' => $workloadId,
        ':submitted_by' => currentUserId(),
        ':task_title' => $task['task_title'],
        ':committee_id' => (int)$task['committee_id'],
        ':committee_name' => $task['committee_name'],
        ':jurisdiction_name' => $task['jurisdiction_name'],
        ':completion_notes' => $completionNotes,
    ]);
    $requestId = (int)$pdo->lastInsertId();

    $fileInsert = $pdo->prepare(
        'INSERT INTO task_completion_request_files
            (request_id, original_filename, stored_filename, file_path, mime_type, file_size, uploaded_by)
         VALUES (:request_id, :original_filename, :stored_filename, :file_path, :mime_type, :file_size, :uploaded_by)'
    );
    $fileInsert->execute([
        ':request_id' => $requestId,
        ':original_filename' => $storedProof['original_filename'],
        ':stored_filename' => $storedProof['stored_filename'],
        ':file_path' => $storedProof['file_path'],
        ':mime_type' => $storedProof['mime_type'],
        ':file_size' => $storedProof['file_size'],
        ':uploaded_by' => currentUserId(),
    ]);

    $recipients = taskApprovalReviewRecipients($pdo, (int)$task['committee_id']);
    if (!$recipients) {
        throw new RuntimeException('No eligible Chairperson or Administrator can receive this request.');
    }
    recordTaskApprovalActivity(
        $pdo,
        (int)currentUserId(),
        'Submit Task Completion',
        'Submitted completion request #' . $requestId . ' for task "' . $task['task_title'] . '" in committee "' . $task['committee_name'] . '".',
        $recipients,
        'Completion approval requested for "' . $task['task_title'] . '" in ' . $task['committee_name'] . '.',
        APP_URL . '/modules/workload/approval_center.php?section=completion&request_id=' . $requestId
    );

    $pdo->commit();
    jsonResponse(true, 'Your completion request and proof were submitted for approval.', ['request_id' => $requestId]);
} catch (DomainException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    deleteStoredTaskCompletionProof($storedProof['absolute_path'] ?? null);
    jsonResponse(false, $e->getMessage());
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    deleteStoredTaskCompletionProof($storedProof['absolute_path'] ?? null);
    error_log('Task completion submission failed: ' . $e->getMessage());
    jsonResponse(false, 'The completion request could not be submitted. Please try again or contact an administrator.');
}
