<?php
require_once __DIR__ . '/../../includes/task_approval.php';
requireRole([ROLE_ADMIN, ROLE_STAFF, ROLE_COMMITTEE]);

$isAdministrator = isAdmin();
$isCommitteeMember = isCommitteeMember();
$userId = (int)currentUserId();
$section = clean($_GET['section'] ?? '');
$requestId = (int)($_GET['request_id'] ?? 0);
$statusFilter = clean($_GET['status'] ?? '');
if (!in_array($statusFilter, ['Pending', 'Approved', 'Rejected', 'all'], true)) {
    $statusFilter = $isCommitteeMember && $requestId <= 0 ? 'all' : 'Pending';
}
if ($requestId > 0 && !isset($_GET['status'])) {
    $statusFilter = 'all';
}

$completionConditions = [];
$removalConditions = [];
$completionParams = [];
$removalParams = [];
if ($isCommitteeMember) {
    $completionConditions[] = 'completion_request.submitted_by = :completion_user_id';
    $removalConditions[] = 'removal_request.submitted_by = :removal_user_id';
    $completionParams[':completion_user_id'] = $userId;
    $removalParams[':removal_user_id'] = $userId;
} elseif (!$isAdministrator) {
    $committeeScope = "SELECT committee_id FROM committee_members
                       WHERE user_id = :chair_user_id
                         AND member_role = 'Chairperson' AND status = 'Active'";
    $completionConditions[] = 'completion_request.committee_id IN (' . $committeeScope . ')';
    $removalConditions[] = 'removal_request.committee_id IN (' . $committeeScope . ')';
    $completionParams[':chair_user_id'] = $userId;
    $removalParams[':chair_user_id'] = $userId;
}
if ($statusFilter !== 'all') {
    $completionConditions[] = 'completion_request.status = :completion_status';
    $removalConditions[] = 'removal_request.status = :removal_status';
    $completionParams[':completion_status'] = $statusFilter;
    $removalParams[':removal_status'] = $statusFilter;
}
$completionWhere = $completionConditions ? 'WHERE ' . implode(' AND ', $completionConditions) : '';
$removalWhere = $removalConditions ? 'WHERE ' . implode(' AND ', $removalConditions) : '';

$completionStmt = db()->prepare(
    "SELECT completion_request.id, completion_request.workload_id,
            completion_request.task_title_snapshot, completion_request.committee_id,
            completion_request.committee_snapshot, completion_request.jurisdiction_snapshot,
            completion_request.completion_notes, completion_request.status,
            completion_request.reviewed_at, completion_request.reviewer_remarks,
            completion_request.created_at, requester.full_name AS requester_name,
            reviewer.full_name AS reviewer_name, completion_file.id AS file_id,
            completion_file.original_filename, completion_file.mime_type,
            wa.workload_id AS current_workload_id
     FROM task_completion_requests completion_request
     LEFT JOIN users requester ON requester.id = completion_request.submitted_by
     LEFT JOIN users reviewer ON reviewer.id = completion_request.reviewed_by
     LEFT JOIN task_completion_request_files completion_file
       ON completion_file.request_id = completion_request.id
     LEFT JOIN workload_assignments wa ON wa.workload_id = completion_request.workload_id
     $completionWhere
     ORDER BY CASE WHEN completion_request.status = 'Pending' THEN 0 ELSE 1 END,
              completion_request.created_at DESC"
);
$completionStmt->execute($completionParams);
$completionRequests = $completionStmt->fetchAll();

$removalStmt = db()->prepare(
    "SELECT removal_request.id, removal_request.workload_id,
            removal_request.task_title_snapshot, removal_request.committee_id,
            removal_request.committee_snapshot, removal_request.jurisdiction_snapshot,
            removal_request.reason, removal_request.status,
            removal_request.reviewed_at, removal_request.reviewer_remarks,
            removal_request.created_at, requester.full_name AS requester_name,
            reviewer.full_name AS reviewer_name, wa.workload_id AS current_workload_id
     FROM task_removal_requests removal_request
     LEFT JOIN users requester ON requester.id = removal_request.submitted_by
     LEFT JOIN users reviewer ON reviewer.id = removal_request.reviewed_by
     LEFT JOIN workload_assignments wa ON wa.workload_id = removal_request.workload_id
     $removalWhere
     ORDER BY CASE WHEN removal_request.status = 'Pending' THEN 0 ELSE 1 END,
              removal_request.created_at DESC"
);
$removalStmt->execute($removalParams);
$removalRequests = $removalStmt->fetchAll();

$pageTitle = 'Approval Center';
$activeMenu = 'approval_center';
include __DIR__ . '/../../layouts/header.php';
?>
<div class="app-wrapper">
  <?php include __DIR__ . '/../../layouts/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/../../layouts/content-topbar.php'; ?>
    <div class="container-fluid py-3 approval-center-page">
      <div class="approval-center-heading d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div class="approval-center-title">
          <span class="approval-center-title-icon"><i class="bi bi-clipboard-check"></i></span>
          <div>
            <h4 class="mb-1">Approval Center</h4>
            <p class="text-muted mb-0">
              <?= $isCommitteeMember
                  ? 'Track your task completion and task removal requests.'
                  : 'Review task completion proof and separate task removal requests for your committees.' ?>
            </p>
          </div>
        </div>
        <form method="get" class="approval-status-filter d-flex align-items-center gap-2">
          <?php if ($section !== ''): ?><input type="hidden" name="section" value="<?= e($section) ?>"><?php endif; ?>
          <?php if ($requestId > 0): ?><input type="hidden" name="request_id" value="<?= $requestId ?>"><?php endif; ?>
          <label for="approvalStatusFilter" class="small text-muted">Show</label>
          <select class="form-select form-select-sm" id="approvalStatusFilter" name="status" onchange="this.form.submit()">
            <?php foreach (['Pending' => 'Pending', 'all' => 'All requests', 'Approved' => 'Approved', 'Rejected' => 'Rejected'] as $value => $label): ?>
              <option value="<?= e($value) ?>" <?= $statusFilter === $value ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </form>
      </div>

      <?php if (canManage()): ?>
        <a class="approval-center-related-link" href="<?= e(APP_URL) ?>/modules/jurisdictions/requests.php">
          <i class="bi bi-inbox"></i>
          <span><strong>Jurisdiction Removal Requests</strong><small>Open the existing jurisdiction removal review workflow.</small></span>
          <i class="bi bi-arrow-up-right ms-auto"></i>
        </a>
      <?php endif; ?>

      <section class="card approval-request-section mb-3" id="taskCompletionRequests">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
          <div class="approval-section-title">
            <span class="approval-section-icon approval-section-icon-completion"><i class="bi bi-check2-circle"></i></span>
            <div>
              <h5 class="mb-1">Task Completion Requests</h5>
              <p class="small text-muted mb-0">Members submit remarks and proof; assignments are marked complete only after approval.</p>
            </div>
          </div>
          <span class="badge rounded-pill approval-request-count"><?= count($completionRequests) ?> request<?= count($completionRequests) === 1 ? '' : 's' ?></span>
        </div>
        <?php if (!$completionRequests): ?>
          <div class="card-body approval-empty-state text-center text-muted py-5">
            <i class="bi bi-inbox approval-empty-icon"></i>
            <strong class="d-block text-body mb-1">No completion requests</strong>
            <?= $isCommitteeMember ? 'You have no task completion requests in this view.' : 'No task completion requests are waiting in this view.' ?>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table align-middle mb-0 approval-request-table">
              <thead><tr><th>Member</th><th>Task &amp; Committee</th><th>Submitted</th><th>Remarks</th><th class="text-end">Review</th></tr></thead>
              <tbody>
              <?php foreach ($completionRequests as $request): ?>
                <?php $statusClass = $request['status'] === 'Pending' ? 'pending' : ($request['status'] === 'Approved' ? 'approved' : 'rejected'); ?>
                <tr id="completion-request-<?= (int)$request['id'] ?>">
                  <td data-label="Member"><?= e($request['requester_name'] ?: 'Account unavailable') ?></td>
                  <td data-label="Task & Committee">
                    <strong><?= e($request['task_title_snapshot']) ?></strong>
                    <small class="d-block"><?= e($request['committee_snapshot']) ?></small>
                    <small class="d-block text-muted"><?= e($request['jurisdiction_snapshot'] ?: 'No jurisdiction') ?></small>
                    <?php if ($request['current_workload_id']): ?>
                      <a class="small" href="<?= e(APP_URL) ?>/modules/workload/task.php?id=<?= (int)$request['current_workload_id'] ?>">View Task</a>
                    <?php endif; ?>
                  </td>
                  <td data-label="Submitted">
                    <span class="approval-submitted-date"><?= e(date('M j, Y', strtotime($request['created_at']))) ?></span>
                    <small class="approval-submitted-time"><?= e(date('g:i A', strtotime($request['created_at']))) ?></small>
                    <span class="badge approval-status-badge approval-status-<?= $statusClass ?> d-block mt-1"><?= e($request['status'] === 'Pending' ? 'Awaiting review' : $request['status']) ?></span>
                    <?php if ($request['reviewer_name']): ?><small class="d-block text-muted">Reviewed by <?= e($request['reviewer_name']) ?></small><?php endif; ?>
                  </td>
                  <td data-label="Completion Remarks"><?= e(truncate($request['completion_notes'], 140)) ?></td>
                  <td class="approval-actions-cell" data-label="Review">
                    <div class="approval-request-actions">
                      <?php if ($request['file_id']): ?>
                        <button type="button" class="btn btn-outline-primary btn-sm" data-proof-preview
                                data-proof-url="<?= e(APP_URL . '/modules/workload/download_completion_proof.php?id=' . (int)$request['file_id']) ?>"
                                data-proof-name="<?= e($request['original_filename']) ?>"
                                data-proof-type="<?= e($request['mime_type']) ?>">
                          <i class="bi bi-paperclip"></i> View Proof
                        </button>
                      <?php else: ?>
                        <span class="approval-proof-unavailable">Proof unavailable</span>
                      <?php endif; ?>
                      <button type="button" class="btn btn-outline-secondary btn-sm" data-approval-details
                              data-request-type="Task Completion Request"
                              data-requester="<?= e($request['requester_name'] ?: 'Account unavailable') ?>"
                              data-task="<?= e($request['task_title_snapshot']) ?>"
                              data-committee="<?= e($request['committee_snapshot']) ?>"
                              data-jurisdiction="<?= e($request['jurisdiction_snapshot'] ?: 'No jurisdiction') ?>"
                              data-submitted="<?= e(date('M j, Y g:i A', strtotime($request['created_at']))) ?>"
                              data-status="<?= e($request['status']) ?>"
                              data-notes="<?= e($request['completion_notes']) ?>"
                              data-reviewer-remarks="<?= e($request['reviewer_remarks'] ?: '') ?>"
                              data-proof-url="<?= $request['file_id'] ? e(APP_URL . '/modules/workload/download_completion_proof.php?id=' . (int)$request['file_id']) : '' ?>"
                              data-proof-name="<?= e($request['original_filename'] ?: '') ?>">
                        <i class="bi bi-eye"></i> View Details
                      </button>
                      <?php if (canManage() && $request['status'] === 'Pending'): ?>
                        <button type="button" class="btn btn-success btn-sm" data-review-completion="Approved" data-request-id="<?= (int)$request['id'] ?>">Approve</button>
                        <button type="button" class="btn btn-outline-danger btn-sm" data-review-completion="Rejected" data-request-id="<?= (int)$request['id'] ?>">Reject</button>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </section>

      <section class="card approval-request-section mb-3" id="taskRemovalRequests">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
          <div class="approval-section-title">
            <span class="approval-section-icon approval-section-icon-removal"><i class="bi bi-trash3"></i></span>
            <div>
              <h5 class="mb-1">Task Removal Requests</h5>
              <p class="small text-muted mb-0">A separate workflow for members asking to remove an assignment; approval removes the task.</p>
            </div>
          </div>
          <span class="badge rounded-pill approval-request-count"><?= count($removalRequests) ?> request<?= count($removalRequests) === 1 ? '' : 's' ?></span>
        </div>
        <?php if (!$removalRequests): ?>
          <div class="card-body approval-empty-state text-center text-muted py-5">
            <i class="bi bi-inbox approval-empty-icon"></i>
            <strong class="d-block text-body mb-1">No removal requests</strong>
            <?= $isCommitteeMember ? 'You have no task removal requests in this view.' : 'No task removal requests are waiting in this view.' ?>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table align-middle mb-0 approval-request-table">
              <thead><tr><th>Member</th><th>Task &amp; Committee</th><th>Submitted</th><th>Removal Reason / Reviewer Remarks</th><th class="text-end">Actions</th></tr></thead>
              <tbody>
              <?php foreach ($removalRequests as $request): ?>
                <?php $statusClass = $request['status'] === 'Pending' ? 'pending' : ($request['status'] === 'Approved' ? 'approved' : 'rejected'); ?>
                <tr id="removal-request-<?= (int)$request['id'] ?>">
                  <td data-label="Member"><?= e($request['requester_name'] ?: 'Account unavailable') ?></td>
                  <td data-label="Task & Committee">
                    <strong><?= e($request['task_title_snapshot']) ?></strong>
                    <small class="d-block"><?= e($request['committee_snapshot']) ?></small>
                    <small class="d-block text-muted"><?= e($request['jurisdiction_snapshot'] ?: 'No jurisdiction') ?></small>
                    <?php if ($request['current_workload_id']): ?>
                      <a class="small" href="<?= e(APP_URL) ?>/modules/workload/task.php?id=<?= (int)$request['current_workload_id'] ?>">View Task</a>
                    <?php endif; ?>
                  </td>
                  <td data-label="Submitted">
                    <span class="approval-submitted-date"><?= e(date('M j, Y', strtotime($request['created_at']))) ?></span>
                    <small class="approval-submitted-time"><?= e(date('g:i A', strtotime($request['created_at']))) ?></small>
                    <span class="badge approval-status-badge approval-status-<?= $statusClass ?> d-block mt-1"><?= e($request['status'] === 'Pending' ? 'Awaiting review' : $request['status']) ?></span>
                    <?php if ($request['reviewer_name']): ?><small class="d-block text-muted">Reviewed by <?= e($request['reviewer_name']) ?></small><?php endif; ?>
                  </td>
                  <td data-label="Removal Reason / Reviewer Remarks">
                    <span><?= e(truncate($request['reason'], 120)) ?></span>
                    <?php if ($request['reviewer_remarks']): ?><small class="d-block text-muted mt-1"><strong>Reviewer Remarks:</strong> <?= e(truncate($request['reviewer_remarks'], 120)) ?></small><?php endif; ?>
                  </td>
                  <td class="approval-actions-cell" data-label="Actions">
                    <div class="approval-request-actions">
                      <button type="button" class="btn btn-outline-secondary btn-sm" data-approval-details
                              data-request-type="Task Removal Request"
                              data-requester="<?= e($request['requester_name'] ?: 'Account unavailable') ?>"
                              data-task="<?= e($request['task_title_snapshot']) ?>"
                              data-committee="<?= e($request['committee_snapshot']) ?>"
                              data-jurisdiction="<?= e($request['jurisdiction_snapshot'] ?: 'No jurisdiction') ?>"
                              data-submitted="<?= e(date('M j, Y g:i A', strtotime($request['created_at']))) ?>"
                              data-status="<?= e($request['status']) ?>"
                              data-notes="<?= e($request['reason']) ?>"
                              data-reviewer-remarks="<?= e($request['reviewer_remarks'] ?: '') ?>">
                        <i class="bi bi-eye"></i> View Details
                      </button>
                      <?php if (canManage() && $request['status'] === 'Pending'): ?>
                        <button type="button" class="btn btn-success btn-sm" data-review-removal="Approved" data-request-id="<?= (int)$request['id'] ?>">Approve</button>
                        <button type="button" class="btn btn-outline-danger btn-sm" data-review-removal="Rejected" data-request-id="<?= (int)$request['id'] ?>">Reject</button>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </section>
    </div>
  </div>
</div>

<div class="modal fade" id="approvalRequestDetailsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <span class="small text-muted" id="approvalDetailType"></span>
          <h5 class="modal-title" id="approvalDetailTask"></h5>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3 approval-detail-grid">
          <div><span>Requested By</span><strong id="approvalDetailRequester"></strong></div>
          <div><span>Committee</span><strong id="approvalDetailCommittee"></strong></div>
          <div><span>Jurisdiction</span><strong id="approvalDetailJurisdiction"></strong></div>
          <div><span>Submitted</span><strong id="approvalDetailSubmitted"></strong></div>
          <div><span>Status</span><strong id="approvalDetailStatus"></strong></div>
          <div class="col-12"><span id="approvalDetailNotesLabel"></span><p id="approvalDetailNotes" class="mb-0"></p></div>
          <div class="col-12 d-none" id="approvalDetailRemarksWrap"><span>Reviewer Remarks</span><p id="approvalDetailRemarks" class="mb-0"></p></div>
          <div class="col-12 d-none" id="approvalDetailProofWrap"><span>Proof / Supporting File</span><a id="approvalDetailProof" href=""></a></div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button></div>
    </div>
  </div>
</div>

<div class="modal fade" id="completionProofPreviewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable approval-proof-modal-dialog">
    <div class="modal-content approval-proof-modal">
      <div class="modal-header">
        <div class="approval-proof-heading">
          <span class="approval-proof-icon"><i class="bi bi-file-earmark-check"></i></span>
          <div>
            <span class="small text-muted">Completion proof</span>
            <h5 class="modal-title" id="completionProofFileName">Proof preview</h5>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body approval-proof-modal-body">
        <div class="approval-proof-image-wrap d-none" id="completionProofImageWrap">
          <img id="completionProofImage" src="" alt="Completion proof preview">
        </div>
        <div class="approval-proof-document-wrap d-none" id="completionProofDocumentWrap">
          <iframe id="completionProofDocument" title="Completion proof document preview"></iframe>
        </div>
        <div class="approval-proof-unavailable-state d-none" id="completionProofDownloadOnly">
          <span class="approval-proof-download-icon"><i class="bi bi-file-earmark-lock"></i></span>
          <h6>Preview isn’t available for this file type</h6>
          <p class="mb-0">Download the file to open it in a compatible application.</p>
        </div>
      </div>
      <div class="modal-footer">
        <a class="btn btn-primary btn-sm" id="completionProofDownload" href="">
          <i class="bi bi-download me-1"></i> Download proof
        </a>
        <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
<?php
$extraJs = [APP_URL . '/assets/js/approval-center.js?v=' . (int)@filemtime(__DIR__ . '/../../assets/js/approval-center.js')];
include __DIR__ . '/../../layouts/footer.php';
?>
