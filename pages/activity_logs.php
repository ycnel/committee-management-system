<?php
/**
 * pages/activity_logs.php
 * ------------------------------------------------------------------
 * Administrator audit trail and personal activity history for
 * Committee Chairperson and Committee Member accounts.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../includes/auth.php';
requireRole([ROLE_ADMIN, ROLE_STAFF, ROLE_COMMITTEE]);

$pageTitle  = 'Activity Logs';
$activeMenu = 'activity_logs';
$pdo = db();
$isAdministrator = isAdmin();
$isCommitteeMemberView = currentRole() === ROLE_COMMITTEE;

$search = clean($_GET['search'] ?? '');
$userId = (int)($_GET['user_id'] ?? 0);
$module = clean($_GET['module'] ?? '');
$action = clean($_GET['action'] ?? '');
$dateFrom = clean($_GET['date_from'] ?? '');
$dateTo = clean($_GET['date_to'] ?? '');
$status = clean($_GET['status'] ?? '');
if ($isCommitteeMemberView) {
  $module = '';
  $status = '';
  $dateFrom = '';
  $dateTo = '';
  if ($action !== '' && !in_array($action, ['Login', 'Logout', 'OTP Generated'], true)) {
    $action = '';
  }
}

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
if ($isAdministrator && $userId > 0) {
  $where[] = 'l.user_id = :user_id';
  $params[':user_id'] = $userId;
}
if (!$isAdministrator) {
  $where[] = 'l.user_id = :self_user_id';
  $params[':self_user_id'] = currentUserId();
  $userId = (int)currentUserId();
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

$users = $isAdministrator ? $pdo->query('SELECT id, full_name FROM users ORDER BY full_name')->fetchAll() : [];

$actionColors = ['Created' => 'success', 'Updated' => 'info', 'Deleted' => 'danger', 'Login' => 'secondary', 'Logout' => 'secondary', 'Assigned' => 'primary', 'Completed' => 'success', 'Failed' => 'danger', 'Exported' => 'primary'];
$actionOptions = $isCommitteeMemberView
  ? ['Login', 'Logout', 'OTP Generated']
  : ['Created', 'Updated', 'Deleted', 'Login', 'Logout', 'Assigned', 'Completed', 'Failed', 'Exported'];
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
      <h5 class="mb-0"><i class="bi bi-clock-history text-primary"></i> <?= $isAdministrator ? 'Activity Logs' : 'My Activity Logs' ?></h5>
      <small class="text-muted"><?= $isAdministrator ? 'System-wide audit trail of account and record activity.' : 'Your personal account and record activity history.' ?></small>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-body py-3">
      <form method="get" class="row g-2 align-items-center <?= $isCommitteeMemberView ? 'activity-member-filter-form' : '' ?>">
        <div class="col-md-3">
          <input type="text" class="form-control form-control-sm" name="search" value="<?= e($search) ?>" placeholder="Search activity...">
        </div>
        <?php if ($isAdministrator): ?><div class="col-md-2"><select class="form-select form-select-sm" name="user_id">
          <option value="">All Users</option>
          <?php foreach ($users as $userOption): ?><option value="<?= (int)$userOption['id'] ?>" <?= $userId === (int)$userOption['id'] ? 'selected' : '' ?>><?= e($userOption['full_name']) ?></option><?php endforeach; ?>
        </select></div><?php endif; ?>
        <?php if (!$isCommitteeMemberView): ?><div class="col-md-2"><select class="form-select form-select-sm" name="module">
          <option value="">All Modules</option>
          <?php foreach (['Authentication', 'Committees', 'Jurisdictions', 'Workload', 'Users', 'Reports', 'Performance', 'System'] as $moduleOption): ?><option value="<?= e($moduleOption) ?>" <?= $module === $moduleOption ? 'selected' : '' ?>><?= e($moduleOption) ?></option><?php endforeach; ?>
        </select></div><?php endif; ?>
        <div class="col-md-2"><select class="form-select form-select-sm activity-filter-control" name="action">
            <option value="">All Actions</option>
            <?php foreach ($actionOptions as $actionOption): ?><option value="<?= e($actionOption) ?>" <?= $action === $actionOption ? 'selected' : '' ?>><?= e($actionOption) ?></option><?php endforeach; ?>
          </select></div>
        <?php if (!$isCommitteeMemberView): ?><div class="col-md-1"><select class="form-select form-select-sm" name="status">
          <option value="">Status</option><option value="Success" <?= $status === 'Success' ? 'selected' : '' ?>>Success</option><option value="Failed" <?= $status === 'Failed' ? 'selected' : '' ?>>Failed</option>
        </select></div><?php endif; ?>
        <?php if (!$isCommitteeMemberView): ?><div class="col-md-2"><div class="input-group input-group-sm"><input type="date" class="form-control" name="date_from" value="<?= e($dateFrom) ?>" aria-label="Date from"><input type="date" class="form-control" name="date_to" value="<?= e($dateTo) ?>" aria-label="Date to"></div></div><?php endif; ?>
        <div class="col-md-12 d-flex justify-content-end gap-2 activity-filter-actions"><a href="activity_logs.php" class="btn btn-outline-secondary btn-sm activity-filter-action activity-filter-control">Reset</a>
          <button type="submit" class="btn btn-primary btn-sm activity-filter-action activity-filter-control"><i class="bi bi-search"></i> Apply Filters</button>
        </div>
      </form>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <?php
      $summaryCards = $isAdministrator
        ? [['Total Activities', $summary['total_activities'] ?? 0, 'bi-activity', 'bg-gov-blue'], ['User Actions', $summary['user_actions'] ?? 0, 'bi-person-check', 'bg-gov-teal'], ['System Actions', $summary['system_actions'] ?? 0, 'bi-shield-check', 'bg-gov-red']]
        : [['Total Activities', $summary['total_activities'] ?? 0, 'bi-activity', 'bg-gov-blue']];
    ?>
    <?php foreach ($summaryCards as $card): ?>
      <div class="col-6 col-lg-3"><div class="card stat-card activity-summary-card"><div class="card-body"><i class="bi <?= e($card[2]) ?> stat-icon"></i><div class="stat-value"><?= (int)$card[1] ?></div><div class="stat-label"><?= e($card[0]) ?></div></div></div></div>
    <?php endforeach; ?>
  </div>

  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead><tr><th>Date &amp; Time</th><th>User</th><th>Action</th><th>Module</th><th>Description</th><th>Status</th><th class="text-end">Details</th></tr></thead>
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
              <td class="text-end"><button type="button" class="btn btn-sm btn-outline-primary activity-details-button" data-activity='<?= e(json_encode(['user' => $l['full_name'] ?? 'System', 'action' => $displayAction, 'module' => $l['module_name'], 'timestamp' => formatDateTime($l['created_at']), 'description' => $l['details'] ?? '', 'changes' => 'No recorded changes available.'])) ?>'><i class="bi bi-eye"></i></button></td>
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

<div class="modal fade" id="activityDetailsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title"><i class="bi bi-clock-history text-primary"></i> Activity Details</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <dl class="row small mb-0">
          <dt class="col-sm-3">User</dt><dd class="col-sm-9" id="activityDetailUser"></dd>
          <dt class="col-sm-3">Action</dt><dd class="col-sm-9" id="activityDetailAction"></dd>
          <dt class="col-sm-3">Module</dt><dd class="col-sm-9" id="activityDetailModule"></dd>
          <dt class="col-sm-3">Timestamp</dt><dd class="col-sm-9" id="activityDetailTimestamp"></dd>
          <dt class="col-sm-3">Description</dt><dd class="col-sm-9" id="activityDetailDescription"></dd>
          <dt class="col-sm-3">Recorded Changes</dt><dd class="col-sm-9" id="activityDetailChanges"></dd>
        </dl>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button></div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const modalElement = document.getElementById('activityDetailsModal');
  if (!modalElement || !window.bootstrap) return;
  const modal = new bootstrap.Modal(modalElement);
  document.querySelectorAll('.activity-details-button').forEach(function (button) {
    button.addEventListener('click', function () {
      const activity = JSON.parse(button.getAttribute('data-activity') || '{}');
      document.getElementById('activityDetailUser').textContent = activity.user || 'System';
      document.getElementById('activityDetailAction').textContent = activity.action || '';
      document.getElementById('activityDetailModule').textContent = activity.module || '';
      document.getElementById('activityDetailTimestamp').textContent = activity.timestamp || '';
      document.getElementById('activityDetailDescription').textContent = activity.description || '';
      document.getElementById('activityDetailChanges').textContent = activity.changes || 'No recorded changes available.';
      modal.show();
    });
  });
});
</script>

<?php
include __DIR__ . '/../layouts/footer.php';
?>
