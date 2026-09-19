<?php
/**
 * modules/reports/index.php
 * ------------------------------------------------------------------
 * Reports & Analytics: system-wide KPIs, charts (status mix, monthly
 * assignments, member workload, committee completion, activity trend)
 * and a per-committee breakdown table with CSV export and print.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/report_queries.php';
requireRole([ROLE_ADMIN, ROLE_STAFF]);

$pageTitle  = 'Reports & Analytics';
$activeMenu = 'reports_analytics';
$pdo = db();
[$dateFrom, $dateTo] = reportDateRange();
[$range, $rangeParams] = waRange($dateFrom, $dateTo);

// ---- KPIs ------------------------------------------------------------
$totals = $pdo->query(
    "SELECT (SELECT COUNT(*) FROM committees WHERE status = 'Active') AS committees,
            (SELECT COUNT(*) FROM committee_members WHERE status = 'Active') AS members,
            (SELECT COUNT(*) FROM jurisdictions WHERE status = 'Active') AS jurisdictions"
)->fetch();

$assignStmt = $pdo->prepare(
    "SELECT COUNT(*) AS total,
            SUM(status = 'Completed')   AS done,
            SUM(status = 'In Progress') AS in_progress,
            SUM(status = 'Pending')     AS pending,
            SUM(status = 'Overdue')     AS overdue
     FROM workload_assignments wa WHERE $range"
);
$assignStmt->execute($rangeParams);
$assign = $assignStmt->fetch();
$totalAssign = (int)$assign['total'];
$completionRate = $totalAssign > 0 ? round(((int)$assign['done'] / $totalAssign) * 100) : 0;

// ---- Chart datasets ----------------------------------------------------
$statusStmt = $pdo->prepare("SELECT status, COUNT(*) AS n FROM workload_assignments wa WHERE $range GROUP BY status");
$statusStmt->execute($rangeParams);
$statusCounts = array_fill_keys(['Pending', 'In Progress', 'Completed', 'Overdue'], 0);
foreach ($statusStmt->fetchAll() as $r) { $statusCounts[$r['status']] = (int)$r['n']; }

$monthlyRaw = $pdo->query(
    "SELECT DATE_FORMAT(assigned_date, '%Y-%m') AS ym, COUNT(*) AS n
     FROM workload_assignments
     WHERE assigned_date >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 5 MONTH), '%Y-%m-01')
     GROUP BY ym"
)->fetchAll(PDO::FETCH_KEY_PAIR);
$monthlyLabels = [];
$monthlyData = [];
for ($i = 5; $i >= 0; $i--) {
    $ym = date('Y-m', strtotime("first day of -$i months"));
    $monthlyLabels[] = date('M Y', strtotime($ym . '-01'));
    $monthlyData[] = (int)($monthlyRaw[$ym] ?? 0);
}

$memberStmt = $pdo->prepare(
    "SELECT u.full_name, COUNT(*) AS n
     FROM workload_assignments wa
     INNER JOIN committee_members cm ON cm.committee_member_id = wa.committee_member_id
     INNER JOIN users u ON u.id = cm.user_id
     WHERE $range
     GROUP BY u.id ORDER BY n DESC LIMIT 10"
);
$memberStmt->execute($rangeParams);
$memberLoad = $memberStmt->fetchAll();

$breakdown = committeeBreakdown($pdo, $dateFrom, $dateTo);
$committeeChart = array_slice($breakdown, 0, 12);

// Activity trend: range-bound when a filter is set, else last 14 days.
$trendFrom = $dateFrom ?? date('Y-m-d', strtotime('-13 days'));
$trendTo   = $dateTo   ?? date('Y-m-d');
$trendStmt = $pdo->prepare(
    "SELECT DATE(created_at) AS d, COUNT(*) AS n
     FROM activity_logs
     WHERE created_at >= :tfrom AND created_at < DATE_ADD(:tto, INTERVAL 1 DAY)
     GROUP BY DATE(created_at)"
);
$trendStmt->execute([':tfrom' => $trendFrom, ':tto' => $trendTo]);
$trendRaw = $trendStmt->fetchAll(PDO::FETCH_KEY_PAIR);
$trendLabels = [];
$trendData = [];
for ($d = strtotime($trendFrom); $d <= strtotime($trendTo); $d = strtotime('+1 day', $d)) {
    $key = date('Y-m-d', $d);
    $trendLabels[] = date('M j', $d);
    $trendData[] = (int)($trendRaw[$key] ?? 0);
}

include __DIR__ . '/../../layouts/header.php';
?>
<div class="app-wrapper">
  <?php include __DIR__ . '/../../layouts/sidebar.php'; ?>

  <div class="main-content">
  <?php include __DIR__ . '/../../layouts/content-topbar.php'; ?>
  <div class="breadcrumb-bar d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <h5 class="mb-0"><i class="bi bi-bar-chart-line text-primary"></i> Reports &amp; Analytics</h5>
      <small class="text-muted">System-wide metrics, workload analytics, and committee breakdowns.</small>
    </div>
    <div class="d-flex gap-2 no-print">
      <a class="btn btn-outline-secondary btn-sm" href="export_csv.php?date_from=<?= e($dateFrom ?? '') ?>&date_to=<?= e($dateTo ?? '') ?>"><i class="bi bi-file-earmark-excel"></i> Export CSV</a>
      <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
    </div>
  </div>

  <!-- Date range filter -->
  <div class="card mb-3 no-print">
    <div class="card-body py-3">
      <form method="get" id="rangeFilterForm" class="d-flex flex-wrap align-items-end gap-3">
        <?php if ($dateFrom || $dateTo): ?>
          <a href="index.php" class="badge badge-soft-neutral align-self-center text-decoration-none" title="Clear date filter">
            <i class="bi bi-funnel"></i> <?= e($dateFrom ? formatDate($dateFrom) : 'Start') ?> – <?= e($dateTo ? formatDate($dateTo) : 'Today') ?> <i class="bi bi-x-lg ms-1"></i>
          </a>
        <?php endif; ?>
        <div class="d-flex align-items-end gap-2">
          <div>
            <label class="form-label small mb-1" for="dateFrom">From</label>
            <input type="date" id="dateFrom" name="date_from" class="form-control form-control-sm" style="min-width:150px;" value="<?= e($dateFrom ?? '') ?>">
          </div>
          <div class="pb-1 text-muted small"><i class="bi bi-arrow-right"></i></div>
          <div>
            <label class="form-label small mb-1" for="dateTo">To</label>
            <input type="date" id="dateTo" name="date_to" class="form-control form-control-sm" style="min-width:150px;" value="<?= e($dateTo ?? '') ?>">
          </div>
        </div>
        <div class="d-flex gap-2 pb-0">
          <button class="btn btn-primary btn-sm"><i class="bi bi-check2"></i> Apply</button>
          <a href="index.php" class="btn btn-outline-secondary btn-sm">Reset</a>
        </div>
        <div class="vr d-none d-lg-block" style="height:30px;opacity:.25;"></div>
        <div class="ms-auto">
          <label class="form-label small mb-1 text-muted">Quick range</label>
          <div class="btn-group btn-group-sm" role="group" aria-label="Quick date ranges">
            <button type="button" class="btn btn-outline-secondary" data-range="7d">7 days</button>
            <button type="button" class="btn btn-outline-secondary" data-range="30d">30 days</button>
            <button type="button" class="btn btn-outline-secondary" data-range="month">This month</button>
            <button type="button" class="btn btn-outline-secondary" data-range="year">This year</button>
            <button type="button" class="btn btn-outline-secondary" data-range="all">All time</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- KPI cards -->
  <div class="row g-3 mb-3">
    <?php
    $kpis = [
      ['bi-diagram-3',    (int)$totals['committees'],   'Active Committees'],
      ['bi-people',       (int)$totals['members'],      'Active Members'],
      ['bi-list-task',    $totalAssign,                 'Assignments'],
      ['bi-check-circle', (int)$assign['done'],         'Completed'],
      ['bi-exclamation-triangle', (int)$assign['overdue'], 'Overdue'],
      ['bi-percent',      $completionRate . '%',        'Completion Rate'],
    ];
    foreach ($kpis as [$icon, $value, $label]): ?>
      <div class="col-6 col-lg-2">
        <div class="card stat-card activity-summary-card">
          <div class="card-body"><i class="bi <?= e($icon) ?> stat-icon"></i><div class="stat-value"><?= e((string)$value) ?></div><div class="stat-label"><?= e($label) ?></div></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Charts -->
  <div class="row g-3 mb-3">
    <div class="col-lg-4">
      <div class="card hero-card h-100"><div class="card-body">
        <div class="member-card-heading"><div><span class="performance-kicker">Task mix</span><h5>Status Distribution</h5></div><i class="bi bi-pie-chart member-card-heading-icon"></i></div>
        <div style="height:220px;"><canvas id="statusChart"></canvas></div>
      </div></div>
    </div>
    <div class="col-lg-8">
      <div class="card hero-card h-100"><div class="card-body">
        <div class="member-card-heading"><div><span class="performance-kicker">Last 6 months</span><h5>Assignments Created</h5></div><i class="bi bi-bar-chart member-card-heading-icon"></i></div>
        <div style="height:220px;"><canvas id="monthlyChart"></canvas></div>
      </div></div>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-lg-6">
      <div class="card hero-card h-100"><div class="card-body">
        <div class="member-card-heading"><div><span class="performance-kicker">Top 10</span><h5>Workload per Member</h5></div><i class="bi bi-person-lines-fill member-card-heading-icon"></i></div>
        <div style="height:260px;"><canvas id="memberChart"></canvas></div>
      </div></div>
    </div>
    <div class="col-lg-6">
      <div class="card hero-card h-100"><div class="card-body">
        <div class="member-card-heading"><div><span class="performance-kicker">Top 12</span><h5>Completion by Committee</h5></div><i class="bi bi-graph-up-arrow member-card-heading-icon"></i></div>
        <div style="height:260px;"><canvas id="committeeChart"></canvas></div>
      </div></div>
    </div>
  </div>

  <div class="card hero-card mb-3"><div class="card-body">
    <div class="member-card-heading"><div><span class="performance-kicker"><?= e(formatDate($trendFrom)) ?> – <?= e(formatDate($trendTo)) ?></span><h5>System Activity Trend</h5></div><i class="bi bi-activity member-card-heading-icon"></i></div>
    <div style="height:200px;"><canvas id="activityChart"></canvas></div>
  </div></div>

  <!-- Per-committee breakdown -->
  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead><tr><th>Committee</th><th>Status</th><th class="text-center">Members</th><th class="text-center">Assignments</th><th class="text-center">Completed</th><th class="text-center">In Progress</th><th class="text-center">Pending</th><th class="text-center">Overdue</th><th class="text-end">Completion</th></tr></thead>
        <tbody>
        <?php if (empty($breakdown)): ?>
          <tr><td colspan="9" class="text-center text-muted py-4">No committees found.</td></tr>
        <?php else: foreach ($breakdown as $b):
          $rate = (int)$b['assignments'] > 0 ? round(((int)$b['completed'] / (int)$b['assignments']) * 100) : 0;
        ?>
          <tr>
            <td class="small fw-semibold"><?= e($b['committee_name']) ?></td>
            <td><span class="badge bg-<?= $b['status'] === 'Active' ? 'success' : 'secondary' ?>"><?= e($b['status']) ?></span></td>
            <td class="text-center"><?= (int)$b['member_count'] ?></td>
            <td class="text-center"><?= (int)$b['assignments'] ?></td>
            <td class="text-center"><?= (int)$b['completed'] ?></td>
            <td class="text-center"><?= (int)$b['in_progress'] ?></td>
            <td class="text-center"><?= (int)$b['pending'] ?></td>
            <td class="text-center"><?= (int)$b['overdue'] ?></td>
            <td class="text-end"><span class="badge bg-<?= $rate >= 80 ? 'success' : ($rate >= 40 ? 'warning' : 'secondary') ?>"><?= $rate ?>%</span></td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>
</div>

<style>
@media print {
  .sidebar, .sidebar-overlay, .sidebar-toggle-wrapper, .content-topbar, .no-print { display: none !important; }
  .main-content { margin: 0 !important; padding: 0 !important; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  // ---- Quick date-range presets ---------------------------------------
  const rangeForm = document.getElementById('rangeFilterForm');
  const fromInput = document.getElementById('dateFrom');
  const toInput = document.getElementById('dateTo');
  const iso = function (d) { return d.toISOString().slice(0, 10); };

  // Highlight the preset matching the current inputs
  (function () {
    const today = new Date();
    const presets = {
      '7d':    iso(new Date(today.getFullYear(), today.getMonth(), today.getDate() - 6)),
      '30d':   iso(new Date(today.getFullYear(), today.getMonth(), today.getDate() - 29)),
      'month': iso(new Date(today.getFullYear(), today.getMonth(), 1)),
      'year':  iso(new Date(today.getFullYear(), 0, 1))
    };
    document.querySelectorAll('[data-range]').forEach(function (btn) {
      const r = btn.getAttribute('data-range');
      const matches = r === 'all'
        ? !fromInput.value && !toInput.value
        : presets[r] && fromInput.value === presets[r] && toInput.value === iso(today);
      btn.classList.toggle('active', matches);
    });
  })();

  document.querySelectorAll('[data-range]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const today = new Date();
      let from = null;
      switch (btn.getAttribute('data-range')) {
        case '7d':    from = new Date(today); from.setDate(from.getDate() - 6); break;
        case '30d':   from = new Date(today); from.setDate(from.getDate() - 29); break;
        case 'month': from = new Date(today.getFullYear(), today.getMonth(), 1); break;
        case 'year':  from = new Date(today.getFullYear(), 0, 1); break;
        case 'all':   from = null; break;
      }
      fromInput.value = from ? iso(from) : '';
      toInput.value = btn.getAttribute('data-range') === 'all' ? '' : iso(today);
      rangeForm.submit();
    });
  });

  rangeForm.addEventListener('submit', function () {
    if (fromInput.value && toInput.value && fromInput.value > toInput.value) {
      const tmp = fromInput.value; fromInput.value = toInput.value; toInput.value = tmp;
    }
  });

  if (!window.Chart) return;

  new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: { labels: <?= json_encode(array_keys($statusCounts)) ?>,
            datasets: [{ data: <?= json_encode(array_values($statusCounts)) ?>,
                         backgroundColor: ['#6B7280', '#2f6fed', '#198754', '#dc3545'], borderWidth: 0 }] },
    options: { plugins: { legend: { position: 'bottom' } }, responsive: true, maintainAspectRatio: false, cutout: '62%' }
  });

  new Chart(document.getElementById('monthlyChart'), {
    type: 'bar',
    data: { labels: <?= json_encode($monthlyLabels) ?>,
            datasets: [{ label: 'Assignments', data: <?= json_encode($monthlyData) ?>, backgroundColor: '#0B2E59', borderRadius: 5 }] },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { grid: { display: false } } }, responsive: true, maintainAspectRatio: false }
  });

  new Chart(document.getElementById('memberChart'), {
    type: 'bar',
    data: { labels: <?= json_encode(array_column($memberLoad, 'full_name')) ?>,
            datasets: [{ label: 'Assignments', data: <?= json_encode(array_map('intval', array_column($memberLoad, 'n'))) ?>, backgroundColor: '#D4AF37', borderRadius: 5 }] },
    options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { precision: 0 } }, y: { grid: { display: false } } }, responsive: true, maintainAspectRatio: false }
  });

  new Chart(document.getElementById('committeeChart'), {
    type: 'bar',
    data: { labels: <?= json_encode(array_column($committeeChart, 'committee_name')) ?>,
            datasets: [{ label: 'Completion %', data: <?= json_encode(array_map(function ($r) { return (int)$r['assignments'] > 0 ? round(((int)$r['completed'] / (int)$r['assignments']) * 100) : 0; }, $committeeChart)) ?>, backgroundColor: '#1F4E85', borderRadius: 5 }] },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, max: 100, ticks: { callback: function (v) { return v + '%'; } } }, x: { grid: { display: false }, ticks: { autoSkip: false, maxRotation: 45 } } }, responsive: true, maintainAspectRatio: false }
  });

  new Chart(document.getElementById('activityChart'), {
    type: 'line',
    data: { labels: <?= json_encode($trendLabels) ?>,
            datasets: [{ label: 'Log entries', data: <?= json_encode($trendData) ?>, borderColor: '#2f6fed', backgroundColor: 'rgba(47,111,237,0.12)', fill: true, tension: 0.3, pointRadius: 2 }] },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { grid: { display: false } } }, responsive: true, maintainAspectRatio: false }
  });
});
</script>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>
