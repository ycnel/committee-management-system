<?php
/**
 * modules/performance/index.php
 * ------------------------------------------------------------------
 * Committee Performance Monitoring module (Module 5). Computes KPIs
 * (completed/pending/overdue tasks, completion rate) live from
 * workload_assignments for every active committee, charts them, and
 * lets Admin/Staff snapshot the current numbers into
 * `committee_performance` for historical reporting (feeds Module 6).
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../../includes/auth.php';
requireRole([ROLE_ADMIN, ROLE_STAFF, ROLE_COMMITTEE]);

$pageTitle  = 'Committee Performance';
$activeMenu = 'performance';
$pdo = db();

// ---- Live KPIs per committee -----------------------------------------
$kpiStmt = $pdo->query(
    "SELECT c.committee_id, c.committee_name,
            COUNT(wa.workload_id) AS total_tasks,
            SUM(CASE WHEN wa.status = 'Completed' THEN 1 ELSE 0 END) AS completed_tasks,
            SUM(CASE WHEN wa.status IN ('Pending','In Progress') THEN 1 ELSE 0 END) AS pending_tasks,
            SUM(CASE WHEN wa.status != 'Completed' AND wa.due_date IS NOT NULL AND wa.due_date < CURDATE() THEN 1 ELSE 0 END) AS overdue_tasks
     FROM committees c
     LEFT JOIN committee_members cm ON cm.committee_id = c.committee_id AND cm.status = 'Active'
     LEFT JOIN workload_assignments wa ON wa.committee_member_id = cm.committee_member_id
     WHERE c.status = 'Active'
     GROUP BY c.committee_id, c.committee_name
     ORDER BY c.committee_name"
);
$kpis = $kpiStmt->fetchAll();
foreach ($kpis as &$k) {
    $k['completion_rate'] = $k['total_tasks'] > 0 ? round(($k['completed_tasks'] / $k['total_tasks']) * 100, 2) : 0.0;
}
unset($k);

$totalTasks = (int)array_sum(array_column($kpis, 'total_tasks'));
$completedTasks = (int)array_sum(array_column($kpis, 'completed_tasks'));
$pendingTasks = (int)array_sum(array_column($kpis, 'pending_tasks'));
$overdueTasks = (int)array_sum(array_column($kpis, 'overdue_tasks'));
$overallRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;
$committeeCount = count($kpis);

// ---- Recent saved snapshots -------------------------------------------
$snapshots = $pdo->query(
    "SELECT cp.*, c.committee_name FROM committee_performance cp
     INNER JOIN committees c ON c.committee_id = cp.committee_id
     ORDER BY cp.generated_at DESC LIMIT 10"
)->fetchAll();

include __DIR__ . '/../../layouts/header.php';
?>
<div class="app-wrapper">
  <?php include __DIR__ . '/../../layouts/sidebar.php'; ?>

  <div class="main-content">
  <?php include __DIR__ . '/../../layouts/content-topbar.php'; ?>
  <div class="breadcrumb-bar performance-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <span class="performance-eyebrow">Live operations overview</span>
      <h5 class="mb-1"><i class="bi bi-graph-up-arrow text-primary"></i> Committee Performance</h5>
      <small class="text-muted">Completion rates and workload health, computed live.</small>
    </div>
    <span class="performance-live-badge"><span></span> Updated from live data</span>
  </div>

  <div class="performance-dashboard">
    <div class="performance-stat-grid">
      <div class="performance-stat">
        <div class="performance-stat-icon performance-stat-icon--blue"><i class="bi bi-list-check"></i></div>
        <span>Total tasks</span>
        <strong><?= number_format($totalTasks) ?></strong>
      </div>
      <div class="performance-stat">
        <div class="performance-stat-icon performance-stat-icon--green"><i class="bi bi-check2-circle"></i></div>
        <span>Completed</span>
        <strong><?= number_format($completedTasks) ?></strong>
      </div>
      <div class="performance-stat">
        <div class="performance-stat-icon performance-stat-icon--gold"><i class="bi bi-hourglass-split"></i></div>
        <span>In progress</span>
        <strong><?= number_format($pendingTasks) ?></strong>
      </div>
      <div class="performance-stat">
        <div class="performance-stat-icon performance-stat-icon--red"><i class="bi bi-exclamation-triangle"></i></div>
        <span>Overdue</span>
        <strong><?= number_format($overdueTasks) ?></strong>
      </div>
    </div>

    <div class="row g-3 mb-3">
      <div class="col-xl-8">
        <div class="card performance-chart-card h-100">
          <div class="card-body">
            <div class="performance-card-heading">
              <div>
                <span class="performance-kicker">Committee comparison</span>
                <h2>Completion rate</h2>
              </div>
              <span class="performance-rate-pill"><?= $overallRate ?>% overall</span>
            </div>
            <div class="performance-bar-wrap">
              <canvas id="completionRateChart"></canvas>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-4">
        <div class="card performance-chart-card performance-mix-card h-100">
          <div class="card-body">
            <div class="performance-card-heading">
              <div>
                <span class="performance-kicker">All active committees</span>
                <h2>Task mix</h2>
              </div>
              <i class="bi bi-pie-chart performance-heading-icon"></i>
            </div>
            <div class="performance-donut-wrap">
              <canvas id="taskMixChart"></canvas>
            </div>
            <div class="performance-mix-legend">
              <span><i class="performance-dot performance-dot--green"></i>Completed <b><?= number_format($completedTasks) ?></b></span>
              <span><i class="performance-dot performance-dot--gold"></i>In progress <b><?= number_format($pendingTasks) ?></b></span>
              <span><i class="performance-dot performance-dot--red"></i>Overdue <b><?= number_format($overdueTasks) ?></b></span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="performance-insight">
      <div class="performance-insight-number"><?= $overallRate ?>%</div>
      <div class="performance-insight-copy">
        <span class="performance-kicker">Portfolio health</span>
        <h2>Workload completion across <?= $committeeCount ?> active <?= $committeeCount === 1 ? 'committee' : 'committees' ?></h2>
        <p><?= $overdueTasks > 0 ? 'There are ' . number_format($overdueTasks) . ' overdue task(s) that need attention.' : 'No overdue tasks are currently recorded.' ?></p>
      </div>
      <div class="performance-segmented-progress" aria-label="Task status distribution">
        <span class="performance-progress-complete" style="width: <?= $totalTasks > 0 ? ($completedTasks / $totalTasks) * 100 : 0 ?>%"></span>
        <span class="performance-progress-pending" style="width: <?= $totalTasks > 0 ? ($pendingTasks / $totalTasks) * 100 : 0 ?>%"></span>
        <span class="performance-progress-overdue" style="width: <?= $totalTasks > 0 ? ($overdueTasks / $totalTasks) * 100 : 0 ?>%"></span>
      </div>
    </div>
  </div>


<!--

  <div class="card mb-3">
    <div class="card-header">Committee KPIs</div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead>
          <tr>
            <th>Committee</th>
            <th>Total Tasks</th>
            <th>Completed</th>
            <th>Pending</th>
            <th>Overdue</th>
            <th>Completion Rate</th>
            <?php if (canManage()): ?><th class="text-end">Actions</th><?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($kpis)): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">No active committees yet.</td></tr>
          <?php else: foreach ($kpis as $k):
            $rateColor = $k['completion_rate'] >= 75 ? 'success' : ($k['completion_rate'] >= 40 ? 'warning' : 'danger');
          ?>
            <tr>
              <td><a href="<?= e(APP_URL) ?>/modules/committees/view.php?id=<?= (int)$k['committee_id'] ?>"><?= e($k['committee_name']) ?></a></td>
              <td><?= (int)$k['total_tasks'] ?></td>
              <td><?= (int)$k['completed_tasks'] ?></td>
              <td><?= (int)$k['pending_tasks'] ?></td>
              <td><?= (int)$k['overdue_tasks'] ?></td>
              <td style="min-width:140px;">
                <div class="d-flex align-items-center gap-2">
                  <div class="progress flex-grow-1" style="height:8px;">
                    <div class="progress-bar bg-<?= $rateColor ?>" style="width: <?= $k['completion_rate'] ?>%"></div>
                  </div>
                  <span class="small fw-semibold"><?= $k['completion_rate'] ?>%</span>
                </div>
              </td>
              <?php if (canManage()): ?>
                <td class="text-end">
                  <button type="button" class="btn btn-sm btn-outline-primary btn-snapshot"
                          data-id="<?= (int)$k['committee_id'] ?>" title="Save snapshot for reporting">
                    <i class="bi bi-camera"></i> Snapshot
                  </button>
                </td>
              <?php endif; ?>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

              -->

  




<?php
$extraJs = [APP_URL . '/assets/js/performance.js'];
include __DIR__ . '/../../layouts/footer.php';
?>
<script>
new Chart(document.getElementById('completionRateChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_column($kpis, 'committee_name')) ?>,
    datasets: [{
      label: 'Completion Rate (%)',
      data: <?= json_encode(array_column($kpis, 'completion_rate')) ?>,
      backgroundColor: '#6366f1',
      borderRadius: 5,
      barThickness: 14
    }]
  },
  options: {
    indexAxis: 'y',
    scales: {
      x: { beginAtZero: true, max: 100, grid: { color: '#ececf0' }, ticks: { callback: value => value + '%' } },
      y: { grid: { display: false } }
    },
    plugins: {
      legend: { display: false },
      tooltip: { callbacks: { label: context => ' ' + context.raw + '%' } }
    },
    responsive: true,
    maintainAspectRatio: false
  }
});

new Chart(document.getElementById('taskMixChart'), {
  type: 'doughnut',
  data: {
    labels: ['Completed', 'Pending / In Progress', 'Overdue'],
    datasets: [{
      data: [
        <?= (int)array_sum(array_column($kpis, 'completed_tasks')) ?>,
        <?= (int)array_sum(array_column($kpis, 'pending_tasks')) ?>,
        <?= (int)array_sum(array_column($kpis, 'overdue_tasks')) ?>
      ],
      backgroundColor: ['#27a644', '#e0a21a', '#eb5757'],
      borderWidth: 4,
      borderColor: '#ffffff'
    }]
  },
  options: {
    plugins: {
      legend: { display: false },
      tooltip: { callbacks: { label: context => ' ' + context.label + ': ' + context.raw } }
    },
    cutout: '76%',
    responsive: true,
    maintainAspectRatio: false
  }
});
</script>
