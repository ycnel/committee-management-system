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
      <form id="filterForm" class="d-flex flex-wrap align-items-end gap-3">
        <span class="badge badge-soft-neutral d-none align-self-center" id="activeFilterCount"></span>
        <div style="min-width:230px;flex:1 1 230px;">
          <label class="form-label small mb-1" for="searchInput">Search</label>
          <div class="input-group input-group-sm">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" class="form-control" id="searchInput" name="search"
                   placeholder="Committee name or description...">
          </div>
        </div>
        <div>
          <label class="form-label small mb-1">Status</label>
          <select class="form-select form-select-sm" name="status" aria-label="Filter by status" style="min-width:120px;">
            <option value="">All Statuses</option>
            <?php foreach (['Active', 'Inactive', 'Dissolved'] as $s): ?>
              <option value="<?= e($s) ?>"><?= e($s) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="form-label small mb-1">Jurisdiction</label>
          <select class="form-select form-select-sm" name="jurisdiction_id" aria-label="Filter by jurisdiction" style="min-width:180px;">
            <option value="">All Jurisdictions</option>
            <?php foreach ($jurisdictions as $j): ?>
              <option value="<?= (int)$j['jurisdiction_id'] ?>"><?= e($j['jurisdiction_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="form-label small mb-1">Members</label>
          <select class="form-select form-select-sm" name="members" aria-label="Filter by member count" style="min-width:120px;">
            <option value="">Any size</option>
            <option value="none">No members</option>
            <option value="1-5">1–5 members</option>
            <option value="6+">6+ members</option>
          </select>
        </div>
        <div>
          <label class="form-label small mb-1">Assignments</label>
          <select class="form-select form-select-sm" name="work" aria-label="Filter by open assignments" style="min-width:170px;">
            <option value="">Any</option>
            <option value="open">With open assignments</option>
            <option value="none">No open assignments</option>
          </select>
        </div>
        <div class="d-flex align-items-end gap-1">
          <div>
            <label class="form-label small mb-1">Created from</label>
            <input type="date" name="created_from" class="form-control form-control-sm" style="min-width:140px;">
          </div>
          <span class="pb-1 text-muted small"><i class="bi bi-arrow-right"></i></span>
          <div>
            <label class="form-label small mb-1">To</label>
            <input type="date" name="created_to" class="form-control form-control-sm" style="min-width:140px;">
          </div>
        </div>
        <div>
          <label class="form-label small mb-1">Sort</label>
          <select class="form-select form-select-sm" name="sort" aria-label="Sort by" style="min-width:140px;">
            <option value="committee_name">Name</option>
            <option value="member_count">Members</option>
            <option value="status">Status</option>
            <option value="date_created">Date created</option>
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
    <div id="committeesTableWrap">
      <?php include __DIR__ . '/table.php'; ?>
    </div>
  </div>

<!-- Read-only committee detail modal (card click target) -->
<div class="modal fade" id="committeeViewModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-diagram-3 text-primary"></i> <span id="cvmName">Committee</span> <span class="badge ms-1" id="cvmStatus"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-4">
            <div class="border rounded p-3 h-100">
              <div class="small fw-semibold text-uppercase mb-2" style="letter-spacing:.05em;color:var(--ln-text-muted);">Committee Information</div>
              <dl class="row mb-0 small">
                <dt class="col-5">Jurisdiction</dt><dd class="col-7" id="cvmJurisdiction">—</dd>
                <dt class="col-5">Category</dt><dd class="col-7" id="cvmCategory">—</dd>
                <dt class="col-5">Created</dt><dd class="col-7" id="cvmCreated">—</dd>
                <dt class="col-5">Members</dt><dd class="col-7"><span class="badge bg-primary rounded-pill" id="cvmMemberCount">0</span></dd>
              </dl>
            </div>
          </div>
          <div class="col-md-8">
            <div class="border rounded p-3 h-100">
              <div class="small fw-semibold text-uppercase mb-2" style="letter-spacing:.05em;color:var(--ln-text-muted);">Description</div>
              <p class="small text-muted mb-0" id="cvmDescription" style="white-space:pre-line;"></p>
            </div>
          </div>
          <div class="col-12">
            <div class="small fw-semibold text-uppercase mb-2" style="letter-spacing:.05em;color:var(--ln-text-muted);"><i class="bi bi-people"></i> Members</div>
            <div class="table-responsive border rounded">
              <table class="table table-sm table-hover align-middle mb-0">
                <thead><tr><th>Name</th><th>Account Role</th><th>Committee Role</th><th class="text-end">Assigned</th></tr></thead>
                <tbody id="cvmMembersBody"><tr><td colspan="4" class="text-center text-muted py-3">Loading…</td></tr></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
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
$extraJs = [APP_URL . '/assets/js/committees.js?v=5'];
include __DIR__ . '/../../layouts/footer.php';
?>
