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
    "SELECT wa.*, c.committee_name,
            COALESCE(wj.jurisdiction_name, cj.jurisdiction_name,
                (SELECT j2.jurisdiction_name FROM jurisdictions j2
                 WHERE j2.category = c.committee_name
                 ORDER BY j2.jurisdiction_name LIMIT 1)
            ) AS jurisdiction_name,
            u.full_name AS assigned_name, tt.proof_requirement,
            completion_request.status AS completion_status,
            completion_request.created_at AS completion_submitted_at,
            completion_request.reviewer_remarks AS completion_reviewer_remarks,
            completion_file.id AS completion_file_id,
            completion_file.original_filename AS completion_filename,
            removal_request.status AS removal_status,
            removal_request.reviewer_remarks AS removal_reviewer_remarks
     FROM workload_assignments wa
     INNER JOIN committee_members cm ON cm.committee_member_id = wa.committee_member_id
     INNER JOIN committees c ON c.committee_id = cm.committee_id
     LEFT JOIN jurisdictions wj ON wj.jurisdiction_id = wa.jurisdiction_id
     LEFT JOIN jurisdictions cj ON cj.jurisdiction_id = c.jurisdiction_id
     INNER JOIN users u ON u.id = cm.user_id
     LEFT JOIN task_templates tt ON tt.id = wa.task_template_id
     LEFT JOIN (
        SELECT workload_id, MAX(id) AS request_id
        FROM task_completion_requests
        WHERE workload_id IS NOT NULL GROUP BY workload_id
     ) latest_completion ON latest_completion.workload_id = wa.workload_id
     LEFT JOIN task_completion_requests completion_request
       ON completion_request.id = latest_completion.request_id
     LEFT JOIN task_completion_request_files completion_file
       ON completion_file.request_id = completion_request.id
     LEFT JOIN (
        SELECT workload_id, MAX(id) AS request_id
        FROM task_removal_requests
        WHERE workload_id IS NOT NULL GROUP BY workload_id
     ) latest_removal ON latest_removal.workload_id = wa.workload_id
     LEFT JOIN task_removal_requests removal_request
       ON removal_request.id = latest_removal.request_id
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
      <div class="d-flex flex-wrap gap-2 mb-3">
        <?php if ($task['status'] === 'Completed'): ?>
          <span class="badge workload-task-tag status-completed"><i class="bi bi-check2-circle me-1"></i>Done</span>
        <?php else: ?>
          <span class="badge bg-light text-dark border">Assigned</span>
        <?php endif; ?>
        <span class="badge bg-light text-dark border"><?= e($task['priority']) ?></span>
        <span class="badge bg-light text-dark border">Due: <?= $task['due_date'] ? e(formatDate($task['due_date'])) : 'No due date' ?></span>
      </div>
      <p class="text-muted"><?= nl2br(e($task['task_description'] ?? 'No description provided.')) ?></p>
      <div class="small text-muted">Assigned to <?= e($task['assigned_name']) ?> &middot; <?= e($task['jurisdiction_name'] ?: 'Unassigned jurisdiction') ?></div>
      <div class="small text-muted mt-1">Assigned date: <?= $task['assigned_date'] ? e(formatDate($task['assigned_date'])) : 'Not recorded' ?></div>
      <?php if (isCommitteeMember()): ?>
        <?php if (!empty($task['completion_status'])): ?>
          <div class="workload-member-request-status">
            <span class="badge text-bg-<?= $task['completion_status'] === 'Pending' ? 'warning' : ($task['completion_status'] === 'Approved' ? 'success' : 'danger') ?>">
              <?= $task['completion_status'] === 'Pending' ? 'Pending Approval' : e($task['completion_status']) ?>
            </span>
            <?php if (!empty($task['completion_submitted_at'])): ?>
              <small>Submitted <?= e(date('M j, Y', strtotime($task['completion_submitted_at']))) ?></small>
            <?php endif; ?>
            <?php if (!empty($task['completion_reviewer_remarks'])): ?>
              <p class="small mt-2 mb-0"><strong>Reviewer Remarks:</strong> <?= e($task['completion_reviewer_remarks']) ?></p>
            <?php endif; ?>
            <?php if (!empty($task['completion_file_id'])): ?>
              <a class="btn btn-link btn-sm p-0 mt-1" href="<?= e(APP_URL) ?>/modules/workload/download_completion_proof.php?id=<?= (int)$task['completion_file_id'] ?>">
                <i class="bi bi-paperclip"></i> <?= e($task['completion_filename'] ?: 'View Proof') ?>
              </a>
            <?php endif; ?>
          </div>
        <?php endif; ?>
        <?php if (!empty($task['removal_status'])): ?>
          <div class="workload-member-request-status mt-2">
            <span class="badge text-bg-<?= $task['removal_status'] === 'Pending' ? 'warning' : ($task['removal_status'] === 'Approved' ? 'success' : 'danger') ?>">
              <?= e($task['removal_status'] === 'Pending' ? 'Removal Pending' : 'Removal ' . $task['removal_status']) ?>
            </span>
            <?php if (!empty($task['removal_reviewer_remarks'])): ?>
              <p class="small mt-2 mb-0"><strong>Reviewer Remarks:</strong> <?= e($task['removal_reviewer_remarks']) ?></p>
            <?php endif; ?>
          </div>
        <?php endif; ?>
        <?php if ($task['completion_status'] !== 'Approved' && $task['completion_status'] !== 'Pending'
            && $task['removal_status'] !== 'Pending' && $task['status'] !== 'Completed'): ?>
          <div class="workload-member-task-actions">
            <button type="button" class="btn btn-primary btn-sm" data-submit-completion
                    data-workload-id="<?= (int)$task['workload_id'] ?>"
                    data-task-title="<?= e($task['task_title']) ?>"
                    data-committee-name="<?= e($task['committee_name']) ?>"
                    data-jurisdiction-name="<?= e($task['jurisdiction_name'] ?: 'Unassigned jurisdiction') ?>"
                    data-assigned-date="<?= e($task['assigned_date'] ? formatDate($task['assigned_date']) : 'Not recorded') ?>"
                    data-proof-requirement="<?= e($task['proof_requirement'] ?: '') ?>">
              <i class="bi bi-send-check"></i> Submit for Approval
            </button>
            <?php if ($task['removal_status'] !== 'Approved'): ?>
              <button type="button" class="btn btn-outline-secondary btn-sm" data-request-removal
                      data-workload-id="<?= (int)$task['workload_id'] ?>"
                      data-task-title="<?= e($task['task_title']) ?>"
                      data-committee-name="<?= e($task['committee_name']) ?>"
                      data-jurisdiction-name="<?= e($task['jurisdiction_name'] ?: 'Unassigned jurisdiction') ?>">
                <i class="bi bi-trash3"></i> Request Removal
              </button>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div></div>
  </div>
</div>
<?php
include __DIR__ . '/_task_member_modals.php';
$extraJs = isCommitteeMember()
    ? [APP_URL . '/assets/js/task-member-requests.js?v=' . (int)@filemtime(__DIR__ . '/../../assets/js/task-member-requests.js')]
    : [];
include __DIR__ . '/../../layouts/footer.php';
?>
