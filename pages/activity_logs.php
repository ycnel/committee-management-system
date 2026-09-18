<?php
/**
 * pages/activity_logs.php
 * ------------------------------------------------------------------
 * Admin-only audit trail of Insert/Update/Delete/Export/Login
 * actions recorded via includes/activity_log.php::logActivity().
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../includes/auth.php';
requireRole([ROLE_ADMIN, ROLE_COMMITTEE]);

$pageTitle  = 'Activity Logs';
$activeMenu = 'activity_logs';
$pdo = db();

$search = clean($_GET['search'] ?? '');
$action = clean($_GET['action'] ?? '');

$where = [];
$params = [];
if ($search !== '') {
    $where[] = '(u.full_name LIKE :s1 OR l.details LIKE :s2)';
    $params[':s1'] = $params[':s2'] = '%' . $search . '%';
}
if ($action !== '') { $where[] = 'l.action = :action'; $params[':action'] = $action; }
if (isCommitteeMember()) {
  $where[] = 'l.user_id = :current_user_id';
  $params[':current_user_id'] = currentUserId();
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM activity_logs l LEFT JOIN users u ON u.id = l.user_id $whereSql");
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();
$pageInfo = paginate($totalRows, 25);

$sql = "SELECT l.*, u.full_name FROM activity_logs l
        LEFT JOIN users u ON u.id = l.user_id
        $whereSql
        ORDER BY l.created_at DESC
        LIMIT {$pageInfo['perPage']} OFFSET {$pageInfo['offset']}";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

$actionOptionsStmt = $pdo->prepare(
  'SELECT DISTINCT action FROM activity_logs WHERE user_id = :uid OR :is_admin = 1 ORDER BY action'
);
$actionOptionsStmt->execute([':uid' => currentUserId(), ':is_admin' => isAdmin() ? 1 : 0]);
$actionOptions = $actionOptionsStmt->fetchAll(PDO::FETCH_COLUMN);
$actionColors = ['Insert' => 'success', 'Update' => 'info', 'Delete' => 'danger', 'Export' => 'primary', 'Login' => 'secondary', 'Logout' => 'secondary', 'Login Failed' => 'danger'];

include __DIR__ . '/../layouts/header.php';
?>
<div class="app-wrapper">
  <?php include __DIR__ . '/../layouts/sidebar.php'; ?>

  <div class="main-content">
  <?php include __DIR__ . '/../layouts/content-topbar.php'; ?>
  <div class="breadcrumb-bar d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <h5 class="mb-0"><i class="bi bi-clock-history text-primary"></i> Activity Logs</h5>
      <small class="text-muted">Audit trail of account and record changes across the system.</small>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-body py-3">
      <form method="get" class="row g-2 align-items-center">
        <div class="col-md-6">
          <input type="text" class="form-control form-control-sm" name="search" value="<?= e($search) ?>" placeholder="Search user or details...">
        </div>
        <div class="col-md-3">
          <select class="form-select form-select-sm" name="action" onchange="this.form.submit()">
            <option value="">All Actions</option>
            <?php foreach ($actionOptions as $a): ?>
              <option value="<?= e($a) ?>" <?= $action === $a ? 'selected' : '' ?>><?= e($a) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <button type="submit" class="btn btn-outline-primary btn-sm w-100"><i class="bi bi-search"></i> Filter</button>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead><tr><th><?= isAdmin() ? 'User' : 'Activity' ?></th><th>Action</th><th>Details</th><th>When</th><?php if (isAdmin()): ?><th>IP / Session</th><?php endif; ?></tr></thead>
        <tbody>
          <?php if (empty($logs)): ?>
            <tr><td colspan="<?= isAdmin() ? 5 : 4 ?>" class="text-center text-muted py-4">No activity recorded yet.</td></tr>
          <?php else: foreach ($logs as $l): ?>
            <tr>
              <td><?= isAdmin() ? ($l['full_name'] ? e($l['full_name']) : '<span class="text-muted">System</span>') : '<span class="text-muted">My activity</span>' ?></td>
              <td><span class="badge bg-<?= $actionColors[$l['action']] ?? 'secondary' ?>"><?= e($l['action']) ?></span></td>
              <td class="small"><?= e($l['details']) ?></td>
              <td class="small text-muted"><?= formatDateTime($l['created_at']) ?></td>
              <?php if (isAdmin()): ?><td class="small text-muted"><?= e($l['ip_address'] ?? '') ?><?php if (!empty($l['user_agent'])): ?><br><?= e(truncate($l['user_agent'], 45)) ?><?php endif; ?><?php if (!empty($l['session_duration_seconds'])): ?><br>Session: <?= (int)$l['session_duration_seconds'] ?>s<?php endif; ?></td><?php endif; ?>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
    <div class="card-footer bg-white py-3">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <small class="text-muted">Showing <?= count($logs) ?> of <?= $pageInfo['total'] ?> entr(y/ies)</small>
        <?= renderPagination($pageInfo, 'activity_logs.php') ?>
      </div>
    </div>
  </div>

<?php
include __DIR__ . '/../layouts/footer.php';
?>
