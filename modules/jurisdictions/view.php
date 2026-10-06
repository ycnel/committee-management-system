<?php
/**
 * modules/jurisdictions/view.php
 * ------------------------------------------------------------------
 * Read-only details view for one jurisdiction and its complete scope.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../../includes/auth.php';
requireRole([ROLE_ADMIN, ROLE_STAFF, ROLE_SUPER_ADMIN, ...LEGISLATIVE_OVERSIGHT_ROLES]); // read/oversight only; canManage() still gates writes

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    setFlash('danger', 'Invalid jurisdiction.');
    redirect(APP_URL . '/modules/jurisdictions/index.php');
}

$pdo = db();
$stmt = $pdo->prepare(
    'SELECT j.*, u.full_name AS creator_name
     FROM jurisdictions j
     LEFT JOIN users u ON u.id = j.created_by
     WHERE j.jurisdiction_id = :id
     LIMIT 1'
);
$stmt->execute([':id' => $id]);
$jurisdiction = $stmt->fetch();

if (!$jurisdiction) {
    setFlash('danger', 'Jurisdiction not found.');
    redirect(APP_URL . '/modules/jurisdictions/index.php');
}

  $pageTitle = $jurisdiction['jurisdiction_name'];
$activeMenu = 'jurisdictions';
$statusClass = $jurisdiction['status'] === 'Active' ? 'success' : 'secondary';

function jurisdictionDetail(?string $value): string
{
    return $value !== null && trim($value) !== ''
        ? nl2br(e($value))
        : '<span class="text-muted">Not provided.</span>';
}

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
          <li class="breadcrumb-item"><a href="index.php">Jurisdictions</a></li>
          <li class="breadcrumb-item active"><?= e($jurisdiction['jurisdiction_name']) ?></li>
        </ol>
      </nav>
      <h5 class="mb-0"><i class="bi bi-geo-alt text-primary"></i> <?= e($jurisdiction['jurisdiction_name']) ?>
        <span class="badge bg-<?= $statusClass ?>"><?= e($jurisdiction['status']) ?></span>
      </h5>
      <a href="index.php" class="btn btn-outline-secondary btn-sm mt-2">
        <i class="bi bi-arrow-left"></i> Back to Jurisdictions
      </a>
    </div>
    <div class="jurisdiction-heading-actions">
      <?php if (canManage()): ?>
        <a href="index.php?edit=<?= (int)$id ?>" class="btn btn-primary btn-sm"><i class="bi bi-pencil-square"></i> Edit Jurisdiction</a>
      <?php endif; ?>
      <?php if (isAdmin()): ?>
        <button type="button" class="btn btn-outline-danger btn-sm" id="removeJurisdictionButton"
                data-jurisdiction-id="<?= (int)$id ?>" data-jurisdiction-name="<?= e($jurisdiction['jurisdiction_name']) ?>">
          <i class="bi bi-trash"></i> Remove Jurisdiction
        </button>
      <?php elseif (currentRole() === ROLE_STAFF): ?>
        <button type="button" class="btn btn-outline-danger btn-sm" id="requestJurisdictionRemovalButton"
                data-jurisdiction-id="<?= (int)$id ?>" data-jurisdiction-name="<?= e($jurisdiction['jurisdiction_name']) ?>">
          <i class="bi bi-send"></i> Request Removal
        </button>
      <?php endif; ?>
    </div>
  </div>

  <div class="row g-3 align-items-stretch jurisdiction-details-grid">
    <div class="col-md-6">
      <div class="card jurisdiction-detail-card">
        <div class="card-header"><i class="bi bi-info-circle"></i> Overview</div>
        <div class="card-body">
          <dl class="row mb-0 small">
            <dt class="col-5">Jurisdiction Name</dt><dd class="col-7"><?= e($jurisdiction['jurisdiction_name']) ?></dd>
            <dt class="col-5">Status</dt><dd class="col-7"><span class="badge bg-<?= $statusClass ?>"><?= e($jurisdiction['status']) ?></span></dd>
          </dl>
          <hr>
          <h6 class="small text-uppercase text-muted">Description</h6>
          <p class="mb-0 small"><?= jurisdictionDetail($jurisdiction['description']) ?></p>
        </div>
      </div>
    </div>

    <div class="col-md-6">
      <div class="card jurisdiction-detail-card">
        <div class="card-header"><i class="bi bi-bullseye"></i> Scope Definition</div>
        <div class="card-body">
          <h6>Scope Definition</h6>
          <p class="small mb-4"><?= jurisdictionDetail($jurisdiction['scope_definition']) ?></p>
          <h6>Covered Areas / Subjects</h6>
          <p class="small mb-0"><?= jurisdictionDetail($jurisdiction['covered_areas']) ?></p>
        </div>
      </div>
    </div>

    <div class="col-md-6">
      <div class="card jurisdiction-detail-card">
        <div class="card-header"><i class="bi bi-list-check"></i> Responsibilities</div>
        <div class="card-body">
          <h6>Primary Responsibilities</h6>
          <p class="small mb-4"><?= jurisdictionDetail($jurisdiction['primary_responsibilities']) ?></p>
          <h6>Typical Legislative Matters</h6>
          <p class="small mb-0"><?= jurisdictionDetail($jurisdiction['typical_legislative_matters']) ?></p>
        </div>
      </div>
    </div>

    <div class="col-md-6">
      <div class="card jurisdiction-detail-card">
        <div class="card-header"><i class="bi bi-sign-stop"></i> Limitations</div>
        <div class="card-body">
          <h6>Outside Scope</h6>
          <p class="small mb-0"><?= jurisdictionDetail($jurisdiction['outside_scope']) ?></p>
        </div>
      </div>
    </div>

    <div class="col-12">
      <div class="card jurisdiction-detail-card jurisdiction-notes-card">
        <div class="card-header"><i class="bi bi-sticky"></i> Notes</div>
        <div class="card-body small">
          <?= jurisdictionDetail($jurisdiction['notes']) ?>
        </div>
      </div>
    </div>

  </div>
  </div>
</div>

<?php
$extraJs = [APP_URL . '/assets/js/jurisdiction-view.js'];
include __DIR__ . '/../../layouts/footer.php';
?>
