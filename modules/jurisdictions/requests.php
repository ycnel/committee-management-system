<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole([ROLE_ADMIN, ROLE_STAFF]);

$isAdministrator = isAdmin();
$currentUserId = (int)currentUserId();
$requestId = (int)($_GET['request_id'] ?? 0);
$pageTitle = $isAdministrator ? 'Jurisdiction Removal Requests' : 'My Removal Requests';
$activeMenu = 'requests';

$sql = 'SELECT rr.*, requester.full_name AS requester_name, processor.full_name AS processor_name
        FROM jurisdiction_removal_requests rr
        LEFT JOIN users requester ON requester.id = rr.requested_by
        LEFT JOIN users processor ON processor.id = rr.processed_by';
if (!$isAdministrator) {
    $sql .= ' WHERE rr.requested_by = :requester_id';
}
$sql .= ' ORDER BY CASE WHEN rr.status = \'Pending\' THEN 0 ELSE 1 END, rr.requested_at DESC';
$stmt = db()->prepare($sql);
if (!$isAdministrator) {
    $stmt->bindValue(':requester_id', $currentUserId, PDO::PARAM_INT);
}
$stmt->execute();
$requests = $stmt->fetchAll();

include __DIR__ . '/../../layouts/header.php';
?>
<div class="app-wrapper">
  <?php include __DIR__ . '/../../layouts/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/../../layouts/content-topbar.php'; ?>
    <div class="container-fluid py-3">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
          <h4 class="mb-1"><?= e($pageTitle) ?></h4>
          <p class="text-muted mb-0">
            <?= $isAdministrator
                ? 'Review and process jurisdiction removal requests submitted by Committee Chairpersons.'
                : 'Track the status and response for your jurisdiction removal requests.' ?>
          </p>
        </div>
        <a class="btn btn-outline-secondary btn-sm" href="<?= e(APP_URL) ?>/modules/jurisdictions/index.php">
          <i class="bi bi-arrow-left"></i> Jurisdictions
        </a>
      </div>

      <?php if (!$requests): ?>
        <div class="card"><div class="card-body text-center py-5 text-muted">
          <i class="bi bi-inbox fs-2 d-block mb-2"></i>
          <?= $isAdministrator ? 'There are no jurisdiction removal requests.' : 'You have not submitted any removal requests.' ?>
        </div></div>
      <?php else: ?>
        <div class="d-flex flex-column gap-3">
          <?php foreach ($requests as $request): ?>
            <?php
              $statusClass = match ($request['status']) {
                  'Pending' => 'warning',
                  'Approved' => 'success',
                  default => 'secondary',
              };
            ?>
            <article class="card" id="request-<?= (int)$request['id'] ?>">
              <div class="card-header d-flex justify-content-between align-items-center gap-2 flex-wrap">
                <div class="fw-semibold">
                  Request #<?= (int)$request['id'] ?> · <?= e($request['jurisdiction_name']) ?>
                </div>
                <span class="badge text-bg-<?= e($statusClass) ?>"><?= e($request['status']) ?></span>
              </div>
              <div class="card-body">
                <div class="row g-3 small">
                  <?php if ($isAdministrator): ?>
                    <div class="col-md-4"><span class="text-muted d-block">Requested by</span><?= e($request['requester_name'] ?: 'Account unavailable') ?></div>
                  <?php endif; ?>
                  <div class="col-md-4"><span class="text-muted d-block">Submitted</span><?= e(date('M j, Y g:i A', strtotime($request['requested_at']))) ?></div>
                  <?php if ($request['processed_at']): ?>
                    <div class="col-md-4"><span class="text-muted d-block"><?= e($request['status']) ?> by</span><?= e($request['processor_name'] ?: 'Account unavailable') ?> · <?= e(date('M j, Y g:i A', strtotime($request['processed_at']))) ?></div>
                  <?php endif; ?>
                  <div class="col-12">
                    <span class="text-muted d-block">Reason</span>
                    <div style="white-space:pre-line"><?= $request['reason'] ? e($request['reason']) : '<span class="text-muted">No reason provided.</span>' ?></div>
                  </div>
                  <?php if ($request['admin_response']): ?>
                    <div class="col-12">
                      <span class="text-muted d-block">Administrator response</span>
                      <div style="white-space:pre-line"><?= e($request['admin_response']) ?></div>
                    </div>
                  <?php endif; ?>
                </div>
                <?php if ($isAdministrator && $request['status'] === 'Pending'): ?>
                  <div class="d-flex justify-content-end gap-2 mt-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm"
                            data-process-removal="Rejected" data-request-id="<?= (int)$request['id'] ?>">
                      Reject
                    </button>
                    <button type="button" class="btn btn-danger btn-sm"
                            data-process-removal="Approved" data-request-id="<?= (int)$request['id'] ?>">
                      Approve and Remove
                    </button>
                  </div>
                <?php endif; ?>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
<?php
$extraJs = [APP_URL . '/assets/js/jurisdiction-requests.js'];
include __DIR__ . '/../../layouts/footer.php';
?>
