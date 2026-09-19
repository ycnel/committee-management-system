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

// Filtered CSV export of the audit trail (capped for safety)
if (($_GET['export'] ?? '') === 'csv') {
    $expStmt = $pdo->prepare(
        "SELECT l.created_at, u.full_name, l.action, ($moduleSql) AS module_name,
                ($statusSql) AS activity_status, l.details, l.ip_address, l.user_agent
         FROM activity_logs l LEFT JOIN users u ON u.id = l.user_id
         $whereSql ORDER BY l.created_at DESC LIMIT 5000"
    );
    $expStmt->execute($params);
    logActivity((int)$_SESSION['user_id'], 'Export', 'Audit Logs CSV export');
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="audit_logs_' . date('Ymd_His') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Date & Time', 'User', 'Action', 'Module', 'Description', 'Status', 'IP Address', 'User Agent']);
    foreach ($expStmt->fetchAll() as $r) {
        fputcsv($out, [$r['created_at'], $r['full_name'] ?? 'System', $r['action'], $r['module_name'], $r['details'], $r['activity_status'], $r['ip_address'], $r['user_agent']]);
    }
    fclose($out);
    exit;
}

$summaryStmt = $pdo->prepare(
  "SELECT COUNT(*) AS total_activities,
      SUM(CASE WHEN l.user_id IS NOT NULL THEN 1 ELSE 0 END) AS user_actions,
      SUM(CASE WHEN l.user_id IS NULL THEN 1 ELSE 0 END) AS system_actions,
      SUM(CASE WHEN ($statusSql) = 'Success' THEN 1 ELSE 0 END) AS success_actions,
      SUM(CASE WHEN ($statusSql) = 'Failed' THEN 1 ELSE 0 END) AS failed_actions
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

/** Condense a raw User-Agent into "Browser · OS" for the Device column. */
function activityDeviceLabel(?string $ua): array
{
  if (!$ua) return ['—', 'bi-question-circle'];
  $browser = 'Other';
  if (preg_match('/Edg(e|A|iOS)?\//', $ua))        $browser = 'Edge';
  elseif (stripos($ua, 'OPR/') !== false || stripos($ua, 'Opera') !== false) $browser = 'Opera';
  elseif (stripos($ua, 'Chrome/') !== false)       $browser = 'Chrome';
  elseif (stripos($ua, 'Firefox/') !== false)      $browser = 'Firefox';
  elseif (stripos($ua, 'Safari/') !== false)       $browser = 'Safari';
  elseif (stripos($ua, 'PowerShell') !== false || stripos($ua, 'curl') !== false) $browser = 'CLI';
  $os = 'Other OS';
  if (stripos($ua, 'Windows NT') !== false)        $os = 'Windows';
  elseif (stripos($ua, 'Android') !== false)       $os = 'Android';
  elseif (stripos($ua, 'iPhone') !== false || stripos($ua, 'iPad') !== false) $os = 'iOS';
  elseif (stripos($ua, 'Mac OS X') !== false)      $os = 'macOS';
  elseif (stripos($ua, 'Linux') !== false)         $os = 'Linux';
  $mobile = stripos($ua, 'Mobile|Android|iPhone|iPad') !== false;
  return [$browser . ' · ' . $os, $mobile ? 'bi-phone' : 'bi-display'];
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
      <form method="get" id="filterForm" class="d-flex flex-wrap align-items-center gap-2">
        <div class="input-group input-group-sm" style="max-width:230px;">
          <span class="input-group-text"><i class="bi bi-search"></i></span>
          <input type="text" class="form-control" name="search" value="<?= e($search) ?>" placeholder="Search activity...">
        </div>
        <select class="form-select form-select-sm" name="user_id" style="min-width:140px;width:auto;">
          <option value="">All Users</option>
          <?php foreach ($users as $userOption): ?><option value="<?= (int)$userOption['id'] ?>" <?= $userId === (int)$userOption['id'] ? 'selected' : '' ?>><?= e($userOption['full_name']) ?></option><?php endforeach; ?>
        </select>
        <select class="form-select form-select-sm" name="module" style="min-width:130px;width:auto;">
          <option value="">All Modules</option>
          <?php foreach (['Authentication', 'Committees', 'Jurisdictions', 'Workload', 'Users', 'Reports', 'Performance', 'System'] as $moduleOption): ?><option value="<?= e($moduleOption) ?>" <?= $module === $moduleOption ? 'selected' : '' ?>><?= e($moduleOption) ?></option><?php endforeach; ?>
        </select>
        <select class="form-select form-select-sm activity-filter-control" name="action" style="min-width:120px;width:auto;">
          <option value="">All Actions</option>
          <?php foreach ($actionOptions as $actionOption): ?><option value="<?= e($actionOption) ?>" <?= $action === $actionOption ? 'selected' : '' ?>><?= e($actionOption) ?></option><?php endforeach; ?>
        </select>
        <select class="form-select form-select-sm" name="status" style="min-width:105px;width:auto;">
          <option value="">Status</option><option value="Success" <?= $status === 'Success' ? 'selected' : '' ?>>Success</option><option value="Failed" <?= $status === 'Failed' ? 'selected' : '' ?>>Failed</option>
        </select>
        <div class="input-group input-group-sm" style="width:auto;min-width:255px;">
          <input type="date" class="form-control" name="date_from" value="<?= e($dateFrom) ?>" aria-label="Date from"><input type="date" class="form-control" name="date_to" value="<?= e($dateTo) ?>" aria-label="Date to">
        </div>
        <div class="ms-auto d-flex gap-2 activity-filter-actions">
          <a href="activity_logs.php" class="btn btn-outline-secondary btn-sm activity-filter-action activity-filter-control">Reset</a>
          <a href="activity_logs.php?<?= e(http_build_query(array_merge($_GET, ['export' => 'csv']))) ?>" class="btn btn-soft-primary btn-sm"><i class="bi bi-download"></i> Export CSV</a>
          <button type="submit" class="btn btn-primary btn-sm activity-filter-action activity-filter-control"><i class="bi bi-check2"></i> Apply</button>
        </div>
      </form>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <?php
      $summaryCards = [
        ['Total Activities', $summary['total_activities'] ?? 0, 'bi-activity'],
        ['Successful', $summary['success_actions'] ?? 0, 'bi-check-circle'],
        ['Failed', $summary['failed_actions'] ?? 0, 'bi-x-octagon'],
        ['User Actions', $summary['user_actions'] ?? 0, 'bi-person-check'],
        ['System Actions', $summary['system_actions'] ?? 0, 'bi-shield-check'],
      ];
    ?>
    <?php foreach ($summaryCards as $card): ?>
      <div class="col-6 col-md-4 col-lg"><div class="card stat-card activity-summary-card"><div class="card-body"><i class="bi <?= e($card[2]) ?> stat-icon"></i><div class="stat-value"><?= (int)$card[1] ?></div><div class="stat-label"><?= e($card[0]) ?></div></div></div></div>
    <?php endforeach; ?>
  </div>

  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead><tr><th>Date &amp; Time</th><th>User</th><th>Action</th><th>Module</th><th>Description</th><th>Status</th><th>Device</th><th class="text-end">IP Address</th></tr></thead>
        <tbody>
          <?php if (empty($logs)): ?>
            <tr><td colspan="8" class="text-center text-muted py-4">No activity recorded yet.</td></tr>
          <?php else: foreach ($logs as $l): ?>
            <?php
              $displayAction = activityActionLabel($l['action'], $l['details'] ?? '');
              [$deviceLabel, $deviceIcon] = activityDeviceLabel($l['user_agent'] ?? null);
            ?>
            <tr>
              <td class="small text-muted" title="<?= e($l['created_at']) ?>"><?= e(formatDateTime($l['created_at'])) ?></td>
              <td><?= e($l['full_name'] ?? 'System') ?></td>
              <td>
                <span class="badge bg-<?= e($actionColors[$displayAction] ?? 'secondary') ?>"><?= e($displayAction) ?></span>
                <?php if ($displayAction !== $l['action']): ?><span class="text-muted small ms-1"><?= e($l['action']) ?></span><?php endif; ?>
              </td>
              <td><span class="badge bg-light text-dark border"><?= e($l['module_name']) ?></span></td>
              <td class="small"><?= e($l['details'] ?? '') ?></td>
              <td><span class="badge bg-<?= $l['activity_status'] === 'Failed' ? 'danger' : 'success' ?>"><?= e($l['activity_status']) ?></span></td>
              <td class="small text-muted" title="<?= e($l['user_agent'] ?? '') ?>"><i class="bi <?= e($deviceIcon) ?>"></i> <?= e($deviceLabel) ?></td>
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
