<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole([ROLE_ADMIN, ROLE_STAFF, ROLE_COMMITTEE]);

$pageTitle  = 'Committee Workload';
$activeMenu = 'workload';
$pdo = db();

$selectedCommitteeId = (int)($_GET['committee_id'] ?? 0);
if ($selectedCommitteeId <= 0) {
    redirect(APP_URL . '/modules/workload/index.php');
}

$committeeStmt = $pdo->prepare('SELECT committee_id, committee_name, status FROM committees WHERE committee_id = :id LIMIT 1');
$committeeStmt = $pdo->prepare(
    'SELECT committee_id, committee_name, status FROM committees
  WHERE committee_id = :id
    AND (:is_manager = 1 OR committee_id IN
      (SELECT committee_id FROM committee_members WHERE user_id = :uid AND status = \'Active\'))
  LIMIT 1'
);
$committeeStmt->execute([':id' => $selectedCommitteeId, ':is_manager' => canManage() ? 1 : 0, ':uid' => currentUserId()]);
$committee = $committeeStmt->fetch();

if (!$committee) {
    setFlash('danger', 'Committee not found.');
    redirect(APP_URL . '/modules/workload/index.php');
}

$selectedCommitteeName = $committee['committee_name'];

$summaryStmt = $pdo->prepare(
    "SELECT
        SUM(CASE WHEN wa.status = 'Pending' THEN 1 ELSE 0 END) AS pending,
        SUM(CASE WHEN wa.status = 'In Progress' THEN 1 ELSE 0 END) AS in_progress,
        SUM(CASE WHEN wa.status = 'Completed' THEN 1 ELSE 0 END) AS completed,
        SUM(CASE WHEN wa.status = 'Overdue' OR (wa.status IN ('Pending','In Progress') AND wa.due_date IS NOT NULL AND wa.due_date < CURDATE()) THEN 1 ELSE 0 END) AS overdue
     FROM workload_assignments wa
     INNER JOIN committee_members cm ON cm.committee_member_id = wa.committee_member_id
    WHERE cm.committee_id = :cid
      AND (:is_manager = 1 OR cm.user_id = :uid)"
);
  $summaryStmt->execute([':cid' => $selectedCommitteeId, ':is_manager' => canManage() ? 1 : 0, ':uid' => currentUserId()]);
$committeeSummary = $summaryStmt->fetch() ?: ['pending' => 0, 'in_progress' => 0, 'completed' => 0, 'overdue' => 0];

include __DIR__ . '/../../layouts/header.php';
?>
<div class="app-wrapper">
  <?php include __DIR__ . '/../../layouts/sidebar.php'; ?>

  <div class="main-content">
    <?php include __DIR__ . '/../../layouts/content-topbar.php'; ?>

    <div class="breadcrumb-bar d-flex justify-content-between align-items-center flex-wrap gap-2">
      <div>
        <nav aria-label="breadcrumb" class="mb-1">
          <ol class="breadcrumb mb-0 small">
            <li class="breadcrumb-item"><a href="index.php">Workload</a></li>
            <li class="breadcrumb-item active"><?= e($selectedCommitteeName) ?></li>
          </ol>
        </nav>
        <h5 class="mb-0"><i class="bi bi-diagram-3 text-primary"></i> <?= e($selectedCommitteeName) ?> Workload</h5>
      </div>
      <div class="d-flex gap-2">
        <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Choose another committee</a>
        <?php if (canManage()): ?>
          <button type="button" class="btn btn-primary btn-sm" id="btnAddTask">
            <i class="bi bi-plus-circle"></i> Assign Task
          </button>
        <?php endif; ?>
      </div>
    </div>

    <div class="workload-mini-overview mb-3">
      <div class="workload-mini-overview-header">
        <span class="workload-mini-title"><?= e($selectedCommitteeName) ?></span>
        <span class="workload-mini-subtitle">Task overview</span>
      </div>
      <div class="workload-mini-stat-grid">
        <div class="workload-mini-stat workload-mini-stat-completed">
          <span>Completed</span>
          <strong><?= (int)$committeeSummary['completed'] ?></strong>
        </div>
        <div class="workload-mini-stat workload-mini-stat-overdue">
          <span>Overdue</span>
          <strong><?= (int)$committeeSummary['overdue'] ?></strong>
        </div>
        <div class="workload-mini-stat workload-mini-stat-progress">
          <span>In Progress</span>
          <strong><?= (int)$committeeSummary['in_progress'] ?></strong>
        </div>
        <div class="workload-mini-stat workload-mini-stat-pending">
          <span>Pending</span>
          <strong><?= (int)$committeeSummary['pending'] ?></strong>
        </div>
      </div>
    </div>

    <div class="card mb-3">
      <div class="card-body py-3">
        <form id="filterForm" class="row g-2 align-items-center">
          <input type="hidden" name="committee_id" value="<?= (int)$selectedCommitteeId ?>">
          <div class="col-md-4">
            <input type="text" class="form-control form-control-sm" id="searchInput" name="search" placeholder="Search task title...">
          </div>
          <div class="col-md-2">
            <select class="form-select form-select-sm" name="status">
              <option value="">All Statuses</option>
              <?php foreach (['Pending', 'In Progress', 'Completed', 'Overdue'] as $s): ?>
                <option value="<?= e($s) ?>"><?= e($s) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <select class="form-select form-select-sm" name="priority">
              <option value="">All Priorities</option>
              <?php foreach (['Low', 'Medium', 'High', 'Urgent'] as $p): ?>
                <option value="<?= e($p) ?>"><?= e($p) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </form>
      </div>
    </div>

    <div class="card">
      <div id="workloadTableWrap">
        <?php include __DIR__ . '/table.php'; ?>
      </div>
    </div>
  </div>
</div>

<?php if (canManage()): ?>
<div class="modal fade" id="taskModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="taskForm">
        <?= csrfField() ?>
        <input type="hidden" name="id" id="wl_id" value="0">
        <input type="hidden" name="ai_recommendation_id" id="wl_ai_recommendation_id" value="">
        <div class="modal-header">
          <h5 class="modal-title" id="taskModalTitle"><i class="bi bi-bar-chart-steps"></i> Assign Task</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="task-stepper" aria-label="Task assignment progress">
            <div class="task-stepper-step is-active" data-step-indicator="1"><span>1</span><div><strong>Task</strong><small>Committee and title</small></div></div>
            <div class="task-stepper-line"></div>
            <div class="task-stepper-step" data-step-indicator="2"><span>2</span><div><strong>Assignment</strong><small>Member and priority</small></div></div>
            <div class="task-stepper-line"></div>
            <div class="task-stepper-step" data-step-indicator="3"><span>3</span><div><strong>Review</strong><small>Details and due date</small></div></div>
          </div>

          <section class="task-step-panel is-active" data-step-panel="1">
            <div class="task-step-heading"><span class="task-step-kicker">Step 1</span><h6>Set up the task</h6><p>Choose the committee and give the assignment a clear title.</p></div>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Committee <span class="text-danger">*</span></label>
                <select name="committee_id" id="wl_committee" class="form-select" required>
                  <option value="">-- Select Committee --</option>
                  <?php foreach ($pdo->query("SELECT committee_id, committee_name FROM committees WHERE status = 'Active' ORDER BY committee_name")->fetchAll() as $c): ?>
                    <option value="<?= (int)$c['committee_id'] ?>" <?= (int)$c['committee_id'] === $selectedCommitteeId ? 'selected' : '' ?>><?= e($c['committee_name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">Task Title <span class="text-danger">*</span></label>
                <input type="text" name="task_title" id="wl_title" class="form-control" required maxlength="255" placeholder="e.g. Draft Budget Hearing Report">
              </div>
              <div class="col-12">
                <button type="button" class="btn btn-outline-primary" id="btnGenerateAI"><i class="bi bi-stars"></i> Generate with AI</button>
                <div class="form-text">Optional: generate assignment details after selecting a committee and entering a title.</div>
              </div>
            </div>
            <div class="mt-3" id="wl_ai_loading" style="display:none;"><div class="task-ai-loading"><span class="spinner-border spinner-border-sm text-primary"></span><span class="fw-semibold small text-primary" id="wl_ai_loading_text">AI is analyzing the task...</span></div></div>
          </section>

          <section class="task-step-panel" data-step-panel="2">
            <div class="task-step-heading"><span class="task-step-kicker">Step 2</span><h6>Assign the work</h6><p>Choose the responsible member and set the task priority.</p></div>
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label">Assign To <span class="text-danger">*</span></label><select name="committee_member_id" id="wl_member" class="form-select" required><option value="">-- Select Committee First --</option></select></div>
              <div class="col-md-3"><label class="form-label">Priority</label><select name="priority" id="wl_priority" class="form-select"><?php foreach (['Low', 'Medium', 'High', 'Urgent'] as $p): ?><option value="<?= e($p) ?>" <?= $p === 'Medium' ? 'selected' : '' ?>><?= e($p) ?></option><?php endforeach; ?></select></div>
              <div class="col-md-3"><label class="form-label">Status</label><select name="status" id="wl_status" class="form-select"><?php foreach (['Pending', 'In Progress', 'Completed', 'Overdue'] as $s): ?><option value="<?= e($s) ?>"><?= e($s) ?></option><?php endforeach; ?></select></div>
            </div>
          </section>

          <section class="task-step-panel" data-step-panel="3">
            <div class="task-step-heading"><span class="task-step-kicker">Step 3</span><h6>Review and save</h6><p>Complete the details, then save the assignment.</p></div>
            <div class="mt-3" id="wl_ai_panel_wrap" style="display:none;"><div class="task-ai-result"><div class="d-flex justify-content-between align-items-center"><span class="small fw-semibold text-primary"><i class="bi bi-cpu"></i> AI Recommendation</span><span id="wl_ai_error_badge" class="badge bg-warning text-dark" style="display:none;"></span></div><div id="wl_ai_summary" class="small mt-2"></div><div id="wl_ai_reasoning" class="small text-muted mt-2 fst-italic"></div></div></div>
            <div class="row g-3">
              <div class="col-12"><label class="form-label task-description-label">Description <span id="wl_ai_desc_badge" class="badge bg-primary-subtle text-primary border border-primary-subtle task-ai-badge" style="display:none;"><i class="bi bi-stars"></i> AI Generated by Gemini</span></label><textarea name="task_description" id="wl_description" class="form-control" rows="3" placeholder="Describe the task, or generate one in Step 1."></textarea></div>
              <div class="col-md-6"><label class="form-label">Workload Points</label><input type="number" name="workload_points" id="wl_points" class="form-control" min="1" max="100" value="1"></div>
              <div class="col-md-6"><label class="form-label">Due Date</label><input type="date" name="due_date" id="wl_due" class="form-control"></div>
            </div>
          </section>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="task-step-button" id="taskStepPrevious"><i class="bi bi-arrow-left"></i> Previous</button>
          <button type="button" class="task-step-button task-step-button-primary" id="taskStepNext">Next <i class="bi bi-arrow-right"></i></button>
          <button type="submit" class="btn btn-primary" id="taskStepSave" style="display:none;"><i class="bi bi-check-circle"></i> Save Task</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<?php
$extraJs = [APP_URL . '/assets/js/workload.js'];
include __DIR__ . '/../../layouts/footer.php';
?>
