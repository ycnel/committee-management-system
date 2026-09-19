<?php
/** Read-only task detail page with server-side ownership enforcement. */

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    setFlash('danger', 'Invalid task id.');
    redirect(APP_URL . '/dashboard.php');
}

$pdo = db();
$stmt = $pdo->prepare(
    "SELECT wa.*, c.committee_name, j.jurisdiction_name, u.full_name AS assigned_name
     FROM workload_assignments wa
     INNER JOIN committee_members cm ON cm.committee_member_id = wa.committee_member_id
     INNER JOIN committees c ON c.committee_id = cm.committee_id
     LEFT JOIN jurisdictions j ON j.jurisdiction_id = c.jurisdiction_id
     INNER JOIN users u ON u.id = cm.user_id
     WHERE wa.workload_id = :id
       AND (:is_manager = 1 OR cm.user_id = :uid)
     LIMIT 1"
);
$stmt->execute([':id' => $id, ':is_manager' => canManage() ? 1 : 0, ':uid' => currentUserId()]);
$task = $stmt->fetch();

if (!$task) {
    http_response_code(404);
    if (isAjaxRequest()) {
        jsonResponse(false, 'Task not found or you do not have permission to view it.');
    }
    setFlash('danger', 'Task not found or you do not have permission to view it.');
    redirect(APP_URL . '/dashboard.php');
}

$pageTitle = 'Task Details';
$activeMenu = 'workload';
include __DIR__ . '/../../layouts/header.php';
?>
<div class="app-wrapper">
  <?php include __DIR__ . '/../../layouts/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/../../layouts/content-topbar.php'; ?>
    <div class="breadcrumb-bar d-flex justify-content-between align-items-center gap-2">
      <div><h5 class="mb-0"><i class="bi bi-list-task text-primary"></i> Task Details</h5><small class="text-muted"><?= e($task['committee_name']) ?></small></div>
      <a href="<?= e(APP_URL) ?>/dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back to dashboard</a>
    </div>
    <div class="card hero-card mt-3"><div class="card-body">
      <h4><?= e($task['task_title']) ?></h4>
      <div class="d-flex flex-wrap gap-2 mb-3"><span class="badge bg-light text-dark border">Assigned</span><span class="badge bg-light text-dark border"><?= e($task['priority']) ?></span><span class="badge bg-light text-dark border">Due: <?= $task['due_date'] ? e(formatDate($task['due_date'])) : 'No due date' ?></span></div>
      <p class="text-muted"><?= nl2br(e($task['task_description'] ?? 'No description provided.')) ?></p>
      <div class="small text-muted">Assigned to <?= e($task['assigned_name']) ?> &middot; <?= e($task['jurisdiction_name'] ?: 'Unassigned jurisdiction') ?></div>
      <div class="small text-muted mt-1">Assigned date: <?= $task['assigned_date'] ? e(formatDate($task['assigned_date'])) : 'Not recorded' ?></div>
    </div></div>
  </div>
</div>
<?php include __DIR__ . '/../../layouts/footer.php'; ?>
