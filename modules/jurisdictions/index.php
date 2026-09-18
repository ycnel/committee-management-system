<?php
/**
 * modules/jurisdictions/index.php
 * ------------------------------------------------------------------
 * Jurisdiction and Scope module (Module 3). Lists jurisdictions
 * that committees can be tied to, with search/filter and a
 * Create/Edit modal.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../../includes/auth.php';
requireRole([ROLE_ADMIN, ROLE_STAFF]);

$pageTitle  = 'Jurisdictions';
$activeMenu = 'jurisdictions';

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
          <li class="breadcrumb-item"><a href="<?= e(APP_URL) ?>/modules/committees/index.php">Committees</a></li>
          <li class="breadcrumb-item active">Jurisdictions</li>
        </ol>
      </nav>
      <h5 class="mb-0"><i class="bi bi-geo-alt text-primary"></i> Jurisdiction &amp; Scope</h5>
      <small class="text-muted">Define the subject-matter areas committees are formed around.</small>
    </div>
    <button type="button" class="btn btn-primary btn-sm" id="btnAddJurisdiction">
      <i class="bi bi-plus-circle"></i> New Jurisdiction
    </button>
  </div>

  <div class="card mb-3">
    <div class="card-body py-3">
      <form id="filterForm" class="row g-2 align-items-center">
        <div class="col-md-6">
          <input type="text" class="form-control form-control-sm" id="searchInput" name="search"
                 placeholder="Search jurisdiction name, category, or description...">
        </div>
        <div class="col-md-3">
          <select class="form-select form-select-sm" name="status">
            <option value="">All Statuses</option>
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
          </select>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div id="jurisdictionsTableWrap">
      <?php include __DIR__ . '/table.php'; ?>
    </div>
  </div>

<div class="modal fade" id="jurisdictionModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="jurisdictionForm">
        <?= csrfField() ?>
        <input type="hidden" name="id" id="jd_id" value="0">
        <div class="modal-header">
          <h5 class="modal-title" id="jurisdictionModalTitle"><i class="bi bi-geo-alt"></i> New Jurisdiction</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="jurisdiction-stepper" aria-label="Jurisdiction setup progress">
            <div class="jurisdiction-stepper-step is-active" data-jurisdiction-step-indicator="1"><span>1</span><div><strong>Identity</strong><small>Name</small></div></div>
            <div class="jurisdiction-stepper-line"></div>
            <div class="jurisdiction-stepper-step" data-jurisdiction-step-indicator="2"><span>2</span><div><strong>Scope</strong><small>Category and description</small></div></div>
            <div class="jurisdiction-stepper-line"></div>
            <div class="jurisdiction-stepper-step" data-jurisdiction-step-indicator="3"><span>3</span><div><strong>Status</strong><small>Activation</small></div></div>
          </div>

          <section class="jurisdiction-step-panel is-active" data-jurisdiction-step-panel="1">
            <div class="jurisdiction-step-heading"><span>Step 1</span><h6>Name the jurisdiction</h6><p>Give this jurisdiction a clear and recognizable name.</p></div>
            <label class="form-label">Jurisdiction Name <span class="text-danger">*</span></label>
            <input type="text" name="jurisdiction_name" id="jd_name" class="form-control" required maxlength="150" placeholder="e.g. Public Safety">
          </section>

          <section class="jurisdiction-step-panel" data-jurisdiction-step-panel="2">
            <div class="jurisdiction-step-heading"><span>Step 2</span><h6>Define the scope</h6><p>Add a category and describe the jurisdiction's responsibilities.</p></div>
            <div class="mb-3"><label class="form-label">Category</label><input type="text" name="category" id="jd_category" class="form-control" maxlength="100" placeholder="e.g. Finance, Public Safety"></div>
            <div><label class="form-label">Description</label><textarea name="description" id="jd_description" class="form-control" rows="4" placeholder="Describe the jurisdiction's scope and responsibilities."></textarea></div>
          </section>

          <section class="jurisdiction-step-panel" data-jurisdiction-step-panel="3">
            <div class="jurisdiction-step-heading"><span>Step 3</span><h6>Set the status</h6><p>Choose whether this jurisdiction is currently available.</p></div>
            <label class="form-label">Status</label>
            <select name="status" id="jd_status" class="form-select">
              <option value="Active">Active</option>
              <option value="Inactive">Inactive</option>
            </select>
          </section>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="jurisdiction-step-button" id="jurisdictionStepPrevious"><i class="bi bi-arrow-left"></i> Previous</button>
          <button type="button" class="jurisdiction-step-button jurisdiction-step-button-primary" id="jurisdictionStepNext">Next <i class="bi bi-arrow-right"></i></button>
          <button type="submit" class="btn btn-primary" id="jurisdictionStepSave" style="display:none;"><i class="bi bi-check-circle"></i> Save Jurisdiction</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
$extraJs = [APP_URL . '/assets/js/jurisdictions.js'];
include __DIR__ . '/../../layouts/footer.php';
?>
