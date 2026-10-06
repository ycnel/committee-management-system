<?php
/**
 * modules/system/index.php
 * ------------------------------------------------------------------
 * System Overview — Super Admin's landing page for SYSTEM AUTHORITY
 * concerns (role-hierarchy revision §2/§21): system health/config,
 * security posture, and administrator-level accounts. Deliberately
 * read-only; mutation lives in backup.php (Backup & Restore) and the
 * existing pages/users.php (User Management) / ai_settings.php.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../../includes/auth.php';
requireSuperAdmin();

$pdo = db();

// ---- Database size / table count -----------------------------------
$dbStats = ['size_mb' => 0.0, 'table_count' => 0];
try {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) AS table_count,
                ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
         FROM information_schema.tables WHERE table_schema = :db"
    );
    $stmt->execute([':db' => DB_NAME]);
    $row = $stmt->fetch();
    if ($row) {
        $dbStats['table_count'] = (int)$row['table_count'];
        $dbStats['size_mb'] = (float)($row['size_mb'] ?? 0);
    }
} catch (Throwable $e) {
    error_log('System Overview DB stats error: ' . $e->getMessage());
}

// ---- Accounts per role, with Administrator-level called out --------
$roleCounts = $pdo->query(
    "SELECT r.name, COUNT(u.id) AS total, SUM(u.status = 'Active') AS active
     FROM roles r LEFT JOIN users u ON u.role_id = r.id
     GROUP BY r.id, r.name ORDER BY r.id"
)->fetchAll();

// ---- Recent system-level activity (account/security changes only) ---
$recentSystemActivity = $pdo->query(
    "SELECT al.action, al.details, al.created_at, u.full_name
     FROM activity_logs al LEFT JOIN users u ON u.id = al.user_id
     WHERE al.action IN ('Login', 'Insert User', 'Update User', 'Delete User',
                          'AI Settings Update', 'Backup', 'Restore')
     ORDER BY al.created_at DESC LIMIT 10"
)->fetchAll();

$dbVersion = $pdo->query('SELECT VERSION()')->fetchColumn();

$pageTitle = 'System Overview';
$activeMenu = 'system_overview';
include __DIR__ . '/../../layouts/header.php';
?>
<div class="app-wrapper">
  <?php include __DIR__ . '/../../layouts/sidebar.php'; ?>

  <div class="main-content">
  <?php include __DIR__ . '/../../layouts/content-topbar.php'; ?>
  <div class="breadcrumb-bar d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <h5 class="mb-0">System Overview</h5>
      <small class="text-muted">Technical/system-health information — Super Admin only.</small>
    </div>
    <a href="<?= e(APP_URL) ?>/modules/system/backup.php" class="btn btn-dark btn-sm">
      <i class="bi bi-database-down me-1"></i> Backup &amp; Restore
    </a>
  </div>

<div class="container-fluid px-4 py-4">
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="text-muted small text-uppercase">Database Size</div>
          <div class="fs-4 fw-bold"><?= e(number_format($dbStats['size_mb'], 2)) ?> MB</div>
          <div class="text-muted small"><?= (int)$dbStats['table_count'] ?> tables · <?= e(DB_NAME) ?></div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="text-muted small text-uppercase">Database Server</div>
          <div class="fs-5 fw-bold">MySQL/MariaDB</div>
          <div class="text-muted small"><?= e((string)$dbVersion) ?></div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="text-muted small text-uppercase">PHP Runtime</div>
          <div class="fs-5 fw-bold">PHP <?= e(PHP_VERSION) ?></div>
          <div class="text-muted small"><?= e(php_sapi_name()) ?></div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="text-muted small text-uppercase">App Debug Mode</div>
          <div class="fs-5 fw-bold">
            <?php if (APP_DEBUG): ?>
              <span class="text-danger">ON</span>
            <?php else: ?>
              <span class="text-success">OFF</span>
            <?php endif; ?>
          </div>
          <div class="text-muted small"><?= APP_DEBUG ? 'Turn off before production' : 'Errors hidden from users' ?></div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-lg-6">
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white fw-semibold">Security &amp; Session Configuration</div>
        <div class="card-body p-0">
          <table class="table table-sm mb-0">
            <tbody>
              <tr><td class="text-muted">Idle timeout</td><td><?= (int)(IDLE_TIMEOUT / 60) ?> minutes</td></tr>
              <tr><td class="text-muted">Session cookie lifetime</td><td><?= (int)(SESSION_LIFETIME / 3600) ?> hours (max)</td></tr>
              <tr><td class="text-muted">Login lockout</td><td><?= LOGIN_LOCKOUT_BYPASS ? '<span class="text-danger">Bypassed</span>' : '<span class="text-success">Enforced</span>' ?></td></tr>
              <tr><td class="text-muted">OTP delivery</td><td><?= e(OTP_DELIVERY_MODE) ?><?= OTP_BYPASS ? ' <span class="text-danger">(bypassed)</span>' : '' ?></td></tr>
              <tr><td class="text-muted">App URL</td><td><code><?= e(APP_URL) ?></code></td></tr>
              <tr><td class="text-muted">Upload limit</td><td><?= (int)(MAX_UPLOAD_SIZE / 1024 / 1024) ?> MB — <?= e(implode(', ', ALLOWED_UPLOAD_EXT)) ?></td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
          <span>AI / Recommendation Engine</span>
          <a href="<?= e(APP_URL) ?>/modules/workload/ai_settings.php" class="small">Configure →</a>
        </div>
        <div class="card-body">
          <p class="text-muted small mb-0">Smart AI Settings (provider, weighting, connection test) is shared SYSTEM AUTHORITY configuration — reachable by both Administrator and Super Admin, per the role-hierarchy revision. AI remains decision-support only: it never finalizes a committee assignment on its own.</p>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
          <span>Accounts by Role</span>
          <a href="<?= e(APP_URL) ?>/pages/users.php" class="small">Manage →</a>
        </div>
        <div class="card-body p-0">
          <table class="table table-sm mb-0">
            <thead>
              <tr><th>Role</th><th class="text-end">Active</th><th class="text-end">Total</th></tr>
            </thead>
            <tbody>
              <?php foreach ($roleCounts as $rc): ?>
                <?php $isAdminLevel = in_array($rc['name'], [ROLE_ADMIN, ROLE_SUPER_ADMIN], true); ?>
                <tr<?= $isAdminLevel ? ' class="table-warning bg-opacity-25"' : '' ?>>
                  <td><?= e($rc['name']) ?><?= $isAdminLevel ? ' <span class="badge bg-secondary ms-1" title="Administrator-level — managed by Super Admin only">system</span>' : '' ?></td>
                  <td class="text-end"><?= (int)($rc['active'] ?? 0) ?></td>
                  <td class="text-end"><?= (int)$rc['total'] ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
          <span>Recent System-Level Activity</span>
          <a href="<?= e(APP_URL) ?>/pages/activity_logs.php" class="small">Full audit log →</a>
        </div>
        <div class="card-body p-0">
          <?php if (!$recentSystemActivity): ?>
            <p class="text-muted small p-3 mb-0">No account/security/backup activity recorded yet.</p>
          <?php else: ?>
            <table class="table table-sm mb-0">
              <tbody>
                <?php foreach ($recentSystemActivity as $log): ?>
                  <tr>
                    <td>
                      <div class="fw-semibold small"><?= e($log['action']) ?></div>
                      <div class="text-muted small"><?= e($log['full_name'] ?? 'System') ?> · <?= e(timeAgo($log['created_at'])) ?></div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>
