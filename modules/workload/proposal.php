<?php
/**
 * modules/workload/proposal.php
 * ------------------------------------------------------------------
 * Detail page for one workload_assignment_proposals row — the Member
 * Acceptance Validation workflow's "task inbox" screen. Mirrors
 * task.php's shape and server-side ownership enforcement, but for the
 * pre-approval stage: the assigned member sees Accept/Decline here;
 * Administrator/Chairperson see Approve/Reassign/Stop Distribution.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../includes/checklist.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    setFlash('danger', 'Invalid proposal id.');
    redirect(APP_URL . '/dashboard.php');
}

$pdo = db();
$proposal = getProposalWithContext($pdo, $id);

$autoChecklistItems = autoChecklistItems($proposal ?: []);
$manualChecklistItems = $proposal ? manualChecklistItems($pdo, (int)$proposal['proposal_id']) : [];
$checklistProgress = $proposal ? checklistProgress($pdo, $proposal) : ['checked' => 0, 'total' => 0];

$isMine = $proposal && (int)$proposal['assignee_user_id'] === (int)currentUserId();
if (!$proposal || (!$isMine && !canManage())) {
    http_response_code(404);
    setFlash('danger', 'Proposal not found or you do not have permission to view it.');
    redirect(APP_URL . '/dashboard.php');
}

// Full reassignment chain (oldest first) so the page can show history.
$chain = [];
$cursor = $proposal;
while ($cursor['previous_proposal_id']) {
    $prev = getProposalWithContext($pdo, (int)$cursor['previous_proposal_id']);
    if (!$prev) break;
    $chain[] = $prev;
    $cursor = $prev;
}
$chain = array_reverse($chain);

$pageTitle = 'Task Proposal';
$activeMenu = 'workload';
include __DIR__ . '/../../layouts/header.php';
?>
<div class="app-wrapper">
  <?php include __DIR__ . '/../../layouts/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/../../layouts/content-topbar.php'; ?>
    <div class="breadcrumb-bar d-flex justify-content-between align-items-center gap-2">
      <div><h5 class="mb-0"><i class="bi bi-person-check text-primary"></i> Task Proposal</h5><small class="text-muted"><?= e($proposal['committee_name']) ?></small></div>
      <a href="<?= e(APP_URL) ?>/modules/workload/index.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back to Workload</a>
    </div>

    <div class="container-fluid px-4 py-4">
      <?php if ($chain): ?>
        <div class="alert alert-secondary small mb-3">
          <i class="bi bi-arrow-repeat me-1"></i> This task was reassigned <?= count($chain) ?> time(s) before reaching
          <strong><?= e($proposal['assignee_name']) ?></strong>:
          <?php foreach ($chain as $prev): ?>
            <a href="<?= e(APP_URL) ?>/modules/workload/proposal.php?id=<?= (int)$prev['proposal_id'] ?>">#<?= (int)$prev['proposal_id'] ?> <?= e($prev['assignee_name']) ?> (<?= e($prev['state']) ?>)</a><?= $prev !== end($chain) ? ', ' : '' ?>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="card hero-card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <h4 class="mb-0"><?= e($proposal['task_title']) ?></h4>
            <?= statusBadge($proposal['state']) ?>
          </div>
          <div class="d-flex flex-wrap gap-2 my-3">
            <?= priorityBadge($proposal['priority']) ?>
            <span class="badge bg-light text-dark border">Due: <?= $proposal['due_date'] ? e(formatDate($proposal['due_date'])) : 'No due date' ?></span>
            <?php if ($proposal['ai_recommendation_id']): ?>
              <span class="badge bg-light text-dark border"><i class="bi bi-robot"></i> AI-recommended candidate</span>
            <?php endif; ?>
          </div>
          <p class="text-muted"><?= nl2br(e($proposal['task_description'] ?: 'No description provided.')) ?></p>

          <div class="row g-3 small text-muted mt-2">
            <div class="col-md-6">Proposed to <strong><?= e($proposal['assignee_name']) ?></strong> by <?= e($proposal['proposed_by_name'] ?? 'a former user') ?> &middot; <?= e(timeAgo($proposal['proposed_at'])) ?></div>
            <?php if ($proposal['responded_at']): ?>
              <div class="col-md-6">Member responded <?= e(timeAgo($proposal['responded_at'])) ?><?= $proposal['note'] ? ': "' . e($proposal['note']) . '"' : '' ?></div>
            <?php endif; ?>
            <?php if ($proposal['approved_at']): ?>
              <div class="col-md-6">Approved by <?= e($proposal['approved_by_name'] ?? 'a former user') ?> &middot; <?= e(timeAgo($proposal['approved_at'])) ?></div>
            <?php endif; ?>
            <?php if ($proposal['state'] === 'Stopped' && $proposal['note']): ?>
              <div class="col-md-6">Stop reason: <?= e($proposal['note']) ?></div>
            <?php endif; ?>
          </div>

          <?php if ($proposal['state'] === 'Approved' && $proposal['resulting_workload_id']): ?>
            <a href="<?= e(APP_URL) ?>/modules/workload/task.php?id=<?= (int)$proposal['resulting_workload_id'] ?>" class="btn btn-dark btn-sm mt-3">
              View confirmed assignment <i class="bi bi-arrow-right"></i>
            </a>
          <?php endif; ?>

          <?php if ($proposal): ?>
            <hr>
            <div class="card mt-3 border-0 shadow-sm">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                  <h6 class="mb-0"><i class="bi bi-list-check text-primary"></i> Committee matter checklist</h6>
                  <span class="badge bg-light text-dark border">
                    <?= (int)$checklistProgress['checked'] ?>/<?= (int)$checklistProgress['total'] ?> complete
                  </span>
                </div>

                <div class="progress mb-3" style="height: 8px;">
                  <?php $pct = $checklistProgress['total'] ? round(($checklistProgress['checked'] / $checklistProgress['total']) * 100) : 0; ?>
                  <div class="progress-bar bg-success" role="progressbar" style="width: <?= $pct ?>%" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>

                <div class="row g-3">
                  <?php foreach ($autoChecklistItems as $item): ?>
                    <div class="col-md-6">
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" <?= $item['checked'] ? 'checked' : '' ?> disabled>
                        <label class="form-check-label text-muted">
                          <?= e($item['label']) ?>
                        </label>
                      </div>
                    </div>
                  <?php endforeach; ?>

                  <?php foreach ($manualChecklistItems as $item): ?>
                    <div class="col-md-6">
                      <div class="form-check">
                        <input
                          class="form-check-input checklist-toggle"
                          type="checkbox"
                          data-proposal-id="<?= (int)$proposal['proposal_id'] ?>"
                          data-item-key="<?= e($item['key']) ?>"
                          data-item-label="<?= e($item['label']) ?>"
                          <?= $item['checked'] ? 'checked' : '' ?>
                          <?= canManage() ? '' : 'disabled' ?>
                        >
                        <label class="form-check-label <?= $item['checked'] ? '' : 'text-muted' ?>">
                          <?= e($item['label']) ?>
                        </label>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          <?php endif; ?>

          <?php if ($isMine && $proposal['state'] === 'Awaiting Response'): ?>
            <hr>
            <h6>Your response</h6>
            <p class="text-muted small">Accepting does not finalize the assignment yet — <?= e($proposal['proposed_by_name'] ?? 'the Chairperson') ?> still needs to give final approval.</p>
            <div class="d-flex gap-2 flex-wrap">
              <button type="button" class="btn btn-success" id="btnAccept"><i class="bi bi-check-lg"></i> Accept</button>
              <button type="button" class="btn btn-outline-danger" id="btnDecline"><i class="bi bi-x-lg"></i> Decline</button>
            </div>
            <div class="mt-2" id="declineReasonWrap" style="display:none;">
              <textarea class="form-control form-control-sm" id="declineReason" rows="2" maxlength="500" placeholder="Optional reason for declining"></textarea>
              <button type="button" class="btn btn-danger btn-sm mt-2" id="btnConfirmDecline">Confirm Decline</button>
            </div>
          <?php endif; ?>

          <?php if (canManage() && in_array($proposal['state'], ['Awaiting Response', 'Accepted', 'Declined'], true)): ?>
            <hr>
            <h6>Chairperson / Administrator actions</h6>
            <div class="d-flex gap-2 flex-wrap">
              <?php if ($proposal['state'] === 'Accepted'): ?>
                <button type="button" class="btn btn-dark" id="btnApprove"><i class="bi bi-patch-check"></i> Give Final Approval</button>
              <?php endif; ?>
              <button type="button" class="btn btn-outline-secondary" id="btnReassign" data-committee-id="<?= (int)$proposal['committee_id'] ?>">
                <i class="bi bi-arrow-repeat"></i> Reassign
              </button>
              <button type="button" class="btn btn-outline-danger" id="btnStop"><i class="bi bi-slash-circle"></i> Stop Distribution</button>
            </div>
            <div class="mt-2" id="reassignWrap" style="display:none;">
              <select class="form-select form-select-sm" id="reassignMemberSelect"><option value="">Loading members…</option></select>
              <button type="button" class="btn btn-secondary btn-sm mt-2" id="btnConfirmReassign">Confirm Reassign</button>
            </div>
            <div class="mt-2" id="stopWrap" style="display:none;">
              <textarea class="form-control form-control-sm" id="stopReason" rows="2" maxlength="500" placeholder="Optional reason"></textarea>
              <button type="button" class="btn btn-danger btn-sm mt-2" id="btnConfirmStop">Confirm Stop</button>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
<?php
$extraJs = [APP_URL . '/assets/js/workload-proposal.js'];
include __DIR__ . '/../../layouts/footer.php';
?>
