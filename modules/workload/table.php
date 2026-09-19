<?php
/**
 * modules/workload/table.php
 * ------------------------------------------------------------------
 * Filtered/sorted/paginated assignment table.
 * ------------------------------------------------------------------
 */

$pdo = db();

$search      = clean($_GET['search'] ?? '');
$committeeId = (int)($_GET['committee_id'] ?? 0);
$priorityFil = clean($_GET['priority'] ?? '');

$sortableColumns = ['task_title', 'priority', 'due_date'];
$sortBy  = in_array($_GET['sort'] ?? '', $sortableColumns, true) ? $_GET['sort'] : 'due_date';
$sortDir = strtolower($_GET['dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';

$where = [];
$params = [];
if ($search !== '') { $where[] = 'wa.task_title LIKE :s'; $params[':s'] = '%' . $search . '%'; }
if ($committeeId > 0) { $where[] = 'c.committee_id = :cid'; $params[':cid'] = $committeeId; }
if ($priorityFil !== '') { $where[] = 'wa.priority = :priority'; $params[':priority'] = $priorityFil; }
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

$sql = "SELECT wa.*, u.full_name AS member_name, c.committee_name
        FROM workload_assignments wa
        INNER JOIN committee_members cm ON cm.committee_member_id = wa.committee_member_id
        INNER JOIN users u ON u.id = cm.user_id
        INNER JOIN committees c ON c.committee_id = cm.committee_id
        $whereSql
        ORDER BY wa.$sortBy $sortDir
        LIMIT {$pageInfo['perPage']} OFFSET {$pageInfo['offset']}";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$priorityColors = ['Low' => 'success', 'Medium' => 'info', 'High' => 'warning', 'Urgent' => 'danger'];
?>
<div class="workload-list-toolbar">
  <span class="small text-muted">Sort tasks by:</span>
  <a href="#" class="sort-link" data-sort="task_title">Title <i class="bi bi-arrow-down-up sort-icon"></i></a>
  <a href="#" class="sort-link" data-sort="priority">Priority <i class="bi bi-arrow-down-up sort-icon"></i></a>
  <a href="#" class="sort-link" data-sort="due_date">Due date <i class="bi bi-arrow-down-up sort-icon"></i></a>
</div>

<?php if (empty($rows)): ?>
  <div class="workload-empty-state text-center text-muted py-5">
    <p class="mb-3">No tasks found for the selected filters.</p>
    <?php if ($committeeId > 0 && canManage()): ?>
      <button type="button" class="btn btn-primary btn-sm workload-empty-assign" data-committee-id="<?= $committeeId ?>">
        <i class="bi bi-plus-circle"></i> Assign a task to this committee
      </button>
    <?php endif; ?>
  </div>
<?php else: ?>
  <div class="workload-card-grid">
    <?php foreach ($rows as $r):
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
          </div>
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
