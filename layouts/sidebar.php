<?php
/**
 * layouts/sidebar.php
 * ------------------------------------------------------------------
 * Left sidebar navigation with Manila theme. Now the sole owner of
 * the app's branding (logo + name), since the top header has been
 * removed. When collapsed, only icons remain visible.
 * ------------------------------------------------------------------
 */

$activeMenu = $activeMenu ?? '';
$role = currentRole();

/**
 * Each nav item: key, label, icon, url, roles allowed to see it.
 *
 * Administrator gets every operational module (full feature set) plus
 * its own system-administration items. Committee Member keeps its
 * read-only subset.
 */
$menuItems = [
    ['key' => 'dashboard',     'label' => 'Dashboard',              'icon' => 'bi-speedometer2',   'url' => '/dashboard.php',
        'roles' => [ROLE_ADMIN, ROLE_STAFF, ROLE_COMMITTEE]],

    ['key' => 'committees',    'label' => 'Committee Management',   'icon' => 'bi-diagram-3',      'url' => '/modules/committees/index.php',
        'roles' => [ROLE_ADMIN, ROLE_STAFF, ROLE_COMMITTEE]],

    ['key' => 'workload',      'label' => 'Workload Distribution',  'icon' => 'bi-bar-chart-steps', 'url' => '/modules/workload/index.php',
        'roles' => [ROLE_ADMIN, ROLE_STAFF, ROLE_COMMITTEE]],

    ['key' => 'performance',   'label' => 'Committee Performance',  'icon' => 'bi-graph-up-arrow', 'url' => '/modules/performance/index.php',
        'roles' => [ROLE_ADMIN, ROLE_STAFF, ROLE_COMMITTEE]],

    ['key' => 'jurisdictions', 'label' => 'Jurisdictions',          'icon' => 'bi-scale',          'url' => '/modules/jurisdictions/index.php',
        'roles' => [ROLE_ADMIN, ROLE_STAFF]],

    ['key' => 'committee_reports', 'label' => 'Committee Reports',  'icon' => 'bi-file-earmark-text', 'url' => '/modules/committee_reports/index.php',
        'roles' => [ROLE_ADMIN, ROLE_STAFF]],

    ['key' => 'reports_analytics', 'label' => 'Reports & Analytics', 'icon' => 'bi-bar-chart-line', 'url' => '/modules/reports/index.php',
        'roles' => [ROLE_ADMIN, ROLE_STAFF]],

    ['key' => 'activity_logs', 'label' => 'Audit Logs',             'icon' => 'bi-clock-history',  'url' => '/pages/activity_logs.php',
      'roles' => [ROLE_ADMIN]],

    ['key' => 'users',         'label' => 'User Management',        'icon' => 'bi-person-gear',    'url' => '/pages/users.php',
        'roles' => [ROLE_ADMIN]],

    ['key' => 'ai_settings',   'label' => 'Smart AI Settings',      'icon' => 'bi-robot',          'url' => '/modules/workload/ai_settings.php',
        'roles' => [ROLE_ADMIN]],
];
?>

<aside class="sidebar" id="sidebar">
  <!-- Brand Header -->
  <div class="sidebar-header">
    <div class="brand-icon-wrapper">
      <img src="<?= e(APP_URL) ?>/assets/img/Ph_seal_ncr_manila.svg" alt="City of Manila Seal" class="brand-icon-img">
    </div>
    <div class="brand-text">
      <div class="brand-title">Committee <span>Management</span></div>
      <div class="brand-subtitle">& Assigment System</div>
    </div>
  </div>

  <!-- Divider -->
  <div class="sidebar-divider"></div>

  <!-- Navigation -->
  <nav class="sidebar-nav">

    <ul class="nav-list">
      <?php foreach ($menuItems as $item): ?>
        <?php if (!in_array($role, $item['roles'], true)) continue; ?>
        <li class="nav-item">
          <a class="nav-link <?= $activeMenu === $item['key'] ? 'active' : '' ?>"
             href="<?= e(APP_URL . $item['url']) ?>">
            <span class="nav-icon-wrapper">
              <?php if ($item['key'] === 'jurisdictions'): ?>
                <svg class="nav-scale-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                  <path d="M12 3v17M5 6h14M7 6 4 12h6L7 6Zm10 0-3 6h6l-3-6ZM9 20h6M4 12c0 2 1.5 3 3 3s3-1 3-3M14 12c0 2 1.5 3 3 3s3-1 3-3" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3"/>
                </svg>
              <?php else: ?>
                <i class="bi <?= e($item['icon']) ?>"></i>
              <?php endif; ?>
            </span>
            <span class="nav-label"><?= e($item['label']) ?></span>
            <?php if ($activeMenu === $item['key']): ?>
              <span class="nav-indicator"></span>
            <?php endif; ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  </nav>

  <!-- Footer Section -->
  <div class="sidebar-footer">
    <div class="footer-divider"></div>
    <div class="user-badge">
      <i class="bi bi-shield-check"></i>
      <span><?= e($role) ?></span>
    </div>
  </div>
</aside>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<style>
/* ============================================
   SIDEBAR STYLES — Linear, Dark Mode
   Uses Linear's actual dark-mode palette (void/
   carbon/graphite) rather than the light inversion —
   the sidebar stays dark while the page content
   (header.php / dashboard.php) stays light.
   ============================================ */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;510;590&display=swap');
@import url('https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap');

@import url('https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Google+Sans:ital,opsz,wght@0,17..18,400..700;1,17..18,400..700&display=swap');


:root {
    /* Surfaces — Linear's void/carbon/obsidian/graphite */
    --sb-canvas: #061A35;  
    --sb-surface: #000822;
    --sb-surface-2: rgba(40, 61, 129, 0.24);

    --sb-active-background: rgba(23, 45, 118, 0.39);
    --sb-border: #113e7d63;
    --sb-border-strong: #121c42;

    /* Text — neutral grays instead of Linear's slightly blue-tinted mist/fog/ash */
    --sb-text-heading: #ffffff;
    --sb-text-body: #d6deeb;
    --sb-text-muted: #8badc1;
    --sb-text-faint: #5f7e97;

    /* Accent — the one chromatic element, used sparingly */
    --sb-accent: #82aaff;
    --sb-accent-tint: rgba(130, 170, 255, 0.1);

    /* Kept for the brand seal ring only — not used elsewhere */
    --mn-gold: #F0B429;

    --primary-blue: #0B2E59;
    --primary-blue-dark: #08213F;     
    --primary-blue-light: #1F4E85;
    --primary-yellow: #D4AF37;
    --primary-yellow-light: #E5C767;
    --primary-white: #FFFFFF;
    --primary-gray: #F7F8FA;


}

.sidebar {
  width: 264px;
  height: 100vh;
  position: fixed;
  top: 0;
  left: 0;
  background: var(--sb-canvas);
  color: var(--sb-text-body);
  display: flex;
  flex-direction: column;
  padding: 0;
  z-index: 1000;
  transition: width 0.3s ease, transform 0.3s ease;
  border-right: 1px solid var(--sb-border);
  overflow: hidden;
  font-family: 'Inter Variable', 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
  font-feature-settings: "cv01" on, "ss03" on, "zero" on;
}

.sidebar-header {
  padding: 20px 10px;
  display: flex;
  flex-direction: row;
  align-items: center;
  text-align: left;
  gap: 10px;
  border-bottom: 1px solid var(--sb-border);
  transition: all 0.3s ease;
}

.brand-icon-wrapper {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  padding: 0px;
  perspective: 240px;
  transition: all 0.3s ease;
  overflow: hidden;
}

.brand-icon-img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  transform: rotateX(0deg) rotateY(0deg) scale(1);
  transform-style: preserve-3d;
  transition: transform 0.18s ease-out, filter 0.18s ease-out;
}
.brand-icon-wrapper:hover .brand-icon-img {
  filter: drop-shadow(0 5px 8px rgba(0, 0, 0, 0.28));
}

.brand-text {
  line-height: 1.3;
  transition: all 0.3s ease;
  overflow: visible;
  min-width: 0;
}

.brand-title {
  font-family: 'Inter Variable', 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
  font-weight: 550;
  font-size: 15px;

  color: #ffffff;
  letter-spacing: -0.3px;
  white-space: normal;
  overflow-wrap: break-word;
}
.brand-title span {
  font-weight: 550;
  font-size: 15px;
  color: var(--primary-yellow);
  letter-spacing: -0.3px;
  font-style: italic;
  white-space: nowrap;
}

.brand-subtitle {
  font-size: 14px;
  color: var(--sb-text-body);
  font-weight: 400;
  letter-spacing: -0.3px;
  
  white-space: nowrap;
}

.sidebar-divider {
  height: 0.5px;
  background: var(--sb-border);
}

.sidebar-nav {
  flex: 1;
  overflow-y: auto;
  overflow-x: hidden;
  padding: 12px 12px;
}
.sidebar-nav::-webkit-scrollbar { width: 5px; }
.sidebar-nav::-webkit-scrollbar-thumb { background: var(--sb-border-strong); border-radius: 10px; }

.nav-section-label {
  font-size: 11px;
  font-weight: 510;
  letter-spacing: 0.6px;
  text-transform: uppercase;
  white-space: normal;
  padding: 4px 10px 8px 10px;
  transition: all 0.3s ease;
}

.nav-list { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 2px; }
.nav-item { width: 100%; }

.nav-link {
  display: flex;
  align-items: center;
  padding: 8px 10px;
  border-radius: 10px;
  text-decoration: none;
  font-size: 13px;
  font-weight: 510;
  letter-spacing: -0.006em;
  white-space: normal;
  gap: 10px;
  white-space: nowrap;
  color: var(--sb-text-body);
  transform-origin: left center;
  transition: transform 0.2s ease, background 0.2s ease, color 0.2s ease, box-shadow 0.2s ease;
}

.nav-link,
.nav-link:hover,
.nav-link:focus,
.nav-link:active,
.nav-link.active {
  text-decoration: none !important;
}

.nav-link:hover { background: var(--sb-surface-2); color: var(--sb-text-heading); transform: scale(1.04); box-shadow: 0 6px 14px rgba(0, 0, 0, 0.16); }
.nav-link.active { background: var(--sb-active-background); color: var(--sb-text-heading); border: 1.5px solid var(--sb-border); box-shadow: inset 0 -2px 5px rgba(0, 0, 0, 0.28); }
.nav-link.active::before {
  display: none;
}

.nav-label { flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: inherit; font-weight: inherit; transition: all 0.12s ease; }
.nav-link:hover .nav-label { color: var(--sb-text-heading); }
.nav-link.active .nav-label { color: var(--sb-text-heading); font-weight: 510; }

.nav-icon-wrapper { width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; border-radius: 6px; font-size: 13.5px; flex-shrink: 0; transition: all 0.12s ease; color: var(--sb-text-body); background: transparent;    -webkit-text-stroke: 1px currentColor;
}
.nav-scale-icon { width: 18px; height: 18px; display: block; -webkit-text-stroke: 1px currentColor; }
.nav-link:hover .nav-icon-wrapper { color: var(--sb-text-heading); background: transparent; }
.nav-link.active .nav-icon-wrapper { color: var(--sb-text-heading); background: transparent; }



.sidebar.collapsed { width: 72px; }
.sidebar.collapsed .brand-text, .sidebar.collapsed .nav-label, .sidebar.collapsed .nav-indicator, .sidebar.collapsed .user-badge span, .sidebar.collapsed .sidebar-divider, .sidebar.collapsed .footer-divider, .sidebar.collapsed .brand-subtitle, .sidebar.collapsed .nav-section-label { display: none !important; }
.sidebar.collapsed .sidebar-header { padding: 16px 10px; justify-content: center; }
.sidebar.collapsed .brand-icon-wrapper { width: 38px; height: 38px; }
.sidebar.collapsed .sidebar-nav { padding: 10px 8px; }
.sidebar.collapsed .nav-link { padding: 8px; justify-content: center; gap: 0; }
.sidebar.collapsed .nav-link:hover { background: var(--sb-surface-2); transform: scale(1.1); box-shadow: 0 6px 14px rgba(0, 0, 0, 0.2); }
.sidebar.collapsed .nav-link.active { background: var(--sb-active-background); }
.sidebar.collapsed .nav-link.active::before { left: 0; }
.sidebar.collapsed .nav-icon-wrapper { width: 24px; height: 24px; font-size: 13.5px; background: transparent !important; }
.sidebar.collapsed .nav-link:hover .nav-icon-wrapper { color: var(--sb-text-heading) !important; background: transparent !important; }
.sidebar.collapsed .nav-link.active .nav-icon-wrapper { color: var(--sb-accent) !important; background: transparent !important; }
.sidebar.collapsed .sidebar-footer { padding: 8px 12px 16px 12px; }
.sidebar.collapsed .user-badge { justify-content: center; padding: 7px; background: var(--sb-active-background); border: 1px solid var(--sb-border); }
.sidebar.collapsed .user-badge i { font-size: 15px; color: var(--sb-text-muted); display: flex !important; }

.sidebar-footer { padding: 12px 16px 18px 16px; margin-top: auto; transition: all 0.3s ease; }
.footer-divider { height: 1px; background: var(--sb-border); margin-bottom: 12px; transition: all 0.3s ease; }
.user-badge { display: flex; align-items: center; gap: 9px; padding: 8px 12px; background: var(--sb-active-background); border-radius: 10px; font-size: 13px; border: 1.5px solid var(--sb-border); transition: all 0.3s ease; color: var(--sb-text-muted); box-shadow: inset 0 -2px 5px rgba(0, 0, 0, 0.28);; }
.user-badge i { color: var(--sb-text-body); font-size: 13px; -webkit-text-stroke: 1px currentColor }
.user-badge span { font-weight: 510; color: var(--sb-text-body); transition: all 0.3s ease; }

.nav-indicator { display: none; }

.sidebar.collapsed .nav-link { position: relative; }
.sidebar.collapsed .nav-link:hover::after {
  content: attr(data-tooltip); position: absolute; left: 72px; top: 50%; transform: translateY(-50%);
  background: var(--sb-active-background); color: #ffffff; padding: 5px 10px; border-radius: 10px; font-size: 12px; font-weight: 400;
  white-space: nowrap; box-shadow: 0 4px 16px rgba(0,0,0,0.4); border: 1px solid var(--sb-border); z-index: 100; pointer-events: none;
}

@media (max-width: 992px) {
  .sidebar { transform: translateX(-100%); width: 280px; box-shadow: 4px 0 24px rgba(0, 0, 0, 0.4); }
  .sidebar.active { transform: translateX(0); }
  .sidebar.collapsed { width: 72px; transform: translateX(0); }
  .sidebar-overlay.active { display: block; }
}

.sidebar-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 999; backdrop-filter: blur(2px); }
.sidebar-overlay.active { display: block; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.brand-icon-wrapper').forEach(function(wrapper) {
    const image = wrapper.querySelector('.brand-icon-img');
    if (!image || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    wrapper.addEventListener('pointermove', function(event) {
      const bounds = wrapper.getBoundingClientRect();
      const x = (event.clientX - bounds.left) / bounds.width - 0.5;
      const y = (event.clientY - bounds.top) / bounds.height - 0.5;
      image.style.transform = 'rotateX(' + (y * -12).toFixed(2) + 'deg) rotateY(' + (x * 12).toFixed(2) + 'deg) scale(1.06)';
    });

    wrapper.addEventListener('pointerleave', function() {
      image.style.transform = 'rotateX(0deg) rotateY(0deg) scale(1)';
    });
  });

  document.querySelectorAll('.nav-link').forEach(function(link) {
    const label = link.querySelector('.nav-label');
    if (label) link.setAttribute('data-tooltip', label.textContent.trim());
  });
});

// Fallback: if a page renders no .topnav (e.g. auth pages), force-remove
// the fixed-topnav spacing reserved by --gov-topnav-height even if a
// cached stylesheet is served. Inline styles always win.
document.addEventListener('DOMContentLoaded', function() {
  if (document.querySelector('.topnav')) return;
  document.documentElement.style.setProperty('--gov-topnav-height', '0px');
  const wrapper = document.querySelector('.app-wrapper');
  if (wrapper) {
    wrapper.style.paddingTop = '0px';
    wrapper.style.marginTop = '0px';
  }
  const main = document.querySelector('.main-content');
  if (main) {
    main.style.marginTop = '0px';
  }
});

// Fallback dropdown toggle: the notification bell and account menu
// (data-bs-toggle="dropdown") rely on Bootstrap's JS bundle. If that
// bundle isn't loaded on a given page for any reason, this takes over
// so the menus still open/close. It steps aside automatically if
// Bootstrap's own JS is present and already working.
document.addEventListener('DOMContentLoaded', function() {
  const hasBootstrapJs = typeof window.bootstrap !== 'undefined' && window.bootstrap.Dropdown;
  if (hasBootstrapJs) return;

  function closeAllDropdowns(except) {
    document.querySelectorAll('.dropdown-menu.show').forEach(function(menu) {
      if (menu !== except) {
        menu.classList.remove('show');
        const toggle = menu.previousElementSibling;
        if (toggle) toggle.setAttribute('aria-expanded', 'false');
      }
    });
  }

  document.querySelectorAll('[data-bs-toggle="dropdown"]').forEach(function(toggle) {
    toggle.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      const menu = toggle.nextElementSibling;
      if (!menu || !menu.classList.contains('dropdown-menu')) return;
      const isOpen = menu.classList.contains('show');
      closeAllDropdowns(isOpen ? null : menu);
      menu.classList.toggle('show', !isOpen);
      toggle.setAttribute('aria-expanded', String(!isOpen));
    });
  });

  document.addEventListener('click', function() {
    closeAllDropdowns(null);
  });

  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeAllDropdowns(null);
  });
});

document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const mainContent = document.querySelector('.main-content');
    const menuBtn = document.getElementById('mobileSidebarToggle');

    function closeMobileDrawer() {
        sidebar.classList.remove('active');
        if (overlay) overlay.classList.remove('active');
        document.body.style.overflow = '';
        if (menuBtn) {
            menuBtn.setAttribute('aria-expanded', 'false');
            const icon = menuBtn.querySelector('i');
            if (icon) icon.className = 'bi bi-list';
        }
    }

    if (overlay) {
        overlay.addEventListener('click', function() {
            closeMobileDrawer();
            sidebar.classList.remove('collapsed');
            if (mainContent) mainContent.classList.remove('sidebar-collapsed');
        });
    }

    document.querySelectorAll('.nav-link').forEach(function(link) {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 992) closeMobileDrawer();
        });
    });

    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            if (window.innerWidth > 992) {
                if (sidebar.classList.contains('active')) closeMobileDrawer();
                if (mainContent) {
                    mainContent.classList.toggle('sidebar-collapsed', sidebar.classList.contains('collapsed'));
                }
            }
        }, 200);
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (sidebar.classList.contains('active') || sidebar.classList.contains('collapsed')) {
                closeMobileDrawer();
                sidebar.classList.remove('collapsed');
                if (mainContent) mainContent.classList.remove('sidebar-collapsed');
            }
        }
    });
});
</script>