<?php
/**
 * pages/profile.php
 * ------------------------------------------------------------------
 * Lets the signed-in user view their account info and change their
 * own password.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$pageTitle  = 'My Profile';
$activeMenu = '';
$user = currentUser();

include __DIR__ . '/../layouts/header.php';
?>
<div class="app-wrapper">
  <?php include __DIR__ . '/../layouts/sidebar.php'; ?>

  <div class="main-content">
  <?php include __DIR__ . '/../layouts/content-topbar.php'; ?>
  <div class="breadcrumb-bar d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <h5 class="mb-0"><i class="bi bi-person-circle text-primary"></i> My Profile</h5>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-lg-5">
      <div class="card">
        <div class="card-header">Account Information</div>
        <div class="card-body">
          <dl class="row mb-0 small">
            <dt class="col-4">Full Name</dt><dd class="col-8"><?= e($user['full_name']) ?></dd>
            <dt class="col-4">Email</dt><dd class="col-8"><?= e($user['email']) ?></dd>
            <dt class="col-4">Role</dt><dd class="col-8"><span class="badge bg-primary"><?= e($user['role_name']) ?></span></dd>
          </dl>
        </div>
      </div>
    </div>

    <div class="col-lg-7">
      <div class="card">
        <div class="card-header">Change Password</div>
        <div class="card-body">
          <form id="passwordForm">
            <?= csrfField() ?>
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label">Current Password <span class="text-danger">*</span></label>
                <input type="password" name="current_password" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">New Password <span class="text-danger">*</span></label>
                <input type="password" name="new_password" id="pf_new_password" class="form-control" required minlength="8">
              </div>
              <div class="col-md-6">
                <label class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                <input type="password" name="confirm_password" class="form-control" required minlength="8">
              </div>
            </div>
            <button type="submit" class="btn btn-primary mt-3"><i class="bi bi-check-circle"></i> Update Password</button>
          </form>
        </div>
      </div>
    </div>
  </div>

<?php
$extraJs = [APP_URL . '/assets/js/profile.js'];
include __DIR__ . '/../layouts/footer.php';
?>
