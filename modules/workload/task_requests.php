<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/task_approval.php';
requireRole([ROLE_ADMIN, ROLE_STAFF]);

$isAdministrator = isAdmin();
$isChairperson = false;
$currentUserId = (int)currentUserId();
$requestId = (int)($_GET['request_id'] ?? 0);
$chairCommitteeIds = [];
if (!$isAdministrator && currentRole() === ROLE_STAFF) {
    $chairStmt = db()->prepare(
        "SELECT committee_id
         FROM committee_members
         WHERE user_id = :user_id
           AND member_role = 'Chairperson'
           AND status = 'Active'"
    );
    $chairStmt->execute([':user_id' => $currentUserId]);
    $chairCommitteeIds = array_map('intval', $chairStmt->fetchAll(PDO::FETCH_COLUMN));
    $isChairperson = !empty($chairCommitteeIds);
}
$canReviewRequests = $isAdministrator || $isChairperson;
$statusFilter = clean($_GET['status'] ?? '');
$validFilters = ['Pending', 'Approved', 'Rejected', 'all'];
if (!in_array($statusFilter, $validFilters, true)) {
    $statusFilter = $canReviewRequests
        ? ($requestId > 0 ? 'all' : 'Pending')
        : 'all';
}
$pageTitle = $isAdministrator
    ? 'Pending Task Requests'
    : ($isChairperson ? 'Committee Task Requests' : 'My Task Requests');
$activeMenu = 'task_requests';

$conditions = [];
$params = [];
if ($isChairperson) {
    $placeholders = [];
    foreach ($chairCommitteeIds as $index => $committeeId) {
        $key = ':chair_committee_' . $index;
        $placeholders[] = $key;
        $params[$key] = $committeeId;
    }
    $conditions[] = 'cm.committee_id IN (' . implode(', ', $placeholders) . ')';
} elseif (!$isAdministrator) {
    $conditions[] = 'p.proposed_by = :requester_id';
    $params[':requester_id'] = $currentUserId;
}
if ($statusFilter !== 'all') {
    $conditions[] = 'p.state = :state';
    $params[':state'] = $statusFilter;
} elseif ($canReviewRequests) {
    $conditions[] = "p.state IN ('Pending', 'Approved', 'Rejected')";
}
$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$stmt = db()->prepare(
    "SELECT p.proposal_id, p.task_title, p.task_description, p.priority, p.due_date,
            p.state, p.proposed_at, p.proposed_by, p.approved_at, p.rejected_at,
            p.admin_response, p.resulting_workload_id,
            requester.full_name AS requester_name, roles.name AS requester_role,
            member.full_name AS assignee_name, c.committee_name,
            COALESCE(j.jurisdiction_name,
                (SELECT j2.jurisdiction_name
                 FROM jurisdictions j2
                 WHERE j2.category = c.committee_name
                 ORDER BY j2.jurisdiction_name
                 LIMIT 1)
            ) AS jurisdiction_name,
            tt.task_name AS template_name,
            processor.full_name AS processor_name
     FROM workload_assignment_proposals p
     INNER JOIN committee_members cm ON cm.committee_member_id = p.committee_member_id
     INNER JOIN users member ON member.id = cm.user_id
     INNER JOIN committees c ON c.committee_id = cm.committee_id
     LEFT JOIN jurisdictions j
       ON j.jurisdiction_id = COALESCE(p.jurisdiction_id, c.jurisdiction_id)
     LEFT JOIN task_templates tt ON tt.id = p.task_template_id
     LEFT JOIN users requester ON requester.id = p.proposed_by
     LEFT JOIN roles ON roles.id = requester.role_id
     LEFT JOIN users processor ON processor.id = COALESCE(p.approved_by, p.rejected_by)
     $where
     ORDER BY CASE WHEN p.state = 'Pending' THEN 0 ELSE 1 END, p.proposed_at DESC"
);
$stmt->execute($params);
$requests = $stmt->fetchAll();

include __DIR__ . '/../../layouts/header.php';
?>
<div class="app-wrapper">
  <?php include __DIR__ . '/../../layouts/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/../../layouts/content-topbar.php'; ?>
    <div class="container-fluid py-3 task-request-page">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
          <h4 class="mb-1"><?= e($pageTitle) ?></h4>
          <p class="text-muted mb-0">
            <?= $canReviewRequests
                ? ($isAdministrator
                    ? 'Review task proposals and approve them to create assignments, or reject them with an optional response.'
                    : 'Review task proposals for your committees and approve them to create assignments, or reject them with an optional response.')
                : 'View the review status and reviewer response for your task proposals.' ?>
          </p>
        </div>
        <?php if ($canReviewRequests): ?>
          <form method="get" class="d-flex align-items-center gap-2">
            <label for="taskRequestStatus" class="small text-muted">Show</label>
            <select class="form-select form-select-sm" id="taskRequestStatus" name="status" onchange="this.form.submit()">
              <?php foreach (['Pending' => 'Pending only', 'all' => 'All review history', 'Approved' => 'Approved', 'Rejected' => 'Rejected'] as $value => $label): ?>
                <option value="<?= e($value) ?>" <?= $statusFilter === $value ? 'selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </form>
        <?php endif; ?>
      </div>

      <?php if (!$requests): ?>
        <div class="card"><div class="card-body text-center py-5 text-muted">
          <i class="bi bi-clipboard-check fs-2 d-block mb-2"></i>
          <?= $canReviewRequests ? 'No task requests are waiting for review.' : 'You have no task proposals in this view.' ?>
        </div></div>
      <?php else: ?>
        <div class="card task-request-table-card">
          <div class="table-responsive">
            <table class="table align-middle mb-0 task-request-table">
              <thead>
                <tr>
                  <th>Task</th>
                  <th>Proposed By</th>
                  <th>Committee /<br>Jurisdiction</th>
                  <th>Priority</th>
                  <th>Due Date</th>
                  <th>Submitted</th>
                  <th>Status</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($requests as $request): ?>
                  <?php
                    $statusClass = match ($request['state']) {
                        'Pending' => 'pending',
                        'Approved' => 'approved',
                        default => 'rejected',
                    };
                    $priorityClass = match ($request['priority']) {
                        'Urgent' => 'urgent',
                        'High' => 'high',
                        'Low' => 'low',
                        default => 'medium',
                    };
                    $processedAt = $request['approved_at'] ?: $request['rejected_at'];
                  ?>
                  <tr id="task-request-<?= (int)$request['proposal_id'] ?>">
                    <td class="task-request-title-cell">
                      <strong><?= e($request['task_title']) ?></strong>
                      <?php if (!empty($request['template_name'])): ?>
                        <small class="d-block text-primary">Standard task: <?= e($request['template_name']) ?></small>
                      <?php endif; ?>
                      <small><?= e(truncate($request['task_description'] ?: 'No description provided.', 90)) ?></small>
                    </td>
                    <td>
                      <?= e($request['requester_name'] ?: 'Account unavailable') ?>
                      <small class="d-block text-muted"><?= e($request['requester_role'] ?: 'Former user') ?></small>
                    </td>
                    <td>
                      <?= e($request['committee_name']) ?>
                      <small class="d-block text-muted"><?= e($request['jurisdiction_name'] ?: 'No jurisdiction') ?></small>
                    </td>
                    <td><span class="badge task-request-pill task-request-priority-<?= e($priorityClass) ?>"><?= e($request['priority']) ?></span></td>
                    <td><?= $request['due_date'] ? e(formatDate($request['due_date'])) : '<span class="text-muted">Not set</span>' ?></td>
                    <td><?= e(date('M j, Y g:i A', strtotime($request['proposed_at']))) ?></td>
                    <td>
                      <span class="badge task-request-pill task-request-status-<?= e($statusClass) ?>"><?= e($request['state']) ?></span>
                      <?php if ($processedAt): ?>
                        <small class="d-block text-muted"><?= e($request['processor_name'] ?: 'Reviewer') ?> · <?= e(date('M j, Y g:i A', strtotime($processedAt))) ?></small>
                      <?php endif; ?>
                    </td>
                    <td class="task-request-actions-cell">
                      <button type="button" class="btn btn-outline-secondary btn-sm task-request-view"
                              data-title="<?= e($request['task_title']) ?>"
                              data-description="<?= e($request['task_description'] ?: '') ?>"
                              data-requester="<?= e($request['requester_name'] ?: 'Account unavailable') ?>"
                              data-role="<?= e($request['requester_role'] ?: 'Former user') ?>"
                              data-committee="<?= e($request['committee_name']) ?>"
                              data-jurisdiction="<?= e($request['jurisdiction_name'] ?: 'No jurisdiction') ?>"
                              data-template="<?= e($request['template_name'] ?: '') ?>"
                              data-assignee="<?= e($request['assignee_name']) ?>"
                              data-priority="<?= e($request['priority']) ?>"
                              data-due="<?= e($request['due_date'] ? formatDate($request['due_date']) : 'Not set') ?>"
                              data-submitted="<?= e(date('M j, Y g:i A', strtotime($request['proposed_at']))) ?>"
                              data-status="<?= e($request['state']) ?>"
                              data-response="<?= e($request['admin_response'] ?: '') ?>">
                        <i class="bi bi-eye"></i> View Details
                      </button>
                      <?php if ($canReviewRequests && $request['state'] === 'Pending'): ?>
                        <button type="button" class="btn btn-success btn-sm" data-review-task="Approved" data-proposal-id="<?= (int)$request['proposal_id'] ?>">
                          Approve
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm" data-review-task="Rejected" data-proposal-id="<?= (int)$request['proposal_id'] ?>">
                          Reject
                        </button>
                      <?php elseif ($request['state'] === 'Approved' && $request['resulting_workload_id']): ?>
                        <a class="btn btn-outline-primary btn-sm" href="<?= e(APP_URL) ?>/modules/workload/task.php?id=<?= (int)$request['resulting_workload_id'] ?>">View Task</a>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="modal fade" id="taskRequestDetailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <h5 class="modal-title" id="taskRequestDetailTitle">Task request details</h5>
          <span class="badge task-request-pill task-request-status-pending" id="taskRequestDetailStatus">Pending</span>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3 task-request-detail-grid">
          <div><span>Proposed by</span><strong id="taskRequestDetailRequester"></strong><small id="taskRequestDetailRole"></small></div>
          <div><span>Committee</span><strong id="taskRequestDetailCommittee"></strong><small id="taskRequestDetailJurisdiction"></small><small id="taskRequestDetailTemplate"></small></div>
          <div><span>Proposed assignee</span><strong id="taskRequestDetailAssignee"></strong></div>
          <div><span>Priority / Due date</span><strong><span id="taskRequestDetailPriority"></span> · <span id="taskRequestDetailDue"></span></strong></div>
          <div><span>Submitted</span><strong id="taskRequestDetailSubmitted"></strong></div>
          <div class="task-request-detail-description"><span>Description</span><p id="taskRequestDetailDescription"></p></div>
          <div class="task-request-detail-response d-none" id="taskRequestDetailResponseWrap"><span>Reviewer response</span><p id="taskRequestDetailResponse"></p></div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button></div>
    </div>
  </div>
</div>
<?php
$extraJs = [APP_URL . '/assets/js/task-requests.js?v=' . (int)@filemtime(__DIR__ . '/../../assets/js/task-requests.js')];
include __DIR__ . '/../../layouts/footer.php';
?>
