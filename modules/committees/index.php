<?php
/**
 * modules/committees/index.php
 * ------------------------------------------------------------------
 * Committee Management and Assignment System - Committee Formation
 * (Module 1). Lists all committees with search/filter, and a
 * Create/Edit modal. Reuses the shared app shell (header/sidebar/
 * footer) and global style.css - no new layout is introduced here.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../../includes/auth.php';
requireRole([ROLE_ADMIN, ROLE_STAFF, ROLE_COMMITTEE]);

$pageTitle  = 'Committees';
$activeMenu = 'committees';
$pdo = db();

$jurisdictions = $pdo->query('SELECT jurisdiction_id, jurisdiction_name FROM jurisdictions WHERE status = \'Active\' ORDER BY jurisdiction_name')->fetchAll();

include __DIR__ . '/../../layouts/header.php';
?>
<div class="app-wrapper">
  <?php include __DIR__ . '/../../layouts/sidebar.php'; ?>

  <div class="main-content">
  <?php include __DIR__ . '/../../layouts/content-topbar.php'; ?>
  <div class="breadcrumb-bar d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <h5 class="mb-0"><i class="bi bi-diagram-3 text-primary"></i> Committee Management</h5>
      <small class="text-muted">Create committees, assign jurisdictions, and manage membership.</small>
    </div>
    <div class="d-flex gap-2">
      <?php if (!isCommitteeMember()): ?>
        <a class="btn btn-outline-secondary btn-sm" href="<?= e(APP_URL) ?>/modules/jurisdictions/index.php">
          <i class="bi bi-geo-alt"></i> Jurisdictions
        </a>
      <?php endif; ?>
      <?php if (canManage()): ?>
        <button type="button" class="btn btn-primary btn-sm" id="btnAddCommittee">
          <i class="bi bi-plus-circle"></i> New Committee
        </button>
      <?php endif; ?>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-body py-3">
      <form id="filterForm" class="row g-2 align-items-center">
        <div class="col-md-5">
          <input type="text" class="form-control form-control-sm" id="searchInput" name="search"
                 placeholder="Search committee name or description...">
        </div>
        
        <div class="col-md-3">
          <select class="form-select form-select-sm" name="status">
            <option value="">All Statuses</option>
            <?php foreach (['Active', 'Inactive'] as $s): ?>
              <option value="<?= e($s) ?>"><?= e($s) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div id="committeesTableWrap">
      <?php include __DIR__ . '/table.php'; ?>
    </div>
  </div>

<?php if (canManage()): ?>
<div class="modal fade" id="committeeModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="committeeForm">
        <?= csrfField() ?>
        <input type="hidden" name="id" id="cm_id" value="0">
        <div class="modal-header">
          <h5 class="modal-title" id="committeeModalTitle"><i class="bi bi-diagram-3"></i> New Committee</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="committee-stepper" aria-label="Committee creation progress">
            <div class="committee-stepper-step is-active" data-committee-step-indicator="1"><span>1</span><div><strong>Identity</strong><small>Name</small></div></div>
            <div class="committee-stepper-line"></div>
            <div class="committee-stepper-step" data-committee-step-indicator="2"><span>2</span><div><strong>Details</strong><small>Description and scope</small></div></div>
            <div class="committee-stepper-line"></div>
            <div class="committee-stepper-step" data-committee-step-indicator="3"><span>3</span><div><strong>Setup</strong><small>Date and status</small></div></div>
          </div>
          <section class="committee-step-panel is-active" data-committee-step-panel="1">
            <div class="committee-step-heading"><span>Step 1</span><h6>Name the committee</h6><p>Give the new committee a clear and recognizable name.</p></div>
            <label class="form-label">Committee Name <span class="text-danger">*</span></label>
            <input type="text" name="committee_name" id="cm_name" class="form-control" required maxlength="150" placeholder="e.g. Committee on Budget and Appropriations">
          </section>
          <section class="committee-step-panel" data-committee-step-panel="2">
            <div class="committee-step-heading"><span>Step 2</span><h6>Add committee details</h6><p>Describe the committee and connect it to a jurisdiction.</p></div>
            <div class="row g-3">
              <div class="col-12"><label class="form-label">Description</label><textarea name="description" id="cm_description" class="form-control" rows="4" placeholder="Describe the committee's purpose and responsibilities."></textarea></div>
              <div class="col-12"><label class="form-label">Jurisdiction</label><select name="jurisdiction_id" id="cm_jurisdiction" class="form-select"><option value="">-- None --</option><?php foreach ($jurisdictions as $j): ?><option value="<?= (int)$j['jurisdiction_id'] ?>"><?= e($j['jurisdiction_name']) ?></option><?php endforeach; ?></select></div>
            </div>
          </section>
          <section class="committee-step-panel" data-committee-step-panel="3">
            <div class="committee-step-heading"><span>Step 3</span><h6>Finish the setup</h6><p>Choose the creation date and current committee status.</p></div>
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label">Date Created</label><input type="date" name="date_created" id="cm_date_created" class="form-control"></div>
              <div class="col-md-6"><label class="form-label">Status</label><select name="status" id="cm_status" class="form-select"><?php foreach (['Active', 'Inactive', 'Dissolved'] as $s): ?><option value="<?= e($s) ?>"><?= e($s) ?></option><?php endforeach; ?></select></div>
            </div>
          </section>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="committee-step-button" id="committeeStepPrevious"><i class="bi bi-arrow-left"></i> Previous</button>
          <button type="button" class="committee-step-button committee-step-button-primary" id="committeeStepNext">Next <i class="bi bi-arrow-right"></i></button>
          <button type="submit" class="btn btn-primary" id="committeeStepSave" style="display:none;"><i class="bi bi-check-circle"></i> Save Committee</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<?php
$extraJs = [APP_URL . '/assets/js/committees.js'];
include __DIR__ . '/../../layouts/footer.php';
?>
