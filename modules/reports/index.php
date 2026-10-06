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
requireRole([ROLE_ADMIN, ROLE_STAFF, ROLE_SUPER_ADMIN, ...LEGISLATIVE_OVERSIGHT_ROLES]); // read/oversight only; canManage() still gates writes

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

  <div class="main-content reports-analytics-page">
  <?php include __DIR__ . '/../../layouts/content-topbar.php'; ?>
  <div class="breadcrumb-bar reports-analytics-hero d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div class="reports-analytics-heading">
      <span class="reports-analytics-icon"><i class="bi bi-bar-chart-line"></i></span>
      <div><span class="reports-analytics-eyebrow">Performance intelligence</span>
        <h5 class="mb-1">Reports &amp; Analytics</h5>
        <small>System-wide workload metrics, trends, and committee performance.</small>
      </div>
    </div>
    <div class="reports-analytics-actions d-flex gap-2 no-print">
      <a class="btn btn-outline-secondary btn-sm" href="export_csv.php?date_from=<?= e($dateFrom ?? '') ?>&date_to=<?= e($dateTo ?? '') ?>"><i class="bi bi-download"></i> Export CSV</a>
      <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="bi bi-printer"></i> Print report</button>
    </div>
  </div>

  <!-- Date range filter -->
  <div class="card reports-analytics-filter mb-3 no-print">
    <div class="card-body">
      <form method="get" id="rangeFilterForm" class="reports-filter-form d-flex flex-wrap align-items-end gap-3">
        <div class="reports-filter-title"><span class="reports-section-eyebrow">Date range</span><strong>Filter analytics</strong></div>
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
        <div class="reports-quick-ranges ms-auto">
          <label class="form-label small mb-1 text-muted">Quick select</label>
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
  <div class="reports-kpi-grid mb-3">
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
      <div class="card reports-kpi-card">
        <div class="card-body"><span class="reports-kpi-icon"><i class="bi <?= e($icon) ?>"></i></span><div><div class="reports-kpi-value"><?= e((string)$value) ?></div><div class="reports-kpi-label"><?= e($label) ?></div></div></div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Charts -->
  <div class="row g-3 mb-3">
    <div class="col-lg-4">
      <div class="card reports-chart-card h-100"><div class="card-body">
        <div class="reports-chart-heading"><div><span class="reports-section-eyebrow">Task mix</span><h5>Status Distribution</h5><small>Assignments by current status</small></div><span class="reports-chart-icon"><i class="bi bi-pie-chart"></i></span></div>
        <div class="reports-chart-canvas reports-chart-canvas--donut"><canvas id="statusChart"></canvas></div>
      </div></div>
    </div>
    <div class="col-lg-8">
      <div class="card reports-chart-card h-100"><div class="card-body">
        <div class="reports-chart-heading"><div><span class="reports-section-eyebrow">Last 6 months</span><h5>Assignments Created</h5><small>Monthly assignment volume</small></div><span class="reports-chart-icon"><i class="bi bi-bar-chart"></i></span></div>
        <div class="reports-chart-canvas"><canvas id="monthlyChart"></canvas></div>
      </div></div>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-lg-6">
      <div class="card reports-chart-card h-100"><div class="card-body">
        <div class="reports-chart-heading"><div><span class="reports-section-eyebrow">Top 10 members</span><h5>Workload per Member</h5><small>Assignments by member</small></div><span class="reports-chart-icon"><i class="bi bi-person-lines-fill"></i></span></div>
        <div class="reports-chart-canvas reports-chart-canvas--ranked" style="height:<?= max(260, count($memberLoad) * 28) ?>px;">
          <?php if (empty($memberLoad)): ?>
            <div class="h-100 d-flex flex-column align-items-center justify-content-center text-center text-muted">
              <i class="bi bi-person-lines-fill" style="font-size:28px;"></i>
              <p class="small mt-2 mb-0">No assignment data for members in this date range.</p>
            </div>
          <?php else: ?>
            <canvas id="memberChart"></canvas>
          <?php endif; ?>
        </div>
      </div></div>
    </div>
    <div class="col-lg-6">
      <div class="card reports-chart-card h-100"><div class="card-body">
        <div class="reports-chart-heading"><div><span class="reports-section-eyebrow">Top 12 committees</span><h5>Completion by Committee</h5><small>Completed assignments as a share of total</small></div><span class="reports-chart-icon"><i class="bi bi-graph-up-arrow"></i></span></div>
        <div class="reports-chart-canvas reports-chart-canvas--ranked" style="height:<?= max(260, count($committeeChart) * 28) ?>px;">
          <?php if (empty($committeeChart)): ?>
            <div class="h-100 d-flex flex-column align-items-center justify-content-center text-center text-muted">
              <i class="bi bi-graph-up-arrow" style="font-size:28px;"></i>
              <p class="small mt-2 mb-0">No committee assignment data for this date range.</p>
            </div>
          <?php else: ?>
            <canvas id="committeeChart"></canvas>
          <?php endif; ?>
        </div>
      </div></div>
    </div>
  </div>

  <div class="card reports-chart-card mb-3"><div class="card-body">
    <div class="reports-chart-heading"><div><span class="reports-section-eyebrow"><?= e(formatDate($trendFrom)) ?> – <?= e(formatDate($trendTo)) ?></span><h5>System Activity Trend</h5><small>Recorded system activity over time</small></div><span class="reports-chart-icon"><i class="bi bi-activity"></i></span></div>
    <div class="reports-chart-canvas reports-chart-canvas--activity"><canvas id="activityChart"></canvas></div>
  </div></div>

  <!-- Per-committee breakdown -->
  <div class="card reports-breakdown-card">
    <div class="reports-breakdown-heading"><div><span class="reports-section-eyebrow">Committee overview</span><h5>Performance Breakdown</h5><small>Membership and task status by committee for the selected period.</small></div><span class="reports-breakdown-count"><?= count($breakdown) ?> committees</span></div>
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
  Chart.defaults.font.family = 'Inter, system-ui, -apple-system, "Segoe UI", sans-serif';
  Chart.defaults.font.size = 10;
  Chart.defaults.color = '#718397';
  Chart.defaults.plugins.tooltip.backgroundColor = '#17324d';
  Chart.defaults.plugins.tooltip.padding = 10;
  Chart.defaults.plugins.tooltip.cornerRadius = 8;
  Chart.defaults.plugins.tooltip.titleFont = { weight: '600' };
  Chart.defaults.plugins.tooltip.bodyFont = { weight: '500' };

  new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: { labels: <?= json_encode(array_keys($statusCounts)) ?>,
            datasets: [{ data: <?= json_encode(array_values($statusCounts)) ?>,
                         backgroundColor: ['#94a3b8', '#3974a5', '#239276', '#d46b72'],
                         hoverBackgroundColor: ['#8393a8', '#2f638f', '#1d7f67', '#bf5a62'],
                         borderWidth: 3, borderColor: '#fff', hoverOffset: 5, spacing: 2 }] },
    options: {
      plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, pointStyle: 'circle', boxWidth: 7, boxHeight: 7, padding: 15, font: { size: 9 } } } },
      responsive: true, maintainAspectRatio: false, cutout: '68%'
    }
  });

  new Chart(document.getElementById('monthlyChart'), {
    type: 'bar',
    data: { labels: <?= json_encode($monthlyLabels) ?>,
            datasets: [{ label: 'Assignments', data: <?= json_encode($monthlyData) ?>, backgroundColor: '#315f86', hoverBackgroundColor: '#173c62', borderRadius: 6, maxBarThickness: 38 }] },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, border: { display: false }, grid: { color: 'rgba(43, 73, 102, .08)' }, ticks: { precision: 0, padding: 8 } }, x: { border: { display: false }, grid: { display: false }, ticks: { padding: 7 } } }, responsive: true, maintainAspectRatio: false }
  });

  <?php if (!empty($memberLoad)): ?>
  new Chart(document.getElementById('memberChart'), {
    type: 'bar',
    data: { labels: <?= json_encode(array_column($memberLoad, 'full_name')) ?>,
            datasets: [{ label: 'Assignments', data: <?= json_encode(array_map('intval', array_column($memberLoad, 'n'))) ?>, backgroundColor: '#bd9a48', hoverBackgroundColor: '#a98230', borderRadius: 5, barThickness: 12 }] },
    options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, border: { display: false }, grid: { color: 'rgba(43, 73, 102, .08)' }, ticks: { precision: 0, padding: 7 } }, y: { border: { display: false }, grid: { display: false }, ticks: { autoSkip: false, font: { size: 9 }, padding: 8 } } }, responsive: true, maintainAspectRatio: false }
  });
  <?php endif; ?>

  <?php if (!empty($committeeChart)): ?>
  new Chart(document.getElementById('committeeChart'), {
    type: 'bar',
    data: { labels: <?= json_encode(array_column($committeeChart, 'committee_name')) ?>,
            datasets: [{ label: 'Completion', data: <?= json_encode(array_map(function ($r) { return (int)$r['assignments'] > 0 ? round(((int)$r['completed'] / (int)$r['assignments']) * 100) : 0; }, $committeeChart)) ?>, backgroundColor: '#3974a5', hoverBackgroundColor: '#24577f', borderRadius: 5, barThickness: 12 }] },
    options: { indexAxis: 'y', plugins: { legend: { display: false }, tooltip: { callbacks: { label: function (context) { return 'Completion: ' + context.raw + '%'; } } } }, scales: { x: { beginAtZero: true, max: 100, border: { display: false }, grid: { color: 'rgba(43, 73, 102, .08)' }, ticks: { stepSize: 25, padding: 7, callback: function (v) { return v + '%'; } } }, y: { border: { display: false }, grid: { display: false }, ticks: { autoSkip: false, font: { size: 9 }, padding: 8, callback: function (value) { const label = this.getLabelForValue(value); return label.length > 28 ? label.slice(0, 25) + '…' : label; } } } }, responsive: true, maintainAspectRatio: false }
  });
  <?php endif; ?>

  new Chart(document.getElementById('activityChart'), {
    type: 'line',
    data: { labels: <?= json_encode($trendLabels) ?>,
            datasets: [{ label: 'Log entries', data: <?= json_encode($trendData) ?>, borderColor: '#3974a5', backgroundColor: 'rgba(57,116,165,.12)', fill: true, tension: 0.35, pointRadius: 2, pointHoverRadius: 5, pointBackgroundColor: '#fff', pointBorderWidth: 2, pointBorderColor: '#3974a5' }] },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, border: { display: false }, grid: { color: 'rgba(43, 73, 102, .08)' }, ticks: { precision: 0, padding: 7 } }, x: { border: { display: false }, grid: { display: false }, ticks: { maxTicksLimit: 12, padding: 7 } } }, responsive: true, maintainAspectRatio: false }
  });
});
</script>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>
