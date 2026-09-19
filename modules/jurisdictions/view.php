<?php
/**
 * modules/jurisdictions/view.php
 * ------------------------------------------------------------------
 * Read-only details view for one jurisdiction and its complete scope.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../../includes/auth.php';
requireRole([ROLE_ADMIN, ROLE_STAFF]);

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    setFlash('danger', 'Invalid jurisdiction.');
    redirect(APP_URL . '/modules/jurisdictions/index.php');
}

$pdo = db();
$stmt = $pdo->prepare(
    'SELECT j.*,
            u.full_name AS creator_name,
            (SELECT COUNT(*) FROM committees c WHERE c.jurisdiction_id = j.jurisdiction_id) AS committee_count
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

  $committeeStmt = $pdo->prepare(
    'SELECT committee_id, committee_name, description, status
     FROM committees
     WHERE jurisdiction_id = :jurisdiction_id
     ORDER BY committee_name'
  );
  $committeeStmt->execute([':jurisdiction_id' => $id]);
  $jurisdictionCommittees = $committeeStmt->fetchAll();

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
    </div>
    <div class="d-flex gap-2">
      <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back to Jurisdictions</a>
      <a href="index.php?edit=<?= (int)$id ?>" class="btn btn-primary btn-sm"><i class="bi bi-pencil-square"></i> Edit Jurisdiction</a>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-lg-5">
      <div class="card h-100">
        <div class="card-header"><i class="bi bi-info-circle"></i> Overview</div>
        <div class="card-body">
          <dl class="row mb-0 small">
            <dt class="col-5">Jurisdiction Name</dt><dd class="col-7"><?= e($jurisdiction['jurisdiction_name']) ?></dd>
            <dt class="col-5">Category</dt><dd class="col-7"><?= $jurisdiction['category'] ? e($jurisdiction['category']) : '<span class="text-muted">Not provided.</span>' ?></dd>
            <dt class="col-5">Status</dt><dd class="col-7"><span class="badge bg-<?= $statusClass ?>"><?= e($jurisdiction['status']) ?></span></dd>
            <dt class="col-5">Committees</dt><dd class="col-7"><?= (int)$jurisdiction['committee_count'] ?></dd>
          </dl>
          <hr>
          <h6 class="small text-uppercase text-muted">Description</h6>
          <p class="mb-0 small"><?= jurisdictionDetail($jurisdiction['description']) ?></p>
        </div>
      </div>
    </div>

    <div class="col-lg-7">
      <div class="card h-100">
        <div class="card-header"><i class="bi bi-bullseye"></i> Scope Definition</div>
        <div class="card-body">
          <h6>Scope Definition</h6>
          <p class="small mb-4"><?= jurisdictionDetail($jurisdiction['scope_definition']) ?></p>
          <h6>Covered Areas / Subjects</h6>
          <p class="small mb-0"><?= jurisdictionDetail($jurisdiction['covered_areas']) ?></p>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card h-100">
        <div class="card-header"><i class="bi bi-list-check"></i> Responsibilities</div>
        <div class="card-body">
          <h6>Primary Responsibilities</h6>
          <p class="small mb-4"><?= jurisdictionDetail($jurisdiction['primary_responsibilities']) ?></p>
          <h6>Typical Legislative Matters</h6>
          <p class="small mb-0"><?= jurisdictionDetail($jurisdiction['typical_legislative_matters']) ?></p>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card h-100">
        <div class="card-header"><i class="bi bi-sign-stop"></i> Limitations</div>
        <div class="card-body">
          <h6>Outside Scope</h6>
          <p class="small mb-0"><?= jurisdictionDetail($jurisdiction['outside_scope']) ?></p>
        </div>
      </div>
    </div>

    <div class="col-12">
      <div class="card">
        <div class="card-header"><i class="bi bi-sticky"></i> Notes</div>
        <div class="card-body small">
          <?= jurisdictionDetail($jurisdiction['notes']) ?>
        </div>
      </div>
    </div>

    <div class="col-12">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span><i class="bi bi-diagram-3"></i> Committees Under This Jurisdiction</span>
          <span class="badge bg-light text-dark border"><?= count($jurisdictionCommittees) ?></span>
        </div>
        <?php if (empty($jurisdictionCommittees)): ?>
          <div class="card-body text-muted small">
            No committees are currently associated with this jurisdiction.
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th>Committee Name</th>
                  <th>Description</th>
                  <th>Status</th>
                  <th class="text-end">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($jurisdictionCommittees as $committee): ?>
                  <?php $committeeUrl = APP_URL . '/modules/committees/view.php?id=' . (int)$committee['committee_id']; ?>
                  <tr class="jurisdiction-committee-row" tabindex="0"
                      data-committee-url="<?= e($committeeUrl) ?>"
                      aria-label="Open <?= e($committee['committee_name']) ?> details"
                      onclick="if (!event.target.closest('a,button')) window.location.href = this.dataset.committeeUrl"
                      onkeydown="if ((event.key === 'Enter' || event.key === ' ') && !event.target.closest('a,button')) { event.preventDefault(); window.location.href = this.dataset.committeeUrl; }">
                    <td class="fw-semibold"><?= e($committee['committee_name']) ?></td>
                    <td class="small text-muted">
                      <?= $committee['description'] ? e(truncate($committee['description'], 120)) : 'No description provided.' ?>
                    </td>
                    <td><span class="badge bg-<?= $committee['status'] === 'Active' ? 'success' : ($committee['status'] === 'Dissolved' ? 'danger' : 'secondary') ?>"><?= e($committee['status']) ?></span></td>
                    <td class="text-end">
                      <a href="<?= e($committeeUrl) ?>" class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation()">
                        <i class="bi bi-eye"></i> View
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  </div>
</div>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>
