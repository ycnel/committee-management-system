<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

if (!canManage()) jsonResponse(false, 'You do not have permission to perform this action.');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method.');
requireCsrf();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id <= 0) jsonResponse(false, 'Invalid task id.');

$pdo = db();
try {
    $stmt = $pdo->prepare(
        'SELECT wa.task_title, cm.user_id
         FROM workload_assignments wa
         INNER JOIN committee_members cm ON cm.committee_member_id = wa.committee_member_id
         WHERE wa.workload_id = :id'
    );
    $stmt->execute([':id' => $id]);
    $task = $stmt->fetch();
    if (!$task) jsonResponse(false, 'Task not found.');

    $del = $pdo->prepare('DELETE FROM workload_assignments WHERE workload_id = :id');
    $del->execute([':id' => $id]);

    $activityId = logActivity(currentUserId(), 'Delete', 'Deleted task #' . $id . ' (' . $task['task_title'] . ')');
    createNotification(
        (int)$task['user_id'],
        'Task deleted: ' . $task['task_title'],
        APP_URL . '/modules/workload/index.php',
        $activityId
    );
    jsonResponse(true, 'Task deleted successfully.');

} catch (PDOException $e) {
    error_log('Workload delete error: ' . $e->getMessage());
    jsonResponse(false, 'A database error occurred while deleting the task.');
}
