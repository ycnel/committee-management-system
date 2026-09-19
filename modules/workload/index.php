<?php
/**
 * modules/workload/index.php
 * ------------------------------------------------------------------
 * Smart Workload Distribution module (Module 4). Shows workload
 * assignment distribution, a per-committee member assignment count,
 * and the full assignment list with a Create/Edit modal.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../../includes/auth.php';
requireRole([ROLE_ADMIN, ROLE_STAFF, ROLE_COMMITTEE]);

$pageTitle  = 'Lungsod ng Manila Committee Management and Assignment System';
$activeMenu = 'workload';
$pdo = db();
$selectedJurisdictionId = (int)($_GET['jurisdiction_id'] ?? 0);
$selectedCommitteeId = (int)($_GET['committee_id'] ?? 0);

if ($selectedJurisdictionId > 0 && $selectedCommitteeId <= 0) {
    $jurisdictionStmt = $pdo->prepare(
        "SELECT jurisdiction_id, jurisdiction_name, category, description
         FROM jurisdictions
         WHERE jurisdiction_id = :id AND status = 'Active'
         LIMIT 1"
    );
    $jurisdictionStmt->execute([':id' => $selectedJurisdictionId]);
    $selectedJurisdiction = $jurisdictionStmt->fetch();

    if (!$selectedJurisdiction) {
        setFlash('danger', 'Jurisdiction not found.');
        redirect(APP_URL . '/modules/workload/index.php');
    }

    if (isCommitteeMember()) {
        $jurisdictionCommitteesStmt = $pdo->prepare(
            "SELECT c.committee_id, c.committee_name, c.description, c.status
             FROM committees c
             INNER JOIN committee_members cm ON cm.committee_id = c.committee_id
             WHERE c.jurisdiction_id = :jurisdiction_id
               AND c.status = 'Active' AND cm.user_id = :user_id AND cm.status = 'Active'
             ORDER BY c.committee_name"
        );
        $jurisdictionCommitteesStmt->execute([
            ':jurisdiction_id' => $selectedJurisdictionId,
            ':user_id' => currentUserId(),
        ]);
    } else {
        $jurisdictionCommitteesStmt = $pdo->prepare(
            "SELECT committee_id, committee_name, description, status
             FROM committees
             WHERE jurisdiction_id = :jurisdiction_id AND status = 'Active'
             ORDER BY committee_name"
        );
        $jurisdictionCommitteesStmt->execute([':jurisdiction_id' => $selectedJurisdictionId]);
    }
    $jurisdictionCommittees = $jurisdictionCommitteesStmt->fetchAll();

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
                <li class="breadcrumb-item"><a href="index.php">Workload Distribution</a></li>
                <li class="breadcrumb-item active"><?= e($selectedJurisdiction['jurisdiction_name']) ?></li>
              </ol>
            </nav>
            <h5 class="mb-0"><i class="bi bi-geo-alt text-primary"></i> <?= e($selectedJurisdiction['jurisdiction_name']) ?></h5>
            <small class="text-muted">Select a committee to view its workload distribution and tasks.</small>
          </div>
          <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back to Jurisdictions</a>
        </div>

        <section class="workload-committee-panel" aria-labelledby="jurisdictionCommitteeHeading">
          <div class="workload-section-heading">
            <div>
              <h6 id="jurisdictionCommitteeHeading" class="mb-1">Committees under this jurisdiction</h6>
              <p class="small text-muted mb-0"><?= e($selectedJurisdiction['description'] ?: 'Choose a committee to continue.') ?></p>
            </div>
            <span class="badge bg-light text-dark border"><?= count($jurisdictionCommittees) ?> committee<?= count($jurisdictionCommittees) === 1 ? '' : 's' ?></span>
          </div>
          <?php if (empty($jurisdictionCommittees)): ?>
            <p class="text-muted small mb-0">No active committees are associated with this jurisdiction.</p>
          <?php else: ?>
            <div class="workload-committee-grid">
              <?php foreach ($jurisdictionCommittees as $committee): ?>
                <a href="committee.php?committee_id=<?= (int)$committee['committee_id'] ?>" class="workload-committee-card">
                  <span class="workload-committee-inner">
                    <span class="workload-committee-form" aria-label="Committee icon"><i class="bi bi-people-fill workload-committee-card-icon"></i></span>
                    <span class="workload-committee-data">
                      <span class="workload-committee-text">
                        <span class="workload-committee-card-name"><?= e($committee['committee_name']) ?></span>
                        <span class="workload-committee-card-description"><?= e($committee['description'] ?: 'View tasks, assignments, priorities, and due dates for this committee.') ?></span>
                      </span>
                    </span>
                  </span>
                </a>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </section>
      </div>
    </div>
    <?php
    include __DIR__ . '/../../layouts/footer.php';
    exit;
}

if ($selectedJurisdictionId <= 0 && $selectedCommitteeId <= 0) {
    if (isCommitteeMember()) {
        $jurisdictionsStmt = $pdo->prepare(
            "SELECT DISTINCT j.jurisdiction_id, j.jurisdiction_name, j.category, j.description,
                    (SELECT COUNT(*) FROM committees c2 WHERE c2.jurisdiction_id = j.jurisdiction_id AND c2.status = 'Active') AS committee_count
             FROM jurisdictions j
             INNER JOIN committees c ON c.jurisdiction_id = j.jurisdiction_id AND c.status = 'Active'
             INNER JOIN committee_members cm ON cm.committee_id = c.committee_id
             WHERE j.status = 'Active' AND cm.user_id = :user_id AND cm.status = 'Active'
             ORDER BY j.jurisdiction_name"
        );
        $jurisdictionsStmt->execute([':user_id' => currentUserId()]);
    } else {
        $jurisdictionsStmt = $pdo->query(
            "SELECT j.jurisdiction_id, j.jurisdiction_name, j.category, j.description,
                    (SELECT COUNT(*) FROM committees c WHERE c.jurisdiction_id = j.jurisdiction_id AND c.status = 'Active') AS committee_count
             FROM jurisdictions j
             WHERE j.status = 'Active'
             ORDER BY j.jurisdiction_name"
        );
    }
    $workloadJurisdictions = $jurisdictionsStmt->fetchAll();

    include __DIR__ . '/../../layouts/header.php';
    ?>
    <div class="app-wrapper">
      <?php include __DIR__ . '/../../layouts/sidebar.php'; ?>
      <div class="main-content">
        <?php include __DIR__ . '/../../layouts/content-topbar.php'; ?>
        <div class="breadcrumb-bar d-flex justify-content-between align-items-center flex-wrap gap-2">
          <div>
            <h5 class="mb-0"><i class="bi bi-bar-chart-steps text-primary"></i> Smart Workload Distribution</h5>
            <small class="text-muted">Start by selecting a jurisdiction to view its committees and workload.</small>
          </div>
          <?php if (canEditAiSettings()): ?><a href="ai_settings.php" class="btn btn-outline-primary btn-sm"><i class="bi bi-stars"></i> Smart AI Settings</a><?php endif; ?>
        </div>
        <section class="workload-committee-panel" aria-labelledby="workloadJurisdictionHeading">
          <div class="workload-section-heading">
            <div><h6 id="workloadJurisdictionHeading" class="mb-1">Choose a jurisdiction</h6><p class="small text-muted mb-0">Jurisdictions are shown using the existing CMAS jurisdiction card design.</p></div>
          </div>
          <?php if (empty($workloadJurisdictions)): ?>
            <p class="text-muted small mb-0">No active jurisdictions are available.</p>
          <?php else: ?>
            <div class="jurisdiction-card-grid">
              <?php foreach ($workloadJurisdictions as $jurisdiction): ?>
                <a href="index.php?jurisdiction_id=<?= (int)$jurisdiction['jurisdiction_id'] ?>" class="jurisdiction-card text-decoration-none" aria-label="View <?= e($jurisdiction['jurisdiction_name']) ?> workload">
                  <div class="jurisdiction-card-details">
                    <div class="jurisdiction-card-meta"><span><?= e($jurisdiction['category'] ?: 'General scope') ?></span><span><?= (int)$jurisdiction['committee_count'] ?> committee<?= (int)$jurisdiction['committee_count'] === 1 ? '' : 's' ?></span></div>
                    <div class="jurisdiction-card-title"><?= e($jurisdiction['jurisdiction_name']) ?></div>
                    <div class="jurisdiction-card-description"><?= e(truncate($jurisdiction['description'] ?: 'Select to view associated committees and workloads.', 110)) ?></div>
                    <div class="jurisdiction-card-footer"><span class="jurisdiction-card-status status-active">Active</span><span class="jurisdiction-action-button"><i class="bi bi-arrow-right"></i></span></div>
                  </div>
                </a>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </section>
      </div>
    </div>
    <?php
    include __DIR__ . '/../../layouts/footer.php';
    exit;
}

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

// ---- Assignment summary ----------------------------------------------
if (isCommitteeMember()) {
    $summaryStmt = $pdo->prepare(
    "SELECT COUNT(wa.workload_id) AS total_assignments
         FROM workload_assignments wa
         INNER JOIN committee_members cm ON cm.committee_member_id = wa.committee_member_id
         WHERE cm.user_id = :uid"
    );
    $summaryStmt->execute([':uid' => currentUserId()]);
    $summary = $summaryStmt->fetch();
} else {
  $summary = $pdo->query("SELECT COUNT(*) AS total_assignments FROM workload_assignments")->fetch();
}
$summary = $summary ?: ['total_assignments' => 0];

$committeeTaskSummary = null;
if ($selectedCommitteeId > 0) {
    $committeeTaskSummaryStmt = $pdo->prepare(
      "SELECT COUNT(wa.workload_id) AS total_assignments
         FROM workload_assignments wa
         INNER JOIN committee_members cm ON cm.committee_member_id = wa.committee_member_id
            WHERE cm.committee_id = :cid
           AND (:is_manager = 1 OR cm.user_id = :uid)"
    );
          $committeeTaskSummaryStmt->execute([':cid' => $selectedCommitteeId, ':is_manager' => canManage() ? 1 : 0, ':uid' => currentUserId()]);
    $committeeTaskSummary = $committeeTaskSummaryStmt->fetch() ?: ['total_assignments' => 0];
}

// ---- Recommendation panel: factual active-assignment counts -----------
// This cross-member comparison is a task-assignment decision-support tool
// for Administrator (view) and Committee Chairperson (use) only. Committee
// Member does not get this view -- they only ever see their own workload.
$recCommittee = (int)($_GET['rec_committee'] ?? ($committees[0]['committee_id'] ?? 0));
$recommendations = [];
if ($recCommittee > 0 && !isCommitteeMember()) {
    $recStmt = $pdo->prepare(
        "SELECT cm.committee_member_id, u.full_name, cm.member_role,
                COUNT(wa.workload_id) AS active_assignments
         FROM committee_members cm
         INNER JOIN users u ON u.id = cm.user_id
         LEFT JOIN workload_assignments wa ON wa.committee_member_id = cm.committee_member_id
         WHERE cm.committee_id = :cid AND cm.status = 'Active'
         GROUP BY cm.committee_member_id, u.full_name, cm.member_role
         ORDER BY active_tasks ASC"
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
              <div class="stat-value"><?= (int)$summary['total_assignments'] ?></div>
            <div class="stat-label">Assigned Tasks</div>
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
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <p class="small text-muted mt-2 mb-0">
          <i class="bi bi-info-circle"></i> Recommendation is based on recorded assignment counts and member background data.
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
            <span class="workload-mini-subtitle">Assignment overview</span>
          </div>
          <div class="workload-mini-stat-grid">
            <div class="workload-mini-stat workload-mini-stat-pending">
              <span>Assigned</span>
              <strong><?= (int)($committeeTaskSummary['total_assignments'] ?? 0) ?></strong>
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
            </div>
          </section>

          <section class="task-step-panel" data-step-panel="3">
            <div class="task-step-heading"><span class="task-step-kicker">Step 3</span><h6>Review and save</h6><p>Complete the details, then save the assignment.</p></div>
            <div class="mt-3" id="wl_ai_panel_wrap" style="display:none;"><div class="task-ai-result"><div class="d-flex justify-content-between align-items-center"><span class="small fw-semibold text-primary"><i class="bi bi-cpu"></i> AI Recommendation</span><span id="wl_ai_error_badge" class="badge bg-warning text-dark" style="display:none;"></span></div><div id="wl_ai_summary" class="small mt-2"></div><div id="wl_ai_reasoning" class="small text-muted mt-2 fst-italic"></div></div></div>
            <div class="row g-3">
              <div class="col-12"><label class="form-label task-description-label">Description <span id="wl_ai_desc_badge" class="badge bg-primary-subtle text-primary border border-primary-subtle task-ai-badge" style="display:none;"><i class="bi bi-stars"></i> AI Generated</span></label><textarea name="task_description" id="wl_description" class="form-control" rows="3" placeholder="Describe the task, or generate one in Step 1."></textarea></div>
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
