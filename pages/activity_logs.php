<?php
/**
 * pages/activity_logs.php
 * ------------------------------------------------------------------
 * Administrator-only audit trail of account and record activity.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../includes/auth.php';
requireRole([ROLE_ADMIN]);

$pageTitle  = 'Audit Logs';
$activeMenu = 'activity_logs';
$pdo = db();

$search = clean($_GET['search'] ?? '');
$userId = (int)($_GET['user_id'] ?? 0);
$module = clean($_GET['module'] ?? '');
$action = clean($_GET['action'] ?? '');
$dateFrom = clean($_GET['date_from'] ?? '');
$dateTo = clean($_GET['date_to'] ?? '');
$status = clean($_GET['status'] ?? '');

$moduleSql = "CASE
  WHEN l.action IN ('Login', 'Logout', 'Login Failed', 'OTP Generated', 'OTP Verification Failed', 'Account Locked') OR l.details LIKE 'Email:%' THEN 'Authentication'
  WHEN l.details LIKE '%jurisdiction%' THEN 'Jurisdictions'
  WHEN l.details LIKE '%committee%' THEN 'Committees'
  WHEN l.details LIKE '%task%' OR l.details LIKE '%workload%' THEN 'Workload'
  WHEN l.details LIKE '%user%' OR l.details LIKE '%password%' THEN 'Users'
  WHEN l.details LIKE '%report%' OR l.details LIKE '%Exported%' THEN 'Reports'
  WHEN l.details LIKE '%performance%' THEN 'Performance'
  ELSE 'System'
END";
$statusSql = "CASE
  WHEN l.action IN ('Login Failed', 'OTP Verification Failed', 'Account Locked') THEN 'Failed'
  ELSE 'Success'
END";
$actionSql = "CASE
  WHEN l.action IN ('Login Failed', 'OTP Verification Failed', 'Account Locked') THEN 'Failed'
  WHEN l.action = 'Insert' AND l.details LIKE '%assigned to committee%' THEN 'Assigned'
  WHEN l.action = 'Update' AND l.details LIKE '%completed%' THEN 'Completed'
  WHEN l.action = 'Insert' THEN 'Created'
  WHEN l.action = 'Update' THEN 'Updated'
  WHEN l.action = 'Delete' THEN 'Deleted'
  WHEN l.action = 'Export' THEN 'Exported'
  ELSE l.action
END";

$where = [];
$params = [];
if ($search !== '') {
    $where[] = '(u.full_name LIKE :s1 OR l.details LIKE :s2)';
    $params[':s1'] = $params[':s2'] = '%' . $search . '%';
}
if ($userId > 0) {
  $where[] = 'l.user_id = :user_id';
  $params[':user_id'] = $userId;
}
if ($module !== '') { $where[] = "($moduleSql) = :module"; $params[':module'] = $module; }
if ($action !== '') { $where[] = "($actionSql) = :action"; $params[':action'] = $action; }
if ($status !== '') { $where[] = "($statusSql) = :status"; $params[':status'] = $status; }
if ($dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) { $where[] = 'l.created_at >= :date_from'; $params[':date_from'] = $dateFrom . ' 00:00:00'; }
if ($dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) { $where[] = 'l.created_at < DATE_ADD(:date_to, INTERVAL 1 DAY)'; $params[':date_to'] = $dateTo; }
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM activity_logs l LEFT JOIN users u ON u.id = l.user_id $whereSql");
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();
$pageInfo = paginate($totalRows, 25);

$sql = "SELECT l.*, u.full_name, ($moduleSql) AS module_name, ($statusSql) AS activity_status
  FROM activity_logs l
        LEFT JOIN users u ON u.id = l.user_id
        $whereSql
        ORDER BY l.created_at DESC, l.id DESC
        LIMIT {$pageInfo['perPage']} OFFSET {$pageInfo['offset']}";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

$summaryStmt = $pdo->prepare(
  "SELECT COUNT(*) AS total_activities,
      SUM(CASE WHEN l.user_id IS NOT NULL THEN 1 ELSE 0 END) AS user_actions,
  SUM(CASE WHEN l.user_id IS NULL THEN 1 ELSE 0 END) AS system_actions
   FROM activity_logs l
   LEFT JOIN users u ON u.id = l.user_id
   $whereSql"
);
$summaryStmt->execute($params);
$summary = $summaryStmt->fetch() ?: [];

$users = $pdo->query('SELECT id, full_name FROM users ORDER BY full_name')->fetchAll();

$actionColors = ['Created' => 'success', 'Updated' => 'info', 'Deleted' => 'danger', 'Login' => 'secondary', 'Logout' => 'secondary', 'Assigned' => 'primary', 'Completed' => 'success', 'Failed' => 'danger', 'Exported' => 'primary'];
$actionOptions = ['Created', 'Updated', 'Deleted', 'Login', 'Logout', 'Assigned', 'Completed', 'Failed', 'Exported'];
function activityActionLabel(string $action, string $details): string
{
  if (in_array($action, ['Login Failed', 'OTP Verification Failed', 'Account Locked'], true)) return 'Failed';
  if ($action === 'Insert' && stripos($details, 'assigned to committee') !== false) return 'Assigned';
  if ($action === 'Update' && stripos($details, 'completed') !== false) return 'Completed';
  return ['Insert' => 'Created', 'Update' => 'Updated', 'Delete' => 'Deleted', 'Export' => 'Exported'][$action] ?? $action;
}

include __DIR__ . '/../layouts/header.php';
?>
<div class="app-wrapper">
  <?php include __DIR__ . '/../layouts/sidebar.php'; ?>

  <div class="main-content">
  <?php include __DIR__ . '/../layouts/content-topbar.php'; ?>
  <div class="breadcrumb-bar d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <h5 class="mb-0"><i class="bi bi-clock-history text-primary"></i> Audit Logs</h5>
      <small class="text-muted">System-wide audit trail of account and record activity.</small>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-body py-3">
      <form method="get" class="row g-2 align-items-center">
        <div class="col-md-3">
          <input type="text" class="form-control form-control-sm" name="search" value="<?= e($search) ?>" placeholder="Search activity...">
        </div>
        <div class="col-md-2"><select class="form-select form-select-sm" name="user_id">
          <option value="">All Users</option>
          <?php foreach ($users as $userOption): ?><option value="<?= (int)$userOption['id'] ?>" <?= $userId === (int)$userOption['id'] ? 'selected' : '' ?>><?= e($userOption['full_name']) ?></option><?php endforeach; ?>
        </select></div>
        <div class="col-md-2"><select class="form-select form-select-sm" name="module">
          <option value="">All Modules</option>
          <?php foreach (['Authentication', 'Committees', 'Jurisdictions', 'Workload', 'Users', 'Reports', 'Performance', 'System'] as $moduleOption): ?><option value="<?= e($moduleOption) ?>" <?= $module === $moduleOption ? 'selected' : '' ?>><?= e($moduleOption) ?></option><?php endforeach; ?>
        </select></div>
        <div class="col-md-2"><select class="form-select form-select-sm activity-filter-control" name="action">
            <option value="">All Actions</option>
            <?php foreach ($actionOptions as $actionOption): ?><option value="<?= e($actionOption) ?>" <?= $action === $actionOption ? 'selected' : '' ?>><?= e($actionOption) ?></option><?php endforeach; ?>
          </select></div>
        <div class="col-md-1"><select class="form-select form-select-sm" name="status">
          <option value="">Status</option><option value="Success" <?= $status === 'Success' ? 'selected' : '' ?>>Success</option><option value="Failed" <?= $status === 'Failed' ? 'selected' : '' ?>>Failed</option>
        </select></div>
        <div class="col-md-2"><div class="input-group input-group-sm"><input type="date" class="form-control" name="date_from" value="<?= e($dateFrom) ?>" aria-label="Date from"><input type="date" class="form-control" name="date_to" value="<?= e($dateTo) ?>" aria-label="Date to"></div></div>
        <div class="col-md-12 d-flex justify-content-end gap-2 activity-filter-actions"><a href="activity_logs.php" class="btn btn-outline-secondary btn-sm activity-filter-action activity-filter-control">Reset</a>
          <button type="submit" class="btn btn-primary btn-sm activity-filter-action activity-filter-control"><i class="bi bi-search"></i> Apply Filters</button>
        </div>
      </form>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <?php
      $summaryCards = [['Total Activities', $summary['total_activities'] ?? 0, 'bi-activity', 'bg-gov-blue'], ['User Actions', $summary['user_actions'] ?? 0, 'bi-person-check', 'bg-gov-teal'], ['System Actions', $summary['system_actions'] ?? 0, 'bi-shield-check', 'bg-gov-red']];
    ?>
    <?php foreach ($summaryCards as $card): ?>
      <div class="col-6 col-lg-3"><div class="card stat-card activity-summary-card"><div class="card-body"><i class="bi <?= e($card[2]) ?> stat-icon"></i><div class="stat-value"><?= (int)$card[1] ?></div><div class="stat-label"><?= e($card[0]) ?></div></div></div></div>
    <?php endforeach; ?>
  </div>

  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead><tr><th>Date &amp; Time</th><th>User</th><th>Action</th><th>Module</th><th>Description</th><th>Status</th><th class="text-end">IP Address</th></tr></thead>
        <tbody>
          <?php if (empty($logs)): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">No activity recorded yet.</td></tr>
          <?php else: foreach ($logs as $l): ?>
            <?php $displayAction = activityActionLabel($l['action'], $l['details'] ?? ''); ?>
            <tr>
              <td class="small text-muted"><?= e(formatDateTime($l['created_at'])) ?></td>
              <td><?= e($l['full_name'] ?? 'System') ?></td>
              <td><span class="badge bg-<?= e($actionColors[$displayAction] ?? 'secondary') ?>"><?= e($displayAction) ?></span></td>
              <td><span class="badge bg-light text-dark border"><?= e($l['module_name']) ?></span></td>
              <td class="small"><?= e($l['details'] ?? '') ?></td>
              <td><span class="badge bg-<?= $l['activity_status'] === 'Failed' ? 'danger' : 'success' ?>"><?= e($l['activity_status']) ?></span></td>
              <td class="text-end small text-muted"><?= e($l['ip_address'] ?? '—') ?></td>
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
