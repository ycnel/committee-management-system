<?php
/**
 * pages/users_table.php
 * ------------------------------------------------------------------
 * Filtered/sorted/paginated users table.
 * ------------------------------------------------------------------
 */

$pdo = db();

$search   = clean($_GET['search'] ?? '');
$roleId   = (int)($_GET['role_id'] ?? 0);
$statusFil = clean($_GET['status'] ?? '');

$sortableColumns = ['full_name', 'email', 'status'];
$sortBy  = in_array($_GET['sort'] ?? '', $sortableColumns, true) ? $_GET['sort'] : 'full_name';
$sortDir = strtolower($_GET['dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';

$where = [];
$params = [];
if ($search !== '') { $where[] = '(u.full_name LIKE :s1 OR u.email LIKE :s2)'; $params[':s1'] = $params[':s2'] = '%' . $search . '%'; }
if ($roleId > 0) { $where[] = 'u.role_id = :rid'; $params[':rid'] = $roleId; }
if ($statusFil !== '') { $where[] = 'u.status = :status'; $params[':status'] = $statusFil; }
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM users u $whereSql");
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();
$pageInfo = paginate($totalRows);

$sql = "SELECT u.id, u.full_name, u.email, u.status, r.name AS role_name
        FROM users u
        INNER JOIN roles r ON r.id = u.role_id
        $whereSql
        ORDER BY u.$sortBy $sortDir
        LIMIT {$pageInfo['perPage']} OFFSET {$pageInfo['offset']}";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
?>
<div class="table-responsive">
  <table class="table table-hover align-middle mb-0">
    <thead>
      <tr>
        <th><a href="#" class="sort-link" data-sort="full_name">Name <i class="bi bi-arrow-down-up sort-icon"></i></a></th>
        <th><a href="#" class="sort-link" data-sort="email">Email <i class="bi bi-arrow-down-up sort-icon"></i></a></th>
        <th>Role</th>
        <th><a href="#" class="sort-link" data-sort="status">Status <i class="bi bi-arrow-down-up sort-icon"></i></a></th>
        <th class="text-end">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="5" class="text-center text-muted py-4">No users found.</td></tr>
      <?php else: foreach ($rows as $r): ?>
        <tr>
          <td class="fw-semibold"><?= e($r['full_name']) ?></td>
          <td><?= e($r['email']) ?></td>
          <td><span class="badge bg-light text-dark border"><?= e($r['role_name']) ?></span></td>
          <td><span class="badge bg-<?= $r['status'] === 'Active' ? 'success' : 'secondary' ?>"><?= e($r['status']) ?></span></td>
          <td class="text-end">
            <button type="button" class="btn btn-sm btn-outline-primary btn-edit-user" data-id="<?= (int)$r['id'] ?>" title="Edit">
              <i class="bi bi-pencil-square"></i>
            </button>
            <?php if ((int)$r['id'] !== currentUserId()): ?>
              <button type="button" class="btn btn-sm btn-outline-danger"
                      data-confirm-delete="user &quot;<?= e($r['full_name']) ?>&quot;"
                      data-delete-url="<?= e(APP_URL) ?>/pages/ajax_user_delete.php?id=<?= (int)$r['id'] ?>"
                      title="Delete">
                <i class="bi bi-trash"></i>
              </button>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
<div class="card-footer bg-white py-3">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
    <small class="text-muted">Showing <?= count($rows) ?> of <?= $pageInfo['total'] ?> user(s)</small>
    <?= renderPagination($pageInfo, 'users.php') ?>
  </div>
</div>
