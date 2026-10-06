<?php
/**
 * modules/committees/table.php
 * ------------------------------------------------------------------
 * Filtered/sorted/paginated committees table. Included by index.php
 * (initial render) and ajax_search.php (live AJAX refresh).
 * ------------------------------------------------------------------
 */

$pdo = db();

$search       = clean($_GET['search'] ?? '');
$jurisdiction = (int)($_GET['jurisdiction_id'] ?? 0);
$membersFil   = clean($_GET['members'] ?? '');

$sortableColumns = ['committee_name', 'status', 'date_created', 'member_count'];
$requestedSort = $_GET['sort'] ?? 'committee_name';
$sortBy = in_array($requestedSort, $sortableColumns, true) ? $requestedSort : 'committee_name';
$sortDir = strtolower($_GET['dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';

$where = [];
$params = [];
$rowParams = [];
if ($search !== '') {
    $where[] = '(c.committee_name LIKE :s1 OR c.description LIKE :s2)';
    $params[':s1'] = $params[':s2'] = '%' . $search . '%';
}
if ($jurisdiction > 0) { $where[] = 'c.jurisdiction_id = :jid'; $params[':jid'] = $jurisdiction; }

$memberCountSql = "(SELECT COUNT(*) FROM committee_members cm WHERE cm.committee_id = c.committee_id AND cm.status = 'Active')";
if ($membersFil === 'none') { $where[] = "$memberCountSql = 0"; }
elseif ($membersFil === '1-5') { $where[] = "$memberCountSql BETWEEN 1 AND 5"; }
elseif ($membersFil === '6+') { $where[] = "$memberCountSql >= 6"; }

if (isCommitteeMember()) {
  $where[] = 'c.committee_id IN (SELECT committee_id FROM committee_members WHERE user_id = :member_uid AND status = \'Active\')';
  $params[':member_uid'] = currentUserId();
  $rowParams[':member_role_uid'] = currentUserId();
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
$rowParams += $params;

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM committees c $whereSql");
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();
$pageInfo = paginate($totalRows);

$sql = "SELECT c.*,
               $memberCountSql AS member_count,
               (SELECT COUNT(*) FROM committee_members cm2 INNER JOIN workload_assignments wa ON wa.committee_member_id = cm2.committee_member_id
                WHERE cm2.committee_id = c.committee_id AND wa.status <> 'Completed') AS open_assignments"
        . (isCommitteeMember()
            ? ", (SELECT cm3.member_role FROM committee_members cm3
                 WHERE cm3.committee_id = c.committee_id AND cm3.user_id = :member_role_uid AND cm3.status = 'Active'
                 LIMIT 1) AS my_committee_role"
            : '') . "
        FROM committees c
        $whereSql
        ORDER BY $sortBy $sortDir
        LIMIT {$pageInfo['perPage']} OFFSET {$pageInfo['offset']}";
$stmt = $pdo->prepare($sql);
$stmt->execute($rowParams);
$rows = $stmt->fetchAll();

$statusColors = ['Active' => 'success', 'Inactive' => 'secondary', 'Dissolved' => 'danger'];
?>
<?php if (empty($rows)): ?>
  <div class="committee-empty-state text-center text-muted py-5"><?= isCommitteeMember() ? 'You are not currently assigned to any active committees.' : 'No committees found.' ?></div>
<?php else: ?>
  <div class="committee-card-grid">
    <?php foreach ($rows as $r): ?>
      <article class="committee-card committee-row" data-href="view.php?id=<?= (int)$r['committee_id'] ?>" data-id="<?= (int)$r['committee_id'] ?>" tabindex="0">
        <div class="committee-card-details">
          <div class="committee-card-top">
            <span class="committee-card-status status-<?= e(strtolower($r['status'])) ?>"><?= e($r['status']) ?></span>
            <?php if (isCommitteeMember() && !empty($r['my_committee_role'])): ?>
              <span class="committee-member-role"><?= e($r['my_committee_role']) ?> assignment</span>
            <?php endif; ?>
          </div>
          <a href="view.php?id=<?= (int)$r['committee_id'] ?>" class="committee-card-title">
            <?= e($r['committee_name']) ?>
          </a>
          <div class="committee-card-description">
            <?= e($r['description'] ? truncate($r['description'], 110) : 'No description provided.') ?>
          </div>
          <div class="committee-card-stats">
            <span title="Active members"><i class="bi bi-people"></i> <?= (int)$r['member_count'] ?></span>
            <span title="Open assignments" class="<?= (int)$r['open_assignments'] > 0 ? 'cc-has-open' : '' ?>"><i class="bi bi-clipboard-check"></i> <?= (int)$r['open_assignments'] ?> open</span>
            <span class="ms-auto" title="Date created"><i class="bi bi-calendar3"></i> <?= formatDate($r['date_created']) ?></span>
          </div>
          <?php if (canManage()): ?>
            <div class="committee-actions">
              <button type="button" class="committee-action-button btn-edit-committee" data-id="<?= (int)$r['committee_id'] ?>" title="Edit committee" aria-label="Edit committee">
                <i class="bi bi-pencil-square"></i><span>Edit</span>
              </button>
              <button type="button" class="committee-action-button committee-action-danger" data-confirm-delete="committee &quot;<?= e($r['committee_name']) ?>&quot;" data-delete-url="<?= e(APP_URL) ?>/modules/committees/ajax_delete.php?id=<?= (int)$r['committee_id'] ?>" title="Delete committee" aria-label="Delete committee">
                <i class="bi bi-trash"></i><span>Delete</span>
              </button>
            </div>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<div class="card-footer bg-white py-3">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
    <small class="text-muted">Showing <?= count($rows) ?> of <?= $pageInfo['total'] ?> committee(s)</small>
    <?= renderPagination($pageInfo, 'index.php') ?>
  </div>
</div>
