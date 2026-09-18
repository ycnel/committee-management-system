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
    $stmt = $pdo->prepare('SELECT task_title FROM workload_assignments WHERE workload_id = :id');
    $stmt->execute([':id' => $id]);
    $task = $stmt->fetch();
    if (!$task) jsonResponse(false, 'Task not found.');

    $del = $pdo->prepare('DELETE FROM workload_assignments WHERE workload_id = :id');
    $del->execute([':id' => $id]);

    logActivity(currentUserId(), 'Delete', 'Deleted task #' . $id . ' (' . $task['task_title'] . ')');
    jsonResponse(true, 'Task deleted successfully.');

} catch (PDOException $e) {
    error_log('Workload delete error: ' . $e->getMessage());
    jsonResponse(false, 'A database error occurred while deleting the task.');
}
