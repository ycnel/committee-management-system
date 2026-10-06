<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole([ROLE_ADMIN, ROLE_STAFF, ROLE_COMMITTEE, ROLE_SUPER_ADMIN, ...LEGISLATIVE_OVERSIGHT_ROLES]);

$pageTitle  = 'Committee Workload';
$activeMenu = 'workload';
$pdo = db();
$activeJurisdictions = $pdo->query(
    "SELECT jurisdiction_id, jurisdiction_name
     FROM jurisdictions
     WHERE status = 'Active'
     ORDER BY jurisdiction_name"
)->fetchAll();
$activeCommittees = $pdo->query(
    "SELECT c.committee_id, c.committee_name, c.jurisdiction_id,
            (SELECT GROUP_CONCAT(DISTINCT j2.jurisdiction_id
                                 ORDER BY (j2.jurisdiction_id = c.jurisdiction_id) DESC, j2.jurisdiction_name
                                 SEPARATOR ',')
             FROM jurisdictions j2
             WHERE j2.status = 'Active'
               AND (j2.category = c.committee_name OR j2.jurisdiction_id = c.jurisdiction_id)
            ) AS jurisdiction_ids
     FROM committees c
     WHERE c.status = 'Active'
     ORDER BY c.committee_name"
)->fetchAll();

$selectedCommitteeId = (int)($_GET['committee_id'] ?? 0);
if ($selectedCommitteeId <= 0) {
    redirect(APP_URL . '/modules/workload/index.php');
}
$taskState = in_array($_GET['task_state'] ?? 'active', ['active', 'done'], true)
    ? ($_GET['task_state'] ?? 'active')
    : 'active';
$_GET['task_state'] = $taskState;

$committeeStmt = $pdo->prepare(
    "SELECT c.committee_id, c.committee_name, c.status, c.jurisdiction_id,
            COALESCE(j.jurisdiction_name, (
                SELECT j2.jurisdiction_name
                FROM jurisdictions j2
                WHERE j2.category = c.committee_name AND j2.status = 'Active'
                ORDER BY j2.jurisdiction_name
                LIMIT 1
            )) AS jurisdiction_name,
            (SELECT GROUP_CONCAT(DISTINCT j2.jurisdiction_id
                                 ORDER BY (j2.jurisdiction_id = c.jurisdiction_id) DESC, j2.jurisdiction_name
                                 SEPARATOR ',')
             FROM jurisdictions j2
             WHERE j2.status = 'Active'
               AND (j2.category = c.committee_name OR j2.jurisdiction_id = c.jurisdiction_id)
            ) AS jurisdiction_ids
     FROM committees c
  LEFT JOIN jurisdictions j ON j.jurisdiction_id = c.jurisdiction_id
  WHERE committee_id = :id
    AND (:is_manager = 1 OR c.committee_id IN
      (SELECT committee_id FROM committee_members WHERE user_id = :uid AND status = 'Active'))
  LIMIT 1"
);
$committeeStmt->execute([':id' => $selectedCommitteeId, ':is_manager' => canManage() ? 1 : 0, ':uid' => currentUserId()]);
$committee = $committeeStmt->fetch();

if (!$committee) {
    setFlash('danger', 'Committee not found.');
    redirect(APP_URL . '/modules/workload/index.php');
}

$selectedCommitteeName = $committee['committee_name'];
$committeeJurisdictionIds = array_filter(array_map(
    'intval',
    explode(',', (string)($committee['jurisdiction_ids'] ?? ''))
));
$selectedCommitteeJurisdictionId = (int)($committee['jurisdiction_id'] ?? 0);
if (!in_array($selectedCommitteeJurisdictionId, $committeeJurisdictionIds, true)) {
    $selectedCommitteeJurisdictionId = (int)($committeeJurisdictionIds[0] ?? 0);
}

$summaryStmt = $pdo->prepare(
  "SELECT COUNT(wa.workload_id) AS total_assignments,
          COALESCE(SUM(wa.status = 'Completed'), 0) AS completed_assignments,
          COALESCE(SUM(wa.status <> 'Completed'), 0) AS active_assignments
     FROM workload_assignments wa
     INNER JOIN committee_members cm ON cm.committee_member_id = wa.committee_member_id
    WHERE cm.committee_id = :cid
      AND (:is_manager = 1 OR cm.user_id = :uid)"
);
  $summaryStmt->execute([':cid' => $selectedCommitteeId, ':is_manager' => canManage() ? 1 : 0, ':uid' => currentUserId()]);
$committeeSummary = $summaryStmt->fetch() ?: ['total_assignments' => 0];

$embed = !empty($_GET['embed']); // chromeless mode for the workload iframe modal
include __DIR__ . '/../../layouts/header.php';
?>
<?php if ($embed): ?>
<div class="p-3">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
      <h5 class="mb-1"><i class="bi bi-diagram-3 text-primary"></i> <?= e($selectedCommitteeName) ?> Workload</h5>
      <?php if (!empty($committee['jurisdiction_name'])): ?>
        <small class="text-muted"><i class="bi bi-geo-alt"></i> <?= e($committee['jurisdiction_name']) ?></small>
      <?php endif; ?>
    </div>
    <?php if (canManage()): ?>
      <button type="button" class="btn btn-assign-task btn-sm" id="btnAddTask" data-committee-id="<?= (int)$selectedCommitteeId ?>">
        <i class="bi bi-plus-circle"></i> Assign a Task
      </button>
    <?php endif; ?>
  </div>
<?php else: ?>
<div class="app-wrapper">
  <?php include __DIR__ . '/../../layouts/sidebar.php'; ?>

  <div class="main-content workload-committee-page admin-polished-page">
    <?php include __DIR__ . '/../../layouts/content-topbar.php'; ?>

    <div class="breadcrumb-bar workload-committee-hero d-flex justify-content-between align-items-center flex-wrap gap-2">
      <div>
        <nav aria-label="breadcrumb" class="mb-1">
          <ol class="breadcrumb mb-0 small">
            <li class="breadcrumb-item"><a href="index.php">Workload</a></li>
            <li class="breadcrumb-item active"><?= e($selectedCommitteeName) ?></li>
          </ol>
        </nav>
        <div>
          <h5 class="mb-1 workload-committee-title"><i class="bi bi-diagram-3" aria-hidden="true"></i> <?= e($selectedCommitteeName) ?> Workload</h5>
          <?php if (!empty($committee['jurisdiction_name'])): ?>
            <small class="text-muted workload-committee-jurisdiction"><i class="bi bi-geo-alt" aria-hidden="true"></i> <?= e($committee['jurisdiction_name']) ?></small>
          <?php endif; ?>
        </div>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <?php if (canManage()): ?>
          <button type="button" class="btn btn-assign-task btn-sm" id="btnAddTask" data-committee-id="<?= (int)$selectedCommitteeId ?>">
            <i class="bi bi-plus-circle"></i> Assign a Task
          </button>
        <?php endif; ?>
        <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Choose another committee</a>
      </div>
    </div>
<?php endif; ?>

    <div class="workload-mini-overview workload-committee-summary mb-3">
      <div class="workload-mini-overview-header">
        <span class="workload-mini-title">Assignment overview</span>
      </div>
      <div class="workload-mini-stat-grid">
        <div class="workload-mini-stat workload-mini-stat-pending">
          <strong><?= (int)$committeeSummary['active_assignments'] ?></strong>
          <span>active</span>
        </div>
        <div class="workload-mini-stat workload-mini-stat-completed">
          <strong><?= (int)$committeeSummary['completed_assignments'] ?></strong>
          <span>done</span>
        </div>
      </div>
    </div>

    <div class="workload-task-state-switch mb-3" role="group" aria-label="Filter tasks by completion status">
      <button type="button" class="workload-state-filter <?= $taskState === 'active' ? 'is-active' : '' ?>" data-task-state="active" aria-pressed="<?= $taskState === 'active' ? 'true' : 'false' ?>">
        <i class="bi bi-list-task" aria-hidden="true"></i> Active tasks
        <span><?= (int)$committeeSummary['active_assignments'] ?></span>
      </button>
      <button type="button" class="workload-state-filter <?= $taskState === 'done' ? 'is-active' : '' ?>" data-task-state="done" aria-pressed="<?= $taskState === 'done' ? 'true' : 'false' ?>">
        <i class="bi bi-check2-circle" aria-hidden="true"></i> Done tasks
        <span><?= (int)$committeeSummary['completed_assignments'] ?></span>
      </button>
    </div>

    <div class="card workload-search-panel mb-3">
      <div class="card-body py-3">
        <form id="filterForm" data-live-search="true">
          <input type="hidden" name="committee_id" value="<?= (int)$selectedCommitteeId ?>">
          <input type="hidden" name="task_state" value="<?= e($taskState) ?>">
          <input type="hidden" name="priority" value="">
          <input type="hidden" name="sort" value="due_date">
          <input type="hidden" name="dir" value="desc">
          <label class="visually-hidden" for="searchInput">Search task title</label>
          <div class="workload-task-search">
            <i class="bi bi-search" aria-hidden="true"></i>
            <input type="search" class="form-control" id="searchInput" name="search" placeholder="Search task title..." aria-label="Search task title">
          </div>
        </form>
      </div>
    </div>

    <div class="card workload-results-panel">
      <div id="workloadTableWrap">
        <?php include __DIR__ . '/table.php'; ?>
      </div>
    </div>
  </div>
<?php if (!$embed): ?></div><?php endif; ?>

<?php if (canManage()): ?>
<div class="modal fade" id="taskModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form id="taskForm">
        <?= csrfField() ?>
        <input type="hidden" name="id" id="wl_id" value="0">
        <input type="hidden" name="ai_recommendation_id" id="wl_ai_recommendation_id" value="">
        <div class="modal-header">
          <h5 class="modal-title" id="taskModalTitle"><i class="bi bi-bar-chart-steps"></i> Propose Task</h5>
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
            <div class="task-step-heading"><span class="task-step-kicker">Step 1</span><h6>Set up the task</h6><p>Choose the committee and standard task for this assignment.</p></div>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Committee <span class="text-danger">*</span></label>
                <select name="committee_id" id="wl_committee" class="form-select" required>
                  <option value="">-- Select Committee --</option>
                  <?php foreach ($activeCommittees as $c): ?>
                    <option value="<?= (int)$c['committee_id'] ?>" data-jurisdiction-id="<?= e((string)($c['jurisdiction_ids'] ?? '')) ?>" <?= (int)$c['committee_id'] === $selectedCommitteeId ? 'selected' : '' ?>><?= e($c['committee_name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">Select Jurisdiction <span class="text-danger">*</span></label>
                <select name="jurisdiction_id" id="wl_jurisdiction" class="form-select" required>
                  <option value="">-- Select Jurisdiction --</option>
                  <?php foreach ($activeJurisdictions as $jurisdiction): ?>
                    <option value="<?= (int)$jurisdiction['jurisdiction_id'] ?>" <?= (int)$jurisdiction['jurisdiction_id'] === $selectedCommitteeJurisdictionId ? 'selected' : '' ?>><?= e($jurisdiction['jurisdiction_name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-12">
                <label class="form-label">Select Standard Task <span class="text-danger">*</span></label>
                <select name="task_template_id" id="wl_template" class="form-select" required disabled>
                  <option value="">-- Select Jurisdiction First --</option>
                </select>
              </div>
              <input type="hidden" name="task_title" id="wl_title">
              <div class="col-12">
                <button type="button" class="btn btn-outline-primary" id="btnGenerateAI"><i class="bi bi-stars"></i> Generate with AI</button>
                <div class="form-text">Optional: generate assignment details after selecting a standard task.</div>
              </div>
            </div>
            <div class="mt-3" id="wl_ai_loading" style="display:none;"><div class="task-ai-loading"><span class="spinner-border spinner-border-sm text-primary"></span><span class="fw-semibold small text-primary" id="wl_ai_loading_text">AI is analyzing the task...</span></div></div>
          </section>

          <section class="task-step-panel" data-step-panel="2">
            <div class="task-step-heading"><span class="task-step-kicker">Step 2</span><h6>Assign the work</h6><p>Choose the responsible member and set the task priority.</p></div>
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label">Assign To <span class="text-danger">*</span></label><select name="committee_member_id" id="wl_member" class="form-select" required><option value="">-- Select Committee First --</option></select></div>
              <div class="col-md-3"><label class="form-label">Priority</label><select name="priority" id="wl_priority" class="form-select"><?php foreach (['Low', 'Medium', 'High', 'Urgent'] as $p): ?><option value="<?= e($p) ?>" <?= $p === 'Medium' ? 'selected' : '' ?>><?= e($p) ?></option><?php endforeach; ?></select></div>
            </div>
            <div class="mt-3" id="wl_ai_panel_wrap" style="display:none;"><div class="task-ai-result"><div class="d-flex justify-content-between align-items-center"><span class="small fw-semibold text-primary"><i class="bi bi-cpu"></i> AI Recommendation</span><span id="wl_ai_error_badge" class="badge bg-warning text-dark" style="display:none;"></span></div><div id="wl_ai_summary" class="small mt-2"></div><div id="wl_ai_reasoning" class="small text-muted mt-2 fst-italic"></div></div></div>
          </section>

          <section class="task-step-panel" data-step-panel="3">
            <div class="task-step-heading"><span class="task-step-kicker">Step 3</span><h6>Review and submit</h6><p>Complete the details and submit the proposal for your Committee Chairperson or an Administrator to review.</p></div>
            <div class="row g-3">
              <div class="col-12"><label class="form-label task-description-label">Description <span id="wl_ai_desc_badge" class="badge bg-primary-subtle text-primary border border-primary-subtle task-ai-badge" style="display:none;"><i class="bi bi-stars"></i> AI Generated by Gemini</span></label><textarea name="task_description" id="wl_description" class="form-control" rows="3" placeholder="Describe the task, or generate one in Step 1."></textarea></div>
              <div class="col-md-6"><label class="form-label">Due Date</label><input type="date" name="due_date" id="wl_due" class="form-control"></div>
            </div>
          </section>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="task-step-button" id="taskStepPrevious"><i class="bi bi-arrow-left"></i> Previous</button>
          <button type="button" class="task-step-button task-step-button-primary" id="taskStepNext">Next <i class="bi bi-arrow-right"></i></button>
          <button type="submit" class="btn btn-primary" id="taskStepSave" style="display:none;"><i class="bi bi-send"></i> Submit for Review</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/_task_member_modals.php'; ?>

<?php
$extraJs = [
    APP_URL . '/assets/js/workload.js?v=' . (int)@filemtime(__DIR__ . '/../../assets/js/workload.js'),
    APP_URL . '/assets/js/workload-jurisdiction.js?v=' . (int)@filemtime(__DIR__ . '/../../assets/js/workload-jurisdiction.js'),
    APP_URL . '/assets/js/workload-templates.js?v=' . (int)@filemtime(__DIR__ . '/../../assets/js/workload-templates.js'),
];
if (isCommitteeMember()) {
    $extraJs[] = APP_URL . '/assets/js/task-member-requests.js?v=' . (int)@filemtime(__DIR__ . '/../../assets/js/task-member-requests.js');
}
include __DIR__ . '/../../layouts/footer.php';
?>
