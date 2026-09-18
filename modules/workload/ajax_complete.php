<?php
/**
 * modules/workload/ajax_complete.php
 * ------------------------------------------------------------------
 * One-click "mark completed" action from the task table's quick
 * action button, so users don't have to open the full edit modal
 * just to close out a task.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

if (!canManage()) jsonResponse(false, 'You do not have permission to perform this action.');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method.');
requireCsrf();

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) jsonResponse(false, 'Invalid task id.');

$pdo = db();
try {
    $stmt = $pdo->prepare('SELECT task_title FROM workload_assignments WHERE workload_id = :id');
    $stmt->execute([':id' => $id]);
    $task = $stmt->fetch();
    if (!$task) jsonResponse(false, 'Task not found.');

    $upd = $pdo->prepare("UPDATE workload_assignments SET status = 'Completed', completion_date = CURDATE() WHERE workload_id = :id");
    $upd->execute([':id' => $id]);

    logActivity(currentUserId(), 'Update', 'Marked task #' . $id . ' (' . $task['task_title'] . ') as completed.');
    jsonResponse(true, 'Task marked as completed.');

} catch (PDOException $e) {
    error_log('Workload complete error: ' . $e->getMessage());
    jsonResponse(false, 'A database error occurred while updating the task.');
}
