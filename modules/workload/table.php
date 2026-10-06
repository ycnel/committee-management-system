<?php
/**
 * modules/workload/table.php
 * ------------------------------------------------------------------
 * Filtered/sorted/paginated assignment table.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../../includes/checklist.php';

$pdo = db();

$search      = clean($_GET['search'] ?? '');
$committeeId = (int)($_GET['committee_id'] ?? 0);
$priorityFil = clean($_GET['priority'] ?? '');
$taskState   = clean($_GET['task_state'] ?? '');

$sortableColumns = ['task_title', 'priority', 'due_date'];
$sortBy  = in_array($_GET['sort'] ?? '', $sortableColumns, true) ? $_GET['sort'] : 'due_date';
$sortDir = strtolower($_GET['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

$where = [];
$params = [];
if ($search !== '') { $where[] = 'wa.task_title LIKE :s'; $params[':s'] = '%' . $search . '%'; }
if ($committeeId > 0) { $where[] = 'c.committee_id = :cid'; $params[':cid'] = $committeeId; }
if ($priorityFil !== '') { $where[] = 'wa.priority = :priority'; $params[':priority'] = $priorityFil; }
if ($taskState === 'active') { $where[] = "wa.status <> 'Completed'"; }
if ($taskState === 'done') { $where[] = "wa.status = 'Completed'"; }
// Committee Member: view only tasks assigned to them, never other members' tasks.
if (isCommitteeMember()) { $where[] = 'cm.user_id = :uid'; $params[':uid'] = currentUserId(); }
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$countStmt = $pdo->prepare(
    "SELECT COUNT(*) FROM workload_assignments wa
     INNER JOIN committee_members cm ON cm.committee_member_id = wa.committee_member_id
     INNER JOIN committees c ON c.committee_id = cm.committee_id
     $whereSql"
);
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();
$pageInfo = paginate($totalRows);

$sql = "SELECT wa.*, u.full_name AS member_name, c.committee_name,
         COALESCE(wj.jurisdiction_name, cj.jurisdiction_name,
             (SELECT j2.jurisdiction_name
              FROM jurisdictions j2
              WHERE j2.category = c.committee_name
              ORDER BY j2.jurisdiction_name
              LIMIT 1)
         ) AS jurisdiction_name,
         tt.task_name AS template_name, tt.proof_requirement,
         p.proposal_id AS proposal_id, p.ai_recommendation_id AS proposal_ai_recommendation_id,
         p.proposed_by AS proposal_proposed_by, p.responded_at AS proposal_responded_at,
         p.state AS proposal_state,
         completion_request.id AS completion_request_id,
         completion_request.status AS completion_status,
         completion_request.created_at AS completion_submitted_at,
         completion_request.completion_notes,
         completion_request.reviewer_remarks AS completion_reviewer_remarks,
         completion_file.id AS completion_file_id,
         completion_file.original_filename AS completion_filename,
         removal_request.id AS removal_request_id,
         removal_request.status AS removal_status,
         removal_request.created_at AS removal_submitted_at,
         removal_request.reason AS removal_reason,
         removal_request.reviewer_remarks AS removal_reviewer_remarks
        FROM workload_assignments wa
        INNER JOIN committee_members cm ON cm.committee_member_id = wa.committee_member_id
        INNER JOIN users u ON u.id = cm.user_id
        INNER JOIN committees c ON c.committee_id = cm.committee_id
  LEFT JOIN jurisdictions wj ON wj.jurisdiction_id = wa.jurisdiction_id
  LEFT JOIN jurisdictions cj ON cj.jurisdiction_id = c.jurisdiction_id
  LEFT JOIN task_templates tt ON tt.id = wa.task_template_id
  LEFT JOIN workload_assignment_proposals p
    ON p.resulting_workload_id = wa.workload_id AND p.state = 'Approved'
  LEFT JOIN (
    SELECT workload_id, MAX(id) AS request_id
    FROM task_completion_requests
    WHERE workload_id IS NOT NULL
    GROUP BY workload_id
  ) latest_completion ON latest_completion.workload_id = wa.workload_id
  LEFT JOIN task_completion_requests completion_request
    ON completion_request.id = latest_completion.request_id
  LEFT JOIN task_completion_request_files completion_file
    ON completion_file.request_id = completion_request.id
  LEFT JOIN (
    SELECT workload_id, MAX(id) AS request_id
    FROM task_removal_requests
    WHERE workload_id IS NOT NULL
    GROUP BY workload_id
  ) latest_removal ON latest_removal.workload_id = wa.workload_id
  LEFT JOIN task_removal_requests removal_request
    ON removal_request.id = latest_removal.request_id
        $whereSql
        ORDER BY wa.$sortBy $sortDir
        LIMIT {$pageInfo['perPage']} OFFSET {$pageInfo['offset']}";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$priorityColors = ['Low' => 'success', 'Medium' => 'info', 'High' => 'warning', 'Urgent' => 'danger'];
?>
<?php if (empty($rows)): ?>
  <div class="workload-empty-state text-center text-muted py-5">
    <span class="workload-empty-icon" aria-hidden="true"><i class="bi bi-inbox"></i></span>
    <p class="workload-empty-title mb-1">No tasks to show</p>
    <p class="workload-empty-copy mb-3">No tasks found for the selected filters.</p>
    <?php if ($committeeId > 0 && canManage()): ?>
      <button type="button" class="btn btn-primary btn-sm workload-empty-assign" data-committee-id="<?= $committeeId ?>">
        <i class="bi bi-plus-circle"></i> Assign a task to this committee
      </button>
    <?php endif; ?>
  </div>
<?php else: ?>
  <div class="workload-card-grid">
    <?php foreach ($rows as $r):
      $proposalChecklistProgress = null;
      if ($r['proposal_id']) {
        $proposalChecklistProgress = checklistProgress($pdo, [
          'proposal_id' => (int)$r['proposal_id'],
          'ai_recommendation_id' => $r['proposal_ai_recommendation_id'],
          'proposed_by' => $r['proposal_proposed_by'],
          'responded_at' => $r['proposal_responded_at'],
          'state' => $r['proposal_state'],
        ]);
      }
    ?>
      <article class="workload-task-card">
        <div class="workload-task-card-inner">
          <div class="workload-task-topline">
            <span class="workload-task-committee"><i class="bi bi-diagram-3"></i> <?= e($r['committee_name']) ?></span>
          </div>
          <div class="workload-task-date">
            <?= $r['due_date'] ? formatDate($r['due_date']) : 'No due date' ?>
          </div>
          <h3 class="workload-task-title"><?= e($r['task_title']) ?></h3>
          <p class="workload-task-description">
            <?= e($r['task_description'] ? truncate($r['task_description'], 125) : 'No task description provided.') ?>
          </p>
          <div class="workload-task-tags">
            <span class="workload-task-tag priority-<?= e(strtolower($r['priority'])) ?>"><?= e($r['priority']) ?></span>
            <?php if ($r['status'] === 'Completed'): ?>
              <button type="button" class="workload-task-done-button" disabled aria-label="Task done">
                <i class="bi bi-check2-circle" aria-hidden="true"></i> Task Done
              </button>
            <?php endif; ?>
            <?php if (!empty($r['jurisdiction_name'])): ?>
              <span class="workload-task-tag"><?= e($r['jurisdiction_name']) ?></span>
            <?php endif; ?>
            <?php if (!empty($r['template_name'])): ?>
              <span class="workload-task-tag"><?= e($r['template_name']) ?></span>
            <?php endif; ?>
            <?php if ($proposalChecklistProgress): ?>
              <a class="workload-task-tag text-decoration-none" href="<?= e(APP_URL) ?>/modules/workload/proposal.php?id=<?= (int)$r['proposal_id'] ?>" title="Open committee matter checklist">
                <i class="bi bi-list-check"></i>
                Checklist <?= (int)$proposalChecklistProgress['checked'] ?>/<?= (int)$proposalChecklistProgress['total'] ?>
              </a>
            <?php endif; ?>
          </div>
          <?php if (isCommitteeMember()): ?>
            <?php if (!empty($r['completion_status'])): ?>
              <div class="workload-member-request-status">
                <span class="badge text-bg-<?= $r['completion_status'] === 'Pending' ? 'warning' : ($r['completion_status'] === 'Approved' ? 'success' : 'danger') ?>">
                  <?= $r['completion_status'] === 'Pending' ? 'Pending Approval' : e($r['completion_status']) ?>
                </span>
                <?php if (!empty($r['completion_submitted_at'])): ?>
                  <small>Submitted <?= e(date('M j, Y', strtotime($r['completion_submitted_at']))) ?></small>
                <?php endif; ?>
                <?php if (!empty($r['completion_reviewer_remarks'])): ?>
                  <small class="d-block mt-1"><strong>Reviewer Remarks:</strong> <?= e(truncate($r['completion_reviewer_remarks'], 180)) ?></small>
                <?php endif; ?>
                <?php if (!empty($r['completion_file_id'])): ?>
                  <a class="btn btn-link btn-sm p-0 mt-1" href="<?= e(APP_URL) ?>/modules/workload/download_completion_proof.php?id=<?= (int)$r['completion_file_id'] ?>">
                    <i class="bi bi-paperclip"></i> <?= e($r['completion_filename'] ?: 'View Proof') ?>
                  </a>
                <?php endif; ?>
              </div>
            <?php endif; ?>
            <?php if (!empty($r['removal_status'])): ?>
              <div class="workload-member-request-status">
                <span class="badge text-bg-<?= $r['removal_status'] === 'Pending' ? 'warning' : ($r['removal_status'] === 'Approved' ? 'success' : 'danger') ?>">
                  <?= e($r['removal_status'] === 'Pending' ? 'Removal Pending' : 'Removal ' . $r['removal_status']) ?>
                </span>
                <?php if (!empty($r['removal_reviewer_remarks'])): ?>
                  <small class="d-block mt-1"><strong>Reviewer Remarks:</strong> <?= e(truncate($r['removal_reviewer_remarks'], 180)) ?></small>
                <?php endif; ?>
              </div>
            <?php endif; ?>
            <?php if ($r['completion_status'] !== 'Approved' && $r['completion_status'] !== 'Pending'
                && $r['removal_status'] !== 'Pending' && $r['status'] !== 'Completed'): ?>
              <div class="workload-member-task-actions">
                <button type="button" class="btn btn-primary btn-sm" data-submit-completion
                        data-workload-id="<?= (int)$r['workload_id'] ?>"
                        data-task-title="<?= e($r['task_title']) ?>"
                        data-committee-name="<?= e($r['committee_name']) ?>"
                        data-jurisdiction-name="<?= e($r['jurisdiction_name'] ?: 'Unassigned jurisdiction') ?>"
                        data-assigned-date="<?= e($r['assigned_date'] ? formatDate($r['assigned_date']) : 'Not recorded') ?>"
                        data-proof-requirement="<?= e($r['proof_requirement'] ?: '') ?>">
                  <i class="bi bi-send-check"></i> Submit for Approval
                </button>
                <?php if ($r['removal_status'] !== 'Approved'): ?>
                  <button type="button" class="btn btn-outline-secondary btn-sm" data-request-removal
                          data-workload-id="<?= (int)$r['workload_id'] ?>"
                          data-task-title="<?= e($r['task_title']) ?>"
                          data-committee-name="<?= e($r['committee_name']) ?>"
                          data-jurisdiction-name="<?= e($r['jurisdiction_name'] ?: 'Unassigned jurisdiction') ?>">
                    <i class="bi bi-trash3"></i> Request Removal
                  </button>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          <?php endif; ?>
          <div class="workload-task-bottomline">
            <span><i class="bi bi-person"></i> <?= e($r['member_name']) ?></span>
            <?php if (canManage()): ?>
              <div class="workload-task-actions">
                <button type="button" class="workload-action-button btn-edit-task" data-id="<?= (int)$r['workload_id'] ?>" title="Edit task"><i class="bi bi-pencil-square"></i></button>
                <button type="button" class="workload-action-button" data-confirm-delete="task &quot;<?= e($r['task_title']) ?>&quot;" data-delete-url="<?= e(APP_URL) ?>/modules/workload/ajax_delete.php?id=<?= (int)$r['workload_id'] ?>" title="Delete task"><i class="bi bi-trash"></i></button>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<div class="card-footer bg-white py-3">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
    <small class="text-muted">Showing <?= count($rows) ?> of <?= $pageInfo['total'] ?> task(s)</small>
    <?= renderPagination($pageInfo, 'index.php') ?>
  </div>
</div>
