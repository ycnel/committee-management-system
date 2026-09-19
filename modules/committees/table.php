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
$statusFil    = clean($_GET['status'] ?? '');

$sortableColumns = ['committee_name', 'status', 'date_created', 'member_count'];
$sortBy  = in_array($_GET['sort'] ?? '', $sortableColumns, true) ? $_GET['sort'] : 'committee_name';
$sortDir = strtolower($_GET['dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';

$where = [];
$params = [];
if ($search !== '') {
    $where[] = '(c.committee_name LIKE :s1 OR c.description LIKE :s2)';
    $params[':s1'] = $params[':s2'] = '%' . $search . '%';
}
if ($jurisdiction > 0) { $where[] = 'c.jurisdiction_id = :jid'; $params[':jid'] = $jurisdiction; }
if ($statusFil !== '') { $where[] = 'c.status = :status'; $params[':status'] = $statusFil; }
if (isCommitteeMember()) {
  $where[] = 'c.committee_id IN (SELECT committee_id FROM committee_members WHERE user_id = :member_uid AND status = \'Active\')';
  $params[':member_uid'] = currentUserId();
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM committees c $whereSql");
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();
$pageInfo = paginate($totalRows);

$sql = "SELECT c.*, j.jurisdiction_name,
               (SELECT COUNT(*) FROM committee_members cm WHERE cm.committee_id = c.committee_id AND cm.status = 'Active') AS member_count
        FROM committees c
        LEFT JOIN jurisdictions j ON j.jurisdiction_id = c.jurisdiction_id
        $whereSql
        ORDER BY $sortBy $sortDir
        LIMIT {$pageInfo['perPage']} OFFSET {$pageInfo['offset']}";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$statusColors = ['Active' => 'success', 'Inactive' => 'secondary', 'Dissolved' => 'danger'];
?>
<div class="committee-list-toolbar">
  <span class="small text-muted">Sort committees by:</span>
  <a href="#" class="sort-link" data-sort="committee_name">Name <i class="bi bi-arrow-down-up sort-icon"></i></a>
  <a href="#" class="sort-link" data-sort="member_count">Members <i class="bi bi-arrow-down-up sort-icon"></i></a>
  <a href="#" class="sort-link" data-sort="status">Status <i class="bi bi-arrow-down-up sort-icon"></i></a>
  <a href="#" class="sort-link" data-sort="date_created">Date created <i class="bi bi-arrow-down-up sort-icon"></i></a>
</div>

<?php if (empty($rows)): ?>
  <div class="committee-empty-state text-center text-muted py-5">No committees found.</div>
<?php else: ?>
  <div class="committee-card-grid">
    <?php foreach ($rows as $r): ?>
      <article class="committee-card committee-row" data-href="view.php?id=<?= (int)$r['committee_id'] ?>" tabindex="0">
        <div class="committee-card-details">
          <div class="committee-card-meta">
            <span><?= e($r['jurisdiction_name'] ?: 'Unassigned jurisdiction') ?></span>
            <span><?= (int)$r['member_count'] ?> member<?= (int)$r['member_count'] === 1 ? '' : 's' ?></span>
          </div>
          <a href="view.php?id=<?= (int)$r['committee_id'] ?>" class="committee-card-title">
            <?= e($r['committee_name']) ?>
          </a>
          <div class="committee-card-description">
            <?= e($r['description'] ? truncate($r['description'], 110) : 'No description provided.') ?>
          </div>
          <div class="committee-card-footer">
            <span class="committee-card-date"><i class="bi bi-calendar3"></i> <?= formatDate($r['date_created']) ?></span>
            <span class="committee-card-status status-<?= e(strtolower($r['status'])) ?>"><?= e($r['status']) ?></span>
          </div>
          <?php if (canManage()): ?>
            <div class="committee-actions">
              <button type="button" class="committee-action-button btn-edit-committee" data-id="<?= (int)$r['committee_id'] ?>" title="Edit committee">
                <i class="bi bi-pencil-square"></i><span>Edit</span>
              </button>
              <button type="button" class="committee-action-button" data-confirm-delete="committee &quot;<?= e($r['committee_name']) ?>&quot;" data-delete-url="<?= e(APP_URL) ?>/modules/committees/ajax_delete.php?id=<?= (int)$r['committee_id'] ?>" title="Delete committee">
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
