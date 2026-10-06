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
requireRole([ROLE_ADMIN, ROLE_STAFF, ROLE_COMMITTEE, ROLE_SUPER_ADMIN, ...LEGISLATIVE_OVERSIGHT_ROLES]);

$pageTitle  = 'Committee Management';
$activeMenu = 'committees';
$pdo = db();

$jurisdictions = $pdo->query('SELECT jurisdiction_id, jurisdiction_name FROM jurisdictions WHERE status = \'Active\' ORDER BY jurisdiction_name')->fetchAll();

include __DIR__ . '/../../layouts/header.php';
?>
<div class="app-wrapper">
  <?php include __DIR__ . '/../../layouts/sidebar.php'; ?>

  <div class="main-content committee-management-page admin-polished-page">
  <?php include __DIR__ . '/../../layouts/content-topbar.php'; ?>
  <div class="breadcrumb-bar d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <h5 class="mb-0">Committee Management</h5>
      <small class="text-muted"><?= isCommitteeMember() ? 'View the committees you are assigned to.' : 'Create committees, assign jurisdictions, and manage membership.' ?></small>
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

  <?php if (!isCommitteeMember()): ?>
    <div class="card mb-3 committee-filter-panel">
      <div class="card-body py-3">
        <form id="filterForm" class="committee-filter-form d-flex flex-wrap align-items-end gap-3">
        <span class="badge badge-soft-neutral d-none align-self-center" id="activeFilterCount"></span>
        <div style="min-width:240px;flex:1 1 240px;">
          <label class="form-label small mb-1" for="searchInput">Search</label>
          <div class="committee-search-group">
            <i class="bi bi-search committee-search-icon" aria-hidden="true"></i>
            <input type="search" class="form-control committee-search-input" id="searchInput" name="search"
                   placeholder="Committee name or description...">
          </div>
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
        <div class="committee-date-range d-flex align-items-end gap-1">
          <div>
            <label class="form-label small mb-1">Created from</label>
            <div class="committee-date-picker" data-date-picker>
              <input type="hidden" name="created_from" data-date-value>
              <button type="button" class="committee-date-input" data-date-trigger aria-haspopup="dialog" aria-expanded="false">
                <span data-date-label>mm/dd/yyyy</span><i class="bi bi-calendar3" aria-hidden="true"></i>
              </button>
            </div>
          </div>
          <span class="pb-1 text-muted small"><i class="bi bi-arrow-right"></i></span>
          <div>
            <label class="form-label small mb-1">To</label>
            <div class="committee-date-picker" data-date-picker>
              <input type="hidden" name="created_to" data-date-value>
              <button type="button" class="committee-date-input" data-date-trigger aria-haspopup="dialog" aria-expanded="false">
                <span data-date-label>mm/dd/yyyy</span><i class="bi bi-calendar3" aria-hidden="true"></i>
              </button>
            </div>
          </div>
        </div>
        <div>
          <label class="form-label small mb-1">Sort</label>
          <select class="form-select form-select-sm" name="sort" aria-label="Sort by" style="min-width:140px;">
            <option value="committee_name" <?= (($_GET['sort'] ?? 'committee_name') === 'committee_name') ? 'selected' : '' ?>>Name</option>
            <option value="member_count" <?= (($_GET['sort'] ?? '') === 'member_count') ? 'selected' : '' ?>>Members</option>
            <option value="status" <?= (($_GET['sort'] ?? '') === 'status') ? 'selected' : '' ?>>Status</option>
            <option value="date_created" <?= (($_GET['sort'] ?? '') === 'date_created') ? 'selected' : '' ?>>Date created</option>
          </select>
        </div>
        <div>
          <label class="form-label small mb-1">Direction</label>
          <select class="form-select form-select-sm" name="dir" aria-label="Sort direction" style="min-width:90px;">
            <option value="asc" <?= (strtolower($_GET['dir'] ?? 'asc') === 'asc') ? 'selected' : '' ?>>Asc</option>
            <option value="desc" <?= (strtolower($_GET['dir'] ?? 'asc') === 'desc') ? 'selected' : '' ?>>Desc</option>
          </select>
        </div>
        <div class="ms-auto d-flex gap-2 committee-filter-actions">
          <button type="submit" class="btn btn-primary btn-sm position-relative committee-filter-action" id="filterApply">
            <i class="bi bi-check2"></i> Apply<span class="apply-pending-dot d-none" id="applyPendingDot" aria-hidden="true"></span>
          </button>
          <button type="button" class="btn btn-outline-secondary btn-sm committee-filter-action" id="filterReset"><i class="bi bi-arrow-counterclockwise"></i> Reset</button>
        </div>
        </form>
      </div>
    </div>
  <?php else: ?>
    <div class="committee-member-assignment-heading">
      <i class="bi bi-person-check" aria-hidden="true"></i>
      <span>Your assigned committees</span>
    </div>
  <?php endif; ?>

  <div class="card committee-results-panel">
    <div id="committeesTableWrap">
      <?php include __DIR__ . '/table.php'; ?>
    </div>
  </div>

<!-- Read-only committee detail modal (card click target) -->
<div class="modal fade" id="committeeViewModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable committee-view-dialog">
    <div class="modal-content committee-view-modal">
      <div class="modal-header">
        <div class="committee-view-heading">
          <span class="committee-view-heading-icon"><i class="bi bi-diagram-3"></i></span>
          <div class="committee-view-heading-text">
            <span class="committee-view-eyebrow">Committee details</span>
            <h5 class="modal-title" id="cvmName">Committee</h5>
          </div>
          <span class="badge committee-view-status" id="cvmStatus"></span>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="committee-view-summary">
          <div class="committee-view-panel committee-view-description">
            <div class="committee-view-section-heading"><i class="bi bi-card-text"></i><span>About this committee</span></div>
            <p id="cvmDescription" class="mb-0"></p>
          </div>
          <div class="committee-view-panel committee-view-facts">
            <div class="committee-view-section-heading"><i class="bi bi-info-circle"></i><span>At a glance</span></div>
            <div class="committee-view-fact"><span>Created</span><strong id="cvmCreated">—</strong></div>
            <div class="committee-view-fact"><span>Members</span><strong id="cvmMemberCount">0</strong></div>
          </div>
        </div>
        <section class="committee-jurisdiction-view">
          <div class="committee-view-section-top">
            <div>
              <div class="committee-view-section-heading mb-1"><i class="bi bi-geo-alt"></i><span>Jurisdictions</span></div>
              <p class="committee-view-section-help mb-0">Select a jurisdiction to review its coverage and scope.</p>
            </div>
            <span class="committee-view-count" id="cvmJurisdictionCount"></span>
          </div>
          <label class="visually-hidden" for="cvmJurisdictionSelect">Select jurisdiction</label>
          <select class="form-select" id="cvmJurisdictionSelect" aria-label="Select a committee jurisdiction"></select>
          <div class="committee-jurisdiction-view-detail" id="cvmJurisdictionDetail">
            <div class="committee-jurisdiction-detail-heading">
              <span class="committee-jurisdiction-detail-icon"><i class="bi bi-bookmark-check"></i></span>
              <h6 id="cvmJurisdictionName" class="mb-0">No jurisdiction assigned</h6>
            </div>
            <div class="committee-jurisdiction-detail-block">
              <span class="committee-jurisdiction-detail-label">Description</span>
              <p class="mb-0" id="cvmJurisdictionDescription"></p>
            </div>
            <div class="committee-jurisdiction-detail-block" id="cvmJurisdictionScopeWrap">
              <span class="committee-jurisdiction-detail-label">Scope</span>
              <p class="mb-0" id="cvmJurisdictionScope"></p>
            </div>
          </div>
        </section>
        <section class="committee-view-members">
            <div class="committee-view-section-heading committee-view-members-heading"><i class="bi bi-people"></i><span>Members</span></div>
            <div class="table-responsive committee-view-members-table">
              <table class="table table-sm table-hover align-middle mb-0">
                <thead><tr><th>Name</th><th>Account Role</th><th>Committee Role</th><th>Political Group</th><th class="text-end">Assigned</th><?php if (canManage()): ?><th class="text-end">Actions</th><?php endif; ?></tr></thead>
                <tbody id="cvmMembersBody" data-can-manage-members="<?= canManage() ? '1' : '0' ?>"><tr><td colspan="<?= canManage() ? 6 : 5 ?>" class="text-center text-muted py-3">Loading…</td></tr></tbody>
              </table>
            </div>
        </section>
      </div>
      <div class="modal-footer">
        <?php if (canManage()): ?>
        <button type="button" class="btn btn-primary btn-sm me-auto" id="btnCvmAssignMember">
          <i class="bi bi-person-plus"></i> Assign Member
        </button>
        <?php endif; ?>
        <button type="button" class="btn btn-primary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<?php if (canManage()): ?>
<div class="modal fade" id="committeeAssignMemberModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="committeeAssignMemberForm">
        <?= csrfField() ?>
        <input type="hidden" name="committee_id" id="cvmAssignCommitteeId" value="">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-person-plus"></i> Assign Member</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label" for="cvmAssignUser">User <span class="text-danger">*</span></label>
            <select name="user_id" id="cvmAssignUser" class="form-select" required>
              <option value="">-- Select a user --</option>
            </select>
            <div class="form-text" id="cvmAssignEmpty">Only active users not already assigned are shown.</div>
          </div>
          <div class="mb-3">
            <label class="form-label" for="cvmAssignRole">Committee Role</label>
            <select name="member_role" id="cvmAssignRole" class="form-select">
              <option value="Member">Member</option>
              <option value="Vice Chairperson">Vice Chairperson</option>
              <option value="Chairperson">Chairperson</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label" for="cvmAssignPoliticalGroup">Political Group</label>
            <select name="political_group" id="cvmAssignPoliticalGroup" class="form-select">
              <option value="">Unassigned</option>
              <option value="Majority">Majority</option>
              <option value="Minority">Minority</option>
            </select>
          </div>
          <div>
            <label class="form-label" for="cvmAssignDate">Assigned Date</label>
            <input type="date" name="assigned_date" id="cvmAssignDate" class="form-control" value="<?= date('Y-m-d') ?>">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Assign</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

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
$extraJs = [APP_URL . '/assets/js/committees.js?v=11'];
include __DIR__ . '/../../layouts/footer.php';
?>
