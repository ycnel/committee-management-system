<?php
/**
 * pages/users.php
 * ------------------------------------------------------------------
 * Admin-only User Management: create/edit/delete accounts and
 * assign roles. Committee members are drawn from this table, so
 * this page is how new committee members get into the system.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../includes/auth.php';
requireRole([ROLE_ADMIN]);

$pageTitle  = 'User Management';
$activeMenu = 'users';
$pdo = db();

$roles = $pdo->query('SELECT id, name FROM roles ORDER BY id')->fetchAll();

include __DIR__ . '/../layouts/header.php';
?>
<div class="app-wrapper">
  <?php include __DIR__ . '/../layouts/sidebar.php'; ?>

  <div class="main-content">
  <?php include __DIR__ . '/../layouts/content-topbar.php'; ?>
  <div class="breadcrumb-bar d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <h5 class="mb-0"><i class="bi bi-person-gear text-primary"></i> User Management</h5>
      <small class="text-muted">Create accounts and assign roles. Committee Member accounts can then be assigned to committees.</small>
    </div>
    <button type="button" class="btn btn-primary btn-sm" id="btnAddUser">
      <i class="bi bi-person-plus"></i> New User
    </button>
  </div>

  <div class="card mb-3">
    <div class="card-body py-3">
      <form id="filterForm" class="row g-2 align-items-center">
        <div class="col-md-5">
          <input type="text" class="form-control form-control-sm" id="searchInput" name="search" placeholder="Search name or email...">
        </div>
        <div class="col-md-3">
          <select class="form-select form-select-sm" name="role_id">
            <option value="">All Roles</option>
            <?php foreach ($roles as $r): ?>
              <option value="<?= (int)$r['id'] ?>"><?= e($r['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <select class="form-select form-select-sm" name="status">
            <option value="">All Statuses</option>
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
          </select>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div id="usersTableWrap">
      <?php include __DIR__ . '/users_table.php'; ?>
    </div>
  </div>

<div class="modal fade" id="userModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="userForm">
        <?= csrfField() ?>
        <input type="hidden" name="id" id="us_id" value="0">
        <div class="modal-header">
          <h5 class="modal-title" id="userModalTitle"><i class="bi bi-person-plus"></i> New User</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Full Name <span class="text-danger">*</span></label>
            <input type="text" name="full_name" id="us_full_name" class="form-control" required maxlength="150">
          </div>
          <div class="mb-3">
            <label class="form-label">Email <span class="text-danger">*</span></label>
            <input type="email" name="email" id="us_email" class="form-control" required maxlength="255">
          </div>
          <div class="mb-3" id="us_password_wrap">
            <label class="form-label">Password <span class="text-danger">*</span></label>
            <input type="password" name="password" id="us_password" class="form-control" minlength="8">
            <div class="form-text">Leave blank when editing to keep the current password.</div>
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Role <span class="text-danger">*</span></label>
              <select name="role_id" id="us_role" class="form-select" required>
                <?php foreach ($roles as $r): ?>
                  <option value="<?= (int)$r['id'] ?>"><?= e($r['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Status</label>
              <select name="status" id="us_status" class="form-select">
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Save User</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
$extraJs = [APP_URL . '/assets/js/users.js'];
include __DIR__ . '/../layouts/footer.php';
?>
