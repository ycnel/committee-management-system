<?php
/**
 * System-authority management page for standard workload task templates.
 */

require_once __DIR__ . '/../../includes/auth.php';
requireRole([ROLE_ADMIN, ROLE_SUPER_ADMIN, ROLE_STAFF]);

$pageTitle = 'Standard Task Templates';
$activeMenu = 'task_templates';
$pendingTemplateRequestCount = isSystemAuthority()
    ? (int)db()->query("SELECT COUNT(*) FROM task_template_requests WHERE status = 'Pending'")->fetchColumn()
    : 0;
$taskTemplateJurisdictions = db()->query(
    'SELECT jurisdiction_id, jurisdiction_name
     FROM jurisdictions
     ORDER BY jurisdiction_name'
)->fetchAll();

include __DIR__ . '/../../layouts/header.php';
?>
<div class="app-wrapper">
  <?php include __DIR__ . '/../../layouts/sidebar.php'; ?>
  <div class="main-content admin-polished-page task-templates-page">
    <?php include __DIR__ . '/../../layouts/content-topbar.php'; ?>
    <div class="container-fluid py-3">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
          <nav aria-label="breadcrumb" class="mb-1">
            <ol class="breadcrumb mb-0 small">
              <li class="breadcrumb-item"><a href="index.php">Workload Distribution</a></li>
              <li class="breadcrumb-item active" aria-current="page">Standard Task Templates</li>
            </ol>
          </nav>
          <h4 class="mb-1"><i class="bi bi-list-check text-primary"></i> Standard Task Templates</h4>
          <p class="text-muted small mb-0">
            <?= isSystemAuthority()
                ? 'Maintain reusable core and jurisdiction-specific task suggestions.'
                : 'Browse available tasks and request new core or jurisdiction-specific templates.' ?>
          </p>
        </div>
        <?php if (isSystemAuthority() && $pendingTemplateRequestCount > 0): ?>
          <span class="badge rounded-pill text-bg-primary"><?= $pendingTemplateRequestCount ?> request<?= $pendingTemplateRequestCount === 1 ? '' : 's' ?> awaiting review</span>
        <?php endif; ?>
        <?php if (isSystemAuthority()): ?>
          <button type="button" class="btn btn-primary btn-sm" id="btnAddTaskTemplate">
            <i class="bi bi-plus-circle"></i> Add Template
          </button>
        <?php else: ?>
          <button type="button" class="btn btn-primary btn-sm" id="btnRequestTaskTemplate">
            <i class="bi bi-send"></i> Request a Template
          </button>
        <?php endif; ?>
      </div>

      <section class="card" id="standardTaskTemplates">
        <div class="card-body">
          <div class="row g-2 align-items-end mb-3">
            <div class="col-md-5">
              <label class="form-label small" for="taskTemplateFilter">Filter templates</label>
              <select class="form-select form-select-sm" id="taskTemplateFilter">
                <option value="">All templates</option>
                <option value="core">Core templates</option>
                <?php foreach ($taskTemplateJurisdictions as $templateJurisdiction): ?>
                  <option value="<?= (int)$templateJurisdiction['jurisdiction_id'] ?>"><?= e($templateJurisdiction['jurisdiction_name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col">
              <p class="form-text mb-0">Sequence order is a recommended display order, not a legally required procedure.</p>
            </div>
          </div>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th>Task</th>
                  <th>Scope</th>
                  <th>Order</th>
                  <th>Required</th>
                  <th>Status</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody id="taskTemplateRows">
                <tr><td colspan="6" class="text-center text-muted py-4">Loading templates...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <section class="card mt-3">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div>
            <h6 class="mb-1"><?= isSystemAuthority() ? 'Template Requests' : 'My Template Requests' ?></h6>
            <p class="small text-muted mb-0">
              <?= isSystemAuthority() ? 'Review requests submitted by Committee Chairpersons.' : 'Track requests awaiting Administrator review.' ?>
            </p>
          </div>
          <?php if (isSystemAuthority()): ?>
            <select class="form-select form-select-sm w-auto" id="taskTemplateRequestFilter" aria-label="Filter template requests">
              <option value="Pending">Pending</option>
              <option value="all">All requests</option>
              <option value="Approved">Approved</option>
              <option value="Rejected">Rejected</option>
            </select>
          <?php endif; ?>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <?php if (isSystemAuthority()): ?><th>Requested By</th><?php endif; ?>
                <th>Task</th>
                <th>Scope</th>
                <th>Submitted</th>
                <th>Status</th>
                <?php if (isSystemAuthority()): ?><th class="text-end">Actions</th><?php endif; ?>
              </tr>
            </thead>
            <tbody id="taskTemplateRequestRows" data-admin="<?= isSystemAuthority() ? 'true' : 'false' ?>">
              <tr><td colspan="<?= isSystemAuthority() ? 6 : 4 ?>" class="text-center text-muted py-4">Loading requests...</td></tr>
            </tbody>
          </table>
        </div>
      </section>
    </div>
  </div>
</div>

<?php if (isSystemAuthority()): ?>
<div class="modal fade" id="taskTemplateModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form id="taskTemplateForm">
        <?= csrfField() ?>
        <input type="hidden" name="id" id="taskTemplateId" value="0">
        <div class="modal-header">
          <h5 class="modal-title" id="taskTemplateModalTitle">Add Standard Task Template</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label" for="taskTemplateName">Task name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="taskTemplateName" name="task_name" maxlength="255" required>
          </div>
          <div class="mb-3">
            <label class="form-label" for="taskTemplateDescription">Description</label>
            <textarea class="form-control" id="taskTemplateDescription" name="description" rows="3"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label" for="taskTemplateProofRequirement">Completion proof guidance</label>
            <textarea class="form-control" id="taskTemplateProofRequirement" name="proof_requirement" rows="2" maxlength="2000" placeholder="Optional: describe evidence the member should attach when requesting completion approval."></textarea>
            <div class="form-text">Members must still attach a proof file; this guidance is specific to the template.</div>
          </div>
          <div class="row g-3">
            <div class="col-md-7">
              <label class="form-label" for="taskTemplateJurisdiction">Template scope</label>
              <select class="form-select" id="taskTemplateJurisdiction" name="jurisdiction_id">
                <option value="">Core / Global</option>
                <?php foreach ($taskTemplateJurisdictions as $templateJurisdiction): ?>
                  <option value="<?= (int)$templateJurisdiction['jurisdiction_id'] ?>"><?= e($templateJurisdiction['jurisdiction_name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label" for="taskTemplateOrder">Sequence</label>
              <input type="number" class="form-control" id="taskTemplateOrder" name="sequence_order" min="0" step="1" value="0" required>
            </div>
            <div class="col-md-3 d-flex flex-column justify-content-center gap-2">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="taskTemplateRequired" name="is_required" value="1">
                <label class="form-check-label" for="taskTemplateRequired">Required</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="taskTemplateActive" name="is_active" value="1" checked>
                <label class="form-check-label" for="taskTemplateActive">Active</label>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check2"></i> Save Template</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if (!isSystemAuthority()): ?>
<div class="modal fade" id="taskTemplateRequestModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form id="taskTemplateRequestForm">
        <?= csrfField() ?>
        <div class="modal-header">
          <h5 class="modal-title">Request a Standard Task Template</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p class="small text-muted">Your request will be reviewed by an Administrator before it becomes available for task proposals.</p>
          <div class="mb-3">
            <label class="form-label" for="taskTemplateRequestName">Task name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="taskTemplateRequestName" name="task_name" maxlength="255" required>
          </div>
          <div class="mb-3">
            <label class="form-label" for="taskTemplateRequestDescription">Description</label>
            <textarea class="form-control" id="taskTemplateRequestDescription" name="description" rows="3"></textarea>
          </div>
          <div class="row g-3">
            <div class="col-md-7">
              <label class="form-label" for="taskTemplateRequestJurisdiction">Template scope</label>
              <select class="form-select" id="taskTemplateRequestJurisdiction" name="jurisdiction_id">
                <option value="">Core / Global</option>
                <?php foreach ($taskTemplateJurisdictions as $templateJurisdiction): ?>
                  <option value="<?= (int)$templateJurisdiction['jurisdiction_id'] ?>"><?= e($templateJurisdiction['jurisdiction_name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label" for="taskTemplateRequestOrder">Sequence</label>
              <input type="number" class="form-control" id="taskTemplateRequestOrder" name="sequence_order" min="0" step="1" value="0" required>
            </div>
            <div class="col-md-3 d-flex align-items-center">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="taskTemplateRequestRequired" name="is_required" value="1">
                <label class="form-check-label" for="taskTemplateRequestRequired">Required</label>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-send"></i> Submit Request</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<?php
$extraJs = [APP_URL . '/assets/js/task-templates-admin.js?v=' . (int)@filemtime(__DIR__ . '/../../assets/js/task-templates-admin.js')];
include __DIR__ . '/../../layouts/footer.php';
?>
