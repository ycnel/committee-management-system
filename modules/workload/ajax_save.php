<?php
/**
 * modules/workload/ajax_save.php
 * ------------------------------------------------------------------
 * Handles CREATE and UPDATE for the `workload_assignments` table
 * (id=0 means create). This is the "Calculate Workload" write path:
 * every task carries workload_points, which the Workload Recommendation
 * panel and the Performance dashboard both read back from.
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
$points            = (int)($_POST['workload_points'] ?? 1);
$dueDate           = clean($_POST['due_date'] ?? '') ?: null;
$status            = clean($_POST['status'] ?? 'Pending');
$aiRecommendationId = (int)($_POST['ai_recommendation_id'] ?? 0) ?: null;

$allowedPriority = ['Low', 'Medium', 'High', 'Urgent'];
$allowedStatus   = ['Pending', 'In Progress', 'Completed', 'Overdue'];

$errors = [];
if ($committeeMemberId <= 0) $errors[] = 'Please select who this task is assigned to.';
if ($title === '') $errors[] = 'Task title is required.';
if (!in_array($priority, $allowedPriority, true)) $errors[] = 'Invalid priority value.';
if (!in_array($status, $allowedStatus, true)) $errors[] = 'Invalid status value.';
if ($points < 1 || $points > 100) $errors[] = 'Workload points must be between 1 and 100.';
if ($dueDate !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate)) $errors[] = 'Invalid due date.';
if (!empty($errors)) jsonResponse(false, implode(' ', $errors));

$pdo = db();

$memberCheck = $pdo->prepare('SELECT committee_member_id FROM committee_members WHERE committee_member_id = :id AND status = \'Active\'');
$memberCheck->execute([':id' => $committeeMemberId]);
if (!$memberCheck->fetch()) jsonResponse(false, 'Selected committee member is invalid or inactive.');

$completionDate = $status === 'Completed' ? date('Y-m-d') : null;

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
        $before = $pdo->prepare('SELECT status FROM workload_assignments WHERE workload_id = :id');
        $before->execute([':id' => $id]);
        $beforeRow = $before->fetch();
        if (!$beforeRow) jsonResponse(false, 'Task not found.');

        // Preserve an existing completion_date unless status is changing to/from Completed.
        if ($status === 'Completed' && $beforeRow['status'] !== 'Completed') {
            $completionDateSql = 'completion_date = :cd,';
        } elseif ($status !== 'Completed') {
            $completionDateSql = 'completion_date = NULL,';
        } else {
            $completionDateSql = '';
        }

        $stmt = $pdo->prepare(
            "UPDATE workload_assignments SET committee_member_id = :cmid, task_title = :title, task_description = :desc,
             priority = :priority, workload_points = :points, due_date = :due, $completionDateSql status = :status
             WHERE workload_id = :id"
        );
        $execParams = [
            ':cmid' => $committeeMemberId, ':title' => $title, ':desc' => $description,
            ':priority' => $priority, ':points' => $points, ':due' => $dueDate,
            ':status' => $status, ':id' => $id,
        ];
        if ($completionDateSql === 'completion_date = :cd,') $execParams[':cd'] = $completionDate;
        $stmt->execute($execParams);

        if ($aiRecommendationId) {
            try {
                cmas_link_ai_recommendation($pdo, $aiRecommendationId, $id, $committeeMemberId);
            } catch (Throwable $e) {
                error_log('AI recommendation outcome tracking failed: ' . $e->getMessage());
            }
        }

        logActivity(currentUserId(), 'Update', 'Updated task #' . $id . ' (' . $title . ')');
        jsonResponse(true, 'Task updated successfully.', ['id' => $id]);
    }

    // ---- CREATE ----
    $stmt = $pdo->prepare(
        'INSERT INTO workload_assignments (committee_member_id, task_title, task_description, priority, workload_points,
         assigned_date, due_date, completion_date, status, created_at)
         VALUES (:cmid, :title, :desc, :priority, :points, CURDATE(), :due, :cd, :status, NOW())'
    );
    $stmt->execute([
        ':cmid' => $committeeMemberId, ':title' => $title, ':desc' => $description,
        ':priority' => $priority, ':points' => $points, ':due' => $dueDate,
        ':cd' => $completionDate, ':status' => $status,
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

    logActivity(currentUserId(), 'Insert', 'Created task #' . $id . ' (' . $title . ')');
    jsonResponse(true, 'Task assigned successfully.', ['id' => $id]);

} catch (PDOException $e) {
    error_log('Workload save error: ' . $e->getMessage());
    jsonResponse(false, 'A database error occurred while saving the task.');
}
