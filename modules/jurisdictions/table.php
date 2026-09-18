<?php
/**
 * modules/jurisdictions/table.php
 * ------------------------------------------------------------------
 * Filtered/sorted/paginated jurisdictions table. Included by
 * index.php (initial render) and ajax_search.php (live refresh).
 * ------------------------------------------------------------------
 */

$pdo = db();

$search    = clean($_GET['search'] ?? '');
$statusFil = clean($_GET['status'] ?? '');

$sortableColumns = ['jurisdiction_name', 'category', 'status', 'committee_count'];
$sortBy  = in_array($_GET['sort'] ?? '', $sortableColumns, true) ? $_GET['sort'] : 'jurisdiction_name';
$sortDir = strtolower($_GET['dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';

$where = [];
$params = [];
if ($search !== '') {
    $where[] = '(j.jurisdiction_name LIKE :s1 OR j.category LIKE :s2 OR j.description LIKE :s3)';
    $params[':s1'] = $params[':s2'] = $params[':s3'] = '%' . $search . '%';
}
if ($statusFil !== '') { $where[] = 'j.status = :status'; $params[':status'] = $statusFil; }
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM jurisdictions j $whereSql");
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();
$pageInfo = paginate($totalRows);

$sql = "SELECT j.*, (SELECT COUNT(*) FROM committees c WHERE c.jurisdiction_id = j.jurisdiction_id) AS committee_count
        FROM jurisdictions j
        $whereSql
        ORDER BY $sortBy $sortDir
        LIMIT {$pageInfo['perPage']} OFFSET {$pageInfo['offset']}";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
?>
<div class="committee-list-toolbar jurisdiction-list-toolbar">
  <span class="small text-muted">Sort jurisdictions by:</span>
  <a href="#" class="sort-link" data-sort="jurisdiction_name">Name <i class="bi bi-arrow-down-up sort-icon"></i></a>
  <a href="#" class="sort-link" data-sort="category">Category <i class="bi bi-arrow-down-up sort-icon"></i></a>
  <a href="#" class="sort-link" data-sort="committee_count">Committees <i class="bi bi-arrow-down-up sort-icon"></i></a>
  <a href="#" class="sort-link" data-sort="status">Status <i class="bi bi-arrow-down-up sort-icon"></i></a>
</div>

<?php if (empty($rows)): ?>
  <div class="jurisdiction-empty-state text-center text-muted py-5">No jurisdictions found.</div>
<?php else: ?>
  <div class="jurisdiction-card-grid">
    <?php foreach ($rows as $r): ?>
      <article class="jurisdiction-card" data-href="index.php" tabindex="0">
        <div class="jurisdiction-card-details">
          <div class="jurisdiction-card-meta">
            <span><?= $r['category'] ? e($r['category']) : 'General scope' ?></span>
            <span><?= (int)$r['committee_count'] ?> committee<?= (int)$r['committee_count'] === 1 ? '' : 's' ?></span>
          </div>
          <div class="jurisdiction-card-title"><?= e($r['jurisdiction_name']) ?></div>
          <div class="jurisdiction-card-description">
            <?= e($r['description'] ? truncate($r['description'], 110) : 'No description provided.') ?>
          </div>
          <div class="jurisdiction-card-footer">
            <span class="jurisdiction-card-status status-<?= e(strtolower($r['status'])) ?>"><?= e($r['status']) ?></span>
            <div class="jurisdiction-actions">
              <button type="button" class="jurisdiction-action-button btn-edit-jurisdiction" data-id="<?= (int)$r['jurisdiction_id'] ?>" title="Edit jurisdiction">
                <i class="bi bi-pencil-square"></i>
              </button>
              <button type="button" class="jurisdiction-action-button"
                      data-confirm-delete="jurisdiction &quot;<?= e($r['jurisdiction_name']) ?>&quot;"
                      data-delete-url="<?= e(APP_URL) ?>/modules/jurisdictions/ajax_delete.php?id=<?= (int)$r['jurisdiction_id'] ?>"
                      title="Delete jurisdiction">
                <i class="bi bi-trash"></i>
              </button>
            </div>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<div class="card-footer bg-white py-3">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
    <small class="text-muted">Showing <?= count($rows) ?> of <?= $pageInfo['total'] ?> jurisdiction(s)</small>
    <?= renderPagination($pageInfo, 'index.php') ?>
  </div>
</div>
