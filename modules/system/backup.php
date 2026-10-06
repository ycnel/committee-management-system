<?php
/**
 * modules/system/backup.php
 * ------------------------------------------------------------------
 * Database Backup & Restore — Super Admin only (role-hierarchy
 * revision §2: "Database backup and restore" is explicitly a SYSTEM
 * AUTHORITY function, not something Administrator or any legislative
 * role gets). Backup streams a full .sql dump immediately on click
 * (ajax_backup.php). Restore is destructive, so the UI requires
 * typing a confirmation phrase before the upload is even allowed to
 * submit — see assets/js/system-backup.js.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../../includes/auth.php';
requireSuperAdmin();

$pageTitle = 'Backup & Restore';
$activeMenu = 'system_backup';
include __DIR__ . '/../../layouts/header.php';
?>
<div class="app-wrapper">
  <?php include __DIR__ . '/../../layouts/sidebar.php'; ?>

  <div class="main-content">
  <?php include __DIR__ . '/../../layouts/content-topbar.php'; ?>
  <div class="breadcrumb-bar d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <h5 class="mb-0">Backup &amp; Restore</h5>
      <small class="text-muted">Super Admin only. Restoring replaces existing data — read the warning before uploading a file.</small>
    </div>
    <a href="<?= e(APP_URL) ?>/modules/system/index.php" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-arrow-left me-1"></i> System Overview
    </a>
  </div>

  <div class="container-fluid px-4 py-4">
    <div class="row g-3">
      <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body">
            <h6 class="fw-semibold"><i class="bi bi-download me-1"></i> Backup</h6>
            <p class="text-muted small">Downloads a complete .sql dump of the <code><?= e(DB_NAME) ?></code> database — schema and data for every table — generated on the fly (no server-side file is kept). Do this before any restore, upgrade, or risky change.</p>
            <button type="button" id="btnRunBackup" class="btn btn-dark">
              <i class="bi bi-database-down me-1"></i> Download Backup Now
            </button>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100 border-danger-subtle">
          <div class="card-body">
            <h6 class="fw-semibold text-danger"><i class="bi bi-exclamation-triangle me-1"></i> Restore</h6>
            <p class="text-muted small">Uploading a .sql file <strong>replaces existing table data</strong> for every table named in the file. This cannot be undone except by restoring an earlier backup. Take a fresh backup first.</p>

            <form id="restoreForm" enctype="multipart/form-data">
              <?= csrfField() ?>
              <div class="mb-2">
                <input type="file" name="backup_file" id="restoreFile" class="form-control form-control-sm" accept=".sql" required>
              </div>
              <div class="mb-3">
                <label class="form-label small">Type <code>RESTORE</code> to confirm</label>
                <input type="text" id="restoreConfirmPhrase" class="form-control form-control-sm" autocomplete="off" placeholder="RESTORE">
              </div>
              <button type="submit" id="btnRunRestore" class="btn btn-outline-danger" disabled>
                <i class="bi bi-upload me-1"></i> Upload &amp; Restore
              </button>
            </form>
            <div id="restoreResult" class="mt-3"></div>
          </div>
        </div>
      </div>
    </div>
  </div>

<?php
$extraJs = [APP_URL . '/assets/js/system-backup.js'];
include __DIR__ . '/../../layouts/footer.php';
?>
