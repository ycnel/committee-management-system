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
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/messages.css">

<nav class="topnav" aria-label="Top navigation">
  <button type="button" class="mobile-sidebar-toggle" id="mobileSidebarToggle" aria-label="Toggle sidebar menu" aria-expanded="false">
    <i class="bi bi-list"></i>
  </button>

  <span class="topnav-title"><?= e($pageTitle ?? 'CMAS') ?></span>

  <div class="topbar-spacer"></div>

  <div class="topbar-actions">
    <button type="button" class="topbar-icon-btn position-relative" id="messagePanelToggle" aria-expanded="false" aria-haspopup="true" aria-controls="messagePanel" aria-label="Messages">
      <i class="bi bi-chat-dots"></i>
      <span data-message-badge class="message-unread-badge position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none"></span>
    </button>

    <div class="dropdown notification-dropdown">
      <button type="button" class="topbar-icon-btn position-relative" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications">
        <i class="bi bi-bell"></i>
        <?php if ($notifCount > 0): ?>
          <span data-notification-badge class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
            <?= $notifCount > 99 ? '99+' : $notifCount ?>
          </span>
        <?php else: ?>
          <span data-notification-badge class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none"></span>
        <?php endif; ?>
      </button>
      <div class="dropdown-menu dropdown-menu-end notification-menu" data-notification-read-url="<?= e(APP_URL) ?>/pages/ajax_notification_read.php">
        <div class="notification-menu-header">
          <div>
            <h6 class="mb-0">Notifications</h6>
            <span class="notification-menu-count"><?= $notifCount ?> unread</span>
          </div>
          <?php if ($notifCount > 0): ?>
            <button type="button" class="btn btn-link btn-sm p-0" data-mark-all-notifications>Mark all as read</button>
          <?php endif; ?>
        </div>
        <?php if (empty($notifItems)): ?>
          <div class="notification-empty">
            <i class="bi bi-bell-slash" aria-hidden="true"></i>
            <span>You're all caught up — nothing new right now.</span>
          </div>
        <?php else: ?>
          <div class="notification-list">
            <?php foreach ($notifItems as $item): ?>
              <a class="notification-item<?= $item['read_at'] === null ? ' is-unread' : '' ?>"
                 data-notification-id="<?= (int)$item['notification_id'] ?>"
                 href="<?= e($item['url'] ?: '#') ?>"
                 title="<?= e($item['message']) ?>">
                <span class="notification-item-icon" aria-hidden="true"><i class="bi bi-bell"></i></span>
                <span class="notification-item-content">
                  <span class="notification-item-message"><?= e($item['message']) ?></span>
                  <time class="notification-item-time" datetime="<?= e(date(DATE_ATOM, strtotime($item['created_at']))) ?>">
                    <?= e(date('M j, Y · g:i A', strtotime($item['created_at']))) ?>
                  </time>
                </span>
                <?php if ($item['read_at'] === null): ?><span class="notification-unread-dot" aria-label="Unread"></span><?php endif; ?>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
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
</nav>

<!-- Floating "Messages" panel (committee group chat) — Facebook Messenger style.
     Lives outside <nav> so it floats over the page instead of scrolling with
     the topbar; assets/js/messages.js drives everything inside it. -->
<div class="message-panel" id="messagePanel" role="dialog" aria-label="Messages" aria-hidden="true">
  <div class="message-panel-header">
    <button type="button" class="message-panel-icon-btn d-none" id="messagePanelBack" aria-label="Back to conversations">
      <i class="bi bi-arrow-left"></i>
    </button>
    <span class="message-panel-header-title" id="messagePanelTitle">Messages</span>
    <button type="button" class="message-panel-icon-btn" id="messagePanelClose" aria-label="Close messages">
      <i class="bi bi-x-lg"></i>
    </button>
  </div>
  <div class="message-panel-body" id="messagePanelBody"></div>
</div>

<div class="auth-loading" id="authLoading" role="statcodeus" aria-live="polite" aria-hidden="true">
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
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const btn = document.getElementById('mobileSidebarToggle');
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebarOverlay');
  const mainContent = document.querySelector('.main-content');
  if (btn && sidebar) {
    const icon = btn.querySelector('i');
    btn.addEventListener('click', function () {
      if (window.innerWidth <= 991.98) {
        const open = sidebar.classList.toggle('active');
        if (overlay) overlay.classList.toggle('active', open);
        document.body.style.overflow = open ? 'hidden' : '';
        if (icon) icon.className = open ? 'bi bi-x-lg' : 'bi bi-list';
        btn.setAttribute('aria-expanded', String(open));
      } else {
        const collapsed = sidebar.classList.toggle('collapsed');
        if (mainContent) mainContent.classList.toggle('sidebar-collapsed', collapsed);
        btn.setAttribute('aria-expanded', String(!collapsed));
        void mainContent.offsetWidth;
      }
    });
  }

  const notificationMenu = document.querySelector('[data-notification-read-url]');
  const notificationBadge = document.querySelector('[data-notification-badge]');
  const csrfToken = window.APP_CSRF_TOKEN || '';

  function updateNotificationBadge(unreadCount) {
    if (!notificationBadge) return;
    if (unreadCount > 0) {
      notificationBadge.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
      notificationBadge.classList.remove('d-none');
    } else {
      notificationBadge.textContent = '';
      notificationBadge.classList.add('d-none');
      const markAllButton = document.querySelector('[data-mark-all-notifications]');
      if (markAllButton) markAllButton.remove();
    }
  }

  function markNotificationRead(notificationId, item) {
    if (!notificationMenu || !notificationId) return Promise.resolve();
    return fetch(notificationMenu.dataset.notificationReadUrl, {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/x-www-form-urlencoded'
      },
      body: 'csrf_token=' + encodeURIComponent(csrfToken)
        + '&notification_id=' + encodeURIComponent(notificationId)
    })
      .then(function (response) { return response.json(); })
      .then(function (data) {
        if (!data.success) return;
        if (item) {
          item.classList.remove('is-unread');
          const unreadDot = item.querySelector('.notification-unread-dot');
          if (unreadDot) unreadDot.remove();
        }
        const unreadCountLabel = notificationMenu.querySelector('.notification-menu-count');
        if (unreadCountLabel) unreadCountLabel.textContent = Number(data.unread_count || 0) + ' unread';
        updateNotificationBadge(Number(data.unread_count || 0));
      });
  }

  if (notificationMenu) {
    notificationMenu.addEventListener('click', function (event) {
      const item = event.target.closest('[data-notification-id]');
      if (!item) return;
      const targetUrl = item.href;
      if (!targetUrl || targetUrl.endsWith('#')) return;
      event.preventDefault();
      markNotificationRead(item.dataset.notificationId, item)
        .catch(function () {})
        .then(function () { window.location.href = targetUrl; });
    });
  }

  const markAllButton = document.querySelector('[data-mark-all-notifications]');
  if (markAllButton && notificationMenu) {
    markAllButton.addEventListener('click', function (event) {
      event.preventDefault();
      fetch(notificationMenu.dataset.notificationReadUrl, {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'csrf_token=' + encodeURIComponent(csrfToken) + '&mark_all=1'
      })
        .then(function (response) { return response.json(); })
        .then(function (data) {
          if (!data.success) return;
          notificationMenu.querySelectorAll('[data-notification-id]').forEach(function (item) {
            item.classList.remove('is-unread');
            const unreadDot = item.querySelector('.notification-unread-dot');
            if (unreadDot) unreadDot.remove();
          });
          markAllButton.remove();
          const unreadCountLabel = notificationMenu.querySelector('.notification-menu-count');
          if (unreadCountLabel) unreadCountLabel.textContent = '0 unread';
          updateNotificationBadge(Number(data.unread_count || 0));
        })
        .catch(function () {});
    });
  }
});
</script>
<script src="<?= e(APP_URL) ?>/assets/js/auth-loading.js"></script>
<!-- Messages panel logic (assets/js/messages.js) calls window.APP_URL /
     window.APP_CSRF_TOKEN and the appGet()/appPost() helpers, all defined
     by assets/js/app.js in layouts/footer.php further down the page. That's
     fine: messages.js only reads them inside its DOMContentLoaded handler,
     which runs after every script tag on the page (including footer.php's)
     has already executed, regardless of tag order in the HTML. -->
<script src="<?= e(APP_URL) ?>/assets/js/messages.js"></script>
