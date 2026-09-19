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
      <form id="filterForm" class="d-flex flex-wrap align-items-end gap-3">
        <span class="badge badge-soft-neutral d-none align-self-center" id="activeFilterCount"></span>
        <div style="min-width:240px;flex:1 1 240px;">
          <label class="form-label small mb-1" for="searchInput">Search</label>
          <div class="input-group input-group-sm">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" class="form-control" id="searchInput" name="search"
                   placeholder="Jurisdiction name, category, or description...">
          </div>
        </div>
        <div>
          <label class="form-label small mb-1">Status</label>
          <select class="form-select form-select-sm" name="status" style="min-width:120px;">
            <option value="">All Statuses</option>
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
          </select>
        </div>
        <div>
          <label class="form-label small mb-1">Committees</label>
          <select class="form-select form-select-sm" name="committees" aria-label="Filter by committee coverage" style="min-width:160px;">
            <option value="">Any</option>
            <option value="some">With committees</option>
            <option value="none">Without committees</option>
          </select>
        </div>
        <div>
          <label class="form-label small mb-1">Sort</label>
          <select class="form-select form-select-sm" name="sort" aria-label="Sort by" style="min-width:140px;">
            <option value="jurisdiction_name">Name</option>
            <option value="category">Category</option>
            <option value="committee_count">Committees</option>
            <option value="status">Status</option>
          </select>
        </div>
        <div>
          <label class="form-label small mb-1">Direction</label>
          <select class="form-select form-select-sm" name="dir" aria-label="Sort direction" style="min-width:90px;">
            <option value="asc">Asc</option>
            <option value="desc">Desc</option>
          </select>
        </div>
        <div class="ms-auto d-flex gap-2">
          <button type="submit" class="btn btn-primary btn-sm position-relative" id="filterApply">
            <i class="bi bi-check2"></i> Apply<span class="apply-pending-dot d-none" id="applyPendingDot" aria-hidden="true"></span>
          </button>
          <button type="button" class="btn btn-outline-secondary btn-sm" id="filterReset"><i class="bi bi-arrow-counterclockwise"></i> Reset</button>
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
  <div class="modal-dialog modal-lg">
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
            <div class="jurisdiction-stepper-step" data-jurisdiction-step-indicator="2"><span>2</span><div><strong>Scope</strong><small>Definition and subjects</small></div></div>
            <div class="jurisdiction-stepper-line"></div>
            <div class="jurisdiction-stepper-step" data-jurisdiction-step-indicator="3"><span>3</span><div><strong>Review</strong><small>Status and notes</small></div></div>
          </div>

          <section class="jurisdiction-step-panel is-active" data-jurisdiction-step-panel="1">
            <div class="jurisdiction-step-heading"><span>Step 1</span><h6>Name the jurisdiction</h6><p>Give this jurisdiction a clear and recognizable name.</p></div>
            <label class="form-label">Jurisdiction Name <span class="text-danger">*</span></label>
            <input type="text" name="jurisdiction_name" id="jd_name" class="form-control" required maxlength="150" placeholder="e.g. Public Safety">
          </section>

          <section class="jurisdiction-step-panel" data-jurisdiction-step-panel="2">
            <div class="jurisdiction-step-heading"><span>Step 2</span><h6>Define the scope</h6><p>Explain what this jurisdiction covers and the matters it normally handles.</p></div>
            <div class="mb-3"><label class="form-label">Category</label><input type="text" name="category" id="jd_category" class="form-control" maxlength="100" placeholder="e.g. Finance, Public Safety"></div>
            <div class="mb-3"><label class="form-label">Description</label><textarea name="description" id="jd_description" class="form-control" rows="3" placeholder="Briefly describe this jurisdiction."></textarea></div>
            <div class="mb-3"><label class="form-label">Scope Definition</label><textarea name="scope_definition" id="jd_scope_definition" class="form-control" rows="4" placeholder="Explain what matters and subjects are covered."></textarea></div>
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label">Covered Areas / Subjects</label><textarea name="covered_areas" id="jd_covered_areas" class="form-control" rows="4" placeholder="List covered subjects, one per line."></textarea></div>
              <div class="col-md-6"><label class="form-label">Primary Responsibilities</label><textarea name="primary_responsibilities" id="jd_primary_responsibilities" class="form-control" rows="4" placeholder="List primary responsibilities, one per line."></textarea></div>
              <div class="col-md-6"><label class="form-label">Typical Legislative Matters</label><textarea name="typical_legislative_matters" id="jd_typical_legislative_matters" class="form-control" rows="4" placeholder="List typical matters, one per line."></textarea></div>
              <div class="col-md-6"><label class="form-label">Outside Scope</label><textarea name="outside_scope" id="jd_outside_scope" class="form-control" rows="4" placeholder="Describe matters outside this jurisdiction."></textarea></div>
            </div>
          </section>

          <section class="jurisdiction-step-panel" data-jurisdiction-step-panel="3">
            <div class="jurisdiction-step-heading"><span>Step 3</span><h6>Review and save</h6><p>Set availability and add any additional notes.</p></div>
            <label class="form-label">Status</label>
            <select name="status" id="jd_status" class="form-select">
              <option value="Active">Active</option>
              <option value="Inactive">Inactive</option>
            </select>
            <div class="mt-3"><label class="form-label">Notes</label><textarea name="notes" id="jd_notes" class="form-control" rows="4" placeholder="Add internal notes or clarifications."></textarea></div>
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

<!-- Jurisdiction details modal — mirrors view.php layout -->
<div class="modal fade" id="jurisdictionViewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-geo-alt text-primary"></i> <span id="jvName">Jurisdiction</span> <span class="badge ms-1" id="jvStatus"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-lg-5">
            <div class="card h-100">
              <div class="card-header"><i class="bi bi-info-circle"></i> Overview</div>
              <div class="card-body">
                <dl class="row mb-0 small">
                  <dt class="col-5">Jurisdiction Name</dt><dd class="col-7" id="jvNameDl"></dd>
                  <dt class="col-5">Category</dt><dd class="col-7" id="jvCategory"></dd>
                  <dt class="col-5">Status</dt><dd class="col-7" id="jvStatusDl"></dd>
                  <dt class="col-5">Committees</dt><dd class="col-7" id="jvCount"></dd>
                </dl>
                <hr>
                <h6 class="small text-uppercase text-muted">Description</h6>
                <p class="mb-0 small" style="white-space:pre-line" id="jvDescription"></p>
              </div>
            </div>
          </div>
          <div class="col-lg-7">
            <div class="card h-100">
              <div class="card-header"><i class="bi bi-bullseye"></i> Scope Definition</div>
              <div class="card-body">
                <h6>Scope Definition</h6>
                <p class="small mb-4" style="white-space:pre-line" id="jvScope"></p>
                <h6>Covered Areas / Subjects</h6>
                <p class="small mb-0" style="white-space:pre-line" id="jvCovered"></p>
              </div>
            </div>
          </div>
          <div class="col-lg-6">
            <div class="card h-100">
              <div class="card-header"><i class="bi bi-list-check"></i> Responsibilities</div>
              <div class="card-body">
                <h6>Primary Responsibilities</h6>
                <p class="small mb-4" style="white-space:pre-line" id="jvResp"></p>
                <h6>Typical Legislative Matters</h6>
                <p class="small mb-0" style="white-space:pre-line" id="jvMatters"></p>
              </div>
            </div>
          </div>
          <div class="col-lg-6">
            <div class="card h-100">
              <div class="card-header"><i class="bi bi-sign-stop"></i> Limitations</div>
              <div class="card-body">
                <h6>Outside Scope</h6>
                <p class="small mb-0" style="white-space:pre-line" id="jvOutside"></p>
              </div>
            </div>
          </div>
          <div class="col-12" id="jvNotesWrap">
            <div class="card">
              <div class="card-header"><i class="bi bi-sticky"></i> Notes</div>
              <div class="card-body small" style="white-space:pre-line" id="jvNotes"></div>
            </div>
          </div>
          <div class="col-12">
            <div class="card">
              <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-diagram-3"></i> Committees Under This Jurisdiction</span>
                <span class="badge bg-light text-dark border" id="jvCountBadge">0</span>
              </div>
              <div id="jvCommitteesWrap">
                <div class="card-body text-muted small" id="jvCommitteesEmpty">No committees are currently associated with this jurisdiction.</div>
                <div class="table-responsive d-none" id="jvCommitteesTableWrap">
                  <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>Committee Name</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                    <tbody id="jvCommitteesBody"></tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <a href="#" class="btn btn-outline-secondary btn-sm" id="jvFullView"><i class="bi bi-box-arrow-up-right"></i> Open full page</a>
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<?php
$extraJs = [APP_URL . '/assets/js/jurisdictions.js?v=4'];
include __DIR__ . '/../../layouts/footer.php';
?>
