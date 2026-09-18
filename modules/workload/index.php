<?php
/**
 * modules/workload/index.php
 * ------------------------------------------------------------------
 * Smart Workload Distribution module (Module 4). Shows workload
 * summary widgets, a per-committee "who's least busy" recommendation
 * panel, and the full task list (Pending/Completed Tasks) with a
 * Create/Edit modal. The Assign Task modal includes a "Generate with
 * AI" flow: the admin types a Task Title, clicks Generate, and local
 * Google Gemini AI fills in Description, Assign To, Priority, Workload
 * Points, Due Date and Status from real committee/member data —
 * every field stays fully editable afterward.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../../includes/auth.php';
requireRole([ROLE_ADMIN, ROLE_STAFF, ROLE_COMMITTEE]);

$pageTitle  = 'Lungsod ng Manila Committee Management and Assignment System';
$activeMenu = 'workload';
$pdo = db();

if (isCommitteeMember()) {
  $committeeStmt = $pdo->prepare(
    "SELECT c.committee_id, c.committee_name
     FROM committees c
     INNER JOIN committee_members cm ON cm.committee_id = c.committee_id
     WHERE c.status = 'Active' AND cm.user_id = :uid AND cm.status = 'Active'
     ORDER BY c.committee_name"
  );
  $committeeStmt->execute([':uid' => currentUserId()]);
  $committees = $committeeStmt->fetchAll();
} else {
  $committees = $pdo->query("SELECT committee_id, committee_name FROM committees WHERE status = 'Active' ORDER BY committee_name")->fetchAll();
}
$selectedCommitteeId = (int)($_GET['committee_id'] ?? 0);
$selectedCommitteeName = '';
foreach ($committees as $committee) {
  if ((int)$committee['committee_id'] === $selectedCommitteeId) {
    $selectedCommitteeName = $committee['committee_name'];
    break;
  }
}
if (isCommitteeMember() && $selectedCommitteeId > 0 && $selectedCommitteeName === '') {
  $selectedCommitteeId = 0;
}

// ---- Summary widgets --------------------------------------------------
// Committee Member sees only their OWN workload counts; Administrator and
// Committee Chairperson see the full system-wide counts (unchanged).
if (isCommitteeMember()) {
    $summaryStmt = $pdo->prepare(
        "SELECT
            SUM(CASE WHEN wa.status = 'Pending' THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN wa.status = 'In Progress' THEN 1 ELSE 0 END) AS in_progress,
            SUM(CASE WHEN wa.status = 'Completed' THEN 1 ELSE 0 END) AS completed,
            SUM(CASE WHEN wa.status = 'Overdue' OR (wa.status IN ('Pending','In Progress') AND wa.due_date IS NOT NULL AND wa.due_date < CURDATE()) THEN 1 ELSE 0 END) AS overdue
         FROM workload_assignments wa
         INNER JOIN committee_members cm ON cm.committee_member_id = wa.committee_member_id
         WHERE cm.user_id = :uid"
    );
    $summaryStmt->execute([':uid' => currentUserId()]);
    $summary = $summaryStmt->fetch();
} else {
    $summary = $pdo->query(
        "SELECT
            SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) AS in_progress,
            SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) AS completed,
            SUM(CASE WHEN status = 'Overdue' OR (status IN ('Pending','In Progress') AND due_date IS NOT NULL AND due_date < CURDATE()) THEN 1 ELSE 0 END) AS overdue
         FROM workload_assignments"
    )->fetch();
}
$summary = $summary ?: ['pending' => 0, 'in_progress' => 0, 'completed' => 0, 'overdue' => 0];

$committeeTaskSummary = null;
if ($selectedCommitteeId > 0) {
    $committeeTaskSummaryStmt = $pdo->prepare(
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
          $committeeTaskSummaryStmt->execute([':cid' => $selectedCommitteeId, ':is_manager' => canManage() ? 1 : 0, ':uid' => currentUserId()]);
    $committeeTaskSummary = $committeeTaskSummaryStmt->fetch() ?: ['pending' => 0, 'in_progress' => 0, 'completed' => 0, 'overdue' => 0];
}

// ---- Recommendation panel: workload points per active member --------
// This cross-member comparison is a task-assignment decision-support tool
// for Administrator (view) and Committee Chairperson (use) only. Committee
// Member does not get this view -- they only ever see their own workload.
$recCommittee = (int)($_GET['rec_committee'] ?? ($committees[0]['committee_id'] ?? 0));
$recommendations = [];
if ($recCommittee > 0 && !isCommitteeMember()) {
    $recStmt = $pdo->prepare(
        "SELECT cm.committee_member_id, u.full_name, cm.member_role,
                COALESCE(SUM(CASE WHEN wa.status IN ('Pending','In Progress') THEN wa.workload_points ELSE 0 END), 0) AS active_points,
                COUNT(CASE WHEN wa.status IN ('Pending','In Progress') THEN 1 END) AS active_tasks
         FROM committee_members cm
         INNER JOIN users u ON u.id = cm.user_id
         LEFT JOIN workload_assignments wa ON wa.committee_member_id = cm.committee_member_id
         WHERE cm.committee_id = :cid AND cm.status = 'Active'
         GROUP BY cm.committee_member_id, u.full_name, cm.member_role
         ORDER BY active_points ASC, active_tasks ASC"
    );
    $recStmt->execute([':cid' => $recCommittee]);
    $recommendations = $recStmt->fetchAll();
}

include __DIR__ . '/../../layouts/header.php';
?>
<div class="app-wrapper">
  <?php include __DIR__ . '/../../layouts/sidebar.php'; ?>

  <div class="main-content">
  <div class="breadcrumb-bar d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <h5 class="mb-0"><i class="bi bi-bar-chart-steps text-primary"></i> Smart Workload Distribution</h5>
      <small class="text-muted">Track task load across committee members and see who has capacity.</small>
    </div>
    <?php if (canEditAiSettings() || canManage()): ?>
      <div class="d-flex gap-2">
        <?php if (canEditAiSettings()): ?>
          <a href="ai_settings.php" class="btn btn-outline-primary btn-sm" title="Adjust Smart AI Workload Distribution settings">
            <i class="bi bi-stars"></i> Smart AI Settings
          </a>
        <?php endif; ?>
        <?php if (canManage() && $selectedCommitteeId > 0): ?>
          <button type="button" class="btn btn-primary btn-sm" id="btnAddTask">
            <i class="bi bi-plus-circle"></i> Assign Task
          </button>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
      <div class="card stat-card bg-gov-blue">
        <div class="card-body"><i class="bi bi-hourglass-split stat-icon"></i>
          <div class="stat-value"><?= (int)$summary['pending'] ?></div>
          <div class="stat-label">Pending Tasks</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card stat-card bg-gov-amber">
        <div class="card-body"><i class="bi bi-arrow-repeat stat-icon"></i>
          <div class="stat-value"><?= (int)$summary['in_progress'] ?></div>
          <div class="stat-label">In Progress</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card stat-card bg-gov-teal">
        <div class="card-body"><i class="bi bi-check2-circle stat-icon"></i>
          <div class="stat-value"><?= (int)$summary['completed'] ?></div>
          <div class="stat-label">Completed Tasks</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card stat-card bg-gov-red">
        <div class="card-body"><i class="bi bi-exclamation-triangle stat-icon"></i>
          <div class="stat-value"><?= (int)$summary['overdue'] ?></div>
          <div class="stat-label">Overdue</div>
        </div>
      </div>
    </div>
  </div>

  <?php if (!isCommitteeMember()): ?>

    <!--
  <div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <span><i class="bi bi-lightbulb"></i> Workload Recommendation</span>
      <form method="get" class="d-flex align-items-center gap-2">
        <label class="small text-muted mb-0">Committee:</label>
        <select name="rec_committee" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
          <?php foreach ($committees as $c): ?>
            <option value="<?= (int)$c['committee_id'] ?>" <?= $recCommittee === (int)$c['committee_id'] ? 'selected' : '' ?>>
              <?= e($c['committee_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </form>
    </div>
    <div class="card-body">
      <?php if (empty($committees)): ?>
        <p class="text-muted mb-0">No active committees yet. Create one first under Committee Management.</p>
      <?php elseif (empty($recommendations)): ?>
        <p class="text-muted mb-0">This committee has no active members yet.</p>
      <?php else: ?>
        <div class="row g-2">
          <?php foreach ($recommendations as $i => $r): ?>
            <div class="col-md-4">
              <div class="border rounded p-2 d-flex justify-content-between align-items-center <?= $i === 0 ? 'border-success bg-opacity-10 bg-success' : '' ?>">
                <div>
                  <div class="fw-semibold small">
                    <?= $i === 0 ? '<i class="bi bi-star-fill text-success"></i> ' : '' ?><?= e($r['full_name']) ?>
                  </div>
                  <div class="text-muted small"><?= e($r['member_role']) ?> &middot; <?= (int)$r['active_tasks'] ?> active task(s)</div>
                </div>
                <span class="badge bg-<?= $i === 0 ? 'success' : 'secondary' ?> rounded-pill"><?= (int)$r['active_points'] ?> pts</span>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <p class="small text-muted mt-2 mb-0">
          <i class="bi bi-info-circle"></i> Recommendation is based on total workload points of currently Pending/In Progress
          tasks — the member with the lowest active load is highlighted first.
        </p>
      <?php endif; ?>
    </div>
  </div>
          -->

  <?php endif; ?>

  <section class="workload-committee-panel mb-3" aria-labelledby="workloadCommitteeHeading">
    <div class="workload-section-heading">
      <div>
        <h6 id="workloadCommitteeHeading" class="mb-1">Choose a committee</h6>
        <p class="small text-muted mb-0">
          <?= $selectedCommitteeName ? 'Showing tasks for ' . e($selectedCommitteeName) . '.' : 'Select a committee to see only its related workload and tasks.' ?>
        </p>
      </div>
      <?php if ($selectedCommitteeId > 0): ?>
        <button type="button" class="workload-clear-committee" id="clearCommitteeFilter">Choose another committee</button>
      <?php endif; ?>
    </div>
    <div class="workload-committee-grid">
      <?php foreach ($committees as $i => $c):
        $committeeHue = ($i * 137 + 22) % 360;
      ?>
        <a href="committee.php?committee_id=<?= (int)$c['committee_id'] ?>"
           class="workload-committee-card <?= $selectedCommitteeId === (int)$c['committee_id'] ? 'is-selected' : '' ?>"
           style="--committee-hue: <?= $committeeHue ?>;">
          <span class="workload-committee-inner">
            <span class="workload-committee-form" aria-label="Committee icon">
              <i class="bi bi-people-fill workload-committee-card-icon"></i>
            </span>
            <span class="workload-committee-data">
              <span class="workload-committee-text">
                <span class="workload-committee-card-name"><?= e($c['committee_name']) ?></span>
                <span class="workload-committee-card-description">View tasks, assignments, priorities, and due dates for this committee.</span>
              </span>
            </span>
          </span>
        </a>
      <?php endforeach; ?>
      <?php if (empty($committees)): ?>
        <p class="text-muted small mb-0">No active committees yet. Create one first under Committee Management.</p>
      <?php endif; ?>
    </div>
  </section>

  <?php if ($selectedCommitteeId > 0): ?>
    <div class="card mb-3">
      <div class="card-body py-3">
        <form id="filterForm" class="row g-2 align-items-center">
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
        <div class="workload-mini-overview">
          <div class="workload-mini-overview-header">
            <span class="workload-mini-title"><?= e($selectedCommitteeName ?: 'Selected committee') ?></span>
            <span class="workload-mini-subtitle">Task overview</span>
          </div>
          <div class="workload-mini-stat-grid">
            <div class="workload-mini-stat workload-mini-stat-completed">
              <span>Completed</span>
              <strong><?= (int)($committeeTaskSummary['completed'] ?? 0) ?></strong>
            </div>
            <div class="workload-mini-stat workload-mini-stat-overdue">
              <span>Overdue</span>
              <strong><?= (int)($committeeTaskSummary['overdue'] ?? 0) ?></strong>
            </div>
            <div class="workload-mini-stat workload-mini-stat-progress">
              <span>In Progress</span>
              <strong><?= (int)($committeeTaskSummary['in_progress'] ?? 0) ?></strong>
            </div>
            <div class="workload-mini-stat workload-mini-stat-pending">
              <span>Pending</span>
              <strong><?= (int)($committeeTaskSummary['pending'] ?? 0) ?></strong>
            </div>
          </div>
        </div>
        <?php include __DIR__ . '/table.php'; ?>
      </div>
    </div>
  <?php endif; ?>

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
                  <?php foreach ($committees as $c): ?>
                    <option value="<?= (int)$c['committee_id'] ?>"><?= e($c['committee_name']) ?></option>
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
