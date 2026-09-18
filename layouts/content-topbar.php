<?php
/**
 * layouts/content-topbar.php
 * ------------------------------------------------------------------
 * Replaces the old shared top navbar (removed from layouts/header.php
 * in the Linear-style redesign). Included by every protected page,
 * right after opening .main-content, so account/notification access
 * and the mobile sidebar toggle are never lost on any page.
 *
 * Reuses $user / $notifItems / $notifCount, already computed by
 * layouts/header.php (included earlier on every page) — this file
 * does not requery anything itself.
 * ------------------------------------------------------------------
 */
?>
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/auth-loading.css">

<div class="content-topbar">
  <button type="button" class="mobile-sidebar-toggle d-lg-none" id="mobileSidebarToggle" aria-label="Open menu">
    <i class="bi bi-list"></i>
  </button>

  <div class="topbar-spacer"></div>

  <div class="topbar-actions">
    <div class="dropdown">
      <button type="button" class="topbar-icon-btn position-relative" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications">
        <i class="bi bi-bell"></i>
        <?php if ($notifCount > 0): ?>
          <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
            <?= $notifCount > 99 ? '99+' : $notifCount ?>
          </span>
        <?php endif; ?>
      </button>
      <div class="dropdown-menu dropdown-menu-end p-2" style="min-width:320px;max-height:380px;overflow-y:auto;">
        <h6 class="dropdown-header">Notifications</h6>
        <?php if (empty($notifItems)): ?>
          <span class="dropdown-item-text small text-muted">You're all caught up — nothing new right now.</span>
        <?php endif; ?>
        <?php foreach ($notifItems as $item): ?>
          <a class="dropdown-item small" href="<?= e($item['url']) ?>"><?= e($item['text']) ?></a>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="dropdown">
      <button type="button" class="topbar-profile-btn" data-bs-toggle="dropdown" aria-expanded="false">
        <span class="avatar-circle sm"><?= e(userInitials($user['full_name'])) ?></span>
        <span class="d-none d-md-inline topbar-username"><?= e($user['full_name']) ?></span>
        <i class="bi bi-caret-down-fill small"></i>
      </button>
      <ul class="dropdown-menu dropdown-menu-end">
        <li><span class="dropdown-item-text small text-muted"><?= e($user['role_name']) ?></span></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item" href="<?= e(APP_URL) ?>/pages/profile.php"><i class="bi bi-person"></i> Profile</a></li>
        <li><a class="dropdown-item" href="<?= e(APP_URL) ?>/logout.php" data-auth-loading-link><i class="bi bi-box-arrow-right"></i> Logout</a></li>
      </ul>
    </div>
  </div>
</div>

<div class="auth-loading" id="authLoading" role="status" aria-live="polite" aria-hidden="true">
  <div class="auth-loading-content">
    <img src="<?= e(APP_URL) ?>/assets/img/Ph_seal_ncr_manila.svg" alt="Manila seal" class="auth-loading-seal">
    <div class="leap-frog" aria-label="Loading">
      <div class="leap-frog__dot"></div>
      <div class="leap-frog__dot"></div>
      <div class="leap-frog__dot"></div>
    </div>
  </div>
</div>

<style>
.content-topbar {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 20px;
}
.topbar-spacer { flex: 1; }
.topbar-actions { display: flex; align-items: center; gap: 10px; }

.mobile-sidebar-toggle, .topbar-icon-btn {
  width: 38px; height: 38px; border-radius: var(--radius-sm, 8px);
  border: 1px solid var(--n-border, #E5E7EB); background: var(--n-surface, #fff);
  display: inline-flex; align-items: center; justify-content: center;
  color: var(--n-text, #111827); font-size: 17px; transition: all 0.15s ease;
}
.mobile-sidebar-toggle:hover, .topbar-icon-btn:hover { background: var(--n-bg, #F7F8FA); }

.topbar-profile-btn {
  display: flex; align-items: center; gap: 8px;
  border: 1px solid var(--n-border, #E5E7EB); background: var(--n-surface, #fff);
  border-radius: 999px; padding: 5px 12px 5px 5px; transition: all 0.15s ease;
}
.topbar-profile-btn:hover { background: var(--n-bg, #F7F8FA); }
.topbar-username { font-size: 13.5px; font-weight: 600; color: var(--n-text, #111827); }
.topbar-profile-btn .avatar-circle.sm { width: 28px; height: 28px; font-size: 11px; }

@media (max-width: 991.98px) {
  .content-topbar { margin-bottom: 16px; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const btn = document.getElementById('mobileSidebarToggle');
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebarOverlay');
  if (btn && sidebar) {
    btn.addEventListener('click', function () {
      sidebar.classList.add('active');
      if (overlay) overlay.classList.add('active');
      document.body.style.overflow = 'hidden';
    });
  }
});
</script>
<script src="<?= e(APP_URL) ?>/assets/js/auth-loading.js"></script>
