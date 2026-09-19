<?php
/**
 * dashboard.php (project root)
 * ------------------------------------------------------------------
 * Landing page after login. Redesigned around the "overview hero card
 * + actionable banner + performance chart + list panels" pattern:
 * everything shown is real, queryable data — no fabricated trend
 * percentages or placeholder content.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/includes/auth.php';
requireLogin();

$pageTitle  = 'Dashboard';
$activeMenu = 'dashboard';
$pdo = db();
$user = currentUser();

if (isCommitteeMember()) {
  include __DIR__ . '/dashboard_member.php';
  exit;
}

$totals = $pdo->query(
    "SELECT
        (SELECT COUNT(*) FROM committees WHERE status = 'Active') AS active_committees,
        (SELECT COUNT(*) FROM committee_members WHERE status = 'Active') AS active_members,
        (SELECT COUNT(*) FROM jurisdictions WHERE status = 'Active') AS active_jurisdictions,
        (SELECT COUNT(*) FROM workload_assignments) AS total_assignments"
)->fetch();

$newCommitteesThisMonth = (int)$pdo->query(
    "SELECT COUNT(*) FROM committees WHERE created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')"
)->fetchColumn();

// ---- Tasks due within the next 7 days, and who's carrying them ----
$dueThisWeek = $pdo->query(
    "SELECT wa.task_title, wa.due_date, u.id AS user_id, u.full_name
     FROM workload_assignments wa
     INNER JOIN committee_members cm ON cm.committee_member_id = wa.committee_member_id
     INNER JOIN users u ON u.id = cm.user_id
     WHERE wa.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
     ORDER BY wa.due_date ASC"
)->fetchAll();
$dueThisWeekCount = count($dueThisWeek);
$dueThisWeekMembers = [];
foreach ($dueThisWeek as $t) {
    $dueThisWeekMembers[$t['user_id']] = $t['full_name'];
    if (count($dueThisWeekMembers) >= 5) break;
}

// ---- 7-day assignment bar chart (Monday-Sunday) ----
$barRaw = $pdo->query(
  "SELECT DATE(assigned_date) AS d, COUNT(*) AS n
     FROM workload_assignments
   WHERE assigned_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
   GROUP BY DATE(assigned_date)"
)->fetchAll(PDO::FETCH_KEY_PAIR);

// Calculate this week's Monday
$today = new DateTime();
$dayOfWeek = (int)$today->format('N'); // 1 = Monday, 7 = Sunday
$daysBackToMonday = $dayOfWeek - 1; // 0 if today is Monday, 6 if today is Sunday
$monday = clone $today;
$monday->modify("-$daysBackToMonday days");

$barChart = [];
for ($i = 0; $i < 7; $i++) {
    $d = $monday->format('Y-m-d');
    $barChart[] = ['date' => $d, 'label' => date('D', strtotime($d)), 'count' => (int)($barRaw[$d] ?? 0)];
    $monday->modify('+1 day');
}
$weekTotal = array_sum(array_column($barChart, 'count'));
$peakCount = max(array_column($barChart, 'count') ?: [0]);
$weekAverage = round($weekTotal / max(count($barChart), 1), 1);
$weekStartLabel = date('j M Y', strtotime($barChart[0]['date']));
$weekEndLabel = date('j M Y', strtotime($barChart[count($barChart) - 1]['date']));
$recentReadings = array_reverse(array_slice($barChart, -2));

// ---- Personal activity feed -----------------------------------------
$recentActivityStmt = $pdo->prepare(
  "SELECT l.action, l.details, l.created_at
   FROM activity_logs l
   WHERE l.user_id = :user_id
   ORDER BY l.created_at DESC, l.id DESC
   LIMIT 4"
);
$recentActivityStmt->execute([':user_id' => currentUserId()]);
$recentActivity = $recentActivityStmt->fetchAll();

$avatarPalette = ['#0B2E59', '#D4AF37', '#C62828', '#1F4E85', '#8A6D1D', '#4B5563'];
function dashInitials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $initials = strtoupper(substr($parts[0] ?? '', 0, 1) . substr($parts[1] ?? '', 0, 1));
    return $initials ?: '?';
}
function dashAvatarColor(array $palette, $seed): string
{
    return $palette[abs(crc32((string)$seed)) % count($palette)];
}

include __DIR__ . '/layouts/header.php';
?>
<div class="app-wrapper">
  <?php include __DIR__ . '/layouts/sidebar.php'; ?>

  <div class="main-content">
  <?php include __DIR__ . '/layouts/content-topbar.php'; ?>
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
      <h4 class="mb-0">Dashboard</h4>
      <small class="text-muted">Welcome back, <?= e($user['full_name']) ?> &middot; <?= e($user['role_name']) ?></small>
    </div>
    <?php if (canManage()): ?>
      <a href="<?= e(APP_URL) ?>/modules/workload/index.php" class="btn-pill-dark">
        <i class="bi bi-plus-lg"></i> Assign Task
      </a>
    <?php endif; ?>
  </div>

  <div class="row g-3">
    <div class="col-lg-8">

      <!-- Overview hero card -->
      <div class="card hero-card overview-card mb-3">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="fw-semibold">Overview</span>
            <span class="badge badge-soft-neutral"><?= date('F Y') ?></span>
          </div>
          <div class="row">
            <div class="col-6">
              <div class="text-muted small mb-1"><i class="bi bi-diagram-3"></i> Active Committees</div>
              <div class="hero-number"><?= (int)$totals['active_committees'] ?></div>
              <span class="badge badge-soft-success mt-2"><i class="bi bi-arrow-up-short"></i> <?= $newCommitteesThisMonth ?> new this month</span>
            </div>
            <div class="col-6 border-start">
              <div class="text-muted small mb-1 ps-3"><i class="bi bi-list-task"></i> Total Assignments</div>
              <div class="hero-number ps-3"><?= (int)$totals['total_assignments'] ?></div>
              <span class="badge badge-soft-gold mt-2 ms-3">Recorded assignments</span>
            </div>
          </div>
          <div class="d-flex gap-2 mt-3">
            <span class="badge badge-soft-neutral"><?= (int)$totals['active_members'] ?> members</span>
            <span class="badge badge-soft-neutral"><?= (int)$totals['active_jurisdictions'] ?> jurisdictions</span>
          </div>
        </div>
      </div>

      <!-- Actionable banner -->
      <?php if ($dueThisWeekCount > 0): ?>
      <div class="card hero-card mb-3">
        <div class="card-body">
          <h6 class="mb-1"><?= $dueThisWeekCount ?> task<?= $dueThisWeekCount === 1 ? '' : 's' ?> due this week!</h6>
          <p class="text-muted small mb-3">Review workload distribution to keep every committee on track.</p>
          <div class="d-flex flex-wrap gap-4">
            <?php foreach ($dueThisWeekMembers as $uid => $name): ?>
              <div class="avatar-row-item">
                <div class="avatar-circle" style="background:<?= dashAvatarColor($avatarPalette, $uid) ?>;"><?= e(dashInitials($name)) ?></div>
                <div class="avatar-name"><?= e(explode(' ', $name)[0]) ?></div>
              </div>
            <?php endforeach; ?>
            <div class="avatar-row-item">
              <a href="<?= e(APP_URL) ?>/modules/workload/index.php" class="avatar-view-all"><i class="bi bi-arrow-right"></i></a>
              <div class="avatar-name">View all</div>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <!-- Assignment distribution bar chart -->
      <div class="card hero-card dashboard-completion-card">
        <div class="card-body">
          <div class="completion-card-header">
            <span class="completion-card-title">Assignments Created</span>
            <span class="completion-card-period">Weekly activity</span>
          </div>
          <div class="completion-card-total"><?= $weekTotal ?> <span>assignments</span></div>
          <div class="completion-card-date-range"><?= e($weekStartLabel) ?> - <?= e($weekEndLabel) ?></div>

          <div class="completion-chart-container">
            <div class="completion-average-line"></div>
            <div class="completion-average-label">Avg. <?= $weekAverage ?></div>
            <div class="completion-chart">
            <?php foreach ($barChart as $b):
              $isPeak = $b['count'] > 0 && $b['count'] === $peakCount;
              $heightPct = $peakCount > 0 ? max(6, round(($b['count'] / $peakCount) * 100)) : 6;
            ?>
              <div class="completion-bar-wrapper">
                <div class="completion-bar-container">
                  <div class="completion-bar <?= $isPeak ? 'peak' : '' ?>" style="height:<?= $heightPct ?>%;">
                    <?php if ($isPeak): ?><span class="completion-bar-dot"></span><?php endif; ?>
                    <span class="completion-bar-tooltip"><?= $b['count'] ?></span>
                  </div>
                </div>
                <div class="completion-day-label"><?= e($b['label']) ?></div>
              </div>
            <?php endforeach; ?>
            </div>
          </div>

          <div class="completion-readings">
            <?php foreach ($recentReadings as $reading): ?>
              <div class="completion-reading">
                <span><?= e($reading['label']) ?> assignments</span>
                <strong><?= (int)$reading['count'] ?></strong>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>



    </div>

    <div class="col-lg-4">

      <!-- Personal activity -->
      <div class="card hero-card">
        <div class="card-body">
          <div class="member-card-heading"><div><span class="performance-kicker">Account history</span><h5>My activity</h5></div><i class="bi bi-activity member-card-heading-icon"></i></div>
          <?php if (empty($recentActivity)): ?>
            <p class="text-muted small mt-2 mb-0">No activity recorded yet.</p>
          <?php else: ?>
            <div class="mt-2">
              <?php foreach ($recentActivity as $a): ?>
                <div class="d-flex gap-2 py-2 border-bottom">
                  <div class="avatar-circle sm flex-shrink-0" style="background:<?= dashAvatarColor($avatarPalette, $user['id']) ?>;">
                    <?= e(dashInitials($user['full_name'])) ?>
                  </div>
                  <div>
                    <div class="small"><strong><?= e($a['action']) ?></strong></div>
                    <div class="text-muted small"><?= e(truncate($a['details'] ?? '', 70)) ?></div>
                    <div class="text-muted small"><?= e(timeAgo($a['created_at'])) ?></div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>

<?php
include __DIR__ . '/layouts/footer.php';
?>
