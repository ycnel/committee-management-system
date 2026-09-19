<?php
/**
 * modules/workload/ajax_save.php
 * ------------------------------------------------------------------
 * Handles CREATE and UPDATE for the `workload_assignments` table
 * (id=0 means create). This is the "Calculate Workload" write path:
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

$memberCheck = $pdo->prepare('SELECT committee_member_id, user_id FROM committee_members WHERE committee_member_id = :id AND status = \'Active\'');
$memberCheck->execute([':id' => $committeeMemberId]);
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

/**
 * Links this saved task back to the AI recommendation log row (if the
 * task was created via "Generate with AI") and records whether the
 * admin's final choice of member matches what the AI recommended.
 * Non-fatal: the task itself always saves even if this bookkeeping
 * step fails for any reason.
 */
function cmas_link_ai_recommendation(PDO $pdo, int $recommendationId, int $workloadId, int $finalCommitteeMemberId): void
{
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
    $stmt = $pdo->prepare(
        'INSERT INTO workload_assignments (committee_member_id, task_title, task_description, priority,
         assigned_date, due_date, created_at)
         VALUES (:cmid, :title, :desc, :priority, CURDATE(), :due, NOW())'
    );
    $stmt->execute([
        ':cmid' => $committeeMemberId, ':title' => $title, ':desc' => $description,
        ':priority' => $priority, ':due' => $dueDate,
    ]);
    $id = (int)$pdo->lastInsertId();

    if ($aiRecommendationId) {
        try {
            cmas_link_ai_recommendation($pdo, $aiRecommendationId, $id, $committeeMemberId);
        } catch (Throwable $e) {
            // Non-fatal: the task itself was saved successfully; only the AI audit trail failed to link.
            error_log('AI recommendation outcome tracking failed: ' . $e->getMessage());
        }
    }

    $activityId = logActivity(currentUserId(), 'Insert', 'Created task #' . $id . ' (' . $title . ')');
    createNotification(
        (int)$member['user_id'],
        'New task assigned to you: ' . $title,
        APP_URL . '/modules/workload/task.php?id=' . $id,
        $activityId
    );
    jsonResponse(true, 'Task assigned successfully.', ['id' => $id]);

} catch (PDOException $e) {
    error_log('Workload save error: ' . $e->getMessage());
    if ((int)$e->getCode() === 23000 || str_contains(strtolower($e->getMessage()), 'uq_workload_member_task_due')) {
        jsonResponse(false, 'A task with this title is already assigned to this member for the selected due date.');
    }
    jsonResponse(false, 'A database error occurred while saving the task.');
}
